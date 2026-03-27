<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CultureSurveyResponse extends Model
{
    protected $fillable = ['survey_id', 'user_id', 'answers', 'submitted_at'];
    protected $casts = ['answers' => 'array', 'submitted_at' => 'datetime'];

    public function survey() { return $this->belongsTo(CultureSurvey::class, 'survey_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
