<?php

namespace App\Http\Controllers;

use App\Models\CultureValue;
use App\Models\CultureInitiative;
use App\Models\CultureSurvey;
use App\Models\CultureSurveyResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CultureController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $userId = auth()->id();

        $values = CultureValue::where('account_id', $accountId)
            ->orderBy('sort_order')->get();

        $initiatives = CultureInitiative::where('account_id', $accountId)
            ->orderByRaw("FIELD(status, 'in_progress', 'planned', 'completed', 'cancelled')")
            ->latest()->get()->map(fn($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'category' => $i->category,
                'status' => $i->status,
                'start_date' => $i->start_date?->format('Y-m-d'),
                'end_date' => $i->end_date?->format('Y-m-d'),
                'assigned_to' => $i->assigned_to,
                'impact' => $i->impact,
                'created_at' => $i->created_at->format('d/m/Y'),
            ]);

        $surveys = CultureSurvey::where('account_id', $accountId)
            ->withCount('responses')
            ->latest()->get()->map(function ($s) use ($userId) {
                $userResponded = $s->responses()->where('user_id', $userId)->exists();
                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'description' => $s->description,
                    'questions' => $s->questions,
                    'status' => $s->status,
                    'anonymous' => $s->anonymous,
                    'responses_count' => $s->responses_count,
                    'user_responded' => $userResponded,
                    'created_at' => $s->created_at->format('d/m/Y'),
                ];
            });

        // Culture health stats
        $totalInitiatives = $initiatives->count();
        $completedInitiatives = $initiatives->where('status', 'completed')->count();
        $activeSurveys = $surveys->where('status', 'active')->count();
        $totalResponses = $surveys->sum('responses_count');

        return Inertia::render('Culture/Index', [
            'values' => $values,
            'initiatives' => $initiatives,
            'surveys' => $surveys,
            'stats' => [
                'values_count' => $values->count(),
                'total_initiatives' => $totalInitiatives,
                'completed_initiatives' => $completedInitiatives,
                'active_surveys' => $activeSurveys,
                'total_responses' => $totalResponses,
            ],
            'filters' => $request->only(['tab']),
        ]);
    }

    // ── Values CRUD ──

    public function storeValue(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'behaviors' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);
        $v['account_id'] = auth()->user()->account_id;
        CultureValue::create($v);
        return back()->with('success', 'Đã tạo giá trị cốt lõi.');
    }

    public function updateValue(Request $request, CultureValue $value)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'behaviors' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);
        $value->update($v);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function deleteValue(CultureValue $value)
    {
        $value->delete();
        return back()->with('success', 'Đã xóa giá trị.');
    }

    // ── Initiatives CRUD ──

    public function storeInitiative(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:30',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:255',
            'impact' => 'nullable|string|max:30',
        ]);
        $v['account_id'] = auth()->user()->account_id;
        $v['created_by'] = auth()->id();
        CultureInitiative::create($v);
        return back()->with('success', 'Đã tạo sáng kiến.');
    }

    public function updateInitiative(Request $request, CultureInitiative $initiative)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:30',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:255',
            'impact' => 'nullable|string|max:30',
        ]);
        $initiative->update($v);
        return back()->with('success', 'Đã cập nhật sáng kiến.');
    }

    public function deleteInitiative(CultureInitiative $initiative)
    {
        $initiative->delete();
        return back()->with('success', 'Đã xóa sáng kiến.');
    }

    // ── Surveys CRUD ──

    public function storeSurvey(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.q' => 'required|string',
            'questions.*.type' => 'required|in:rating,text,choice',
            'anonymous' => 'boolean',
        ]);
        $v['account_id'] = auth()->user()->account_id;
        $v['created_by'] = auth()->id();
        $v['status'] = 'draft';
        CultureSurvey::create($v);
        return back()->with('success', 'Đã tạo khảo sát.');
    }

    public function updateSurvey(Request $request, CultureSurvey $survey)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'status' => 'nullable|in:draft,active,closed',
            'anonymous' => 'boolean',
        ]);
        $survey->update($v);
        return back()->with('success', 'Đã cập nhật khảo sát.');
    }

    public function deleteSurvey(CultureSurvey $survey)
    {
        $survey->delete();
        return back()->with('success', 'Đã xóa khảo sát.');
    }

    public function submitSurvey(Request $request, CultureSurvey $survey)
    {
        $request->validate(['answers' => 'required|array']);

        $existing = CultureSurveyResponse::where('survey_id', $survey->id)
            ->where('user_id', auth()->id())->first();
        if ($existing) {
            return back()->with('warning', 'Bạn đã trả lời khảo sát này.');
        }

        CultureSurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $survey->anonymous ? null : auth()->id(),
            'answers' => $request->answers,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Cảm ơn bạn đã tham gia khảo sát!');
    }

    public function surveyResults(CultureSurvey $survey)
    {
        $responses = $survey->responses()->get();
        $questions = $survey->questions;
        $results = [];

        foreach ($questions as $qi => $q) {
            $qResult = ['question' => $q['q'], 'type' => $q['type'], 'answers' => []];
            foreach ($responses as $r) {
                if (isset($r->answers[$qi])) {
                    $qResult['answers'][] = $r->answers[$qi];
                }
            }

            if ($q['type'] === 'rating') {
                $nums = array_filter($qResult['answers'], 'is_numeric');
                $qResult['average'] = count($nums) > 0 ? round(array_sum($nums) / count($nums), 1) : 0;
            }

            $results[] = $qResult;
        }

        return response()->json([
            'survey' => $survey->only('id', 'title', 'questions'),
            'total_responses' => $responses->count(),
            'results' => $results,
        ]);
    }

    // ── Seed Defaults ──

    public function seedDefaults()
    {
        $accountId = auth()->user()->account_id;
        if (CultureValue::where('account_id', $accountId)->exists()) {
            return back()->with('info', 'Dữ liệu mẫu đã tồn tại.');
        }

        $valuesData = [
            ['title' => 'Chính trực', 'description' => 'Luôn trung thực, minh bạch trong mọi hành động', 'icon' => 'pi pi-shield', 'color' => '#3b82f6', 'behaviors' => ['Nói đúng sự thật dù khó khăn', 'Giữ lời hứa với khách hàng và đồng nghiệp', 'Chịu trách nhiệm khi sai sót']],
            ['title' => 'Đổi mới sáng tạo', 'description' => 'Không ngừng tìm kiếm giải pháp mới và cải tiến', 'icon' => 'pi pi-sparkles', 'color' => '#f59e0b', 'behaviors' => ['Đề xuất ít nhất 1 ý tưởng cải tiến mỗi tháng', 'Không sợ thất bại khi thử nghiệm', 'Học hỏi từ các ngành khác']],
            ['title' => 'Khách hàng là trung tâm', 'description' => 'Mọi quyết định đều hướng đến giá trị cho khách hàng', 'icon' => 'pi pi-heart', 'color' => '#ec4899', 'behaviors' => ['Phản hồi khách hàng trong 24h', 'Chủ động hỏi feedback sau mỗi dự án', 'Đặt mình vào vị trí khách hàng']],
            ['title' => 'Tinh thần đội nhóm', 'description' => 'Hợp tác, hỗ trợ và cùng nhau phát triển', 'icon' => 'pi pi-users', 'color' => '#10b981', 'behaviors' => ['Chia sẻ kiến thức với đồng nghiệp', 'Chủ động hỗ trợ khi team member gặp khó', 'Celebrate thành công cùng nhau']],
            ['title' => 'Cam kết chất lượng', 'description' => 'Luôn nỗ lực mang đến sản phẩm và dịch vụ tốt nhất', 'icon' => 'pi pi-check-circle', 'color' => '#8b5cf6', 'behaviors' => ['Review kỹ trước khi bàn giao', 'Không thỏa hiệp với chất lượng', 'Liên tục nâng cao tiêu chuẩn']],
        ];

        foreach ($valuesData as $i => $vd) {
            $vd['account_id'] = $accountId;
            $vd['sort_order'] = $i;
            CultureValue::create($vd);
        }

        $initData = [
            ['title' => 'Culture Friday — Chia sẻ kiến thức', 'description' => 'Mỗi thứ 6 cuối tháng, 1 thành viên chia sẻ chuyên đề', 'category' => 'learning', 'status' => 'in_progress', 'impact' => 'high'],
            ['title' => 'Buddy Program cho nhân viên mới', 'description' => 'Ghép cặp mentor cho mỗi nhân viên mới trong 3 tháng đầu', 'category' => 'onboarding', 'status' => 'in_progress', 'impact' => 'high'],
            ['title' => 'Hackathon quý', 'description' => '1 ngày mỗi quý để team thử nghiệm ý tưởng sáng tạo', 'category' => 'innovation', 'status' => 'planned', 'impact' => 'medium'],
            ['title' => 'Employee Recognition Wall', 'description' => 'Bảng vinh danh nhân viên xuất sắc hàng tháng', 'category' => 'recognition', 'status' => 'completed', 'impact' => 'medium'],
            ['title' => 'Team Building quý', 'description' => 'Hoạt động gắn kết đội nhóm mỗi quý', 'category' => 'team_building', 'status' => 'planned', 'impact' => 'high'],
        ];

        foreach ($initData as $init) {
            $init['account_id'] = $accountId;
            $init['created_by'] = auth()->id();
            CultureInitiative::create($init);
        }

        // Sample survey
        CultureSurvey::create([
            'account_id' => $accountId,
            'title' => 'Khảo sát văn hóa Q1/2026',
            'description' => 'Đánh giá mức độ hài lòng về văn hóa công ty',
            'status' => 'active',
            'anonymous' => true,
            'created_by' => auth()->id(),
            'questions' => [
                ['q' => 'Bạn cảm thấy được tôn trọng tại nơi làm việc?', 'type' => 'rating'],
                ['q' => 'Bạn hiểu rõ giá trị cốt lõi của công ty?', 'type' => 'rating'],
                ['q' => 'Bạn được khuyến khích đổi mới sáng tạo?', 'type' => 'rating'],
                ['q' => 'Đồng nghiệp sẵn sàng hỗ trợ bạn?', 'type' => 'rating'],
                ['q' => 'Bạn muốn cải thiện điều gì trong văn hóa công ty?', 'type' => 'text'],
            ],
        ]);

        return back()->with('success', 'Đã tạo dữ liệu mẫu văn hóa doanh nghiệp.');
    }
}
