/**
 * Metrics Type Definitions
 * Centralized metric types for JASPEL, attendance, patients, and performance metrics
 */

// JASPEL (Jasa Pelayanan) metrics
export interface JaspelMetrics {
  currentMonth: number;
  previousMonth: number;
  growthPercentage: number;
  progressPercentage: number;
  target?: number;
  ytd?: number; // Year to date
  average?: number;
}

// Attendance metrics
export interface AttendanceMetrics {
  rate: number;
  daysPresent: number;
  totalDays: number;
  displayText: string;
  streak?: number;
  lateCount?: number;
  earlyLeaveCount?: number;
  overtimeHours?: number;
}

// Patient metrics
export interface PatientMetrics {
  today: number;
  thisWeek?: number;
  thisMonth: number;
  satisfaction?: number;
  averageConsultationTime?: number;
  returnRate?: number;
}

// Performance metrics
export interface PerformanceMetrics {
  overall: number;
  clinical: number;
  administrative: number;
  teamwork: number;
  punctuality: number;
  patientCare: number;
}

// Shift metrics
export interface ShiftMetrics {
  totalShifts: number;
  completedShifts: number;
  upcomingShifts: number;
  totalHours: number;
  overtimeHours?: number;
  nightShifts?: number;
  weekendShifts?: number;
}

// Gaming/Gamification metrics
export interface GamificationMetrics {
  level: number;
  experiencePoints: number;
  nextLevelXP: number;
  rank: number;
  badges: string[];
  achievements: Achievement[];
  dailyStreak: number;
  weeklyStreak: number;
  monthlyStreak: number;
}

// Achievement interface
export interface Achievement {
  id: string;
  name: string;
  description: string;
  icon: string;
  unlockedAt?: Date;
  progress?: number;
  target?: number;
}

// Procedure/Tindakan metrics
export interface ProcedureMetrics {
  total: number;
  successful: number;
  successRate: number;
  averageDuration: number;
  complexity: {
    simple: number;
    moderate: number;
    complex: number;
  };
}

// Financial metrics (extended JASPEL)
export interface FinancialMetrics {
  jaspel: JaspelMetrics;
  incentives: number;
  deductions: number;
  netIncome: number;
  taxWithheld: number;
  takeHome: number;
}

// Time-based metrics aggregation
export interface TimeBasedMetrics<T> {
  daily: T;
  weekly: T;
  monthly: T;
  yearly: T;
}

// Metric trend data
export interface MetricTrend {
  value: number;
  timestamp: Date;
  change: number;
  changePercentage: number;
  trend: 'up' | 'down' | 'stable';
}

// Metric history for charts
export interface MetricHistory {
  metric: string;
  data: MetricTrend[];
  average: number;
  min: number;
  max: number;
  standardDeviation?: number;
}

// Dashboard summary metrics
export interface DashboardSummaryMetrics {
  jaspel: JaspelMetrics;
  attendance: AttendanceMetrics;
  patients: PatientMetrics;
  performance?: PerformanceMetrics;
  shifts?: ShiftMetrics;
  procedures?: ProcedureMetrics;
}

// Metric calculation utilities
export class MetricCalculator {
  /**
   * Calculate growth percentage between two values
   */
  static calculateGrowth(current: number, previous: number): number {
    if (previous === 0) return current > 0 ? 100 : 0;
    return ((current - previous) / previous) * 100;
  }

  /**
   * Calculate progress towards target
   */
  static calculateProgress(current: number, target: number): number {
    if (target === 0) return 0;
    return Math.min((current / target) * 100, 100);
  }

  /**
   * Calculate attendance rate
   */
  static calculateAttendanceRate(present: number, total: number): number {
    if (total === 0) return 0;
    return (present / total) * 100;
  }

  /**
   * Format display text for attendance
   */
  static formatAttendanceText(present: number, total: number): string {
    return `${present} dari ${total} hari`;
  }

  /**
   * Calculate success rate
   */
  static calculateSuccessRate(successful: number, total: number): number {
    if (total === 0) return 0;
    return (successful / total) * 100;
  }

  /**
   * Determine trend direction
   */
  static determineTrend(current: number, previous: number): 'up' | 'down' | 'stable' {
    const threshold = 0.01; // 1% threshold for stability
    const change = current - previous;
    const changeRate = previous !== 0 ? Math.abs(change / previous) : 0;
    
    if (changeRate < threshold) return 'stable';
    return change > 0 ? 'up' : 'down';
  }

  /**
   * Calculate experience points for next level
   */
  static calculateNextLevelXP(currentLevel: number): number {
    // Exponential growth formula for XP requirements
    return Math.floor(100 * Math.pow(1.5, currentLevel));
  }

  /**
   * Format currency for JASPEL
   */
  static formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  }

  /**
   * Calculate daily streak
   */
  static calculateStreak(attendanceDates: Date[]): number {
    if (!attendanceDates || attendanceDates.length === 0) return 0;
    
    // Sort dates in descending order
    const sortedDates = attendanceDates.sort((a, b) => b.getTime() - a.getTime());
    
    let streak = 0;
    let currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);
    
    for (const date of sortedDates) {
      const attendanceDate = new Date(date);
      attendanceDate.setHours(0, 0, 0, 0);
      
      const diffDays = Math.floor((currentDate.getTime() - attendanceDate.getTime()) / (1000 * 60 * 60 * 24));
      
      if (diffDays === streak) {
        streak++;
      } else {
        break;
      }
    }
    
    return streak;
  }
}

// Metric validation utilities
export class MetricValidator {
  /**
   * Validate metric value is within acceptable range
   */
  static isValidPercentage(value: number): boolean {
    return value >= 0 && value <= 100;
  }

  /**
   * Validate positive number
   */
  static isPositiveNumber(value: number): boolean {
    return typeof value === 'number' && value >= 0 && isFinite(value);
  }

  /**
   * Validate metrics object has required fields
   */
  static validateMetrics(metrics: any): boolean {
    return metrics && 
      typeof metrics === 'object' &&
      this.isPositiveNumber(metrics.currentMonth) &&
      this.isPositiveNumber(metrics.previousMonth);
  }
}

// Default metric values
export const DEFAULT_JASPEL_METRICS: JaspelMetrics = {
  currentMonth: 0,
  previousMonth: 0,
  growthPercentage: 0,
  progressPercentage: 0,
  target: 0,
  ytd: 0,
  average: 0,
};

export const DEFAULT_ATTENDANCE_METRICS: AttendanceMetrics = {
  rate: 0,
  daysPresent: 0,
  totalDays: 0,
  displayText: '0 dari 0 hari',
  streak: 0,
  lateCount: 0,
  earlyLeaveCount: 0,
  overtimeHours: 0,
};

export const DEFAULT_PATIENT_METRICS: PatientMetrics = {
  today: 0,
  thisWeek: 0,
  thisMonth: 0,
  satisfaction: 0,
  averageConsultationTime: 0,
  returnRate: 0,
};

export const DEFAULT_PERFORMANCE_METRICS: PerformanceMetrics = {
  overall: 0,
  clinical: 0,
  administrative: 0,
  teamwork: 0,
  punctuality: 0,
  patientCare: 0,
};

export const DEFAULT_GAMIFICATION_METRICS: GamificationMetrics = {
  level: 1,
  experiencePoints: 0,
  nextLevelXP: 100,
  rank: 0,
  badges: [],
  achievements: [],
  dailyStreak: 0,
  weeklyStreak: 0,
  monthlyStreak: 0,
};