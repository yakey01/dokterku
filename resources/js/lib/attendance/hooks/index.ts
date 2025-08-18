/**
 * Unified Attendance Hooks Export
 * Central export for all attendance-related hooks
 */

// Core attendance state management
export {
  useAttendanceState,
  type AttendanceState,
  type AttendanceActions,
  type UseAttendanceStateOptions
} from './useAttendanceState';

// GPS and location management
export {
  useGPSLocation,
  type GPSState,
  type GPSActions,
  type UseGPSLocationOptions
} from './useGPSLocation';

// Attendance history and statistics
export {
  useAttendanceHistory,
  type AttendanceHistoryRecord,
  type AttendanceStatistics,
  type AttendanceHistoryState,
  type AttendanceHistoryActions,
  type UseAttendanceHistoryOptions
} from './useAttendanceHistory';

/**
 * Composite hook for complete attendance management
 * Combines all attendance hooks for full functionality
 */
import { useAttendanceState, UseAttendanceStateOptions } from './useAttendanceState';
import { useGPSLocation, UseGPSLocationOptions } from './useGPSLocation';
import { useAttendanceHistory, UseAttendanceHistoryOptions } from './useAttendanceHistory';
import { AttendanceVariant, WorkLocation } from '../types';

export interface UseAttendanceOptions {
  variant: AttendanceVariant;
  
  // State management options
  enableRealTimeUpdates?: boolean;
  updateInterval?: number;
  enableMultiShift?: boolean;
  
  // GPS options
  enableGPS?: boolean;
  enableLocationTracking?: boolean;
  workLocations?: WorkLocation[];
  requiredAccuracy?: number;
  
  // History options
  enableHistory?: boolean;
  historyPeriod?: 'daily' | 'weekly' | 'monthly' | 'yearly';
  enableStatistics?: boolean;
}

export interface AttendanceHookReturn {
  // State management
  attendanceState: ReturnType<typeof useAttendanceState>[0];
  attendanceActions: ReturnType<typeof useAttendanceState>[1];
  
  // GPS management
  gpsState: ReturnType<typeof useGPSLocation>[0];
  gpsActions: ReturnType<typeof useGPSLocation>[1];
  
  // History management
  historyState: ReturnType<typeof useAttendanceHistory>[0];
  historyActions: ReturnType<typeof useAttendanceHistory>[1];
  
  // Composite helpers
  isReady: boolean;
  hasErrors: boolean;
  allErrors: string[];
  getSystemStatus: () => string;
}

/**
 * Complete attendance management hook
 * Provides all attendance functionality in one hook
 */
export const useAttendance = (options: UseAttendanceOptions): AttendanceHookReturn => {
  const {
    variant,
    enableRealTimeUpdates = true,
    updateInterval = 1000,
    enableMultiShift = variant === 'dokter',
    enableGPS = true,
    enableLocationTracking = true,
    workLocations = [],
    requiredAccuracy = 100,
    enableHistory = true,
    historyPeriod = 'monthly',
    enableStatistics = true
  } = options;

  // Initialize all hooks
  const [attendanceState, attendanceActions] = useAttendanceState({
    variant,
    enableRealTimeUpdates,
    updateInterval,
    enableLocationTracking,
    enableMultiShift
  });

  const [gpsState, gpsActions] = useGPSLocation({
    enableTracking: enableLocationTracking,
    workLocations,
    requiredAccuracy,
    autoRequest: enableGPS
  });

  const [historyState, historyActions] = useAttendanceHistory({
    variant,
    initialPeriod: historyPeriod,
    autoLoad: enableHistory,
    enableStatistics
  });

  // Sync GPS location with attendance state
  React.useEffect(() => {
    if (gpsState.location) {
      attendanceActions.updateLocation(gpsState.location);
    }
  }, [gpsState.location, attendanceActions]);

  // Composite status helpers
  const isReady = !attendanceState.isLoading && !gpsState.isLoading && !historyState.isLoading;
  
  const hasErrors = !!(attendanceState.error || gpsState.error || historyState.error);
  
  const allErrors = [
    attendanceState.error?.message,
    gpsState.error?.message,
    historyState.error?.message
  ].filter(Boolean) as string[];

  const getSystemStatus = (): string => {
    if (!isReady) return 'Memuat sistem...';
    if (hasErrors) return `Error: ${allErrors[0]}`;
    if (attendanceState.isOperationInProgress) return 'Memproses operasi...';
    if (gpsState.isLoading) return 'Mencari lokasi...';
    return attendanceActions.getStatusSummary();
  };

  return {
    attendanceState,
    attendanceActions,
    gpsState,
    gpsActions,
    historyState,
    historyActions,
    isReady,
    hasErrors,
    allErrors,
    getSystemStatus
  };
};

/**
 * Simple hook for basic attendance functionality
 * Minimal setup for simple use cases
 */
export const useSimpleAttendance = (variant: AttendanceVariant) => {
  return useAttendance({
    variant,
    enableHistory: false,
    enableLocationTracking: false,
    enableStatistics: false
  });
};

/**
 * Full-featured hook for complete attendance system
 * All features enabled for comprehensive attendance management
 */
export const useFullAttendance = (variant: AttendanceVariant, workLocations: WorkLocation[] = []) => {
  return useAttendance({
    variant,
    enableRealTimeUpdates: true,
    enableMultiShift: variant === 'dokter',
    enableGPS: true,
    enableLocationTracking: true,
    workLocations,
    requiredAccuracy: 50,
    enableHistory: true,
    historyPeriod: 'monthly',
    enableStatistics: true
  });
};

// Re-export React for useEffect usage
import React from 'react';