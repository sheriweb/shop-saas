<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Shop extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name','owner_name','email','phone','whatsapp_number','address','trial_ends_at','status','logo_path'
    ];

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasActiveSubscription(): bool
    {
        // Trial valid?
        if ($this->trial_ends_at && Carbon::parse($this->trial_ends_at)->isFuture()) {
            return true;
        }

        // Active subscription with end_date today or in the future
        return $this->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();
    }
    
    /**
     * Check if the shop has access to a specific feature based on its active subscription
     * 
     * @param string $featureName
     * @return bool
     */
    public function hasFeature(string $featureName): bool
    {
        // Trial: all features available
        if ($this->trial_ends_at && Carbon::parse($this->trial_ends_at)->isFuture()) {
            return true;
        }

        $subscription = $this->activeSubscription();
        if (!$subscription) {
            return false;
        }

        $planFeatures = $subscription->plan->features ?? [];

        // Normalize plan features: support associative (key => bool/value) and list (["feature_a","feature_b"]) formats
        $hasDirect = false;
        if (is_array($planFeatures)) {
            // Associative check
            if (array_keys($planFeatures) !== range(0, count($planFeatures) - 1)) {
                $hasDirect = isset($planFeatures[$featureName]) && $planFeatures[$featureName] !== false;
            } else {
                // List format
                $hasDirect = in_array($featureName, $planFeatures, true);
            }
        }

        if ($hasDirect) {
            return true;
        }

        // Alias mapping: if feature not present, allow via aliased resources (e.g., categories -> products)
        $prefix = config('subscriptions.permission_prefix', 'manage_');
        if (str_starts_with($featureName, $prefix)) {
            $resource = substr($featureName, strlen($prefix));
            $aliases = config('subscriptions.resource_aliases.' . $resource, []);
            foreach ($aliases as $aliasResource) {
                $aliasCandidates = [
                    $prefix . $aliasResource,
                    $prefix . (str_ends_with($aliasResource, 's') ? rtrim($aliasResource, 's') : $aliasResource . 's'),
                ];
                foreach (array_unique($aliasCandidates) as $candidate) {
                    if (array_keys($planFeatures) !== range(0, count($planFeatures) - 1)) {
                        if (isset($planFeatures[$candidate]) && $planFeatures[$candidate] !== false) {
                            return true;
                        }
                    } else {
                        if (in_array($candidate, $planFeatures, true)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
    
    /**
     * Get the active subscription for this shop
     *
     * @return \App\Models\Subscription|null
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->with('plan')
            ->first();
    }
    
    /**
     * Get the pending subscription for this shop
     *
     * @return \App\Models\Subscription|null
     */
    public function pendingSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'pending')
            ->with('plan')
            ->first();
    }
    
    /**
     * Check if the shop can perform operations based on subscription status
     * Operations are allowed if:
     * 1. Shop is in trial period
     * 2. Shop has an active subscription
     * 3. Shop does not have a pending or deactivated subscription
     *
     * @return bool
     */
    public function canPerformOperations(): bool
    {
        // During trial period, operations are allowed
        if ($this->trial_ends_at && Carbon::parse($this->trial_ends_at)->isFuture()) {
            Log::debug('Shop can perform operations - in trial period', [
                'shop_id' => $this->id,
                'trial_ends_at' => $this->trial_ends_at
            ]);
            return true;
        }
        
        // Check if there's a pending subscription
        $hasPendingSubscription = $this->subscriptions()
            ->where('status', 'pending')
            ->exists();
            
        if ($hasPendingSubscription) {
            Log::debug('Shop cannot perform operations - has pending subscription', [
                'shop_id' => $this->id
            ]);
            return false;
        }
        
        // Check if there's a deactivated subscription
        $hasDeactivatedSubscription = $this->subscriptions()
            ->where('status', 'deactivated')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();
            
        if ($hasDeactivatedSubscription) {
            Log::debug('Shop cannot perform operations - has deactivated subscription', [
                'shop_id' => $this->id,
                'subscriptions' => $this->subscriptions()->get(['id', 'status', 'start_date', 'end_date'])->toArray()
            ]);
            return false;
        }
        
        // Check for active subscription
        $hasActiveSubscription = $this->hasActiveSubscription();
        Log::debug('Shop active subscription check', [
            'shop_id' => $this->id,
            'has_active_subscription' => $hasActiveSubscription
        ]);
        return $hasActiveSubscription;
    }

    /**
     * @return HasMany
     */
    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsappAccount::class);
    }

    /**
     * @return HasMany
     */
    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
    }
}
