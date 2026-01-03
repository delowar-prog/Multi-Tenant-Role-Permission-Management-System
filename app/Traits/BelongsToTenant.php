<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Global scope
        static::addGlobalScope('tenant', function ($builder) {
            if (
                auth()->check() &&
                ! auth()->user()->is_super_admin
            ) {
                $builder->where(function ($q) {
                    $q->whereNull('tenant_id')
                      ->orWhere('tenant_id', auth()->user()->tenant_id);
                });
            }
        });

        // Auto assign tenant_id
        static::creating(function ($model) {
            if (
                auth()->check() &&
                empty($model->tenant_id)
            ) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Prevent tenant switch on update
        static::updating(function ($model) {
            if (
                auth()->check() &&
                ! auth()->user()->is_super_admin
            ) {
                unset($model->tenant_id);
            }
        });
    }
}
