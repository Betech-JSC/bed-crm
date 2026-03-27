<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'deal_id', 'version', 'parent_id',
        'title', 'description', 'amount', 'valid_until',
        'file_path', 'file_name', 'file_size', 'file_type',
        'status', 'sent_at', 'viewed_at', 'accepted_at', 'rejected_at',
        'rejection_reason', 'view_count', 'last_viewed_at',
        'created_by', 'sent_by', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_VIEWED = 'viewed';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }
    public function parent(): BelongsTo { return $this->belongsTo(Proposal::class, 'parent_id'); }
}
