<?php

namespace App\Traits;

trait AssignTenant
{
    protected static function bootAssignTenant()
    {
        static::creating(function ($model) {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            // 🔹 Skip auto-assign for super admin (they will set tenant manually)
            if ($user->is_super_admin ?? false) {
                return;
            }

            // 🔹 For normal users: inherit tenant_id if not already set
            if (empty($model->tenant_id) && ! empty($user->tenant_id)) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }
}
