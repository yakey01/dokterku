/**
 * Core Schedule Utilities
 * Shared utility functions for both dokter and paramedis schedule components
 */

import { 
  ScheduleStatus, 
  UnifiedSchedule, 
  DokterSchedule, 
  ParamedisSchedule, 
  AttendanceRecord, 
  BadgeConfig,
  ShiftTemplate,
  ScheduleVariant,
  isDokterSchedule,
  isParamedisSchedule
} from './types';

// Re-export common icons for consistency
import { 
  Clock, 
  CheckCircle, 
  Activity, 
  Zap, 
  Trophy, 
  Hourglass,
  AlertCircle 
} from 'lucide-react';

/**
 * Get current time for schedule status calculations
 */
export const getCurrentTime = (): Date => new Date();

/**
 * Format attendance time for display with comprehensive error handling
 */
export const formatAttendanceTime = (timeString: string | undefined): string => {
  if (!timeString) return '--:--';
  
  try {
    let dateToFormat: Date;
    
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

/**
 * Determine schedule status based on current time and schedule
 */
export const getScheduleStatus = (schedule: UnifiedSchedule): ScheduleStatus => {
  const now = getCurrentTime();
  const scheduleDate = new Date(schedule.full_date);
  const todayString = now.toISOString().split('T')[0];
  const scheduleString = scheduleDate.toISOString().split('T')[0];
  
  if (!schedule.shift_template) {
    // Fallback status determination
    if (scheduleDate < now) return 'expired';
    return 'upcoming';
  }
  
  // Parse shift times
  const [startHour, startMinute] = schedule.shift_template.jam_masuk.split(':').map(Number);
  const [endHour, endMinute] = schedule.shift_template.jam_pulang.split(':').map(Number);
  
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

/**
 * Get gaming-style badge configuration based on status and attendance
 */
export const getBadgeConfig = (
  status: ScheduleStatus,
  attendance?: AttendanceRecord,
  variant: ScheduleVariant = 'dokter'
): BadgeConfig => {
  const checkInTime = attendance?.check_in_time;
  const checkOutTime = attendance?.check_out_time;
  
  switch (status) {
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
          pulse: 'animate-pulse',
          priority: 'normal'
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
          pulse: 'animate-pulse',
          priority: 'important'
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
          pulse: 'animate-bounce',
          priority: 'important'
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
          pulse: '',
          priority: 'normal'
        };
      } else {
        return {
          text: variant === 'dokter' ? 'COMPLETED' : 'EXPIRED',
          icon: variant === 'dokter' ? Hourglass : AlertCircle,
          gradient: 'from-red-500 via-rose-500 to-pink-500',
          textColor: 'text-red-100',
          borderColor: 'border-red-400/50',
          glowColor: 'shadow-red-500/30',
          bgGlow: 'from-red-500/20 to-rose-500/20',
          pulse: '',
          priority: 'critical'
        };
      }
    
    case 'completed':
      return {
        text: 'COMPLETED',
        icon: Trophy,
        gradient: 'from-emerald-500 via-green-500 to-teal-500',
        textColor: 'text-emerald-100',
        borderColor: 'border-emerald-400/50',
        glowColor: 'shadow-emerald-500/30',
        bgGlow: 'from-emerald-500/20 to-green-500/20',
        pulse: '',
        priority: 'normal'
      };
    
    case 'cancelled':
      return {
        text: 'CANCELLED',
        icon: AlertCircle,
        gradient: 'from-gray-500 via-gray-600 to-gray-700',
        textColor: 'text-gray-200',
        borderColor: 'border-gray-400/50',
        glowColor: 'shadow-gray-500/30',
        bgGlow: 'from-gray-500/20 to-gray-600/20',
        pulse: '',
        priority: 'low'
      };
    
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
        pulse: 'animate-pulse',
        priority: 'normal'
      };
  }
};

/**
 * Format time correctly - extract time from datetime or format time string
 */
export const formatTimeFromString = (time: string): string => {
  if (!time) return time;
  
  // If it's a full datetime (2025-08-06T09:00), extract just the time part
  if (time.includes('T')) {
    const timePart = time.split('T')[1];
    if (timePart) {
      return timePart.split(':').slice(0, 2).join(':');
    }
  }
  
  // If time has seconds (HH:MM:SS), remove them
  return time.split(':').slice(0, 2).join(':');
};

/**
 * Get shift color based on shift type
 */
export const getShiftColor = (jenis: string): string => {
  switch (jenis?.toLowerCase()) {
    case 'pagi': 
      return 'bg-gradient-to-r from-yellow-400 to-yellow-500 dark:from-yellow-500 dark:to-yellow-600 text-white';
    case 'siang': 
      return 'bg-gradient-to-r from-orange-400 to-orange-500 dark:from-orange-500 dark:to-orange-600 text-white';
    case 'malam': 
      return 'bg-gradient-to-r from-purple-400 to-purple-500 dark:from-purple-500 dark:to-purple-600 text-white';
    default: 
      return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
  }
};

/**
 * Get status color for legacy status display
 */
export const getStatusColor = (status: string): string => {
  switch (status?.toLowerCase()) {
    case 'scheduled': 
    case 'upcoming':
      return 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-700';
    case 'completed': 
      return 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
    case 'missed': 
    case 'expired':
      return 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
    default: 
      return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700';
  }
};

/**
 * Format date for display
 */
export const formatDateForDisplay = (dateString: string): string => {
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  } catch (error) {
    console.error('Date formatting error:', error);
    return dateString;
  }
};

/**
 * Get shift subtitle based on shift name (for dokter variant)
 */
export const getShiftSubtitle = (namaShift: string): string => {
  switch (namaShift?.toLowerCase()) {
    case 'malam': return "Emergency Critical Care";
    case 'pagi': return "Morning Patient Rounds";
    case 'siang': return "Afternoon Clinical Care";
    case 'sore': return "Evening Medical Service";
    default: return "General Outpatient Care";
  }
};

/**
 * Get shift type classification (for dokter variant)
 */
export const getShiftType = (namaShift: string): 'regular' | 'urgent' | 'special' | 'training' => {
  switch (namaShift?.toLowerCase()) {
    case 'malam': return 'urgent';
    case 'emergency': return 'urgent';
    case 'training': return 'training';
    case 'workshop': return 'training';
    default: return 'regular';
  }
};

/**
 * Map API status to display status
 */
export const mapApiStatus = (apiStatus: string): string => {
  const statusMap: Record<string, string> = {
    'selesai': 'Completed',
    'completed': 'Completed',
    'aktif': 'Aktif',
    'active': 'Aktif',
    'berlangsung': 'Aktif',
    'upcoming': 'Terjadwal',
    'terjadwal': 'Terjadwal',
    'cuti': 'Cuti',
    'izin': 'Izin',
    'oncall': 'OnCall'
  };
  return statusMap[apiStatus?.toLowerCase()] || 'Terjadwal';
};

/**
 * Check if schedule has attendance data
 */
export const hasAttendanceData = (schedule: UnifiedSchedule): boolean => {
  return !!(schedule.attendance && 
    (schedule.attendance.check_in_time || schedule.attendance.check_out_time));
};

/**
 * Check if schedule is completed (has both check-in and check-out)
 */
export const isScheduleCompleted = (schedule: UnifiedSchedule): boolean => {
  return !!(schedule.attendance && 
    schedule.attendance.check_in_time && 
    schedule.attendance.check_out_time);
};

/**
 * Get schedule completion percentage
 */
export const getScheduleCompletionPercentage = (schedules: UnifiedSchedule[]): number => {
  if (schedules.length === 0) return 0;
  const completedCount = schedules.filter(isScheduleCompleted).length;
  return Math.round((completedCount / schedules.length) * 100);
};

/**
 * Sort schedules by date and time
 */
export const sortSchedulesByDate = (schedules: UnifiedSchedule[]): UnifiedSchedule[] => {
  return [...schedules].sort((a, b) => {
    const dateA = new Date(a.full_date);
    const dateB = new Date(b.full_date);
    return dateA.getTime() - dateB.getTime();
  });
};

/**
 * Filter schedules by status
 */
export const filterSchedulesByStatus = (
  schedules: UnifiedSchedule[], 
  status: ScheduleStatus
): UnifiedSchedule[] => {
  return schedules.filter(schedule => getScheduleStatus(schedule) === status);
};

/**
 * Get schedule statistics
 */
export const calculateScheduleStats = (schedules: UnifiedSchedule[]) => {
  const stats = {
    total: schedules.length,
    upcoming: 0,
    active: 0,
    expired: 0,
    completed: 0,
    cancelled: 0
  };
  
  schedules.forEach(schedule => {
    const status = getScheduleStatus(schedule);
    const badgeConfig = getBadgeConfig(status, schedule.attendance);
    
    switch (status) {
      case 'upcoming':
        stats.upcoming++;
        break;
      case 'active':
        stats.active++;
        break;
      case 'expired':
        stats.expired++;
        break;
      case 'cancelled':
        stats.cancelled++;
        break;
    }
    
    // Check for completed status based on badge text
    if (badgeConfig.text === 'COMPLETED') {
      stats.completed++;
    }
  });
  
  return stats;
};

/**
 * Performance measurement utilities
 */
export const performanceUtils = {
  mark: (name: string) => {
    if (typeof performance !== 'undefined' && performance.mark) {
      performance.mark(name);
    }
  },
  
  measure: (name: string, startMark: string, endMark: string) => {
    if (typeof performance !== 'undefined' && performance.measure) {
      try {
        performance.measure(name, startMark, endMark);
      } catch (error) {
        console.warn('Performance measurement failed:', error);
      }
    }
  },
  
  getMemoryUsage: (): number => {
    if (typeof performance !== 'undefined' && 'memory' in performance) {
      return Math.round((performance as any).memory.usedJSHeapSize / 1024 / 1024);
    }
    return 0;
  }
};

/**
 * Device detection utilities
 */
export const deviceUtils = {
  isMobile: (): boolean => {
    return typeof window !== 'undefined' && window.innerWidth < 768;
  },
  
  isTablet: (): boolean => {
    return typeof window !== 'undefined' && 
           window.innerWidth >= 768 && window.innerWidth < 1024;
  },
  
  isDesktop: (): boolean => {
    return typeof window !== 'undefined' && window.innerWidth >= 1024;
  },
  
  getOrientation: (): 'portrait' | 'landscape' => {
    if (typeof window === 'undefined') return 'portrait';
    return window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
  }
};