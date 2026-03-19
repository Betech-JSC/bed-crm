<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalsController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    /**
     * GET /approvals — My pending approvals
     */
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;

        $pending = $this->approvalService->getPendingForUser(Auth::id());

        $myRequests = ApprovalRequest::where('requested_by', Auth::id())
            ->with(['workflow', 'stepApprovals.approver'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Admin: all workflows
        $workflows = ApprovalWorkflow::where('account_id', $accountId)
            ->with('steps')
            ->get();

        $stats = [
            'pending_count' => $pending->count(),
            'my_requests_count' => $myRequests->count(),
            'approved_count' => ApprovalRequest::where('requested_by', Auth::id())->where('status', 'approved')->count(),
            'rejected_count' => ApprovalRequest::where('requested_by', Auth::id())->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Approvals/Index', [
            'pending' => $pending,
            'myRequests' => $myRequests,
            'workflows' => $workflows,
            'stats' => $stats,
        ]);
    }

    /**
     * POST /approvals/{stepId}/approve
     */
    public function approve(Request $request, int $stepId): RedirectResponse
    {
        $validated = $request->validate(['comment' => 'nullable|string']);
        $this->approvalService->approveStep($stepId, $validated['comment'] ?? '');
        return back()->with('success', 'Approved successfully.');
    }

    /**
     * POST /approvals/{requestId}/reject
     */
    public function reject(Request $request, int $requestId): RedirectResponse
    {
        $validated = $request->validate(['comment' => 'nullable|string']);
        $this->approvalService->rejectRequest($requestId, $validated['comment'] ?? '');
        return back()->with('success', 'Request rejected.');
    }

    /**
     * POST /approval-workflows — Create workflow
     */
    public function storeWorkflow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entity_type' => 'required|string|max:50',
            'conditions' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approver_type' => 'required|in:user,role,department_head,manager,ceo',
            'steps.*.approver_user_id' => 'nullable|exists:users,id',
            'steps.*.timeout_hours' => 'nullable|integer|min:1',
            'steps.*.instructions' => 'nullable|string',
        ]);

        $workflow = ApprovalWorkflow::create([
            'account_id' => Auth::user()->account_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'entity_type' => $validated['entity_type'],
            'conditions' => $validated['conditions'] ?? null,
        ]);

        foreach ($validated['steps'] as $i => $step) {
            $step['approval_workflow_id'] = $workflow->id;
            $step['step_order'] = $i + 1;
            ApprovalWorkflowStep::create($step);
        }

        return back()->with('success', 'Approval workflow created.');
    }

    /**
     * DELETE /approval-workflows/{workflow}
     */
    public function destroyWorkflow(ApprovalWorkflow $workflow): RedirectResponse
    {
        $workflow->delete();
        return back()->with('success', 'Workflow deleted.');
    }
}
