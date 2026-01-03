<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:11',
            'address' => 'nullable|string|max:200'
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Create Tenant
            $tenant = Tenant::create([
                'name' => $fields['name'] . "'s Organization",
            ]);

            // 2️⃣ Create User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $fields['name'],
                'email' => $fields['email'],
                'password' => Hash::make($fields['password']),
                'is_super_admin' => false,
                'phone' => $fields['phone'],
                'address' => $fields['address']
            ]);

            // 3️⃣ Set Spatie Team Context (tenant_id)
            app(\Spatie\Permission\PermissionRegistrar::class)
                ->setPermissionsTeamId($tenant->id);

            // 4️⃣ Copy global permissions to this tenant
            $globalPermissions = Permission::where('team_id', null) // global permissions
                ->get();

            foreach ($globalPermissions as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm->name,
                    'team_id' => $tenant->id,
                    'guard_name' => $perm->guard_name,
                ]);
            }

            // 5️⃣ Create tenant-admin role for this tenant
            $role = Role::firstOrCreate([
                'name' => 'tenant-admin',
                'team_id' => $tenant->id,
                'guard_name' => 'web'
            ]);

            // 6️⃣ Assign tenant permissions to tenant role
            $role->syncPermissions(Permission::where('team_id', $tenant->id)->pluck('name')->toArray());

            // 7️⃣ Assign role to user (fills model_has_roles)
            $user->assignRole($role);

            // 8️⃣ Optional: assign direct permissions to user (fills model_has_permissions)
            $user->givePermissionTo(Permission::where('team_id', $tenant->id)->pluck('name')->toArray());

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Tenant check (skip for Super Admin)
        if (! $user->is_super_admin && ! $user->tenant_id) {
            return response()->json([
                'message' => 'Tenant not assigned to this user.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Prepare response
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'tenant_id' => $user->tenant_id,
            ],
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
