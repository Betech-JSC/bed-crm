<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrainingSet extends Model
{
    protected $fillable = [
        'account_id', 'name', 'agent_type', 'description', 'format',
        'data', 'item_count', 'quality_score', 'status', 'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'quality_score' => 'decimal:1',
    ];

    public const FORMATS = [
        'qa_pairs' => ['label' => 'Q&A Pairs', 'icon' => 'pi pi-question-circle'],
        'conversations' => ['label' => 'Conversations', 'icon' => 'pi pi-comments'],
        'instructions' => ['label' => 'Instructions', 'icon' => 'pi pi-list'],
        'examples' => ['label' => 'Examples', 'icon' => 'pi pi-copy'],
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
