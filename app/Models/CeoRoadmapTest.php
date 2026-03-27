<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoRoadmapTest extends Model
{
    protected $fillable = ['milestone_id', 'title', 'description', 'questions', 'passing_score', 'time_limit_minutes'];
    protected $casts = ['questions' => 'array'];

    public function milestone() { return $this->belongsTo(CeoRoadmapMilestone::class, 'milestone_id'); }
    public function attempts() { return $this->hasMany(CeoRoadmapTestAttempt::class, 'test_id'); }
}
