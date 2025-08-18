/**
 * Unified API Response Handling Patterns
 * Standardized API handling for schedule data across dokter and paramedis components
 */

import { 
  ScheduleAPIResponse, 
  UnifiedSchedule, 
  DokterSchedule, 
  ParamedisSchedule, 
  AttendanceRecord,
  ScheduleVariant 
} from './types';
import { formatTimeFromString, getShiftType, getShiftSubtitle } from './utils';

/**
 * Base API client configuration
 */
export class ScheduleAPIClient {
  private baseURL: string;
  private defaultHeaders: Record<string, string>;

  constructor(baseURL: string = '/api/v2/dashboards') {
    this.baseURL = baseURL;
    this.defaultHeaders = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
  }

  private getCSRFToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  private getHeaders(additionalHeaders: Record<string, string> = {}): Record<string, string> {
    return {
      ...this.defaultHeaders,
      'X-CSRF-TOKEN': this.getCSRFToken(),
      ...additionalHeaders
    };
  }

  /**
   * Enhanced fetch with error handling and performance tracking
   */
  private async fetchWithRetry(
    endpoint: string, 
    options: RequestInit = {},
    retries: number = 1
  ): Promise<Response> {
    const startTime = performance.now();
    
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        ...options,
        headers: this.getHeaders(options.headers as Record<string, string>),
        credentials: 'same-origin'
      });

      const endTime = performance.now();
      console.log(`⚡ API ${endpoint}: ${(endTime - startTime).toFixed(0)}ms`);

      if (!response.ok) {
        throw new APIError(response.status, response.statusText, endpoint);
      }

      return response;
    } catch (error) {
      if (retries > 0 && error instanceof TypeError) {
        console.warn(`🔄 Retrying API call: ${endpoint}`);
        await new Promise(resolve => setTimeout(resolve, 1000));
        return this.fetchWithRetry(endpoint, options, retries - 1);
      }
      throw error;
    }
  }

  /**
   * Get schedule data for dokter variant
   */
  async getDokterSchedules(includeAttendance: boolean = true): Promise<ScheduleAPIResponse<{
    calendar_events: any[];
    weekly_schedule: any[];
    attendance_records?: any[];
    schedule_stats?: any;
  }>> {
    const cacheBuster = includeAttendance ? `?include_attendance=true&t=${Date.now()}` : '';
    const response = await this.fetchWithRetry(`/dokter/jadwal-jaga${cacheBuster}`);
    return response.json();
  }

  /**
   * Get schedule data for paramedis variant
   */
  async getParamedisSchedules(includeAttendance: boolean = true): Promise<ScheduleAPIResponse<{
    calendar_events: any[];
    weekly_schedule: any[];
    attendance_records?: any[];
    schedule_stats?: any;
  }>> {
    const cacheBuster = includeAttendance ? `?include_attendance=true&t=${Date.now()}` : '';
    const response = await this.fetchWithRetry(`/paramedis/jadwal-jaga${cacheBuster}`);
    return response.json();
  }

  /**
   * Generic fallback API call
   */
  async getFallbackSchedules(variant: ScheduleVariant): Promise<any[]> {
    try {
      const endpoint = variant === 'dokter' ? '/test-dokter-schedules-api' : '/test-paramedis-schedules-api';
      const response = await fetch(endpoint, {
        credentials: 'include',
        headers: this.getHeaders()
      });
      
      if (response.ok) {
        return response.json();
      }
      return [];
    } catch (error) {
      console.error('Fallback API failed:', error);
      return [];
    }
  }
}

/**
 * Custom API Error class
 */
export class APIError extends Error {
  public status: number;
  public endpoint: string;

  constructor(status: number, message: string, endpoint: string) {
    super(message);
    this.name = 'APIError';
    this.status = status;
    this.endpoint = endpoint;
  }

  get userMessage(): string {
    switch (this.status) {
      case 401:
        return 'Authentication required. Please login again.';
      case 403:
        return 'You do not have permission to access this data.';
      case 404:
        return 'API endpoint not found. Please check configuration.';
      case 429:
        return 'Too many requests. Please wait and try again.';
      case 500:
        return 'Server error occurred. Please try again later.';
      default:
        return `API Error: ${this.status} - ${this.message}`;
    }
  }
}

/**
 * Data transformation utilities
 */
export class ScheduleDataTransformer {
  /**
   * Create attendance map for quick lookup
   */
  static createAttendanceMap(attendanceRecords: any[]): Map<string | number, AttendanceRecord> {
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

  /**
   * Transform API data to dokter schedule format
   */
  static transformToDokterSchedule(
    apiSchedules: any[],
    attendanceMap: Map<string | number, AttendanceRecord>
  ): DokterSchedule[] {
    return apiSchedules.map((schedule, index) => {
      const attendanceRecord = attendanceMap.get(schedule.id);
      const isCalendarEvent = schedule.start && schedule.title;
      const isWeeklySchedule = schedule.tanggal_jaga || schedule.shift_template;

      let dokterSchedule: DokterSchedule;

      if (isCalendarEvent) {
        const shiftInfo = schedule.shift_info || {};
        
        dokterSchedule = {
          id: schedule.id || `cal-${index + 1}`,
          date: new Date(schedule.start).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
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
          attendance: attendanceRecord
        };
      } else if (isWeeklySchedule) {
        const shiftTemplate = schedule.shift_template || {};
        const scheduleDate = schedule.tanggal_jaga || schedule.date;
        
        dokterSchedule = {
          id: schedule.id || `weekly-${index + 1}`,
          date: new Date(scheduleDate).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
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
          attendance: attendanceRecord
        };
      } else {
        // Fallback transformation
        dokterSchedule = {
          id: schedule.id || `fallback-${index + 1}`,
          date: new Date().toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
          full_date: new Date().toISOString(),
          day_name: new Date().toLocaleDateString('id-ID', { weekday: 'long' }),
          time: '08:00 - 16:00',
          location: 'Medical Unit',
          employee_name: 'Doctor',
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
          attendance: attendanceRecord
        };
      }

      return dokterSchedule;
    });
  }

  /**
   * Transform API data to paramedis schedule format
   */
  static transformToParamedisSchedule(
    apiSchedules: any[],
    attendanceMap: Map<string | number, AttendanceRecord>
  ): ParamedisSchedule[] {
    return apiSchedules.map((schedule, index) => {
      const attendanceRecord = attendanceMap.get(schedule.id);
      const isCalendarEvent = schedule.start && schedule.title;
      const isWeeklySchedule = schedule.tanggal_jaga || schedule.shift_template;

      let paramedisSchedule: ParamedisSchedule;

      if (isCalendarEvent) {
        const shiftInfo = schedule.shift_info || {};
        
        paramedisSchedule = {
          id: schedule.id || `cal-${index + 1}`,
          date: new Date(schedule.start).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
          full_date: schedule.start,
          day_name: new Date(schedule.start).toLocaleDateString('id-ID', { weekday: 'long' }),
          time: `${formatTimeFromString(shiftInfo.jam_masuk || '08:00')} - ${formatTimeFromString(shiftInfo.jam_pulang || '16:00')}`,
          location: shiftInfo.unit_kerja || schedule.description || 'Unit Kerja',
          employee_name: shiftInfo.employee_name || 'Paramedis Officer',
          peran: shiftInfo.peran || 'Paramedis',
          tanggal: new Date(schedule.start).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
          waktu: `${formatTimeFromString(shiftInfo.jam_masuk || '08:00')} - ${formatTimeFromString(shiftInfo.jam_pulang || '16:00')}`,
          lokasi: shiftInfo.unit_kerja || schedule.description || 'Unit Kerja',
          jenis: this.getShiftJenis(shiftInfo.nama_shift || 'pagi'),
          status: 'scheduled',
          shift_template: {
            id: shiftInfo.id || index + 1,
            nama_shift: shiftInfo.nama_shift || 'Pagi',
            jam_masuk: formatTimeFromString(shiftInfo.jam_masuk || '08:00'),
            jam_pulang: formatTimeFromString(shiftInfo.jam_pulang || '16:00')
          },
          attendance: attendanceRecord
        };
      } else if (isWeeklySchedule) {
        const shiftTemplate = schedule.shift_template || {};
        const scheduleDate = schedule.tanggal_jaga || schedule.date;
        
        paramedisSchedule = {
          id: schedule.id || `weekly-${index + 1}`,
          date: new Date(scheduleDate).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
          full_date: scheduleDate,
          day_name: new Date(scheduleDate).toLocaleDateString('id-ID', { weekday: 'long' }),
          time: `${formatTimeFromString(shiftTemplate.jam_masuk || '08:00')} - ${formatTimeFromString(shiftTemplate.jam_pulang || '16:00')}`,
          location: schedule.unit_kerja || 'Unit Kerja',
          employee_name: schedule.employee_name || 'Paramedis Officer',
          peran: schedule.peran || 'Paramedis',
          tanggal: new Date(scheduleDate).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
          waktu: `${formatTimeFromString(shiftTemplate.jam_masuk || '08:00')} - ${formatTimeFromString(shiftTemplate.jam_pulang || '16:00')}`,
          lokasi: schedule.unit_kerja || 'Unit Kerja',
          jenis: this.getShiftJenis(shiftTemplate.nama_shift || 'pagi'),
          status: 'scheduled',
          shift_template: {
            id: shiftTemplate.id || index + 1,
            nama_shift: shiftTemplate.nama_shift || 'Pagi',
            jam_masuk: formatTimeFromString(shiftTemplate.jam_masuk || '08:00'),
            jam_pulang: formatTimeFromString(shiftTemplate.jam_pulang || '16:00')
          },
          attendance: attendanceRecord
        };
      } else {
        // Fallback transformation
        paramedisSchedule = {
          id: schedule.id || `fallback-${index + 1}`,
          date: new Date().toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
          full_date: new Date().toISOString(),
          day_name: new Date().toLocaleDateString('id-ID', { weekday: 'long' }),
          time: '08:00 - 16:00',
          location: 'Unit Kerja',
          employee_name: 'Paramedis Officer',
          peran: 'Paramedis',
          tanggal: new Date().toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          }),
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
          attendance: attendanceRecord
        };
      }

      return paramedisSchedule;
    });
  }

  /**
   * Helper method to determine difficulty level for dokter schedules
   */
  private static getDifficultyLevel(namaShift: string): 'easy' | 'medium' | 'hard' | 'legendary' {
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

  /**
   * Helper method to determine shift jenis for paramedis schedules
   */
  private static getShiftJenis(namaShift: string): 'pagi' | 'siang' | 'malam' | 'emergency' {
    const shift = namaShift?.toLowerCase();
    if (shift?.includes('malam')) return 'malam';
    if (shift?.includes('siang')) return 'siang';
    if (shift?.includes('emergency')) return 'emergency';
    return 'pagi';
  }
}

/**
 * Unified schedule data fetcher
 */
export class ScheduleDataManager {
  private apiClient: ScheduleAPIClient;

  constructor() {
    this.apiClient = new ScheduleAPIClient();
  }

  /**
   * Fetch and transform schedule data for any variant
   */
  async fetchScheduleData(variant: ScheduleVariant, includeAttendance: boolean = true): Promise<{
    schedules: UnifiedSchedule[];
    attendanceMap: Map<string | number, AttendanceRecord>;
    rawData: any;
  }> {
    try {
      let apiResponse;
      
      if (variant === 'dokter') {
        apiResponse = await this.apiClient.getDokterSchedules(includeAttendance);
      } else {
        apiResponse = await this.apiClient.getParamedisSchedules(includeAttendance);
      }

      if (!apiResponse.success) {
        throw new Error(apiResponse.message || 'API returned unsuccessful response');
      }

      // Extract data components
      const weeklySchedules = apiResponse.data?.weekly_schedule || [];
      const calendarEvents = apiResponse.data?.calendar_events || [];
      const attendanceRecords = apiResponse.data?.attendance_records || [];

      // Combine and deduplicate schedules
      const combinedSchedules = [...weeklySchedules, ...calendarEvents];
      const seenIds = new Set();
      const uniqueSchedules = combinedSchedules.filter(schedule => {
        if (seenIds.has(schedule.id)) return false;
        seenIds.add(schedule.id);
        return true;
      });

      // Create attendance map
      const attendanceMap = ScheduleDataTransformer.createAttendanceMap(attendanceRecords);

      // Transform to appropriate format
      let schedules: UnifiedSchedule[];
      if (variant === 'dokter') {
        schedules = ScheduleDataTransformer.transformToDokterSchedule(uniqueSchedules, attendanceMap);
      } else {
        schedules = ScheduleDataTransformer.transformToParamedisSchedule(uniqueSchedules, attendanceMap);
      }

      return {
        schedules,
        attendanceMap,
        rawData: apiResponse.data
      };

    } catch (error) {
      console.error(`Failed to fetch ${variant} schedule data:`, error);
      
      // Try fallback API
      const fallbackSchedules = await this.apiClient.getFallbackSchedules(variant);
      const attendanceMap = new Map<string | number, AttendanceRecord>();
      
      let schedules: UnifiedSchedule[];
      if (variant === 'dokter') {
        schedules = ScheduleDataTransformer.transformToDokterSchedule(fallbackSchedules, attendanceMap);
      } else {
        schedules = ScheduleDataTransformer.transformToParamedisSchedule(fallbackSchedules, attendanceMap);
      }

      return {
        schedules,
        attendanceMap,
        rawData: { fallback: true, schedules: fallbackSchedules }
      };
    }
  }
}

// Export instances for easy import
export const scheduleAPI = new ScheduleAPIClient();
export const scheduleDataManager = new ScheduleDataManager();