/**
 * useScheduleManager Hook
 * Unified schedule management hook for both dokter and paramedis variants
 */

import { useState, useCallback, useEffect, useRef } from 'react';
import { 
  UnifiedSchedule, 
  ScheduleVariant, 
  UseScheduleDataReturn,
  ScheduleStats,
  PerformanceMetrics,
  AttendanceRecord
} from '../types';
import { 
  calculateScheduleStats, 
  sortSchedulesByDate, 
  filterSchedulesByStatus,
  getScheduleCompletionPercentage,
  getScheduleStatus
} from '../utils';
import { ScheduleDataManager, APIError } from '../api';
import { usePerformanceMonitoring, useCache } from '../hooks';

interface UseScheduleManagerOptions {
  variant: ScheduleVariant;
  enableCache?: boolean;
  cacheTTL?: number;
  autoRefresh?: boolean;
  refreshInterval?: number;
  includeAttendance?: boolean;
  onError?: (error: string) => void;
  onSuccess?: (schedules: UnifiedSchedule[]) => void;
}

interface UseScheduleManagerReturn extends UseScheduleDataReturn {
  // Enhanced functionality
  refreshData: (force?: boolean) => Promise<void>;
  updateSchedule: (id: string | number, updates: Partial<UnifiedSchedule>) => void;
  removeSchedule: (id: string | number) => void;
  addSchedule: (schedule: UnifiedSchedule) => void;
  
  // Filtering and sorting
  filteredSchedules: UnifiedSchedule[];
  sortedSchedules: UnifiedSchedule[];
  setFilter: (filter: (schedule: UnifiedSchedule) => boolean) => void;
  setSortBy: (sort: (a: UnifiedSchedule, b: UnifiedSchedule) => number) => void;
  
  // Advanced stats
  performanceMetrics: PerformanceMetrics;
  completionPercentage: number;
  lastFetch: number;
  isRefreshing: boolean;
  
  // Attendance specific
  attendanceMap: Map<string | number, AttendanceRecord>;
  getAttendanceForSchedule: (scheduleId: string | number) => AttendanceRecord | undefined;
  
  // Utility functions
  retryFetch: () => Promise<void>;
  clearCache: () => void;
}

export const useScheduleManager = (options: UseScheduleManagerOptions): UseScheduleManagerReturn => {
  const {
    variant,
    enableCache = true,
    cacheTTL = 300000, // 5 minutes
    autoRefresh = false,
    refreshInterval = 60000, // 1 minute
    includeAttendance = true,
    onError,
    onSuccess
  } = options;

  // Core state
  const [schedules, setSchedules] = useState<UnifiedSchedule[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [lastFetch, setLastFetch] = useState<number>(0);
  const [attendanceMap, setAttendanceMap] = useState<Map<string | number, AttendanceRecord>>(new Map());

  // Filtering and sorting state
  const [currentFilter, setCurrentFilter] = useState<((schedule: UnifiedSchedule) => boolean) | null>(null);
  const [currentSort, setCurrentSort] = useState<((a: UnifiedSchedule, b: UnifiedSchedule) => number) | null>(null);

  // Performance monitoring
  const { metrics: performanceMetrics, startMeasure, endMeasure, updateMetric } = usePerformanceMonitoring(`ScheduleManager-${variant}`);
  
  // Cache management
  const cache = useCache<{
    schedules: UnifiedSchedule[];
    attendanceMap: Map<string | number, AttendanceRecord>;
    timestamp: number;
  }>(cacheTTL);

  // Data manager
  const dataManagerRef = useRef(new ScheduleDataManager());
  const refreshTimeoutRef = useRef<NodeJS.Timeout>();

  // Calculate derived values
  const stats: ScheduleStats = calculateScheduleStats(schedules);
  const completionPercentage = getScheduleCompletionPercentage(schedules);
  
  // Apply filtering and sorting
  const filteredSchedules = currentFilter ? schedules.filter(currentFilter) : schedules;
  const sortedSchedules = currentSort ? [...filteredSchedules].sort(currentSort) : sortSchedulesByDate(filteredSchedules);

  /**
   * Clear error state
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  /**
   * Update performance metrics
   */
  const updatePerformanceMetrics = useCallback((apiTime: number, cached: boolean = false) => {
    updateMetric('apiResponseTime', apiTime);
    updateMetric('totalRequests', performanceMetrics.totalRequests + 1);
    if (cached) {
      updateMetric('cacheHits', performanceMetrics.cacheHits + 1);
    }
  }, [updateMetric, performanceMetrics]);

  /**
   * Fetch schedule data from API or cache
   */
  const fetchScheduleData = useCallback(async (force: boolean = false): Promise<void> => {
    startMeasure('api');
    
    try {
      // Check cache first (unless forced)
      if (!force && enableCache) {
        const cacheKey = `${variant}-schedules-${includeAttendance ? 'with-attendance' : 'basic'}`;
        const cachedData = cache.get(cacheKey);
        
        if (cachedData) {
          setSchedules(cachedData.schedules);
          setAttendanceMap(cachedData.attendanceMap);
          setLastFetch(cachedData.timestamp);
          updatePerformanceMetrics(0, true);
          endMeasure('api');
          
          if (onSuccess) {
            onSuccess(cachedData.schedules);
          }
          return;
        }
      }

      // Fetch from API
      const result = await dataManagerRef.current.fetchScheduleData(variant, includeAttendance);
      
      endMeasure('api');
      updatePerformanceMetrics(performanceMetrics.apiResponseTime);

      // Update state
      setSchedules(result.schedules);
      setAttendanceMap(result.attendanceMap);
      setLastFetch(Date.now());
      setError(null);

      // Cache the result
      if (enableCache) {
        const cacheKey = `${variant}-schedules-${includeAttendance ? 'with-attendance' : 'basic'}`;
        cache.set(cacheKey, {
          schedules: result.schedules,
          attendanceMap: result.attendanceMap,
          timestamp: Date.now()
        });
      }

      if (onSuccess) {
        onSuccess(result.schedules);
      }

    } catch (err) {
      endMeasure('api');
      
      let errorMessage = 'Failed to load schedule data';
      if (err instanceof APIError) {
        errorMessage = err.userMessage;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      }

      setError(errorMessage);
      console.error(`Schedule fetch error (${variant}):`, err);

      if (onError) {
        onError(errorMessage);
      }
    }
  }, [variant, includeAttendance, enableCache, cache, startMeasure, endMeasure, updatePerformanceMetrics, performanceMetrics.apiResponseTime, onSuccess, onError]);

  /**
   * Refresh data (public API)
   */
  const refreshData = useCallback(async (force: boolean = false): Promise<void> => {
    if (loading && !force) return;
    
    setIsRefreshing(true);
    try {
      await fetchScheduleData(force);
    } finally {
      setIsRefreshing(false);
    }
  }, [loading, fetchScheduleData]);

  /**
   * Retry fetch (for error recovery)
   */
  const retryFetch = useCallback(async (): Promise<void> => {
    clearError();
    setLoading(true);
    try {
      await fetchScheduleData(true);
    } finally {
      setLoading(false);
    }
  }, [clearError, fetchScheduleData]);

  /**
   * Update a schedule in the local state
   */
  const updateSchedule = useCallback((id: string | number, updates: Partial<UnifiedSchedule>) => {
    setSchedules(prev => prev.map(schedule => 
      schedule.id === id ? { ...schedule, ...updates } : schedule
    ));
  }, []);

  /**
   * Remove a schedule from local state
   */
  const removeSchedule = useCallback((id: string | number) => {
    setSchedules(prev => prev.filter(schedule => schedule.id !== id));
  }, []);

  /**
   * Add a schedule to local state
   */
  const addSchedule = useCallback((schedule: UnifiedSchedule) => {
    setSchedules(prev => [...prev, schedule]);
  }, []);

  /**
   * Set filter function
   */
  const setFilter = useCallback((filter: (schedule: UnifiedSchedule) => boolean) => {
    setCurrentFilter(() => filter);
  }, []);

  /**
   * Set sort function
   */
  const setSortBy = useCallback((sort: (a: UnifiedSchedule, b: UnifiedSchedule) => number) => {
    setCurrentSort(() => sort);
  }, []);

  /**
   * Get attendance data for a specific schedule
   */
  const getAttendanceForSchedule = useCallback((scheduleId: string | number): AttendanceRecord | undefined => {
    return attendanceMap.get(scheduleId);
  }, [attendanceMap]);

  /**
   * Clear cache
   */
  const clearCache = useCallback(() => {
    cache.clear();
  }, [cache]);

  // Initial data fetch
  useEffect(() => {
    const initialFetch = async () => {
      setLoading(true);
      try {
        await fetchScheduleData();
      } finally {
        setLoading(false);
      }
    };

    initialFetch();
  }, [fetchScheduleData]);

  // Auto refresh setup
  useEffect(() => {
    if (!autoRefresh || refreshInterval <= 0) return;

    const startAutoRefresh = () => {
      refreshTimeoutRef.current = setTimeout(() => {
        refreshData(false).then(() => {
          startAutoRefresh(); // Recursive call for continuous refresh
        });
      }, refreshInterval);
    };

    startAutoRefresh();

    return () => {
      if (refreshTimeoutRef.current) {
        clearTimeout(refreshTimeoutRef.current);
      }
    };
  }, [autoRefresh, refreshInterval, refreshData]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (refreshTimeoutRef.current) {
        clearTimeout(refreshTimeoutRef.current);
      }
    };
  }, []);

  return {
    // Core data
    schedules,
    loading,
    error,
    stats,
    refresh: refreshData,
    clearError,

    // Enhanced functionality
    refreshData,
    updateSchedule,
    removeSchedule,
    addSchedule,

    // Filtering and sorting
    filteredSchedules,
    sortedSchedules,
    setFilter,
    setSortBy,

    // Advanced data
    performanceMetrics,
    completionPercentage,
    lastFetch,
    isRefreshing,

    // Attendance
    attendanceMap,
    getAttendanceForSchedule,

    // Utilities
    retryFetch,
    clearCache
  };
};

/**
 * Predefined filter functions for common use cases
 */
export const scheduleFilters = {
  active: (schedule: UnifiedSchedule) => {
    const status = getScheduleStatus(schedule);
    return status === 'active';
  },
  
  upcoming: (schedule: UnifiedSchedule) => {
    const status = getScheduleStatus(schedule);
    return status === 'upcoming';
  },
  
  completed: (schedule: UnifiedSchedule) => {
    return !!(schedule.attendance?.check_in_time && schedule.attendance?.check_out_time);
  },
  
  today: (schedule: UnifiedSchedule) => {
    const today = new Date().toISOString().split('T')[0];
    const scheduleDate = new Date(schedule.full_date).toISOString().split('T')[0];
    return scheduleDate === today;
  },
  
  thisWeek: (schedule: UnifiedSchedule) => {
    const now = new Date();
    const weekStart = new Date(now.setDate(now.getDate() - now.getDay()));
    const weekEnd = new Date(now.setDate(now.getDate() - now.getDay() + 6));
    const scheduleDate = new Date(schedule.full_date);
    return scheduleDate >= weekStart && scheduleDate <= weekEnd;
  },
  
  hasAttendance: (schedule: UnifiedSchedule) => {
    return !!(schedule.attendance?.check_in_time || schedule.attendance?.check_out_time);
  }
};

/**
 * Predefined sort functions for common use cases
 */
export const scheduleSorts = {
  byDate: (a: UnifiedSchedule, b: UnifiedSchedule) => {
    return new Date(a.full_date).getTime() - new Date(b.full_date).getTime();
  },
  
  byDateDesc: (a: UnifiedSchedule, b: UnifiedSchedule) => {
    return new Date(b.full_date).getTime() - new Date(a.full_date).getTime();
  },
  
  byLocation: (a: UnifiedSchedule, b: UnifiedSchedule) => {
    return a.location.localeCompare(b.location);
  },
  
  byEmployee: (a: UnifiedSchedule, b: UnifiedSchedule) => {
    return a.employee_name.localeCompare(b.employee_name);
  }
};

