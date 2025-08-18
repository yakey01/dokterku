/**
 * Unified Dashboard Type Definitions
 * Consolidates all dashboard-related interfaces to eliminate code duplication
 * across multiple dashboard components.
 */

// Core user data interface
export interface UserData {
  name: string;
  email: string;
  greeting?: string;
  role?: string;
  initials?: string;
}

// Dashboard component props interface
export interface HolisticMedicalDashboardProps {
  userData?: UserData;
}

// Core dashboard metrics structure
export interface DashboardMetrics {
  jaspel: {
    currentMonth: number;
    previousMonth: number;
    growthPercentage: number;
    progressPercentage: number;
  };
  attendance: {
    rate: number;
    daysPresent: number;
    totalDays: number;
    displayText: string;
  };
  patients: {
    today: number;
    thisMonth: number;
  };
}

// Loading states for different dashboard sections
export interface LoadingState {
  dashboard: boolean;
  error: string | null;
}

// Enhanced loading states for multiple sections
export interface MultiSectionLoadingState {
  user: boolean;
  dashboard: boolean;
  attendance: boolean;
  jadwalJaga: boolean;
  jaspel: boolean;
  leaderboard: boolean;
}

// Error states for different dashboard sections
export interface ErrorState {
  user: string | null;
  dashboard: string | null;
  attendance: string | null;
  jadwalJaga: string | null;
  jaspel: string | null;
  leaderboard: string | null;
}

// Progress bar animation props
export interface ProgressBarAnimationProps {
  percentage: number;
  delay?: number;
  className?: string;
}

// Leaderboard doctor interface
export interface LeaderboardDoctor {
  id: number;
  rank: number;
  name: string;
  level: number;
  xp: number;
  attendance_rate: number;
  streak_days: number;
  total_hours: number;
  total_days: number;
  total_patients: number;
  consultation_hours: number;
  procedures_count: number;
  badge: string;
  month: number;
  year: number;
  monthLabel: string;
}

// Attendance history record
export interface AttendanceHistory {
  date: string;
  checkIn: string;
  checkOut: string;
  status: string;
  hours: string;
}

// Dashboard state for providers/contexts
export interface DashboardState {
  // Core data
  userData: UserData | null;
  dashboardData: any | null;
  attendanceData: any | null;
  jadwalJagaData: any | null;
  jaspelData: any | null;
  
  // Metrics
  metrics: DashboardMetrics;
  leaderboard: LeaderboardDoctor[];
  attendanceHistory: AttendanceHistory[];
  
  // Gaming elements
  doctorLevel: number;
  experiencePoints: number;
  dailyStreak: number;
  
  // Loading states
  isLoading: MultiSectionLoadingState;
  
  // Error states
  errors: ErrorState;
  
  // Metadata
  lastUpdated: {
    dashboard: number;
    leaderboard: number;
    attendance: number;
  };
  isInitialLoad: boolean;
}

// Dashboard actions interface
export interface DashboardActions {
  fetchDashboardData: () => Promise<void>;
  fetchLeaderboard: () => Promise<void>;
  fetchAttendanceHistory: () => Promise<void>;
  refreshAll: () => Promise<void>;
  clearErrors: () => void;
  resetState: () => void;
}

// Cache interface for dashboard data
export interface DashboardCache {
  isDashboardCacheValid: () => boolean;
  isLeaderboardCacheValid: () => boolean;
  isAttendanceCacheValid: () => boolean;
  clearCache: () => void;
  getCacheInfo: () => {
    dashboardLastUpdated: number;
    leaderboardLastUpdated: number;
    attendanceLastUpdated: number;
  };
}

// Time-based greeting configuration
export interface TimeBasedGreeting {
  greeting: string;
  colorGradient: string;
}

// Performance summary for quick access
export interface PerformanceSummary {
  attendanceRate: number;
  patientsThisMonth: number;
  jaspelGrowth: number;
  level: number;
  experience: number;
  streak: number;
}

// Computed dashboard data from hooks
export interface ComputedDashboardData {
  timeBasedGreeting: TimeBasedGreeting;
  jaspelMetrics: {
    growth: number;
    progress: number;
    current: number;
    previous: number;
  };
  attendanceDisplay: {
    rate: number;
    text: string;
    daysPresent: number;
    totalDays: number;
  };
  performanceSummary: PerformanceSummary;
  leaderboardTop3: LeaderboardDoctor[];
  attendanceHistory: AttendanceHistory[];
  hasData: boolean;
}

// Dashboard provider context type
export interface DashboardContextType {
  // Raw state data
  rawData: {
    metrics: DashboardMetrics;
    leaderboard: LeaderboardDoctor[];
    attendanceHistory: AttendanceHistory[];
    doctorLevel: number;
    experiencePoints: number;
    dailyStreak: number;
  };
  
  // Computed/formatted data
  computed: ComputedDashboardData;
  
  // Loading states
  loading: {
    isDashboardLoading: boolean;
    isLeaderboardLoading: boolean;
    isAttendanceLoading: boolean;
    isAnyLoading: boolean;
  };
  
  // Error states
  errors: {
    dashboardError: string | null;
    leaderboardError: string | null;
    attendanceError: string | null;
    hasErrors: boolean;
    allErrors: string[];
  };
  
  // Cache information
  cache: {
    dashboardCacheValid: boolean;
    leaderboardCacheValid: boolean;
    attendanceCacheValid: boolean;
    lastUpdated: {
      dashboard: string;
      leaderboard: string;
      attendance: string;
    };
  };
  
  // Actions
  actions: {
    refreshDashboard: () => Promise<void>;
    refreshLeaderboard: () => Promise<void>;
    refreshAll: () => Promise<void>;
    clearErrors: () => void;
    resetState: () => void;
  };
}

// Default values for dashboard metrics
export const DEFAULT_DASHBOARD_METRICS: DashboardMetrics = {
  jaspel: {
    currentMonth: 0,
    previousMonth: 0,
    growthPercentage: 0,
    progressPercentage: 0,
  },
  attendance: {
    rate: 0,
    daysPresent: 0,
    totalDays: 0,
    displayText: '0 dari 0 hari',
  },
  patients: {
    today: 0,
    thisMonth: 0,
  },
};

// Default loading state
export const DEFAULT_LOADING_STATE: MultiSectionLoadingState = {
  user: true,
  dashboard: true,
  attendance: true,
  jadwalJaga: true,
  jaspel: true,
  leaderboard: true,
};

// Default error state
export const DEFAULT_ERROR_STATE: ErrorState = {
  user: null,
  dashboard: null,
  attendance: null,
  jadwalJaga: null,
  jaspel: null,
  leaderboard: null,
};

// Type guards for runtime type checking
export const isDashboardMetrics = (obj: any): obj is DashboardMetrics => {
  return obj && 
    typeof obj === 'object' &&
    obj.jaspel &&
    obj.attendance &&
    obj.patients &&
    typeof obj.jaspel.currentMonth === 'number' &&
    typeof obj.attendance.rate === 'number' &&
    typeof obj.patients.today === 'number';
};

export const isLeaderboardDoctor = (obj: any): obj is LeaderboardDoctor => {
  return obj && 
    typeof obj === 'object' &&
    typeof obj.id === 'number' &&
    typeof obj.rank === 'number' &&
    typeof obj.name === 'string' &&
    typeof obj.attendance_rate === 'number';
};