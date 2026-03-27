<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CultureSurvey extends Model
{
    protected $fillable = ['account_id', 'title', 'description', 'questions', 'status', 'anonymous', 'created_by'];
    protected $casts = ['questions' => 'array', 'anonymous' => 'boolean'];

    public function responses() { return $this->hasMany(CultureSurveyResponse::class, 'survey_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
