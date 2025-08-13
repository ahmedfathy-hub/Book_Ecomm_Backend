<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
   protected $fillable = ['title', 'description', 'image', 'link', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
