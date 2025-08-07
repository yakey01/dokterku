<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Premium Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <!-- Using built-in system fonts -->
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 25%, #16213e 50%, #0f3460 75%, #533483 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .glass-morphism {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .glass-morphism-dark {
            background: rgba(15, 15, 35, 0.7);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
        }
        
        .premium-glow {
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.3);
        }
        
        .premium-glow:hover {
            box-shadow: 0 0 40px rgba(139, 92, 246, 0.5);
        }
        
        .card-premium {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-premium:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 50px rgba(139, 92, 246, 0.2);
        }
        
        .sidebar-glow {
            box-shadow: 
                inset 0 1px 0 rgba(255, 255, 255, 0.1),
                0 0 30px rgba(0, 0, 0, 0.5),
                0 0 50px rgba(139, 92, 246, 0.1);
        }
        
        .scrollbar-premium::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollbar-premium::-webkit-scrollbar-track {
            background: rgba(15, 15, 35, 0.3);
            border-radius: 10px;
        }
        
        .scrollbar-premium::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            border-radius: 10px;
            border: 2px solid rgba(15, 15, 35, 0.3);
        }
        
        .scrollbar-premium::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #7c3aed, #0891b2);
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .premium-border {
            border: 1px solid;
            border-image: linear-gradient(135deg, #8b5cf6, #06b6d4, #10b981) 1;
        }
        
        .stats-number {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="h-full text-white overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Background Effects -->
    <div class="fixed inset-0 z-0">
        <div class="absolute top-0 left-0 w-96 h-96 bg-purple-500/10 rounded-full filter blur-3xl animate-pulse"></div>
        <div class="absolute top-1/2 right-0 w-96 h-96 bg-blue-500/10 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-0 left-1/3 w-96 h-96 bg-emerald-500/10 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
    </div>
    
    <div class="relative z-10 flex h-full">
        <!-- Sidebar -->
        @include('admin.layouts.partials.sidebar')
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            @include('admin.layouts.partials.header')
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-auto scrollbar-premium bg-gradient-to-br from-transparent via-black/5 to-transparent">
                <div class="p-6 space-y-8">
                    @if (session('success'))
                        <div class="glass-morphism border-l-4 border-emerald-400 p-4 rounded-2xl animate-in fade-in slide-in-from-top-5 duration-500">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-emerald-200">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="glass-morphism border-l-4 border-red-400 p-4 rounded-2xl animate-in fade-in slide-in-from-top-5 duration-500">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-200">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 lg:hidden"
         @click="sidebarOpen = false">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
    </div>
</body>
</html>