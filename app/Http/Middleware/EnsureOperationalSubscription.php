<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperationalSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Debug logging
        Log::debug('EnsureOperationalSubscription middleware running', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles->pluck('name'),
            'path' => $request->path(),
            'shop_id' => $user?->shop_id,
            'has_shop' => (bool)$user?->shop,
            'can_perform_operations' => $user?->shop ? $user->shop->canPerformOperations() : null,
        ]);
        
        // Allow logout requests to pass through
        if (str_contains($request->path(), 'logout')) {
            Log::debug('Allowing logout request to pass through');
            return $next($request);
        }
        
        // Super admins bypass this check
        if ($user && $user->roles->contains('name', 'super_admin')) {
            Log::debug('Super admin bypassing subscription check');
            return $next($request);
        }
        
        // Check if user has a shop and if the shop can perform operations
        if ($user && $user->shop && !$user->shop->canPerformOperations()) {
            // If this is a Filament request
            if (str_contains($request->path(), 'admin')) {
                // Always allow access to the admin panel login and dashboard
                if ($request->path() === 'admin' || $request->path() === 'admin/login') {
                    return $next($request);
                }
                
                // Allow access to subscription management page and prevent redirect loops
                if (str_contains($request->path(), 'manage-subscription') || 
                    str_contains($request->path(), 'subscriptions') || 
                    $request->routeIs('filament.admin.pages.manage-subscription')) {
                    Log::debug('Allowing access to subscription management', [
                        'path' => $request->path(),
                        'route' => $request->route() ? $request->route()->getName() : null
                    ]);
                    return $next($request);
                }
                
                // Check if there's a deactivated subscription
                $hasDeactivatedSubscription = $user->shop->subscriptions()
                    ->where('status', 'deactivated')
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->exists();
                
                Log::debug('Subscription status check', [
                    'has_deactivated_subscription' => $hasDeactivatedSubscription,
                    'shop_id' => $user->shop_id,
                    'subscriptions' => $user->shop->subscriptions()->get(['id', 'status', 'start_date', 'end_date'])->toArray()
                ]);
                
                $message = $hasDeactivatedSubscription 
                    ? 'Your subscription has been deactivated by an administrator. Please contact support or reactivate your subscription to access other features.'
                    : 'You need an active subscription to access this feature. Please activate your subscription to continue.';
                
                Notification::make()
                    ->title('Subscription Required')
                    ->body($message)
                    ->warning() // Changed from danger to warning
                    ->persistent() // Make notification persistent
                    ->send();
                
                // Redirect to subscription management page without throwing an error
                return redirect()->route('filament.admin.pages.manage-subscription');
            }
            
            // For non-Filament requests
            return redirect()->route('subscriptions.current')
                ->with('warning', 'You need an active subscription to perform this action. Please activate your subscription to continue.');
        }
        
        return $next($request);
    }
}
