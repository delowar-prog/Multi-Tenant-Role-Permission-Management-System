<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Permission::paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);
        $validated['guard_name']='web';
        $permission = Permission::create($validated);
        return response()->json($permission, 201);
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
            'name' => 'required|unique:permissions,name,'. $permission->id,
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
