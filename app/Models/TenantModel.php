<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\AssignTenant;
use Illuminate\Support\Facades\Schema;

abstract class TenantModel extends Model
{
    use BelongsToTenant, AssignTenant;

    public function scopeTenant($query)
    {
        if (! auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        // Super admin → no restriction
        if ($user->is_super_admin) {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $hasBranch = Schema::hasColumn($table, 'branch_id');

        return $query->where(function ($q) use ($user, $hasBranch) {

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
    }
}
