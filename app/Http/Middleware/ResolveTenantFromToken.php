<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = auth()->user()?->currentAccessToken();

        if (! $token) {
            return $next($request);
        }

        $tenantAbility = collect($token->abilities)
            ->first(fn($a) => str_starts_with($a, 'tenant_id:'));

        if ($tenantAbility) {
            $tenantId = explode(':', $tenantAbility)[1];
            $tenant = Tenant::findOrFail($tenantId);

            app()->instance('tenant', $tenant);
        }
        
        return $next($request);
    }
}
