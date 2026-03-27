<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoRoadmapTestAttempt extends Model
{
    protected $fillable = ['test_id', 'user_id', 'answers', 'score', 'passed', 'started_at', 'completed_at'];
    protected $casts = ['answers' => 'array', 'passed' => 'boolean', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function test() { return $this->belongsTo(CeoRoadmapTest::class, 'test_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
