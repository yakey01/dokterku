/**
 * React Hook for GPS Location Management
 * Provides easy integration with GPSManager
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import GPSManagerInstance, { LocationResult, GPSStatus, GPSStrategy, GPSManager } from '../utils/GPSManager';

export interface UseGPSLocationOptions {
  autoStart?: boolean;
  watchMode?: boolean;
  cacheTimeout?: number;
  fallbackLocation?: { lat: number; lng: number };
  onError?: (error: any) => void;
  onPermissionDenied?: () => void;
  enableHighAccuracy?: boolean;
}

export interface UseGPSLocationReturn {
  location: LocationResult | null;
  status: GPSStatus;
  error: Error | null;
  isLoading: boolean;
  accuracy: number | null;
  confidence: number;
  source: GPSStrategy | null;
  
  // Actions
  getCurrentLocation: () => Promise<void>;
  watchPosition: () => void;
  stopWatching: () => void;
  requestPermission: () => Promise<boolean>;
  retryLocation: () => Promise<void>;
  clearCache: () => void;
  
  // Utilities
  distanceToLocation: (lat: number, lng: number) => number | null;
  isWithinRadius: (lat: number, lng: number, radius: number) => boolean;
  getDiagnostics: () => object;
}

/**
 * Hook for GPS location management
 */
export function useGPSLocation(options: UseGPSLocationOptions = {}): UseGPSLocationReturn {
  const {
    autoStart = true,
    watchMode = false,
    cacheTimeout = 300000, // 5 minutes
    fallbackLocation,
    onError,
    onPermissionDenied,
    enableHighAccuracy = true
  } = options;

  // State
  const [location, setLocation] = useState<LocationResult | null>(null);
  const [status, setStatus] = useState<GPSStatus>(GPSStatus.IDLE);
  const [error, setError] = useState<Error | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  
  // Refs
  const watchIdRef = useRef<number | null>(null);
  const gpsManagerRef = useRef<typeof GPSManagerInstance>(GPSManagerInstance);
  const isMountedRef = useRef(true);
  const lastAppliedConfigRef = useRef<{ cacheTimeout: number; fallbackLocationKey: string } | null>(null);

  /**
   * Initialize GPS Manager with configuration
   */
  useEffect(() => {
    // Use the singleton instance directly
    gpsManagerRef.current = GPSManagerInstance;

    // Update configuration if provided
    if (fallbackLocation || cacheTimeout !== 300000) {
      const fallbackKey = fallbackLocation ? `${fallbackLocation.lat},${fallbackLocation.lng}` : 'none';
      const last = lastAppliedConfigRef.current;
      const shouldUpdate = !last || last.cacheTimeout !== cacheTimeout || last.fallbackLocationKey !== fallbackKey;

      if (shouldUpdate) {
        gpsManagerRef.current.updateConfig({
          defaultLocation: fallbackLocation,
          cacheExpiry: cacheTimeout,
          enableLogging: process.env.NODE_ENV === 'development'
        });
        lastAppliedConfigRef.current = { cacheTimeout, fallbackLocationKey: fallbackKey };
      }
    }

    // Listen to status changes
    const handleStatusChange = (newStatus: GPSStatus) => {
      if (isMountedRef.current) {
        setStatus(newStatus);
        
        if (newStatus === GPSStatus.PERMISSION_REQUIRED) {
          onPermissionDenied?.();
        }
      }
    };

    gpsManagerRef.current.on('status_changed', handleStatusChange);
    
    return () => {
      gpsManagerRef.current.off('status_changed', handleStatusChange);
    };
  }, [fallbackLocation, cacheTimeout, onPermissionDenied]);

  /**
   * Get current location
   */
  const getCurrentLocation = useCallback(async (forceRefresh: boolean = false) => {
    if (!isMountedRef.current) return;

    setIsLoading(true);
    setError(null);

    try {
      const result = await gpsManagerRef.current.getCurrentLocation(forceRefresh);
      
      if (isMountedRef.current) {
        setLocation(result);
        setStatus(GPSStatus.SUCCESS);
      }
    } catch (err) {
      if (isMountedRef.current) {
        const error = err as Error;
        setError(error);
        setStatus(GPSStatus.ERROR);
        onError?.(error);
      }
    } finally {
      if (isMountedRef.current) {
        setIsLoading(false);
      }
    }
  }, [onError]);

  /**
   * Start watching position
   */
  const watchPosition = useCallback(() => {
    if (watchIdRef.current) {
      return; // Already watching
    }

    const id = gpsManagerRef.current.watchPosition(
      (newLocation: LocationResult) => {
        if (isMountedRef.current) {
          setLocation(newLocation);
          setStatus(GPSStatus.SUCCESS);
          setError(null);
        }
      },
      (error: any) => {
        if (isMountedRef.current) {
          setError(error);
          setStatus(GPSStatus.ERROR);
          onError?.(error);
        }
      }
    );

    watchIdRef.current = id;
  }, [onError]);

  /**
   * Stop watching position
   */
  const stopWatching = useCallback(() => {
    if (watchIdRef.current) {
      gpsManagerRef.current.clearWatch();
      watchIdRef.current = null;
    }
  }, []);

  /**
   * Request GPS permission
   */
  const requestPermission = useCallback(async (): Promise<boolean> => {
    try {
      const granted = await gpsManagerRef.current.requestPermission();
      
      if (granted) {
        // Try to get location after permission granted
        await getCurrentLocation(true);
      }
      
      return granted;
    } catch (error) {
      console.error('Permission request failed:', error);
      return false;
    }
  }, [getCurrentLocation]);

  /**
   * Retry location with force refresh
   */
  const retryLocation = useCallback(async () => {
    await getCurrentLocation(true);
  }, [getCurrentLocation]);

  /**
   * Clear location cache
   */
  const clearCache = useCallback(() => {
    // Force refresh on next request
    setLocation(null);
    setStatus(GPSStatus.IDLE);
  }, []);

  /**
   * Calculate distance to a location
   */
  const distanceToLocation = useCallback((lat: number, lng: number): number | null => {
    if (!location) return null;

    const R = 6371e3; // Earth's radius in meters
    const φ1 = location.latitude * Math.PI / 180;
    const φ2 = lat * Math.PI / 180;
    const Δφ = (lat - location.latitude) * Math.PI / 180;
    const Δλ = (lng - location.longitude) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  }, [location]);

  /**
   * Check if current location is within radius of target
   */
  const isWithinRadius = useCallback((lat: number, lng: number, radius: number): boolean => {
    const distance = distanceToLocation(lat, lng);
    return distance !== null && distance <= radius;
  }, [distanceToLocation]);

  /**
   * Get diagnostics information
   */
  const getDiagnostics = useCallback((): object => {
    return gpsManagerRef.current.getDiagnostics();
  }, []);

  /**
   * Auto-start location on mount
   */
  useEffect(() => {
    isMountedRef.current = true;

    if (autoStart) {
      if (watchMode) {
        watchPosition();
      } else {
        getCurrentLocation();
      }
    }

    return () => {
      isMountedRef.current = false;
      stopWatching();
    };
  }, [autoStart, watchMode]); // Only run once on mount

  // Computed values
  const accuracy = location?.accuracy ?? null;
  const confidence = location?.confidence ?? 0;
  const source = location?.source ?? null;

  return {
    location,
    status,
    error,
    isLoading,
    accuracy,
    confidence,
    source,
    
    // Actions
    getCurrentLocation: () => getCurrentLocation(false),
    watchPosition,
    stopWatching,
    requestPermission,
    retryLocation,
    clearCache,
    
    // Utilities
    distanceToLocation,
    isWithinRadius,
    getDiagnostics
  };
}

/**
 * Hook for GPS permission status
 */
export function useGPSPermission() {
  const [permission, setPermission] = useState<PermissionState>('prompt');

  useEffect(() => {
    if (!navigator.permissions) {
      setPermission('granted'); // Assume granted if can't check
      return;
    }

    navigator.permissions.query({ name: 'geolocation' }).then((result) => {
      setPermission(result.state);
      
      // Listen for changes
      result.addEventListener('change', () => {
        setPermission(result.state);
      });
    }).catch(() => {
      setPermission('granted'); // Assume granted on error
    });
  }, []);

  return permission;
}

/**
 * Hook for GPS availability
 */
export function useGPSAvailability() {
  const [available, setAvailable] = useState<boolean>(false);
  const [reason, setReason] = useState<string>('');

  useEffect(() => {
    const checkAvailability = () => {
      if (!navigator.geolocation) {
        setAvailable(false);
        setReason('Geolocation API not supported');
        return;
      }

      const isHttps = window.location.protocol === 'https:';
      const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
      
      if (!isHttps && !isLocalhost) {
        setAvailable(false);
        setReason('HTTPS required for GPS (currently on HTTP)');
        return;
      }

      setAvailable(true);
      setReason('');
    };

    checkAvailability();
  }, []);

  return { available, reason };
}