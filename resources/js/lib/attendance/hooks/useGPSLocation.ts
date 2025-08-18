/**
 * Unified GPS Location Management Hook
 * Handles GPS permissions, location tracking, and geolocation for attendance systems
 */

import { useState, useEffect, useRef, useCallback } from 'react';
import { LocationData, AttendanceError, WorkLocation } from '../types';
import { validateWorkLocation } from '../locationUtils';

export interface GPSState {
  // Location data
  location: LocationData | null;
  accuracy: number | null;
  lastUpdate: Date | null;
  
  // Permission and availability
  permission: 'granted' | 'denied' | 'prompt' | 'unknown';
  isAvailable: boolean;
  isSupported: boolean;
  
  // Operation states
  isLoading: boolean;
  isTracking: boolean;
  error: AttendanceError | null;
  
  // Validation
  isWithinWorkArea: boolean;
  distanceToWork: number | null;
  nearestWorkLocation: WorkLocation | null;
}

export interface GPSActions {
  // Core operations
  requestLocation: () => Promise<LocationData | null>;
  startTracking: () => void;
  stopTracking: () => void;
  refreshLocation: () => Promise<LocationData | null>;
  
  // Permission management
  requestPermission: () => Promise<'granted' | 'denied' | 'prompt'>;
  checkPermission: () => Promise<'granted' | 'denied' | 'prompt'>;
  
  // Validation
  validateCurrentLocation: () => boolean;
  getLocationStatus: () => string;
  clearError: () => void;
}

export interface UseGPSLocationOptions {
  enableTracking?: boolean;
  trackingInterval?: number;
  timeout?: number;
  enableHighAccuracy?: boolean;
  maximumAge?: number;
  workLocations?: WorkLocation[];
  requiredAccuracy?: number; // meters
  autoRequest?: boolean;
}

export const useGPSLocation = (
  options: UseGPSLocationOptions = {}
): [GPSState, GPSActions] => {
  const {
    enableTracking = false,
    trackingInterval = 30000, // 30 seconds
    timeout = 15000, // 15 seconds
    enableHighAccuracy = true,
    maximumAge = 300000, // 5 minutes
    workLocations = [],
    requiredAccuracy = 100, // meters
    autoRequest = true
  } = options;

  // Refs for cleanup and management
  const watchIdRef = useRef<number | null>(null);
  const trackingIntervalRef = useRef<number | null>(null);
  const isMountedRef = useRef(true);

  // Core location state
  const [location, setLocation] = useState<LocationData | null>(null);
  const [accuracy, setAccuracy] = useState<number | null>(null);
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

  // Permission and availability state
  const [permission, setPermission] = useState<'granted' | 'denied' | 'prompt' | 'unknown'>('unknown');
  const [isAvailable, setIsAvailable] = useState(false);
  const [isSupported, setIsSupported] = useState(false);

  // Operation states
  const [isLoading, setIsLoading] = useState(false);
  const [isTracking, setIsTracking] = useState(false);
  const [error, setError] = useState<AttendanceError | null>(null);

  // Validation state
  const [isWithinWorkArea, setIsWithinWorkArea] = useState(false);
  const [distanceToWork, setDistanceToWork] = useState<number | null>(null);
  const [nearestWorkLocation, setNearestWorkLocation] = useState<WorkLocation | null>(null);

  /**
   * Check if geolocation is supported
   */
  const checkGeolocationSupport = useCallback((): boolean => {
    const supported = 'geolocation' in navigator;
    setIsSupported(supported);
    
    if (!supported) {
      setError({
        type: 'system',
        message: 'Geolocation tidak didukung oleh browser ini',
        code: 'geolocation.not_supported'
      });
    }
    
    return supported;
  }, []);

  /**
   * Check current permission status
   */
  const checkPermission = useCallback(async (): Promise<'granted' | 'denied' | 'prompt'> => {
    if (!navigator.permissions) {
      return 'unknown';
    }

    try {
      const result = await navigator.permissions.query({ name: 'geolocation' });
      const permissionState = result.state as 'granted' | 'denied' | 'prompt';
      setPermission(permissionState);
      setIsAvailable(permissionState === 'granted');
      return permissionState;
    } catch (err) {
      console.warn('Permission check failed:', err);
      return 'unknown';
    }
  }, []);

  /**
   * Request geolocation permission
   */
  const requestPermission = useCallback(async (): Promise<'granted' | 'denied' | 'prompt'> => {
    if (!checkGeolocationSupport()) {
      return 'denied';
    }

    return new Promise((resolve) => {
      navigator.geolocation.getCurrentPosition(
        () => {
          setPermission('granted');
          setIsAvailable(true);
          setError(null);
          resolve('granted');
        },
        (error) => {
          const permissionState = error.code === 1 ? 'denied' : 'prompt';
          setPermission(permissionState);
          setIsAvailable(false);
          
          setError({
            type: 'gps',
            message: getGeolocationErrorMessage(error.code),
            code: `gps.${getGeolocationErrorCode(error.code)}`,
            details: error
          });
          
          resolve(permissionState);
        },
        {
          enableHighAccuracy: false,
          timeout: 5000,
          maximumAge: Infinity
        }
      );
    });
  }, [checkGeolocationSupport]);

  /**
   * Get current location
   */
  const requestLocation = useCallback(async (): Promise<LocationData | null> => {
    if (!isSupported) {
      await checkGeolocationSupport();
      if (!isSupported) return null;
    }

    setIsLoading(true);
    setError(null);

    return new Promise((resolve) => {
      const options: PositionOptions = {
        enableHighAccuracy,
        timeout,
        maximumAge
      };

      navigator.geolocation.getCurrentPosition(
        (position) => {
          if (!isMountedRef.current) return;

          const locationData: LocationData = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: new Date()
          };

          setLocation(locationData);
          setAccuracy(position.coords.accuracy);
          setLastUpdate(new Date());
          setIsLoading(false);
          setError(null);

          // Validate against work locations
          validateLocationAgainstWorkAreas(locationData);

          resolve(locationData);
        },
        (error) => {
          if (!isMountedRef.current) return;

          const gpsError: AttendanceError = {
            type: 'gps',
            message: getGeolocationErrorMessage(error.code),
            code: `gps.${getGeolocationErrorCode(error.code)}`,
            details: error
          };

          setError(gpsError);
          setIsLoading(false);
          resolve(null);
        },
        options
      );
    });
  }, [isSupported, enableHighAccuracy, timeout, maximumAge, checkGeolocationSupport]);

  /**
   * Start continuous location tracking
   */
  const startTracking = useCallback(() => {
    if (!isSupported || isTracking) return;

    const options: PositionOptions = {
      enableHighAccuracy,
      timeout,
      maximumAge
    };

    watchIdRef.current = navigator.geolocation.watchPosition(
      (position) => {
        if (!isMountedRef.current) return;

        const locationData: LocationData = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
          accuracy: position.coords.accuracy,
          timestamp: new Date()
        };

        setLocation(locationData);
        setAccuracy(position.coords.accuracy);
        setLastUpdate(new Date());
        setError(null);

        // Validate against work locations
        validateLocationAgainstWorkAreas(locationData);
      },
      (error) => {
        if (!isMountedRef.current) return;

        const gpsError: AttendanceError = {
          type: 'gps',
          message: getGeolocationErrorMessage(error.code),
          code: `gps.${getGeolocationErrorCode(error.code)}`,
          details: error
        };

        setError(gpsError);
      },
      options
    );

    setIsTracking(true);

    // Optional: Set up periodic refresh
    if (trackingInterval > 0) {
      trackingIntervalRef.current = window.setInterval(() => {
        requestLocation();
      }, trackingInterval);
    }
  }, [isSupported, isTracking, enableHighAccuracy, timeout, maximumAge, trackingInterval, requestLocation]);

  /**
   * Stop location tracking
   */
  const stopTracking = useCallback(() => {
    if (watchIdRef.current !== null) {
      navigator.geolocation.clearWatch(watchIdRef.current);
      watchIdRef.current = null;
    }

    if (trackingIntervalRef.current !== null) {
      clearInterval(trackingIntervalRef.current);
      trackingIntervalRef.current = null;
    }

    setIsTracking(false);
  }, []);

  /**
   * Refresh current location
   */
  const refreshLocation = useCallback(async (): Promise<LocationData | null> => {
    return await requestLocation();
  }, [requestLocation]);

  /**
   * Validate location against work areas
   */
  const validateLocationAgainstWorkAreas = useCallback((locationData: LocationData) => {
    if (workLocations.length === 0) {
      setIsWithinWorkArea(true); // No work location restrictions
      setDistanceToWork(null);
      setNearestWorkLocation(null);
      return;
    }

    const validation = validateWorkLocation(locationData, workLocations);
    
    setIsWithinWorkArea(validation.isValid);
    setDistanceToWork(validation.nearestDistance);
    setNearestWorkLocation(validation.nearestLocation);
  }, [workLocations]);

  /**
   * Validate current location
   */
  const validateCurrentLocation = useCallback((): boolean => {
    if (!location) return false;
    if (workLocations.length === 0) return true;
    
    return isWithinWorkArea;
  }, [location, workLocations, isWithinWorkArea]);

  /**
   * Get location status message
   */
  const getLocationStatus = useCallback((): string => {
    if (error) return error.message;
    if (isLoading) return 'Mencari lokasi...';
    if (!isSupported) return 'GPS tidak didukung';
    if (permission === 'denied') return 'Izin lokasi ditolak';
    if (!location) return 'Lokasi belum tersedia';
    
    if (accuracy !== null) {
      if (accuracy <= requiredAccuracy) {
        return `GPS akurat (±${Math.round(accuracy)}m)`;
      } else {
        return `GPS kurang akurat (±${Math.round(accuracy)}m)`;
      }
    }
    
    return 'GPS siap';
  }, [error, isLoading, isSupported, permission, location, accuracy, requiredAccuracy]);

  /**
   * Clear current error
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // Helper functions for error handling
  const getGeolocationErrorMessage = (code: number): string => {
    switch (code) {
      case 1: return 'Izin lokasi diperlukan untuk presensi';
      case 2: return 'Lokasi tidak tersedia';
      case 3: return 'Timeout mendapatkan lokasi';
      default: return 'Error mendapatkan lokasi';
    }
  };

  const getGeolocationErrorCode = (code: number): string => {
    switch (code) {
      case 1: return 'permission_denied';
      case 2: return 'position_unavailable';
      case 3: return 'timeout';
      default: return 'unknown';
    }
  };

  // Initialize GPS state
  useEffect(() => {
    checkGeolocationSupport();
    checkPermission();

    if (autoRequest && isSupported) {
      requestPermission();
    }

    return () => {
      isMountedRef.current = false;
    };
  }, [checkGeolocationSupport, checkPermission, autoRequest, isSupported, requestPermission]);

  // Start tracking if enabled and permission granted
  useEffect(() => {
    if (enableTracking && permission === 'granted' && !isTracking) {
      startTracking();
    }

    return () => {
      stopTracking();
    };
  }, [enableTracking, permission, isTracking, startTracking, stopTracking]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      isMountedRef.current = false;
      stopTracking();
    };
  }, [stopTracking]);

  // Validate location when work locations change
  useEffect(() => {
    if (location) {
      validateLocationAgainstWorkAreas(location);
    }
  }, [location, workLocations, validateLocationAgainstWorkAreas]);

  // Compose state object
  const state: GPSState = {
    location,
    accuracy,
    lastUpdate,
    permission,
    isAvailable,
    isSupported,
    isLoading,
    isTracking,
    error,
    isWithinWorkArea,
    distanceToWork,
    nearestWorkLocation
  };

  // Compose actions object
  const actions: GPSActions = {
    requestLocation,
    startTracking,
    stopTracking,
    refreshLocation,
    requestPermission,
    checkPermission,
    validateCurrentLocation,
    getLocationStatus,
    clearError
  };

  return [state, actions];
};