// Type definitions for Dynamic Progress Bar System

export interface DynamicDuration {
  duration: number;
  easing: string;
  stagger: number;
}

export interface ProgressBarState {
  currentValue: number;
  percentage: number;
  targetPercentage: number;
  isAnimating: boolean;
  hasCompleted: boolean;
}

export interface ProgressBarActions {
  start: () => void;
  reset: () => void;
  dynamicDuration: DynamicDuration;
}

export interface UseProgressBarReturn extends ProgressBarState, ProgressBarActions {}

export interface BaseProgressBarProps {
  value: number;
  maxValue?: number;
  label?: string;
  className?: string;
  showPercentage?: boolean;
  showValue?: boolean;
  accessibilityLabel?: string;
  onAnimationComplete?: () => void;
  delay?: number;
}

export interface GradientConfig {
  gradientFrom: string;
  gradientTo: string;
  gradientVia?: string;
}

export interface DynamicProgressBarProps extends BaseProgressBarProps, GradientConfig {}

export interface MedicalProgressBarProps {
  label?: string;
  delay?: number;
}

export interface AttendanceProgressBarProps extends MedicalProgressBarProps {
  attendanceRate: number;
}

export interface JaspelProgressBarProps extends MedicalProgressBarProps {
  currentJaspel: number;
  targetJaspel: number;
}

export type PerformanceVariant = 'success' | 'warning' | 'info' | 'critical';

export interface PerformanceProgressBarProps extends MedicalProgressBarProps {
  performance: number;
  variant?: PerformanceVariant;
}

export interface WeightedMetric {
  value: number;
  weight: number;
}

export interface CompositeProgressProps {
  metrics: WeightedMetric[];
  delay?: number;
  onComplete?: () => void;
}

export interface MedicalDashboardData {
  attendanceRate?: number;
  currentJaspel?: number;
  targetJaspel?: number;
  patientSatisfaction?: number;
  procedureSuccess?: number;
  compositeScore?: number;
}

export interface MedicalProgressDashboardProps extends MedicalDashboardData {
  onProgressComplete?: (metric: string) => void;
}

export interface AnimationThresholds {
  fast: { min: number; max: number; duration: [number, number] };
  medium: { min: number; max: number; duration: [number, number] };
  slow: { min: number; max: number; duration: [number, number] };
  impactful: { min: number; max: number; duration: [number, number] };
}

export interface EasingFunctions {
  fast: string;
  medium: string;
  slow: string;
  impactful: string;
}

export interface ProgressBarConfig {
  thresholds: AnimationThresholds;
  easingFunctions: EasingFunctions;
  staggerDelays: number[];
  accessibilitySettings: {
    reducedMotion: boolean;
    highContrast: boolean;
    announceProgress: boolean;
  };
}

export interface CelebrationConfig {
  triggerThreshold: number;
  duration: number;
  animations: string[];
}

export interface PerformanceMetrics {
  animationStartTime: number;
  animationEndTime: number;
  actualDuration: number;
  targetDuration: number;
  frameRate: number;
  dropped_frames: number;
}

export interface ValidationResult {
  isValid: boolean;
  errors: string[];
  warnings: string[];
}

// Utility type for medical context
export interface MedicalContext {
  department: string;
  role: 'doctor' | 'nurse' | 'admin';
  shiftType: 'morning' | 'afternoon' | 'night';
  criticalMetrics: string[];
}

// Animation lifecycle hooks
export interface AnimationLifecycle {
  onStart?: () => void;
  onProgress?: (percentage: number) => void;
  onComplete?: () => void;
  onError?: (error: Error) => void;
}

// Progress bar factory interface
export interface ProgressBarFactory {
  createAttendanceBar: (props: AttendanceProgressBarProps) => React.ComponentType;
  createJaspelBar: (props: JaspelProgressBarProps) => React.ComponentType;
  createPerformanceBar: (props: PerformanceProgressBarProps) => React.ComponentType;
  createCompositeBar: (props: CompositeProgressProps) => React.ComponentType;
}

// Export default configuration
export const DEFAULT_PROGRESS_CONFIG: ProgressBarConfig = {
  thresholds: {
    fast: { min: 0, max: 25, duration: [300, 400] },
    medium: { min: 25, max: 50, duration: [500, 600] },
    slow: { min: 50, max: 75, duration: [700, 800] },
    impactful: { min: 75, max: 100, duration: [900, 1200] }
  },
  easingFunctions: {
    fast: 'cubic-bezier(0.4, 0, 0.2, 1)',
    medium: 'cubic-bezier(0.25, 0.1, 0.25, 1)',
    slow: 'cubic-bezier(0.165, 0.84, 0.44, 1)',
    impactful: 'cubic-bezier(0.19, 1, 0.22, 1)'
  },
  staggerDelays: [50, 100, 150, 200],
  accessibilitySettings: {
    reducedMotion: false,
    highContrast: false,
    announceProgress: true
  }
};

export const MEDICAL_CELEBRATION_CONFIG: CelebrationConfig = {
  triggerThreshold: 90,
  duration: 3000,
  animations: ['bounce-gentle', 'shimmer', 'glow']
};