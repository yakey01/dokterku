/**
 * Unified Location Utilities
 * Shared GPS and location functions for attendance systems
 */

import { LocationData, GPSResult, ValidationResult, WorkLocation } from './types';

/**
 * Calculate distance between two coordinates using Haversine formula
 * (Used for work location validation)
 */
export const calculateDistance = (
  lat1: number,
  lng1: number,
  lat2: number,
  lng2: number
): number => {
  const R = 6371000; // Earth's radius in meters
  const dLat = toRadians(lat2 - lat1);
  const dLng = toRadians(lng2 - lng1);
  
  const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
    Math.sin(dLng / 2) * Math.sin(dLng / 2);
  
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
};

/**
 * Convert degrees to radians
 */
const toRadians = (degrees: number): number => {
  return degrees * (Math.PI / 180);
};

/**
 * Validate if location is within work area
 */
export const validateWorkLocation = (
  userLocation: LocationData,
  workLocations: WorkLocation[]
): ValidationResult => {
  if (!userLocation || !workLocations || workLocations.length === 0) {
    return {
      isValid: false,
      message: 'Lokasi kerja tidak ditemukan',
      errors: ['No work locations configured']
    };
  }

  const activeLocations = workLocations.filter(loc => loc.is_active);
  
  if (activeLocations.length === 0) {
    return {
      isValid: false,
      message: 'Tidak ada lokasi kerja aktif',
      errors: ['No active work locations']
    };
  }

  const validations = activeLocations.map(location => {
    const distance = calculateDistance(
      userLocation.lat,
      userLocation.lng,
      location.latitude,
      location.longitude
    );

    return {
      location,
      distance,
      isWithinRadius: distance <= location.radius
    };
  });

  const validLocation = validations.find(v => v.isWithinRadius);
  
  if (validLocation) {
    return {
      isValid: true,
      message: `Lokasi valid - ${validLocation.location.name} (${Math.round(validLocation.distance)}m)`,
      warnings: validLocation.distance > (validLocation.location.radius * 0.8) ? 
        ['Anda berada di tepi area kerja'] : undefined
    };
  }

  // Find closest location for better error message
  const closest = validations.reduce((prev, current) => 
    current.distance < prev.distance ? current : prev
  );

  return {
    isValid: false,
    message: `Anda terlalu jauh dari area kerja. Jarak ke ${closest.location.name}: ${Math.round(closest.distance)}m (maksimal ${closest.location.radius}m)`,
    errors: [`Distance: ${Math.round(closest.distance)}m > ${closest.location.radius}m`]
  };
};

/**
 * Get location accuracy description
 */
export const getAccuracyDescription = (accuracy: number | undefined): string => {
  if (!accuracy) return 'Tidak diketahui';
  
  if (accuracy <= 10) return 'Sangat akurat';
  if (accuracy <= 50) return 'Akurat';
  if (accuracy <= 100) return 'Cukup akurat';
  if (accuracy <= 500) return 'Kurang akurat';
  return 'Tidak akurat';
};

/**
 * Format location for display
 */
export const formatLocationDisplay = (location: LocationData): string => {
  if (location.address) {
    return location.address;
  }
  
  return `${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}`;
};

/**
 * Check if GPS coordinates are valid
 */
export const isValidCoordinates = (lat: number, lng: number): boolean => {
  return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 && 
         lat !== 0 && lng !== 0; // Exclude null island
};

/**
 * GPS result validator
 */
export const validateGPSResult = (result: any): GPSResult | null => {
  if (!result || typeof result !== 'object') {
    return null;
  }

  const { latitude, longitude, accuracy, source, timestamp, cached, confidence } = result;

  if (!isValidCoordinates(latitude, longitude)) {
    return null;
  }

  return {
    latitude: Number(latitude),
    longitude: Number(longitude),
    accuracy: Number(accuracy || 999),
    source: String(source || 'unknown'),
    timestamp: Number(timestamp || Date.now()),
    cached: Boolean(cached),
    confidence: Number(confidence || 0)
  };
};

/**
 * Get GPS strategy priority
 */
export const getGPSStrategyPriority = (strategy: string): number => {
  const priorities: Record<string, number> = {
    'high_accuracy_gps': 1,
    'network_based': 2,
    'cached_location': 3,
    'ip_geolocation': 4,
    'user_manual': 5,
    'default_fallback': 6
  };
  
  return priorities[strategy] || 999;
};

/**
 * Calculate GPS confidence score
 */
export const calculateGPSConfidence = (
  accuracy: number,
  age: number,
  source: string
): number => {
  // Base confidence from accuracy (0-1)
  let confidence = Math.max(0, Math.min(1, (100 - accuracy) / 100));
  
  // Reduce confidence based on age (older = less confident)
  const ageMinutes = age / (1000 * 60);
  if (ageMinutes > 5) {
    confidence *= Math.max(0.1, 1 - (ageMinutes - 5) / 60);
  }
  
  // Adjust based on source
  const sourceMultipliers: Record<string, number> = {
    'high_accuracy_gps': 1.0,
    'network_based': 0.8,
    'cached_location': 0.6,
    'ip_geolocation': 0.3,
    'user_manual': 0.9,
    'default_fallback': 0.1
  };
  
  confidence *= sourceMultipliers[source] || 0.5;
  
  return Math.max(0, Math.min(1, confidence));
};

/**
 * Format GPS status for display
 */
export const formatGPSStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    'idle': 'Siap',
    'requesting': 'Mengambil lokasi...',
    'success': 'Berhasil',
    'error': 'Error',
    'fallback': 'Menggunakan cadangan',
    'permission_required': 'Butuh izin lokasi'
  };
  
  return statusMap[status] || status;
};

/**
 * Get default location fallback (clinic location)
 */
export const getDefaultLocation = (): LocationData => {
  return {
    lat: -7.898878,
    lng: 111.961884,
    accuracy: 999,
    address: 'RS. Kediri Medical Center',
    timestamp: Date.now()
  };
};

/**
 * Merge multiple GPS results (for averaging)
 */
export const mergeGPSResults = (results: GPSResult[]): GPSResult | null => {
  if (!results || results.length === 0) {
    return null;
  }
  
  if (results.length === 1) {
    return results[0];
  }
  
  // Weight by confidence and accuracy
  let totalWeight = 0;
  let weightedLat = 0;
  let weightedLng = 0;
  let bestAccuracy = Infinity;
  let latestTimestamp = 0;
  
  results.forEach(result => {
    const weight = result.confidence / (result.accuracy + 1);
    totalWeight += weight;
    weightedLat += result.latitude * weight;
    weightedLng += result.longitude * weight;
    bestAccuracy = Math.min(bestAccuracy, result.accuracy);
    latestTimestamp = Math.max(latestTimestamp, result.timestamp);
  });
  
  if (totalWeight === 0) {
    return results[0];
  }
  
  return {
    latitude: weightedLat / totalWeight,
    longitude: weightedLng / totalWeight,
    accuracy: bestAccuracy,
    source: 'merged',
    timestamp: latestTimestamp,
    cached: false,
    confidence: Math.min(1, totalWeight / results.length)
  };
};