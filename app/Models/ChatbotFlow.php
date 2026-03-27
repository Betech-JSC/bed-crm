<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotFlow extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'name', 'description',
        'nodes', 'edges', 'settings', 'trigger_type', 'trigger_value',
        'status', 'conversations_count', 'leads_captured',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'settings' => 'array',
    ];

    public static function getNodeTypes(): array
    {
        return [
            'message' => ['label' => 'Tin nhắn', 'icon' => 'pi pi-comment', 'color' => '#6366f1'],
            'question' => ['label' => 'Câu hỏi', 'icon' => 'pi pi-question-circle', 'color' => '#f59e0b'],
            'options' => ['label' => 'Lựa chọn', 'icon' => 'pi pi-list', 'color' => '#10b981'],
            'collect_info' => ['label' => 'Thu thập TT', 'icon' => 'pi pi-user-edit', 'color' => '#ec4899'],
            'condition' => ['label' => 'Điều kiện', 'icon' => 'pi pi-directions', 'color' => '#0ea5e9'],
            'action' => ['label' => 'Hành động', 'icon' => 'pi pi-bolt', 'color' => '#8b5cf6'],
            'end' => ['label' => 'Kết thúc', 'icon' => 'pi pi-stop-circle', 'color' => '#94a3b8'],
        ];
    }

    public static function getTriggerTypes(): array
    {
        return [
            'page_load' => 'Khi tải trang',
            'button_click' => 'Khi click button',
            'time_delay' => 'Sau N giây',
            'exit_intent' => 'Khi rời trang',
            'scroll_percent' => 'Khi scroll %',
        ];
    }

    public function getConversionRateAttribute(): float
    {
        return $this->conversations_count > 0
            ? round(($this->leads_captured / $this->conversations_count) * 100, 1) : 0;
    }

    public function messages(): HasMany { return $this->hasMany(ChatbotMessage::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
