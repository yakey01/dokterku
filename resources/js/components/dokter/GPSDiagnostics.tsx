import React, { useState, useEffect, useCallback } from 'react';
import { MapPin, Navigation, AlertTriangle, Wifi, WifiOff, Crosshair, Copy, Download, RefreshCw, Eye, EyeOff, Target, Globe, Smartphone, Router, ShieldAlert, Clock, Zap } from 'lucide-react';

interface GPSDiagnosticsProps {
  onLocationUpdate?: (location: [number, number], accuracy: number) => void;
  onClose?: () => void;
  workLocation?: {
    latitude: number;
    longitude: number;
    name: string;
    address: string;
    radius_meters: number;
  };
}

interface LocationReading {
  timestamp: Date;
  latitude: number;
  longitude: number;
  accuracy: number;
  altitude?: number;
  heading?: number;
  speed?: number;
  distance?: number;
}

interface VPNDetection {
  isVPN: boolean;
  confidence: number;
  indicators: string[];
  timezone: string;
  publicIP?: string;
}

const GPSDiagnostics: React.FC<GPSDiagnosticsProps> = ({ onLocationUpdate, onClose, workLocation }) => {
  // GPS State
  const [currentLocation, setCurrentLocation] = useState<[number, number] | null>(null);
  const [accuracy, setAccuracy] = useState<number | null>(null);
  const [gpsStatus, setGpsStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [gpsError, setGpsError] = useState<string | null>(null);
  
  // Historical readings
  const [locationHistory, setLocationHistory] = useState<LocationReading[]>([]);
  const [isTracking, setIsTracking] = useState(false);
  const [watchId, setWatchId] = useState<number | null>(null);
  
  // VPN/Proxy detection
  const [vpnDetection, setVpnDetection] = useState<VPNDetection | null>(null);
  const [isDetectingVPN, setIsDetectingVPN] = useState(false);
  
  // UI State
  const [showRawData, setShowRawData] = useState(false);
  const [manualCoords, setManualCoords] = useState({ lat: '', lng: '' });
  const [showManualInput, setShowManualInput] = useState(false);
  
  // Known work locations for comparison
  const KNOWN_LOCATIONS = {
    bandung: { lat: -6.91750000, lng: 107.61910000, name: 'Cabang Bandung' },
    eastJava: { lat: -7.899104425119698, lng: 111.96316396455585, name: 'East Java (VPN Issue)' },
    kediri: { lat: -7.8481, lng: 112.0178, name: 'RS Kediri Medical Center' }
  };

  // Calculate distance between two points
  const calculateDistance = useCallback((lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI/180;
    const φ2 = lat2 * Math.PI/180;
    const Δφ = (lat2-lat1) * Math.PI/180;
    const Δλ = (lon2-lon1) * Math.PI/180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;
  }, []);

  // VPN Detection
  const detectVPN = useCallback(async () => {
    setIsDetectingVPN(true);
    const indicators: string[] = [];
    let confidence = 0;

    try {
      // Check timezone
      const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      const isIndonesianTimezone = timezone.includes('Asia/Jakarta') || timezone.includes('Asia/Makassar') || timezone.includes('Asia/Jayapura');
      
      if (!isIndonesianTimezone) {
        indicators.push(`Timezone: ${timezone} (Not Indonesian)`);
        confidence += 30;
      }

      // Check if GPS coordinates match timezone expectation
      if (currentLocation) {
        const [lat, lng] = currentLocation;
        // Indonesia is roughly between 6°N-11°S and 95°E-141°E
        const isInIndonesia = lat >= -11 && lat <= 6 && lng >= 95 && lng <= 141;
        
        if (!isInIndonesia) {
          indicators.push(`GPS coordinates outside Indonesia bounds`);
          confidence += 40;
        }

        // Check distance from known work locations
        Object.values(KNOWN_LOCATIONS).forEach(location => {
          const distance = calculateDistance(lat, lng, location.lat, location.lng);
          if (distance > 1000000) { // > 1000km
            indicators.push(`${Math.round(distance/1000)}km from ${location.name}`);
            confidence += 20;
          }
        });
      }

      // Try to get public IP (if CORS allows)
      try {
        const ipResponse = await fetch('https://api.ipify.org?format=json');
        const ipData = await ipResponse.json();
        
        // Basic check for known VPN IP ranges (simplified)
        if (ipData.ip.startsWith('10.') || ipData.ip.startsWith('172.') || ipData.ip.startsWith('192.168.')) {
          indicators.push(`Private IP detected: ${ipData.ip}`);
          confidence += 20;
        }
        
        setVpnDetection({
          isVPN: confidence > 50,
          confidence,
          indicators,
          timezone,
          publicIP: ipData.ip
        });
      } catch {
        // IP check failed, still set VPN detection based on other indicators
        setVpnDetection({
          isVPN: confidence > 50,
          confidence,
          indicators,
          timezone
        });
      }
    } catch (error) {
      console.error('VPN detection failed:', error);
    } finally {
      setIsDetectingVPN(false);
    }
  }, [currentLocation, calculateDistance]);

  // Get single GPS reading
  const getSingleReading = useCallback(() => {
    setGpsStatus('loading');
    setGpsError(null);

    if (!navigator.geolocation) {
      setGpsStatus('error');
      setGpsError('GPS not supported by this browser');
      return;
    }

    const options = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 1000
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude, accuracy, altitude, heading, speed } = position.coords;
        const location: [number, number] = [latitude, longitude];
        
        setCurrentLocation(location);
        setAccuracy(accuracy);
        setGpsStatus('success');
        
        // Calculate distance to work location
        let distance: number | undefined;
        if (workLocation) {
          distance = calculateDistance(latitude, longitude, workLocation.latitude, workLocation.longitude);
        }

        // Add to history
        const reading: LocationReading = {
          timestamp: new Date(),
          latitude,
          longitude,
          accuracy,
          altitude: altitude || undefined,
          heading: heading || undefined,
          speed: speed || undefined,
          distance
        };
        
        setLocationHistory(prev => [reading, ...prev].slice(0, 50)); // Keep last 50 readings
        
        // Callback to parent
        onLocationUpdate?.(location, accuracy);
      },
      (error) => {
        setGpsStatus('error');
        switch (error.code) {
          case error.PERMISSION_DENIED:
            setGpsError('GPS permission denied. Please allow location access.');
            break;
          case error.POSITION_UNAVAILABLE:
            setGpsError('GPS position unavailable. Check GPS settings.');
            break;
          case error.TIMEOUT:
            setGpsError('GPS timeout. Try again.');
            break;
          default:
            setGpsError(`GPS error: ${error.message}`);
        }
      }
    );
  }, [calculateDistance, workLocation, onLocationUpdate]);

  // Start continuous tracking
  const startTracking = useCallback(() => {
    if (watchId) return; // Already tracking

    if (!navigator.geolocation) {
      setGpsError('GPS not supported by this browser');
      return;
    }

    const options = {
      enableHighAccuracy: true,
      timeout: 5000,
      maximumAge: 2000
    };

    const id = navigator.geolocation.watchPosition(
      (position) => {
        const { latitude, longitude, accuracy, altitude, heading, speed } = position.coords;
        const location: [number, number] = [latitude, longitude];
        
        setCurrentLocation(location);
        setAccuracy(accuracy);
        setGpsStatus('success');
        
        // Calculate distance to work location
        let distance: number | undefined;
        if (workLocation) {
          distance = calculateDistance(latitude, longitude, workLocation.latitude, workLocation.longitude);
        }

        // Add to history
        const reading: LocationReading = {
          timestamp: new Date(),
          latitude,
          longitude,
          accuracy,
          altitude: altitude || undefined,
          heading: heading || undefined,
          speed: speed || undefined,
          distance
        };
        
        setLocationHistory(prev => [reading, ...prev].slice(0, 100)); // Keep last 100 readings
        
        // Callback to parent
        onLocationUpdate?.(location, accuracy);
      },
      (error) => {
        setGpsStatus('error');
        setGpsError(`Tracking error: ${error.message}`);
      },
      options
    );

    setWatchId(id);
    setIsTracking(true);
  }, [watchId, calculateDistance, workLocation, onLocationUpdate]);

  // Stop tracking
  const stopTracking = useCallback(() => {
    if (watchId) {
      navigator.geolocation.clearWatch(watchId);
      setWatchId(null);
    }
    setIsTracking(false);
  }, [watchId]);

  // Manual coordinate input
  const useManualCoords = () => {
    const lat = parseFloat(manualCoords.lat);
    const lng = parseFloat(manualCoords.lng);
    
    if (isNaN(lat) || isNaN(lng)) {
      alert('Please enter valid coordinates');
      return;
    }

    const location: [number, number] = [lat, lng];
    setCurrentLocation(location);
    setAccuracy(1); // Perfect accuracy for manual input
    setGpsStatus('success');
    
    // Add to history
    const reading: LocationReading = {
      timestamp: new Date(),
      latitude: lat,
      longitude: lng,
      accuracy: 1,
      distance: workLocation ? calculateDistance(lat, lng, workLocation.latitude, workLocation.longitude) : undefined
    };
    
    setLocationHistory(prev => [reading, ...prev].slice(0, 50));
    onLocationUpdate?.(location, 1);
    setShowManualInput(false);
  };

  // Export diagnostics data
  const exportDiagnostics = () => {
    const diagnosticsData = {
      timestamp: new Date().toISOString(),
      currentLocation,
      accuracy,
      gpsStatus,
      gpsError,
      vpnDetection,
      locationHistory,
      workLocation,
      knownLocations: KNOWN_LOCATIONS,
      browserInfo: {
        userAgent: navigator.userAgent,
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
      }
    };

    const blob = new Blob([JSON.stringify(diagnosticsData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `gps-diagnostics-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  // Copy coordinates to clipboard
  const copyCoordinates = () => {
    if (!currentLocation) return;
    const coords = `${currentLocation[0]}, ${currentLocation[1]}`;
    navigator.clipboard.writeText(coords);
    alert('Coordinates copied to clipboard!');
  };

  // Format distance
  const formatDistance = (meters: number) => {
    if (meters < 1000) {
      return `${Math.round(meters)}m`;
    }
    return `${(meters / 1000).toFixed(2)}km`;
  };

  // Auto-detect VPN when location changes
  useEffect(() => {
    if (currentLocation) {
      detectVPN();
    }
  }, [currentLocation, detectVPN]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (watchId) {
        navigator.geolocation.clearWatch(watchId);
      }
    };
  }, [watchId]);

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="sticky top-0 bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-2xl">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <Navigation className="w-6 h-6" />
              <h2 className="text-xl font-bold">GPS Diagnostics & Debugging</h2>
            </div>
            <button
              onClick={onClose}
              className="text-white hover:bg-white/20 rounded-full p-2 transition-colors"
            >
              ✕
            </button>
          </div>
          
          {/* Quick Status */}
          <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white/10 rounded-lg p-3 text-center">
              <div className="text-lg font-bold">
                {gpsStatus === 'success' ? '✅' : gpsStatus === 'loading' ? '⏳' : '❌'}
              </div>
              <div className="text-xs opacity-90">GPS Status</div>
            </div>
            <div className="bg-white/10 rounded-lg p-3 text-center">
              <div className="text-lg font-bold">
                {accuracy ? `±${Math.round(accuracy)}m` : '--'}
              </div>
              <div className="text-xs opacity-90">Accuracy</div>
            </div>
            <div className="bg-white/10 rounded-lg p-3 text-center">
              <div className="text-lg font-bold">
                {currentLocation && workLocation ? formatDistance(calculateDistance(
                  currentLocation[0], currentLocation[1],
                  workLocation.latitude, workLocation.longitude
                )) : '--'}
              </div>
              <div className="text-xs opacity-90">Distance</div>
            </div>
            <div className="bg-white/10 rounded-lg p-3 text-center">
              <div className="text-lg font-bold">
                {vpnDetection ? (vpnDetection.isVPN ? '⚠️' : '✅') : '⏳'}
              </div>
              <div className="text-xs opacity-90">VPN Check</div>
            </div>
          </div>
        </div>

        <div className="p-6 space-y-6">
          {/* Control Buttons */}
          <div className="flex flex-wrap gap-3">
            <button
              onClick={getSingleReading}
              disabled={gpsStatus === 'loading'}
              className="flex items-center space-x-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
            >
              <Crosshair className="w-4 h-4" />
              <span>Get GPS Reading</span>
            </button>
            
            <button
              onClick={isTracking ? stopTracking : startTracking}
              className={`flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors ${
                isTracking 
                  ? 'bg-red-500 hover:bg-red-600 text-white'
                  : 'bg-green-500 hover:bg-green-600 text-white'
              }`}
            >
              <Target className="w-4 h-4" />
              <span>{isTracking ? 'Stop Tracking' : 'Start Tracking'}</span>
            </button>

            <button
              onClick={() => setShowManualInput(!showManualInput)}
              className="flex items-center space-x-2 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors"
            >
              <MapPin className="w-4 h-4" />
              <span>Manual Input</span>
            </button>

            <button
              onClick={detectVPN}
              disabled={isDetectingVPN}
              className="flex items-center space-x-2 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50 transition-colors"
            >
              <ShieldAlert className="w-4 h-4" />
              <span>Check VPN</span>
            </button>

            <button
              onClick={exportDiagnostics}
              className="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
            >
              <Download className="w-4 h-4" />
              <span>Export Data</span>
            </button>
          </div>

          {/* Manual Input */}
          {showManualInput && (
            <div className="bg-purple-50 rounded-lg p-4 border border-purple-200">
              <h3 className="font-semibold text-purple-800 mb-3">Manual Coordinate Input</h3>
              <div className="grid grid-cols-2 gap-4">
                <input
                  type="number"
                  step="any"
                  placeholder="Latitude (e.g., -6.91750000)"
                  value={manualCoords.lat}
                  onChange={(e) => setManualCoords(prev => ({ ...prev, lat: e.target.value }))}
                  className="px-3 py-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                />
                <input
                  type="number"
                  step="any"
                  placeholder="Longitude (e.g., 107.61910000)"
                  value={manualCoords.lng}
                  onChange={(e) => setManualCoords(prev => ({ ...prev, lng: e.target.value }))}
                  className="px-3 py-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                />
              </div>
              <div className="mt-3 flex justify-between">
                <button
                  onClick={useManualCoords}
                  className="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600"
                >
                  Use These Coordinates
                </button>
                <div className="text-sm text-purple-600">
                  Quick fill:
                  <button onClick={() => setManualCoords({ lat: '-6.91750000', lng: '107.61910000' })} className="ml-1 underline">
                    Bandung
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Current Location */}
          <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div className="flex items-center justify-between mb-3">
              <h3 className="font-semibold text-gray-800 flex items-center space-x-2">
                <Globe className="w-5 h-5" />
                <span>Current Location</span>
              </h3>
              {currentLocation && (
                <button
                  onClick={copyCoordinates}
                  className="flex items-center space-x-1 text-sm text-blue-600 hover:text-blue-800"
                >
                  <Copy className="w-4 h-4" />
                  <span>Copy</span>
                </button>
              )}
            </div>
            
            {gpsStatus === 'success' && currentLocation ? (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <div className="text-sm text-gray-600">Coordinates</div>
                  <div className="font-mono text-lg">
                    {currentLocation[0].toFixed(8)}, {currentLocation[1].toFixed(8)}
                  </div>
                  <div className="text-sm text-green-600">
                    ±{Math.round(accuracy || 0)}m accuracy
                  </div>
                </div>
                
                {workLocation && (
                  <div>
                    <div className="text-sm text-gray-600">Distance to Work</div>
                    <div className="font-semibold text-lg">
                      {formatDistance(calculateDistance(
                        currentLocation[0], currentLocation[1],
                        workLocation.latitude, workLocation.longitude
                      ))}
                    </div>
                    <div className={`text-sm ${
                      calculateDistance(currentLocation[0], currentLocation[1], workLocation.latitude, workLocation.longitude) <= workLocation.radius_meters
                        ? 'text-green-600' : 'text-red-600'
                    }`}>
                      {calculateDistance(currentLocation[0], currentLocation[1], workLocation.latitude, workLocation.longitude) <= workLocation.radius_meters
                        ? '✅ Within work radius' : '❌ Outside work radius'}
                    </div>
                  </div>
                )}
              </div>
            ) : gpsStatus === 'loading' ? (
              <div className="flex items-center space-x-2 text-blue-600">
                <RefreshCw className="w-4 h-4 animate-spin" />
                <span>Getting location...</span>
              </div>
            ) : gpsStatus === 'error' ? (
              <div className="flex items-center space-x-2 text-red-600">
                <AlertTriangle className="w-4 h-4" />
                <span>{gpsError}</span>
              </div>
            ) : (
              <div className="text-gray-500">No location data available</div>
            )}
          </div>

          {/* VPN Detection */}
          {vpnDetection && (
            <div className={`rounded-lg p-4 border ${
              vpnDetection.isVPN 
                ? 'bg-red-50 border-red-200' 
                : 'bg-green-50 border-green-200'
            }`}>
              <div className="flex items-center space-x-2 mb-3">
                {vpnDetection.isVPN ? (
                  <>
                    <ShieldAlert className="w-5 h-5 text-red-600" />
                    <h3 className="font-semibold text-red-800">VPN/Proxy Detected</h3>
                  </>
                ) : (
                  <>
                    <ShieldAlert className="w-5 h-5 text-green-600" />
                    <h3 className="font-semibold text-green-800">No VPN/Proxy Detected</h3>
                  </>
                )}
                <span className={`text-sm px-2 py-1 rounded ${
                  vpnDetection.confidence > 70 ? 'bg-red-200 text-red-800' :
                  vpnDetection.confidence > 40 ? 'bg-yellow-200 text-yellow-800' :
                  'bg-green-200 text-green-800'
                }`}>
                  {vpnDetection.confidence}% confidence
                </span>
              </div>
              
              <div className="space-y-2">
                <div className="text-sm">
                  <strong>Timezone:</strong> {vpnDetection.timezone}
                </div>
                {vpnDetection.publicIP && (
                  <div className="text-sm">
                    <strong>Public IP:</strong> {vpnDetection.publicIP}
                  </div>
                )}
                {vpnDetection.indicators.length > 0 && (
                  <div>
                    <div className="text-sm font-medium mb-1">Indicators:</div>
                    <ul className="text-sm space-y-1">
                      {vpnDetection.indicators.map((indicator, index) => (
                        <li key={index} className="flex items-center space-x-2">
                          <span className="text-red-500">•</span>
                          <span>{indicator}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Known Locations Comparison */}
          {currentLocation && (
            <div className="bg-blue-50 rounded-lg p-4 border border-blue-200">
              <h3 className="font-semibold text-blue-800 mb-3">Distance to Known Locations</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {Object.entries(KNOWN_LOCATIONS).map(([key, location]) => {
                  const distance = calculateDistance(
                    currentLocation[0], currentLocation[1],
                    location.lat, location.lng
                  );
                  return (
                    <div key={key} className="bg-white rounded-lg p-3">
                      <div className="font-medium text-gray-800">{location.name}</div>
                      <div className="text-sm text-gray-600 mb-1">
                        {location.lat.toFixed(6)}, {location.lng.toFixed(6)}
                      </div>
                      <div className={`font-semibold ${
                        distance < 1000 ? 'text-green-600' :
                        distance < 10000 ? 'text-yellow-600' :
                        'text-red-600'
                      }`}>
                        {formatDistance(distance)}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* Location History */}
          {locationHistory.length > 0 && (
            <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-semibold text-gray-800 flex items-center space-x-2">
                  <Clock className="w-5 h-5" />
                  <span>Location History ({locationHistory.length})</span>
                </h3>
                <button
                  onClick={() => setShowRawData(!showRawData)}
                  className="flex items-center space-x-1 text-sm text-blue-600 hover:text-blue-800"
                >
                  {showRawData ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  <span>{showRawData ? 'Hide' : 'Show'} Raw Data</span>
                </button>
              </div>
              
              <div className="max-h-64 overflow-y-auto space-y-2">
                {locationHistory.slice(0, 10).map((reading, index) => (
                  <div key={index} className="bg-white rounded-lg p-3 border border-gray-200">
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="text-sm text-gray-600">
                          {reading.timestamp.toLocaleTimeString()}
                        </div>
                        {showRawData ? (
                          <div className="font-mono text-xs text-gray-700 mt-1">
                            {reading.latitude.toFixed(8)}, {reading.longitude.toFixed(8)}
                          </div>
                        ) : (
                          <div className="text-sm font-medium">
                            ±{Math.round(reading.accuracy)}m accuracy
                          </div>
                        )}
                        {reading.distance && (
                          <div className={`text-sm ${
                            reading.distance <= (workLocation?.radius_meters || 50) 
                              ? 'text-green-600' : 'text-red-600'
                          }`}>
                            {formatDistance(reading.distance)} from work
                          </div>
                        )}
                      </div>
                      
                      {reading.speed && reading.speed > 0 && (
                        <div className="text-xs text-blue-600">
                          <Zap className="w-3 h-3 inline mr-1" />
                          {Math.round(reading.speed * 3.6)}km/h
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
              
              {locationHistory.length > 10 && (
                <div className="text-sm text-gray-500 text-center mt-2">
                  Showing last 10 readings of {locationHistory.length} total
                </div>
              )}
            </div>
          )}

          {/* Troubleshooting Tips */}
          <div className="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <h3 className="font-semibold text-yellow-800 mb-3">Troubleshooting Tips</h3>
            <ul className="text-sm text-yellow-700 space-y-2">
              <li className="flex items-start space-x-2">
                <span>•</span>
                <span><strong>VPN Issues:</strong> Disable VPN/proxy services as they can show incorrect locations</span>
              </li>
              <li className="flex items-start space-x-2">
                <span>•</span>
                <span><strong>GPS Accuracy:</strong> Go outside or near windows for better GPS signal</span>
              </li>
              <li className="flex items-start space-x-2">
                <span>•</span>
                <span><strong>Browser Settings:</strong> Allow location permission in browser settings</span>
              </li>
              <li className="flex items-start space-x-2">
                <span>•</span>
                <span><strong>Device Settings:</strong> Enable high accuracy location mode in device settings</span>
              </li>
              <li className="flex items-start space-x-2">
                <span>•</span>
                <span><strong>Distance Issues:</strong> If you're 491km away, you're likely using a VPN</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GPSDiagnostics;