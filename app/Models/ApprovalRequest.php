<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'account_id', 'requested_by', 'approver_id', 
        'subject_type', 'subject_id', 
        'reason_vi', 'reason_en', 
        'comment', 'status'
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
