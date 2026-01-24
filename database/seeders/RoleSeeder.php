<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 🔁 clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ✅ permissions list
        $permissions = [
            'view-users',
            'manage-users',
            'view-permissions',
            'view-roles',
            'manage-roles',
            'view-branches',
            'create-branches',
            'update-branches',
            'delete-branches',
        ];

        // ✅ create permissions (GLOBAL)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ✅ create tenant-admin role
        $tenantAdminRole = Role::firstOrCreate([
            'name' => 'tenant-admin',
            'guard_name' => 'web',
        ]);

        // ✅ assign permissions to role
        $tenantAdminRole->syncPermissions($permissions);
    }
}
