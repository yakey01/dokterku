/**
 * World-Class GPS Manager
 * Handles all GPS operations with multiple fallback strategies
 * and intelligent error recovery
 */

// Browser-compatible EventEmitter implementation
class EventEmitter {
  private events: Map<string, Function[]> = new Map();

  on(event: string, listener: Function): void {
    if (!this.events.has(event)) {
      this.events.set(event, []);
    }
    this.events.get(event)!.push(listener);
  }

  off(event: string, listener: Function): void {
    const listeners = this.events.get(event);
    if (listeners) {
      const index = listeners.indexOf(listener);
      if (index !== -1) {
        listeners.splice(index, 1);
      }
    }
  }

  emit(event: string, ...args: any[]): void {
    const listeners = this.events.get(event);
    if (listeners) {
      listeners.forEach(listener => listener(...args));
    }
  }

  removeAllListeners(event?: string): void {
    if (event) {
      this.events.delete(event);
    } else {
      this.events.clear();
    }
  }
}

// GPS Strategy Types
export enum GPSStrategy {
  HIGH_ACCURACY_GPS = 'high_accuracy_gps',
  NETWORK_BASED = 'network_based',
  IP_GEOLOCATION = 'ip_geolocation',
  CACHED_LOCATION = 'cached_location',
  USER_MANUAL_INPUT = 'user_manual',
  DEFAULT_FALLBACK = 'default_fallback'
}

// Location Result Interface
export interface LocationResult {
  latitude: number;
  longitude: number;
  accuracy: number;
  source: GPSStrategy;
  timestamp: number;
  cached: boolean;
  confidence: number; // 0-1 confidence score
}

// GPS Status
export enum GPSStatus {
  IDLE = 'idle',
  REQUESTING = 'requesting',
  SUCCESS = 'success',
  ERROR = 'error',
  FALLBACK = 'fallback',
  PERMISSION_REQUIRED = 'permission_required'
}

// Configuration
export interface GPSManagerConfig {
  strategies: GPSStrategy[];
  maxRetries: number;
  timeoutProgression: number[];
  cacheExpiry: number;
  accuracyThreshold: number;
  distanceThreshold: number;
  defaultLocation: { lat: number; lng: number };
  enableLogging: boolean;
}

// Singleton GPS Manager
export class GPSManager extends EventEmitter {
  private static instance: GPSManager;
  private config: GPSManagerConfig;
  private locationCache: Map<string, LocationResult>;
  private currentRequest: Promise<LocationResult> | null = null;
  private watchId: number | null = null;
  private status: GPSStatus = GPSStatus.IDLE;
  private retryCount: number = 0;
  private lastKnownLocation: LocationResult | null = null;

  private constructor(config: Partial<GPSManagerConfig> = {}) {
    super();
    
    this.config = {
      strategies: [
        GPSStrategy.HIGH_ACCURACY_GPS,
        GPSStrategy.NETWORK_BASED,
        GPSStrategy.IP_GEOLOCATION,
        GPSStrategy.CACHED_LOCATION,
        GPSStrategy.DEFAULT_FALLBACK
      ],
      maxRetries: 3,
      timeoutProgression: [5000, 3000, 2000], // Decreasing timeouts
      cacheExpiry: 5 * 60 * 1000, // 5 minutes
      accuracyThreshold: 100, // meters
      distanceThreshold: 500, // meters for cache validity
      defaultLocation: { lat: -7.898878, lng: 111.961884 }, // Hospital location
      enableLogging: true,
      ...config
    };

    this.locationCache = new Map();
    this.initializeLocationPersistence();
  }

  public static getInstance(config?: Partial<GPSManagerConfig>): GPSManager {
    if (!GPSManager.instance) {
      GPSManager.instance = new GPSManager(config);
    }
    return GPSManager.instance;
  }

  /**
   * Initialize location persistence from localStorage
   */
  private initializeLocationPersistence(): void {
    try {
      const saved = localStorage.getItem('gps_last_location');
      if (saved) {
        const location = JSON.parse(saved);
        const age = Date.now() - location.timestamp;
        
        // Use saved location if less than 1 hour old
        if (age < 3600000) {
          this.lastKnownLocation = location;
          this.locationCache.set('last_known', location);
          this.log('Restored last known location from storage');
        }
      }
    } catch (error) {
      this.log('Could not restore saved location', 'warn');
    }
  }

  /**
   * Get current location using strategy waterfall
   */
  public async getCurrentLocation(forceRefresh: boolean = false): Promise<LocationResult> {
    // Prevent duplicate concurrent requests
    if (this.currentRequest && !forceRefresh) {
      this.log('Returning existing location request');
      return this.currentRequest;
    }

    // Check cache first unless forced refresh
    if (!forceRefresh) {
      const cached = this.getCachedLocation();
      if (cached) {
        this.log('Using cached location');
        return cached;
      }
    }

    // Start new location request
    this.currentRequest = this.executeStrategies();
    
    try {
      const result = await this.currentRequest;
      this.saveLocation(result);
      return result;
    } finally {
      this.currentRequest = null;
    }
  }

  /**
   * Execute location strategies in order
   */
  private async executeStrategies(): Promise<LocationResult> {
    this.setStatus(GPSStatus.REQUESTING);
    
    for (const strategy of this.config.strategies) {
      try {
        this.log(`Attempting strategy: ${strategy}`);
        const result = await this.executeStrategy(strategy);
        
        if (result && this.isLocationValid(result)) {
          this.setStatus(GPSStatus.SUCCESS);
          this.retryCount = 0;
          return result;
        }
      } catch (error) {
        this.log(`Strategy ${strategy} failed: ${error}`, 'warn');
        continue;
      }
    }

    // All strategies failed, use ultimate fallback
    this.setStatus(GPSStatus.FALLBACK);
    return this.getDefaultLocation();
  }

  /**
   * Execute individual strategy
   */
  private async executeStrategy(strategy: GPSStrategy): Promise<LocationResult> {
    switch (strategy) {
      case GPSStrategy.HIGH_ACCURACY_GPS:
        return await this.getHighAccuracyGPS();
      
      case GPSStrategy.NETWORK_BASED:
        return await this.getNetworkBasedLocation();
      
      case GPSStrategy.IP_GEOLOCATION:
        return await this.getIPGeolocation();
      
      case GPSStrategy.CACHED_LOCATION:
        return this.getCachedLocation() || Promise.reject('No cache');
      
      case GPSStrategy.DEFAULT_FALLBACK:
        return this.getDefaultLocation();
      
      default:
        throw new Error(`Unknown strategy: ${strategy}`);
    }
  }

  /**
   * High accuracy GPS with progressive timeout
   */
  private async getHighAccuracyGPS(): Promise<LocationResult> {
    if (!this.canUseGeolocation()) {
      throw new Error('Geolocation not available');
    }

    const timeout = this.config.timeoutProgression[
      Math.min(this.retryCount, this.config.timeoutProgression.length - 1)
    ];

    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            source: GPSStrategy.HIGH_ACCURACY_GPS,
            timestamp: Date.now(),
            cached: false,
            confidence: this.calculateConfidence(position.coords.accuracy)
          });
        },
        (error) => {
          this.handleGeolocationError(error);
          reject(error);
        },
        {
          enableHighAccuracy: true,
          timeout: timeout,
          maximumAge: 30000
        }
      );
    });
  }

  /**
   * Network-based location (lower accuracy, faster)
   */
  private async getNetworkBasedLocation(): Promise<LocationResult> {
    if (!this.canUseGeolocation()) {
      throw new Error('Geolocation not available');
    }

    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            source: GPSStrategy.NETWORK_BASED,
            timestamp: Date.now(),
            cached: false,
            confidence: this.calculateConfidence(position.coords.accuracy) * 0.8
          });
        },
        reject,
        {
          enableHighAccuracy: false,
          timeout: 3000,
          maximumAge: 60000
        }
      );
    });
  }

  /**
   * IP-based geolocation fallback
   */
  private async getIPGeolocation(): Promise<LocationResult> {
    try {
      // Try multiple IP geolocation services
      const services = [
        'https://ipapi.co/json/',
        'https://ip-api.com/json/',
        'https://geolocation-db.com/json/'
      ];

      for (const service of services) {
        try {
          const response = await fetch(service, { 
            signal: AbortSignal.timeout(3000) 
          });
          
          if (response.ok) {
            const data = await response.json();
            
            // Normalize different API responses
            const lat = data.latitude || data.lat;
            const lng = data.longitude || data.lon || data.lng;
            
            if (lat && lng) {
              return {
                latitude: parseFloat(lat),
                longitude: parseFloat(lng),
                accuracy: 5000, // IP geolocation is very inaccurate
                source: GPSStrategy.IP_GEOLOCATION,
                timestamp: Date.now(),
                cached: false,
                confidence: 0.3 // Low confidence
              };
            }
          }
        } catch (error) {
          continue; // Try next service
        }
      }
      
      throw new Error('All IP geolocation services failed');
    } catch (error) {
      throw new Error(`IP geolocation failed: ${error}`);
    }
  }

  /**
   * Get cached location if valid
   */
  private getCachedLocation(): LocationResult | null {
    // Check memory cache
    const cached = this.locationCache.get('current');
    
    if (cached) {
      const age = Date.now() - cached.timestamp;
      
      if (age < this.config.cacheExpiry) {
        return {
          ...cached,
          cached: true,
          confidence: cached.confidence * (1 - age / this.config.cacheExpiry)
        };
      }
    }

    // Check last known location
    if (this.lastKnownLocation) {
      const age = Date.now() - this.lastKnownLocation.timestamp;
      
      // Use last known if less than 1 hour old
      if (age < 3600000) {
        return {
          ...this.lastKnownLocation,
          cached: true,
          confidence: this.lastKnownLocation.confidence * 0.5
        };
      }
    }

    return null;
  }

  /**
   * Get default fallback location
   */
  private getDefaultLocation(): LocationResult {
    return {
      latitude: this.config.defaultLocation.lat,
      longitude: this.config.defaultLocation.lng,
      accuracy: 100,
      source: GPSStrategy.DEFAULT_FALLBACK,
      timestamp: Date.now(),
      cached: false,
      confidence: 0.1 // Very low confidence
    };
  }

  /**
   * Watch position with intelligent updates
   */
  public watchPosition(
    callback: (location: LocationResult) => void,
    errorCallback?: (error: any) => void
  ): number {
    if (this.watchId) {
      this.clearWatch();
    }

    if (!this.canUseGeolocation()) {
      // Fallback to polling with cache
      this.watchId = window.setInterval(async () => {
        try {
          const location = await this.getCurrentLocation();
          callback(location);
        } catch (error) {
          errorCallback?.(error);
        }
      }, 30000); // Poll every 30 seconds
      
      return this.watchId;
    }

    // Use native watch with intelligent filtering
    let lastUpdate = Date.now();
    let lastLocation: LocationResult | null = null;

    this.watchId = navigator.geolocation.watchPosition(
      (position) => {
        const now = Date.now();
        const location: LocationResult = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          source: GPSStrategy.HIGH_ACCURACY_GPS,
          timestamp: now,
          cached: false,
          confidence: this.calculateConfidence(position.coords.accuracy)
        };

        // Only update if significant change or time elapsed
        const shouldUpdate = 
          !lastLocation ||
          now - lastUpdate > 10000 || // 10 seconds elapsed
          this.calculateDistance(location, lastLocation) > 10; // Moved >10 meters

        if (shouldUpdate) {
          lastUpdate = now;
          lastLocation = location;
          this.saveLocation(location);
          callback(location);
        }
      },
      (error) => {
        this.handleGeolocationError(error);
        errorCallback?.(error);
      },
      {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 5000
      }
    );

    return this.watchId;
  }

  /**
   * Clear position watch
   */
  public clearWatch(): void {
    if (this.watchId) {
      if (typeof this.watchId === 'number' && navigator.geolocation) {
        navigator.geolocation.clearWatch(this.watchId);
      } else {
        clearInterval(this.watchId);
      }
      this.watchId = null;
    }
  }

  /**
   * Check if geolocation can be used
   */
  private canUseGeolocation(): boolean {
    if (!navigator.geolocation) {
      return false;
    }

    const isHttps = window.location.protocol === 'https:';
    const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    
    return isHttps || isLocalhost;
  }

  /**
   * Handle geolocation errors intelligently
   */
  private handleGeolocationError(error: GeolocationPositionError): void {
    switch (error.code) {
      case error.PERMISSION_DENIED:
        this.setStatus(GPSStatus.PERMISSION_REQUIRED);
        this.emit('permission_required');
        this.log('GPS permission denied', 'error');
        break;
      
      case error.POSITION_UNAVAILABLE:
        this.log('GPS position unavailable', 'warn');
        this.retryCount++;
        break;
      
      case error.TIMEOUT:
        this.log('GPS timeout', 'warn');
        this.retryCount++;
        break;
      
      default:
        this.log(`Unknown GPS error: ${error.message}`, 'error');
    }
  }

  /**
   * Calculate confidence score based on accuracy
   */
  private calculateConfidence(accuracy: number): number {
    if (accuracy <= 10) return 1.0;
    if (accuracy <= 50) return 0.9;
    if (accuracy <= 100) return 0.7;
    if (accuracy <= 500) return 0.5;
    if (accuracy <= 1000) return 0.3;
    return 0.1;
  }

  /**
   * Calculate distance between two points (Haversine formula)
   */
  private calculateDistance(loc1: LocationResult, loc2: LocationResult): number {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = loc1.latitude * Math.PI / 180;
    const φ2 = loc2.latitude * Math.PI / 180;
    const Δφ = (loc2.latitude - loc1.latitude) * Math.PI / 180;
    const Δλ = (loc2.longitude - loc1.longitude) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  }

  /**
   * Validate location result
   */
  private isLocationValid(location: LocationResult): boolean {
    return location.latitude !== 0 && 
           location.longitude !== 0 &&
           location.accuracy <= this.config.accuracyThreshold * 10;
  }

  /**
   * Save location to cache and localStorage
   */
  private saveLocation(location: LocationResult): void {
    this.locationCache.set('current', location);
    this.lastKnownLocation = location;
    
    try {
      localStorage.setItem('gps_last_location', JSON.stringify(location));
    } catch (error) {
      this.log('Could not save location to storage', 'warn');
    }
  }

  /**
   * Set GPS status and emit event
   */
  private setStatus(status: GPSStatus): void {
    if (this.status !== status) {
      this.status = status;
      this.emit('status_changed', status);
    }
  }

  /**
   * Logging utility
   */
  private log(message: string, level: 'log' | 'warn' | 'error' = 'log'): void {
    if (this.config.enableLogging) {
      const prefix = '🌍 [GPSManager]';
      console[level](`${prefix} ${message}`);
    }
  }

  /**
   * Get current status
   */
  public getStatus(): GPSStatus {
    return this.status;
  }

  /**
   * Update configuration
   */
  public updateConfig(newConfig: Partial<GPSManagerConfig>): void {
    this.config = { ...this.config, ...newConfig };
    this.log('Configuration updated');
  }

  /**
   * Request permission explicitly
   */
  public async requestPermission(): Promise<boolean> {
    if (!navigator.permissions) {
      return true; // Can't check, assume granted
    }

    try {
      const result = await navigator.permissions.query({ name: 'geolocation' });
      
      if (result.state === 'granted') {
        return true;
      } else if (result.state === 'prompt') {
        // Trigger permission request
        await this.getHighAccuracyGPS();
        return true;
      } else {
        return false;
      }
    } catch (error) {
      this.log('Could not check permissions', 'warn');
      return true; // Assume granted
    }
  }

  /**
   * Get diagnostic information
   */
  public getDiagnostics(): object {
    return {
      canUseGeolocation: this.canUseGeolocation(),
      status: this.status,
      retryCount: this.retryCount,
      hasCache: this.locationCache.size > 0,
      lastKnownLocation: this.lastKnownLocation,
      protocol: window.location.protocol,
      hostname: window.location.hostname,
      userAgent: navigator.userAgent
    };
  }
}

// Export singleton instance as default
export default GPSManager.getInstance();