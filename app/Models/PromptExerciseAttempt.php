<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptExerciseAttempt extends Model
{
    protected $fillable = ['exercise_id', 'user_id', 'user_prompt', 'rating', 'notes'];

    public function exercise() { return $this->belongsTo(PromptExercise::class, 'exercise_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
