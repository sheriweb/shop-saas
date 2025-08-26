<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Your Subscription Plan</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-900 text-white">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full space-y-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Choose Your Plan
                </h1>
                <p class="mt-4 text-xl text-gray-300">
                    Select the plan that best fits your business needs
                </p>
            </div>

            @if(session('error'))
                <div class="bg-red-500 text-white p-4 rounded-md mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-12 grid gap-8 lg:grid-cols-3">
                @foreach($plans as $plan)
                <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-white">{{ $plan->name }}</h3>
                        <div class="mt-4 flex items-baseline text-white">
                            <span class="text-5xl font-extrabold tracking-tight">PKR {{ number_format($plan->price) }}</span>
                            <span class="ml-1 text-xl font-semibold">/{{ $plan->duration_days }} days</span>
                        </div>
                        
                        <div class="mt-6">
                            <h4 class="text-lg font-medium text-white">Features included:</h4>
                            <ul class="mt-2 space-y-2">
                                @if(is_array($plan->features))
                                    @foreach($plan->features as $feature => $value)
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
                    </div>
                    <div class="px-6 py-4 bg-gray-700">
                        <form method="POST" action="{{ route('subscriptions.subscribe') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                                Subscribe Now
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>
