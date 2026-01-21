<?php

use App\Http\Middleware\CheckTenantSubscription;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\ResolveTenantFromToken;
use App\Http\Middleware\SetTenantPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'tenant.permission' => SetTenantPermission::class,
            'tenant.subscription' => CheckTenantSubscription::class,
            'feature' => EnsureFeatureEnabled::class,
            'tenant.resolve' => ResolveTenantFromToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        App\Providers\AuthServiceProvider::class,
    ])->create();
