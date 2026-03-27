<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKnowledgeBase extends Model
{
    protected $fillable = [
        'account_id', 'name', 'description', 'type', 'settings',
        'status', 'stats', 'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'stats' => 'array',
    ];

    public const TYPES = [
        'general' => ['label' => 'Tổng hợp', 'icon' => 'pi pi-database', 'color' => '#6366f1'],
        'sales' => ['label' => 'Sales', 'icon' => 'pi pi-chart-line', 'color' => '#10b981'],
        'support' => ['label' => 'Support', 'icon' => 'pi pi-headphones', 'color' => '#f59e0b'],
        'hr' => ['label' => 'HR', 'icon' => 'pi pi-users', 'color' => '#ec4899'],
        'product' => ['label' => 'Product', 'icon' => 'pi pi-box', 'color' => '#8b5cf6'],
        'custom' => ['label' => 'Custom', 'icon' => 'pi pi-cog', 'color' => '#64748b'],
    ];

    public const STATUSES = [
        'building' => ['label' => 'Đang xây dựng', 'color' => '#f59e0b'],
        'ready' => ['label' => 'Sẵn sàng', 'color' => '#10b981'],
        'training' => ['label' => 'Đang training', 'color' => '#6366f1'],
        'error' => ['label' => 'Lỗi', 'color' => '#ef4444'],
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function documents(): HasMany { return $this->hasMany(AiKnowledgeDocument::class, 'knowledge_base_id'); }

    public function getDocumentCountAttribute(): int { return $this->stats['documents'] ?? 0; }
    public function getChunkCountAttribute(): int { return $this->stats['chunks'] ?? 0; }

    public function refreshStats(): void
    {
        $docs = $this->documents()->count();
        $indexed = $this->documents()->where('status', 'indexed')->count();
        $this->update(['stats' => [
            'documents' => $docs,
            'indexed' => $indexed,
            'chunks' => $this->documents()->whereNotNull('chunks')->get()->sum(fn ($d) => count($d->chunks ?? [])),
        ]]);
    }
}
