<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

    // 🔹 Global permissions
    $permissions = [
        'manage_users',
        'manage_roles',
        'manage_permissions',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
            'team_id' => null, // 🔥 GLOBAL
        ]);
    }

    // 🔹 Super Admin role (GLOBAL)
    $superAdminRole = Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
        'team_id' => null,
    ]);

    // 🔹 Attach ALL permissions
    $superAdminRole->syncPermissions(Permission::all());

    // 🔹 Super Admin user
    $superAdmin = User::firstOrCreate(
        ['email' => 'admin@gmail.com'],
        [
            'name' => 'Admin',
            'password' => Hash::make('12345678'),
            'tenant_id' => null,
            'is_super_admin' => true,
        ]
    );

    // 🔹 THIS LINE fills model_has_roles & model_has_permissions
    $superAdmin->assignRole($superAdminRole);
    }
}
