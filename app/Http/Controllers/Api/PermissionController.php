<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Debug tenant_id
        // dd(auth()->user()->tenant_id);
        return Permission::paginate(10);
        // return Permission::where('tenant_id', auth()->user()->tenant_id)->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);
        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'data' => $permission
        ]);
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
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update($validated);
        return response()->json($permission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(permission $permission)
    {
        $permission->delete();
        return response()->json(['message' => 'Permission deleted']);
    }
}
