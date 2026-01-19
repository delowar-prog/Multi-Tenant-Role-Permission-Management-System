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
        'billing_cycle',
        'duration_days',
        'trial_days',
        'features',
        'is_active',
    ];
   
    // null-safe default
    public function getFeaturesAttribute($value)
    {
        return $value ?? [];
    }
}
