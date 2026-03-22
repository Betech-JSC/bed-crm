<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesPipeline\StoreSalesPipelineRequest;
use App\Http\Requests\SalesPipeline\UpdateSalesPipelineRequest;
use App\Models\Lead;
use App\Models\SalesChannel;
use App\Models\SalesPipeline;
use App\Models\User;
use App\Services\AiAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SalesPipelineController extends Controller
{
    /**
     * Display the kanban pipeline board.
     */
    public function index(Request $request): Response
    {
        $account = Auth::user()->account;
        $channels = $account->salesChannels()->active()->ordered()->get();

        // Seed default channels if none exist
        if ($channels->isEmpty()) {
            $this->seedDefaultChannels($account->id);
            $channels = $account->salesChannels()->active()->ordered()->get();
        }

        // Active channel (from request or first channel)
        $activeChannelId = $request->get('channel_id', $channels->first()?->id);
        $activeChannel = $channels->firstWhere('id', $activeChannelId) ?? $channels->first();

        // Get pipeline items for this channel
        $pipelines = $account->salesPipelines()
            ->where('channel_id', $activeChannel?->id)
            ->with(['lead:id,name,company', 'deal:id,title,value', 'assignedUser:id,first_name,last_name', 'channel:id,name,color,icon'])
            ->open()
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by stage based on channel stages
        $pipelinesByStage = [];
        $openStages = $activeChannel ? $activeChannel->open_stages : [];
        foreach ($openStages as $stage) {
            $pipelinesByStage[$stage['key']] = $pipelines
                ->where('stage', $stage['key'])
                ->map(fn ($p) => $this->formatPipelineCard($p))
                ->values()
                ->toArray();
        }

        // Stats per channel
        $stats = [
            'total_open' => $pipelines->count(),
            'closed_won' => $account->salesPipelines()
                ->where('channel_id', $activeChannel?->id)
                ->where(function ($q) {
                    $q->where('stage', 'closed_won')
                      ->orWhere('stage', 'completed');
                })->count(),
            'closed_lost' => $account->salesPipelines()
                ->where('channel_id', $activeChannel?->id)
                ->where(function ($q) {
                    $q->where('stage', 'closed_lost')
                      ->orWhere('stage', 'cancelled');
                })->count(),
            'total_value' => $pipelines->sum('quote_amount'),
        ];

        // Channel stats (count per channel)
        $channelStats = $channels->map(fn ($ch) => [
            'id' => $ch->id,
            'name' => $ch->name,
            'icon' => $ch->icon,
            'color' => $ch->color,
            'open_count' => $account->salesPipelines()->where('channel_id', $ch->id)->open()->count(),
        ]);

        return Inertia::render('SalesPipeline/Index', [
            'channels' => $channels->map(fn ($ch) => [
                'id' => $ch->id,
                'name' => $ch->name,
                'slug' => $ch->slug,
                'icon' => $ch->icon,
                'color' => $ch->color,
                'description' => $ch->description,
                'stages' => $ch->stages,
                'open_stages' => $ch->open_stages,
                'closed_stages' => $ch->closed_stages,
                'is_default' => $ch->is_default,
            ]),
            'activeChannelId' => $activeChannel?->id,
            'pipelinesByStage' => $pipelinesByStage,
            'stages' => $openStages,
            'allStages' => $activeChannel?->stages ?? [],
            'priorities' => SalesPipeline::getPriorities(),
            'salesUsers' => $this->getSalesUsers(),
            'stats' => $stats,
            'channelStats' => $channelStats,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        return Inertia::render('SalesPipeline/Create', [
            'leads' => Auth::user()->account->leads()
                ->orderBy('name')
                ->get()
                ->map(fn ($lead) => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'company' => $lead->company,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                ]),
            'priorities' => SalesPipeline::getPriorities(),
            'socialChannels' => SalesPipeline::getSocialChannels(),
            'salesUsers' => $this->getSalesUsers(),
            'auditTemplate' => SalesPipeline::getAuditTemplate(),
        ]);
    }

    /**
     * Store a new pipeline entry.
     */
    public function store(StoreSalesPipelineRequest $request): RedirectResponse
    {
        $channelId = $request->input('channel_id');
        $channel = SalesChannel::find($channelId);

        // Use first open stage of the channel
        $firstStage = $channel ? ($channel->open_stages[0]['key'] ?? 'new') : SalesPipeline::STAGE_AUDIT;

        Auth::user()->account->salesPipelines()->create(array_merge(
            $request->validated(),
            [
                'stage' => $request->input('stage', $firstStage),
                'channel_id' => $channelId,
                'stage_changed_at' => now(),
                'stage_history' => [['stage' => $firstStage, 'at' => now()->toISOString(), 'by' => Auth::id()]],
            ]
        ));

        return Redirect::route('sales-pipeline', ['channel_id' => $channelId])
            ->with('success', 'Đã tạo quy trình bán hàng mới.');
    }

    /**
     * Show edit page with tabs.
     */
    public function edit(SalesPipeline $pipeline): Response
    {
        $pipeline->load('channel');

        return Inertia::render('SalesPipeline/Edit', [
            'pipeline' => [
                'id' => $pipeline->id,
                'channel_id' => $pipeline->channel_id,
                'company_name' => $pipeline->company_name,
                'contact_name' => $pipeline->contact_name,
                'phone' => $pipeline->phone,
                'email' => $pipeline->email,
                'website_url' => $pipeline->website_url,
                'stage' => $pipeline->stage,
                'priority' => $pipeline->priority,
                'priority_severity' => $pipeline->priority_severity,
                'win_probability' => $pipeline->win_probability,
                'social_channel' => $pipeline->social_channel,
                'social_account' => $pipeline->social_account,
                'audit_data' => $pipeline->audit_data ?? SalesPipeline::getAuditTemplate(),
                'audit_score' => $pipeline->audit_score,
                'proposal_summary' => $pipeline->proposal_summary,
                'proposal_file_path' => $pipeline->proposal_file_path,
                'discussion_notes' => $pipeline->discussion_notes,
                'quote_amount' => $pipeline->quote_amount,
                'quote_valid_until' => $pipeline->quote_valid_until?->format('Y-m-d'),
                'quote_file_path' => $pipeline->quote_file_path,
                'quote_notes' => $pipeline->quote_notes,
                'close_date' => $pipeline->close_date?->format('Y-m-d'),
                'close_notes' => $pipeline->close_notes,
                'lost_reason' => $pipeline->lost_reason,
                'notes' => $pipeline->notes,
                'lead_id' => $pipeline->lead_id,
                'deal_id' => $pipeline->deal_id,
                'is_open' => $pipeline->is_open,
                'stage_history' => $pipeline->stage_history ?? [],
                'deleted_at' => $pipeline->deleted_at,
                'channel' => $pipeline->channel ? [
                    'id' => $pipeline->channel->id,
                    'name' => $pipeline->channel->name,
                    'icon' => $pipeline->channel->icon,
                    'color' => $pipeline->channel->color,
                    'stages' => $pipeline->channel->stages,
                ] : null,
                'lead' => $pipeline->lead ? [
                    'id' => $pipeline->lead->id,
                    'name' => $pipeline->lead->name,
                    'company' => $pipeline->lead->company,
                ] : null,
                'deal' => $pipeline->deal ? [
                    'id' => $pipeline->deal->id,
                    'title' => $pipeline->deal->title,
                    'value' => $pipeline->deal->value,
                ] : null,
            ],
            'activities' => $pipeline->activities()
                ->with('user:id,first_name,last_name')
                ->orderBy('date', 'desc')
                ->get()
                ->map(fn ($activity) => [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'date' => $activity->date->toISOString(),
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                    ] : null,
                ]),
            'stages' => $pipeline->channel?->stages ?? SalesPipeline::getStages(),
            'allStages' => $pipeline->channel?->stages ?? SalesPipeline::getStages(),
            'priorities' => SalesPipeline::getPriorities(),
            'socialChannels' => SalesPipeline::getSocialChannels(),
            'salesUsers' => $this->getSalesUsers(),
            'leads' => Auth::user()->account->leads()
                ->orderBy('name')
                ->get()
                ->map(fn ($lead) => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'company' => $lead->company,
                ]),
        ]);
    }

    /**
     * Update pipeline entry.
     */
    public function update(UpdateSalesPipelineRequest $request, SalesPipeline $pipeline): RedirectResponse
    {
        $data = $request->validated();

        // Track stage changes
        if (isset($data['stage']) && $data['stage'] !== $pipeline->stage) {
            $history = $pipeline->stage_history ?? [];
            $history[] = [
                'from' => $pipeline->stage,
                'to' => $data['stage'],
                'at' => now()->toISOString(),
                'by' => Auth::id(),
            ];
            $data['stage_history'] = $history;
            $data['stage_changed_at'] = now();
        }

        $pipeline->update($data);

        return Redirect::back()->with('success', 'Đã cập nhật quy trình.');
    }

    /**
     * Update pipeline stage (drag-drop kanban).
     */
    public function updateStage(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'stage' => ['required', 'string'],
        ]);

        $history = $pipeline->stage_history ?? [];
        $history[] = [
            'from' => $pipeline->stage,
            'to' => $request->stage,
            'at' => now()->toISOString(),
            'by' => Auth::id(),
        ];

        $pipeline->update([
            'stage' => $request->stage,
            'stage_changed_at' => now(),
            'stage_history' => $history,
        ]);

        return Redirect::back()->with('success', 'Đã chuyển giai đoạn.');
    }

    /**
     * Save audit data.
     */
    public function storeAudit(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate(['audit_data' => ['required', 'array']]);
        $pipeline->update(['audit_data' => $request->audit_data]);
        return Redirect::back()->with('success', 'Đã lưu kết quả audit.');
    }

    /**
     * Save quote data.
     */
    public function storeQuote(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'quote_amount' => ['required', 'numeric', 'min:0'],
            'quote_valid_until' => ['nullable', 'date'],
            'quote_notes' => ['nullable', 'string'],
        ]);
        $pipeline->update($request->only(['quote_amount', 'quote_valid_until', 'quote_notes']));
        return Redirect::back()->with('success', 'Đã lưu báo giá.');
    }

    /**
     * Mark pipeline as closed won.
     */
    public function closeWon(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'close_notes' => ['nullable', 'string'],
            'deal_id' => ['nullable', 'exists:deals,id'],
        ]);

        $history = $pipeline->stage_history ?? [];
        $history[] = ['from' => $pipeline->stage, 'to' => 'closed_won', 'at' => now()->toISOString(), 'by' => Auth::id()];

        $pipeline->update([
            'stage' => 'closed_won',
            'close_date' => now(),
            'close_notes' => $request->close_notes,
            'deal_id' => $request->deal_id,
            'stage_changed_at' => now(),
            'stage_history' => $history,
        ]);

        return Redirect::route('sales-pipeline', ['channel_id' => $pipeline->channel_id])
            ->with('success', 'Chốt deal thành công! 🎉');
    }

    /**
     * Mark pipeline as closed lost.
     */
    public function closeLost(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'lost_reason' => ['required', 'string'],
            'close_notes' => ['nullable', 'string'],
        ]);

        $history = $pipeline->stage_history ?? [];
        $history[] = ['from' => $pipeline->stage, 'to' => 'closed_lost', 'at' => now()->toISOString(), 'by' => Auth::id()];

        $pipeline->update([
            'stage' => 'closed_lost',
            'close_date' => now(),
            'lost_reason' => $request->lost_reason,
            'close_notes' => $request->close_notes,
            'stage_changed_at' => now(),
            'stage_history' => $history,
        ]);

        return Redirect::route('sales-pipeline', ['channel_id' => $pipeline->channel_id])
            ->with('success', 'Đã đóng deal.');
    }

    /**
     * Soft delete.
     */
    public function destroy(SalesPipeline $pipeline): RedirectResponse
    {
        $pipeline->delete();
        return Redirect::back()->with('success', 'Đã xóa.');
    }

    /**
     * Restore soft-deleted record.
     */
    public function restore(SalesPipeline $pipeline): RedirectResponse
    {
        $pipeline->restore();
        return Redirect::back()->with('success', 'Đã khôi phục.');
    }

    /**
     * AI-powered audit analysis.
     */
    public function aiAnalyze(Request $request, SalesPipeline $pipeline): JsonResponse
    {
        $service = app(AiAuditService::class);
        $auditData = $pipeline->audit_data ?? SalesPipeline::getAuditTemplate();
        $context = ['company_name' => $pipeline->company_name, 'website_url' => $pipeline->website_url];
        $result = $service->analyzeAudit($auditData, $context);
        $currentAudit = $pipeline->audit_data ?? [];
        $currentAudit['ai_analysis'] = $result;
        $pipeline->update(['audit_data' => $currentAudit]);
        return response()->json($result);
    }

    /**
     * AI-powered proposal generation.
     */
    public function aiProposal(Request $request, SalesPipeline $pipeline): JsonResponse
    {
        $service = app(AiAuditService::class);
        $auditData = $pipeline->audit_data ?? SalesPipeline::getAuditTemplate();
        $context = ['company_name' => $pipeline->company_name, 'website_url' => $pipeline->website_url];
        $result = $service->generateProposal($auditData, $context);
        return response()->json($result);
    }

    // ════════════════════════════════════════
    //  CHANNEL MANAGEMENT
    // ════════════════════════════════════════

    /**
     * Store a new sales channel.
     */
    public function storeChannel(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'stages' => 'required|array|min:2',
            'stages.*.key' => 'required|string',
            'stages.*.label' => 'required|string',
            'stages.*.color' => 'nullable|string',
        ]);

        $channel = Auth::user()->account->salesChannels()->create($request->only([
            'name', 'icon', 'color', 'description', 'stages',
        ]));

        return response()->json(['success' => true, 'channel' => $channel]);
    }

    /**
     * Update a sales channel.
     */
    public function updateChannel(Request $request, SalesChannel $channel): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'stages' => 'required|array|min:2',
            'stages.*.key' => 'required|string',
            'stages.*.label' => 'required|string',
            'stages.*.color' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $channel->update($request->only([
            'name', 'icon', 'color', 'description', 'stages', 'is_active',
        ]));

        return response()->json(['success' => true, 'channel' => $channel->fresh()]);
    }

    /**
     * Delete a sales channel.
     */
    public function destroyChannel(SalesChannel $channel): JsonResponse
    {
        // Move orphaned pipelines to null
        $channel->pipelines()->update(['channel_id' => null]);
        $channel->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Seed default channels.
     */
    private function seedDefaultChannels(int $accountId): void
    {
        $presets = SalesChannel::getPresets();
        foreach ($presets as $i => $preset) {
            SalesChannel::create(array_merge($preset, [
                'account_id' => $accountId,
                'sort_order' => $i,
                'is_default' => $i === 0,
            ]));
        }
    }

    /**
     * Get sales users.
     */
    private function getSalesUsers(): \Illuminate\Support\Collection
    {
        return Auth::user()->account->users()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])
            ->orderByName()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);
    }

    /**
     * Format pipeline card data.
     */
    private function formatPipelineCard(SalesPipeline $p): array
    {
        return [
            'id' => $p->id,
            'channel_id' => $p->channel_id,
            'company_name' => $p->company_name,
            'contact_name' => $p->contact_name,
            'phone' => $p->phone,
            'email' => $p->email,
            'website_url' => $p->website_url,
            'stage' => $p->stage,
            'priority' => $p->priority,
            'priority_severity' => $p->priority_severity,
            'win_probability' => $p->win_probability,
            'social_channel' => $p->social_channel,
            'social_account' => $p->social_account,
            'quote_amount' => $p->quote_amount ? (float) $p->quote_amount : null,
            'audit_score' => $p->audit_score,
            'stage_changed_at' => $p->stage_changed_at?->diffForHumans(),
            'channel' => $p->channel ? [
                'id' => $p->channel->id,
                'name' => $p->channel->name,
                'color' => $p->channel->color,
                'icon' => $p->channel->icon,
            ] : null,
            'lead' => $p->lead ? [
                'id' => $p->lead->id,
                'name' => $p->lead->name,
                'company' => $p->lead->company,
            ] : null,
            'deal' => $p->deal ? [
                'id' => $p->deal->id,
                'title' => $p->deal->title,
                'value' => $p->deal->value,
            ] : null,
            'assigned_user' => $p->assignedUser ? [
                'id' => $p->assignedUser->id,
                'name' => $p->assignedUser->name,
            ] : null,
            'created_at' => $p->created_at->toISOString(),
        ];
    }
}
