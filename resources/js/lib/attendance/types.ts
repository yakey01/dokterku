/**
 * Unified Attendance Types
 * Shared type definitions for both dokter and paramedis attendance systems
 */

// Attendance variant types
export type AttendanceVariant = 'dokter' | 'paramedis';

// Base attendance record interface
export interface BaseAttendanceRecord {
  id: number | string;
  date: string;
  time_in: string | null;
  time_out: string | null;
  status: AttendanceStatus;
  location?: string;
  latitude?: number;
  longitude?: number;
  accuracy?: number;
}

// Attendance status enum
export type AttendanceStatus = 
  | 'present' 
  | 'late' 
  | 'completed' 
  | 'pending'
  | 'absent';

// Shift information interface
export interface ShiftInfo {
  id: number;
  nama_shift: string;
  jam_masuk: string;
  jam_pulang: string;
  shift_sequence?: number;
  is_available?: boolean;
  is_current?: boolean;
  can_checkin?: boolean;
  window_message?: string;
}

// Location data interface
export interface LocationData {
  lat: number;
  lng: number;
  accuracy?: number;
  address?: string;
  timestamp?: number;
}

// GPS manager integration
export interface GPSResult {
  latitude: number;
  longitude: number;
  accuracy: number;
  source: string;
  timestamp: number;
  cached: boolean;
  confidence: number;
}

// Attendance calculation result
export interface AttendanceCalculation {
  workingHours: string;
  hoursShortage: string;
  breakTime: string;
  overtimeHours?: string;
  totalMinutes: number;
  isActive: boolean;
}

// Multi-shift status for dokter variant
export interface MultiShiftStatus {
  can_check_in: boolean;
  can_check_out: boolean;
  current_shift?: ShiftInfo;
  next_shift?: ShiftInfo;
  today_attendances: BaseAttendanceRecord[];
  shifts_available: ShiftInfo[];
  max_shifts_reached: boolean;
  message: string;
}

// API response structure
export interface AttendanceApiResponse {
  status: string;
  message: string;
  can_check_in: boolean;
  can_check_out: boolean;
  attendance?: BaseAttendanceRecord;
  shifts?: ShiftInfo[];
  multi_shift_status?: MultiShiftStatus;
}

// Error types
export interface AttendanceError {
  type: 'network' | 'gps' | 'validation' | 'permission' | 'system';
  message: string;
  code?: string;
  details?: any;
}

// User data interface
export interface UserData {
  name: string;
  email: string;
  role: string;
  id?: number;
}

// Work location interface
export interface WorkLocation {
  id: number;
  name: string;
  latitude: number;
  longitude: number;
  radius: number;
  is_active: boolean;
  address?: string;
}

// Time format options
export interface TimeFormatOptions {
  locale?: string;
  hour12?: boolean;
  includeSeconds?: boolean;
  timeZone?: string;
}

// Validation result interface
export interface ValidationResult {
  isValid: boolean;
  message: string;
  errors?: string[];
  warnings?: string[];
}