<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Display the subscription plans selection page
     */
    public function showPlans()
    {
        // Get all available plans
        $plans = Plan::all();
        
        // Get the current user's shop
        $shop = null;
        if (Auth::check()) {
            $user = Auth::user();
            $shop = $user->shop;
        }
        
        return view('subscriptions.plans', compact('plans', 'shop'));
    }
    
    /**
     * Display the current subscription details
     */
    public function showCurrentSubscription()
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        if (!$shop) {
            return redirect()->route('subscriptions.plans')
                ->with('error', 'You need to create a shop first before viewing subscription details.');
        }
        
        // Get active subscription
        $activeSubscription = $shop->activeSubscription();
        
        // Get subscription history
        $subscriptionHistory = Subscription::where('shop_id', $shop->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('subscriptions.current', compact('activeSubscription', 'subscriptionHistory'));
    }
    
    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);
        
        $user = Auth::user();
        $shop = $user->shop;
        
        if (!$shop) {
            return redirect()->route('subscriptions.plans')
                ->with('error', 'You need to create a shop first before subscribing to a plan.');
        }
        
        $plan = Plan::findOrFail($request->plan_id);
        
        // Check if there's an active subscription and mark it as expired
        $activeSubscription = $shop->activeSubscription();
        if ($activeSubscription) {
            $activeSubscription->status = 'expired';
            $activeSubscription->save();
        }
        
        // Create a new subscription with pending status
        $subscription = new Subscription();
        $subscription->shop_id = $shop->id;
        $subscription->plan_id = $plan->id;
        $subscription->start_date = now();
        $subscription->end_date = now()->addDays($plan->duration_days);
        $subscription->status = 'pending';
        $subscription->save();
        
        // For now, we'll assume payment is handled separately or manually
        
        return redirect()->route('subscriptions.current')
            ->with('success', 'Your subscription request for the ' . $plan->name . ' plan has been submitted and is pending admin approval.');
    }
    
    /**
     * Renew an existing subscription
     */
    public function renewSubscription($subscriptionId)
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        if (!$shop) {
            return redirect()->route('subscriptions.plans')
                ->with('error', 'You need to create a shop first before renewing a subscription.');
        }
        
        // Find the subscription
        $subscription = Subscription::findOrFail($subscriptionId);
        
        // Verify the subscription belongs to the user's shop
        if ($subscription->shop_id !== $shop->id) {
            return redirect()->route('subscriptions.current')
                ->with('error', 'You do not have permission to renew this subscription.');
        }
        
        // Create a new subscription with the same plan but pending status
        $newSubscription = new Subscription();
        $newSubscription->shop_id = $shop->id;
        $newSubscription->plan_id = $subscription->plan_id;
        $newSubscription->start_date = $subscription->end_date; // Start when the current one ends
        $newSubscription->end_date = Carbon::parse($subscription->end_date)->addDays($subscription->plan->duration_days);
        $newSubscription->status = 'pending';
        $newSubscription->save();
        
        return redirect()->route('subscriptions.current')
            ->with('success', 'Your subscription renewal request has been submitted and is pending admin approval.');
    }
    
    /**
     * Show the payment page for a plan
     */
    public function showPayment($planId)
    {
        $plan = Plan::findOrFail($planId);
        return view('subscriptions.payment', compact('plan'));
    }
    
    /**
     * Process the payment and activate the subscription
     */
    public function processPayment(Request $request, $planId)
    {
        // This would typically integrate with a payment gateway
        // For now, we'll just create the subscription directly
        
        $plan = Plan::findOrFail($planId);
        $user = Auth::user();
        $shop = $user->shop;
        
        // Check if there's an active subscription and mark it as expired
        $activeSubscription = $shop->activeSubscription();
        if ($activeSubscription) {
            $activeSubscription->status = 'expired';
            $activeSubscription->save();
        }
        
        // Create a new subscription with pending status
        $subscription = new Subscription();
        $subscription->shop_id = $shop->id;
        $subscription->plan_id = $plan->id;
        $subscription->start_date = now();
        $subscription->end_date = now()->addDays($plan->duration_days);
        $subscription->status = 'pending';
        $subscription->save();
        
        return redirect()->route('subscriptions.current')
            ->with('success', 'Payment processed successfully. Your subscription request has been submitted and is pending admin approval.');
    }
}
