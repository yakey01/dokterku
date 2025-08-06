<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Dokterku</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }
        .glass-effect {
            backdrop-filter: blur(16px);
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
        }
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.25);
        }
        .scale-110 {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-clinic-primary rounded-full mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">DOKTERKU</h1>
            <p class="text-slate-300 text-sm">Sistem Manajemen Klinik</p>
        </div>

        <!-- Login Form -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl">
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

            <form method="POST" action="{{ route('unified.login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email_or_username" class="block text-sm font-medium text-slate-200 mb-2">
                        Email atau Username
                    </label>
                    <input 
                        id="email_or_username" 
                        name="email_or_username" 
                        type="text" 
                        required 
                        class="input-focus w-full px-4 py-3 bg-slate-800/50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-clinic-primary focus:border-transparent"
                        value="{{ old('email_or_username') }}" 
                        autofocus 
                        placeholder="Masukkan email atau username"
                        autocomplete="username"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-200 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            class="input-focus w-full px-4 py-3 pr-12 bg-slate-800/50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-clinic-primary focus:border-transparent"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200 transition-colors duration-200"
                            aria-label="Toggle password visibility"
                            title="Klik untuk menampilkan/menyembunyikan password"
                        >
                            <!-- Eye Icon (Hidden) -->
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <!-- Eye Slash Icon (Visible) -->
                            <svg id="eyeSlashIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        id="remember"
                        class="w-4 h-4 text-clinic-primary bg-slate-800 border-slate-600 rounded focus:ring-clinic-primary focus:ring-2"
                    >
                    <label for="remember" class="ml-2 text-sm text-slate-300">
                        Ingat saya
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="btn-hover w-full py-3 px-4 bg-gradient-to-r from-clinic-primary to-clinic-secondary text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-clinic-primary focus:ring-offset-2 focus:ring-offset-slate-900"
                >
                    Masuk ke Sistem
                </button>
            </form>
        </div>

        <!-- Clinic Motto -->
        <div class="text-center mt-8">
            <div class="inline-flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-clinic-primary/20 to-clinic-secondary/20 rounded-full border border-clinic-primary/30">
                <svg class="w-5 h-5 text-clinic-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span class="text-clinic-accent font-medium text-sm tracking-wide">
                    SAHABAT MENUJU SEHAT
                </span>
                <svg class="w-5 h-5 text-clinic-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-slate-400 text-xs">
                © {{ date('Y') }} Dokterku. Sistem manajemen klinik terpercaya.
            </p>
        </div>
    </div>

    <script>
        // Auto-detect system dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }

        // Smooth form animation & Password toggle
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[type="text"], input[type="password"]');
            
            // Form input animations
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.classList.add('ring-2', 'ring-clinic-primary');
                });
                
                input.addEventListener('blur', function() {
                    this.classList.remove('ring-2', 'ring-clinic-primary');
                });
            });

            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeSlashIcon = document.getElementById('eyeSlashIcon');

            if (togglePassword && passwordInput && eyeIcon && eyeSlashIcon) {
                togglePassword.addEventListener('click', function() {
                    // Toggle the type attribute
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle the icons
                    if (type === 'password') {
                        eyeIcon.style.display = 'none';
                        eyeSlashIcon.style.display = 'block';
                    } else {
                        eyeIcon.style.display = 'block';
                        eyeSlashIcon.style.display = 'none';
                    }
                });

                // Add hover effect for the toggle button
                togglePassword.addEventListener('mouseenter', function() {
                    this.classList.add('scale-110');
                });
                
                togglePassword.addEventListener('mouseleave', function() {
                    this.classList.remove('scale-110');
                });

                // Keyboard shortcut: Ctrl+Shift+P to toggle password
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                        e.preventDefault();
                        togglePassword.click();
                    }
                });
            }
        });
    </script>
</body>
</html>