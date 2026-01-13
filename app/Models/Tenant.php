<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'plan_id',
        'subscription_expires_at',
        // যদি আরও ফিল্ড থাকে, যেমন 'domain', 'uuid', add here
    ];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (!$tenant->id) {
                $tenant->id = (string) Str::uuid();
            }
        });
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function feature(string $key, $default = false): bool
    {
        if (!$this->plan) {
            return $default;
        }

        $features = optional($this->plan)->features;

        // If features is still a string (JSON), decode it
        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        // 🔥 MUST be array
        if (!is_array($features)) {
            return $default;
        }

        return (bool) ($features[$key] ?? $default);
    }
}
