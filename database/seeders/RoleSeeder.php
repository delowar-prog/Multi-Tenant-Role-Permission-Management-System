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
            'manage_users',
            'manage_roles',
            'manage_permissions',
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
