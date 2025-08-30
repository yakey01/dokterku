import React, { useState, useEffect, useRef } from 'react';
import { Eye, EyeOff, Lock, User, Stethoscope, Heart, Shield, Star, ChevronRight, CheckCircle, Activity, Zap, Mail, ArrowLeft, Crown, Sparkles } from 'lucide-react';
import { LoginSuccessAnimation } from '../utils/LoginSuccessAnimation';

interface WelcomeLoginProps {
  onLogin?: () => void;
}

const WelcomeLogin = ({ onLogin }: WelcomeLoginProps) => {
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [loginSuccess, setLoginSuccess] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [particles, setParticles] = useState<Array<{id: number, top: string, left: string, delay: string}>>([]);
  
  // New state for enhanced features
  const [isAdmin, setIsAdmin] = useState(false);
  const [showForgotPassword, setShowForgotPassword] = useState(false);
  const [resetEmail, setResetEmail] = useState('');
  const [resetLoading, setResetLoading] = useState(false);
  const [resetSuccess, setResetSuccess] = useState(false);
  const [emailErrors, setEmailErrors] = useState<string[]>([]);
  
  const loginButtonRef = useRef<HTMLButtonElement>(null);
  const loginFormRef = useRef<HTMLDivElement>(null);
  const animationRef = useRef<LoginSuccessAnimation | null>(null);

  // Admin detection helper
  const detectAdminUser = (emailInput: string) => {
    const adminPatterns = [
      /^admin@/i,
      /@admin\./i,
      /^administrator@/i,
      /^root@/i,
      /^super@/i,
      /admin$/i
    ];
    return adminPatterns.some(pattern => pattern.test(emailInput));
  };

  // Email validation helper
  const validateEmail = (emailInput: string): string[] => {
    const errors: string[] = [];
    if (!emailInput) {
      errors.push('Email wajib diisi');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput)) {
      errors.push('Format email tidak valid');
    }
    return errors;
  };

  // Handle forgot password
  const handleForgotPassword = async () => {
    const errors = validateEmail(resetEmail);
    setEmailErrors(errors);
    
    if (errors.length > 0) return;
    
    setResetLoading(true);
    
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      const response = await fetch('/forgot-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken || ''
        },
        body: new URLSearchParams({
          _token: csrfToken || '',
          email: resetEmail
        }),
        credentials: 'same-origin'
      });

      if (response.ok) {
        setResetSuccess(true);
        setTimeout(() => {
          setShowForgotPassword(false);
          setResetSuccess(false);
          setResetEmail('');
        }, 3000);
      } else {
        const errorData = await response.json().catch(() => ({ message: 'Terjadi kesalahan' }));
        setEmailErrors([errorData.message || 'Gagal mengirim email reset']);
      }
    } catch (error) {
      setEmailErrors(['Terjadi kesalahan jaringan. Silakan coba lagi.']);
    } finally {
      setResetLoading(false);
    }
  };

  useEffect(() => {
    // Admin detection when email changes
    setIsAdmin(detectAdminUser(email));
  }, [email]);

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
      // Get CSRF token from meta tag with enhanced debugging
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      console.log('🔑 CSRF Token found:', csrfToken ? 'Yes' : 'No');
      console.log('🔑 CSRF Token value:', csrfToken ? csrfToken.substring(0, 10) + '...' : 'null');
      
      if (!csrfToken) {
        console.error('❌ No CSRF token found in meta tag');
        // Try to regenerate token by refreshing the page
        console.log('🔄 Attempting to refresh page for new CSRF token...');
        window.location.reload();
        return;
      }
      
      // Validate token format
      if (csrfToken.length < 32) {
        console.error('❌ CSRF token appears invalid (too short)');
        alert('Invalid CSRF token. Please refresh the page and try again.');
        setIsLoading(false);
        return;
      }
      
      // Submit login form to web endpoint
      const response = await fetch('/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: new URLSearchParams({
          _token: csrfToken,
          email_or_username: email,
          password: password,
          remember: rememberMe ? '1' : '0'
        }),
        credentials: 'same-origin'
      });

      console.log('📡 Response status:', response.status);
      console.log('📡 Response headers:', Object.fromEntries(response.headers.entries()));

      // Handle successful response (could be redirect or JSON)
      if (response.status === 302 || response.redirected) {
        // Server redirected us - follow the redirect
        console.log('✅ Login successful - server redirected to:', response.url);
        window.location.href = response.url;
        return;
      }
      
      if (response.ok) {
        // Try to parse JSON response
        let responseData;
        try {
          responseData = await response.json();
        } catch (e) {
          // Not JSON, assume successful login and redirect to admin
          console.log('✅ Login successful - redirecting to admin panel');
          window.location.href = '/admin';
          return;
        }
        
        setIsLoading(false);
        setLoginSuccess(true);
        
        console.log('✅ Login successful, response data:', responseData);
        
        // Check if response contains redirect URL
        const redirectUrl = responseData.url || responseData.redirect_url || '/admin';
        
        // Trigger success animation then redirect
        if (animationRef.current && loginButtonRef.current && loginFormRef.current) {
          console.log('🎨 Starting success animation...');
          animationRef.current.playLoginSuccessAnimation({
            loginButton: loginButtonRef.current,
            loginForm: loginFormRef.current,
            showParticles: true,
            showRipple: true,
            showSuccessMessage: true,
            successMessage: '🎉 Selamat Datang di Klinik Dokterku!',
            slideDirection: 'left',
            onComplete: () => {
              console.log('🎯 Animation completed - redirecting to:', redirectUrl);
              window.location.href = redirectUrl;
            }
          });
        } else {
          // Immediate redirect without animation
          console.log('🎯 Redirecting immediately to:', redirectUrl);
          window.location.href = redirectUrl;
        }
      } else {
        const errorData = await response.json().catch(() => ({ message: 'Unknown error occurred' }));
        setIsLoading(false);
        
        console.error('❌ Login failed:', errorData);
        
        if (response.status === 419) {
          console.log('🔄 CSRF token mismatch (419) - auto-refreshing page...');
          alert('Session expired. Page will refresh automatically.');
          window.location.reload();
        } else if (errorData.message && errorData.message.includes('CSRF')) {
          console.log('🔄 CSRF error in message - auto-refreshing page...');
          alert('CSRF token mismatch. Page will refresh automatically.');
          window.location.reload();
        } else {
          alert(errorData.message || 'Login failed. Please check your credentials.');
        }
      }
    } catch (error) {
      console.error('❌ Login error:', error);
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
              <div className="flex items-center justify-center mb-3">
                <h2 className="text-xl font-bold text-white">Welcome Back</h2>
                {isAdmin && (
                  <div className="ml-3 flex items-center bg-gradient-to-r from-yellow-400 to-orange-500 text-black px-3 py-1 rounded-full text-xs font-bold animate-pulse border-2 border-white shadow-lg">
                    <Crown className="w-3 h-3 mr-1" />
                    ADMIN
                  </div>
                )}
              </div>
              <p className="text-gray-300 text-sm">
                {isAdmin ? 'Panel Administrator Sistem' : 'Masuk ke sistem klinik'}
              </p>
              {isAdmin && (
                <div className="mt-2 flex items-center justify-center text-yellow-300 text-xs">
                  <Sparkles className="w-3 h-3 mr-1 animate-pulse" />
                  <span>Enhanced Security Mode</span>
                </div>
              )}
            </div>

            {!loginSuccess ? (
              <form onSubmit={(e) => { e.preventDefault(); handleLogin(); }} className="space-y-4">
                {/* Email Field with Admin Detection */}
                <div className="relative">
                  <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                    {isAdmin ? (
                      <Crown className="w-5 h-5 text-yellow-400 animate-pulse" />
                    ) : (
                      <User className="w-5 h-5 text-gray-400" />
                    )}
                  </div>
                  <input
                    type="text"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder={isAdmin ? "Admin Email" : "Email atau Username"}
                    className={`w-full bg-white/10 border rounded-xl pl-12 pr-4 py-3 text-base sm:text-base lg:text-lg text-white placeholder-gray-400 focus:outline-none transition-all duration-300 touch-manipulation ${
                      isAdmin 
                        ? 'border-yellow-400/50 focus:border-yellow-400 focus:bg-yellow-400/10 shadow-lg shadow-yellow-400/20'
                        : 'border-white/20 focus:border-cyan-400/50 focus:bg-white/15'
                    }`}
                  />
                  <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                    {isAdmin ? (
                      <div className="w-2 h-2 bg-yellow-400 rounded-full animate-ping"></div>
                    ) : (
                      <div className="w-2 h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                    )}
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
                  {/* Fixed: Icon container dengan positioning yang stabil */}
                  <div className="absolute right-2 top-2 bottom-2 w-10 flex items-center justify-center">
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="w-6 h-6 flex items-center justify-center text-gray-300 hover:text-white active:text-cyan-400 focus:text-cyan-400 transition-colors touch-manipulation rounded"
                      aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                    >
                      {/* Fixed: Kedua icon dalam container yang sama dengan opacity */}
                      <div className="relative w-5 h-5 flex items-center justify-center">
                        <EyeOff 
                          className={`w-5 h-5 drop-shadow-sm absolute transition-opacity duration-150 ${showPassword ? 'opacity-100' : 'opacity-0'}`}
                          strokeWidth={2.5} 
                        />
                        <Eye 
                          className={`w-5 h-5 drop-shadow-sm absolute transition-opacity duration-150 ${showPassword ? 'opacity-0' : 'opacity-100'}`}
                          strokeWidth={2.5} 
                        />
                      </div>
                    </button>
                  </div>
                </div>

                {/* Remember Me & Forgot Password */}
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="remember"
                      checked={rememberMe}
                      onChange={(e) => setRememberMe(e.target.checked)}
                      className="w-4 h-4 bg-white/10 border border-white/20 rounded text-purple-500 focus:ring-purple-400 focus:ring-2"
                    />
                    <label htmlFor="remember" className="ml-2 text-sm text-gray-300 select-none cursor-pointer">
                      Ingat saya
                    </label>
                  </div>
                  
                  <button
                    type="button"
                    onClick={() => {
                      setShowForgotPassword(true);
                      setResetEmail(email);
                    }}
                    className="text-sm text-cyan-400 hover:text-cyan-300 transition-colors duration-300 flex items-center group"
                  >
                    <span>Lupa Password?</span>
                    <ChevronRight className="w-3 h-3 ml-1 group-hover:translate-x-0.5 transition-transform duration-300" />
                  </button>
                </div>

                {/* Login Button with Admin Styling */}
                <button
                  ref={loginButtonRef}
                  type="submit"
                  disabled={isLoading}
                  className={`w-full font-bold py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 shadow-lg disabled:opacity-70 relative overflow-hidden group touch-manipulation text-base sm:text-lg ${
                    isAdmin 
                      ? 'bg-gradient-to-r from-yellow-500 via-orange-500 to-red-500 hover:from-yellow-600 hover:via-orange-600 hover:to-red-600 shadow-yellow-500/30 text-black'
                      : 'bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 hover:from-cyan-600 hover:via-purple-600 hover:to-pink-600 shadow-purple-500/30 text-white'
                  }`}
                  style={{ minHeight: '44px' }}
                >
                  <div className="absolute inset-0 bg-gradient-to-r from-white/10 via-white/5 to-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                  
                  {isLoading ? (
                    <>
                      <Activity className="w-5 h-5 animate-spin" />
                      <span>{isAdmin ? 'Verifying Admin...' : 'Connecting...'}</span>
                    </>
                  ) : (
                    <>
                      {isAdmin ? (
                        <>
                          <Crown className="w-5 h-5 animate-pulse" />
                          <span>ADMIN LOGIN</span>
                          <Shield className="w-5 h-5 group-hover:scale-110 transition-transform duration-300" />
                        </>
                      ) : (
                        <>
                          <Zap className="w-5 h-5" />
                          <span>LOGIN</span>
                          <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" />
                        </>
                      )}
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

        {/* Forgot Password Modal/Panel */}
        {showForgotPassword && (
          <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div className="bg-gradient-to-br from-slate-800/95 via-slate-700/95 to-slate-800/95 backdrop-blur-xl rounded-3xl p-6 sm:p-8 border border-white/20 shadow-2xl max-w-md w-full relative overflow-hidden">
              {/* Close button */}
              <button
                onClick={() => {
                  setShowForgotPassword(false);
                  setResetEmail('');
                  setEmailErrors([]);
                  setResetSuccess(false);
                }}
                className="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors duration-300"
              >
                <ArrowLeft className="w-5 h-5" />
              </button>

              {/* Header */}
              <div className="text-center mb-6">
                <div className="w-16 h-16 mx-auto mb-4 relative">
                  <div className="absolute inset-0 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-2xl animate-pulse"></div>
                  <div className="absolute inset-2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl flex items-center justify-center">
                    <Mail className="w-6 h-6 text-cyan-400" />
                  </div>
                </div>
                <h2 className="text-2xl font-bold text-white mb-2">Reset Password</h2>
                <p className="text-gray-300 text-sm">
                  Masukkan email Anda untuk menerima link reset password
                </p>
              </div>

              {!resetSuccess ? (
                <div className="space-y-4">
                  {/* Email input */}
                  <div className="relative">
                    <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                      <Mail className="w-5 h-5 text-gray-400" />
                    </div>
                    <input
                      type="email"
                      value={resetEmail}
                      onChange={(e) => {
                        setResetEmail(e.target.value);
                        setEmailErrors([]);
                      }}
                      placeholder="Masukkan email Anda"
                      className="w-full bg-white/10 border border-white/20 rounded-xl pl-12 pr-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-cyan-400/50 focus:bg-white/15 transition-all duration-300"
                    />
                    {detectAdminUser(resetEmail) && (
                      <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <Crown className="w-4 h-4 text-yellow-400 animate-pulse" />
                      </div>
                    )}
                  </div>

                  {/* Error messages */}
                  {emailErrors.length > 0 && (
                    <div className="bg-red-500/20 border border-red-500/30 rounded-xl p-3">
                      {emailErrors.map((error, index) => (
                        <p key={index} className="text-red-300 text-sm flex items-center">
                          <Shield className="w-4 h-4 mr-2" />
                          {error}
                        </p>
                      ))}
                    </div>
                  )}

                  {/* Action buttons */}
                  <div className="space-y-3">
                    <button
                      onClick={handleForgotPassword}
                      disabled={resetLoading}
                      className="w-full bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 hover:from-cyan-600 hover:via-purple-600 hover:to-pink-600 text-white font-bold py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 shadow-lg shadow-purple-500/30 disabled:opacity-70"
                    >
                      {resetLoading ? (
                        <>
                          <Activity className="w-5 h-5 animate-spin" />
                          <span>Mengirim Email...</span>
                        </>
                      ) : (
                        <>
                          <Mail className="w-5 h-5" />
                          <span>Kirim Link Reset</span>
                          <ChevronRight className="w-5 h-5" />
                        </>
                      )}
                    </button>

                    <button
                      onClick={() => {
                        setShowForgotPassword(false);
                        setResetEmail('');
                        setEmailErrors([]);
                      }}
                      className="w-full bg-white/10 hover:bg-white/15 border border-white/20 text-white py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2"
                    >
                      <ArrowLeft className="w-5 h-5" />
                      <span>Kembali ke Login</span>
                    </button>
                  </div>
                </div>
              ) : (
                <div className="text-center py-8">
                  <CheckCircle className="w-16 h-16 text-green-400 mx-auto mb-4 animate-bounce" />
                  <h3 className="text-xl font-bold text-white mb-2">Email Terkirim!</h3>
                  <p className="text-gray-300 text-sm mb-4">
                    Silakan cek email Anda dan klik link untuk reset password
                  </p>
                  <button
                    onClick={() => {
                      setShowForgotPassword(false);
                      setResetSuccess(false);
                      setResetEmail('');
                    }}
                    className="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-bold py-2 px-6 rounded-xl transition-all duration-300"
                  >
                    Tutup
                  </button>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Gaming ambient effects */}
        <div className="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-purple-900/20 to-transparent pointer-events-none"></div>
      </div>
    </div>
  );
};

export default WelcomeLogin;