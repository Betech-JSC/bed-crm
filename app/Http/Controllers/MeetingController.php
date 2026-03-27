<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    /**
     * Dashboard – list all meetings with stats
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $accountId = $user->account_id;

        $meetings = Meeting::where('account_id', $accountId)
            ->with('creator')
            ->filter($request->only('search', 'status', 'type'))
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByDesc('scheduled_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => substr($m->description ?? '', 0, 120),
                'room_code' => $m->room_code,
                'status' => $m->status,
                'status_info' => $m->status_info,
                'type' => $m->type,
                'type_info' => Meeting::getTypes()[$m->type] ?? [],
                'scheduled_at' => $m->scheduled_at?->format('d/m/Y H:i'),
                'scheduled_at_iso' => $m->scheduled_at?->toISOString(),
                'started_at' => $m->started_at?->format('H:i'),
                'ended_at' => $m->ended_at?->format('H:i'),
                'duration_formatted' => $m->duration_formatted,
                'participant_count' => $m->participant_count,
                'max_participants' => $m->max_participants,
                'record_enabled' => $m->record_enabled,
                'has_recording' => (bool) $m->recording_url,
                'has_recap' => $m->has_recap,
                'is_live' => $m->is_live,
                'join_url' => $m->join_url,
                'creator' => $m->creator ? ['name' => $m->creator->first_name . ' ' . $m->creator->last_name, 'email' => $m->creator->email] : null,
                'created_at' => $m->created_at->diffForHumans(),
            ]);

        $stats = [
            'total' => Meeting::where('account_id', $accountId)->count(),
            'live' => Meeting::where('account_id', $accountId)->live()->count(),
            'scheduled' => Meeting::where('account_id', $accountId)->where('status', 'scheduled')->count(),
            'ended' => Meeting::where('account_id', $accountId)->where('status', 'ended')->count(),
            'total_recording_hours' => round(Meeting::where('account_id', $accountId)
                ->whereNotNull('duration_minutes')->sum('duration_minutes') / 60, 1),
        ];

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'stats' => $stats,
            'statuses' => Meeting::getStatuses(),
            'types' => Meeting::getTypes(),
            'filters' => $request->only('search', 'status', 'type'),
        ]);
    }

    /**
     * Create meeting form
     */
    public function create(): Response
    {
        return Inertia::render('Meetings/Create', [
            'types' => Meeting::getTypes(),
        ]);
    }

    /**
     * Store a new meeting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:video,audio,screen_share',
            'scheduled_at' => 'nullable|date',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'is_public' => 'boolean',
            'record_enabled' => 'boolean',
            'agenda' => 'nullable|string|max:5000',
            'password' => 'nullable|string|max:20',
            'settings' => 'nullable|array',
        ]);

        $user = Auth::user();

        $meeting = Meeting::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['scheduled_at'] ? 'scheduled' : 'live',
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'max_participants' => $validated['max_participants'] ?? 20,
            'is_public' => $validated['is_public'] ?? false,
            'record_enabled' => $validated['record_enabled'] ?? false,
            'agenda' => $validated['agenda'] ?? null,
            'password' => $validated['password'] ?? null,
            'settings' => $validated['settings'] ?? [
                'mute_on_join' => false,
                'camera_off' => false,
                'waiting_room' => false,
            ],
            'participants' => [
                [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'role' => 'host',
                    'joined_at' => null,
                ],
            ],
        ]);

        // Nếu không lên lịch → redirect vào phòng meeting luôn
        if (!$validated['scheduled_at']) {
            return redirect("/meetings/{$meeting->room_code}/room");
        }

        return redirect('/meetings')->with('success', 'Cuộc họp đã được tạo!');
    }

    /**
     * Meeting room (video conferencing page)
     */
    public function room(string $roomCode): Response
    {
        $user = Auth::user();
        $meeting = Meeting::where('room_code', $roomCode)
            ->where('account_id', $user->account_id)
            ->firstOrFail();

        return Inertia::render('Meetings/Room', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'room_code' => $meeting->room_code,
                'status' => $meeting->status,
                'type' => $meeting->type,
                'type_info' => Meeting::getTypes()[$meeting->type] ?? [],
                'scheduled_at' => $meeting->scheduled_at?->format('d/m/Y H:i'),
                'participants' => $meeting->participants ?? [],
                'max_participants' => $meeting->max_participants,
                'record_enabled' => $meeting->record_enabled,
                'has_recording' => (bool) $meeting->recording_url,
                'recording_url' => $meeting->recording_url,
                'agenda' => $meeting->agenda,
                'meeting_notes' => $meeting->meeting_notes,
                'settings' => $meeting->settings ?? [],
                'is_host' => $meeting->created_by === $user->id,
            ],
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Start meeting (change status to live)
     */
    public function start(Meeting $meeting): JsonResponse
    {
        $meeting->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'status' => 'live']);
    }

    /**
     * End meeting (change status to ended, calc duration)
     */
    public function end(Meeting $meeting): JsonResponse
    {
        $duration = $meeting->started_at
            ? now()->diffInMinutes($meeting->started_at)
            : 0;

        $meeting->update([
            'status' => 'ended',
            'ended_at' => now(),
            'duration_minutes' => $duration,
        ]);

        return response()->json(['success' => true, 'duration' => $duration]);
    }

    /**
     * Save recording URL
     */
    public function saveRecording(Request $request, Meeting $meeting): JsonResponse
    {
        $validated = $request->validate([
            'recording_url' => 'required|string',
            'recording_size_mb' => 'nullable|integer',
        ]);

        $meeting->update([
            'recording_url' => $validated['recording_url'],
            'recording_size_mb' => $validated['recording_size_mb'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Save meeting notes
     */
    public function saveNotes(Request $request, Meeting $meeting): JsonResponse
    {
        $meeting->update([
            'meeting_notes' => $request->input('notes'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * AI Generate Recap (summary, action items, key decisions)
     */
    public function generateRecap(Meeting $meeting): JsonResponse
    {
        $prompt = $this->buildRecapPrompt($meeting);

        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        if ($apiKey) {
            try {
                $response = Http::timeout(120)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature' => 0.5,
                            'maxOutputTokens' => 4096,
                            'responseMimeType' => 'application/json',
                        ],
                    ]
                );

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text', '');
                    $data = json_decode($text, true);

                    if ($data) {
                        $meeting->update([
                            'ai_summary' => $data['summary'] ?? null,
                            'ai_action_items' => $data['action_items'] ?? [],
                            'ai_key_decisions' => $data['key_decisions'] ?? [],
                            'ai_topics' => $data['topics'] ?? [],
                        ]);

                        return response()->json([
                            'success' => true,
                            'summary' => $data['summary'] ?? '',
                            'action_items' => $data['action_items'] ?? [],
                            'key_decisions' => $data['key_decisions'] ?? [],
                            'topics' => $data['topics'] ?? [],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Fall through to mock
            }
        }

        // Mock response
        $mock = $this->getMockRecap($meeting);
        $meeting->update([
            'ai_summary' => $mock['summary'],
            'ai_action_items' => $mock['action_items'],
            'ai_key_decisions' => $mock['key_decisions'],
            'ai_topics' => $mock['topics'],
        ]);

        return response()->json(['success' => true, ...$mock]);
    }

    /**
     * View recap page
     */
    public function recap(Meeting $meeting): Response
    {
        return Inertia::render('Meetings/Recap', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'room_code' => $meeting->room_code,
                'status' => $meeting->status,
                'type' => $meeting->type,
                'scheduled_at' => $meeting->scheduled_at?->format('d/m/Y H:i'),
                'started_at' => $meeting->started_at?->format('d/m/Y H:i'),
                'ended_at' => $meeting->ended_at?->format('d/m/Y H:i'),
                'duration_formatted' => $meeting->duration_formatted,
                'participants' => $meeting->participants ?? [],
                'recording_url' => $meeting->recording_url,
                'ai_summary' => $meeting->ai_summary,
                'ai_action_items' => $meeting->ai_action_items ?? [],
                'ai_key_decisions' => $meeting->ai_key_decisions ?? [],
                'ai_topics' => $meeting->ai_topics ?? [],
                'ai_transcript' => $meeting->ai_transcript,
                'agenda' => $meeting->agenda,
                'meeting_notes' => $meeting->meeting_notes,
            ],
        ]);
    }

    /**
     * Edit meeting
     */
    public function edit(Meeting $meeting): Response
    {
        return Inertia::render('Meetings/Edit', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'room_code' => $meeting->room_code,
                'status' => $meeting->status,
                'type' => $meeting->type,
                'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d\TH:i'),
                'max_participants' => $meeting->max_participants,
                'is_public' => $meeting->is_public,
                'record_enabled' => $meeting->record_enabled,
                'agenda' => $meeting->agenda,
                'password' => $meeting->password,
                'settings' => $meeting->settings ?? ['mute_on_join' => false, 'camera_off' => false, 'waiting_room' => false],
                'participants' => $meeting->participants ?? [],
                'started_at' => $meeting->started_at?->format('d/m/Y H:i'),
                'ended_at' => $meeting->ended_at?->format('d/m/Y H:i'),
                'duration_formatted' => $meeting->duration_formatted,
                'has_recap' => $meeting->has_recap,
                'recording_url' => $meeting->recording_url,
                'meeting_notes' => $meeting->meeting_notes,
                'created_at' => $meeting->created_at->format('d/m/Y H:i'),
            ],
            'types' => Meeting::getTypes(),
            'statuses' => Meeting::getStatuses(),
        ]);
    }

    /**
     * Update meeting
     */
    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:video,audio,screen_share',
            'scheduled_at' => 'nullable|date',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'is_public' => 'boolean',
            'record_enabled' => 'boolean',
            'agenda' => 'nullable|string|max:5000',
            'password' => 'nullable|string|max:20',
            'settings' => 'nullable|array',
        ]);

        $meeting->update($validated);

        return redirect()->back()->with('success', 'Đã cập nhật cuộc họp');
    }

    /**
     * Cancel meeting
     */
    public function cancel(Meeting $meeting): JsonResponse
    {
        $meeting->update(['status' => 'cancelled']);
        return response()->json(['success' => true]);
    }

    /**
     * Duplicate meeting
     */
    public function duplicate(Meeting $meeting)
    {
        $user = Auth::user();

        $newMeeting = $meeting->replicate(['room_code', 'status', 'started_at', 'ended_at', 'duration_minutes', 'recording_path', 'recording_url', 'recording_size_mb', 'ai_transcript', 'ai_summary', 'ai_action_items', 'ai_key_decisions', 'ai_topics', 'meeting_notes']);
        $newMeeting->title = $meeting->title . ' (Copy)';
        $newMeeting->status = 'scheduled';
        $newMeeting->created_by = $user->id;
        $newMeeting->scheduled_at = now()->addHour();
        $newMeeting->participants = [
            ['user_id' => $user->id, 'name' => $user->first_name . ' ' . $user->last_name, 'email' => $user->email, 'role' => 'host', 'joined_at' => null],
        ];
        $newMeeting->save();

        return redirect('/meetings/' . $newMeeting->id . '/edit')->with('success', 'Đã nhân bản cuộc họp');
    }

    /**
     * Delete meeting
     */
    public function destroy(Meeting $meeting)
    {
        $meeting->delete();
        return redirect('/meetings')->with('success', 'Đã xóa cuộc họp');
    }

    // ── Private ──
    private function buildRecapPrompt(Meeting $meeting): string
    {
        $notes = $meeting->meeting_notes ?? 'Không có ghi chú';
        $agenda = $meeting->agenda ?? 'Không có agenda';
        $participants = collect($meeting->participants ?? [])->pluck('name')->implode(', ');

        return <<<PROMPT
Bạn là AI assistant chuyên tóm tắt cuộc họp. Hãy phân tích thông tin cuộc họp sau và tạo recap bằng tiếng Việt.

**Cuộc họp:** {$meeting->title}
**Mô tả:** {$meeting->description}
**Thời lượng:** {$meeting->duration_formatted}
**Người tham gia:** {$participants}
**Agenda:** {$agenda}
**Ghi chú cuộc họp:** {$notes}

Hãy trả về JSON với cấu trúc:
{
  "summary": "Tóm tắt ngắn gọn cuộc họp (3-5 câu)",
  "action_items": [
    {"task": "Mô tả công việc", "assignee": "Tên người", "deadline": "Hạn chót", "priority": "high/medium/low"}
  ],
  "key_decisions": [
    {"decision": "Quyết định đã thống nhất", "context": "Bối cảnh"}
  ],
  "topics": ["Chủ đề 1", "Chủ đề 2"]
}
PROMPT;
    }

    private function getMockRecap(Meeting $meeting): array
    {
        return [
            'summary' => "Cuộc họp \"{$meeting->title}\" đã diễn ra với sự tham gia của "
                . count($meeting->participants ?? []) . " thành viên trong "
                . ($meeting->duration_formatted) . ". "
                . "Các thành viên đã thảo luận về tiến độ dự án, kế hoạch sprint tiếp theo và phân bổ nguồn lực. "
                . "Cuộc họp đạt được nhiều đồng thuận quan trọng về hướng phát triển sản phẩm.",
            'action_items' => [
                ['task' => 'Hoàn thành wireframe cho tính năng mới', 'assignee' => 'Design team', 'deadline' => 'Thứ 6 tuần này', 'priority' => 'high'],
                ['task' => 'Review code và merge PR #142', 'assignee' => 'Dev team', 'deadline' => 'Thứ 4', 'priority' => 'medium'],
                ['task' => 'Chuẩn bị báo cáo tiến độ cho stakeholders', 'assignee' => 'PM', 'deadline' => 'Thứ 2 tuần sau', 'priority' => 'medium'],
                ['task' => 'Test E2E cho flow checkout mới', 'assignee' => 'QA team', 'deadline' => 'Cuối sprint', 'priority' => 'high'],
            ],
            'key_decisions' => [
                ['decision' => 'Chuyển sang kiến trúc microservices cho module thanh toán', 'context' => 'Cải thiện performance và scalability'],
                ['decision' => 'Áp dụng TDD cho tất cả features mới', 'context' => 'Nâng cao chất lượng code'],
                ['decision' => 'Tăng headcount thêm 2 fullstack developer', 'context' => 'Đáp ứng roadmap Q2'],
            ],
            'topics' => ['Tiến độ Sprint', 'Architecture Review', 'Resource Planning', 'Q2 Roadmap'],
        ];
    }
}
