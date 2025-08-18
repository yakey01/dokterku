/**
 * Schedule Hooks Index
 * Central export for all schedule-related hooks
 */

// Performance and monitoring hooks
export {
  usePerformanceMonitoring,
  useCache,
  useDevice,
  withPerformanceMonitoring,
  useTouchOptimization,
  useResponsiveClasses
} from '../hooks';

// Schedule management hooks
export {
  useScheduleManager,
  scheduleFilters,
  scheduleSorts
} from './useScheduleManager';

// Hook types
export type {
  UsePerformanceMonitoringReturn,
  UseCacheReturn,
  UseScheduleDataReturn
} from '../types';