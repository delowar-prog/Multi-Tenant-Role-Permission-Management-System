<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Publisher;

class PublisherController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-publishers')->only(['index', 'show']);
        $this->middleware('permission:create-publishers')->only(['store']);
        $this->middleware('permission:update-publishers')->only(['update']);
        $this->middleware('permission:delete-publishers')->only(['destroy']);
    }

    public function index()
    {
        return Publisher::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string'
        ]);

        $publisher = Publisher::create($validated);
        return response()->json($publisher, 201);
    }

    public function show(Publisher $publisher)
    {
        return $publisher;
    }

    public function update(Request $request, Publisher $publisher)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string'
        ]);

        $publisher->update($validated);
        return response()->json($publisher);
    }

    public function destroy(Publisher $publisher)
    {
        $publisher->delete();
        return response()->json(['message' => 'Publisher deleted']);
    }
}
