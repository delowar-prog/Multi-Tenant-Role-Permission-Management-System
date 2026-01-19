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

        if (!$tenant) {
            abort(403, 'Tenant not found');
        }

        //    Trial active থাকলে full access
        if ($tenant->trial_ends_at && now()->lt($tenant->trial_ends_at)) {
            return $next($request);
        }
        /*
    |--------------------------------------------------------------------------
    | Paid subscription check
    |--------------------------------------------------------------------------
    */
        $activeSubscription = $tenant->subscriptions()
            ->active() // status = active + not expired
            ->first();

        if ($activeSubscription) {
            return $next($request);
        }
        
    // Trial expired & no subscription
    return response()->json([
        'message' => 'Trial expired',
        'redirect' => '/billing',
        'code' => 'SUBSCRIPTION_REQUIRED',
    ], 402);
    }
}
