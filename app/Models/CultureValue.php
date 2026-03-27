<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CultureValue extends Model
{
    protected $fillable = ['account_id', 'title', 'description', 'icon', 'color', 'behaviors', 'sort_order'];
    protected $casts = ['behaviors' => 'array'];
}
