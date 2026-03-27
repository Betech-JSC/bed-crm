<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptLesson extends Model
{
    protected $fillable = ['category_id', 'title', 'content', 'examples', 'tips', 'sort_order'];
    protected $casts = ['examples' => 'array', 'tips' => 'array'];

    public function category() { return $this->belongsTo(PromptCategory::class, 'category_id'); }
    public function exercises() { return $this->hasMany(PromptExercise::class, 'lesson_id'); }
}
