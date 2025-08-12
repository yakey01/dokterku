// Type definitions for Dokter Attendance System

export interface AttendanceData {
  checkInTime: string | null;
  checkOutTime: string | null;
  workingHours: string;
  overtimeHours: string;
  breakTime: string;
  location: string;
}

export interface UserData {
  name: string;
  email: string;
  role: string;
}

export interface ScheduleData {
  todaySchedule: any;
  currentShift: any;
  workLocation: any;
  isOnDuty: boolean;
  canCheckIn: boolean;
  canCheckOut: boolean;
  validationMessage: string;
}

export interface AttendanceRecord {
  id?: number;
  jadwal_jaga_id?: number;
  time_in?: string;
  time_out?: string;
  date?: string;
  status?: string;
  hours?: string;
  checkIn?: string;
  checkOut?: string;
}

export interface ShiftInfo {
  id?: number;
  jadwal_jaga_id?: number;
  shift_template?: {
    jam_masuk: string;
    jam_pulang: string;
    durasi_jam?: number;
    nama?: string;
  };
  shift_info?: {
    jam_masuk?: string;
    jam_pulang?: string;
    jam_masuk_format?: string;
    jam_pulang_format?: string;
  };
}

export interface WorkLocation {
  id: number;
  name: string;
  latitude: number;
  longitude: number;
  radius: number;
  early_checkout_tolerance_minutes?: number;
  early_departure_tolerance_minutes?: number;
  checkout_after_shift_minutes?: number;
}

export interface LeaveForm {
  type: string;
  startDate: string;
  endDate: string;
  reason: string;
  days: number;
}

export interface MonthlyStats {
  totalDays: number;
  presentDays: number;
  lateDays: number;
  absentDays: number;
  overtimeHours: number;
  leaveBalance: number;
}

export interface LastKnownState {
  isCheckedIn: boolean;
  checkInTime: string | null;
  checkOutTime: string | null;
}

export interface ShiftTimes {
  startMs: number;
  endMs: number;
  startBufMs: number;
  endBufMs: number;
}