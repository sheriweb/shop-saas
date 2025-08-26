<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Current Subscription</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-900 text-white">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl w-full space-y-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Your Subscription
                </h1>
                <p class="mt-4 text-xl text-gray-300">
                    Manage your current subscription plan
                </p>
            </div>

            @if(session('success'))
                <div class="bg-green-500 text-white p-4 rounded-md mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-500 text-white p-4 rounded-md mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $pendingSubscription = auth()->user()->shop->pendingSubscription();
            @endphp

            @if($pendingSubscription)
                <div class="bg-yellow-700 rounded-lg shadow-lg overflow-hidden mb-8">
                    <div class="px-6 py-8">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-white">{{ $pendingSubscription->plan->name }}</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                Pending Approval
                            </span>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-white">Your subscription request is currently pending admin approval. You will be notified once it's approved.</p>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-300">Requested Plan</p>
                                <p class="text-lg text-white">{{ $pendingSubscription->plan->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-300">Requested On</p>
                                <p class="text-lg text-white">{{ $pendingSubscription->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($activeSubscription)
                <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-8">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-white">{{ $activeSubscription->plan->name }}</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $activeSubscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($activeSubscription->status) }}
                            </span>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-400">Start Date</p>
                                <p class="text-lg">{{ $activeSubscription->start_date->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">End Date</p>
                                <p class="text-lg">{{ $activeSubscription->end_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <h4 class="text-lg font-medium text-white">Features included:</h4>
                            <ul class="mt-2 space-y-2">
                                @if(is_array($activeSubscription->plan->features))
                                    @foreach($activeSubscription->plan->features as $feature => $value)
                                        @if($value)
                                            <li class="flex items-center">
                                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span class="ml-2">{{ str_replace('_', ' ', ucfirst($feature)) }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                        
                        <div class="mt-8">
                            <div class="flex items-center">
                                <div class="flex-grow bg-gray-700 rounded-full h-2">
                                    @php
                                        $totalDays = $activeSubscription->end_date->diffInDays($activeSubscription->start_date);
                                        $daysLeft = $activeSubscription->end_date->diffInDays(now());
                                        $percentLeft = min(100, max(0, ($daysLeft / $totalDays) * 100));
                                        $percentUsed = 100 - $percentLeft;
                                    @endphp
<div class="bg-blue-500 h-2 rounded-full" style="width: {{ $percentUsed }}%"></div>
                                </div>
                                <span class="ml-3 text-sm">
                                    {{ $daysLeft }} days left
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-700 flex justify-between">
                        <a href="{{ route('subscriptions.plans') }}" class="text-blue-400 hover:text-blue-300 font-medium">
                            Change Plan
                        </a>
                        <form method="POST" action="{{ route('subscriptions.renew', $activeSubscription->id) }}">
                            @csrf
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                                Renew Subscription
                            </button>
                        </form>
                    </div>
                </div>
            @elseif(!$pendingSubscription)
                <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden p-8 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-white">No Active Subscription</h3>
                    <p class="mt-2 text-gray-400">You don't have an active subscription at the moment.</p>
                    <div class="mt-6">
                        <a href="{{ route('subscriptions.plans') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            View Available Plans
                        </a>
                    </div>
                </div>
            @endif
            
            <div class="mt-8">
                <h2 class="text-2xl font-bold mb-4">Subscription History</h2>
                
                @if($subscriptionHistory && $subscriptionHistory->count() > 0)
                    <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Plan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Period</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                @foreach($subscriptionHistory as $subscription)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">{{ $subscription->plan->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-300">
                                            {{ $subscription->start_date->format('M d, Y') }} - {{ $subscription->end_date->format('M d, Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : ($subscription->status === 'expired' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($subscription->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 bg-gray-800 rounded-lg">
                        <p class="text-gray-400">No subscription history found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
