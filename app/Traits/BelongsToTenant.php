<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Global scope
        static::addGlobalScope('tenant_branch', function (Builder $builder) {

            if (! auth()->check()) return;

            $user  = auth()->user();
            $table = $builder->getModel()->getTable();

            if ($user->is_super_admin) return;

            if (! Schema::hasColumn($table, 'tenant_id')) return;

            $hasBranch = Schema::hasColumn($table, 'branch_id');

            $builder->where(function ($q) use ($user, $hasBranch) {

                $q->whereNull('tenant_id')
                    ->orWhere(function ($q2) use ($user, $hasBranch) {

                        $q2->where('tenant_id', $user->tenant_id);

                        if (
                            $hasBranch &&
                            ! $user->is_support_admin &&
                            $user->active_branch_id
                        ) {
                            $q2->where('branch_id', $user->active_branch_id);
                        }
                    });
            });
        });

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
        static::updating(function ($model) {

            if (! auth()->check()) {
                return;
            }

            $user  = auth()->user();
            $table = $model->getTable();

            /*
     |--------------------------------------
     | Protect tenant_id
     |--------------------------------------
     */
            if (
                Schema::hasColumn($table, 'tenant_id') &&
                ! $user->is_super_admin &&
                $model->isDirty('tenant_id')
            ) {
                $model->tenant_id = $model->getOriginal('tenant_id');
            }

            /*
     |--------------------------------------
     | Protect branch_id
     |--------------------------------------
     */
            if (
                Schema::hasColumn($table, 'branch_id') &&
                ! $user->is_super_admin &&
                ! $user->is_support_admin &&
                $model->isDirty('branch_id')
            ) {
                $model->branch_id = $model->getOriginal('branch_id');
            }
        });
    }
}
