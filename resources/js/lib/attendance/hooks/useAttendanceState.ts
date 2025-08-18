/**
 * Unified Attendance State Management Hook
 * Centralizes all attendance-related state for both dokter and paramedis variants
 */

import { useState, useEffect, useRef, useCallback } from 'react';
import { 
  AttendanceVariant, 
  AttendanceStatus, 
  UserData, 
  LocationData, 
  AttendanceError,
  BaseAttendanceRecord,
  ShiftInfo,
  WorkLocation,
  AttendanceCalculation
} from '../types';
import { getApiService } from '../apiService';
import { getErrorHandler } from '../errorHandler';
import { calculateWorkingHours, formatTime } from '../timeUtils';

export interface AttendanceState {
  // Core attendance data
  currentTime: Date;
  isCheckedIn: boolean;
  checkInTime: string | null;
  checkOutTime: string | null;
  workingHours: AttendanceCalculation | null;
  
  // User and schedule data
  userData: UserData | null;
  currentShift: ShiftInfo | null;
  workLocation: WorkLocation | null;
  
  // Operation states
  isLoading: boolean;
  isOperationInProgress: boolean;
  error: AttendanceError | null;
  
  // Location data
  userLocation: LocationData | null;
  distanceToWorkLocation: number | null;
  
  // Multi-shift support (dokter variant)
  todayRecords: BaseAttendanceRecord[];
  shiftsAvailable: ShiftInfo[];
  maxShiftsReached: boolean;
  
  // Real-time status
  canCheckIn: boolean;
  canCheckOut: boolean;
  statusMessage: string | null;
  validationMessage: string | null;
}

export interface AttendanceActions {
  // Core operations
  checkin: (location?: LocationData) => Promise<boolean>;
  checkout: (location?: LocationData) => Promise<boolean>;
  refreshStatus: () => Promise<void>;
  
  // Data management
  updateLocation: (location: LocationData) => void;
  clearError: () => void;
  
  // State helpers
  getWorkingDuration: () => string;
  getStatusSummary: () => string;
  validateLocation: (location: LocationData) => boolean;
}

export interface UseAttendanceStateOptions {
  variant: AttendanceVariant;
  enableRealTimeUpdates?: boolean;
  updateInterval?: number;
  enableLocationTracking?: boolean;
  enableMultiShift?: boolean;
}

export const useAttendanceState = (
  options: UseAttendanceStateOptions
): [AttendanceState, AttendanceActions] => {
  const {
    variant,
    enableRealTimeUpdates = true,
    updateInterval = 1000,
    enableLocationTracking = true,
    enableMultiShift = variant === 'dokter'
  } = options;

  // Initialize services
  const apiService = getApiService(variant);
  const errorHandler = getErrorHandler(variant);

  // Refs for managing intervals and cleanup
  const updateIntervalRef = useRef<number | null>(null);
  const workingHoursIntervalRef = useRef<number | null>(null);
  const isMountedRef = useRef(true);

  // Core state
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [checkInTime, setCheckInTime] = useState<string | null>(null);
  const [checkOutTime, setCheckOutTime] = useState<string | null>(null);
  const [workingHours, setWorkingHours] = useState<AttendanceCalculation | null>(null);

  // User and schedule state
  const [userData, setUserData] = useState<UserData | null>(null);
  const [currentShift, setCurrentShift] = useState<ShiftInfo | null>(null);
  const [workLocation, setWorkLocation] = useState<WorkLocation | null>(null);

  // Operation states
  const [isLoading, setIsLoading] = useState(false);
  const [isOperationInProgress, setIsOperationInProgress] = useState(false);
  const [error, setError] = useState<AttendanceError | null>(null);

  // Location state
  const [userLocation, setUserLocation] = useState<LocationData | null>(null);
  const [distanceToWorkLocation, setDistanceToWorkLocation] = useState<number | null>(null);

  // Multi-shift state (dokter variant)
  const [todayRecords, setTodayRecords] = useState<BaseAttendanceRecord[]>([]);
  const [shiftsAvailable, setShiftsAvailable] = useState<ShiftInfo[]>([]);
  const [maxShiftsReached, setMaxShiftsReached] = useState(false);

  // Validation state
  const [canCheckIn, setCanCheckIn] = useState(false);
  const [canCheckOut, setCanCheckOut] = useState(false);
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [validationMessage, setValidationMessage] = useState<string | null>(null);

  /**
   * Initialize attendance state by fetching all required data
   */
  const initializeState = useCallback(async () => {
    if (!isMountedRef.current) return;
    
    setIsLoading(true);
    setError(null);

    try {
      // Fetch user data
      const user = await apiService.fetchUserData();
      if (isMountedRef.current) {
        setUserData(user);
      }

      // Fetch attendance status
      const status = await apiService.fetchAttendanceStatus();
      if (isMountedRef.current) {
        updateAttendanceState(status);
      }

      // Fetch schedule data (dokter variant)
      if (variant === 'dokter') {
        try {
          const scheduleData = await apiService.fetchScheduleData();
          if (isMountedRef.current) {
            setCurrentShift(scheduleData.currentShift);
          }
        } catch (scheduleError) {
          console.warn('Schedule data not available:', scheduleError);
        }
      }

      // Fetch work location
      const workLoc = await apiService.fetchWorkLocationStatus();
      if (isMountedRef.current && workLoc) {
        setWorkLocation(workLoc[0]); // Use first work location
      }

      // Fetch multi-shift data (dokter variant)
      if (enableMultiShift && variant === 'dokter') {
        try {
          const multiShiftStatus = await apiService.fetchMultiShiftStatus();
          if (isMountedRef.current) {
            setTodayRecords(multiShiftStatus.todayRecords || []);
            setShiftsAvailable(multiShiftStatus.shiftsAvailable || []);
            setMaxShiftsReached(multiShiftStatus.maxShiftsReached || false);
          }
        } catch (multiShiftError) {
          console.warn('Multi-shift data not available:', multiShiftError);
        }
      }

    } catch (err) {
      if (isMountedRef.current) {
        const processedError = errorHandler.handleError(err, 'initialize state');
        setError(processedError);
      }
    } finally {
      if (isMountedRef.current) {
        setIsLoading(false);
      }
    }
  }, [variant, enableMultiShift, apiService, errorHandler]);

  /**
   * Update attendance state from API response
   */
  const updateAttendanceState = useCallback((status: any) => {
    const attendance = status.attendance || status;
    
    setIsCheckedIn(!!attendance.check_in_time && !attendance.check_out_time);
    setCheckInTime(attendance.check_in_time);
    setCheckOutTime(attendance.check_out_time);
    
    // Calculate working hours if checked in
    if (attendance.check_in_time) {
      const calculation = calculateWorkingHours(
        attendance.check_in_time,
        attendance.check_out_time,
        currentTime
      );
      setWorkingHours(calculation);
    } else {
      setWorkingHours(null);
    }

    // Update validation flags
    updateValidationFlags(attendance);
  }, [currentTime]);

  /**
   * Update validation flags based on current state
   */
  const updateValidationFlags = useCallback((attendance: any) => {
    const now = new Date();
    const isWithinWorkLocation = userLocation && workLocation ? 
      distanceToWorkLocation !== null && distanceToWorkLocation <= (workLocation.radius || 100) : 
      true; // Allow if location not available

    // Check-in validation
    const canCheckInNow = !attendance.check_in_time && isWithinWorkLocation;
    setCanCheckIn(canCheckInNow);

    // Check-out validation
    const canCheckOutNow = !!attendance.check_in_time && !attendance.check_out_time;
    setCanCheckOut(canCheckOutNow);

    // Status messages
    if (!isWithinWorkLocation && userLocation) {
      setValidationMessage(
        `Anda berada ${Math.round(distanceToWorkLocation || 0)}m dari lokasi kerja`
      );
    } else if (attendance.check_in_time && !attendance.check_out_time) {
      setStatusMessage('Sedang bekerja - Jangan lupa check-out');
    } else if (attendance.check_in_time && attendance.check_out_time) {
      setStatusMessage('Presensi hari ini sudah selesai');
    } else {
      setStatusMessage(null);
      setValidationMessage(null);
    }
  }, [userLocation, workLocation, distanceToWorkLocation]);

  /**
   * Refresh attendance status from server
   */
  const refreshStatus = useCallback(async () => {
    if (isOperationInProgress) return;

    try {
      const status = await apiService.fetchAttendanceStatus();
      if (isMountedRef.current) {
        updateAttendanceState(status);
        setError(null);
      }
    } catch (err) {
      if (isMountedRef.current) {
        const processedError = errorHandler.handleError(err, 'refresh status');
        setError(processedError);
      }
    }
  }, [isOperationInProgress, apiService, errorHandler, updateAttendanceState]);

  /**
   * Perform check-in operation
   */
  const checkin = useCallback(async (location?: LocationData): Promise<boolean> => {
    if (isOperationInProgress || !canCheckIn) {
      return false;
    }

    setIsOperationInProgress(true);
    setError(null);

    try {
      const response = await apiService.checkin(location);
      
      if (response.success) {
        // Update state immediately for better UX
        setIsCheckedIn(true);
        setCheckInTime(new Date().toISOString());
        setCheckOutTime(null);
        
        // Refresh full status from server
        await refreshStatus();
        
        return true;
      } else {
        throw new Error(response.message || 'Check-in failed');
      }
    } catch (err) {
      const processedError = errorHandler.handleError(err, 'check-in');
      setError(processedError);
      return false;
    } finally {
      setIsOperationInProgress(false);
    }
  }, [isOperationInProgress, canCheckIn, apiService, errorHandler, refreshStatus]);

  /**
   * Perform check-out operation
   */
  const checkout = useCallback(async (location?: LocationData): Promise<boolean> => {
    if (isOperationInProgress || !canCheckOut) {
      return false;
    }

    setIsOperationInProgress(true);
    setError(null);

    try {
      const response = await apiService.checkout(location);
      
      if (response.success) {
        // Update state immediately for better UX
        setIsCheckedIn(false);
        setCheckOutTime(new Date().toISOString());
        
        // Refresh full status from server
        await refreshStatus();
        
        return true;
      } else {
        throw new Error(response.message || 'Check-out failed');
      }
    } catch (err) {
      const processedError = errorHandler.handleError(err, 'check-out');
      setError(processedError);
      return false;
    } finally {
      setIsOperationInProgress(false);
    }
  }, [isOperationInProgress, canCheckOut, apiService, errorHandler, refreshStatus]);

  /**
   * Update user location and calculate distance
   */
  const updateLocation = useCallback((location: LocationData) => {
    setUserLocation(location);

    // Calculate distance to work location
    if (workLocation && location) {
      const distance = calculateDistance(
        location.lat,
        location.lng,
        workLocation.latitude,
        workLocation.longitude
      );
      setDistanceToWorkLocation(distance);
    }
  }, [workLocation]);

  /**
   * Validate if location is within work area
   */
  const validateLocation = useCallback((location: LocationData): boolean => {
    if (!workLocation) return true; // Allow if no work location set
    
    const distance = calculateDistance(
      location.lat,
      location.lng,
      workLocation.latitude,
      workLocation.longitude
    );
    
    return distance <= (workLocation.radius || 100);
  }, [workLocation]);

  /**
   * Get current working duration as formatted string
   */
  const getWorkingDuration = useCallback((): string => {
    if (!workingHours) return '00:00:00';
    return workingHours.workingHours;
  }, [workingHours]);

  /**
   * Get status summary for display
   */
  const getStatusSummary = useCallback((): string => {
    if (error) return `Error: ${error.message}`;
    if (isOperationInProgress) return 'Memproses...';
    if (isCheckedIn) return 'Sedang Bekerja';
    if (checkOutTime) return 'Selesai Bekerja';
    return 'Belum Check-in';
  }, [error, isOperationInProgress, isCheckedIn, checkOutTime]);

  /**
   * Clear current error
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // Calculate distance helper function
  const calculateDistance = (lat1: number, lng1: number, lat2: number, lng2: number): number => {
    const R = 6371000; // Earth's radius in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  };

  // Initialize state on mount
  useEffect(() => {
    initializeState();
    
    return () => {
      isMountedRef.current = false;
    };
  }, [initializeState]);

  // Real-time clock updates
  useEffect(() => {
    if (!enableRealTimeUpdates) return;

    updateIntervalRef.current = window.setInterval(() => {
      setCurrentTime(new Date());
    }, updateInterval);

    return () => {
      if (updateIntervalRef.current) {
        clearInterval(updateIntervalRef.current);
      }
    };
  }, [enableRealTimeUpdates, updateInterval]);

  // Real-time working hours calculation
  useEffect(() => {
    if (!isCheckedIn || !checkInTime || checkOutTime) return;

    workingHoursIntervalRef.current = window.setInterval(() => {
      const calculation = calculateWorkingHours(checkInTime, null, new Date());
      setWorkingHours(calculation);
    }, 1000);

    return () => {
      if (workingHoursIntervalRef.current) {
        clearInterval(workingHoursIntervalRef.current);
      }
    };
  }, [isCheckedIn, checkInTime, checkOutTime]);

  // Update validation when dependencies change
  useEffect(() => {
    if (checkInTime || checkOutTime) {
      updateValidationFlags({ 
        check_in_time: checkInTime, 
        check_out_time: checkOutTime 
      });
    }
  }, [userLocation, workLocation, distanceToWorkLocation, checkInTime, checkOutTime, updateValidationFlags]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      isMountedRef.current = false;
      if (updateIntervalRef.current) {
        clearInterval(updateIntervalRef.current);
      }
      if (workingHoursIntervalRef.current) {
        clearInterval(workingHoursIntervalRef.current);
      }
    };
  }, []);

  // Compose state object
  const state: AttendanceState = {
    currentTime,
    isCheckedIn,
    checkInTime,
    checkOutTime,
    workingHours,
    userData,
    currentShift,
    workLocation,
    isLoading,
    isOperationInProgress,
    error,
    userLocation,
    distanceToWorkLocation,
    todayRecords,
    shiftsAvailable,
    maxShiftsReached,
    canCheckIn,
    canCheckOut,
    statusMessage,
    validationMessage
  };

  // Compose actions object
  const actions: AttendanceActions = {
    checkin,
    checkout,
    refreshStatus,
    updateLocation,
    clearError,
    getWorkingDuration,
    getStatusSummary,
    validateLocation
  };

  return [state, actions];
};