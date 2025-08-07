import React, { useEffect, useState } from 'react';
import { Loader2, MapPin, Wifi, Satellite, Battery } from 'lucide-react';

interface GPSProgressIndicatorProps {
  status: 'idle' | 'loading' | 'success' | 'error';
  progress: number;
  progressText: string;
  batteryLevel?: number | null;
  isLowBattery?: boolean;
  accuracy?: number | null;
  source?: 'cache' | 'network' | 'gps';
  onRetry?: () => void;
}

const GPSProgressIndicator: React.FC<GPSProgressIndicatorProps> = ({
  status,
  progress,
  progressText,
  batteryLevel,
  isLowBattery,
  accuracy,
  source,
  onRetry
}) => {
  const [dots, setDots] = useState('');
  
  // Animated dots for loading state
  useEffect(() => {
    if (status === 'loading') {
      const interval = setInterval(() => {
        setDots(prev => prev.length >= 3 ? '' : prev + '.');
      }, 500);
      return () => clearInterval(interval);
    }
  }, [status]);
  
  const getStatusIcon = () => {
    switch (status) {
      case 'loading':
        return <Loader2 className="w-4 h-4 animate-spin text-yellow-400" />;
      case 'success':
        switch (source) {
          case 'cache':
            return <MapPin className="w-4 h-4 text-blue-400" />;
          case 'network':
            return <Wifi className="w-4 h-4 text-orange-400" />;
          case 'gps':
          default:
            return <Satellite className="w-4 h-4 text-green-400" />;
        }
      case 'error':
        return (
          <div className="relative">
            <MapPin className="w-4 h-4 text-red-400" />
            <div className="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse" />
          </div>
        );
      default:
        return <MapPin className="w-4 h-4 text-gray-400" />;
    }
  };
  
  const getStatusColor = () => {
    switch (status) {
      case 'loading':
        return 'border-yellow-400/30 bg-yellow-500/10';
      case 'success':
        switch (source) {
          case 'cache':
            return 'border-blue-400/30 bg-blue-500/10';
          case 'network':
            return 'border-orange-400/30 bg-orange-500/10';
          case 'gps':
          default:
            return 'border-green-400/30 bg-green-500/10';
        }
      case 'error':
        return 'border-red-400/30 bg-red-500/10';
      default:
        return 'border-gray-400/30 bg-gray-500/10';
    }
  };
  
  const getAccuracyLevel = (accuracy: number) => {
    if (accuracy <= 10) return { level: 'Excellent', color: 'text-green-400', icon: '🎯' };
    if (accuracy <= 50) return { level: 'Good', color: 'text-yellow-400', icon: '📍' };
    if (accuracy <= 100) return { level: 'Fair', color: 'text-orange-400', icon: '📌' };
    return { level: 'Poor', color: 'text-red-400', icon: '📍' };
  };
  
  const getSourceInfo = () => {
    switch (source) {
      case 'cache':
        return { name: 'Cached', description: 'Using stored location', icon: '💾' };
      case 'network':
        return { name: 'Network', description: 'IP-based location', icon: '🌐' };
      case 'gps':
      default:
        return { name: 'GPS', description: 'Satellite location', icon: '🛰️' };
    }
  };
  
  return (
    <div className={`border rounded-xl p-4 transition-all duration-300 ${getStatusColor()}`}>
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center space-x-2">
          {getStatusIcon()}
          <span className="text-sm font-medium text-white">
            GPS Location
          </span>
        </div>
        
        {/* Battery indicator */}
        {batteryLevel !== null && (
          <div className="flex items-center space-x-1">
            <Battery 
              className={`w-4 h-4 ${isLowBattery ? 'text-red-400' : 'text-green-400'}`}
            />
            <span className={`text-xs ${isLowBattery ? 'text-red-400' : 'text-green-400'}`}>
              {Math.round(batteryLevel * 100)}%
            </span>
          </div>
        )}
      </div>
      
      {/* Progress bar */}
      {status === 'loading' && (
        <div className="mb-3">
          <div className="w-full bg-gray-700 rounded-full h-2">
            <div 
              className="bg-yellow-400 h-2 rounded-full transition-all duration-300 ease-out"
              style={{ width: `${Math.min(progress, 100)}%` }}
            />
          </div>
          <div className="mt-2 flex items-center justify-between">
            <div className="text-xs text-gray-300">
              {progressText}{dots}
            </div>
            <div className="text-xs text-yellow-400 font-medium">
              {Math.round(progress)}%
            </div>
          </div>
        </div>
      )}
      
      {/* Location info */}
      {status === 'success' && accuracy && (
        <div className="space-y-2">
          <div className="flex items-center justify-between text-xs">
            <div className="flex items-center space-x-2">
              <span className="text-gray-400">Source:</span>
              <span className="flex items-center space-x-1">
                <span>{getSourceInfo().icon}</span>
                <span className="text-white">{getSourceInfo().name}</span>
              </span>
            </div>
            <div className="flex items-center space-x-1">
              <span className="text-gray-400">Accuracy:</span>
              <span className={`${getAccuracyLevel(accuracy).color} font-medium`}>
                {getAccuracyLevel(accuracy).icon} {Math.round(accuracy)}m
              </span>
            </div>
          </div>
          
          <div className="text-xs text-gray-400">
            {getSourceInfo().description}
          </div>
        </div>
      )}
      
      {/* Enhanced Error state with troubleshooting */}
      {status === 'error' && (
        <div className="space-y-3">
          <div className="p-3 bg-red-500/10 border border-red-400/30 rounded-lg">
            <div className="flex items-start space-x-2 mb-2">
              <span className="text-red-400 text-sm">❌</span>
              <div className="flex-1">
                <div className="text-xs font-medium text-red-300 mb-1">
                  Tidak dapat mendeteksi lokasi
                </div>
                <div className="text-xs text-red-200 leading-relaxed">
                  Pastikan GPS aktif dan izin lokasi sudah diberikan
                </div>
              </div>
            </div>
            
            {/* Troubleshooting steps */}
            <div className="mt-3 p-2 bg-red-500/5 border border-red-400/20 rounded">
              <div className="text-xs text-red-200 mb-2 font-medium">💡 Langkah troubleshooting:</div>
              <div className="text-xs text-red-200 space-y-1">
                <div>1. Klik ikon 🔒 di address bar → Izinkan lokasi</div>
                <div>2. Pastikan GPS aktif di perangkat</div>
                <div>3. Keluar ke area terbuka</div>
                <div>4. Refresh halaman jika perlu</div>
              </div>
            </div>
          </div>
          
          {onRetry && (
            <div className="grid grid-cols-2 gap-2">
              <button
                onClick={onRetry}
                className="px-3 py-2 bg-red-500/20 border border-red-400/30 rounded-lg text-red-300 hover:bg-red-500/30 transition-colors text-xs font-medium flex items-center justify-center space-x-1"
              >
                <span>🔄</span>
                <span>Coba Lagi</span>
              </button>
              <button
                onClick={() => window.location.reload()}
                className="px-3 py-2 bg-blue-500/20 border border-blue-400/30 rounded-lg text-blue-300 hover:bg-blue-500/30 transition-colors text-xs font-medium flex items-center justify-center space-x-1"
              >
                <span>🔄</span>
                <span>Refresh</span>
              </button>
            </div>
          )}
        </div>
      )}
      
      {/* Low battery warning */}
      {isLowBattery && status === 'loading' && (
        <div className="mt-2 p-2 bg-orange-500/10 border border-orange-400/30 rounded-lg">
          <div className="flex items-center space-x-2">
            <Battery className="w-3 h-3 text-orange-400" />
            <span className="text-xs text-orange-300">
              Battery saving mode active
            </span>
          </div>
        </div>
      )}
    </div>
  );
};

export default GPSProgressIndicator;