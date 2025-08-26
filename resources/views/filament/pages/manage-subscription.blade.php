<x-filament::page>
    {{ $this->form }}
    
    @php
        $shop = auth()->user()->shop;
        $subscription = $shop ? $shop->activeSubscription() : null;
        $pendingSubscription = $shop ? $shop->pendingSubscription() : null;
    @endphp
    
    @if($pendingSubscription)
        <div class="mt-6">
            <div class="rounded-lg bg-yellow-50 shadow dark:bg-yellow-900">
                <div class="p-6">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="ml-2 text-lg font-medium text-gray-900 dark:text-white">Pending Subscription Request</h3>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Your subscription request for the <strong>{{ $pendingSubscription->plan->name }}</strong> plan is currently pending admin approval. You will be notified once it's approved.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @if($subscription)
        <div class="mt-6">
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Subscription Progress</h3>
                    <div class="mt-4">
                        @php
                            $totalDays = $subscription->end_date->diffInDays($subscription->start_date);
                            $daysLeft = $subscription->end_date->diffInDays(now());
                            $percentLeft = min(100, max(0, ($daysLeft / $totalDays) * 100));
                            $percentUsed = 100 - $percentLeft;
                        @endphp
                        
                        <div class="flex items-center">
                            <div class="flex-grow bg-gray-200 dark:bg-gray-700 rounded-full h-2">
<div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentUsed }}%"></div>
                            </div>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $daysLeft }} days left
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <div class="mt-6">
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Subscription History</h3>
                
                @php
                    $subscriptionHistory = $shop ? \App\Models\Subscription::where('shop_id', $shop->id)
                        ->with('plan')
                        ->orderBy('created_at', 'desc')
                        ->get() : collect();
                @endphp
                
                @if($subscriptionHistory->count() > 0)
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Period</th>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($subscriptionHistory as $sub)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $sub->plan->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $sub->start_date->format('M d, Y') }} - {{ $sub->end_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($sub->status === 'active') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                            @elseif($sub->status === 'expired') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                            @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 @endif">
                                            {{ ucfirst($sub->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-4 text-center py-4 text-gray-500 dark:text-gray-400">
                        No subscription history found.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>
