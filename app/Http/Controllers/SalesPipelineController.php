<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesPipeline\StoreSalesPipelineRequest;
use App\Http\Requests\SalesPipeline\UpdateSalesPipelineRequest;
use App\Models\Lead;
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
    public function index(): Response
    {
        $pipelines = Auth::user()->account->salesPipelines()
            ->with(['lead:id,name,company', 'deal:id,title,value', 'assignedUser:id,first_name,last_name'])
            ->open()
            ->orderBy('created_at', 'desc')
            ->get();

        // Also get closed ones for stats
        $closedWon = Auth::user()->account->salesPipelines()->closedWon()->count();
        $closedLost = Auth::user()->account->salesPipelines()->closedLost()->count();

        // Group by stage
        $pipelinesByStage = [];
        foreach (SalesPipeline::getOpenStages() as $stageKey => $stageLabel) {
            $pipelinesByStage[$stageKey] = $pipelines
                ->where('stage', $stageKey)
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'company_name' => $p->company_name,
                    'contact_name' => $p->contact_name,
                    'phone' => $p->phone,
                    'email' => $p->email,
                    'website_url' => $p->website_url,
                    'stage' => $p->stage,
                    'priority' => $p->priority,
                    'priority_severity' => $p->priority_severity,
                    'social_channel' => $p->social_channel,
                    'social_account' => $p->social_account,
                    'quote_amount' => $p->quote_amount ? (float) $p->quote_amount : null,
                    'audit_score' => $p->audit_score,
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
                ])
                ->values()
                ->toArray();
        }

        return Inertia::render('SalesPipeline/Index', [
            'pipelinesByStage' => $pipelinesByStage,
            'stages' => SalesPipeline::getOpenStages(),
            'allStages' => SalesPipeline::getStages(),
            'priorities' => SalesPipeline::getPriorities(),
            'salesUsers' => $this->getSalesUsers(),
            'stats' => [
                'total_open' => $pipelines->count(),
                'closed_won' => $closedWon,
                'closed_lost' => $closedLost,
            ],
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
        Auth::user()->account->salesPipelines()->create(array_merge(
            $request->validated(),
            ['stage' => SalesPipeline::STAGE_AUDIT]
        ));

        return Redirect::route('sales-pipeline')->with('success', 'Đã tạo quy trình bán hàng mới.');
    }

    /**
     * Show edit page with tabs.
     */
    public function edit(SalesPipeline $pipeline): Response
    {
        return Inertia::render('SalesPipeline/Edit', [
            'pipeline' => [
                'id' => $pipeline->id,
                'company_name' => $pipeline->company_name,
                'contact_name' => $pipeline->contact_name,
                'phone' => $pipeline->phone,
                'email' => $pipeline->email,
                'website_url' => $pipeline->website_url,
                'stage' => $pipeline->stage,
                'priority' => $pipeline->priority,
                'priority_severity' => $pipeline->priority_severity,
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
                'deleted_at' => $pipeline->deleted_at,
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
            'stages' => SalesPipeline::getStages(),
            'allStages' => SalesPipeline::getStages(),
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
        $pipeline->update($request->validated());

        return Redirect::back()->with('success', 'Đã cập nhật quy trình.');
    }

    /**
     * Update pipeline stage (drag-drop kanban).
     */
    public function updateStage(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'stage' => ['required', 'in:' . implode(',', array_keys(SalesPipeline::getStages()))],
        ]);

        $pipeline->update(['stage' => $request->stage]);

        return Redirect::back()->with('success', 'Đã chuyển giai đoạn.');
    }

    /**
     * Save audit data.
     */
    public function storeAudit(Request $request, SalesPipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'audit_data' => ['required', 'array'],
        ]);

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

        $pipeline->update([
            'stage' => SalesPipeline::STAGE_CLOSED_WON,
            'close_date' => now(),
            'close_notes' => $request->close_notes,
            'deal_id' => $request->deal_id,
        ]);

        return Redirect::route('sales-pipeline')->with('success', 'Chốt deal thành công! 🎉');
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

        $pipeline->update([
            'stage' => SalesPipeline::STAGE_CLOSED_LOST,
            'close_date' => now(),
            'lost_reason' => $request->lost_reason,
            'close_notes' => $request->close_notes,
        ]);

        return Redirect::route('sales-pipeline')->with('success', 'Đã đóng deal.');
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
        $context = [
            'company_name' => $pipeline->company_name,
            'website_url' => $pipeline->website_url,
        ];

        $result = $service->analyzeAudit($auditData, $context);

        // Store analysis result in pipeline
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
        $context = [
            'company_name' => $pipeline->company_name,
            'website_url' => $pipeline->website_url,
        ];

        $result = $service->generateProposal($auditData, $context);

        return response()->json($result);
    }

    /**
     * Get sales users for the current account.
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
}
