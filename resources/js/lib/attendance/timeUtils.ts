/**
 * Unified Time Utilities
 * Shared time formatting and calculation functions for attendance systems
 */

import { TimeFormatOptions, AttendanceCalculation } from './types';

/**
 * Format time for display (unified from both components)
 */
export const formatTime = (
  timeInput: string | Date | null, 
  options: TimeFormatOptions = {}
): string => {
  if (!timeInput) return '--:--';
  
  const {
    locale = 'id-ID',
    hour12 = false,
    includeSeconds = false,
    timeZone
  } = options;

  try {
    let date: Date;
    
    if (timeInput instanceof Date) {
      date = timeInput;
    } else if (typeof timeInput === 'string') {
      // Handle different time formats from both components
      if (timeInput.includes('T')) {
        // ISO format: 2024-01-01T14:30:00.000000Z
        date = new Date(timeInput);
      } else if (timeInput.length > 5) {
        // Format: 14:30:00
        date = new Date(`1970-01-01T${timeInput}`);
      } else {
        // Format: 14:30
        date = new Date(`1970-01-01T${timeInput}:00`);
      }
    } else {
      return '--:--';
    }

    if (isNaN(date.getTime())) {
      return '--:--';
    }

    const formatOptions: Intl.DateTimeFormatOptions = {
      hour: '2-digit',
      minute: '2-digit',
      hour12,
      timeZone
    };

    if (includeSeconds) {
      formatOptions.second = '2-digit';
    }

    return date.toLocaleTimeString(locale, formatOptions);
  } catch (error) {
    console.warn('Time formatting error:', error);
    return '--:--';
  }
};

/**
 * Parse date/time strings with multiple fallback strategies
 * (Extracted from paramedis component)
 */
export const parseDateTime = (dateInput: string | Date): Date => {
  if (dateInput instanceof Date) {
    return dateInput;
  }
  
  if (typeof dateInput !== 'string') {
    throw new Error('Invalid date input type');
  }
  
  // Try multiple parsing strategies
  const strategies = [
    // Standard ISO format
    () => new Date(dateInput),
    // Replace space with T for ISO format
    () => new Date(dateInput.replace(' ', 'T')),
    // Add timezone if missing
    () => new Date(dateInput + (dateInput.includes('Z') || dateInput.includes('+') ? '' : 'Z')),
    // Handle local timezone format "2025-01-25 10:30:00"
    () => {
      const normalized = dateInput.replace(' ', 'T');
      return new Date(normalized + (normalized.includes('Z') || normalized.includes('+') ? '' : '+07:00'));
    },
    // Handle time-only format like "14:30:00" - combine with today's date
    () => {
      if (/^\d{2}:\d{2}:\d{2}$/.test(dateInput)) {
        const today = new Date().toISOString().split('T')[0];
        return new Date(`${today}T${dateInput}`);
      }
      throw new Error('Not time-only format');
    },
    // Handle time-only format like "14:30" - combine with today's date
    () => {
      if (/^\d{2}:\d{2}$/.test(dateInput)) {
        const today = new Date().toISOString().split('T')[0];
        return new Date(`${today}T${dateInput}:00`);
      }
      throw new Error('Not time-only format');
    }
  ];
  
  for (const strategy of strategies) {
    try {
      const result = strategy();
      if (!isNaN(result.getTime())) {
        return result;
      }
    } catch (error) {
      // Continue to next strategy
    }
  }
  
  throw new Error(`Unable to parse date: ${dateInput}`);
};

/**
 * Format date for display
 */
export const formatDate = (
  date: Date | string,
  options: Intl.DateTimeFormatOptions = {}
): string => {
  try {
    const dateObj = typeof date === 'string' ? parseDateTime(date) : date;
    
    const defaultOptions: Intl.DateTimeFormatOptions = {
      day: '2-digit',
      month: '2-digit', 
      year: 'numeric',
      ...options
    };
    
    return dateObj.toLocaleDateString('id-ID', defaultOptions);
  } catch (error) {
    console.warn('Date formatting error:', error);
    return '--/--/----';
  }
};

/**
 * Calculate working hours between check-in and check-out
 * (Unified from both components)
 */
export const calculateWorkingHours = (
  checkInTime: string | Date | null,
  checkOutTime: string | Date | null,
  breakTimeMinutes: number = 0
): AttendanceCalculation => {
  if (!checkInTime) {
    return {
      workingHours: '00:00:00',
      hoursShortage: '00:00:00',
      breakTime: '00:00:00',
      totalMinutes: 0,
      isActive: false
    };
  }

  try {
    const startTime = parseDateTime(checkInTime);
    const endTime = checkOutTime ? parseDateTime(checkOutTime) : new Date();
    
    // Calculate total minutes worked
    const diffMs = endTime.getTime() - startTime.getTime();
    const totalMinutes = Math.max(0, Math.floor(diffMs / (1000 * 60)) - breakTimeMinutes);
    
    // Convert to hours and minutes
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    const seconds = 0; // Keep for compatibility
    
    // Format working hours
    const workingHours = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    // Calculate shortage (8 hours = 480 minutes standard)
    const standardMinutes = 8 * 60;
    const shortageMinutes = Math.max(0, standardMinutes - totalMinutes);
    const shortageHours = Math.floor(shortageMinutes / 60);
    const shortageRemainder = shortageMinutes % 60;
    const hoursShortage = `${shortageHours.toString().padStart(2, '0')}:${shortageRemainder.toString().padStart(2, '0')}:00`;
    
    // Format break time
    const breakHours = Math.floor(breakTimeMinutes / 60);
    const breakRemainder = breakTimeMinutes % 60;
    const breakTime = `${breakHours.toString().padStart(2, '0')}:${breakRemainder.toString().padStart(2, '0')}:00`;
    
    return {
      workingHours,
      hoursShortage,
      breakTime,
      totalMinutes,
      isActive: !checkOutTime
    };
  } catch (error) {
    console.warn('Working hours calculation error:', error);
    return {
      workingHours: '00:00:00',
      hoursShortage: '08:00:00',
      breakTime: '00:00:00',
      totalMinutes: 0,
      isActive: false
    };
  }
};

/**
 * Get current local date string (YYYY-MM-DD)
 */
export const getCurrentDateString = (): string => {
  return new Date().toISOString().split('T')[0];
};

/**
 * Check if a date is today
 */
export const isToday = (date: string | Date): boolean => {
  try {
    const dateObj = typeof date === 'string' ? parseDateTime(date) : date;
    const today = new Date();
    
    return dateObj.getDate() === today.getDate() &&
           dateObj.getMonth() === today.getMonth() &&
           dateObj.getFullYear() === today.getFullYear();
  } catch (error) {
    return false;
  }
};

/**
 * Get time difference in minutes
 */
export const getTimeDifferenceMinutes = (
  startTime: string | Date,
  endTime: string | Date = new Date()
): number => {
  try {
    const start = typeof startTime === 'string' ? parseDateTime(startTime) : startTime;
    const end = typeof endTime === 'string' ? parseDateTime(endTime) : endTime;
    
    return Math.floor((end.getTime() - start.getTime()) / (1000 * 60));
  } catch (error) {
    return 0;
  }
};

/**
 * Format duration in minutes to HH:MM format
 */
export const formatDurationMinutes = (minutes: number): string => {
  const hours = Math.floor(Math.abs(minutes) / 60);
  const mins = Math.abs(minutes) % 60;
  const sign = minutes < 0 ? '-' : '';
  
  return `${sign}${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
};

/**
 * Parse time string to minutes since midnight
 */
export const timeStringToMinutes = (timeString: string): number => {
  try {
    const [hours, minutes] = timeString.split(':').map(Number);
    return (hours || 0) * 60 + (minutes || 0);
  } catch (error) {
    return 0;
  }
};

/**
 * Convert minutes since midnight to time string
 */
export const minutesToTimeString = (minutes: number): string => {
  const hours = Math.floor(minutes / 60) % 24;
  const mins = minutes % 60;
  
  return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
};