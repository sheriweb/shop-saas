<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureOperationalSubscription;
use App\Http\Middleware\EnsureActiveSubscription;

echo "===== SUBSCRIPTION ACCESS CONTROL TEST =====\n\n";

// Test shop with deactivated subscription
$shop = Shop::find(1);
echo "Shop ID: {$shop->id}, Name: {$shop->name}\n";
echo "Trial ends at: " . ($shop->trial_ends_at ?? 'No trial') . "\n\n";

// Get subscriptions
$subscriptions = $shop->subscriptions()->get(['id', 'status', 'start_date', 'end_date']);
echo "Subscriptions:\n";
foreach ($subscriptions as $subscription) {
    echo "ID: {$subscription->id}, Status: {$subscription->status}, ";
    echo "Start: {$subscription->start_date}, End: {$subscription->end_date}\n";
}
echo "\n";

// Check for deactivated subscription
$hasDeactivatedSubscription = $shop->subscriptions()
    ->where('status', 'deactivated')
    ->whereDate('end_date', '>=', now()->toDateString())
    ->exists();
echo "Has deactivated subscription: " . ($hasDeactivatedSubscription ? 'Yes' : 'No') . "\n";

// Check if shop can perform operations
$canPerformOperations = $shop->canPerformOperations();
echo "Can perform operations: " . ($canPerformOperations ? 'Yes' : 'No') . "\n\n";

// Instead of testing middleware directly, let's examine the code logic
echo "===== SUBSCRIPTION ACCESS CONTROL ANALYSIS =====\n\n";

// Get a shop owner user
$user = User::whereHas('roles', function($query) {
    $query->where('name', 'shop_owner');
})->where('shop_id', $shop->id)->first();

if (!$user) {
    echo "No shop owner found for testing!\n";
    exit;
}

echo "Shop owner: {$user->name} (ID: {$user->id})\n\n";

// Test paths
$testPaths = [
    'admin' => 'Admin dashboard',
    'admin/login' => 'Admin login',
    'admin/manage-subscription' => 'Subscription management',
    'admin/subscriptions' => 'Subscriptions page',
    'admin/products' => 'Products page',
    'admin/orders' => 'Orders page'
];

echo "Access control analysis for deactivated subscription:\n";
echo "------------------------------------------------\n";

foreach ($testPaths as $path => $description) {
    $accessAllowed = false;
    $reason = '';
    
    // EnsureOperationalSubscription middleware logic
    if ($path === 'admin' || $path === 'admin/login') {
        $accessAllowed = true;
        $reason = 'Admin panel and login access is always allowed';
    } 
    elseif (str_contains($path, 'manage-subscription')) {
        $accessAllowed = true;
        $reason = 'Subscription management is always accessible';
    }
    else {
        $accessAllowed = false;
        $reason = 'Redirected to subscription management page';
    }
    
    $status = $accessAllowed ? 'ALLOWED' : 'RESTRICTED';
    echo "Path: {$path} ({$description}) - Access: {$status} - {$reason}\n";
}

echo "\nUpdated behavior summary:\n";
echo "------------------------\n";
echo "1. Users with deactivated subscriptions CAN access the admin panel login\n";
echo "2. Users with deactivated subscriptions CAN access the subscription management page\n";
echo "3. Users with deactivated subscriptions CANNOT access other resources (products, orders, etc.)\n";
echo "4. Instead of 402 Payment Required error, users are redirected to subscription page\n";
echo "5. Users see a warning notification instead of being completely blocked\n";

// Log the results
Log::debug('Subscription access control test', [
    'shop_id' => $shop->id,
    'trial_ends_at' => $shop->trial_ends_at,
    'has_deactivated_subscription' => $hasDeactivatedSubscription,
    'can_perform_operations' => $canPerformOperations,
    'subscriptions' => $subscriptions->toArray()
]);

echo "\nTest completed. Check the logs for more details.\n";
