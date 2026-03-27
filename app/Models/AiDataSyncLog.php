<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDataSyncLog extends Model
{
    protected $fillable = [
        'account_id', 'knowledge_base_id', 'source_type', 'source_ref',
        'action', 'records_processed', 'records_failed', 'duration_ms',
        'status', 'error_message',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function knowledgeBase(): BelongsTo { return $this->belongsTo(AiKnowledgeBase::class, 'knowledge_base_id'); }

    public function scopeRecent($q) { return $q->latest()->limit(20); }

    public function getDurationFormatAttribute(): string
    {
        $ms = $this->duration_ms;
        if ($ms < 1000) return $ms . 'ms';
        return round($ms / 1000, 1) . 's';
    }
}
