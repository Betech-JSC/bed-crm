<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'url', 'secret', 'events',
        'headers', 'is_active', 'retry_count', 'timeout',
        'last_triggered_at', 'last_status_code', 'total_deliveries',
        'total_failures', 'created_by', 'notes',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'retry_count' => 'integer',
        'timeout' => 'integer',
        'last_triggered_at' => 'datetime',
        'last_status_code' => 'integer',
        'total_deliveries' => 'integer',
        'total_failures' => 'integer',
    ];

    protected $hidden = ['secret'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) return 'Disabled';
        return 'Active';
    }

    public function getSuccessRateAttribute(): ?float
    {
        if ($this->total_deliveries === 0) return null;
        return round(($this->total_deliveries - $this->total_failures) / $this->total_deliveries * 100, 1);
    }

    public static function availableEvents(): array
    {
        return [
            'lead.created', 'lead.updated', 'lead.deleted',
            'deal.created', 'deal.updated', 'deal.won', 'deal.lost',
            'contact.created', 'contact.updated', 'contact.deleted',
            'customer.created', 'customer.updated',
            'quotation.created', 'quotation.approved', 'quotation.rejected',
            'contract.created', 'contract.approved',
            'order.created', 'order.updated', 'order.shipped', 'order.delivered',
            'pipeline.stage_changed',
            'ticket.created', 'ticket.resolved',
        ];
    }
}
