<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-roles')->only(['index', 'show']);
        $this->middleware('permission:manage-roles')->only(['store', 'update', 'destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::visibleTo(auth()->user())->with(['permissions','tenant'])->paginate(10);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                // same tenant এ duplicate না হয়
                Rule::unique('roles', 'name')->where(function ($query) {
                    return $query->where(
                        'team_id',
                        auth()->user()->is_super_admin
                            ? null
                            : auth()->user()->tenant_id
                    );
                }),
            ],
            'permissions' => 'array'
        ]);

        // 🔥 Determine role scope
        $isSuperAdmin = auth()->user()->is_super_admin;

        $role = Role::create(['team_id' => $isSuperAdmin ? null : auth()->user()->tenant_id, 'name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $permissions = collect($validated['permissions'])
                ->filter(fn($p) => $p && !in_array($p, ['web', 'api']))
                ->values()
                ->all();

            $role->syncPermissions($permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['tenant_id' => auth()->user()->tenant_id, 'name' => $validated['name'], 'guard_name' => 'web']);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}
