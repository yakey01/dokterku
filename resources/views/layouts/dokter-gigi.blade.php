<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Dokterku') }} - Dokter Gigi Dashboard</title>

    <!-- Optimized System Fonts - Medical Professional Interface -->
    
    <!-- Heroicons for icons -->
    <script src="https://unpkg.com/heroicons@2.0.16/outline/index.js" type="module"></script>

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css'])
    
    <!-- Custom styles for isolation -->
    <style>
        /* Complete CSS isolation for dentist dashboard */
        .dokter-gigi-dashboard * {
            box-sizing: border-box;
        }
        
        .dokter-gigi-dashboard {
            font-family: 'Inter', sans-serif;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            /* Reset any global transformations */
            transform: none !important;
            transition: none !important;
        }
        
        /* Prevent any global CSS from affecting this dashboard */
        .dokter-gigi-dashboard * {
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }
        
        /* Force proper icon sizing */
        .dokter-gigi-dashboard svg {
            width: 1.25rem !important;  /* Force w-5 */
            height: 1.25rem !important; /* Force h-5 */
            transform: none !important;
            scale: 1 !important;
        }
        
        /* Force proper Tailwind utility classes */
        .dokter-gigi-dashboard .w-5 {
            width: 1.25rem !important;
        }
        
        .dokter-gigi-dashboard .h-5 {
            height: 1.25rem !important;
        }
        
        .dokter-gigi-dashboard .w-8 {
            width: 2rem !important;
        }
        
        .dokter-gigi-dashboard .h-8 {
            height: 2rem !important;
        }
        
        .dokter-gigi-dashboard .w-10 {
            width: 2.5rem !important;
        }
        
        .dokter-gigi-dashboard .h-10 {
            height: 2.5rem !important;
        }
        
        .dokter-gigi-dashboard .w-12 {
            width: 3rem !important;
        }
        
        .dokter-gigi-dashboard .h-12 {
            height: 3rem !important;
        }
        
        /* Override any potential external CSS conflicts */
        .dokter-gigi-dashboard .card {
            background: white !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1) !important;
        }
        
        /* Ensure proper button styling */
        .dokter-gigi-dashboard button {
            transform: none !important;
            transition: color 0.2s, background-color 0.2s !important;
        }
        
        /* Ensure proper link styling */
        .dokter-gigi-dashboard a {
            transform: none !important;
            transition: color 0.2s !important;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="dokter-gigi-dashboard min-h-screen">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-semibold text-gray-900">Dokterku - Dokter Gigi</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('dokter-gigi.dashboard') }}" 
                               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('dokter-gigi.dashboard') ? 'border-blue-500 text-blue-600' : '' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('dokter-gigi.jaspel') }}" 
                               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('dokter-gigi.jaspel') ? 'border-blue-500 text-blue-600' : '' }}">
                                Jaspel
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>