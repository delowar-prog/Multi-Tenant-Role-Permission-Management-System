<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:view-branches')->only(['index', 'show']);
        // $this->middleware('permission:create-branches')->only(['store']);
        // $this->middleware('permission:update-branches')->only(['update']);
        // $this->middleware('permission:delete-branches')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('perpage', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return Branch::paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
        ]);

        if (! auth()->user()->is_super_admin) {
            unset($validated['tenant_id']);
        }

        $branch = Branch::create($validated);

        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        return $branch;
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
        ]);

        if (! auth()->user()->is_super_admin) {
            unset($validated['tenant_id']);
        }

        $branch->update($validated);

        return response()->json($branch);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json(['message' => 'Branch deleted']);
    }
}
