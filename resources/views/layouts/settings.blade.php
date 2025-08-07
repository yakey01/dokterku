<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Pengaturan Admin') - {{ config('app.name', 'Dokterku') }}</title>

        <!-- Optimized System Fonts - Medical Interface Optimized -->

        <!-- Tailwind CSS & Alpine.js -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Additional styles for dark mode compatibility -->
        <style>
            .fi-dark {
                --sidebar-bg: rgb(17 24 39);
                --content-bg: rgb(31 41 55);
                --border-color: rgb(55 65 81);
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-900 text-gray-100 dark">
        <div class="min-h-screen">
            <!-- Navigation Header -->
            <nav class="bg-gray-800 border-b border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <!-- Logo and App Name -->
                            <div class="flex-shrink-0 flex items-center">
                                <h2 class="font-semibold text-xl text-gray-100 leading-tight">
                                    {{ config('app.name', 'Dokterku') }} - Admin Settings
                                </h2>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-300">{{ auth()->user()->name }}</span>
                            <a href="/admin" class="text-blue-400 hover:text-blue-300 text-sm">
                                Kembali ke Dashboard
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-300 text-sm">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="flex">
                <!-- Sidebar -->
                <aside class="w-64 bg-gray-800 min-h-screen border-r border-gray-700">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-100 mb-4">Menu Pengaturan</h3>
                        <nav class="space-y-2">
                            <a href="{{ route('settings.users.index') }}" 
                               class="flex items-center px-3 py-2 text-sm rounded-md {{ request()->routeIs('settings.users.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                                Manajemen User
                            </a>
                            <a href="{{ route('settings.config.index') }}" 
                               class="flex items-center px-3 py-2 text-sm rounded-md {{ request()->routeIs('settings.config.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Konfigurasi Sistem
                            </a>
                            <a href="{{ route('settings.backup.index') }}" 
                               class="flex items-center px-3 py-2 text-sm rounded-md {{ request()->routeIs('settings.backup.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                </svg>
                                Backup & Export
                            </a>
                            <a href="{{ route('settings.telegram.index') }}" 
                               class="flex items-center px-3 py-2 text-sm rounded-md {{ request()->routeIs('settings.telegram.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                                </svg>
                                Notifikasi Telegram
                            </a>
                        </nav>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 bg-gray-900">
                    <div class="p-6">
                        <!-- Page Header -->
                        @isset($header)
                            <div class="mb-6">
                                {{ $header }}
                            </div>
                        @endisset

                        <!-- Flash Messages -->
                        @if(session('success'))
                            <div class="mb-4 bg-green-800 border border-green-600 text-green-100 px-4 py-3 rounded">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="mb-4 bg-red-800 border border-red-600 text-red-100 px-4 py-3 rounded">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="mb-4 bg-red-800 border border-red-600 text-red-100 px-4 py-3 rounded">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Page Content -->
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <!-- Scripts -->
        <script>
            // Add any JavaScript needed for interactive elements
            function confirmAction(message) {
                return confirm(message);
            }
        </script>
    </body>
</html>