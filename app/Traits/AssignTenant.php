<?php

namespace App\Traits;

trait AssignTenant
{
    protected static function bootAssignTenant()
    {
        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
