<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Alarabiya Academy') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .lms-vector {
            position: absolute;
            opacity: 0.1;
            pointer-events: none;
        }
        .lms-vector-1 {
            top: 10%;
            left: 5%;
            animation: float 6s ease-in-out infinite;
        }
        .lms-vector-2 {
            top: 30%;
            right: 8%;
            animation: float 8s ease-in-out infinite;
        }
        .lms-vector-3 {
            bottom: 15%;
            left: 10%;
            animation: float 7s ease-in-out infinite;
        }
        .lms-vector-4 {
            bottom: 25%;
            right: 5%;
            animation: float 9s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 relative overflow-hidden">
    <!-- LMS Background Vectors -->
    <div class="lms-vector lms-vector-1">
        <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M100 20L120 60L160 60L130 90L140 130L100 110L60 130L70 90L40 60L80 60L100 20Z" fill="currentColor" class="text-blue-400 dark:text-blue-600"/>
            <circle cx="100" cy="100" r="30" fill="currentColor" class="text-indigo-400 dark:text-indigo-600"/>
            <path d="M50 150L70 170L90 150L100 180L110 150L130 170L150 150L140 120L160 100L130 100L100 80L70 100L40 100L50 150Z" fill="currentColor" class="text-purple-400 dark:text-purple-600"/>
        </svg>
    </div>
    <div class="lms-vector lms-vector-2">
        <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="30" y="30" width="120" height="120" rx="10" fill="currentColor" class="text-blue-400 dark:text-blue-600"/>
            <path d="M60 60L80 80L120 40" stroke="white" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="90" cy="90" r="25" fill="currentColor" class="text-indigo-400 dark:text-indigo-600"/>
            <path d="M40 140L60 160L100 120L140 160L160 140" stroke="currentColor" stroke-width="6" stroke-linecap="round" class="text-purple-400 dark:text-purple-600"/>
        </svg>
    </div>
    <div class="lms-vector lms-vector-3">
        <svg width="160" height="160" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M80 20C100 20 120 30 130 50C140 70 130 90 110 100C130 110 140 130 130 150C120 170 100 180 80 180C60 180 40 170 30 150C20 130 30 110 50 100C30 90 20 70 30 50C40 30 60 20 80 20Z" fill="currentColor" class="text-indigo-400 dark:text-indigo-600"/>
            <rect x="50" y="60" width="60" height="40" rx="5" fill="currentColor" class="text-blue-400 dark:text-blue-600"/>
            <path d="M60 80L75 95L100 70" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
    <div class="lms-vector lms-vector-4">
        <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="100" cy="50" r="30" fill="currentColor" class="text-purple-400 dark:text-purple-600"/>
            <path d="M70 100L100 130L130 100" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400 dark:text-blue-600"/>
            <rect x="60" y="140" width="80" height="40" rx="5" fill="currentColor" class="text-indigo-400 dark:text-indigo-600"/>
            <path d="M75 160L90 175L125 140" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
                </div>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12 relative z-10">
        <div class="max-w-md w-full">
            <div class="bg-gradient-to-br from-white/95 via-indigo-50/90 to-blue-100/80 dark:from-gray-900/95 dark:via-slate-900/90 dark:to-indigo-950/80 backdrop-blur-xl rounded-2xl shadow-2xl p-8 sm:p-10 border border-indigo-100/60 dark:border-indigo-900/50 ring-1 ring-white/60 dark:ring-black/30">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Alarabiya Academy
                        </span>
                    </h1>
                    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Welcome Back
                </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Sign in to continue to your account
                </p>
            </div>

            <!-- Session Status -->
            @if(session('status'))
                    <div class="mb-6 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Email Address
                        </label>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                required 
                                autofocus 
                                autocomplete="username"
                            placeholder="Email address"
                            class="block w-full px-4 py-3.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Password
                        </label>
                            <input 
                                id="password" 
                                type="password" 
                                name="password"
                                required 
                                autocomplete="current-password"
                            placeholder="Password"
                            class="block w-full px-4 py-3.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            >
                        @error('password')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember_me" 
                                type="checkbox" 
                                name="remember"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                            >
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Remember me
                            </label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-xl text-base font-semibold text-white bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 hover:from-indigo-700 hover:via-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
                    >
                        <span>Sign In</span>
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </form>

            <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        © {{ date('Y') }} Technest-Agency. Created by Technest Agency for Software Solutions |
                        <a href="https://wa.me/201557601371" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            Connect on WhatsApp
                        </a>
            </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
