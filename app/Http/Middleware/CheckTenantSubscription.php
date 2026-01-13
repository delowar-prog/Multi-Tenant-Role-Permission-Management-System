<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = auth()->user();

        // super admin skip
        if ($user->is_super_admin) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (!$tenant || !$tenant->subscription_expires_at) {
            abort(403, 'Subscription not active');
        }

        if (now()->greaterThan($tenant->subscription_expires_at)) {
            abort(403, 'Subscription expired');
        }

        return $next($request);
    }
}
