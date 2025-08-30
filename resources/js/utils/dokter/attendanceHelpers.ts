// Helper functions for Dokter Attendance System

/**
 * Format time to HH:MM:SS
 */
export const formatHHMMSS = (date: Date): string => {
  const pad = (n: number) => n.toString().padStart(2, '0');
  return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
};

/**
 * Format duration from milliseconds to HH:MM:SS
 */
export const formatDuration = (ms: number): string => {
  const s = Math.max(0, Math.floor(ms / 1000));
  const hh = Math.floor(s / 3600).toString().padStart(2, '0');
  const mm = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
  const ss = Math.floor(s % 60).toString().padStart(2, '0');
  return `${hh}:${mm}:${ss}`;
};

/**
 * Get local date string in YYYY-MM-DD format
 */
export const getLocalDateStr = (): string => {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
};

/**
 * Parse time string to Date object
 */
export const parseTodayTimeToDate = (timeStr?: string | null): Date | null => {
  if (!timeStr) return null;
  
  // Handle HH:MM or HH:MM:SS format
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(timeStr)) {
    const now = new Date();
    const [hh, mm, ss] = timeStr.split(':').map(Number);
    return new Date(now.getFullYear(), now.getMonth(), now.getDate(), hh || 0, mm || 0, ss || 0);
  }
  
  // Handle ISO date string
  const d = new Date(timeStr);
  return isNaN(d.getTime()) ? null : d;
};

/**
 * Format time for display (12-hour format with AM/PM)
 */
export const formatTime = (date: Date): string => {
  const hours = date.getHours();
  const minutes = date.getMinutes();
  const ampm = hours >= 12 ? 'PM' : 'AM';
  const displayHours = hours % 12 || 12;
  return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
};

/**
 * Calculate working hours between two times
 */
export const calculateWorkingHours = (checkIn: Date | null, checkOut: Date | null): string => {
  if (!checkIn || !checkOut) return '00:00:00';
  const diff = checkOut.getTime() - checkIn.getTime();
  return formatDuration(diff);
};

/**
 * Check if current time is within shift window
 */
export const isWithinShiftWindow = (
  currentTime: Date,
  shiftStart: string,
  shiftEnd: string,
  toleranceMinutes: number = 30
): boolean => {
  const [startHour, startMin] = shiftStart.split(':').map(Number);
  const [endHour, endMin] = shiftEnd.split(':').map(Number);
  
  const currentMinutes = currentTime.getHours() * 60 + currentTime.getMinutes();
  const startMinutes = startHour * 60 + startMin - toleranceMinutes;
  const endMinutes = endHour * 60 + endMin + toleranceMinutes;
  
  // Handle overnight shifts
  if (endMinutes < startMinutes) {
    return currentMinutes >= startMinutes || currentMinutes <= endMinutes;
  }
  
  return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
};

/**
 * Calculate distance between two GPS coordinates (Haversine formula)
 */
export const calculateDistance = (
  lat1: number,
  lon1: number,
  lat2: number,
  lon2: number
): number => {
  const R = 6371e3; // Earth's radius in meters
  const φ1 = lat1 * Math.PI / 180;
  const φ2 = lat2 * Math.PI / 180;
  const Δφ = (lat2 - lat1) * Math.PI / 180;
  const Δλ = (lon2 - lon1) * Math.PI / 180;

  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c; // Distance in meters
};

/**
 * Retry function with exponential backoff
 */
export const retryWithBackoff = async <T>(
  fn: () => Promise<T>,
  maxRetries: number = 3,
  baseDelay: number = 1000
): Promise<T> => {
  let lastError: Error | null = null;
  
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      console.log(`🔄 Attempt ${attempt + 1}/${maxRetries}...`);
      return await fn();
    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));
      
      // Don't retry on client errors (4xx)
      if (lastError.message.includes('HTTP 4')) {
        throw lastError;
      }
      
      if (attempt < maxRetries - 1) {
        const delay = baseDelay * Math.pow(2, attempt);
        console.log(`⏳ Retrying in ${delay}ms...`);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }
  
  throw lastError || new Error('Max retries exceeded');
};

/**
 * Format date for display (DD-MM-Y format: 21-8-25, same as mobile app)
 */
export const formatDate = (date: Date | string): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1); // No leading zero for month
  const year = String(d.getFullYear()).slice(-2);
  
  return `${day}-${month}-${year}`;
};

/**
 * Get greeting based on time of day
 */
export const getGreeting = (): string => {
  const hour = new Date().getHours();
  if (hour < 10) return 'Selamat Pagi';
  if (hour < 15) return 'Selamat Siang';
  if (hour < 18) return 'Selamat Sore';
  return 'Selamat Malam';
};

/**
 * Validation message helper
 */
export const getValidationMessage = (
  duty: boolean,
  withinShift: boolean,
  hasWL: boolean,
  mayCheckOut?: boolean,
  checkedIn?: boolean
): string => {
  if (checkedIn) {
    if (mayCheckOut === false) {
      return '⏰ Waktu check-out sudah melewati batas (jam jaga + 30 menit)';
    }
    return '';
  }
  if (!duty) return 'Anda tidak memiliki jadwal jaga hari ini';
  if (!withinShift) return 'Saat ini bukan jam jaga Anda';
  if (!hasWL) return 'Work location belum ditugaskan';
  return '';
};