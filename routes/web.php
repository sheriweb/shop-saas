<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Include test routes
require __DIR__.'/test.php';

Route::get('/', function () {
    return view('welcome');
});

// Subscription routes
Route::middleware(['auth'])->group(function () {
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'showPlans'])->name('subscriptions.plans');
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::get('/subscriptions/payment/{plan}', [SubscriptionController::class, 'showPayment'])->name('subscriptions.payment');
    Route::post('/subscriptions/payment/{plan}', [SubscriptionController::class, 'processPayment'])->name('subscriptions.process-payment');
    Route::get('/subscriptions/current', [SubscriptionController::class, 'showCurrentSubscription'])->name('subscriptions.current');
    Route::post('/subscriptions/renew/{subscription}', [SubscriptionController::class, 'renewSubscription'])->name('subscriptions.renew');
});
