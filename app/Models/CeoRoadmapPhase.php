<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoRoadmapPhase extends Model
{
    protected $fillable = ['account_id', 'title', 'description', 'icon', 'color', 'sort_order'];

    public function milestones() { return $this->hasMany(CeoRoadmapMilestone::class, 'phase_id')->orderBy('sort_order'); }
}
