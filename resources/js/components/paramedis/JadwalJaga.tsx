import React, { useState, useEffect, useRef, useCallback, useMemo, memo } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { HoursFormatter } from '../../utils/hoursFormatter';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Calendar, Clock, MapPin, Plus, ChevronRight, Activity, Edit, X, AlertCircle, CheckCircle, Loader2, Zap, Trophy, Hourglass, LogIn, LogOut } from 'lucide-react';
import { useScreenReader } from '../../hooks/useScreenReader';
import { useFocusManagement } from '../../hooks/useFocusManagement';

interface JadwalItem {
  id: string;
  tanggal: string;
  waktu: string;
  lokasi: string;
  jenis: 'pagi' | 'siang' | 'malam';
  status: 'scheduled' | 'completed' | 'missed';
  full_date: string;
  day_name: string;
  shift_template?: {
    id: number;
    nama_shift: string;
    jam_masuk: string;
    jam_pulang: string;
  };
  // Enhanced: attendance fields
  attendance?: {
    check_in_time?: string;
    check_out_time?: string;
    status?: 'not_started' | 'checked_in' | 'checked_out' | 'completed' | 'expired';
  };
  peran: string;
  employee_name: string;
}

// Performance monitoring HOC
const withPerformanceMonitoring = <P extends object>(
  Component: React.ComponentType<P>,
  componentName: string
) => {
  return memo((props: P) => {
    const startTime = useRef(performance.now());
    
    useEffect(() => {
      const endTime = performance.now();
      const renderTime = endTime - startTime.current;
      
      if (renderTime > 100) {
        console.warn(`🐌 Slow render detected: ${componentName} took ${renderTime.toFixed(2)}ms`);
      }
      
      // Update performance metrics
      if (componentName === 'JadwalJaga') {
        console.log(`⚡ ${componentName} render time: ${renderTime.toFixed(2)}ms`);
      }
    });

    return <Component {...props} />;
  });
};

export const JadwalJaga = memo(function JadwalJaga() {
  const [jadwal, setJadwal] = useState<JadwalItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [lastFetch, setLastFetch] = useState<number>(0);
  
  // Performance monitoring state
  const [performanceMetrics, setPerformanceMetrics] = useState({
    renderTime: 0,
    apiResponseTime: 0,
    cacheHits: 0,
    totalRequests: 0,
    memoryUsage: 0
  });
  const [isIpad, setIsIpad] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [orientation, setOrientation] = useState<'portrait' | 'landscape'>('portrait');
  
  // Accessibility hooks
  const { announce } = useScreenReader();
  const { manageFocus, createFocusTrap } = useFocusManagement();
  
  // Refs for accessibility
  const mainRef = useRef<HTMLDivElement>(null);
  const errorRef = useRef<HTMLDivElement>(null);
  
  // Mobile touch optimization handlers
  const handleTouchStart = useCallback((e: React.TouchEvent, jadwalId: string) => {
    if (isMobile) {
      // Haptic feedback on supported devices
      if ('vibrate' in navigator) {
        navigator.vibrate(50);
      }
      
      // Add visual feedback
      const target = e.currentTarget as HTMLElement;
      target.style.transform = 'scale(0.98)';
      target.style.transition = 'transform 0.1s ease-out';
    }
  }, [isMobile]);

  const handleTouchEnd = useCallback((e: React.TouchEvent) => {
    if (isMobile) {
      const target = e.currentTarget as HTMLElement;
      setTimeout(() => {
        target.style.transform = 'scale(1)';
      }, 100);
    }
  }, [isMobile]);

  // Responsive breakpoint optimization
  const getResponsiveClasses = useCallback(() => {
    const baseClasses = "space-y-4";
    
    if (isIpad && orientation === 'landscape') {
      return `${baseClasses} lg:space-y-6 xl:space-y-8`;
    } else if (isMobile) {
      return `${baseClasses} space-y-3`;
    } else {
      return `${baseClasses} md:space-y-6 lg:space-y-8`;
    }
  }, [isIpad, isMobile, orientation]);

  const getCardClasses = useCallback(() => {
    let classes = "shadow-lg hover:shadow-xl transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced group-hover:bg-white/90 dark:group-hover:bg-gray-900/90 overflow-hidden";
    
    if (isMobile) {
      classes += " active:scale-95";
    } else {
      classes += " hover:scale-[1.02]";
    }
    
    return classes;
  }, [isMobile]);

  // Enhanced: Get current time for schedule status
  const getCurrentTime = () => new Date();

  // Enhanced: Determine schedule status based on current time and schedule
  const getScheduleStatus = (jadwalItem: JadwalItem): 'upcoming' | 'active' | 'expired' => {
    const now = getCurrentTime();
    const scheduleDate = new Date(jadwalItem.full_date || jadwalItem.tanggal);
    const todayString = now.toISOString().split('T')[0];
    const scheduleString = scheduleDate.toISOString().split('T')[0];
    
    // Parse shift times
    const [startHour, startMinute] = jadwalItem.shift_template?.jam_masuk.split(':').map(Number) || [8, 0];
    const [endHour, endMinute] = jadwalItem.shift_template?.jam_pulang.split(':').map(Number) || [16, 0];
    
    // Create shift start and end times
    const shiftStart = new Date(scheduleDate);
    shiftStart.setHours(startHour, startMinute, 0, 0);
    
    let shiftEnd = new Date(scheduleDate);
    shiftEnd.setHours(endHour, endMinute, 0, 0);
    
    // Handle overnight shifts (end time is next day)
    if (endHour < startHour) {
      shiftEnd = new Date(scheduleDate);
      shiftEnd.setDate(shiftEnd.getDate() + 1);
      shiftEnd.setHours(endHour, endMinute, 0, 0);
    }
    
    // Check if schedule is today and within time range
    if (scheduleString === todayString) {
      if (now >= shiftStart && now <= shiftEnd) {
        return 'active';
      } else if (now > shiftEnd) {
        return 'expired';
      } else {
        return 'upcoming';
      }
    } else if (scheduleDate < now) {
      return 'expired';
    } else {
      return 'upcoming';
    }
  };

  // Enhanced: Format attendance times for display with improved error handling
  const formatAttendanceTime = (timeString: string | undefined): string => {
    if (!timeString) return '--:--';
    
    try {
      // Handle both full datetime and time-only strings
      let dateToFormat;
      
      if (timeString.includes('T') || timeString.includes(' ')) {
        // Full datetime string
        dateToFormat = new Date(timeString);
      } else if (timeString.includes(':')) {
        // Time-only string (HH:MM or HH:MM:SS)
        dateToFormat = new Date(`2025-01-01 ${timeString}`);
      } else {
        // Fallback
        dateToFormat = new Date(timeString);
      }
      
      // Validate the date
      if (isNaN(dateToFormat.getTime())) {
        console.warn('⚠️ Invalid date format:', timeString);
        return '--:--';
      }
      
      const formatted = dateToFormat.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
      });
      
      return formatted;
    } catch (error) {
      console.error('❌ Time formatting error:', { input: timeString, error });
      return '--:--';
    }
  };

  // Intelligent cache implementation with TTL
  const cacheMap = useMemo(() => new Map(), []);
  const getCachedData = useCallback((key: string) => {
    const cached = cacheMap.get(key);
    if (cached && cached.timestamp && (Date.now() - cached.timestamp < 300000)) { // 5 min TTL
      setPerformanceMetrics(prev => ({
        ...prev,
        cacheHits: prev.cacheHits + 1
      }));
      return cached.data;
    }
    cacheMap.delete(key);
    return null;
  }, [cacheMap]);

  const setCachedData = useCallback((key: string, data: any) => {
    cacheMap.set(key, {
      data,
      timestamp: Date.now()
    });
    // Cleanup old cache entries
    if (cacheMap.size > 50) {
      const firstKey = cacheMap.keys().next().value;
      cacheMap.delete(firstKey);
    }
  }, [cacheMap]);

  // Enhanced API Data Fetching function with attendance data
  const fetchSchedulesWithAttendance = useCallback(async (isRefresh = false) => {
    const startTime = performance.now();
    performance.mark('fetch-start');
    
    console.log(`ParamedisJadwal: ${isRefresh ? 'Refreshing' : 'Starting'} API fetch with attendance data at ${new Date().toLocaleTimeString()}`);
    
    try {
      if (!isRefresh) setLoading(true);
      setError(null);
      announce('Memuat jadwal jaga dengan data presensi...', 'polite');
      
      // Check cache first (only for non-refresh requests)
      if (!isRefresh) {
        const cacheKey = 'paramedis-schedules-with-attendance';
        const cachedData = getCachedData(cacheKey);
        if (cachedData) {
          setJadwal(cachedData.jadwal);
          setPerformanceMetrics(prev => ({
            ...prev,
            cacheHits: prev.cacheHits + 1,
            apiResponseTime: 0 // Cache hit
          }));
          return;
        }
      }
      
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      
      // Enhanced: Use real API endpoint with attendance data
      console.log('Making API call to /api/v2/dashboards/paramedis/jadwal-jaga with attendance data');
      const cacheBuster = isRefresh ? `?refresh=${Date.now()}&include_attendance=true` : '?include_attendance=true';
      
      const response = await fetch(`/api/v2/dashboards/paramedis/jadwal-jaga${cacheBuster}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin' // Important for web session auth
      });

      console.log('Paramedis API Response:', { status: response.status, ok: response.ok });

      if (!response.ok) {
        if (response.status === 401) {
          throw new Error('Authentication required. Please login again.');
        } else if (response.status === 404) {
          throw new Error('API endpoint not found. Please check configuration.');
        } else {
          throw new Error(`API Error: ${response.status} - ${response.statusText}`);
        }
      }

      const data = await response.json();
      console.log('Enhanced Paramedis API Data received:', { 
        hasData: !!data.data,
        calendarEventsCount: data.data?.calendar_events?.length || 0,
        weeklyScheduleCount: data.data?.weekly_schedule?.length || 0,
        hasScheduleStats: !!data.data?.schedule_stats,
        hasAttendanceData: !!data.data?.attendance_records,
        success: data.success,
        message: data.message
      });
      
      // Check if API response is successful
      if (!data.success) {
        throw new Error(data.message || 'API returned unsuccessful response');
      }
      
      // Enhanced: Combine schedule data with attendance data
      const weeklySchedules = data.data?.weekly_schedule || [];
      const calendarEvents = data.data?.calendar_events || [];
      const attendanceRecords = data.data?.attendance_records || [];
      
      // DEBUG: Log attendance records
      console.log('📊 Paramedis Attendance Records Debug:', {
        attendanceCount: attendanceRecords.length,
        attendanceRecords: attendanceRecords,
        hasAttendanceData: attendanceRecords.length > 0
      });
      
      // Create attendance map for quick lookup
      const attendanceMap = new Map();
      attendanceRecords.forEach((record: any) => {
        if (record.jadwal_jaga_id) {
          attendanceMap.set(record.jadwal_jaga_id, record);
          console.log('📍 Mapped paramedis attendance:', {
            jadwalJagaId: record.jadwal_jaga_id,
            timeIn: record.time_in || record.check_in_time,
            timeOut: record.time_out || record.check_out_time,
            rawRecord: record
          });
        }
      });
      
      // Combine and deduplicate schedules
      const combinedSchedules = [...weeklySchedules, ...calendarEvents];
      const seenIds = new Set();
      const apiSchedules = combinedSchedules.filter(schedule => {
        const id = schedule.id;
        if (seenIds.has(id)) {
          return false; // Skip duplicate
        }
        seenIds.add(id);
        return true;
      });
      
      console.log('Enhanced paramedis schedule sources:', {
        weeklySchedulesCount: weeklySchedules.length,
        calendarEventsCount: calendarEvents.length,
        attendanceRecordsCount: attendanceRecords.length,
        totalCombined: combinedSchedules.length,
        afterDeduplication: apiSchedules.length,
        duplicatesRemoved: combinedSchedules.length - apiSchedules.length
      });
      
      // Enhanced: Transform API data with attendance information
      const transformedJadwal = transformApiDataWithAttendance(apiSchedules, attendanceMap);
      
      // DEBUG: Log jadwal with attendance
      console.log('🎯 Paramedis Jadwal with Attendance Debug:', {
        totalJadwal: transformedJadwal.length,
        jadwalWithAttendance: transformedJadwal.filter(j => j.attendance).length,
        attendanceDetails: transformedJadwal.map(j => ({
          id: j.id,
          tanggal: j.tanggal,
          hasAttendance: !!j.attendance,
          checkIn: j.attendance?.check_in_time,
          checkOut: j.attendance?.check_out_time,
          status: getScheduleStatus(j),
          willShowAttendanceTime: !!(j.attendance && (j.attendance.check_in_time || j.attendance.check_out_time)),
          formattedCheckIn: j.attendance?.check_in_time ? formatAttendanceTime(j.attendance.check_in_time) : 'N/A',
          formattedCheckOut: j.attendance?.check_out_time ? formatAttendanceTime(j.attendance.check_out_time) : 'N/A'
        }))
      });
      
      if (transformedJadwal.length === 0) {
        console.warn('No paramedis schedules found - checking if this is expected');
        setError('No schedules assigned. Please contact administrator for duty assignments.');
        setJadwal([]);
      } else {
        console.log(`Using real paramedis API schedule data - ${transformedJadwal.length} schedules loaded`);
        setJadwal(transformedJadwal);
        announce(`Jadwal berhasil dimuat dengan data presensi. Ditemukan ${transformedJadwal.length} jadwal jaga.`, 'polite');
        
        // Cache successful API response
        const cacheKey = 'paramedis-schedules-with-attendance';
        setCachedData(cacheKey, { jadwal: transformedJadwal });
      }
      
      // Update performance metrics
      const endTime = performance.now();
      const apiResponseTime = endTime - startTime;
      setPerformanceMetrics(prev => ({
        ...prev,
        apiResponseTime,
        totalRequests: prev.totalRequests + 1,
        memoryUsage: performance.memory ? Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) : 0
      }));
      
      console.log('ParamedisJadwal: Data loaded successfully with attendance tracking');
    } catch (err) {
      console.error('Failed to fetch paramedis jadwal with attendance:', err);
      
      const errorMessage = err instanceof Error ? err.message : 'Unknown error occurred';
      setError(`Failed to load schedule data: ${errorMessage}`);
      
      // Fallback to basic API if enhanced fails
      try {
        console.log('Falling back to basic API...');
        const response = await fetch('/test-paramedis-schedules-api', {
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          }
        });
        
        if (response.ok) {
          const schedules = await response.json();
          const transformedSchedules = schedules?.map((schedule: any, index: number) => ({
            id: schedule.id || `fallback-${index}`,
            tanggal: schedule.tanggal || new Date().toLocaleDateString('id-ID'),
            waktu: schedule.waktu || '08:00 - 16:00',
            lokasi: schedule.lokasi || 'Unit Kerja',
            jenis: schedule.jenis || 'pagi',
            status: schedule.status || 'scheduled',
            full_date: schedule.full_date || new Date().toISOString(),
            day_name: schedule.day_name || new Date().toLocaleDateString('id-ID', { weekday: 'long' }),
            shift_template: schedule.shift_template || {
              id: index + 1,
              nama_shift: schedule.jenis || 'Pagi',
              jam_masuk: '08:00',
              jam_pulang: '16:00'
            },
            peran: schedule.peran || 'Paramedis',
            employee_name: schedule.employee_name || 'Paramedis Officer'
          })) || [];
          
          setJadwal(transformedSchedules);
          console.log('✅ Fallback paramedis schedules loaded:', transformedSchedules.length, 'items');
          announce(`Jadwal fallback berhasil dimuat. Ditemukan ${transformedSchedules.length} jadwal jaga.`, 'polite');
        } else {
          setJadwal([]);
        }
      } catch (fallbackErr) {
        console.error('Fallback API also failed:', fallbackErr);
        setJadwal([]);
      }
    } finally {
      setLoading(false);
      setLastFetch(Date.now());
    }
  }, [announce, getCachedData, setCachedData, getScheduleStatus, formatAttendanceTime]);

  // Enhanced transform API data with attendance information
  const transformApiDataWithAttendance = (apiSchedules: any[], attendanceMap: Map<any, any>): JadwalItem[] => {
    console.log('🔄 Starting enhanced paramedis transform with attendance data');
    
    const transformedJadwal = apiSchedules.map((schedule, index) => {
      // Get attendance record using jadwal_jaga_id
      const attendanceRecord = attendanceMap.get(schedule.id);
      
      // DEBUG: Log the lookup process
      console.log(`🔍 Paramedis Attendance Lookup for Schedule ${schedule.id}:`, {
        scheduleId: schedule.id,
        attendanceMapKeys: Array.from(attendanceMap.keys()),
        attendanceMapSize: attendanceMap.size,
        foundAttendanceRecord: !!attendanceRecord,
        attendanceRecord: attendanceRecord
      });
      
      // Handle both calendar_events and weekly_schedule formats
      const isCalendarEvent = schedule.start && schedule.title;
      const isWeeklySchedule = schedule.tanggal_jaga || schedule.shift_template;
      
      let jadwalItem: JadwalItem;
      
      if (isCalendarEvent) {
        const shiftInfo = schedule.shift_info || {};
        
        const formatTime = (time: string) => {
          if (!time) return time;
          if (time.includes('T')) {
            const timePart = time.split('T')[1];
            if (timePart) {
              return timePart.split(':').slice(0, 2).join(':');
            }
          }
          return time.split(':').slice(0, 2).join(':');
        };
        
        const formattedJamMasuk = formatTime(shiftInfo.jam_masuk) || '08:00';
        const formattedJamPulang = formatTime(shiftInfo.jam_pulang) || '16:00';
        const shiftTime = `${formattedJamMasuk} - ${formattedJamPulang}`;
        
        jadwalItem = {
          id: schedule.id || `cal-${index + 1}`,
          tanggal: new Date(schedule.start).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
          waktu: shiftTime,
          lokasi: shiftInfo.unit_kerja || schedule.description || 'Unit Kerja',
          jenis: (shiftInfo.nama_shift?.toLowerCase().includes('malam') ? 'malam' : 
                 shiftInfo.nama_shift?.toLowerCase().includes('siang') ? 'siang' : 'pagi') as 'pagi' | 'siang' | 'malam',
          status: 'scheduled' as const,
          full_date: schedule.start,
          day_name: new Date(schedule.start).toLocaleDateString('id-ID', { weekday: 'long' }),
          shift_template: {
            id: shiftInfo.id || index + 1,
            nama_shift: shiftInfo.nama_shift || 'Pagi',
            jam_masuk: formatTime(shiftInfo.jam_masuk) || '08:00',
            jam_pulang: formatTime(shiftInfo.jam_pulang) || '16:00'
          },
          peran: shiftInfo.peran || 'Paramedis',
          employee_name: shiftInfo.employee_name || 'Paramedis Officer',
          attendance: attendanceRecord ? {
            check_in_time: attendanceRecord.time_in || attendanceRecord.check_in_time,
            check_out_time: attendanceRecord.time_out || attendanceRecord.check_out_time,
            status: attendanceRecord.status || 'not_started'
          } : undefined
        };
      } else if (isWeeklySchedule) {
        const shiftTemplate = schedule.shift_template || {};
        const scheduleDate = schedule.tanggal_jaga || schedule.date;
        
        const formatTime = (time: string) => {
          if (!time) return time;
          if (time.includes('T')) {
            const timePart = time.split('T')[1];
            if (timePart) {
              return timePart.split(':').slice(0, 2).join(':');
            }
          }
          return time.split(':').slice(0, 2).join(':');
        };
        
        const formattedJamMasuk = formatTime(shiftTemplate.jam_masuk) || '08:00';
        const formattedJamPulang = formatTime(shiftTemplate.jam_pulang) || '16:00';
        const shiftTime = `${formattedJamMasuk} - ${formattedJamPulang}`;
        
        jadwalItem = {
          id: schedule.id || `weekly-${index + 1}`,
          tanggal: new Date(scheduleDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
          waktu: shiftTime,
          lokasi: schedule.unit_kerja || 'Unit Kerja',
          jenis: (shiftTemplate.nama_shift?.toLowerCase().includes('malam') ? 'malam' : 
                 shiftTemplate.nama_shift?.toLowerCase().includes('siang') ? 'siang' : 'pagi') as 'pagi' | 'siang' | 'malam',
          status: 'scheduled' as const,
          full_date: scheduleDate,
          day_name: new Date(scheduleDate).toLocaleDateString('id-ID', { weekday: 'long' }),
          shift_template: {
            id: shiftTemplate.id || index + 1,
            nama_shift: shiftTemplate.nama_shift || 'Pagi',
            jam_masuk: formatTime(shiftTemplate.jam_masuk) || '08:00',
            jam_pulang: formatTime(shiftTemplate.jam_pulang) || '16:00'
          },
          peran: schedule.peran || 'Paramedis',
          employee_name: schedule.employee_name || 'Paramedis Officer',
          attendance: attendanceRecord ? {
            check_in_time: attendanceRecord.time_in || attendanceRecord.check_in_time,
            check_out_time: attendanceRecord.time_out || attendanceRecord.check_out_time,
            status: attendanceRecord.status || 'not_started'
          } : undefined
        };
      } else {
        // Fallback
        jadwalItem = {
          id: schedule.id || `fallback-${index + 1}`,
          tanggal: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
          waktu: '08:00 - 16:00',
          lokasi: 'Unit Kerja',
          jenis: 'pagi' as const,
          status: 'scheduled' as const,
          full_date: new Date().toISOString(),
          day_name: new Date().toLocaleDateString('id-ID', { weekday: 'long' }),
          shift_template: {
            id: index + 1,
            nama_shift: 'Pagi',
            jam_masuk: '08:00',
            jam_pulang: '16:00'
          },
          peran: 'Paramedis',
          employee_name: 'Paramedis Officer',
          attendance: attendanceRecord ? {
            check_in_time: attendanceRecord.time_in || attendanceRecord.check_in_time,
            check_out_time: attendanceRecord.time_out || attendanceRecord.check_out_time,
            status: attendanceRecord.status || 'not_started'
          } : undefined
        };
      }
      
      return jadwalItem;
    });
    
    console.log('🎉 FINAL PARAMEDIS JADWAL SUMMARY:', {
      totalJadwal: transformedJadwal.length,
      jadwalWithAttendance: transformedJadwal.filter(j => j.attendance).length,
      attendanceSummary: transformedJadwal
        .filter(j => j.attendance)
        .map(j => ({
          jadwalId: j.id,
          tanggal: j.tanggal,
          attendanceTimes: j.attendance && (j.attendance.check_in_time && j.attendance.check_out_time) 
            ? `${formatAttendanceTime(j.attendance.check_in_time)} - ${formatAttendanceTime(j.attendance.check_out_time)}`
            : 'Incomplete',
          rawCheckIn: j.attendance?.check_in_time,
          rawCheckOut: j.attendance?.check_out_time
        }))
    });
    
    return transformedJadwal;
  };

  // Refresh function for external use
  const refreshSchedules = useCallback(async () => {
    await fetchSchedulesWithAttendance(true);
  }, [fetchSchedulesWithAttendance]);

  useEffect(() => {
    fetchSchedulesWithAttendance();
  }, [fetchSchedulesWithAttendance]);

  const handleEditSchedule = (id: string) => {
    console.log('Edit schedule:', id);
    announce('Membuka editor jadwal', 'polite');
    // Add edit functionality here
  };

  const handleCancelSchedule = (id: string) => {
    console.log('Cancel schedule:', id);
    const scheduleName = jadwal.find(item => item.id === id)?.tanggal || 'jadwal';
    setJadwal(prev => prev.map(item => 
      item.id === id ? { ...item, status: 'missed' as const } : item
    ));
    announce(`Jadwal ${scheduleName} telah dibatalkan`, 'polite');
  };

  // Enhanced retry function with accessibility
  const handleRetry = () => {
    announce('Mencoba memuat ulang jadwal...', 'polite');
    window.location.reload();
  };

  // Enhanced gaming-style status badge configuration with attendance awareness
  const getStatusBadgeConfig = (scheduleStatus: 'upcoming' | 'active' | 'expired', jadwalItem: JadwalItem) => {
    const checkInTime = jadwalItem.attendance?.check_in_time;
    const checkOutTime = jadwalItem.attendance?.check_out_time;
    
    switch (scheduleStatus) {
      case 'active':
        if (checkInTime && checkOutTime) {
          return {
            text: 'COMPLETED',
            icon: CheckCircle,
            gradient: 'from-emerald-500 via-green-500 to-teal-500',
            textColor: 'text-emerald-100',
            borderColor: 'border-emerald-400/50',
            glowColor: 'shadow-emerald-500/30',
            bgGlow: 'from-emerald-500/20 to-green-500/20',
            pulse: 'animate-pulse'
          };
        } else if (checkInTime) {
          return {
            text: 'ACTIVE',
            icon: Activity,
            gradient: 'from-cyan-500 via-blue-500 to-indigo-500',
            textColor: 'text-cyan-100',
            borderColor: 'border-cyan-400/50',
            glowColor: 'shadow-cyan-500/30',
            bgGlow: 'from-cyan-500/20 to-blue-500/20',
            pulse: 'animate-pulse'
          };
        } else {
          return {
            text: 'READY',
            icon: Zap,
            gradient: 'from-yellow-500 via-amber-500 to-orange-500',
            textColor: 'text-yellow-100',
            borderColor: 'border-yellow-400/50',
            glowColor: 'shadow-yellow-500/30',
            bgGlow: 'from-yellow-500/20 to-amber-500/20',
            pulse: 'animate-bounce'
          };
        }
      case 'expired':
        if (checkInTime && checkOutTime) {
          return {
            text: 'COMPLETED',
            icon: Trophy,
            gradient: 'from-emerald-500 via-green-500 to-teal-500',
            textColor: 'text-emerald-100',
            borderColor: 'border-emerald-400/50',
            glowColor: 'shadow-emerald-500/30',
            bgGlow: 'from-emerald-500/20 to-green-500/20',
            pulse: ''
          };
        } else {
          return {
            text: 'EXPIRED',
            icon: Hourglass,
            gradient: 'from-red-500 via-rose-500 to-pink-500',
            textColor: 'text-red-100',
            borderColor: 'border-red-400/50',
            glowColor: 'shadow-red-500/30',
            bgGlow: 'from-red-500/20 to-rose-500/20',
            pulse: ''
          };
        }
      case 'upcoming':
      default:
        return {
          text: 'UPCOMING',
          icon: Clock,
          gradient: 'from-purple-500 via-indigo-500 to-blue-500',
          textColor: 'text-purple-100',
          borderColor: 'border-purple-400/50',
          glowColor: 'shadow-purple-500/30',
          bgGlow: 'from-purple-500/20 to-indigo-500/20',
          pulse: 'animate-pulse'
        };
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'scheduled': return 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-700';
      case 'completed': return 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
      case 'missed': return 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700';
    }
  };

  const getShiftColor = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return 'bg-gradient-to-r from-yellow-400 to-yellow-500 dark:from-yellow-500 dark:to-yellow-600 text-white';
      case 'siang': return 'bg-gradient-to-r from-orange-400 to-orange-500 dark:from-orange-500 dark:to-orange-600 text-white';
      case 'malam': return 'bg-gradient-to-r from-purple-400 to-purple-500 dark:from-purple-500 dark:to-purple-600 text-white';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const formatTanggal = (tanggal: string) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const container = {
    hidden: { opacity: 0 },
    show: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1
      }
    }
  };

  const item = {
    hidden: { opacity: 0, y: 20 },
    show: { opacity: 1, y: 0 }
  };

  // Memoized stats calculation for performance optimization
  const scheduleStats = useMemo(() => {
    performance.mark('stats-calculation-start');
    
    const upcomingCount = jadwal.filter(item => getScheduleStatus(item) === 'upcoming').length;
    const activeCount = jadwal.filter(item => getScheduleStatus(item) === 'active').length;
    const completedCount = jadwal.filter(item => {
      const status = getScheduleStatus(item);
      const badgeConfig = getStatusBadgeConfig(status, item);
      return badgeConfig.text === 'COMPLETED';
    }).length;
    const expiredCount = jadwal.filter(item => {
      const status = getScheduleStatus(item);
      const badgeConfig = getStatusBadgeConfig(status, item);
      return badgeConfig.text === 'EXPIRED';
    }).length;
    
    performance.mark('stats-calculation-end');
    performance.measure('stats-calculation-duration', 'stats-calculation-start', 'stats-calculation-end');
    
    return { upcomingCount, activeCount, completedCount, expiredCount };
  }, [jadwal, getScheduleStatus, getStatusBadgeConfig]);

  const { upcomingCount, activeCount, completedCount, expiredCount } = scheduleStats;

  return (
    <div 
      ref={mainRef}
      className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white"
      role="application"
      aria-label="Paramedis Schedule Management Application"
    >
      {/* Skip Links for Keyboard Navigation */}
      <div className="sr-only">
        <a 
          href="#main-content" 
          className="skip-link focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded focus-outline"
        >
          Skip to main content
        </a>
        <a 
          href="#schedule-list" 
          className="skip-link focus:not-sr-only focus:absolute focus:top-2 focus:left-32 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded focus-outline"
        >
          Skip to schedule list
        </a>
        <a 
          href="#add-schedule" 
          className="skip-link focus:not-sr-only focus:absolute focus:top-2 right-2 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded focus-outline"
        >
          Skip to add schedule
        </a>
      </div>

      {/* Screen Reader Announcements */}
      <div 
        id="sr-announcements" 
        className="sr-only" 
        aria-live="polite" 
        aria-atomic="true"
      ></div>
      
      <div 
        id="sr-loading" 
        className="sr-only" 
        aria-live="assertive" 
        aria-atomic="true"
      >
        {loading && "Loading schedule data, please wait..."}
      </div>

      {/* Dynamic Floating Background Elements */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-20 bg-blue-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '10%', width: '30vw', maxWidth: '400px', height: '30vw', maxHeight: '400px' }}></div>
        <div className="absolute top-60 bg-purple-500 bg-opacity-5 rounded-full blur-2xl animate-bounce" style={{ right: '5%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }}></div>
        <div className="absolute bottom-80 bg-pink-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '15%', width: '28vw', maxWidth: '380px', height: '28vw', maxHeight: '380px' }}></div>
      </div>

      <motion.div 
        variants={container}
        initial="hidden"
        animate="show"
        className={`${getResponsiveClasses()} theme-transition ${isMobile ? 'p-3' : 'p-4'}`}
        role="main"
        id="main-content"
        aria-label="Paramedis schedule dashboard"
      >
      {/* Header Section */}
      <header role="banner" aria-label="Schedule management header">
        <motion.div variants={item}>
          <Card className="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-xl card-enhanced">
            <CardContent className="p-6">
              <div className="flex items-center justify-between text-white">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center" aria-hidden="true">
                    <Calendar className="w-6 h-6" />
                  </div>
                  <div>
                    <h1 className="text-xl font-semibold text-white text-heading-mobile">Jadwal Jaga Paramedis</h1>
                    <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly" id="page-description">
                      Kelola jadwal kerja dan shift paramedis Anda dengan data presensi real-time
                    </p>
                    {lastFetch > 0 && (
                      <div className="text-xs text-blue-200/80 mt-1 space-y-1">
                        <div>📊 Data terakhir diperbarui: {new Date(lastFetch).toLocaleTimeString('id-ID')}</div>
                        {performanceMetrics.totalRequests > 0 && (
                          <div className="flex items-center space-x-2 text-xs">
                            <span>⚡ API: {performanceMetrics.apiResponseTime.toFixed(0)}ms</span>
                            <span>🎯 Cache: {((performanceMetrics.cacheHits / performanceMetrics.totalRequests) * 100).toFixed(0)}%</span>
                            {performanceMetrics.memoryUsage > 0 && (
                              <span>💾 Memory: {performanceMetrics.memoryUsage}MB</span>
                            )}
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                </div>
                <motion.div
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                >
                  <div className="flex items-center gap-2">
                    {/* Refresh Button */}
                    <Button 
                      size="sm" 
                      onClick={refreshSchedules}
                      disabled={loading}
                      className="bg-white/20 dark:bg-white/25 hover:bg-white/30 dark:hover:bg-white/35 border-white/30 dark:border-white/40 text-white gap-2 backdrop-blur-sm transition-colors duration-300 btn-primary-accessible focus-outline touch-target"
                      aria-label="Refresh jadwal dan data presensi"
                      title="Refresh data jadwal dan presensi"
                    >
                      <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} aria-hidden="true" />
                      {loading ? 'Refresh...' : 'Refresh'}
                    </Button>
                    
                    {/* Add Button */}
                    <Button 
                      size="sm" 
                      className="bg-white/20 dark:bg-white/25 hover:bg-white/30 dark:hover:bg-white/35 border-white/30 dark:border-white/40 text-white gap-2 backdrop-blur-sm transition-colors duration-300 btn-primary-accessible focus-outline touch-target"
                      aria-label="Tambah jadwal baru"
                      aria-describedby="page-description"
                    >
                      <Plus className="w-4 h-4" aria-hidden="true" />
                      Tambah
                    </Button>
                  </div>
                </motion.div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </header>
      
      {/* Error Display */}
      {error && (
        <div className="p-4">
          <div className="bg-red-600/20 hover:bg-red-600/40 border border-red-400/30 text-red-300 px-4 py-3 rounded-xl backdrop-blur-sm flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <AlertCircle className="w-5 h-5" aria-hidden="true" />
              <span>{error}</span>
            </div>
            <Button 
              size="sm" 
              variant="ghost"
              onClick={refreshSchedules}
              className="text-red-300 hover:text-white hover:bg-red-500/20"
              aria-label="Coba muat ulang data"
            >
              Coba Lagi
            </Button>
          </div>
        </div>
      )}

      {/* Gaming-Style Stats Dashboard */}
      <section role="region" aria-labelledby="stats-heading">
        <h2 id="stats-heading" className="sr-only">Ringkasan statistik jadwal</h2>
        <motion.div variants={item} className="grid grid-cols-3 gap-4 mb-6">
          {/* Total Scheduled */}
          <Card 
            className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-purple-400/20 hover:border-purple-400/40 transition-all duration-300 card-enhanced group"
            role="group"
            aria-labelledby="scheduled-label"
          >
            <CardContent className="p-4 text-center">
              <div className="flex items-center justify-between mb-2">
                <Activity className="w-5 h-5 text-purple-400" aria-hidden="true" />
                <div className="w-4 h-4 bg-purple-300/50 rounded-full animate-pulse" aria-hidden="true"></div>
              </div>
              <div className="text-2xl font-bold text-purple-400" aria-describedby="upcoming-label">
                {upcomingCount}
              </div>
              <div id="upcoming-label" className="text-xs text-purple-300/80">
                Upcoming Shifts
              </div>
            </CardContent>
          </Card>
          
          {/* Completed */}
          <Card 
            className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-green-400/20 hover:border-green-400/40 transition-all duration-300 card-enhanced group"
            role="group"
            aria-labelledby="completed-label"
          >
            <CardContent className="p-4 text-center">
              <div className="flex items-center justify-between mb-2">
                <CheckCircle className="w-5 h-5 text-green-400" aria-hidden="true" />
                <div className="w-4 h-4 bg-green-300/50 rounded-full" aria-hidden="true"></div>
              </div>
              <div className="text-2xl font-bold text-green-400" aria-describedby="completed-label">
                {completedCount}
              </div>
              <div id="completed-label" className="text-xs text-green-300/80">
                Completed
              </div>
            </CardContent>
          </Card>
          
          {/* Active */}
          <Card 
            className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-cyan-400/20 hover:border-cyan-400/40 transition-all duration-300 card-enhanced group"
            role="group"
            aria-labelledby="active-label"
          >
            <CardContent className="p-4 text-center">
              <div className="flex items-center justify-between mb-2">
                <Activity className="w-5 h-5 text-cyan-400" aria-hidden="true" />
                <div className="w-4 h-4 bg-cyan-300/50 rounded-full animate-pulse" aria-hidden="true"></div>
              </div>
              <div className="text-2xl font-bold text-cyan-400" aria-describedby="active-label">
                {activeCount}
              </div>
              <div id="active-label" className="text-xs text-cyan-300/80">
                Active Now
              </div>
            </CardContent>
          </Card>
        </motion.div>
        
        {/* Epic Progress Bar */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10 mb-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-white font-semibold">Mission Progress</span>
            <span className="text-cyan-400 font-bold">{completedCount} / {jadwal.length}</span>
          </div>
          <div className="bg-gray-800/50 rounded-full h-3 relative overflow-hidden">
            <div 
              className="absolute inset-0 bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 rounded-full transition-all duration-1000 ease-out"
              style={{ width: `${jadwal.length > 0 ? (completedCount / jadwal.length) * 100 : 0}%` }}
            >
              <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
            </div>
          </div>
          
          {/* Additional Progress Info */}
          <div className="mt-2 text-xs text-gray-400 flex justify-between">
            <span>🟢 Aktif: {activeCount}</span>
            <span>🔄 Upcoming: {upcomingCount}</span>
            <span>❌ Expired: {expiredCount}</span>
          </div>
        </div>
      </section>

      {/* Schedule List */}
      <section role="region" aria-labelledby="schedule-list-heading" id="schedule-list">
        <h2 id="schedule-list-heading" className="sr-only">Daftar jadwal jaga</h2>
        <motion.div variants={container} className="space-y-4">
          {loading ? (
            <div 
              className="flex items-center justify-center py-8"
              role="status"
              aria-live="polite"
              aria-label="Memuat data jadwal"
            >
              <Loader2 
                className="w-6 h-6 text-blue-500 animate-spin mr-3" 
                aria-hidden="true"
              />
              <span className="text-sm text-high-contrast">Memuat jadwal...</span>
            </div>
          ) : error ? (
            <Card 
              className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced"
              role="alert"
              aria-labelledby="error-title"
              ref={errorRef}
            >
              <CardContent className="p-6 text-center">
                <div className="text-red-500 mb-2">
                  <AlertCircle className="w-12 h-12 mx-auto mb-3" aria-hidden="true" />
                  <h3 id="error-title" className="font-medium text-red-400 mb-2">
                    Terjadi Kesalahan
                  </h3>
                  <p className="text-red-300">{error}</p>
                </div>
                <Button 
                  variant="outline" 
                  onClick={handleRetry}
                  className="mt-3 btn-error-accessible focus-outline touch-target"
                  aria-label="Muat ulang jadwal"
                >
                  Coba Lagi
                </Button>
              </CardContent>
            </Card>
          ) : jadwal.length === 0 ? (
            <Card 
              className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced"
              role="status"
              aria-labelledby="empty-title"
            >
              <CardContent className="p-6 text-center">
                <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-3" aria-hidden="true" />
                <h3 id="empty-title" className="text-sm font-medium text-high-contrast mb-1">
                  Belum ada jadwal
                </h3>
                <p className="text-xs text-medium-contrast">
                  Hubungi admin untuk penjadwalan jaga
                </p>
              </CardContent>
            </Card>
          ) : (
          jadwal.map((scheduleItem, index) => {
            const badgeConfig = getStatusBadgeConfig(scheduleItem.status);
            const BadgeIcon = badgeConfig.icon;
            
            return (
            <motion.div
              key={scheduleItem.id}
              variants={item}
              whileHover={{ scale: 1.01, y: -2 }}
              transition={{ duration: 0.2 }}
              className="relative group"
            >
              {/* Enhanced Card with Gaming Badge */}
              <Card 
                className={getCardClasses()}
                role="article"
                aria-labelledby={`schedule-title-${scheduleItem.id}`}
                aria-describedby={`schedule-details-${scheduleItem.id}`}
                tabIndex={0}
                onTouchStart={(e) => handleTouchStart(e, scheduleItem.id)}
                onTouchEnd={handleTouchEnd}
              >
                {/* Gaming Badge - Top Right */}
                <div className="absolute top-3 right-3 z-20">
                  <div className={`
                    bg-gradient-to-r ${badgeConfig.gradient} rounded-xl px-3 py-1.5
                    border ${badgeConfig.borderColor} shadow-lg ${badgeConfig.glowColor}
                    ${badgeConfig.pulse}
                  `}>
                    <div className="flex items-center space-x-1.5">
                      <BadgeIcon className="w-3.5 h-3.5 text-white" aria-hidden="true" />
                      <span className={`text-xs font-bold ${badgeConfig.textColor} tracking-wide`}>
                        {badgeConfig.text}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Background Glow Effect */}
                <div className={`
                  absolute inset-0 bg-gradient-to-br ${badgeConfig.bgGlow} opacity-0 
                  group-hover:opacity-20 transition-opacity duration-400
                `}></div>

                <CardContent className="p-6 relative z-10">
                  <div className="flex justify-between items-start mb-4 pr-20">
                    <div>
                      <h3 
                        id={`schedule-title-${scheduleItem.id}`}
                        className="text-lg font-semibold text-high-contrast"
                      >
                        {formatTanggal(scheduleItem.tanggal)}
                      </h3>
                      <p 
                        className="text-sm text-muted-foreground font-medium"
                        aria-label={`Shift ${scheduleItem.jenis}`}
                      >
                        Shift {scheduleItem.jenis}
                      </p>
                    </div>
                  </div>
                
                <div id={`schedule-details-${scheduleItem.id}`} className="space-y-3">
                  <div className="flex items-center gap-3" role="group" aria-label="Waktu shift">
                    <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center" aria-hidden="true">
                      <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span 
                      className="text-sm font-medium text-high-contrast"
                      aria-label={`Waktu shift: ${scheduleItem.waktu}`}
                    >
                      {scheduleItem.waktu}
                    </span>
                    <Badge 
                      className={`${getShiftColor(scheduleItem.jenis)} text-xs font-semibold ml-auto`}
                      aria-label={`Jenis shift: ${scheduleItem.jenis}`}
                    >
                      {scheduleItem.jenis.charAt(0).toUpperCase() + scheduleItem.jenis.slice(1)}
                    </Badge>
                  </div>
                  
                  <div className="flex items-center gap-3" role="group" aria-label="Lokasi tugas">
                    <div className="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center" aria-hidden="true">
                      <MapPin className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span 
                      className="text-sm font-medium text-high-contrast flex-1"
                      aria-label={`Lokasi: ${scheduleItem.lokasi}`}
                    >
                      {scheduleItem.lokasi}
                    </span>
                    <ChevronRight className="w-4 h-4 text-muted-foreground" aria-hidden="true" />
                  </div>
                </div>
                
                {/* Action Buttons - Always visible for scheduled items */}
                {scheduleItem.status === 'scheduled' && (
                  <motion.div 
                    initial={{ opacity: 0, height: 0 }}
                    animate={{ opacity: 1, height: 'auto' }}
                    transition={{ duration: 0.3 }}
                    className="flex gap-3 pt-4 mt-4 border-t border-gray-100 dark:border-gray-700"
                    role="group"
                    aria-label="Aksi jadwal"
                  >
                    <motion.div
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      className="flex-1"
                    >
                      <Button 
                        variant="outline" 
                        size="sm" 
                        onClick={() => handleEditSchedule(scheduleItem.id)}
                        className="w-full border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-300 dark:hover:border-blue-600 gap-2 font-medium transition-colors duration-300 btn-primary-accessible focus-outline touch-target"
                        aria-label={`Ubah jadwal ${formatTanggal(scheduleItem.tanggal)}`}
                        aria-describedby={`schedule-details-${scheduleItem.id}`}
                      >
                        <Edit className="w-4 h-4" aria-hidden="true" />
                        Ubah
                      </Button>
                    </motion.div>
                    <motion.div
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      className="flex-1"
                    >
                      <Button 
                        variant="outline" 
                        size="sm" 
                        onClick={() => handleCancelSchedule(scheduleItem.id)}
                        className="w-full border-red-200 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-300 dark:hover:border-red-600 gap-2 font-medium transition-colors duration-300 btn-error-accessible focus-outline touch-target"
                        aria-label={`Batalkan jadwal ${formatTanggal(scheduleItem.tanggal)}`}
                        aria-describedby={`schedule-details-${scheduleItem.id}`}
                      >
                        <X className="w-4 h-4" aria-hidden="true" />
                        Batalkan
                      </Button>
                    </motion.div>
                  </motion.div>
                )}
              </CardContent>
            </Card>
          </motion.div>
          );
          })
        )}
        </motion.div>
      </section>

      {/* Add Schedule Button */}
      <section role="region" aria-labelledby="add-schedule-heading" id="add-schedule">
        <h2 id="add-schedule-heading" className="sr-only">Tambah jadwal baru</h2>
        <motion.div variants={item}>
          <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-dashed border-2 border-blue-200 dark:border-blue-700 card-enhanced">
            <CardContent className="p-6">
              <motion.div
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                className="text-center"
              >
                <Button 
                  variant="ghost" 
                  className="w-full h-16 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 gap-3 text-base font-medium transition-colors duration-300 btn-primary-accessible focus-outline touch-target"
                  aria-label="Tambah jadwal jaga baru"
                  aria-describedby="add-schedule-description"
                >
                  <Plus className="w-5 h-5" aria-hidden="true" />
                  Tambah Jadwal Baru
                </Button>
                <div id="add-schedule-description" className="sr-only">
                  Klik untuk membuat jadwal jaga baru
                </div>
              </motion.div>
            </CardContent>
          </Card>
        </motion.div>
      </section>
      </motion.div>
    </div>
  );
});