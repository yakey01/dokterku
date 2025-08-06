<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Dokterku Gaming</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://unpkg.com/lucide@latest/font/lucide.min.css" rel="stylesheet">
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(96, 165, 250, 0.5); }
            50% { box-shadow: 0 0 40px rgba(168, 85, 247, 0.8); }
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .animate-glow {
            animation: glow 2s ease-in-out infinite;
        }
        
        .bg-clip-text {
            -webkit-background-clip: text;
            background-clip: text;
        }
        
        .text-transparent {
            color: transparent;
        }
        
        /* Responsive Container System */
        .container {
            width: 100%;
            max-width: 384px; /* Mobile default */
            margin: 0 auto;
            transition: all 0.3s ease;
        }
        
        /* Mobile Landscape */
        @media (min-width: 576px) {
            .container {
                max-width: 540px;
            }
        }
        
        /* Tablet */
        @media (min-width: 768px) {
            .container {
                max-width: 720px;
            }
            
            .desktop-layout {
                display: grid;
                grid-template-columns: 1fr 400px;
                gap: 3rem;
                align-items: center;
                min-height: 100vh;
                padding: 2rem;
            }
            
            .hero-section {
                padding: 2rem;
            }
            
            .login-section {
                max-width: 400px;
            }
        }
        
        /* Desktop */
        @media (min-width: 1024px) {
            .container {
                max-width: 1200px;
            }
            
            .desktop-layout {
                grid-template-columns: 1fr 420px;
                gap: 4rem;
                padding: 3rem;
            }
        }
        
        /* Wide Desktop */
        @media (min-width: 1280px) {
            .container {
                max-width: 1400px;
            }
            
            .desktop-layout {
                grid-template-columns: 1fr 450px;
                gap: 5rem;
            }
        }
        
        /* Hide mobile status bar on desktop */
        @media (min-width: 768px) {
            .mobile-status-bar {
                display: none;
            }
        }
        
        /* Responsive typography */
        @media (min-width: 768px) {
            .hero-title {
                font-size: 3.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (min-width: 1024px) {
            .hero-title {
                font-size: 4rem;
            }
            
            .hero-subtitle {
                font-size: 1.5rem;
            }
        }
        
        /* Desktop hover effects */
        @media (hover: hover) {
            .desktop-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            }
            
            .desktop-input:hover {
                border-color: rgba(96, 165, 250, 0.5);
            }
            
            .desktop-button:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 20px rgba(168, 85, 247, 0.4);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white">
    <!-- Mobile Layout (0-767px) -->
    <div class="container md:hidden min-h-screen relative">
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-32 h-32 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
            <div class="absolute top-40 right-5 w-24 h-24 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
            <div class="absolute bottom-40 left-5 w-40 h-40 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
            
            <!-- Gaming particles -->
            @for ($i = 0; $i < 15; $i++)
                <div 
                    class="absolute w-1 h-1 bg-cyan-400/30 rounded-full animate-ping"
                    style="
                        top: {{ rand(0, 100) }}%;
                        left: {{ rand(0, 100) }}%;
                        animation-delay: {{ $i * 200 }}ms;
                        animation-duration: 3s;
                    "
                ></div>
            @endfor
        </div>

        <!-- Mobile Status Bar -->
        <div class="mobile-status-bar flex justify-between items-center px-6 pt-3 pb-2 text-white text-sm font-semibold relative z-10">
            <span id="currentTime">{{ date('H:i') }}</span>
            <div class="flex items-center space-x-1">
                <div class="flex space-x-1">
                    <div class="w-1 h-3 bg-white rounded-full"></div>
                    <div class="w-1 h-3 bg-white rounded-full"></div>
                    <div class="w-1 h-3 bg-white rounded-full"></div>
                    <div class="w-1 h-3 bg-gray-500 rounded-full"></div>
                </div>
                <div class="w-6 h-3 border border-white rounded-sm relative">
                    <div class="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 px-6 pt-8">
            
            <!-- Header with Logo -->
            <div class="text-center mb-8">
                <div class="relative">
                    <!-- Gaming-style logo container -->
                    <div class="w-24 h-24 mx-auto mb-6 relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-3xl animate-pulse shadow-2xl shadow-purple-500/30"></div>
                        <div class="absolute inset-2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-cyan-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        
                        <!-- Gaming corner accents -->
                        <div class="absolute -top-2 -left-2 w-4 h-4 border-l-2 border-t-2 border-cyan-400 rounded-tl-lg"></div>
                        <div class="absolute -top-2 -right-2 w-4 h-4 border-r-2 border-t-2 border-purple-400 rounded-tr-lg"></div>
                        <div class="absolute -bottom-2 -left-2 w-4 h-4 border-l-2 border-b-2 border-pink-400 rounded-bl-lg"></div>
                        <div class="absolute -bottom-2 -right-2 w-4 h-4 border-r-2 border-b-2 border-cyan-400 rounded-br-lg"></div>
                        
                        <!-- Level indicator -->
                        <div class="absolute -top-3 -right-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-black font-bold px-2 py-1 rounded-full text-xs border-2 border-white shadow-lg">
                            PRO
                        </div>
                    </div>
                    
                    <h1 class="hero-title text-3xl font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                        KLINIK DOKTERKU
                    </h1>
                    <p class="hero-subtitle text-purple-200 text-lg font-medium mb-2">
                        Sahabat Menuju Sehat
                    </p>
                    <p class="text-gray-400 text-sm">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>
            </div>

            <!-- Gaming Stats Cards -->
            <div class="grid grid-cols-3 gap-3 mb-8 sm:gap-4 sm:mb-10">
                <div class="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-xl p-3 border border-cyan-400/20 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-cyan-400/5 to-purple-400/5 animate-pulse"></div>
                    <svg class="w-6 h-6 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <div class="text-lg font-bold text-white">24/7</div>
                    <div class="text-xs text-gray-300">Service</div>
                </div>
                
                <div class="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-xl p-3 border border-purple-400/20 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-400/5 to-pink-400/5 animate-pulse"></div>
                    <svg class="w-6 h-6 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <div class="text-lg font-bold text-white">100%</div>
                    <div class="text-xs text-gray-300">Trusted</div>
                </div>
                
                <div class="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-xl p-3 border border-pink-400/20 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-pink-400/5 to-cyan-400/5 animate-pulse"></div>
                    <svg class="w-6 h-6 text-yellow-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <div class="text-lg font-bold text-white">4.9</div>
                    <div class="text-xs text-gray-300">Rating</div>
                </div>
            </div>

            <!-- Login Form -->
            <div class="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-3xl p-6 border border-white/20 shadow-2xl">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-white mb-2">Welcome Back</h2>
                    <p class="text-gray-300 text-sm">Masuk ke sistem klinik</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 text-green-400 rounded-lg text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg">
                        <ul class="text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('unified.login') }}" class="space-y-4" id="loginForm">
                    @csrf
                    
                    <!-- Email Field -->
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="email_or_username"
                            id="email_or_username"
                            value="{{ old('email_or_username') }}"
                            placeholder="Email atau Username"
                            class="w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-cyan-400/50 focus:bg-white/15 transition-all duration-300"
                            required
                            autofocus
                        >
                        <!-- Gaming accent -->
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Password"
                            class="w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-12 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-purple-400/50 focus:bg-white/15 transition-all duration-300"
                            required
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
                        >
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember"
                            class="w-4 h-4 text-purple-500 bg-white/10 border-white/20 rounded focus:ring-purple-500 focus:ring-2"
                        >
                        <label for="remember" class="ml-2 text-sm text-gray-300">
                            Ingat saya
                        </label>
                    </div>

                    <!-- Login Button -->
                    <button
                        type="submit"
                        id="loginButton"
                        class="w-full bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 hover:from-cyan-600 hover:via-purple-600 hover:to-pink-600 text-white font-bold py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 shadow-lg shadow-purple-500/30 disabled:opacity-70 relative overflow-hidden group"
                    >
                        <!-- Gaming button effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-white/10 via-white/5 to-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <span id="loginTextNormal" class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>LOGIN</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                        <span id="loginTextLoading" class="hidden items-center space-x-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Connecting...</span>
                        </span>
                    </button>
                </form>

                <!-- Additional Options -->
                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-center">
                        <div class="h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent flex-1"></div>
                        <span class="px-4 text-gray-400 text-sm">atau</span>
                        <div class="h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent flex-1"></div>
                    </div>

                    <button type="button" onclick="alert('Fitur login tamu belum tersedia')" class="w-full bg-white/10 hover:bg-white/15 border border-white/20 text-white py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span>Login sebagai Tamu</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 pb-8">
                <p class="text-gray-400 text-sm mb-2">
                    Butuh bantuan? Hubungi admin
                </p>
                <div class="flex items-center justify-center space-x-4 text-xs text-gray-500">
                    <span>© 2025 Klinik Dokterku</span>
                    <div class="w-1 h-1 bg-gray-500 rounded-full"></div>
                    <span>v2.1.0</span>
                </div>
            </div>
        </div>

        <!-- Gaming ambient effects -->
        <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-purple-900/20 to-transparent pointer-events-none"></div>
    </div>

    <!-- Desktop/Tablet Layout (768px+) -->
    <div class="hidden md:block">
        <div class="container desktop-layout">
            <!-- Hero Section -->
            <div class="hero-section">
                <!-- Animated Background Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-20 left-10 w-40 h-40 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
                    <div class="absolute top-40 right-20 w-32 h-32 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
                    <div class="absolute bottom-40 left-20 w-48 h-48 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
                    
                    <!-- Desktop particles -->
                    @for ($i = 0; $i < 25; $i++)
                        <div 
                            class="absolute w-1 h-1 bg-cyan-400/20 rounded-full animate-ping"
                            style="
                                top: {{ rand(0, 100) }}%;
                                left: {{ rand(0, 100) }}%;
                                animation-delay: {{ $i * 150 }}ms;
                                animation-duration: 4s;
                            "
                        ></div>
                    @endfor
                </div>

                <div class="relative z-10">
                    <!-- Desktop Logo -->
                    <div class="text-center mb-12">
                        <div class="w-32 h-32 mx-auto mb-8 relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-3xl animate-pulse shadow-2xl shadow-purple-500/30"></div>
                            <div class="absolute inset-2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl flex items-center justify-center">
                                <svg class="w-16 h-16 text-cyan-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            
                            <!-- Gaming corner accents -->
                            <div class="absolute -top-3 -left-3 w-6 h-6 border-l-2 border-t-2 border-cyan-400 rounded-tl-lg"></div>
                            <div class="absolute -top-3 -right-3 w-6 h-6 border-r-2 border-t-2 border-purple-400 rounded-tr-lg"></div>
                            <div class="absolute -bottom-3 -left-3 w-6 h-6 border-l-2 border-b-2 border-pink-400 rounded-bl-lg"></div>
                            <div class="absolute -bottom-3 -right-3 w-6 h-6 border-r-2 border-b-2 border-cyan-400 rounded-br-lg"></div>
                            
                            <!-- Level indicator -->
                            <div class="absolute -top-4 -right-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-black font-bold px-3 py-2 rounded-full text-sm border-2 border-white shadow-lg">
                                PRO
                            </div>
                        </div>
                        
                        <h1 class="hero-title text-4xl lg:text-5xl font-bold mb-4 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                            KLINIK DOKTERKU
                        </h1>
                        <p class="hero-subtitle text-purple-200 text-xl lg:text-2xl font-medium mb-4">
                            Sahabat Menuju Sehat
                        </p>
                        <p class="text-gray-400 text-base lg:text-lg">
                            {{ now()->translatedFormat('l, d F Y') }}
                        </p>
                    </div>

                    <!-- Desktop Stats Cards -->
                    <div class="grid grid-cols-3 gap-6 lg:gap-8 max-w-2xl mx-auto">
                        <div class="desktop-card bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-2xl p-6 border border-cyan-400/20 text-center relative overflow-hidden transition-all duration-300">
                            <div class="absolute inset-0 bg-gradient-to-r from-cyan-400/5 to-purple-400/5 animate-pulse"></div>
                            <svg class="w-8 h-8 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <div class="text-2xl font-bold text-white mb-2">24/7</div>
                            <div class="text-sm text-gray-300">Service</div>
                        </div>
                        
                        <div class="desktop-card bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-2xl p-6 border border-purple-400/20 text-center relative overflow-hidden transition-all duration-300">
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-400/5 to-pink-400/5 animate-pulse"></div>
                            <svg class="w-8 h-8 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <div class="text-2xl font-bold text-white mb-2">100%</div>
                            <div class="text-sm text-gray-300">Trusted</div>
                        </div>
                        
                        <div class="desktop-card bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-2xl p-6 border border-pink-400/20 text-center relative overflow-hidden transition-all duration-300">
                            <div class="absolute inset-0 bg-gradient-to-r from-pink-400/5 to-cyan-400/5 animate-pulse"></div>
                            <svg class="w-8 h-8 text-yellow-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            <div class="text-2xl font-bold text-white mb-2">4.9</div>
                            <div class="text-sm text-gray-300">Rating</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Section -->
            <div class="login-section">
                <div class="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-3xl p-8 border border-white/20 shadow-2xl">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl lg:text-3xl font-bold text-white mb-3">Welcome Back</h2>
                        <p class="text-gray-300 text-base lg:text-lg">Masuk ke sistem klinik</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 text-green-400 rounded-lg">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('unified.login') }}" class="space-y-6" id="desktopLoginForm">
                        @csrf
                        
                        <!-- Email Field -->
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                name="email_or_username"
                                value="{{ old('email_or_username') }}"
                                placeholder="Email atau Username"
                                class="desktop-input w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-4 py-4 text-white placeholder-gray-400 focus:outline-none focus:border-cyan-400/50 focus:bg-white/15 transition-all duration-300"
                                required
                                autofocus
                            >
                            <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                                <div class="w-2 h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input
                                type="password"
                                name="password"
                                id="desktopPassword"
                                placeholder="Password"
                                class="desktop-input w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-12 py-4 text-white placeholder-gray-400 focus:outline-none focus:border-purple-400/50 focus:bg-white/15 transition-all duration-300"
                                required
                            >
                            <button
                                type="button"
                                id="desktopTogglePassword"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
                            >
                                <svg id="desktopEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="desktopEyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                id="desktopRemember"
                                class="w-4 h-4 text-purple-500 bg-white/10 border-white/20 rounded focus:ring-purple-500 focus:ring-2"
                            >
                            <label for="desktopRemember" class="ml-3 text-gray-300">
                                Ingat saya
                            </label>
                        </div>

                        <!-- Login Button -->
                        <button
                            type="submit"
                            id="desktopLoginButton"
                            class="desktop-button w-full bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 hover:from-cyan-600 hover:via-purple-600 hover:to-pink-600 text-white font-bold py-4 rounded-xl transition-all duration-300 flex items-center justify-center space-x-3 shadow-lg shadow-purple-500/30 disabled:opacity-70 relative overflow-hidden group"
                        >
                            <div class="absolute inset-0 bg-gradient-to-r from-white/10 via-white/5 to-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            
                            <span id="desktopLoginTextNormal" class="flex items-center space-x-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="text-lg">LOGIN</span>
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </span>
                            <span id="desktopLoginTextLoading" class="hidden items-center space-x-3">
                                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-lg">Connecting...</span>
                            </span>
                        </button>
                    </form>

                    <!-- Additional Options -->
                    <div class="mt-8 space-y-4">
                        <div class="flex items-center justify-center">
                            <div class="h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent flex-1"></div>
                            <span class="px-4 text-gray-400">atau</span>
                            <div class="h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent flex-1"></div>
                        </div>

                        <button type="button" onclick="alert('Fitur login tamu belum tersedia')" class="w-full bg-white/10 hover:bg-white/15 border border-white/20 text-white py-4 rounded-xl transition-all duration-300 flex items-center justify-center space-x-3 group">
                            <svg class="w-5 h-5 text-blue-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Login sebagai Tamu</span>
                        </button>
                    </div>
                </div>

                <!-- Desktop Footer -->
                <div class="text-center mt-8">
                    <p class="text-gray-400 mb-3">
                        Butuh bantuan? Hubungi admin
                    </p>
                    <div class="flex items-center justify-center space-x-4 text-sm text-gray-500">
                        <span>© 2025 Klinik Dokterku</span>
                        <div class="w-1 h-1 bg-gray-500 rounded-full"></div>
                        <span>v2.1.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update time every second
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('currentTime').textContent = `${hours}:${minutes}`;
        }
        
        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Responsive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile form elements
            const form = document.getElementById('loginForm');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            const loginButton = document.getElementById('loginButton');
            const loginTextNormal = document.getElementById('loginTextNormal');
            const loginTextLoading = document.getElementById('loginTextLoading');
            
            // Desktop form elements
            const desktopForm = document.getElementById('desktopLoginForm');
            const desktopTogglePassword = document.getElementById('desktopTogglePassword');
            const desktopPasswordInput = document.getElementById('desktopPassword');
            const desktopEyeIcon = document.getElementById('desktopEyeIcon');
            const desktopEyeOffIcon = document.getElementById('desktopEyeOffIcon');
            const desktopLoginButton = document.getElementById('desktopLoginButton');
            const desktopLoginTextNormal = document.getElementById('desktopLoginTextNormal');
            const desktopLoginTextLoading = document.getElementById('desktopLoginTextLoading');
            
            // Password toggle function
            function setupPasswordToggle(toggleBtn, passwordField, eyeIconShow, eyeIconHide) {
                if (toggleBtn && passwordField) {
                    toggleBtn.addEventListener('click', function() {
                        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordField.setAttribute('type', type);
                        
                        // Toggle icons
                        if (type === 'password') {
                            eyeIconShow.classList.remove('hidden');
                            eyeIconHide.classList.add('hidden');
                        } else {
                            eyeIconShow.classList.add('hidden');
                            eyeIconHide.classList.remove('hidden');
                        }
                    });
                }
            }
            
            // Setup password toggle for mobile
            setupPasswordToggle(togglePassword, passwordInput, eyeIcon, eyeOffIcon);
            
            // Setup password toggle for desktop
            setupPasswordToggle(desktopTogglePassword, desktopPasswordInput, desktopEyeIcon, desktopEyeOffIcon);
            
            // Form submission function
            function setupFormSubmission(formElement, buttonElement, normalText, loadingText) {
                if (formElement && buttonElement) {
                    formElement.addEventListener('submit', function(e) {
                        // Check CSRF token
                        const tokenInput = formElement.querySelector('input[name="_token"]');
                        if (!tokenInput || !tokenInput.value) {
                            e.preventDefault();
                            alert('Token keamanan tidak valid. Halaman akan di-refresh.');
                            window.location.reload();
                            return false;
                        }
                        
                        // Show loading state
                        buttonElement.disabled = true;
                        normalText.classList.add('hidden');
                        loadingText.classList.remove('hidden');
                        loadingText.classList.add('flex');
                    });
                }
            }
            
            // Setup form submission for mobile
            setupFormSubmission(form, loginButton, loginTextNormal, loginTextLoading);
            
            // Setup form submission for desktop
            setupFormSubmission(desktopForm, desktopLoginButton, desktopLoginTextNormal, desktopLoginTextLoading);
            
            // Add focus effects to all inputs
            function setupInputEffects(formElement) {
                if (formElement) {
                    const inputs = formElement.querySelectorAll('input[type="text"], input[type="password"]');
                    inputs.forEach(input => {
                        input.addEventListener('focus', function() {
                            this.classList.add('ring-2', 'ring-cyan-400', 'ring-opacity-50');
                        });
                        
                        input.addEventListener('blur', function() {
                            this.classList.remove('ring-2', 'ring-cyan-400', 'ring-opacity-50');
                        });
                    });
                }
            }
            
            // Setup input effects for both forms
            setupInputEffects(form);
            setupInputEffects(desktopForm);
            
            // Desktop keyboard shortcuts
            if (window.matchMedia('(min-width: 768px)').matches) {
                document.addEventListener('keydown', function(e) {
                    // Enter key to focus first input or submit
                    if (e.key === 'Enter' && !e.target.matches('input, button')) {
                        e.preventDefault();
                        const firstInput = desktopForm.querySelector('input[type="text"]');
                        if (firstInput) firstInput.focus();
                    }
                    
                    // Escape key to clear form
                    if (e.key === 'Escape') {
                        desktopForm.reset();
                    }
                });
            }
        });
    </script>
</body>
</html>