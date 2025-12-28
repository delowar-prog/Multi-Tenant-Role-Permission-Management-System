<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        $user->removeRole($request->role);

        return response()->json([
            'message' => 'Role removed successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Sync roles for a user.
     */
    public function syncRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user->syncRoles($request->roles);

        return response()->json([
            'message' => 'Roles synced successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Get user's roles.
     */
    public function getRoles(User $user)
    {
        return response()->json([
            'user' => $user,
            'roles' => $user->roles
        ]);
    }

    /**
     * Assign a permission to a user.
     */
    public function assignPermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $user->givePermissionTo($request->permission);

        return response()->json([
            'message' => 'Permission assigned successfully',
            'user' => $user->load('permissions')
        ]);
    }

    /**
     * Remove a permission from a user.
     */
    public function removePermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $user->revokePermissionTo($request->permission);

        return response()->json([
            'message' => 'Permission removed successfully',
            'user' => $user->load('permissions')
        ]);
    }

    /**
     * Sync permissions for a user.
     */
    public function syncPermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $user->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'user' => $user->load('permissions')
        ]);
    }

    /**
     * Get user's permissions.
     */
    public function getPermissions(User $user)
    {
        return response()->json([
            'user' => $user,
            'permissions' => $user->permissions
        ]);
    }

    /**
     * Display a listing of users with their roles and permissions.
     */
    public function index()
    {
        $users = User::with(['roles', 'permissions'])->paginate();

        return response()->json($users);
    }

    /**
     * Display the specified user with roles and permissions.
     */
    public function show(User $user)
    {
        return response()->json($user->load(['roles', 'permissions']));
    }
}
