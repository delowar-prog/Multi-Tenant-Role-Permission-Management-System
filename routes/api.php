<?php

use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PublisherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    //get authenticate user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', function () {
        $user = auth()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    });

    // ✅ Role & Permission CRUD
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);

    // ✅ User CRUD and role/permission management
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::post('users/{user}/remove-role', [UserController::class, 'removeRole']);
    Route::post('users/{user}/sync-roles', [UserController::class, 'syncRoles']);
    Route::get('users/{user}/roles', [UserController::class, 'getRoles']);
    Route::post('users/{user}/assign-permission', [UserController::class, 'assignPermission']);
    Route::post('users/{user}/remove-permission', [UserController::class, 'removePermission']);
    Route::post('users/{user}/sync-permissions', [UserController::class, 'syncPermissions']);
    Route::get('users/{user}/permissions', [UserController::class, 'getPermissions']);

    Route::apiResource('authors', AuthorController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('publishers', PublisherController::class);

    // ✅ Protected routes by role
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin']);
    })->middleware('role:admin');

    // ✅ Protected routes by permission
    Route::get('/report', function () {
        return response()->json(['message' => 'You can view report']);
    })->middleware('permission:view-report');
});
