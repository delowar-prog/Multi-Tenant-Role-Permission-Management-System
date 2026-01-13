<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Plan extends Model
{
     protected $casts = [
        'features' => 'array',
    ];
    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'features',
    ];
   
    // null-safe default
    public function getFeaturesAttribute($value)
    {
        return $value ?? [];
    }
}
