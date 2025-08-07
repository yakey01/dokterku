import React, { useState, useCallback, useEffect } from 'react';
import { 
  MapPin, 
  Satellite, 
  Wifi, 
  Battery, 
  AlertTriangle, 
  CheckCircle, 
  RefreshCw,
  Settings,
  HelpCircle,
  Navigation
} from 'lucide-react';
import { 
  enhancedGPS, 
  GPSCapabilities, 
  GPSErrorDetails, 
  GPSDetectionStrategy, 
  GPSLocationResult 
} from '../../utils/enhancedGPSHelper';

interface EnhancedGPSDetectorProps {
  onLocationDetected?: (location: GPSLocationResult) => void;
  onError?: (error: GPSErrorDetails) => void;
  className?: string;
  autoStart?: boolean;
  showDebugInfo?: boolean;
}

const EnhancedGPSDetector: React.FC<EnhancedGPSDetectorProps> = ({
  onLocationDetected,
  onError,
  className = '',
  autoStart = false,
  showDebugInfo = false
}) => {
  const [status, setStatus] = useState<'idle' | 'checking' | 'detecting' | 'success' | 'error'>('idle');
  const [progress, setProgress] = useState(0);
  const [statusMessage, setStatusMessage] = useState('');
  const [currentStrategy, setCurrentStrategy] = useState<GPSDetectionStrategy | null>(null);
  const [location, setLocation] = useState<GPSLocationResult | null>(null);
  const [error, setError] = useState<GPSErrorDetails | null>(null);
  const [capabilities, setCapabilities] = useState<GPSCapabilities | null>(null);
  const [showTroubleshooting, setShowTroubleshooting] = useState(false);
  const [detectionAttempts, setDetectionAttempts] = useState(0);

  // Check GPS capabilities on mount
  useEffect(() => {
    const checkCapabilities = async () => {
      try {
        const caps = await enhancedGPS.checkCapabilities();
        setCapabilities(caps);
        
        if (autoStart && caps.supported) {
          startDetection();
        }
      } catch (error) {
        console.error('Failed to check GPS capabilities:', error);
      }
    };
    
    checkCapabilities();
  }, [autoStart]);

  // Enhanced GPS detection with progressive strategies
  const startDetection = useCallback(async () => {
    setStatus('checking');
    setProgress(0);
    setError(null);
    setLocation(null);
    setDetectionAttempts(prev => prev + 1);

    try {
      setStatus('detecting');
      
      const result = await enhancedGPS.detectLocationProgressive(
        (statusMsg, progressValue, strategy) => {
          setStatusMessage(enhancedGPS.getStatusMessage(statusMsg, strategy));
          setProgress(progressValue);
        },
        (strategy) => {
          setCurrentStrategy(strategy);
        }
      );

      setLocation(result);
      setStatus('success');
      setProgress(100);
      setStatusMessage('✅ Lokasi berhasil ditemukan!');
      
      onLocationDetected?.(result);
      
    } catch (err) {
      const gpsError = err as GeolocationPositionError;
      const errorDetails = enhancedGPS.getDetailedError(gpsError);
      
      setError(errorDetails);
      setStatus('error');
      setStatusMessage(`❌ ${errorDetails.message}`);
      
      onError?.(errorDetails);
    }
  }, [onLocationDetected, onError]);

  const getStatusColor = () => {
    switch (status) {
      case 'checking':
      case 'detecting':
        return 'border-yellow-400/30 bg-yellow-500/10';
      case 'success':
        return 'border-green-400/30 bg-green-500/10';
      case 'error':
        return 'border-red-400/30 bg-red-500/10';
      default:
        return 'border-gray-400/30 bg-gray-500/10';
    }
  };

  const getStatusIcon = () => {
    switch (status) {
      case 'checking':
        return <Settings className="w-5 h-5 animate-spin text-blue-400" />;
      case 'detecting':
        return <Satellite className="w-5 h-5 animate-pulse text-yellow-400" />;
      case 'success':
        return <CheckCircle className="w-5 h-5 text-green-400" />;
      case 'error':
        return (
          <div className="relative">
            <AlertTriangle className="w-5 h-5 text-red-400" />
            <div className="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-ping" />
          </div>
        );
      default:
        return <MapPin className="w-5 h-5 text-gray-400" />;
    }
  };

  const getAccuracyBadge = (accuracy: number) => {
    if (accuracy <= 10) return { text: 'Sangat Akurat', color: 'bg-green-500/20 text-green-300', icon: '🎯' };
    if (accuracy <= 50) return { text: 'Akurat', color: 'bg-blue-500/20 text-blue-300', icon: '📍' };
    if (accuracy <= 100) return { text: 'Cukup Akurat', color: 'bg-yellow-500/20 text-yellow-300', icon: '📌' };
    return { text: 'Kurang Akurat', color: 'bg-red-500/20 text-red-300', icon: '📍' };
  };

  const getSourceBadge = (source: string) => {
    switch (source) {
      case 'high-accuracy':
        return { text: 'GPS Akurat', color: 'bg-green-500/20 text-green-300', icon: '🛰️' };
      case 'normal':
        return { text: 'GPS Normal', color: 'bg-blue-500/20 text-blue-300', icon: '📡' };
      case 'cached':
        return { text: 'Tersimpan', color: 'bg-purple-500/20 text-purple-300', icon: '💾' };
      case 'network':
        return { text: 'Jaringan', color: 'bg-orange-500/20 text-orange-300', icon: '🌐' };
      default:
        return { text: source, color: 'bg-gray-500/20 text-gray-300', icon: '❓' };
    }
  };

  const formatDetectionTime = (ms: number) => {
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(1)}s`;
  };

  return (
    <div className={`border rounded-xl p-4 transition-all duration-300 ${getStatusColor()} ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center space-x-3">
          {getStatusIcon()}
          <div>
            <div className="text-sm font-medium text-white">
              Enhanced GPS Detector
            </div>
            <div className="text-xs text-gray-400">
              Deteksi lokasi cerdas dengan multiple strategi
            </div>
          </div>
        </div>

        {/* Capabilities indicators */}
        <div className="flex items-center space-x-2">
          {capabilities?.batteryLevel && (
            <div className={`flex items-center space-x-1 px-2 py-1 rounded-full text-xs ${
              capabilities.batteryLevel < 0.2 ? 'bg-red-500/20 text-red-300' : 'bg-green-500/20 text-green-300'
            }`}>
              <Battery className="w-3 h-3" />
              <span>{Math.round(capabilities.batteryLevel * 100)}%</span>
            </div>
          )}
          {capabilities && !capabilities.isSecureContext && (
            <div className="px-2 py-1 bg-orange-500/20 text-orange-300 rounded-full text-xs">
              🔒 HTTP
            </div>
          )}
        </div>
      </div>

      {/* Progress bar for detection */}
      {(status === 'checking' || status === 'detecting') && (
        <div className="mb-4">
          <div className="w-full bg-gray-700 rounded-full h-2 mb-2">
            <div 
              className="bg-gradient-to-r from-yellow-400 to-blue-400 h-2 rounded-full transition-all duration-300 ease-out"
              style={{ width: `${Math.min(progress, 100)}%` }}
            />
          </div>
          <div className="flex items-center justify-between text-xs">
            <span className="text-gray-300">{statusMessage}</span>
            <span className="text-yellow-400 font-medium">{Math.round(progress)}%</span>
          </div>
          {currentStrategy && (
            <div className="mt-1 text-xs text-blue-300">
              📡 Strategi: {currentStrategy.description}
            </div>
          )}
        </div>
      )}

      {/* Success state */}
      {status === 'success' && location && (
        <div className="space-y-3">
          {/* Location info */}
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <div className="text-xs text-gray-400">Koordinat</div>
              <div className="font-mono text-xs text-white bg-black/20 p-2 rounded">
                {location.latitude.toFixed(6)}<br />
                {location.longitude.toFixed(6)}
              </div>
            </div>
            <div className="space-y-2">
              <div className="text-xs text-gray-400">Informasi</div>
              <div className="space-y-1">
                <div className={`px-2 py-1 rounded text-xs ${getAccuracyBadge(location.accuracy).color}`}>
                  {getAccuracyBadge(location.accuracy).icon} {getAccuracyBadge(location.accuracy).text}
                </div>
                <div className={`px-2 py-1 rounded text-xs ${getSourceBadge(location.source).color}`}>
                  {getSourceBadge(location.source).icon} {getSourceBadge(location.source).text}
                </div>
              </div>
            </div>
          </div>

          {/* Detailed metrics */}
          <div className="grid grid-cols-3 gap-2 text-xs">
            <div className="text-center p-2 bg-black/20 rounded">
              <div className="text-gray-400">Akurasi</div>
              <div className="text-white font-medium">±{Math.round(location.accuracy)}m</div>
            </div>
            <div className="text-center p-2 bg-black/20 rounded">
              <div className="text-gray-400">Confidence</div>
              <div className="text-white font-medium">{Math.round(location.confidence * 100)}%</div>
            </div>
            <div className="text-center p-2 bg-black/20 rounded">
              <div className="text-gray-400">Waktu</div>
              <div className="text-white font-medium">{formatDetectionTime(location.detectionTime)}</div>
            </div>
          </div>

          {showDebugInfo && (
            <div className="p-2 bg-black/20 rounded text-xs space-y-1">
              <div className="text-gray-400">Debug Info:</div>
              <div className="text-gray-300">Strategy: {location.strategy}</div>
              <div className="text-gray-300">Timestamp: {new Date(location.timestamp).toISOString()}</div>
              <div className="text-gray-300">Attempts: {detectionAttempts}</div>
            </div>
          )}
        </div>
      )}

      {/* Error state with enhanced troubleshooting */}
      {status === 'error' && error && (
        <div className="space-y-3">
          <div className="p-3 bg-red-500/10 border border-red-400/30 rounded-lg">
            <div className="flex items-start space-x-2 mb-2">
              <AlertTriangle className="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" />
              <div className="flex-1">
                <div className="text-sm font-medium text-red-300 mb-1">
                  {error.message}
                </div>
                <div className="text-xs text-red-200">
                  {error.userFriendlyMessage}
                </div>
              </div>
            </div>

            {/* Troubleshooting toggle */}
            <button
              onClick={() => setShowTroubleshooting(!showTroubleshooting)}
              className="mt-2 flex items-center space-x-2 text-xs text-red-300 hover:text-red-200 transition-colors"
            >
              <HelpCircle className="w-3 h-3" />
              <span>{showTroubleshooting ? 'Sembunyikan' : 'Tampilkan'} panduan troubleshooting</span>
            </button>

            {/* Troubleshooting steps */}
            {showTroubleshooting && (
              <div className="mt-3 p-3 bg-red-500/5 border border-red-400/20 rounded">
                <div className="text-xs font-medium text-red-200 mb-2">
                  🛠️ Langkah-langkah troubleshooting:
                </div>
                <div className="space-y-1">
                  {error.troubleshootingSteps.map((step, index) => (
                    <div key={index} className="text-xs text-red-200 flex items-start space-x-2">
                      <span className="text-red-400 font-medium">{index + 1}.</span>
                      <span className="flex-1">{step}</span>
                    </div>
                  ))}
                </div>

                <div className="mt-3 text-xs font-medium text-red-200 mb-2">
                  💡 Saran tindakan:
                </div>
                <div className="space-y-1">
                  {error.suggestedActions.map((action, index) => (
                    <div key={index} className="text-xs text-red-200 flex items-start space-x-2">
                      <span className="text-red-400">•</span>
                      <span className="flex-1">{action}</span>
                    </div>
                  ))}
                </div>

                <div className="mt-2 text-xs text-red-300">
                  ⏱️ Estimasi waktu perbaikan: {error.estimatedFixTime}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Optimization tips */}
      {capabilities && (
        <div className="mt-3">
          {enhancedGPS.getOptimizationTips(capabilities).map((tip, index) => (
            <div key={index} className="text-xs text-blue-300 bg-blue-500/10 p-2 rounded mt-1">
              {tip}
            </div>
          ))}
        </div>
      )}

      {/* Action buttons */}
      <div className="mt-4 grid grid-cols-2 gap-2">
        <button
          onClick={startDetection}
          disabled={status === 'checking' || status === 'detecting'}
          className="px-4 py-2 bg-blue-500/20 border border-blue-400/30 rounded-lg text-blue-300 hover:bg-blue-500/30 transition-colors text-sm font-medium flex items-center justify-center space-x-2 disabled:opacity-50"
        >
          <RefreshCw className={`w-4 h-4 ${(status === 'checking' || status === 'detecting') ? 'animate-spin' : ''}`} />
          <span>{status === 'detecting' ? 'Mendeteksi...' : 'Deteksi GPS'}</span>
        </button>

        <button
          onClick={() => window.location.reload()}
          className="px-4 py-2 bg-gray-500/20 border border-gray-400/30 rounded-lg text-gray-300 hover:bg-gray-500/30 transition-colors text-sm font-medium flex items-center justify-center space-x-2"
        >
          <Navigation className="w-4 h-4" />
          <span>Refresh</span>
        </button>
      </div>

      {/* Capability check results */}
      {showDebugInfo && capabilities && (
        <div className="mt-3 p-2 bg-black/20 rounded text-xs">
          <div className="text-gray-400 mb-1">Device Capabilities:</div>
          <div className="space-y-1 text-gray-300">
            <div>GPS: {capabilities.supported ? '✅ Supported' : '❌ Not Supported'}</div>
            <div>Permission: {capabilities.permissionStatus}</div>
            <div>Secure Context: {capabilities.isSecureContext ? '✅ Yes' : '⚠️ No (HTTP)'}</div>
            <div>Connection: {capabilities.connectionType}</div>
            {capabilities.batteryLevel && (
              <div>Battery: {Math.round(capabilities.batteryLevel * 100)}%</div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default EnhancedGPSDetector;