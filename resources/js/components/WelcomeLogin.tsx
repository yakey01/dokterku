import React, { useState, useEffect, useRef } from 'react';
import { Eye, EyeOff, Lock, User, Stethoscope, Heart, Shield, Star, ChevronRight, CheckCircle, Activity, Zap } from 'lucide-react';

// LoginSuccessAnimation class embedded
class LoginSuccessAnimation {
  constructor() {
    this.isAnimating = false;
    this.particles = [];
    this.canvas = null;
    this.ctx = null;
  }

  initCanvas() {
    this.canvas = document.createElement('canvas');
    this.canvas.style.position = 'fixed';
    this.canvas.style.top = '0';
    this.canvas.style.left = '0';
    this.canvas.style.width = '100%';
    this.canvas.style.height = '100%';
    this.canvas.style.pointerEvents = 'none';
    this.canvas.style.zIndex = '9999';
    
    this.canvas.width = window.innerWidth;
    this.canvas.height = window.innerHeight;
    
    this.ctx = this.canvas.getContext('2d');
    document.body.appendChild(this.canvas);
    
    return this.canvas;
  }

  createParticles(centerX, centerY, count = 50) {
    this.particles = [];
    
    for (let i = 0; i < count; i++) {
      this.particles.push({
        x: centerX,
        y: centerY,
        vx: (Math.random() - 0.5) * 15,
        vy: (Math.random() - 0.5) * 15,
        life: 1,
        decay: Math.random() * 0.015 + 0.01,
        size: Math.random() * 4 + 2,
        color: this.getRandomColor(),
        rotation: Math.random() * Math.PI * 2,
        rotationSpeed: (Math.random() - 0.5) * 0.2
      });
    }
  }

  getRandomColor() {
    const colors = ['#00f5ff', '#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#ef4444'];
    return colors[Math.floor(Math.random() * colors.length)];
  }

  animateParticles() {
    if (!this.ctx || this.particles.length === 0) return;

    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

    for (let i = this.particles.length - 1; i >= 0; i--) {
      const particle = this.particles[i];
      
      particle.x += particle.vx;
      particle.y += particle.vy;
      particle.vy += 0.3;
      particle.life -= particle.decay;
      particle.rotation += particle.rotationSpeed;

      if (particle.life <= 0) {
        this.particles.splice(i, 1);
        continue;
      }

      this.ctx.save();
      this.ctx.globalAlpha = particle.life;
      this.ctx.translate(particle.x, particle.y);
      this.ctx.rotate(particle.rotation);
      
      this.drawStar(particle.size, particle.color);
      
      this.ctx.restore();
    }

    if (this.particles.length > 0) {
      requestAnimationFrame(() => this.animateParticles());
    } else {
      if (this.canvas && document.body.contains(this.canvas)) {
        document.body.removeChild(this.canvas);
      }
    }
  }

  drawStar(size, color) {
    this.ctx.fillStyle = color;
    this.ctx.beginPath();
    
    for (let i = 0; i < 5; i++) {
      const angle = (i * Math.PI * 2) / 5;
      const x = Math.cos(angle) * size;
      const y = Math.sin(angle) * size;
      
      if (i === 0) {
        this.ctx.moveTo(x, y);
      } else {
        this.ctx.lineTo(x, y);
      }
    }
    
    this.ctx.closePath();
    this.ctx.fill();
  }

  createRippleEffect(element) {
    const ripple = document.createElement('div');
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'radial-gradient(circle, rgba(139,92,246,0.3) 0%, rgba(139,92,246,0) 70%)';
    ripple.style.transform = 'scale(0)';
    ripple.style.animation = 'rippleAnimation 1s ease-out forwards';
    ripple.style.pointerEvents = 'none';
    ripple.style.zIndex = '1000';

    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 2;
    ripple.style.width = size + 'px';
    ripple.style.height = size + 'px';
    ripple.style.left = (rect.left + rect.width / 2 - size / 2) + 'px';
    ripple.style.top = (rect.top + rect.height / 2 - size / 2) + 'px';

    document.body.appendChild(ripple);

    setTimeout(() => {
      if (document.body.contains(ripple)) {
        document.body.removeChild(ripple);
      }
    }, 1000);
  }

  showSuccessMessage(message = 'Login Berhasil!', duration = 3000) {
    const successDiv = document.createElement('div');
    successDiv.innerHTML = `
      <div style="
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 20px;
        font-size: clamp(14px, 4vw, 18px);
        font-weight: bold;
        text-align: center;
        z-index: 10000;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        animation: successMessageAnimation 3s ease forwards;
        border: 2px solid rgba(255,255,255,0.2);
      ">
        <div style="margin-bottom: 10px; font-size: 24px;">🎉</div>
        ${message}
      </div>
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
      if (document.body.contains(successDiv)) {
        document.body.removeChild(successDiv);
      }
    }, duration);
  }

  slideOutLoginForm(formElement, direction = 'left') {
    const translateX = direction === 'left' ? '-100%' : '100%';
    
    formElement.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s ease';
    formElement.style.transform = `translateX(${translateX})`;
    formElement.style.opacity = '0';
  }

  playLoginSuccessAnimation(options = {}) {
    if (this.isAnimating) {
      console.warn('🚫 Animation already in progress, skipping');
      return;
    }
    
    console.log('🎬 Starting playLoginSuccessAnimation with options:', options);
    this.isAnimating = true;
    
    const {
      loginButton = null,
      loginForm = null,
      showParticles = true,
      showRipple = true,
      showSuccessMessage = true,
      successMessage = 'Login Berhasil!',
      slideDirection = 'left',
      onComplete = null
    } = options;

    if (showRipple && loginButton) {
      console.log('💫 Creating ripple effect...');
      this.createRippleEffect(loginButton);
    }

    if (showParticles && loginButton) {
      console.log('✨ Creating particles...');
      const rect = loginButton.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      
      console.log('🎯 Button position:', { centerX, centerY, rect });
      
      this.initCanvas();
      this.createParticles(centerX, centerY, 30);
      this.animateParticles();
    }

    if (showSuccessMessage) {
      console.log('💬 Scheduling success message...');
      setTimeout(() => {
        console.log('💬 Showing success message now');
        this.showSuccessMessage(successMessage);
      }, 500);
    }

    if (loginForm) {
      console.log('📱 Scheduling form slide out...');
      setTimeout(() => {
        console.log('📱 Sliding form out now');
        this.slideOutLoginForm(loginForm, slideDirection);
      }, 1500);
    }

    setTimeout(() => {
      console.log('🏁 Animation sequence complete');
      this.isAnimating = false;
      if (onComplete) {
        onComplete();
      }
    }, 4000);
  }

  cleanup() {
    if (this.canvas && document.body.contains(this.canvas)) {
      document.body.removeChild(this.canvas);
    }
    this.particles = [];
    this.isAnimating = false;
  }
}

interface WelcomeLoginProps {
  onLogin?: () => void;
}

const WelcomeLogin: React.FC<WelcomeLoginProps> = ({ onLogin }) => {
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [currentTime, setCurrentTime] = useState(new Date());
  const [loginSuccess, setLoginSuccess] = useState(false);
  
  const loginButtonRef = useRef(null);
  const loginFormRef = useRef(null);
  const animationRef = useRef(null);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);

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

    // Handle mobile keyboard
    const handleFocus = () => {
      document.body.classList.add('keyboard-open');
    };

    const handleBlur = () => {
      document.body.classList.remove('keyboard-open');
    };

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
      clearInterval(timer);
      if (animationRef.current) {
        animationRef.current.cleanup();
      }
      if (document.head.contains(style)) {
        document.head.removeChild(style);
      }
    };
  }, []);

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
              window.location.href = response.url || '/dokter/mobile-app';
            }
          });
        } else {
          // Fallback redirect without animation
          window.location.href = response.url || '/dokter/mobile-app';
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

  const formatTime = (date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  };

  const formatDate = (date) => {
    return date.toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
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
          {Array.from({ length: window.innerWidth < 768 ? 8 : 15 }).map((_, i) => (
            <div
              key={i}
              className="absolute w-1 h-1 bg-cyan-400/30 rounded-full animate-ping"
              style={{
                top: `${Math.random() * 100}%`,
                left: `${Math.random() * 100}%`,
                animationDelay: `${i * 200}ms`,
                animationDuration: '3s'
              }}
            />
          ))}
        </div>

        {/* Status Bar - Mobile optimized */}
        <div className="flex justify-between items-center pt-safe pb-2 text-white text-xs sm:text-sm font-semibold relative z-10">
          <span className="select-none">{formatTime(currentTime)}</span>
          <div className="flex items-center space-x-1">
            <div className="flex space-x-1">
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-gray-500 rounded-full"></div>
            </div>
            <div className="w-6 h-3 border border-white rounded-sm relative">
              <div className="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
            </div>
          </div>
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
              <p className="text-gray-400 text-xs sm:text-sm lg:text-base">
                {formatDate(currentTime)}
              </p>
            </div>
          </div>

          {/* Gaming Stats Cards - Mobile responsive grid */}
          <div className="grid grid-cols-3 gap-2 sm:gap-3 lg:gap-4 mb-6 sm:mb-8">
            <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-lg sm:rounded-xl p-2 sm:p-3 lg:p-4 border border-cyan-400/20 text-center relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-400/5 to-purple-400/5 animate-pulse"></div>
              <Heart className="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 text-red-400 mx-auto mb-1 sm:mb-2" />
              <div className="text-sm sm:text-base lg:text-lg font-bold text-white">24/7</div>
              <div className="text-[10px] sm:text-xs lg:text-sm text-gray-300">Service</div>
            </div>
            
            <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-lg sm:rounded-xl p-2 sm:p-3 lg:p-4 border border-purple-400/20 text-center relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-r from-purple-400/5 to-pink-400/5 animate-pulse"></div>
              <Shield className="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 text-green-400 mx-auto mb-1 sm:mb-2" />
              <div className="text-sm sm:text-base lg:text-lg font-bold text-white">100%</div>
              <div className="text-[10px] sm:text-xs lg:text-sm text-gray-300">Trusted</div>
            </div>
            
            <div className="bg-gradient-to-br from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-xl rounded-lg sm:rounded-xl p-2 sm:p-3 lg:p-4 border border-pink-400/20 text-center relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-r from-pink-400/5 to-cyan-400/5 animate-pulse"></div>
              <Star className="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 text-yellow-400 mx-auto mb-1 sm:mb-2" />
              <div className="text-sm sm:text-base lg:text-lg font-bold text-white">4.9</div>
              <div className="text-[10px] sm:text-xs lg:text-sm text-gray-300">Rating</div>
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
                  className="w-full bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 hover:from-cyan-600 hover:via-purple-600 hover:to-pink-600 text-white font-bold py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 shadow-lg shadow-purple-500/30 disabled:opacity-70 relative overflow-hidden group touch-manipulation min-h-[44px] sm:min-h-[48px] lg:min-h-[52px] text-base sm:text-lg"
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

                <button className="w-full bg-white/10 hover:bg-white/15 border border-white/20 text-white py-3 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 touch-manipulation min-h-[44px] text-sm sm:text-base">
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
            <div className="flex items-center justify-center space-x-4 text-[10px] sm:text-xs lg:text-sm text-gray-500">
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