import React, { useState, useEffect, useRef } from 'react';
import { Eye, EyeOff, Lock, User, Stethoscope, Heart, Shield, Star, ChevronRight, CheckCircle, Activity, Zap } from 'lucide-react';
import { LoginSuccessAnimation } from '../utils/LoginSuccessAnimation';

interface WelcomeLoginProps {
  onLogin?: () => void;
}

const WelcomeLogin = ({ onLogin }: WelcomeLoginProps) => {
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [loginSuccess, setLoginSuccess] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [particles, setParticles] = useState<Array<{id: number, top: string, left: string, delay: string}>>([]);
  
  const loginButtonRef = useRef<HTMLButtonElement>(null);
  const loginFormRef = useRef<HTMLDivElement>(null);
  const animationRef = useRef<LoginSuccessAnimation | null>(null);

  useEffect(() => {
    // Check if mobile on mount
    const checkMobile = () => {
      setIsMobile(window.innerWidth < 768);
    };
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    // Generate particles
    const generateParticles = () => {
      const particleCount = isMobile ? 8 : 15;
      const newParticles = Array.from({ length: particleCount }, (_, i) => ({
        id: i,
        top: `${Math.random() * 100}%`,
        left: `${Math.random() * 100}%`,
        delay: `${i * 200}ms`
      }));
      setParticles(newParticles);
    };
    
    generateParticles();
    
    // Initialize animation class
    animationRef.current = new LoginSuccessAnimation();

    // Set viewport meta tag for mobile
    const viewport = document.querySelector('meta[name="viewport"]');
    if (!viewport) {
      const meta = document.createElement('meta');
      meta.name = 'viewport';
      meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
      document.head.appendChild(meta);
    }

    // Inject CSS for animations
    const style = document.createElement('style');
    style.textContent = `
      @keyframes rippleAnimation {
        to {
          transform: scale(1);
          opacity: 0;
        }
      }

      @keyframes successMessageAnimation {
        0% {
          transform: translate(-50%, -50%) scale(0);
          opacity: 0;
        }
        15% {
          transform: translate(-50%, -50%) scale(1.1);
          opacity: 1;
        }
        30% {
          transform: translate(-50%, -50%) scale(1);
        }
        85% {
          transform: translate(-50%, -50%) scale(1);
          opacity: 1;
        }
        100% {
          transform: translate(-50%, -50%) scale(0.8);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);

    return () => {
      window.removeEventListener('resize', checkMobile);
      if (animationRef.current) {
        animationRef.current.cleanup();
      }
      if (document.head.contains(style)) {
        document.head.removeChild(style);
      }
    };
  }, [isMobile]);

  const handleLogin = async () => {
    setIsLoading(true);
    
    // Debug logging
    console.log('🔄 Starting login process...');
    
    try {
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      // Submit login form
      const response = await fetch('/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          email_or_username: email,
          password: password,
          remember: false
        }),
        credentials: 'same-origin'
      });

      if (response.ok) {
        setIsLoading(false);
        setLoginSuccess(true);
        
        console.log('✅ Login successful, triggering animation...');
        
        // Trigger success animation
        if (animationRef.current && loginButtonRef.current && loginFormRef.current) {
          console.log('🎨 All refs available, starting animation...');
          animationRef.current.playLoginSuccessAnimation({
            loginButton: loginButtonRef.current,
            loginForm: loginFormRef.current,
            showParticles: true,
            showRipple: true,
            showSuccessMessage: true,
            successMessage: '🎉 Selamat Datang di Klinik Dokterku!',
            slideDirection: 'left',
            onComplete: () => {
              // Redirect ke dashboard atau halaman utama
              console.log('🎯 Animation completed - ready to redirect');
              if (onLogin) {
                onLogin();
              }
              // The server will handle the redirect based on role
              window.location.href = (response as any).url || '/dokter/mobile-app';
            }
          });
        } else {
          // Fallback redirect without animation
          window.location.href = (response as any).url || '/dokter/mobile-app';
        }
      } else {
        const data = await response.json();
        setIsLoading(false);
        alert(data.message || 'Login failed. Please check your credentials.');
      }
    } catch (error) {
      console.error('Login error:', error);
      setIsLoading(false);
      alert('An error occurred during login. Please try again.');
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white overflow-hidden">
      {/* Mobile-first responsive container with proper breakpoints */}
      <div className="w-full max-w-sm sm:max-w-md lg:max-w-lg mx-auto min-h-screen relative px-4 sm:px-6 lg:px-8">
        
        {/* Animated Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-10 w-32 h-32 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
          <div className="absolute top-40 right-5 w-24 h-24 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
          <div className="absolute bottom-40 left-5 w-40 h-40 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
          
          {/* Gaming particles - reduced on mobile for performance */}
          {particles.map((particle) => (
            <div
              key={particle.id}
              className="absolute w-1 h-1 bg-cyan-400/30 rounded-full animate-ping"
              style={{
                top: particle.top,
                left: particle.left,
                animationDelay: particle.delay,
                animationDuration: '3s'
              }}
            />
          ))}
        </div>

        {/* Main Content - Responsive padding */}
        <div className="relative z-10 pt-4 sm:pt-6 lg:pt-8" ref={loginFormRef}>
          
          {/* Header with Logo - Mobile optimized spacing */}
          <div className="text-center mb-6 sm:mb-8">
            <div className="relative">
              {/* Gaming-style logo container - Responsive sizing */}
              <div className="w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 mx-auto mb-4 sm:mb-6 relative">
                <div className="absolute inset-0 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-3xl animate-pulse shadow-2xl shadow-purple-500/30"></div>
                <div className="absolute inset-2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl flex items-center justify-center">
                  <Stethoscope className="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 text-cyan-400 animate-bounce" />
                </div>
                
                {/* Gaming corner accents */}
                <div className="absolute -top-2 -left-2 w-4 h-4 border-l-2 border-t-2 border-cyan-400 rounded-tl-lg"></div>
                <div className="absolute -top-2 -right-2 w-4 h-4 border-r-2 border-t-2 border-purple-400 rounded-tr-lg"></div>
                <div className="absolute -bottom-2 -left-2 w-4 h-4 border-l-2 border-b-2 border-pink-400 rounded-bl-lg"></div>
                <div className="absolute -bottom-2 -right-2 w-4 h-4 border-r-2 border-b-2 border-cyan-400 rounded-br-lg"></div>
                
                {/* Level indicator */}
                <div className="absolute -top-3 -right-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-black font-bold px-2 py-1 rounded-full text-xs border-2 border-white shadow-lg">
                  PRO
                </div>
              </div>
              
              <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                KLINIK DOKTERKU
              </h1>
              <p className="text-purple-200 text-base sm:text-lg lg:text-xl font-medium mb-2">
                Sahabat Menuju Sehat
              </p>
            </div>
          </div>

          {/* Gaming Stats Cards - Mobile responsive grid */}
          <div className="grid grid-cols-3 gap-2 sm:gap-3 lg:gap-4 mb-6 sm:mb-8">
            <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-lg sm:rounded-xl p-2 sm:p-3 lg:p-4 border border-cyan-400/20 text-center relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-400/5 to-purple-400/5 animate-pulse"></div>
              <Heart className="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 text-red-400 mx-auto mb-1 sm:mb-2" />
              <div className="text-sm sm:text-base lg:text-lg font-bold text-white">24/7</div>
              <div className="text-xs sm:text-xs lg:text-sm text-gray-300">Service</div>
            </div>
            
            <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-lg sm:rounded-xl p-2 sm:p-3 lg:p-4 border border-purple-400/20 text-center relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-r from-purple-400/5 to-pink-400/5 animate-pulse"></div>
              <Shield className="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 text-green-400 mx-auto mb-1 sm:mb-2" />
              <div className="text-sm sm:text-base lg:text-lg font-bold text-white">100%</div>
              <div className="text-xs sm:text-xs lg:text-sm text-gray-300">Trusted</div>
            </div>
            
            <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-lg sm:rounded-xl p-2 sm:p-3 lg:p-4 border border-pink-400/20 text-center relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-r from-pink-400/5 to-cyan-400/5 animate-pulse"></div>
              <Star className="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 text-yellow-400 mx-auto mb-1 sm:mb-2" />
              <div className="text-sm sm:text-base lg:text-lg font-bold text-white">4.9</div>
              <div className="text-xs sm:text-xs lg:text-sm text-gray-300">Rating</div>
            </div>
          </div>

          {/* Login Form - Mobile optimized padding and radius */}
          <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 border border-white/20 shadow-2xl">
            <div className="text-center mb-6">
              <h2 className="text-xl font-bold text-white mb-2">Welcome Back</h2>
              <p className="text-gray-300 text-sm">Masuk ke sistem klinik</p>
            </div>

            {!loginSuccess ? (
              <form onSubmit={(e) => { e.preventDefault(); handleLogin(); }} className="space-y-4">
                {/* Email Field */}
                <div className="relative">
                  <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <User className="w-5 h-5 text-gray-400" />
                  </div>
                  <input
                    type="text"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="Email atau Username"
                    className="w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-4 py-3 text-base sm:text-base lg:text-lg text-white placeholder-gray-400 focus:outline-none focus:border-cyan-400/50 focus:bg-white/15 transition-all duration-300 touch-manipulation"
                  />
                  <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <div className="w-2 h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                  </div>
                </div>

                {/* Password Field */}
                <div className="relative">
                  <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <Lock className="w-5 h-5 text-gray-400" />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Password"
                    className="w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-12 py-3 text-base sm:text-base lg:text-lg text-white placeholder-gray-400 focus:outline-none focus:border-purple-400/50 focus:bg-white/15 transition-all duration-300 touch-manipulation"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors p-1 touch-manipulation"
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>

                {/* Login Button */}
                <button
                  ref={loginButtonRef}
                  type="submit"
                  disabled={isLoading}
                  className="w-full bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 hover:from-cyan-600 hover:via-purple-600 hover:to-pink-600 text-white font-bold py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 shadow-lg shadow-purple-500/30 disabled:opacity-70 relative overflow-hidden group touch-manipulation text-base sm:text-lg"
                  style={{ minHeight: '44px' }}
                >
                  <div className="absolute inset-0 bg-gradient-to-r from-white/10 via-white/5 to-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                  
                  {isLoading ? (
                    <>
                      <Activity className="w-5 h-5 animate-spin" />
                      <span>Connecting...</span>
                    </>
                  ) : (
                    <>
                      <Zap className="w-5 h-5" />
                      <span>LOGIN</span>
                      <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" />
                    </>
                  )}
                </button>
              </form>
            ) : (
              <div className="text-center py-8">
                <CheckCircle className="w-16 h-16 text-green-400 mx-auto mb-4 animate-bounce" />
                <h3 className="text-xl font-bold text-white mb-2">Login Berhasil!</h3>
                <p className="text-gray-300">Mengalihkan ke dashboard...</p>
              </div>
            )}

            {!loginSuccess && (
              <div className="mt-6 space-y-3">
                <div className="flex items-center justify-center">
                  <div className="h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent flex-1"></div>
                  <span className="px-4 text-gray-400 text-sm">atau</span>
                  <div className="h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent flex-1"></div>
                </div>

                <button className="w-full bg-white/10 hover:bg-white/15 border border-white/20 text-white py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 touch-manipulation text-sm sm:text-base" style={{ minHeight: '44px' }}>
                  <Shield className="w-5 h-5 text-blue-400" />
                  <span>Login sebagai Tamu</span>
                </button>
              </div>
            )}
          </div>

          {/* Footer - Mobile optimized spacing */}
          <div className="text-center mt-6 pb-safe">
            <p className="text-gray-400 text-xs sm:text-sm lg:text-base mb-2">
              Butuh bantuan? Hubungi admin
            </p>
            <div className="flex items-center justify-center space-x-4 text-xs sm:text-xs lg:text-sm text-gray-500">
              <span>© 2025 Klinik Dokterku</span>
              <div className="w-1 h-1 bg-gray-500 rounded-full"></div>
              <span>v2.1.0</span>
            </div>
          </div>
        </div>

        {/* Gaming ambient effects */}
        <div className="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-purple-900/20 to-transparent pointer-events-none"></div>
      </div>
    </div>
  );
};

export default WelcomeLogin;