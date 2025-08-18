/**
 * Unified Data Transformation Layer
 * Advanced data transformation utilities for schedule processing
 */

import { 
  UnifiedSchedule, 
  DokterSchedule, 
  ParamedisSchedule,
  AttendanceRecord,
  ScheduleVariant,
  ScheduleTransformer,
  ScheduleAPIResponse,
  isDokterSchedule,
  isParamedisSchedule
} from './types';
import { 
  formatTimeFromString,
  getShiftType,
  getShiftSubtitle,
  formatDateForDisplay,
  getCurrentTime
} from './utils';

/**
 * Advanced Schedule Transformer Class
 */
export class AdvancedScheduleTransformer {
  private static instance: AdvancedScheduleTransformer;
  private transformationCache: Map<string, any> = new Map();
  private metricsEnabled: boolean = true;

  private constructor() {}

  static getInstance(): AdvancedScheduleTransformer {
    if (!AdvancedScheduleTransformer.instance) {
      AdvancedScheduleTransformer.instance = new AdvancedScheduleTransformer();
    }
    return AdvancedScheduleTransformer.instance;
  }

  /**
   * Transform raw API response to unified schedules
   */
  transformAPIResponse<T extends UnifiedSchedule>(
    apiResponse: ScheduleAPIResponse,
    variant: ScheduleVariant,
    options: TransformationOptions = {}
  ): TransformationResult<T> {
    const startTime = performance.now();
    
    try {
      if (!apiResponse.success || !apiResponse.data) {
        throw new TransformationError('API response indicates failure', 'API_FAILURE');
      }

      const { 
        enableCache = true,
        preserveRawData = false,
        strictValidation = true,
        includeMetrics = this.metricsEnabled
      } = options;

      // Extract data components
      const weeklySchedules = apiResponse.data.weekly_schedule || [];
      const calendarEvents = apiResponse.data.calendar_events || [];
      const attendanceRecords = apiResponse.data.attendance_records || [];

      // Create attendance map
      const attendanceMap = this.createAttendanceMap(attendanceRecords);

      // Combine and deduplicate schedules
      const combinedSchedules = this.deduplicateSchedules([...weeklySchedules, ...calendarEvents]);

      // Transform to appropriate variant
      let transformedSchedules: T[];
      if (variant === 'dokter') {
        transformedSchedules = this.transformToDokterSchedules(combinedSchedules, attendanceMap) as T[];
      } else {
        transformedSchedules = this.transformToParamedisSchedules(combinedSchedules, attendanceMap) as T[];
      }

      // Validation
      if (strictValidation) {
        transformedSchedules = this.validateSchedules(transformedSchedules, variant);
      }

      // Sort by date
      transformedSchedules.sort((a, b) => new Date(a.full_date).getTime() - new Date(b.full_date).getTime());

      const endTime = performance.now();
      const metrics = includeMetrics ? {
        transformationTime: endTime - startTime,
        inputCount: combinedSchedules.length,
        outputCount: transformedSchedules.length,
        duplicatesRemoved: weeklySchedules.length + calendarEvents.length - combinedSchedules.length,
        attendanceRecordsProcessed: attendanceRecords.length,
        cacheUsed: false
      } : undefined;

      return {
        schedules: transformedSchedules,
        attendanceMap,
        rawData: preserveRawData ? apiResponse.data : undefined,
        metrics,
        errors: [],
        warnings: this.generateWarnings(transformedSchedules, attendanceRecords)
      };

    } catch (error) {
      const endTime = performance.now();
      throw new TransformationError(
        error instanceof Error ? error.message : 'Unknown transformation error',
        'TRANSFORM_FAILED',
        { transformationTime: endTime - startTime }
      );
    }
  }

  /**
   * Transform array of schedules between variants
   */
  transformScheduleVariant<TFrom extends UnifiedSchedule, TTo extends UnifiedSchedule>(
    schedules: TFrom[],
    fromVariant: ScheduleVariant,
    toVariant: ScheduleVariant
  ): TTo[] {
    if (fromVariant === toVariant) {
      return schedules as unknown as TTo[];
    }

    return schedules.map(schedule => {
      if (toVariant === 'dokter') {
        return this.convertToDocterSchedule(schedule) as TTo;
      } else {
        return this.convertToParamedisSchedule(schedule) as TTo;
      }
    });
  }

  /**
   * Batch transform multiple API responses
   */
  batchTransform(
    responses: Array<{ response: ScheduleAPIResponse; variant: ScheduleVariant; options?: TransformationOptions }>,
    globalOptions: BatchTransformOptions = {}
  ): BatchTransformationResult {
    const { 
      continueOnError = true,
      aggregateMetrics = true
    } = globalOptions;

    const results: Array<TransformationResult<any> | TransformationError> = [];
    const aggregatedMetrics = {
      totalTransformationTime: 0,
      totalInputCount: 0,
      totalOutputCount: 0,
      totalDuplicatesRemoved: 0,
      successCount: 0,
      errorCount: 0
    };

    for (const { response, variant, options } of responses) {
      try {
        const result = this.transformAPIResponse(response, variant, options);
        results.push(result);
        
        if (aggregateMetrics && result.metrics) {
          aggregatedMetrics.totalTransformationTime += result.metrics.transformationTime;
          aggregatedMetrics.totalInputCount += result.metrics.inputCount;
          aggregatedMetrics.totalOutputCount += result.metrics.outputCount;
          aggregatedMetrics.totalDuplicatesRemoved += result.metrics.duplicatesRemoved;
          aggregatedMetrics.successCount++;
        }
      } catch (error) {
        aggregatedMetrics.errorCount++;
        
        if (error instanceof TransformationError) {
          results.push(error);
        } else {
          results.push(new TransformationError(
            error instanceof Error ? error.message : 'Unknown error',
            'BATCH_TRANSFORM_FAILED'
          ));
        }

        if (!continueOnError) {
          break;
        }
      }
    }

    return {
      results,
      aggregatedMetrics: aggregateMetrics ? aggregatedMetrics : undefined,
      overallSuccess: aggregatedMetrics.errorCount === 0
    };
  }

  /**
   * Transform with intelligent fallbacks
   */
  transformWithFallbacks<T extends UnifiedSchedule>(
    primaryData: any[],
    fallbackData: any[],
    variant: ScheduleVariant,
    options: TransformationOptions = {}
  ): TransformationResult<T> {
    try {
      // Try primary transformation
      const mockApiResponse: ScheduleAPIResponse = {
        success: true,
        data: {
          weekly_schedule: primaryData.filter(item => !item.start),
          calendar_events: primaryData.filter(item => item.start),
          attendance_records: []
        }
      };

      return this.transformAPIResponse<T>(mockApiResponse, variant, options);
    } catch (error) {
      console.warn('Primary transformation failed, using fallbacks:', error);
      
      // Use fallback data
      try {
        const fallbackResponse: ScheduleAPIResponse = {
          success: true,
          data: {
            weekly_schedule: fallbackData,
            calendar_events: [],
            attendance_records: []
          }
        };

        const result = this.transformAPIResponse<T>(fallbackResponse, variant, options);
        result.warnings = result.warnings || [];
        result.warnings.push({
          type: 'FALLBACK_USED',
          message: 'Used fallback data due to primary transformation failure',
          severity: 'warning'
        });

        return result;
      } catch (fallbackError) {
        throw new TransformationError(
          'Both primary and fallback transformations failed',
          'ALL_TRANSFORMS_FAILED',
          { primaryError: error, fallbackError }
        );
      }
    }
  }

  // Private transformation methods

  private createAttendanceMap(attendanceRecords: any[]): Map<string | number, AttendanceRecord> {
    const attendanceMap = new Map<string | number, AttendanceRecord>();
    
    attendanceRecords.forEach((record: any) => {
      if (record.jadwal_jaga_id) {
        attendanceMap.set(record.jadwal_jaga_id, {
          check_in_time: record.time_in || record.check_in_time,
          check_out_time: record.time_out || record.check_out_time,
          status: record.status || 'not_started'
        });
      }
    });
    
    return attendanceMap;
  }

  private deduplicateSchedules(schedules: any[]): any[] {
    const seenIds = new Set();
    return schedules.filter(schedule => {
      if (seenIds.has(schedule.id)) {
        return false;
      }
      seenIds.add(schedule.id);
      return true;
    });
  }

  private transformToDokterSchedules(
    apiSchedules: any[], 
    attendanceMap: Map<string | number, AttendanceRecord>
  ): DokterSchedule[] {
    return apiSchedules.map((schedule, index) => {
      const attendanceRecord = attendanceMap.get(schedule.id);
      
      try {
        if (schedule.start && schedule.title) {
          // Calendar event format
          return this.transformCalendarEventToDokter(schedule, attendanceRecord, index);
        } else if (schedule.tanggal_jaga || schedule.shift_template) {
          // Weekly schedule format
          return this.transformWeeklyScheduleToDokter(schedule, attendanceRecord, index);
        } else {
          // Fallback format
          return this.createFallbackDokterSchedule(schedule, attendanceRecord, index);
        }
      } catch (error) {
        console.warn(`Failed to transform schedule ${schedule.id}:`, error);
        return this.createFallbackDokterSchedule(schedule, attendanceRecord, index);
      }
    });
  }

  private transformToParamedisSchedules(
    apiSchedules: any[],
    attendanceMap: Map<string | number, AttendanceRecord>
  ): ParamedisSchedule[] {
    return apiSchedules.map((schedule, index) => {
      const attendanceRecord = attendanceMap.get(schedule.id);
      
      try {
        if (schedule.start && schedule.title) {
          // Calendar event format
          return this.transformCalendarEventToParamedis(schedule, attendanceRecord, index);
        } else if (schedule.tanggal_jaga || schedule.shift_template) {
          // Weekly schedule format
          return this.transformWeeklyScheduleToParamedis(schedule, attendanceRecord, index);
        } else {
          // Fallback format
          return this.createFallbackParamedisSchedule(schedule, attendanceRecord, index);
        }
      } catch (error) {
        console.warn(`Failed to transform schedule ${schedule.id}:`, error);
        return this.createFallbackParamedisSchedule(schedule, attendanceRecord, index);
      }
    });
  }

  private transformCalendarEventToDokter(schedule: any, attendance?: AttendanceRecord, index: number = 0): DokterSchedule {
    const shiftInfo = schedule.shift_info || {};
    
    return {
      id: schedule.id || `cal-${index + 1}`,
      date: formatDateForDisplay(schedule.start),
      full_date: schedule.start,
      day_name: new Date(schedule.start).toLocaleDateString('id-ID', { weekday: 'long' }),
      time: `${formatTimeFromString(shiftInfo.jam_masuk || '08:00')} - ${formatTimeFromString(shiftInfo.jam_pulang || '16:00')}`,
      location: shiftInfo.unit_kerja || schedule.description || 'Medical Unit',
      employee_name: shiftInfo.employee_name || 'Doctor',
      peran: shiftInfo.peran || 'Dokter',
      title: schedule.title || shiftInfo.nama_shift || 'Medical Duty',
      subtitle: getShiftSubtitle(shiftInfo.nama_shift || 'pagi'),
      type: getShiftType(shiftInfo.nama_shift || 'pagi'),
      difficulty: this.getDifficultyLevel(shiftInfo.nama_shift || 'pagi'),
      status: 'available',
      status_jaga: schedule.status_jaga || 'Terjadwal',
      description: schedule.description || 'Medical duty assignment',
      shift_template: {
        id: shiftInfo.id || index + 1,
        nama_shift: shiftInfo.nama_shift || 'Pagi',
        jam_masuk: formatTimeFromString(shiftInfo.jam_masuk || '08:00'),
        jam_pulang: formatTimeFromString(shiftInfo.jam_pulang || '16:00')
      },
      attendance
    };
  }

  private transformWeeklyScheduleToDokter(schedule: any, attendance?: AttendanceRecord, index: number = 0): DokterSchedule {
    const shiftTemplate = schedule.shift_template || {};
    const scheduleDate = schedule.tanggal_jaga || schedule.date;
    
    return {
      id: schedule.id || `weekly-${index + 1}`,
      date: formatDateForDisplay(scheduleDate),
      full_date: scheduleDate,
      day_name: new Date(scheduleDate).toLocaleDateString('id-ID', { weekday: 'long' }),
      time: `${formatTimeFromString(shiftTemplate.jam_masuk || '08:00')} - ${formatTimeFromString(shiftTemplate.jam_pulang || '16:00')}`,
      location: schedule.unit_kerja || 'Medical Unit',
      employee_name: schedule.employee_name || 'Doctor',
      peran: schedule.peran || 'Dokter',
      title: shiftTemplate.nama_shift || 'Medical Duty',
      subtitle: getShiftSubtitle(shiftTemplate.nama_shift || 'pagi'),
      type: getShiftType(shiftTemplate.nama_shift || 'pagi'),
      difficulty: this.getDifficultyLevel(shiftTemplate.nama_shift || 'pagi'),
      status: 'available',
      status_jaga: schedule.status_jaga || 'Terjadwal',
      description: schedule.description || 'Medical duty assignment',
      shift_template: {
        id: shiftTemplate.id || index + 1,
        nama_shift: shiftTemplate.nama_shift || 'Pagi',
        jam_masuk: formatTimeFromString(shiftTemplate.jam_masuk || '08:00'),
        jam_pulang: formatTimeFromString(shiftTemplate.jam_pulang || '16:00')
      },
      attendance
    };
  }

  private createFallbackDokterSchedule(schedule: any, attendance?: AttendanceRecord, index: number = 0): DokterSchedule {
    const now = new Date();
    
    return {
      id: schedule.id || `fallback-${index + 1}`,
      date: formatDateForDisplay(now.toISOString()),
      full_date: now.toISOString(),
      day_name: now.toLocaleDateString('id-ID', { weekday: 'long' }),
      time: '08:00 - 16:00',
      location: 'Medical Unit',
      employee_name: schedule.employee_name || 'Doctor',
      peran: 'Dokter',
      title: 'Medical Duty',
      subtitle: 'General Outpatient Care',
      type: 'regular',
      difficulty: 'medium',
      status: 'available',
      status_jaga: 'Terjadwal',
      description: 'General medical duty',
      shift_template: {
        id: index + 1,
        nama_shift: 'Pagi',
        jam_masuk: '08:00',
        jam_pulang: '16:00'
      },
      attendance
    };
  }

  private transformCalendarEventToParamedis(schedule: any, attendance?: AttendanceRecord, index: number = 0): ParamedisSchedule {
    const shiftInfo = schedule.shift_info || {};
    const formattedDate = formatDateForDisplay(schedule.start);
    const timeRange = `${formatTimeFromString(shiftInfo.jam_masuk || '08:00')} - ${formatTimeFromString(shiftInfo.jam_pulang || '16:00')}`;
    
    return {
      id: schedule.id || `cal-${index + 1}`,
      date: formattedDate,
      full_date: schedule.start,
      day_name: new Date(schedule.start).toLocaleDateString('id-ID', { weekday: 'long' }),
      time: timeRange,
      location: shiftInfo.unit_kerja || schedule.description || 'Unit Kerja',
      employee_name: shiftInfo.employee_name || 'Paramedis Officer',
      peran: shiftInfo.peran || 'Paramedis',
      tanggal: formattedDate,
      waktu: timeRange,
      lokasi: shiftInfo.unit_kerja || schedule.description || 'Unit Kerja',
      jenis: this.getShiftJenis(shiftInfo.nama_shift || 'pagi'),
      status: 'scheduled',
      shift_template: {
        id: shiftInfo.id || index + 1,
        nama_shift: shiftInfo.nama_shift || 'Pagi',
        jam_masuk: formatTimeFromString(shiftInfo.jam_masuk || '08:00'),
        jam_pulang: formatTimeFromString(shiftInfo.jam_pulang || '16:00')
      },
      attendance
    };
  }

  private transformWeeklyScheduleToParamedis(schedule: any, attendance?: AttendanceRecord, index: number = 0): ParamedisSchedule {
    const shiftTemplate = schedule.shift_template || {};
    const scheduleDate = schedule.tanggal_jaga || schedule.date;
    const formattedDate = formatDateForDisplay(scheduleDate);
    const timeRange = `${formatTimeFromString(shiftTemplate.jam_masuk || '08:00')} - ${formatTimeFromString(shiftTemplate.jam_pulang || '16:00')}`;
    
    return {
      id: schedule.id || `weekly-${index + 1}`,
      date: formattedDate,
      full_date: scheduleDate,
      day_name: new Date(scheduleDate).toLocaleDateString('id-ID', { weekday: 'long' }),
      time: timeRange,
      location: schedule.unit_kerja || 'Unit Kerja',
      employee_name: schedule.employee_name || 'Paramedis Officer',
      peran: schedule.peran || 'Paramedis',
      tanggal: formattedDate,
      waktu: timeRange,
      lokasi: schedule.unit_kerja || 'Unit Kerja',
      jenis: this.getShiftJenis(shiftTemplate.nama_shift || 'pagi'),
      status: 'scheduled',
      shift_template: {
        id: shiftTemplate.id || index + 1,
        nama_shift: shiftTemplate.nama_shift || 'Pagi',
        jam_masuk: formatTimeFromString(shiftTemplate.jam_masuk || '08:00'),
        jam_pulang: formatTimeFromString(shiftTemplate.jam_pulang || '16:00')
      },
      attendance
    };
  }

  private createFallbackParamedisSchedule(schedule: any, attendance?: AttendanceRecord, index: number = 0): ParamedisSchedule {
    const now = new Date();
    const formattedDate = formatDateForDisplay(now.toISOString());
    
    return {
      id: schedule.id || `fallback-${index + 1}`,
      date: formattedDate,
      full_date: now.toISOString(),
      day_name: now.toLocaleDateString('id-ID', { weekday: 'long' }),
      time: '08:00 - 16:00',
      location: 'Unit Kerja',
      employee_name: schedule.employee_name || 'Paramedis Officer',
      peran: 'Paramedis',
      tanggal: formattedDate,
      waktu: '08:00 - 16:00',
      lokasi: 'Unit Kerja',
      jenis: 'pagi',
      status: 'scheduled',
      shift_template: {
        id: index + 1,
        nama_shift: 'Pagi',
        jam_masuk: '08:00',
        jam_pulang: '16:00'
      },
      attendance
    };
  }

  private convertToDocterSchedule(schedule: UnifiedSchedule): DokterSchedule {
    if (isDokterSchedule(schedule)) {
      return schedule;
    }

    // Convert from ParamedisSchedule to DokterSchedule
    const paramedisSchedule = schedule as ParamedisSchedule;
    
    return {
      id: paramedisSchedule.id,
      date: paramedisSchedule.date,
      full_date: paramedisSchedule.full_date,
      day_name: paramedisSchedule.day_name,
      time: paramedisSchedule.time,
      location: paramedisSchedule.location,
      employee_name: paramedisSchedule.employee_name,
      peran: 'Dokter',
      title: `Medical Duty - ${paramedisSchedule.jenis}`,
      subtitle: getShiftSubtitle(paramedisSchedule.jenis),
      type: getShiftType(paramedisSchedule.jenis),
      difficulty: this.getDifficultyLevel(paramedisSchedule.jenis),
      status: 'available',
      status_jaga: paramedisSchedule.status === 'completed' ? 'Selesai' : 'Terjadwal',
      description: `Converted from paramedis schedule: ${paramedisSchedule.jenis} shift`,
      shift_template: paramedisSchedule.shift_template,
      attendance: paramedisSchedule.attendance
    };
  }

  private convertToParamedisSchedule(schedule: UnifiedSchedule): ParamedisSchedule {
    if (isParamedisSchedule(schedule)) {
      return schedule;
    }

    // Convert from DokterSchedule to ParamedisSchedule
    const dokterSchedule = schedule as DokterSchedule;
    
    return {
      id: dokterSchedule.id,
      date: dokterSchedule.date,
      full_date: dokterSchedule.full_date,
      day_name: dokterSchedule.day_name,
      time: dokterSchedule.time,
      location: dokterSchedule.location,
      employee_name: dokterSchedule.employee_name,
      peran: 'Paramedis',
      tanggal: dokterSchedule.date,
      waktu: dokterSchedule.time,
      lokasi: dokterSchedule.location,
      jenis: this.getShiftJenis(dokterSchedule.shift_template?.nama_shift || 'pagi'),
      status: dokterSchedule.status === 'completed' ? 'completed' : 'scheduled',
      shift_template: dokterSchedule.shift_template,
      attendance: dokterSchedule.attendance
    };
  }

  private getDifficultyLevel(namaShift: string): 'easy' | 'medium' | 'hard' | 'legendary' {
    switch (namaShift?.toLowerCase()) {
      case 'malam':
      case 'emergency':
        return 'hard';
      case 'siang':
        return 'medium';
      case 'training':
      case 'workshop':
        return 'legendary';
      default:
        return 'easy';
    }
  }

  private getShiftJenis(namaShift: string): 'pagi' | 'siang' | 'malam' | 'emergency' {
    const shift = namaShift?.toLowerCase();
    if (shift?.includes('malam')) return 'malam';
    if (shift?.includes('siang')) return 'siang';
    if (shift?.includes('emergency')) return 'emergency';
    return 'pagi';
  }

  private validateSchedules<T extends UnifiedSchedule>(schedules: T[], variant: ScheduleVariant): T[] {
    return schedules.filter(schedule => {
      // Basic validation
      if (!schedule.id || !schedule.full_date || !schedule.employee_name) {
        console.warn('Invalid schedule detected:', schedule);
        return false;
      }

      // Variant-specific validation
      if (variant === 'dokter' && !isDokterSchedule(schedule)) {
        console.warn('Expected dokter schedule:', schedule);
        return false;
      }

      if (variant === 'paramedis' && !isParamedisSchedule(schedule)) {
        console.warn('Expected paramedis schedule:', schedule);
        return false;
      }

      return true;
    });
  }

  private generateWarnings(schedules: UnifiedSchedule[], attendanceRecords: any[]): TransformationWarning[] {
    const warnings: TransformationWarning[] = [];

    // Check for missing attendance data
    const schedulesWithAttendance = schedules.filter(s => s.attendance).length;
    if (attendanceRecords.length > 0 && schedulesWithAttendance === 0) {
      warnings.push({
        type: 'MISSING_ATTENDANCE',
        message: 'Attendance records found but no schedules have attendance data',
        severity: 'warning'
      });
    }

    // Check for duplicate employee assignments
    const employeeSchedules = new Map<string, number>();
    schedules.forEach(schedule => {
      const count = employeeSchedules.get(schedule.employee_name) || 0;
      employeeSchedules.set(schedule.employee_name, count + 1);
    });

    for (const [employee, count] of employeeSchedules.entries()) {
      if (count > 5) { // More than 5 schedules might indicate duplicates
        warnings.push({
          type: 'POTENTIAL_DUPLICATES',
          message: `Employee ${employee} has ${count} schedules, check for duplicates`,
          severity: 'info'
        });
      }
    }

    return warnings;
  }
}

// Types for transformation system

export interface TransformationOptions {
  enableCache?: boolean;
  preserveRawData?: boolean;
  strictValidation?: boolean;
  includeMetrics?: boolean;
}

export interface BatchTransformOptions {
  continueOnError?: boolean;
  aggregateMetrics?: boolean;
}

export interface TransformationMetrics {
  transformationTime: number;
  inputCount: number;
  outputCount: number;
  duplicatesRemoved: number;
  attendanceRecordsProcessed: number;
  cacheUsed: boolean;
}

export interface TransformationWarning {
  type: 'MISSING_ATTENDANCE' | 'POTENTIAL_DUPLICATES' | 'FALLBACK_USED' | 'VALIDATION_FAILED';
  message: string;
  severity: 'info' | 'warning' | 'error';
}

export interface TransformationResult<T extends UnifiedSchedule> {
  schedules: T[];
  attendanceMap: Map<string | number, AttendanceRecord>;
  rawData?: any;
  metrics?: TransformationMetrics;
  errors: TransformationError[];
  warnings: TransformationWarning[];
}

export interface BatchTransformationResult {
  results: Array<TransformationResult<any> | TransformationError>;
  aggregatedMetrics?: {
    totalTransformationTime: number;
    totalInputCount: number;
    totalOutputCount: number;
    totalDuplicatesRemoved: number;
    successCount: number;
    errorCount: number;
  };
  overallSuccess: boolean;
}

export class TransformationError extends Error {
  constructor(
    message: string,
    public code: string,
    public metadata?: any
  ) {
    super(message);
    this.name = 'TransformationError';
  }
}

// Export singleton instance
export const scheduleTransformer = AdvancedScheduleTransformer.getInstance();