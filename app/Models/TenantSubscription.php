<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'subscription_started_at',
        'subscription_expires_at',
        'amount',
        'billing_cycle',
        'status',
    ];

    protected $casts = [
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'amount'     => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('subscription_expires_at')
                    ->orWhere('subscription_expires_at', '>=', now());
            });
    }


}
