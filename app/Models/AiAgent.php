<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends Model
{
    protected $fillable = [
        'account_id', 'name', 'slug', 'type', 'description', 'avatar',
        'system_prompt', 'knowledge_base_ids', 'training_set_ids',
        'tools', 'model_config', 'is_active', 'total_conversations',
        'total_messages', 'avg_satisfaction', 'created_by',
    ];

    protected $casts = [
        'knowledge_base_ids' => 'array',
        'training_set_ids' => 'array',
        'tools' => 'array',
        'model_config' => 'array',
        'is_active' => 'boolean',
        'avg_satisfaction' => 'decimal:2',
    ];

    public const TYPES = [
        'sales' => [
            'label' => 'Sales Agent',
            'icon' => 'pi pi-chart-line',
            'color' => '#10b981',
            'description' => 'Lead qualification, deal coaching, objection handling, forecasting',
        ],
        'support' => [
            'label' => 'Support Agent',
            'icon' => 'pi pi-headphones',
            'color' => '#f59e0b',
            'description' => 'Auto-reply tickets, FAQ, troubleshooting guidance',
        ],
        'content' => [
            'label' => 'Content Agent',
            'icon' => 'pi pi-pencil',
            'color' => '#8b5cf6',
            'description' => 'Blog SEO, social posts, email writer, ad copy',
        ],
        'analytics' => [
            'label' => 'Analytics Agent',
            'icon' => 'pi pi-chart-bar',
            'color' => '#06b6d4',
            'description' => 'Data analysis, trend detection, revenue forecast',
        ],
        'hr' => [
            'label' => 'HR Agent',
            'icon' => 'pi pi-users',
            'color' => '#ec4899',
            'description' => 'Onboarding guide, policy Q&A, leave requests',
        ],
        'custom' => [
            'label' => 'Custom Agent',
            'icon' => 'pi pi-cog',
            'color' => '#64748b',
            'description' => 'User-defined agent for any use case',
        ],
    ];

    public const TOOLS = [
        'search_knowledge' => ['label' => 'Tìm kiếm Knowledge Base', 'icon' => 'pi pi-search'],
        'query_crm' => ['label' => 'Truy vấn CRM Data', 'icon' => 'pi pi-database'],
        'create_lead' => ['label' => 'Tạo Lead', 'icon' => 'pi pi-plus'],
        'create_task' => ['label' => 'Tạo Task', 'icon' => 'pi pi-check-square'],
        'send_email' => ['label' => 'Gửi Email', 'icon' => 'pi pi-envelope'],
        'generate_report' => ['label' => 'Tạo Report', 'icon' => 'pi pi-file'],
        'web_search' => ['label' => 'Tìm kiếm Web', 'icon' => 'pi pi-globe'],
        'analyze_data' => ['label' => 'Phân tích Dữ liệu', 'icon' => 'pi pi-chart-bar'],
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function conversations(): HasMany { return $this->hasMany(AiAgentConversation::class, 'agent_id'); }

    public function knowledgeBases()
    {
        return AiKnowledgeBase::whereIn('id', $this->knowledge_base_ids ?? [])->get();
    }

    public function getConversionRateAttribute(): float
    {
        return $this->total_conversations > 0
            ? round(($this->total_messages / $this->total_conversations), 1)
            : 0;
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
