<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for subscriptions that are about to expire and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expiring subscriptions...');
        
        // Check for subscriptions expiring in 7 days
        $this->checkExpiringSubscriptions(7);
        
        // Check for subscriptions expiring in 3 days
        $this->checkExpiringSubscriptions(3);
        
        // Check for subscriptions expiring in 1 day
        $this->checkExpiringSubscriptions(1);
        
        $this->info('Completed checking expiring subscriptions.');
        
        return Command::SUCCESS;
    }
    
    /**
     * Check for subscriptions expiring in the specified number of days
     * 
     * @param int $days
     */
    private function checkExpiringSubscriptions(int $days)
    {
        $date = Carbon::now()->addDays($days)->toDateString();
        
        $expiringSubscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', $date)
            ->with(['shop', 'plan'])
            ->get();
            
        $this->info("Found {$expiringSubscriptions->count()} subscriptions expiring in {$days} days.");
        
        foreach ($expiringSubscriptions as $subscription) {
            // Find the shop owner to notify
            $shopOwners = User::whereHas('roles', function($query) {
                    $query->where('name', 'shop_owner');
                })
                ->where('shop_id', $subscription->shop_id)
                ->get();
                
            foreach ($shopOwners as $owner) {
                $owner->notify(new SubscriptionExpiringNotification($subscription, $days));
                $this->info("Notification sent to {$owner->email} for subscription #{$subscription->id}");
            }
        }
    }
}
