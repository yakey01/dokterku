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
      monthRange: { start: monthStart, end: monthEnd },
      sampleRecord: records[0]
    });

    // ENHANCED: Filter records for the specified month with better date parsing
    const monthlyData = records.filter(record => {
      try {
        let recordDate;
        
        // Handle different date formats
        if (record.date.includes('/')) {
          // DD/MM/YYYY or DD/MM/YY format
          const parts = record.date.split('/');
          if (parts.length === 3) {
            const day = parts[0];
            const month = parts[1];
            let year = parts[2];
            
            // Convert 2-digit year to 4-digit
            if (year.length === 2) {
              year = '20' + year;
            }
            
            recordDate = new Date(`${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`);
          } else {
            recordDate = new Date(record.date);
          }
        } else if (record.date.includes('-') && record.date.length === 8) {
          // DD-MM-YY format
          const parts = record.date.split('-');
          const day = parts[0];
          const month = parts[1];
          const year = '20' + parts[2];
          recordDate = new Date(`${year}-${month}-${day}`);
        } else {
          // ISO format or other
          recordDate = new Date(record.date);
        }
        
        const isInRange = recordDate >= monthStart && recordDate <= monthEnd;
        
        console.log('🔍 Date filter check:', {
          originalDate: record.date,
          parsedDate: recordDate.toISOString(),
          monthStart: monthStart.toISOString(),
          monthEnd: monthEnd.toISOString(),
          isInRange,
          status: record.status
        });
        
        return isInRange;
      } catch (error) {
        console.error('⚠️ Error parsing date:', record.date, error);
        return false;
      }
    });
    
    console.log('📊 Filtered monthly data:', {
      originalCount: records.length,
      filteredCount: monthlyData.length,
      sampleFiltered: monthlyData[0]
    });

    // ENHANCED: Days-based calculations with detailed debugging
    console.log('🔍 DEEP DEBUG: All monthly data status values:');
    monthlyData.forEach((record, i) => {
      console.log(`Record ${i + 1}:`, {
        date: record.date,
        status: `"${record.status}"`, // Show exact string with quotes
        statusType: typeof record.status,
        statusLength: record.status?.length,
        statusCharCodes: record.status ? Array.from(record.status).map(c => c.charCodeAt(0)) : 'undefined'
      });
    });
    
    const presentRecords = monthlyData.filter(r => {
      // Primary exact match
      const exactMatch = r.status === 'Hadir' || r.status === 'Tepat Waktu' || r.status === 'Terlambat';
      
      // Fallback: flexible matching for common variations (EXCLUDE "Tidak Hadir")
      const flexibleMatch = r.status && !r.status.toLowerCase().includes('tidak') && (
        r.status.toLowerCase().includes('hadir') ||
        r.status.toLowerCase().includes('present') ||
        r.status.toLowerCase().includes('on_time') ||
        r.status.toLowerCase().includes('tepat') ||
        r.status.toLowerCase().includes('late') ||
        r.status.toLowerCase().includes('terlambat')
      );
      
      const isPresent = exactMatch || flexibleMatch;
      
      console.log(`🔍 Status check for "${r.status}":`, {
        status: r.status,
        exactMatch: {
          isHadir: r.status === 'Hadir',
          isTepatWaktu: r.status === 'Tepat Waktu',
          isTerlambat: r.status === 'Terlambat',
          result: exactMatch
        },
        flexibleMatch: {
          hasHadir: r.status?.toLowerCase().includes('hadir'),
          hasPresent: r.status?.toLowerCase().includes('present'),
          hasOnTime: r.status?.toLowerCase().includes('on_time'),
          hasTepat: r.status?.toLowerCase().includes('tepat'),
          hasLate: r.status?.toLowerCase().includes('late'),
          hasTerlambat: r.status?.toLowerCase().includes('terlambat'),
          result: flexibleMatch
        },
        finalResult: isPresent
      });
      return isPresent;
    });
    
    const lateRecords = monthlyData.filter(r => 
      r.status === 'Terlambat'
    );
    
    const presentDays = presentRecords.length;
    const lateDays = lateRecords.length;
    
    console.log('📊 Status-based filtering:', {
      totalRecords: monthlyData.length,
      presentDays,
      lateDays,
      statusBreakdown: monthlyData.reduce((acc, r) => {
        acc[r.status] = (acc[r.status] || 0) + 1;
        return acc;
      }, {} as Record<string, number>),
      samplePresentRecord: presentRecords[0],
      sampleLateRecord: lateRecords[0]
    });

    const workingDaysInMonth = this.calculateWorkingDaysInMonth(monthStart);

    // Hours-based calculations (PRIORITY SYSTEM)
    let totalScheduledHours = 0;
    let totalAttendedHours = 0;

    monthlyData.forEach((record, index) => {
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
      const shouldCountScheduled = record.status !== 'Cuti' && record.status !== 'Leave';
      if (shouldCountScheduled) {
        totalScheduledHours += scheduledHours;
      }

      // Calculate attended hours
      let attendedHours = 0;
      let attendedSource = 'none';
      
      // Priority 1: Use actual_hours from API
      if (record.actual_hours !== undefined && record.actual_hours !== null) {
        attendedHours = record.actual_hours;
        attendedSource = 'actual_hours';
      }
      // Priority 2: Use worked_hours from API  
      else if (record.worked_hours !== undefined && record.worked_hours !== null) {
        attendedHours = record.worked_hours;
        attendedSource = 'worked_hours';
      }
      // Priority 3: Calculate from time_in and time_out
      else if (record.time_in && record.time_out && record.time_in !== '-' && record.time_out !== '-') {
        attendedHours = this.calculateShiftDuration(record.time_in, record.time_out);
        attendedSource = 'calculated_from_times';
      }
      // Priority 4: Parse display hours format
      else if (record.hours && record.hours !== '-' && record.hours !== '0h 0m') {
        attendedHours = this.parseHours(record.hours);
        attendedSource = 'parsed_hours';
      }

      // Only count attended hours for present days (with flexible matching)
      const exactStatusMatch = record.status === 'Hadir' || record.status === 'Tepat Waktu' || record.status === 'Terlambat';
      const flexibleStatusMatch = record.status && (
        record.status.toLowerCase().includes('hadir') ||
        record.status.toLowerCase().includes('present') ||
        record.status.toLowerCase().includes('on_time') ||
        record.status.toLowerCase().includes('tepat') ||
        record.status.toLowerCase().includes('late') ||
        record.status.toLowerCase().includes('terlambat')
      );
      
      const isPresentStatus = exactStatusMatch || flexibleStatusMatch;
      const shouldCountAttended = isPresentStatus && attendedHours > 0;
      
      if (shouldCountAttended) {
        totalAttendedHours += attendedHours;
      }
      
      // DEBUG: Log calculation for each record with ENHANCED detail
      if (index < 5) { // Log first 5 records for better debugging
        console.log(`📊 Record ${index + 1} calculation:`, {
          date: record.date,
          status: `"${record.status}"`,
          statusLength: record.status?.length,
          scheduledHours,
          attendedHours,
          attendedSource,
          shouldCountScheduled,
          shouldCountAttended,
          time_in: record.time_in,
          time_out: record.time_out,
          actual_hours: record.actual_hours,
          worked_hours: record.worked_hours,
          hours: record.hours,
          // CRITICAL: Show the status comparison results
          statusComparisons: {
            isHadir: record.status === 'Hadir',
            isTepatWaktu: record.status === 'Tepat Waktu', 
            isTerlambat: record.status === 'Terlambat',
            attendedHoursGreaterThanZero: attendedHours > 0
          }
        });
      }
    });
    
    console.log('📊 Hours calculation summary:', {
      totalScheduledHours,
      totalAttendedHours,
      recordsProcessed: monthlyData.length,
      // CRITICAL: Show which records contributed to totals
      contributingRecords: monthlyData.filter(r => {
        const attendedHours = r.actual_hours || r.worked_hours || 0;
        return (r.status === 'Hadir' || r.status === 'Tepat Waktu' || r.status === 'Terlambat') && attendedHours > 0;
      }).length
    });
    
    // CRITICAL: Debug why we might get zeros
    if (totalAttendedHours === 0 && monthlyData.length > 0) {
      console.error('🚨 ZERO HOURS DEBUG: Why are attended hours zero?');
      console.log('🔍 COMPREHENSIVE STATUS AND HOURS DEBUG:');
      
      monthlyData.forEach((record, i) => {
        const attendedHours = record.actual_hours || record.worked_hours || 0;
        const isValidStatus = record.status === 'Hadir' || record.status === 'Tepat Waktu' || record.status === 'Terlambat';
        
        // Show EXACT character comparison
        console.log(`🔍 Record ${i + 1} DETAILED DEBUG:`, {
          date: record.date,
          status: {
            value: `"${record.status}"`,
            length: record.status?.length,
            charCodes: record.status ? Array.from(record.status).map(c => `${c}:${c.charCodeAt(0)}`).join(' ') : 'undefined',
            comparisons: {
              equalsHadir: record.status === 'Hadir',
              equalsTepatWaktu: record.status === 'Tepat Waktu',
              equalsTerlambat: record.status === 'Terlambat',
              // Check for potential whitespace or different characters
              trimmedHadir: record.status?.trim() === 'Hadir',
              includesHadir: record.status?.includes('Hadir'),
              lowerCasePresent: record.status?.toLowerCase().includes('present'),
              lowerCaseHadir: record.status?.toLowerCase().includes('hadir')
            }
          },
          isValidStatus,
          hours: {
            actual_hours: record.actual_hours,
            worked_hours: record.worked_hours,
            finalAttendedHours: attendedHours,
            isGreaterThanZero: attendedHours > 0
          },
          wouldContribute: isValidStatus && attendedHours > 0,
          // Show all available fields
          allFields: Object.keys(record).map(key => `${key}: ${typeof record[key]} = ${record[key]}`)
        });
      });
      
      // Try alternative status matching strategies
      console.log('🔧 TRYING ALTERNATIVE STATUS MATCHING:');
      const alternativePresent = monthlyData.filter(r => {
        const status = r.status?.toLowerCase()?.trim();
        return status?.includes('hadir') || status?.includes('present') || status?.includes('on_time') || status?.includes('tepat');
      });
      console.log('Alternative matching found:', alternativePresent.length, 'present records');
    }
    
    // ADDITIONAL: Debug if we get zero present days but have hours
    if (presentDays === 0 && totalAttendedHours > 0) {
      console.error('🚨 WEIRD STATE: Zero present days but non-zero hours!');
    }

    // Calculate final metrics
    const hoursShortage = Math.max(0, totalScheduledHours - totalAttendedHours);
    
    // UNIFIED PERCENTAGE CALCULATION (Hours-based) with DEBUG
    console.log('🎯 FINAL CALCULATION DEBUG:', {
      totalScheduledHours,
      totalAttendedHours,
      division: totalScheduledHours > 0 ? totalAttendedHours / totalScheduledHours : 0,
      percentage: totalScheduledHours > 0 ? (totalAttendedHours / totalScheduledHours) * 100 : 0,
      rounded: totalScheduledHours > 0 ? Math.round((totalAttendedHours / totalScheduledHours) * 100) : 0
    });
    
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