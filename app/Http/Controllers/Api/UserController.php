<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_users')
            ->only(['index', 'assignRole', 'removeRole', 'getRoles']);
    }
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

    //create New user under a tenant
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:11',
            'address' => 'nullable|string|max:200'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'    => $request->address,
            // tenant_id পাঠাতে হবে না
        ]);

        return response()->json($user);
    }
    /**
     * Display a listing of users with their roles and permissions.
     */
    public function index()
    {
        $users = User::with(['roles', 'permissions'])->tenant()->paginate();

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
