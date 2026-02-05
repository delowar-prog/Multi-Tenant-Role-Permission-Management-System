<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-users')
            ->only(['index', 'show']);
        $this->middleware('permission:manage-users')
            ->only([
                'assignRole',
                'removeRole',
                'getRoles',
                'assignBranch',
                'removeBranch',
                'syncBranches',
                'getBranches',
            ]);
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

        activity('audit')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'role' => $request->role,
            ])
            ->log('Role assigned');

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

        activity('audit')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'role' => $request->permission,
            ])
            ->log('Role assigned');

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
        $authUser = auth()->user();
        $tenantId = $authUser->tenant_id;

        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:11',
            'address' => 'nullable|string|max:200',
        ];
        // 🔹 If super admin → tenant_id must be given explicitly
        if ($authUser->is_super_admin) {
            $rules['tenant_id'] = ['required', 'uuid', 'exists:tenants,id'];
        }
        $validated = $request->validate($rules);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone'    => $validated['phone'] ?? null,
            'address'    => $validated['address'] ?? null,

            // 🔹 If super admin → use request tenant_id
            // 🔹 Else → trait will fill tenant_id from auth user
            'tenant_id' => $authUser->is_super_admin
                ? $validated['tenant_id']
                : null,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    /**
     * Display a listing of users with their roles and permissions.
     */
    public function index()
    {
        $query = User::with(['roles', 'permissions', 'branches'])->tenant();
        $this->applyBranchFilter($query);

        $users = $query->paginate();

        return response()->json($users);
    }

    /**
     * Display the specified user with roles and permissions.
     */
    public function show(User $user)
    {
        $query = User::with(['roles', 'permissions', 'branches'])
            ->tenant()
            ->whereKey($user->getKey());
        $this->applyBranchFilter($query);

        $user = $query->firstOrFail();

        return response()->json($user);
    }

    public function assignBranch(Request $request, User $user)
    {
        $tenantId = $this->branchTenantIdFor($user);

        $validated = $request->validate([
            'branch_id' => ['required', 'uuid', $this->branchExistsRule($tenantId)],
        ]);

        $user->branches()->syncWithoutDetaching([$validated['branch_id']]);

        return response()->json([
            'message' => 'Branch assigned successfully',
            'user' => $user->load('branches'),
        ]);
    }

    public function removeBranch(Request $request, User $user)
    {
        $tenantId = $this->branchTenantIdFor($user);

        $validated = $request->validate([
            'branch_id' => ['required', 'uuid', $this->branchExistsRule($tenantId)],
        ]);

        $user->branches()->detach($validated['branch_id']);

        return response()->json([
            'message' => 'Branch removed successfully',
            'user' => $user->load('branches'),
        ]);
    }

    public function syncBranches(Request $request, User $user)
    {
        $tenantId = $this->branchTenantIdFor($user);

        $validated = $request->validate([
            'branches' => 'required|array',
            'branches.*' => ['uuid', $this->branchExistsRule($tenantId)],
        ]);

        $user->branches()->sync($validated['branches']);

        return response()->json([
            'message' => 'Branches synced successfully',
            'user' => $user->load('branches'),
        ]);
    }

    public function getBranches(User $user)
    {
        return response()->json([
            'user' => $user,
            'branches' => $user->branches,
        ]);
    }

    private function branchTenantIdFor(User $user): string
    {
        if (
            ! auth()->user()->is_super_admin &&
            $user->tenant_id &&
            $user->tenant_id !== auth()->user()->tenant_id
        ) {
            throw ValidationException::withMessages([
                'user' => 'User does not belong to your tenant.',
            ]);
        }

        $tenantId = auth()->user()->is_super_admin
            ? $user->tenant_id
            : auth()->user()->tenant_id;

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'branches' => 'User has no tenant assigned.',
            ]);
        }

        return $tenantId;
    }

    private function branchExistsRule(string $tenantId)
    {
        return Rule::exists('branches', 'id')->where('tenant_id', $tenantId);
    }

    private function applyBranchFilter($query): void
    {
        $authUser = auth()->user();

        if (! $authUser || $authUser->is_super_admin || $authUser->is_woner) {
            return;
        }

        $branchIds = $authUser->branches()->pluck('branches.id');

        if ($branchIds->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereHas('branches', function ($branchQuery) use ($branchIds) {
            $branchQuery->whereIn('branches.id', $branchIds);
        });
    }
}
