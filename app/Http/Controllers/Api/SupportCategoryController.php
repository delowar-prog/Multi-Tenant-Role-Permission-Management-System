<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportCategory;
use Illuminate\Http\Request;

class SupportCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support-categories')->only(['index', 'show', 'store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('perpage', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $query = SupportCategory::query();

        if ($request->has('is_active')) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }
        return $query->orderBy('name')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;

        if (! auth()->user()->is_super_admin) {
            unset($validated['tenant_id']);
        }

        $category = SupportCategory::create($validated);

        return response()->json($category, 201);
    }


    public function categories()
    {
        return SupportCategory::select('id', 'name')->get()->prepend([
        'id' => null,
        'name' => 'Select Category',
    ])
    ->values();
    }

    public function show(SupportCategory $supportCategory)
    {
        return $supportCategory;
    }

    public function update(Request $request, SupportCategory $supportCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
        ]);

        if (! auth()->user()->is_super_admin) {
            unset($validated['tenant_id']);
        }

        $supportCategory->update($validated);

        return response()->json($supportCategory);
    }

    public function destroy(SupportCategory $supportCategory)
    {
        $supportCategory->delete();

        return response()->json(['message' => 'Support category deleted']);
    }
}
