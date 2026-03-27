<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoRoadmapMilestone extends Model
{
    protected $fillable = ['phase_id', 'title', 'description', 'skills', 'resources', 'sort_order'];
    protected $casts = ['skills' => 'array', 'resources' => 'array'];

    public function phase() { return $this->belongsTo(CeoRoadmapPhase::class, 'phase_id'); }
    public function tests() { return $this->hasMany(CeoRoadmapTest::class, 'milestone_id'); }
}
