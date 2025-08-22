<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFilamentPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Agar user login nahi hai
        if (! $user) {
            return redirect()->route('login');
        }

        // Agar user ke paas "access_filament" permission nahi hai
        if (! $user->hasPermissionTo('access_filament')) {
            abort(403, 'You do not have permission to access the admin panel.');
        }

        return $next($request);
    }
}
