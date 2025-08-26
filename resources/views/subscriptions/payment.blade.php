<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment for {{ $plan->name }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-900 text-white">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h1 class="text-3xl font-extrabold tracking-tight">
                    Complete Your Payment
                </h1>
                <p class="mt-4 text-xl text-gray-300">
                    {{ $plan->name }} - PKR {{ number_format($plan->price) }}
                </p>
            </div>

            @if(session('error'))
                <div class="bg-red-500 text-white p-4 rounded-md mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-8 bg-gray-800 p-6 rounded-lg shadow-lg">
                <form method="POST" action="{{ route('subscriptions.process-payment', $plan->id) }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="card_number" class="block text-sm font-medium text-gray-300">Card Number</label>
                        <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" class="mt-1 block w-full border-gray-600 rounded-md shadow-sm bg-gray-700 text-white py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="expiry" class="block text-sm font-medium text-gray-300">Expiry Date</label>
                            <input type="text" name="expiry" id="expiry" placeholder="MM/YY" class="mt-1 block w-full border-gray-600 rounded-md shadow-sm bg-gray-700 text-white py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="cvv" class="block text-sm font-medium text-gray-300">CVV</label>
                            <input type="text" name="cvv" id="cvv" placeholder="123" class="mt-1 block w-full border-gray-600 rounded-md shadow-sm bg-gray-700 text-white py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Name on Card</label>
                        <input type="text" name="name" id="name" placeholder="John Doe" class="mt-1 block w-full border-gray-600 rounded-md shadow-sm bg-gray-700 text-white py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Pay PKR {{ number_format($plan->price) }}
                        </button>
                    </div>
                    
                    <div class="text-center text-sm text-gray-400 mt-4">
                        <p>This is a demo payment form. No actual payment will be processed.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
