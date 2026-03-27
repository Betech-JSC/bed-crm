<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CultureInitiative extends Model
{
    protected $fillable = ['account_id', 'title', 'description', 'category', 'status', 'start_date', 'end_date', 'assigned_to', 'impact', 'created_by'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
