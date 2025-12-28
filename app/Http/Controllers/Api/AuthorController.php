<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Author;

class AuthorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-authors')->only(['index', 'show']);
        $this->middleware('permission:create-authors')->only(['store']);
        $this->middleware('permission:update-authors')->only(['update']);
        $this->middleware('permission:delete-authors')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('perpage', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return Author::paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'bio' => 'nullable|string'
        ]);

        $author = Author::create($validated);
        return response()->json($author, 201);
    }

    public function show(Author $author)
    {
        return $author;
    }

    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'bio' => 'nullable|string'
        ]);

        $author->update($validated);
        return response()->json($author);
    }

    public function destroy(Author $author)
    {
        $author->delete();
        return response()->json(['message' => 'Author deleted']);
    }
}
