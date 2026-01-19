<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // 🔥 SUPER ADMIN BYPASS — এখানেই বসবে
        if (auth()->check() && auth()->user()->is_super_admin) {
            return $next($request);
        }
        $tenant = auth()->user()->tenant;
        if (!$tenant || !$tenant->feature($feature)) {
            abort(403, 'This feature is not available in your plan.');
        }
        return $next($request);
    }
}
