<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptCategory extends Model
{
    protected $fillable = ['account_id', 'title', 'description', 'level', 'icon', 'color', 'sort_order'];

    public function lessons() { return $this->hasMany(PromptLesson::class, 'category_id'); }
}
