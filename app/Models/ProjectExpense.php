<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    protected $fillable = ['project_id', 'description', 'amount', 'category', 'date'];

    protected $casts = ['amount' => 'decimal:2', 'date' => 'date'];

    public static function getCategories(): array
    {
        return ['software' => 'Software', 'hosting' => 'Hosting', 'design' => 'Design', 'hardware' => 'Hardware', 'other' => 'Other'];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
