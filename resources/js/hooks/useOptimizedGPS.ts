import { useState, useCallback, useEffect, useRef } from 'react';

// GPS Performance Configuration
const GPS_CONFIG = {
  // Progressive timeout strategy: 3s → 7s → 12s
  TIMEOUT_FAST: 3000,
  TIMEOUT_MEDIUM: 7000,
  TIMEOUT_SLOW: 12000,
  
  // Cache configuration
  CACHE_DURATION: 5 * 60 * 1000, // 5 minutes
  CACHE_MAX_AGE: 30 * 1000, // 30 seconds for getCurrentPosition
  
  // Battery optimization thresholds
  BATTERY_LOW_THRESHOLD: 0.2, // 20%
  BATTERY_CRITICAL_THRESHOLD: 0.1, // 10%
  
  // Accuracy thresholds
  ACCURACY_EXCELLENT: 10, // meters
  ACCURACY_GOOD: 50,
  ACCURACY_ACCEPTABLE: 100,
};

interface GPSLocation {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: number;
  source: 'cache' | 'network' | 'gps';
  confidence: number; // 0-1 based on accuracy and age
}

interface GPSState {
  location: GPSLocation | null;
  status: 'idle' | 'loading' | 'success' | 'error';
  error: string | null;
  attempt: number;
  batteryLevel: number | null;
  isLowBattery: boolean;
}

interface GPSOptions {
  enableHighAccuracy?: boolean;
  enableCache?: boolean;
  enableBatteryOptimization?: boolean;
  onProgress?: (status: string, progress: number) => void;
}

// Smart location cache with confidence decay
class LocationCache {
  private cache: GPSLocation | null = null;
  private readonly CONFIDENCE_DECAY_RATE = 0.1; // per minute
  
  set(location: GPSLocation): void {
    this.cache = {
      ...location,
      timestamp: Date.now(),
    };
  }
  
  get(): GPSLocation | null {
    if (!this.cache) return null;
    
    const age = Date.now() - this.cache.timestamp;
    if (age > GPS_CONFIG.CACHE_DURATION) {
      this.cache = null;
      return null;
    }
    
    // Calculate confidence decay based on age
    const ageMinutes = age / (60 * 1000);
    const confidenceDecay = Math.max(0, 1 - (ageMinutes * this.CONFIDENCE_DECAY_RATE));
    const baseConfidence = this.calculateConfidence(this.cache.accuracy);
    
    return {
      ...this.cache,
      confidence: baseConfidence * confidenceDecay,
      source: 'cache' as const,
    };
  }
  
  public calculateConfidence(accuracy: number): number {
    if (accuracy <= GPS_CONFIG.ACCURACY_EXCELLENT) return 1.0;
    if (accuracy <= GPS_CONFIG.ACCURACY_GOOD) return 0.8;
    if (accuracy <= GPS_CONFIG.ACCURACY_ACCEPTABLE) return 0.6;
    return 0.4;
  }
  
  clear(): void {
    this.cache = null;
  }
}

const locationCache = new LocationCache();

export const useOptimizedGPS = (options: GPSOptions = {}) => {
  const [state, setState] = useState<GPSState>({
    location: null,
    status: 'idle',
    error: null,
    attempt: 0,
    batteryLevel: null,
    isLowBattery: false,
  });
  
  const abortControllerRef = useRef<AbortController | null>(null);
  const timeoutRef = useRef<number | null>(null);
  
  // Battery level monitoring
  const updateBatteryLevel = useCallback(async () => {
    if ('getBattery' in navigator) {
      try {
        const battery = await (navigator as any).getBattery();
        const level = battery.level;
        setState(prev => ({
          ...prev,
          batteryLevel: level,
          isLowBattery: level < GPS_CONFIG.BATTERY_LOW_THRESHOLD,
        }));
      } catch (error) {
        // Battery API not supported, continue without battery optimization
      }
    }
  }, []);
  
  // Enhanced progressive GPS acquisition with detailed progress tracking
  const getLocationWithFallback = useCallback(async (attempt: number = 1): Promise<GPSLocation> => {
    return new Promise((resolve, reject) => {
      const timeouts = [GPS_CONFIG.TIMEOUT_FAST, GPS_CONFIG.TIMEOUT_MEDIUM, GPS_CONFIG.TIMEOUT_SLOW];
      const timeout = timeouts[Math.min(attempt - 1, timeouts.length - 1)];
      
      // Progressive accuracy strategy: start with high accuracy, then relax
      const enableHighAccuracy = attempt <= 2 ? 
        (options.enableHighAccuracy !== false && !state.isLowBattery) : 
        false;
      
      const gpsOptions: PositionOptions = {
        enableHighAccuracy,
        timeout,
        maximumAge: attempt === 1 ? GPS_CONFIG.CACHE_MAX_AGE : 
                   attempt === 2 ? 60000 : 300000, // More lenient cache for later attempts
      };
      
      // Enhanced progress messages in Indonesian
      const progressMessages = [
        `🛰️ Mencari sinyal GPS (percobaan ${attempt}/3)...`,
        `📡 Menggunakan GPS akurasi ${enableHighAccuracy ? 'tinggi' : 'normal'}...`,
        `🔄 Percobaan terakhir dengan pengaturan fleksibel...`
      ];
      
      const progress = (attempt - 1) * 25 + 10; // Better progress distribution
      options.onProgress?.(progressMessages[attempt - 1], progress);
      
      // Set up timeout handler for better error reporting
      let timeoutId: number | null = null;
      let isResolved = false;
      
      const cleanup = () => {
        if (timeoutId) clearTimeout(timeoutId);
      };
      
      navigator.geolocation.getCurrentPosition(
        (position) => {
          if (isResolved) return;
          isResolved = true;
          cleanup();
          
          const { latitude, longitude, accuracy } = position.coords;
          const location: GPSLocation = {
            latitude,
            longitude,
            accuracy,
            timestamp: Date.now(),
            source: 'gps',
            confidence: accuracy <= GPS_CONFIG.ACCURACY_EXCELLENT ? 1.0 :
                       accuracy <= GPS_CONFIG.ACCURACY_GOOD ? 0.8 :
                       accuracy <= GPS_CONFIG.ACCURACY_ACCEPTABLE ? 0.6 : 0.4,
          };
          
          // Cache successful GPS reading
          if (options.enableCache !== false) {
            locationCache.set(location);
          }
          
          // Success message with accuracy info
          const accuracyLevel = accuracy <= 10 ? 'sangat akurat' :
                               accuracy <= 50 ? 'akurat' :
                               accuracy <= 100 ? 'cukup akurat' : 'kurang akurat';
          options.onProgress?.(`✅ GPS berhasil (${accuracyLevel}, ±${Math.round(accuracy)}m)`, 100);
          
          resolve(location);
        },
        (error) => {
          if (isResolved) return;
          cleanup();
          
          if (attempt < 3) {
            // Enhanced retry strategy with delay
            const delay = attempt === 1 ? 1000 : 2000; // Longer delay for later attempts
            options.onProgress?.(`⚠️ Percobaan ${attempt} gagal, mencoba lagi...`, progress + 5);
            
            setTimeout(() => {
              if (!isResolved) {
                getLocationWithFallback(attempt + 1).then(resolve).catch(reject);
              }
            }, delay);
          } else {
            isResolved = true;
            // Enhanced error with troubleshooting
            const enhancedError = new Error(getDetailedErrorMessage(error, attempt));
            (enhancedError as any).originalError = error;
            (enhancedError as any).attempt = attempt;
            reject(enhancedError);
          }
        },
        gpsOptions
      );
      
      // Custom timeout handling for better user feedback
      timeoutId = setTimeout(() => {
        if (!isResolved) {
          options.onProgress?.(`⏳ GPS timeout setelah ${timeout/1000}s, mencoba pengaturan lain...`, progress + 10);
        }
      }, timeout - 1000) as any;
    });
  }, [options.enableHighAccuracy, options.enableCache, state.isLowBattery, options.onProgress]);
  
  // Network-based location fallback (using IP geolocation)
  const getNetworkLocation = useCallback(async (): Promise<GPSLocation | null> => {
    try {
      options.onProgress?.('Trying network location...', 75);
      // Create timeout controller for network request
      const timeoutController = new AbortController();
      const timeoutId = setTimeout(() => timeoutController.abort(), 5000);
      
      const response = await fetch('https://ipapi.co/json/', { 
        signal: abortControllerRef.current?.signal,
      });
      
      clearTimeout(timeoutId);
      const data = await response.json();
      
      if (data.latitude && data.longitude) {
        return {
          latitude: data.latitude,
          longitude: data.longitude,
          accuracy: 1000, // Network location is less accurate
          timestamp: Date.now(),
          source: 'network',
          confidence: 0.3, // Low confidence for network location
        };
      }
    } catch (error) {
      // Network location failed, continue without it
    }
    return null;
  }, [options.onProgress]);
  
  // Main GPS acquisition function
  const getCurrentLocation = useCallback(async (): Promise<GPSLocation> => {
    // Abort any existing request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    abortControllerRef.current = new AbortController();
    
    setState(prev => ({
      ...prev,
      status: 'loading',
      error: null,
      attempt: 0,
    }));
    
    try {
      // Step 1: Check cache first (fastest)
      if (options.enableCache !== false) {
        options.onProgress?.('Checking cached location...', 10);
        const cached = locationCache.get();
        if (cached && cached.confidence > 0.5) {
          setState(prev => ({
            ...prev,
            location: cached,
            status: 'success',
          }));
          options.onProgress?.('Using cached location', 100);
          return cached;
        }
      }
      
      // Step 2: Parallel GPS and network location acquisition
      const promises: Promise<GPSLocation | null>[] = [];
      
      if (navigator.geolocation) {
        promises.push(
          getLocationWithFallback(1).catch(() => null)
        );
      }
      
      // Add network location as fallback
      promises.push(getNetworkLocation());
      
      const results = await Promise.allSettled(promises);
      const locations = results
        .filter((result): result is PromiseFulfilledResult<GPSLocation> => 
          result.status === 'fulfilled' && result.value !== null
        )
        .map(result => result.value)
        .sort((a, b) => b.confidence - a.confidence); // Sort by confidence
      
      if (locations.length === 0) {
        throw new Error('Unable to determine location');
      }
      
      const bestLocation = locations[0];
      setState(prev => ({
        ...prev,
        location: bestLocation,
        status: 'success',
      }));
      
      options.onProgress?.('Location acquired successfully', 100);
      return bestLocation;
      
    } catch (error) {
      const errorMessage = error instanceof GeolocationPositionError ?
        getGeolocationErrorMessage(error) :
        error instanceof Error ? error.message : 'Unknown GPS error';
      
      setState(prev => ({
        ...prev,
        status: 'error',
        error: errorMessage,
      }));
      
      throw new Error(errorMessage);
    }
  }, [options.enableCache, getLocationWithFallback, getNetworkLocation, options.onProgress]);
  
  // Continuous location watching with smart intervals
  const watchLocation = useCallback((
    callback: (location: GPSLocation) => void,
    options: { interval?: number } = {}
  ) => {
    const interval = options.interval || (state.isLowBattery ? 60000 : 30000);
    
    let watchId: number | null = null;
    let intervalId: number | null = null;
    
    const startWatching = () => {
      if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
          (position) => {
            const { latitude, longitude, accuracy } = position.coords;
            const location: GPSLocation = {
              latitude,
              longitude,
              accuracy,
              timestamp: Date.now(),
              source: 'gps',
              confidence: accuracy <= 50 ? 0.9 : 0.7,
            };
            
            locationCache.set(location);
            callback(location);
          },
          (error) => {
            console.warn('GPS watch error:', error);
          },
          {
            enableHighAccuracy: !state.isLowBattery,
            timeout: GPS_CONFIG.TIMEOUT_MEDIUM,
            maximumAge: GPS_CONFIG.CACHE_MAX_AGE,
          }
        );
      }
      
      // Fallback to polling if watchPosition fails
      intervalId = setInterval(async () => {
        try {
          const location = await getCurrentLocation();
          callback(location);
        } catch (error) {
          console.warn('Location polling error:', error);
        }
      }, interval);
    };
    
    const stopWatching = () => {
      if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
      }
      if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
      }
    };
    
    startWatching();
    return stopWatching;
  }, [state.isLowBattery, getCurrentLocation]);
  
  // Initialize battery monitoring
  useEffect(() => {
    updateBatteryLevel();
  }, [updateBatteryLevel]);
  
  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);
  
  return {
    ...state,
    getCurrentLocation,
    watchLocation,
    clearCache: locationCache.clear.bind(locationCache),
    updateBatteryLevel,
  };
};

// Enhanced error messages with detailed troubleshooting
function getGeolocationErrorMessage(error: GeolocationPositionError): string {
  switch (error.code) {
    case error.PERMISSION_DENIED:
      return 'Akses lokasi ditolak. Buka pengaturan browser → Izinkan lokasi untuk situs ini.';
    case error.POSITION_UNAVAILABLE:
      return 'GPS tidak tersedia. Pastikan GPS aktif dan Anda berada di area terbuka.';
    case error.TIMEOUT:
      return 'GPS timeout. Mencari sinyal GPS lebih lama...';
    default:
      return 'Kesalahan GPS. Periksa koneksi internet dan pengaturan lokasi.';
  }
}

// Enhanced error messages with troubleshooting steps
function getDetailedErrorMessage(error: GeolocationPositionError, attempt: number): string {
  const baseMessage = getGeolocationErrorMessage(error);
  const troubleshooting = getTroubleshootingSteps(error);
  
  return `${baseMessage}\n\n${troubleshooting}`;
}

// Get troubleshooting steps based on error type
function getTroubleshootingSteps(error: GeolocationPositionError): string {
  switch (error.code) {
    case error.PERMISSION_DENIED:
      return `Langkah-langkah:\n1. Klik ikon lokasi di address bar\n2. Pilih "Selalu izinkan"\n3. Refresh halaman\n4. Atau buka Settings → Privacy → Location`;
    case error.POSITION_UNAVAILABLE:
      return `Langkah-langkah:\n1. Pastikan GPS aktif di perangkat\n2. Keluar ke area terbuka\n3. Restart aplikasi GPS\n4. Periksa mode pesawat tidak aktif`;
    case error.TIMEOUT:
      return `Langkah-langkah:\n1. Tunggu beberapa detik\n2. Pindah ke lokasi terbuka\n3. Restart browser\n4. Periksa koneksi internet`;
    default:
      return `Langkah-langkah:\n1. Refresh halaman\n2. Periksa koneksi internet\n3. Restart browser\n4. Coba lagi dalam beberapa menit`;
  }
}

export default useOptimizedGPS;