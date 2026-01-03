<?php

use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'tenant.permission'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    //get authenticate user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', function () {
        $user = auth()->user();
        // ✅ Set Spatie team context for tenant
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->setPermissionsTeamId($user->tenant_id);
            
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
});


// Super Admin Panel Routes
Route::middleware(['auth:sanctum', 'super.admin'])->group(function () {
    // Route::resource('tenants', TenantController::class);
    // Route::resource('permissions', PermissionController::class);
    // Route::apiResource('users', UserController::class);
});
