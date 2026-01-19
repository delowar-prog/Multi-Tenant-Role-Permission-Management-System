<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
   public function index(Request $request)
    {
        $perPage = (int) $request->query('perpage', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return Plan::paginate($perPage);
    }

    public function landingIndex()
    {
        return Plan::orderBy('price')->get([
            'id',
            'name',
            'price',
            'duration_days',
            'features',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        $plan = Plan::create($validated);

        return response()->json($plan, 201);
    }

    public function show(Plan $plan)
    {
        return $plan;
    }

    public function landingShow(Plan $plan)
    {
        return $plan->only([
            'id',
            'name',
            'price',
            'duration_days',
            'features',
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return response()->json(['message' => 'Plan deleted']);
    }
}
