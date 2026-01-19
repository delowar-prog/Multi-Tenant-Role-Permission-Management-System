<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'plan_id',
        'subscription_started_at',
        'subscription_expires_at',
        'trial_ends_at'
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

    public function roles()
    {
        return $this->hasMany(Role::class, 'team_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }


    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null
            && now()->lt($this->trial_ends_at);
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at !== null
            && now()->gte($this->trial_ends_at);
    }
}
