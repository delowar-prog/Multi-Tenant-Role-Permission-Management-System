<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::with('permissions')->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
            'tenant_id' => 'required',
            'permissions' => 'array'
        ]);
        $role = Role::create(['tenant_id' => auth()->user()->tenant_id, 'name' => $validated['name'], 'guard_name' => 'web']);

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

        $role->update(['name' => $validated['name'], 'guard_name' => 'web']);

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
