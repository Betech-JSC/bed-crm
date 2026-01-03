<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatWidgetDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'widget_id',
        'name',
        'content',
        'file_path',
        'file_type',
        'chunk_index',
        'embedding',
        'token_count',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(ChatWidget::class, 'widget_id');
    }

    /**
     * Get embedding as array
     */
    public function getEmbeddingArray(): ?array
    {
        if (empty($this->embedding)) {
            return null;
        }

        return json_decode($this->embedding, true);
    }

    /**
     * Set embedding from array
     */
    public function setEmbeddingArray(array $embedding): void
    {
        $this->embedding = json_encode($embedding);
    }
}
