/**
 * Unified Attendance History Management Hook
 * Handles attendance history, statistics, and reporting for both variants
 */

import { useState, useEffect, useCallback, useMemo } from 'react';
import { AttendanceVariant, BaseAttendanceRecord, AttendanceError } from '../types';
import { getApiService } from '../apiService';
import { getErrorHandler } from '../errorHandler';
import { formatDate, parseDateTime } from '../timeUtils';

export interface AttendanceHistoryRecord extends BaseAttendanceRecord {
  working_hours?: string;
  overtime_hours?: string;
  status_display?: string;
  is_late?: boolean;
  is_early_out?: boolean;
}

export interface AttendanceStatistics {
  totalDays: number;
  presentDays: number;
  absentDays: number;
  lateDays: number;
  earlyOutDays: number;
  overtimeDays: number;
  totalWorkingHours: number;
  totalOvertimeHours: number;
  averageWorkingHours: number;
  attendanceRate: number; // percentage
  punctualityRate: number; // percentage
}

export interface AttendanceHistoryState {
  // History data
  records: AttendanceHistoryRecord[];
  filteredRecords: AttendanceHistoryRecord[];
  statistics: AttendanceStatistics;
  
  // Pagination
  currentPage: number;
  totalPages: number;
  itemsPerPage: number;
  totalRecords: number;
  
  // Filtering and sorting
  filterPeriod: 'daily' | 'weekly' | 'monthly' | 'yearly' | 'custom';
  filterStartDate: string | null;
  filterEndDate: string | null;
  sortBy: 'date' | 'status' | 'working_hours';
  sortOrder: 'asc' | 'desc';
  
  // Operation states
  isLoading: boolean;
  isLoadingMore: boolean;
  error: AttendanceError | null;
  hasMoreData: boolean;
}

export interface AttendanceHistoryActions {
  // Data loading
  loadHistory: (period?: string, startDate?: string, endDate?: string) => Promise<void>;
  loadMoreHistory: () => Promise<void>;
  refreshHistory: () => Promise<void>;
  
  // Filtering and pagination
  setFilterPeriod: (period: 'daily' | 'weekly' | 'monthly' | 'yearly' | 'custom') => void;
  setCustomDateRange: (startDate: string, endDate: string) => void;
  setSorting: (sortBy: 'date' | 'status' | 'working_hours', order: 'asc' | 'desc') => void;
  setPage: (page: number) => void;
  setItemsPerPage: (items: number) => void;
  
  // Export and analysis
  exportToCSV: () => string;
  getStatisticsForPeriod: (startDate: string, endDate: string) => AttendanceStatistics;
  getAttendancePattern: () => { [key: string]: number };
  
  // Utilities
  clearError: () => void;
  resetFilters: () => void;
}

export interface UseAttendanceHistoryOptions {
  variant: AttendanceVariant;
  initialPeriod?: 'daily' | 'weekly' | 'monthly' | 'yearly';
  defaultItemsPerPage?: number;
  autoLoad?: boolean;
  enableStatistics?: boolean;
}

export const useAttendanceHistory = (
  options: UseAttendanceHistoryOptions
): [AttendanceHistoryState, AttendanceHistoryActions] => {
  const {
    variant,
    initialPeriod = 'monthly',
    defaultItemsPerPage = 10,
    autoLoad = true,
    enableStatistics = true
  } = options;

  // Initialize services
  const apiService = getApiService(variant);
  const errorHandler = getErrorHandler(variant);

  // Core data state
  const [records, setRecords] = useState<AttendanceHistoryRecord[]>([]);
  const [filteredRecords, setFilteredRecords] = useState<AttendanceHistoryRecord[]>([]);

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(defaultItemsPerPage);
  const [totalRecords, setTotalRecords] = useState(0);

  // Filtering state
  const [filterPeriod, setFilterPeriod] = useState<'daily' | 'weekly' | 'monthly' | 'yearly' | 'custom'>(initialPeriod);
  const [filterStartDate, setFilterStartDate] = useState<string | null>(null);
  const [filterEndDate, setFilterEndDate] = useState<string | null>(null);
  const [sortBy, setSortBy] = useState<'date' | 'status' | 'working_hours'>('date');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

  // Operation states
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [error, setError] = useState<AttendanceError | null>(null);
  const [hasMoreData, setHasMoreData] = useState(true);

  /**
   * Calculate statistics from records
   */
  const statistics = useMemo((): AttendanceStatistics => {
    if (!enableStatistics || filteredRecords.length === 0) {
      return {
        totalDays: 0,
        presentDays: 0,
        absentDays: 0,
        lateDays: 0,
        earlyOutDays: 0,
        overtimeDays: 0,
        totalWorkingHours: 0,
        totalOvertimeHours: 0,
        averageWorkingHours: 0,
        attendanceRate: 0,
        punctualityRate: 0
      };
    }

    const presentRecords = filteredRecords.filter(record => record.time_in);
    const lateRecords = filteredRecords.filter(record => record.is_late);
    const earlyOutRecords = filteredRecords.filter(record => record.is_early_out);
    const overtimeRecords = filteredRecords.filter(record => record.overtime_hours && parseFloat(record.overtime_hours) > 0);

    // Calculate total working hours
    const totalWorkingMinutes = presentRecords.reduce((total, record) => {
      if (record.working_hours) {
        const [hours, minutes] = record.working_hours.split(':').map(Number);
        return total + (hours * 60) + minutes;
      }
      return total;
    }, 0);

    // Calculate total overtime hours
    const totalOvertimeMinutes = overtimeRecords.reduce((total, record) => {
      if (record.overtime_hours) {
        const [hours, minutes] = record.overtime_hours.split(':').map(Number);
        return total + (hours * 60) + minutes;
      }
      return total;
    }, 0);

    const totalWorkingHours = totalWorkingMinutes / 60;
    const totalOvertimeHours = totalOvertimeMinutes / 60;
    const averageWorkingHours = presentRecords.length > 0 ? totalWorkingHours / presentRecords.length : 0;

    // Calculate rates
    const attendanceRate = filteredRecords.length > 0 ? (presentRecords.length / filteredRecords.length) * 100 : 0;
    const punctualityRate = presentRecords.length > 0 ? ((presentRecords.length - lateRecords.length) / presentRecords.length) * 100 : 0;

    return {
      totalDays: filteredRecords.length,
      presentDays: presentRecords.length,
      absentDays: filteredRecords.length - presentRecords.length,
      lateDays: lateRecords.length,
      earlyOutDays: earlyOutRecords.length,
      overtimeDays: overtimeRecords.length,
      totalWorkingHours,
      totalOvertimeHours,
      averageWorkingHours,
      attendanceRate,
      punctualityRate
    };
  }, [filteredRecords, enableStatistics]);

  /**
   * Calculate total pages
   */
  const totalPages = useMemo(() => {
    return Math.ceil(totalRecords / itemsPerPage);
  }, [totalRecords, itemsPerPage]);

  /**
   * Get date range for filter period
   */
  const getDateRangeForPeriod = useCallback((period: string): { startDate: string; endDate: string } => {
    const today = new Date();
    const endDate = formatDate(today);
    let startDate: string;

    switch (period) {
      case 'daily':
        startDate = endDate;
        break;
      case 'weekly':
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - 7);
        startDate = formatDate(weekStart);
        break;
      case 'yearly':
        const yearStart = new Date(today.getFullYear(), 0, 1);
        startDate = formatDate(yearStart);
        break;
      case 'monthly':
      default:
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        startDate = formatDate(monthStart);
        break;
    }

    return { startDate, endDate };
  }, []);

  /**
   * Apply local filtering and sorting
   */
  const applyFiltersAndSorting = useCallback(() => {
    let filtered = [...records];

    // Apply date filtering
    if (filterStartDate && filterEndDate) {
      filtered = filtered.filter(record => {
        const recordDate = record.date;
        return recordDate >= filterStartDate && recordDate <= filterEndDate;
      });
    }

    // Apply sorting
    filtered.sort((a, b) => {
      let aValue: string | number;
      let bValue: string | number;

      switch (sortBy) {
        case 'date':
          aValue = a.date;
          bValue = b.date;
          break;
        case 'status':
          aValue = a.status;
          bValue = b.status;
          break;
        case 'working_hours':
          aValue = a.working_hours || '00:00:00';
          bValue = b.working_hours || '00:00:00';
          break;
        default:
          aValue = a.date;
          bValue = b.date;
      }

      if (sortOrder === 'asc') {
        return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
      } else {
        return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
      }
    });

    setFilteredRecords(filtered);
    setTotalRecords(filtered.length);
  }, [records, filterStartDate, filterEndDate, sortBy, sortOrder]);

  /**
   * Load attendance history
   */
  const loadHistory = useCallback(async (
    period?: string,
    startDate?: string,
    endDate?: string
  ): Promise<void> => {
    setIsLoading(true);
    setError(null);

    try {
      const periodToUse = period || filterPeriod;
      let dateRange = { startDate, endDate };

      if (!startDate || !endDate) {
        if (periodToUse === 'custom' && filterStartDate && filterEndDate) {
          dateRange = { startDate: filterStartDate, endDate: filterEndDate };
        } else {
          dateRange = getDateRangeForPeriod(periodToUse);
        }
      }

      // Build API endpoint with parameters
      const params = new URLSearchParams({
        period: periodToUse,
        start_date: dateRange.startDate,
        end_date: dateRange.endDate,
        page: '1',
        per_page: itemsPerPage.toString()
      });

      const response = await fetch(
        `${apiService['baseUrl']}/attendance/history?${params}`,
        {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      const historyRecords = data.data || data.records || [];

      setRecords(historyRecords);
      setCurrentPage(1);
      setHasMoreData(historyRecords.length === itemsPerPage);

      // Update filter dates
      setFilterStartDate(dateRange.startDate);
      setFilterEndDate(dateRange.endDate);

    } catch (err) {
      const processedError = errorHandler.handleError(err, 'load attendance history');
      setError(processedError);
    } finally {
      setIsLoading(false);
    }
  }, [filterPeriod, filterStartDate, filterEndDate, itemsPerPage, apiService, errorHandler, getDateRangeForPeriod]);

  /**
   * Load more history records (pagination)
   */
  const loadMoreHistory = useCallback(async (): Promise<void> => {
    if (isLoadingMore || !hasMoreData) return;

    setIsLoadingMore(true);

    try {
      const params = new URLSearchParams({
        period: filterPeriod,
        start_date: filterStartDate || '',
        end_date: filterEndDate || '',
        page: (currentPage + 1).toString(),
        per_page: itemsPerPage.toString()
      });

      const response = await fetch(
        `${apiService['baseUrl']}/attendance/history?${params}`,
        {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      const newRecords = data.data || data.records || [];

      setRecords(prev => [...prev, ...newRecords]);
      setCurrentPage(prev => prev + 1);
      setHasMoreData(newRecords.length === itemsPerPage);

    } catch (err) {
      const processedError = errorHandler.handleError(err, 'load more history');
      setError(processedError);
    } finally {
      setIsLoadingMore(false);
    }
  }, [isLoadingMore, hasMoreData, filterPeriod, filterStartDate, filterEndDate, currentPage, itemsPerPage, apiService, errorHandler]);

  /**
   * Refresh current history
   */
  const refreshHistory = useCallback(async (): Promise<void> => {
    await loadHistory();
  }, [loadHistory]);

  /**
   * Set custom date range
   */
  const setCustomDateRange = useCallback((startDate: string, endDate: string) => {
    setFilterStartDate(startDate);
    setFilterEndDate(endDate);
    setFilterPeriod('custom');
  }, []);

  /**
   * Set sorting preferences
   */
  const setSorting = useCallback((newSortBy: 'date' | 'status' | 'working_hours', order: 'asc' | 'desc') => {
    setSortBy(newSortBy);
    setSortOrder(order);
  }, []);

  /**
   * Set current page
   */
  const setPage = useCallback((page: number) => {
    setCurrentPage(page);
  }, []);

  /**
   * Export history to CSV
   */
  const exportToCSV = useCallback((): string => {
    const headers = ['Tanggal', 'Check-in', 'Check-out', 'Jam Kerja', 'Status', 'Lokasi'];
    const rows = filteredRecords.map(record => [
      formatDate(parseDateTime(record.date)),
      record.time_in || '--:--',
      record.time_out || '--:--',
      record.working_hours || '00:00:00',
      record.status_display || record.status,
      record.location || ''
    ]);

    const csvContent = [headers, ...rows]
      .map(row => row.map(cell => `"${cell}"`).join(','))
      .join('\n');

    return csvContent;
  }, [filteredRecords]);

  /**
   * Get statistics for a specific period
   */
  const getStatisticsForPeriod = useCallback((startDate: string, endDate: string): AttendanceStatistics => {
    const periodRecords = records.filter(record => {
      return record.date >= startDate && record.date <= endDate;
    });

    // Use the same calculation logic as the main statistics
    const presentRecords = periodRecords.filter(record => record.time_in);
    const lateRecords = periodRecords.filter(record => record.is_late);

    return {
      totalDays: periodRecords.length,
      presentDays: presentRecords.length,
      absentDays: periodRecords.length - presentRecords.length,
      lateDays: lateRecords.length,
      earlyOutDays: 0, // Simplified for this function
      overtimeDays: 0,
      totalWorkingHours: 0,
      totalOvertimeHours: 0,
      averageWorkingHours: 0,
      attendanceRate: periodRecords.length > 0 ? (presentRecords.length / periodRecords.length) * 100 : 0,
      punctualityRate: presentRecords.length > 0 ? ((presentRecords.length - lateRecords.length) / presentRecords.length) * 100 : 0
    };
  }, [records]);

  /**
   * Get attendance pattern analysis
   */
  const getAttendancePattern = useCallback((): { [key: string]: number } => {
    const pattern: { [key: string]: number } = {};

    filteredRecords.forEach(record => {
      const date = new Date(record.date);
      const dayName = date.toLocaleDateString('id-ID', { weekday: 'long' });
      
      if (!pattern[dayName]) {
        pattern[dayName] = 0;
      }
      
      if (record.time_in) {
        pattern[dayName]++;
      }
    });

    return pattern;
  }, [filteredRecords]);

  /**
   * Clear current error
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  /**
   * Reset all filters to default
   */
  const resetFilters = useCallback(() => {
    setFilterPeriod(initialPeriod);
    setFilterStartDate(null);
    setFilterEndDate(null);
    setSortBy('date');
    setSortOrder('desc');
    setCurrentPage(1);
  }, [initialPeriod]);

  // Apply filters when dependencies change
  useEffect(() => {
    applyFiltersAndSorting();
  }, [applyFiltersAndSorting]);

  // Auto-load history on mount
  useEffect(() => {
    if (autoLoad) {
      loadHistory();
    }
  }, [autoLoad, loadHistory]);

  // Compose state object
  const state: AttendanceHistoryState = {
    records,
    filteredRecords,
    statistics,
    currentPage,
    totalPages,
    itemsPerPage,
    totalRecords,
    filterPeriod,
    filterStartDate,
    filterEndDate,
    sortBy,
    sortOrder,
    isLoading,
    isLoadingMore,
    error,
    hasMoreData
  };

  // Compose actions object
  const actions: AttendanceHistoryActions = {
    loadHistory,
    loadMoreHistory,
    refreshHistory,
    setFilterPeriod,
    setCustomDateRange,
    setSorting,
    setPage,
    setItemsPerPage,
    exportToCSV,
    getStatisticsForPeriod,
    getAttendancePattern,
    clearError,
    resetFilters
  };

  return [state, actions];
};