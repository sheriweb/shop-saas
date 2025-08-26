<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

Route::get('/test-subscription-access', function () {
    if (!Auth::check()) {
        return 'Not authenticated';
    }
    
    $user = Auth::user();
    $shop = $user->shop;
    
    if (!$shop) {
        return 'No shop found for user';
    }
    
    // Log shop and subscription details
    Log::debug('Testing subscription access control', [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'user_roles' => $user->roles->pluck('name'),
        'shop_id' => $shop->id,
        'shop_name' => $shop->name,
        'trial_ends_at' => $shop->trial_ends_at,
    ]);
    
    // Check for deactivated subscription
    $hasDeactivatedSubscription = $shop->subscriptions()
        ->where('status', 'deactivated')
        ->whereDate('end_date', '>=', now()->toDateString())
        ->exists();
        
    Log::debug('Subscription status check', [
        'has_deactivated_subscription' => $hasDeactivatedSubscription,
        'subscriptions' => $shop->subscriptions()->get(['id', 'status', 'start_date', 'end_date'])->toArray()
    ]);
    
    // Check if shop can perform operations
    $canPerformOperations = $shop->canPerformOperations();
    Log::debug('Shop operations check', [
        'can_perform_operations' => $canPerformOperations
    ]);
    
    return [
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')
        ],
        'shop' => [
            'id' => $shop->id,
            'name' => $shop->name,
            'trial_ends_at' => $shop->trial_ends_at
        ],
        'subscription' => [
            'has_deactivated_subscription' => $hasDeactivatedSubscription,
            'can_perform_operations' => $canPerformOperations
        ]
    ];
});
