<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Super admins bypass this check
        if ($user && $user->roles->contains('name', 'super_admin')) {
            return $next($request);
        }
        
        // Check if user has a shop and if the shop can perform operations
        if ($user && $user->shop && !$user->shop->canPerformOperations()) {
            // If this is a Filament request, show a notification
            if (str_contains($request->path(), 'admin')) {
                Notification::make()
                    ->title('Subscription Required')
                    ->body('You need an active subscription to perform this action. Please wait for admin approval or contact support.')
                    ->danger()
                    ->send();
                
                // Redirect to subscription management page
                return redirect()->route('filament.admin.pages.manage-subscription');
            }
            
            // For non-Filament requests
            return redirect()->route('subscriptions.current')
                ->with('error', 'You need an active subscription to perform this action. Please wait for admin approval or contact support.');
        }
        
        return $next($request);
    }
}
