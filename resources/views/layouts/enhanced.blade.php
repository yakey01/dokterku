<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Enhanced Petugas Dashboard') - Dokterku</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Minimal Design System CSS -->
    <link href="{{ asset('css/design-system/tokens.css') }}" rel="stylesheet">
    <link href="{{ asset('css/design-system/components.css') }}" rel="stylesheet">
    
    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'medical': {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22'
                        },
                        'petugas': {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49'
                        }
                    }
                }
            }
        }
    </script>
    
    @stack('styles')
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900" x-data="enhancedLayout()">
    
    <!-- Mobile menu overlay -->
    <div x-show="sidebarOpen" class="fixed inset-0 flex z-40 md:hidden" style="display: none;">
        <div @click="sidebarOpen = false" class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-900">
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Mobile sidebar content -->
            <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                @include('layouts.partials.enhanced-sidebar')
            </div>
        </div>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700">
            @include('layouts.partials.enhanced-sidebar')
        </div>
    </div>

    <!-- Main content -->
    <div class="md:pl-64 flex flex-col flex-1">
        <!-- Top navigation -->
        <div class="sticky top-0 z-10 md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <button @click="sidebarOpen = true" class="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-medical-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Page header -->
        <div class="bg-white dark:bg-gray-900 shadow border-b border-gray-200 dark:border-gray-700">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                            @yield('page-title', 'Enhanced Dashboard')
                        </h1>
                        @hasSection('page-description')
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @yield('page-description')
                        </p>
                        @endif
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle -->
                        <button @click="toggleTheme()" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors">
                            <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                            <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </button>
                        
                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-3 text-sm bg-white dark:bg-gray-800 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-medical-500 p-2">
                                <div class="h-8 w-8 rounded-full bg-medical-500 flex items-center justify-center">
                                    <span class="text-white font-medium text-sm">{{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'G' }}</span>
                                </div>
                                <span class="hidden md:block text-gray-700 dark:text-gray-300 font-medium">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50" style="display: none;">
                                <a href="/petugas" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Standard Dashboard
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                @yield('page-actions')
            </div>
        </div>

        <!-- Main content area -->
        <main class="flex-1 relative overflow-y-auto focus:outline-none">
            <div class="py-6">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Success/Error Alerts -->
    <div id="alerts-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    @stack('scripts')
    
    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function enhancedLayout() {
            return {
                sidebarOpen: false,
                isDark: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
                
                init() {
                    this.updateTheme();
                },
                
                toggleTheme() {
                    this.isDark = !this.isDark;
                    this.updateTheme();
                },
                
                updateTheme() {
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    }
                }
            }
        }
        
        // Global alert function
        window.showAlert = function(type, message, duration = 3000) {
            const alertsContainer = document.getElementById('alerts-container');
            const alertId = 'alert-' + Date.now();
            const alertClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            
            const alertHtml = `
                <div id="${alertId}" class="${alertClass} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full opacity-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                ${type === 'success' 
                                    ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>'
                                    : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
                                }
                            </svg>
                            <span class="text-sm font-medium">${message}</span>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white/80 hover:text-white">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            alertsContainer.insertAdjacentHTML('beforeend', alertHtml);
            
            const alertElement = document.getElementById(alertId);
            
            // Animate in
            setTimeout(() => {
                alertElement.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                alertElement.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    if (alertElement.parentNode) {
                        alertElement.remove();
                    }
                }, 300);
            }, duration);
        };
    </script>
</body>
</html>