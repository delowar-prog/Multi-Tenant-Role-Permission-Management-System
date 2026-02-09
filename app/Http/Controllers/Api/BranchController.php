<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        return $this->branchVisibility(Branch::tenant())->paginate($perPage);
    }

    public function select()
    {
        return $this->branchVisibility(Branch::query())
            ->select('id', 'name')
            ->get()
            ->toArray();
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
        $this->authorizeBranchVisibility($branch);

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

    private function branchVisibility($query)
    {
        if (! auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        if ($user->is_super_admin || $user->is_support_admin) {
            return $query;
        }
        if ($user->is_woner) {
            return $query;
        }

        if ($user->active_branch_id) {
            $query->where('id', $user->active_branch_id);
        }

        return $query;
    }

    private function authorizeBranchVisibility(Branch $branch): void
    {
        if (! auth()->check()) {
            abort(403);
        }

        $user = auth()->user();

        if ($user->is_super_admin || $user->is_support_admin) {
            return;
        }

        if (! $user->active_branch_id || $branch->id !== $user->active_branch_id) {
            abort(403);
        }
    }
}
