<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptExercise extends Model
{
    protected $fillable = ['lesson_id', 'title', 'instruction', 'sample_prompt', 'expected_output', 'difficulty', 'sort_order'];

    public function lesson() { return $this->belongsTo(PromptLesson::class, 'lesson_id'); }
    public function attempts() { return $this->hasMany(PromptExerciseAttempt::class, 'exercise_id'); }
}
