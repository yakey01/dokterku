/**
 * Unified Schedule Types and Interfaces
 * Consolidates dokter and paramedis schedule types into a unified system
 */

// Base types
export type ScheduleStatus = 'upcoming' | 'active' | 'expired' | 'completed' | 'cancelled';
export type ShiftType = 'pagi' | 'siang' | 'malam' | 'emergency';
export type DifficultyLevel = 'easy' | 'medium' | 'hard' | 'legendary';
export type ScheduleVariant = 'dokter' | 'paramedis';
export type BadgePriority = 'critical' | 'important' | 'normal' | 'low';

// Attendance tracking
export interface AttendanceRecord {
  check_in_time?: string;
  check_out_time?: string;
  status?: 'not_started' | 'checked_in' | 'checked_out' | 'completed' | 'expired';
}

// Shift template
export interface ShiftTemplate {
  id: number;
  nama_shift: string;
  jam_masuk: string;
  jam_pulang: string;
}

// Base schedule interface - common fields
export interface BaseSchedule {
  id: string | number;
  date: string;           // Display date
  full_date: string;      // ISO date string
  day_name: string;       // Day of week
  time: string;           // Display time range
  location: string;       // Work location
  employee_name: string;  // Employee name
  peran: string;          // Role/position
  shift_template?: ShiftTemplate;
  attendance?: AttendanceRecord;
}

// Dokter-specific schedule interface
export interface DokterSchedule extends BaseSchedule {
  title: string;
  subtitle: string;
  type: 'regular' | 'urgent' | 'special' | 'training';
  difficulty: DifficultyLevel;
  status: 'available' | 'in-progress' | 'completed' | 'locked';
  status_jaga: string;
  description: string;
  requirements?: string[];
}

// Paramedis-specific schedule interface
export interface ParamedisSchedule extends BaseSchedule {
  tanggal: string;        // Display date (Indonesian format)
  waktu: string;          // Display time
  lokasi: string;         // Location
  jenis: ShiftType;       // Shift type
  status: 'scheduled' | 'completed' | 'missed';
}

// Unified schedule type (can be either dokter or paramedis)
export type UnifiedSchedule = DokterSchedule | ParamedisSchedule;

// Badge configuration
export interface BadgeConfig {
  text: string;
  icon: React.ComponentType<any>;
  gradient: string;
  textColor: string;
  borderColor: string;
  glowColor: string;
  bgGlow: string;
  pulse: string;
  priority: BadgePriority;
}

// Performance metrics
export interface PerformanceMetrics {
  renderTime: number;
  apiResponseTime: number;
  cacheHits: number;
  totalRequests: number;
  memoryUsage: number;
}

// API response wrapper
export interface ScheduleAPIResponse<T = any> {
  success: boolean;
  data: T;
  message?: string;
  meta?: {
    total: number;
    page?: number;
    limit?: number;
  };
}

// Schedule statistics
export interface ScheduleStats {
  total: number;
  upcoming: number;
  active: number;
  completed: number;
  expired: number;
  cancelled?: number;
}

// Cache entry interface
export interface CacheEntry<T = any> {
  data: T;
  timestamp: number;
  ttl?: number;
}

// Device detection
export interface DeviceInfo {
  isMobile: boolean;
  isTablet: boolean;
  isDesktop: boolean;
  orientation: 'portrait' | 'landscape';
  screenSize: 'sm' | 'md' | 'lg' | 'xl';
}

// Accessibility context
export interface AccessibilityContext {
  announcements: (message: string, priority?: 'polite' | 'assertive') => void;
  focusManagement: {
    moveFocus: (elementId: string) => void;
    trapFocus: (containerId: string) => void;
    releaseFocus: () => void;
  };
  keyboardNavigation: {
    handleKeyDown: (event: KeyboardEvent) => void;
    shortcuts: Record<string, () => void>;
  };
}

// Props interfaces for components
export interface ScheduleCardProps {
  schedule: UnifiedSchedule;
  variant: ScheduleVariant;
  onEdit?: (id: string | number) => void;
  onCancel?: (id: string | number) => void;
  onTouchStart?: (e: React.TouchEvent, id: string | number) => void;
  onTouchEnd?: (e: React.TouchEvent) => void;
  className?: string;
}

export interface GamingBadgeProps {
  status: ScheduleStatus;
  attendance?: AttendanceRecord;
  variant: ScheduleVariant;
  className?: string;
}

export interface StatsDashboardProps {
  stats: ScheduleStats;
  variant: 'gaming' | 'professional';
  performanceMetrics?: PerformanceMetrics;
  className?: string;
}

// Hook return types
export interface UseScheduleDataReturn {
  schedules: UnifiedSchedule[];
  loading: boolean;
  error: string | null;
  stats: ScheduleStats;
  refresh: () => Promise<void>;
  clearError: () => void;
}

export interface UsePerformanceMonitoringReturn {
  metrics: PerformanceMetrics;
  startMeasure: (name: string) => void;
  endMeasure: (name: string) => void;
  updateMetric: (key: keyof PerformanceMetrics, value: number) => void;
  resetMetrics: () => void;
}

export interface UseCacheReturn<T = any> {
  get: (key: string) => T | null;
  set: (key: string, data: T, ttl?: number) => void;
  clear: (key?: string) => void;
  size: number;
  hitRate: number;
}

// Type guards
export const isDokterSchedule = (schedule: UnifiedSchedule): schedule is DokterSchedule => {
  return 'title' in schedule && 'difficulty' in schedule;
};

export const isParamedisSchedule = (schedule: UnifiedSchedule): schedule is ParamedisSchedule => {
  return 'tanggal' in schedule && 'jenis' in schedule;
};

// Utility types
export type ScheduleTransformer<T extends UnifiedSchedule> = (
  apiData: any,
  attendanceMap?: Map<string | number, AttendanceRecord>
) => T[];

export type BadgeConfigGenerator = (
  status: ScheduleStatus,
  attendance?: AttendanceRecord,
  variant?: ScheduleVariant
) => BadgeConfig;

export type ScheduleFilterPredicate = (schedule: UnifiedSchedule) => boolean;

export type ScheduleComparator = (a: UnifiedSchedule, b: UnifiedSchedule) => number;