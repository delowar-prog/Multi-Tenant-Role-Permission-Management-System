<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ImpersonationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SslCommerzController;
use App\Http\Controllers\Api\TenantController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
Route::get('/landing/plans', [PlanController::class, 'landingIndex']);
Route::get('/landing/plans/{plan}', [PlanController::class, 'landingShow']);

Route::middleware(['auth:sanctum','tenant.resolve','tenant.permission'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/plans', [BillingController::class, 'plans']);
    // Route::post('/subscribe/{plan}', [BillingController::class, 'subscribe']);

    //SSLcommerz
    Route::post('/payments/sslcommerz/initiate', [SslCommerzController::class, 'initiate']);
    Route::post('/payments/sslcommerz/success', [SslCommerzController::class, 'success'])->name('ssl.success');
    Route::post('/payments/sslcommerz/fail', [SslCommerzController::class, 'fail'])->name('ssl.fail');
    Route::post('/payments/sslcommerz/cancel', [SslCommerzController::class, 'cancel'])->name('ssl.cancel');

    //get authenticate user
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [ProfileController::class, 'show']);

    // ✅ Role & Permission CRUD
    Route::apiResource('roles', RoleController::class)->middleware('feature:branding');
    Route::apiResource('permissions', PermissionController::class)->middleware('feature:branding');
    Route::get('all-permissions', [PermissionController::class, 'getPermissions']);

    // ✅ User CRUD and role/permission management
    Route::apiResource('users', UserController::class)->middleware('tenant.subscription');
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::post('users/{user}/remove-role', [UserController::class, 'removeRole']);
    Route::post('users/{user}/sync-roles', [UserController::class, 'syncRoles']);
    Route::get('users/{user}/roles', [UserController::class, 'getRoles']);
    Route::post('users/{user}/assign-permission', [UserController::class, 'assignPermission']);
    Route::post('users/{user}/remove-permission', [UserController::class, 'removePermission']);
    Route::post('users/{user}/sync-permissions', [UserController::class, 'syncPermissions']);
    Route::get('users/{user}/permissions', [UserController::class, 'getPermissions']);

    //Accounts Setting
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::post('/tenant/update', [TenantController::class, 'update']);

    Route::apiResource('authors', AuthorController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('plans', PlanController::class);

    //Super Admin URL
    Route::get('/all-tenant', [AdminUserController::class, 'index']);
    Route::post('/admin/impersonate/{tenant}',[ImpersonationController::class, 'start']);
    Route::post('/impersonation/exit',[ImpersonationController::class, 'exit']);
    Route::get('/alllogs', [ActivityLogController::class, 'getLogs']);

});
