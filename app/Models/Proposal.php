<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'account_id',
        'deal_id',
        'version',
        'parent_id',
        'title',
        'description',
        'amount',
        'valid_until',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'status',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'view_count',
        'last_viewed_at',
        'created_by',
        'sent_by',
        'metadata',
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

    /**
     * Get the account that owns the proposal.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the deal associated with the proposal.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the user who created the proposal.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who sent the proposal.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the parent proposal (for versioning).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'parent_id');
    }

    /**
     * Get all versions of this proposal.
     */
    public function versions(): HasMany
    {
        $parentId = $this->parent_id ?? $this->id;
        return $this->hasMany(Proposal::class, 'parent_id')
            ->where('parent_id', $parentId)
            ->where('id', '!=', $this->id)
            ->orderBy('version', 'desc');
    }

    /**
     * Get all versions including current (for version history).
     */
    public function allVersions()
    {
        $parentId = $this->parent_id ?? $this->id;
        return Proposal::where('parent_id', $parentId)
            ->orWhere('id', $parentId)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SENT => 'Sent',
            self::STATUS_VIEWED => 'Viewed',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_VIEWED => 'purple',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status severity for PrimeVue Tag
     */
    public function getStatusSeverityAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SENT => 'info',
            self::STATUS_VIEWED => 'warning',
            self::STATUS_ACCEPTED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if proposal can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Check if proposal can be sent
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Check if proposal can be accepted
     */
    public function canBeAccepted(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED]);
    }

    /**
     * Check if proposal can be rejected
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED]);
    }

    /**
     * Create a new version of this proposal
     */
    public function createVersion(array $attributes = []): self
    {
        $parentId = $this->parent_id ?? $this->id;
        $latestVersion = self::where('parent_id', $parentId)
            ->max('version') ?? $this->version;

        return self::create(array_merge([
            'account_id' => $this->account_id,
            'deal_id' => $this->deal_id,
            'parent_id' => $parentId,
            'version' => $latestVersion + 1,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'valid_until' => $this->valid_until,
            'status' => self::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ], $attributes));
    }

    /**
     * Mark proposal as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'sent_by' => auth()->id(),
        ]);
    }

    /**
     * Mark proposal as viewed
     */
    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => self::STATUS_VIEWED,
                'viewed_at' => now(),
            ]);
        }

        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Mark proposal as accepted
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Mark proposal as rejected
     */
    public function markAsRejected(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by deal
     */
    public function scopeForDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    /**
     * Scope for getting latest versions only
     */
    public function scopeLatestVersions($query)
    {
        return $query->whereNull('parent_id')
            ->orWhereColumn('id', 'parent_id');
    }
}
