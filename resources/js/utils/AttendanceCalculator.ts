/**
 * Unified Attendance Rate Calculator
 * Ensures consistency between Dashboard and Presensi components
 */

export interface AttendanceRecord {
  date: string;
  status: string;
  time_in?: string;
  time_out?: string;
  hours?: string;
  actual_hours?: number;
  worked_hours?: number;
  scheduled_hours?: number;
  shift_template?: {
    jam_masuk: string;
    jam_pulang: string;
  };
}

export interface AttendanceMetrics {
  // Days-based metrics
  presentDays: number;
  lateDays: number;
  totalDays: number;
  
  // Hours-based metrics (primary)
  totalScheduledHours: number;
  totalAttendedHours: number;
  hoursShortage: number;
  
  // Unified percentage
  attendancePercentage: number;
  
  // Display format
  displayText: string;
  progressBarValue: number;
}

class AttendanceCalculator {
  /**
   * Parse different hour formats from API data
   */
  static parseHours(hourString: string | number | undefined | null): number {
    if (!hourString) return 0;
    
    // Handle numeric values
    if (typeof hourString === 'number') {
      return Math.max(0, hourString);
    }
    
    const str = String(hourString);
    
    // Handle "8h 30m" format
    if (str.includes('h')) {
      const parts = str.match(/(\d+)h\s*(\d+)?m?/);
      if (parts) {
        return parseInt(parts[1]) + (parseInt(parts[2] || '0') / 60);
      }
    }
    
    // Handle "08:30" format
    if (str.includes(':')) {
      const [h, m] = str.split(':').map(Number);
      if (!isNaN(h) && !isNaN(m)) {
        return h + (m / 60);
      }
    }
    
    // Handle decimal format "8.5"
    const num = parseFloat(str);
    return isNaN(num) ? 0 : Math.max(0, num);
  }

  /**
   * Calculate shift duration from start/end times
   */
  static calculateShiftDuration(startTime: string, endTime: string): number {
    try {
      const start = new Date(`1970-01-01T${startTime}:00`);
      const end = new Date(`1970-01-01T${endTime}:00`);
      
      let diffMs = end.getTime() - start.getTime();
      
      // Handle overnight shifts
      if (diffMs < 0) {
        diffMs += 24 * 60 * 60 * 1000; // Add 24 hours
      }
      
      return Math.max(0, diffMs / (1000 * 60 * 60)); // Convert to hours
    } catch (error) {
      console.warn('Failed to calculate shift duration:', error);
      return 8; // Default 8 hours
    }
  }

  /**
   * UNIFIED ATTENDANCE CALCULATION
   * Used by both Dashboard and Presensi components
   */
  static calculateAttendanceMetrics(
    records: AttendanceRecord[],
    monthStart: Date,
    monthEnd: Date
  ): AttendanceMetrics {
    console.log('🔄 AttendanceCalculator: Starting unified calculation', {
      recordCount: records.length,
      monthRange: { start: monthStart, end: monthEnd }
    });

    // Filter records for the specified month
    const monthlyData = records.filter(record => {
      const recordDate = new Date(record.date.split('/').reverse().join('-'));
      return recordDate >= monthStart && recordDate <= monthEnd;
    });

    // Days-based calculations
    const presentDays = monthlyData.filter(r => 
      r.status === 'Hadir' || r.status === 'Tepat Waktu' || r.status === 'Terlambat'
    ).length;

    const lateDays = monthlyData.filter(r => 
      r.status === 'Terlambat'
    ).length;

    const workingDaysInMonth = this.calculateWorkingDaysInMonth(monthStart);

    // Hours-based calculations (PRIORITY SYSTEM)
    let totalScheduledHours = 0;
    let totalAttendedHours = 0;

    monthlyData.forEach(record => {
      // Calculate scheduled hours for this record
      let scheduledHours = 8; // Default 8 hours
      
      // Priority 1: Use scheduled_hours from API
      if (record.scheduled_hours) {
        scheduledHours = record.scheduled_hours;
      }
      // Priority 2: Calculate from shift template
      else if (record.shift_template?.jam_masuk && record.shift_template?.jam_pulang) {
        scheduledHours = this.calculateShiftDuration(
          record.shift_template.jam_masuk,
          record.shift_template.jam_pulang
        );
      }

      // Only count scheduled hours for work days (not leave)
      if (record.status !== 'Cuti' && record.status !== 'Leave') {
        totalScheduledHours += scheduledHours;
      }

      // Calculate attended hours
      let attendedHours = 0;
      
      // Priority 1: Use actual_hours from API
      if (record.actual_hours !== undefined && record.actual_hours !== null) {
        attendedHours = record.actual_hours;
      }
      // Priority 2: Use worked_hours from API  
      else if (record.worked_hours !== undefined && record.worked_hours !== null) {
        attendedHours = record.worked_hours;
      }
      // Priority 3: Calculate from time_in and time_out
      else if (record.time_in && record.time_out) {
        attendedHours = this.calculateShiftDuration(record.time_in, record.time_out);
      }
      // Priority 4: Parse display hours format
      else if (record.hours && record.hours !== '-' && record.hours !== '0h 0m') {
        attendedHours = this.parseHours(record.hours);
      }

      // Only count attended hours for present days
      if ((record.status === 'Hadir' || record.status === 'Tepat Waktu' || record.status === 'Terlambat') && attendedHours > 0) {
        totalAttendedHours += attendedHours;
      }
    });

    // Calculate final metrics
    const hoursShortage = Math.max(0, totalScheduledHours - totalAttendedHours);
    
    // UNIFIED PERCENTAGE CALCULATION (Hours-based)
    const attendancePercentage = totalScheduledHours > 0 
      ? Math.round((totalAttendedHours / totalScheduledHours) * 100)
      : 0;

    const metrics: AttendanceMetrics = {
      presentDays,
      lateDays,
      totalDays: workingDaysInMonth,
      totalScheduledHours: Math.round(totalScheduledHours * 10) / 10,
      totalAttendedHours: Math.round(totalAttendedHours * 10) / 10,
      hoursShortage: Math.round(hoursShortage * 10) / 10,
      attendancePercentage,
      displayText: `${attendancePercentage}%`,
      progressBarValue: attendancePercentage
    };

    console.log('✅ AttendanceCalculator: Unified calculation complete', metrics);
    return metrics;
  }

  /**
   * Calculate working days in a month (exclude weekends)
   */
  static calculateWorkingDaysInMonth(monthStart: Date): number {
    const year = monthStart.getFullYear();
    const month = monthStart.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    let workingDays = 0;
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      const dayOfWeek = date.getDay();
      // Count Monday to Friday as working days (1-5)
      if (dayOfWeek >= 1 && dayOfWeek <= 5) {
        workingDays++;
      }
    }
    
    return workingDays;
  }

  /**
   * Get current month date range
   */
  static getCurrentMonthRange(): { start: Date; end: Date } {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
    return { start, end };
  }

  /**
   * Format attendance metrics for API compatibility
   * Ensures dashboard API format matches calculation results
   */
  static formatForDashboardAPI(metrics: AttendanceMetrics) {
    return {
      attendance_rate: metrics.attendancePercentage,
      days_present: metrics.presentDays,
      total_days: metrics.totalDays,
      hours_attended: metrics.totalAttendedHours,
      hours_scheduled: metrics.totalScheduledHours,
      hours_shortage: metrics.hoursShortage,
      display_text: metrics.displayText
    };
  }
}

export default AttendanceCalculator;