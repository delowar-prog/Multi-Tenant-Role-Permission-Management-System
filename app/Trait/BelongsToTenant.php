<?php

namespace App\Trait;
use Illuminate\Database\Eloquent\Builder;
trait BelongsToTenant
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
