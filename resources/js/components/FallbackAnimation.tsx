import React, { useEffect, useState } from 'react';
import { CheckCircle, Star, Heart, Shield } from 'lucide-react';

interface FallbackAnimationProps {
  show: boolean;
  message?: string;
  onComplete?: () => void;
}

export function FallbackAnimation({ 
  show, 
  message = "Selamat Datang di Klinik Dokterku!", 
  onComplete 
}: FallbackAnimationProps) {
  const [particles, setParticles] = useState<Array<{id: number, x: number, y: number, delay: number}>>([]);

  useEffect(() => {
    if (show) {
      console.log('🎆 Fallback animation triggered');
      
      // Generate particles
      const newParticles = Array.from({ length: 15 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: i * 100
      }));
      
      setParticles(newParticles);

      // Auto-complete after 4 seconds
      const timer = setTimeout(() => {
        console.log('🏁 Fallback animation completed');
        if (onComplete) {
          onComplete();
        }
      }, 4000);

      return () => clearTimeout(timer);
    }
  }, [show, onComplete]);

  if (!show) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-indigo-900/95 via-purple-900/95 to-pink-900/95 backdrop-blur-sm">
      {/* Background particles */}
      <div className="absolute inset-0 overflow-hidden">
        {particles.map((particle) => (
          <div
            key={particle.id}
            className="absolute w-2 h-2 bg-cyan-400 rounded-full animate-ping"
            style={{
              top: `${particle.y}%`,
              left: `${particle.x}%`,
              animationDelay: `${particle.delay}ms`,
              animationDuration: '2s'
            }}
          />
        ))}
      </div>

      {/* Main animation content */}
      <div className="relative z-10 text-center px-8">
        {/* Logo animation */}
        <div className="mb-8">
          <div className="w-24 h-24 mx-auto mb-6 relative animate-bounce">
            <div className="absolute inset-0 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-3xl animate-pulse shadow-2xl shadow-purple-500/30"></div>
            <div className="absolute inset-2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl flex items-center justify-center">
              <Heart className="w-12 h-12 text-cyan-400 animate-pulse" />
            </div>
            
            {/* Corner accents */}
            <div className="absolute -top-2 -left-2 w-4 h-4 border-l-2 border-t-2 border-cyan-400 rounded-tl-lg animate-pulse"></div>
            <div className="absolute -top-2 -right-2 w-4 h-4 border-r-2 border-t-2 border-purple-400 rounded-tr-lg animate-pulse"></div>
            <div className="absolute -bottom-2 -left-2 w-4 h-4 border-l-2 border-b-2 border-pink-400 rounded-bl-lg animate-pulse"></div>
            <div className="absolute -bottom-2 -right-2 w-4 h-4 border-r-2 border-b-2 border-cyan-400 rounded-br-lg animate-pulse"></div>
          </div>
        </div>

        {/* Main message */}
        <div className="space-y-4 animate-fade-in">
          <div className="text-6xl mb-4 animate-bounce">🎉</div>
          
          <h1 className="text-4xl font-bold bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent animate-pulse">
            KLINIK DOKTERKU
          </h1>
          
          <p className="text-xl text-white/90 font-medium animate-fade-in-up">
            {message}
          </p>
          
          <p className="text-purple-200 text-lg animate-fade-in-up">
            Sahabat Menuju Sehat
          </p>
        </div>

        {/* Success indicators */}
        <div className="flex justify-center space-x-6 mt-8">
          <div className="flex flex-col items-center animate-fade-in-up" style={{ animationDelay: '0.5s' }}>
            <CheckCircle className="w-8 h-8 text-green-400 mb-2 animate-pulse" />
            <span className="text-green-300 text-sm">Authenticated</span>
          </div>
          
          <div className="flex flex-col items-center animate-fade-in-up" style={{ animationDelay: '1s' }}>
            <Shield className="w-8 h-8 text-blue-400 mb-2 animate-pulse" />
            <span className="text-blue-300 text-sm">Secure</span>
          </div>
          
          <div className="flex flex-col items-center animate-fade-in-up" style={{ animationDelay: '1.5s' }}>
            <Star className="w-8 h-8 text-yellow-400 mb-2 animate-pulse" />
            <span className="text-yellow-300 text-sm">Premium</span>
          </div>
        </div>

        {/* Progress bar */}
        <div className="mt-8 w-64 mx-auto">
          <div className="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
            <div className="bg-gradient-to-r from-cyan-400 via-purple-500 to-pink-500 h-2 rounded-full animate-progress-bar"></div>
          </div>
          <p className="text-white/70 text-sm mt-2">Memuat dashboard...</p>
        </div>
      </div>

      <style jsx>{`
        @keyframes fade-in {
          from { opacity: 0; transform: translateY(20px); }
          to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fade-in-up {
          from { opacity: 0; transform: translateY(30px); }
          to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes progress-bar {
          from { width: 0%; }
          to { width: 100%; }
        }
        
        .animate-fade-in {
          animation: fade-in 1s ease-out forwards;
        }
        
        .animate-fade-in-up {
          animation: fade-in-up 1s ease-out forwards;
          opacity: 0;
        }
        
        .animate-progress-bar {
          animation: progress-bar 3s ease-out forwards;
          width: 0%;
        }
      `}</style>
    </div>
  );
}