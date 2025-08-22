<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admins bypass subscription checks
        if (in_array('super_admin', $user->roles->pluck('name')->toArray())) {
            return $next($request);
        }

        // Users without a shop are blocked
        if (! $user->shop) {
            abort(403, 'No shop associated with your account.');
        }

        if (! $user->shop->hasActiveSubscription()) {
            abort(402, 'Your shop subscription is inactive or expired. Please contact the administrator.');
        }

        return $next($request);
    }
}
