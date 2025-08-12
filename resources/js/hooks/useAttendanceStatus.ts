// Custom hook for attendance status management

import { useState, useEffect, useCallback, useRef } from 'react';
import { 
  AttendanceData, 
  ScheduleData, 
  UserData, 
  AttendanceRecord,
  LastKnownState 
} from '../utils/dokter/attendanceTypes';
import * as api from '../services/dokter/attendanceApi';
import { getValidationMessage, isWithinShiftWindow } from '../utils/dokter/attendanceHelpers';

export const useAttendanceStatus = () => {
  // Core state
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [attendanceData, setAttendanceData] = useState<AttendanceData>({
    checkInTime: null,
    checkOutTime: null,
    workingHours: '00:00:00',
    overtimeHours: '00:00:00',
    breakTime: '00:00:00',
    location: 'RS. Kediri Medical Center'
  });
  
  const [todayRecords, setTodayRecords] = useState<AttendanceRecord[]>([]);
  const [userData, setUserData] = useState<UserData | null>(null);
  const [scheduleData, setScheduleData] = useState<ScheduleData>({
    todaySchedule: null,
    currentShift: null,
    workLocation: null,
    isOnDuty: false,
    canCheckIn: false,
    canCheckOut: false,
    validationMessage: ''
  });
  
  // Operation control
  const [isOperationInProgress, setIsOperationInProgress] = useState(false);
  const [lastKnownState, setLastKnownState] = useState<LastKnownState | null>(null);
  const pollingIntervalRef = useRef<number | null>(null);
  const serverOffsetRef = useRef<number>(0);
  
  // Load user data
  const loadUserData = useCallback(async () => {
    try {
      const data = await api.fetchUserData();
      
      if (data?.user) {
        setUserData({
          name: data.user.name || 'Dokter',
          email: data.user.email || '',
          role: data.user.role?.name || 'dokter'
        });
      }
    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  }, []);
  
  // Load schedule data
  const loadScheduleData = useCallback(async () => {
    try {
      const data = await api.fetchScheduleData();
      
      if (data) {
        const todaySchedule = data.jadwal_jaga_today || [];
        const currentShift = data.current_shift || (todaySchedule.length > 0 ? todaySchedule[0] : null);
        
        setScheduleData(prev => ({
          ...prev,
          todaySchedule,
          currentShift,
          isOnDuty: todaySchedule.length > 0
        }));
      }
      
      // Also load work location
      try {
        const workLocation = await api.fetchWorkLocationStatus();
        if (workLocation) {
          setScheduleData(prev => ({
            ...prev,
            workLocation
          }));
        }
      } catch (error) {
        console.warn('Failed to load work location:', error);
      }
    } catch (error) {
      console.error('Failed to load schedule:', error);
    }
  }, []);
  
  // Load attendance records
  const loadAttendanceRecords = useCallback(async () => {
    if (isOperationInProgress) {
      console.log('⏸️ Skipping attendance load during operation');
      return;
    }
    
    try {
      const data = await api.fetchAttendanceRecords(true);
      
      if (data) {
        const records = Array.isArray(data.attendances) ? data.attendances : [];
        setTodayRecords(records);
        
        // Update check-in status based on records
        const hasOpenAttendance = records.some((r: AttendanceRecord) => 
          r.time_in && !r.time_out
        );
        setIsCheckedIn(hasOpenAttendance);
        
        // Update attendance data
        const currentRecord = records.find((r: AttendanceRecord) => 
          r.time_in && !r.time_out
        );
        
        if (currentRecord) {
          setAttendanceData(prev => ({
            ...prev,
            checkInTime: currentRecord.time_in || null,
            checkOutTime: currentRecord.time_out || null
          }));
        }
      }
    } catch (error) {
      console.error('Failed to load attendance:', error);
    }
  }, [isOperationInProgress]);
  
  // Validate current status
  const validateCurrentStatus = useCallback(async () => {
    try {
      const serverTime = await api.fetchServerTime();
      const now = serverTime || new Date();
      
      // Store server offset
      if (serverTime) {
        serverOffsetRef.current = serverTime.getTime() - Date.now();
      }
      
      // Determine effective shift
      let effectiveShift = scheduleData.currentShift;
      const openAttendance = todayRecords.find(r => r.time_in && !r.time_out);
      
      if (openAttendance && scheduleData.todaySchedule) {
        const checkedInShift = scheduleData.todaySchedule.find(
          (s: any) => s.id === openAttendance.jadwal_jaga_id
        );
        if (checkedInShift) {
          effectiveShift = checkedInShift;
        }
      }
      
      // Check if on duty
      const isOnDutyToday = !!effectiveShift;
      
      // Check if within shift window
      let isWithinWindow = false;
      if (effectiveShift?.shift_template) {
        const { jam_masuk, jam_pulang } = effectiveShift.shift_template;
        if (jam_masuk && jam_pulang) {
          isWithinWindow = isWithinShiftWindow(now, jam_masuk, jam_pulang, 30);
        }
      }
      
      // Check work location
      const hasWorkLocation = !!scheduleData.workLocation;
      
      // Determine permissions
      const canCheckIn = isOnDutyToday && isWithinWindow && hasWorkLocation && !isCheckedIn;
      const canCheckOut = isCheckedIn && isWithinWindow;
      
      // Get validation message
      const validationMessage = getValidationMessage(
        isOnDutyToday,
        isWithinWindow,
        hasWorkLocation,
        canCheckOut,
        isCheckedIn
      );
      
      // Update schedule data
      setScheduleData(prev => ({
        ...prev,
        currentShift: effectiveShift,
        isOnDuty: isOnDutyToday,
        canCheckIn,
        canCheckOut,
        validationMessage
      }));
      
    } catch (error) {
      console.error('Validation error:', error);
    }
  }, [scheduleData, todayRecords, isCheckedIn]);
  
  // Initialize on mount
  useEffect(() => {
    loadUserData();
    loadScheduleData();
    loadAttendanceRecords();
  }, []);
  
  // Set up polling
  useEffect(() => {
    const startPolling = () => {
      pollingIntervalRef.current = window.setInterval(() => {
        if (!isOperationInProgress) {
          loadAttendanceRecords();
          validateCurrentStatus();
        }
      }, 30000); // 30 seconds
    };
    
    startPolling();
    
    return () => {
      if (pollingIntervalRef.current) {
        clearInterval(pollingIntervalRef.current);
      }
    };
  }, [isOperationInProgress]);
  
  // Validate when data changes
  useEffect(() => {
    if (scheduleData.todaySchedule && scheduleData.workLocation !== undefined) {
      validateCurrentStatus();
    }
  }, [scheduleData.todaySchedule, scheduleData.workLocation, todayRecords]);
  
  return {
    // State
    isCheckedIn,
    attendanceData,
    todayRecords,
    userData,
    scheduleData,
    isOperationInProgress,
    serverOffsetRef,
    
    // Actions
    setIsCheckedIn,
    setAttendanceData,
    setTodayRecords,
    setScheduleData,
    setIsOperationInProgress,
    setLastKnownState,
    
    // Functions
    loadUserData,
    loadScheduleData,
    loadAttendanceRecords,
    validateCurrentStatus
  };
};