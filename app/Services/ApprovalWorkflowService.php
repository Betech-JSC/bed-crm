<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApprovalWorkflowService
{
    /**
     * Create an approval request for a given subject.
     */
    public function requestApproval(Model $subject, string $reasonVi, string $reasonEn, ?int $approverId = null): ApprovalRequest
    {
        return ApprovalRequest::create([
            'account_id' => $subject->account_id,
            'requested_by' => Auth::id(),
            'approver_id' => $approverId, // If null, it's for any admin
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'reason_vi' => $reasonVi,
            'reason_en' => $reasonEn,
            'status' => 'pending'
        ]);
    }

    /**
     * Approve a request.
     */
    public function approve(int $requestId, ?string $comment = null): bool
    {
        $request = ApprovalRequest::findOrFail($requestId);
        
        DB::transaction(function () use ($request, $comment) {
            $request->update([
                'status' => 'approved',
                'comment' => $comment,
                'approver_id' => Auth::id()
            ]);

            // Optional: Auto-update subject status if logic exists
            if ($request->subject instanceof Deal && $request->subject_type === Deal::class) {
                // e.g., Mark discount as approved
            }
        });

        return true;
    }

    /**
     * Get pending requests for the current user (as an approver).
     */
    public function getPendingRequests(int $accountId): array
    {
        return ApprovalRequest::where('account_id', $accountId)
            ->where('status', 'pending')
            ->with(['requester'])
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'type' => class_basename($r->subject_type),
                'requester' => $r->requester->name,
                'reason_vi' => $r->reason_vi,
                'reason_en' => $r->reason_en,
                'date' => $r->created_at->diffForHumans()
            ])->toArray();
    }
}
