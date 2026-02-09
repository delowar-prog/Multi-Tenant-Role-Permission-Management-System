<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait AssignTenant
{
    protected static function bootAssignTenant()
    {
         // Auto assign tenant_id & branch_id on create
        static::creating(function ($model) {

            if (! auth()->check()) return;

            $user  = auth()->user();
            $table = $model->getTable();

            if (
                Schema::hasColumn($table, 'tenant_id') &&
                empty($model->tenant_id)
            ) {
                $model->tenant_id = $user->tenant_id;
            }

            if (
                Schema::hasColumn($table, 'branch_id') &&
                empty($model->branch_id) &&
                ! $user->is_super_admin &&
                ! $user->is_support_admin
            ) {
                $model->branch_id = $user->active_branch_id;
            }
        });
    }
}
