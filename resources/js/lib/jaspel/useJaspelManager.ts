/**
 * Unified Jaspel Manager Hook
 * Main data management hook that orchestrates all Jaspel functionality
 */

import { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { 
  BaseJaspelItem, 
  JaspelSummary, 
  DashboardData,
  JaspelVariant,
  UseJaspelManagerReturn,
  TransformationOptions,
  RealtimeNotification
} from './types';
import { 
  jaspelDataManager, 
  JaspelAPIError 
} from './api';
import { 
  useJaspelPerformanceMonitoring,
  useJaspelCache,
  useJaspelRealtime,
  useJaspelAutoRefresh
} from './hooks';
import { useBadgeManager } from './useBadgeManager';
import { 
  sortJaspelByDate, 
  sortJaspelByAmount, 
  calculateSummaryFromItems 
} from './utils';

interface UseJaspelManagerOptions {
  variant: JaspelVariant;
  userId?: string;
  month?: number;
  year?: number;
  enableCache?: boolean;
  enableRealtime?: boolean;
  enableGaming?: boolean;
  enableAutoRefresh?: boolean;
  autoRefreshInterval?: number;
  transformationOptions?: TransformationOptions;
}

export const useJaspelManager = (options: UseJaspelManagerOptions): UseJaspelManagerReturn => {
  const {
    variant,
    userId = '',
    month,
    year,
    enableCache = true,
    enableRealtime = true,
    enableGaming = true,
    enableAutoRefresh = false,
    autoRefreshInterval = 60000,
    transformationOptions = {}
  } = options;

  // Core state
  const [data, setData] = useState<BaseJaspelItem[]>([]);
  const [summary, setSummary] = useState<JaspelSummary>({
    total: 0,
    approved: 0,
    pending: 0,
    rejected: 0,
    count: { total: 0, approved: 0, pending: 0, rejected: 0 }
  });
  const [dashboardData, setDashboardData] = useState<DashboardData | undefined>();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastUpdateTime, setLastUpdateTime] = useState<string>('Never');
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Filtering and sorting state
  const [currentFilter, setCurrentFilter] = useState<((item: BaseJaspelItem) => boolean) | null>(null);
  const [currentSort, setCurrentSort] = useState<((a: BaseJaspelItem, b: BaseJaspelItem) => number) | null>(null);

  // Hooks
  const performance = useJaspelPerformanceMonitoring(variant);
  const cache = useJaspelCache({ ttl: 300000, maxSize: 50, strategy: 'lru' });
  const realtime = useJaspelRealtime(userId, variant);
  const badgeManager = useBadgeManager({ variant, enableAnimations: enableGaming });
  
  // Refs for cleanup
  const abortControllerRef = useRef<AbortController | null>(null);
  const retryTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Memoized processed data
  const filteredData = useMemo(() => {
    if (!currentFilter) return data;
    return data.filter(currentFilter);
  }, [data, currentFilter]);

  const sortedData = useMemo(() => {
    if (!currentSort) return filteredData;
    return [...filteredData].sort(currentSort);
  }, [filteredData, currentSort]);

  // Clear error helper
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // Core data fetching function
  const fetchData = useCallback(async (force: boolean = false): Promise<void> => {
    // Cancel previous request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    abortControllerRef.current = new AbortController();
    const startTime = performance.now();

    try {
      setLoading(true);
      setError(null);

      // Options for data transformation
      const options: TransformationOptions = {
        enableCache: enableCache && !force,
        ...transformationOptions
      };

      // Fetch main data
      const result = await jaspelDataManager.fetchJaspelData(
        variant,
        month,
        year,
        options
      );

      const duration = performance.now() - startTime;
      performance.recordApiCall(duration, !!result.meta.endpoint_used);

      // Update state
      setData(result.items);
      setSummary(result.summary);
      setLastUpdateTime(new Date().toLocaleTimeString());

      // Try to fetch dashboard data if variant is paramedis
      if (variant === 'paramedis') {
        try {
          const dashData = await jaspelDataManager.fetchDashboardData();
          setDashboardData(dashData);
        } catch (dashError) {
          console.warn('Dashboard data fetch failed:', dashError);
          // Don't set error state for dashboard failure
        }
      }

      // Show success notification
      if (enableGaming && result.items.length > 0) {
        badgeManager.showGamingBadge('goldEarned', {
          autoHide: true,
          customText: `${result.items.length} items loaded`
        });
      }

      clearError();

    } catch (err) {
      const duration = performance.now() - startTime;
      performance.recordError();

      if (err instanceof JaspelAPIError) {
        setError(err.userMessage || err.message);
      } else if (err instanceof Error && err.name !== 'AbortError') {
        setError(err.message);
      }

      console.error('Jaspel data fetch failed:', err);

      // Show error notification
      if (enableGaming && err instanceof Error && err.name !== 'AbortError') {
        badgeManager.showGamingBadge('statusRejected', {
          autoHide: true,
          customText: 'Load failed'
        });
      }

    } finally {
      setLoading(false);
      setIsRefreshing(false);
    }
  }, [
    variant, 
    month, 
    year, 
    enableCache, 
    transformationOptions, 
    performance, 
    enableGaming, 
    badgeManager,
    clearError
  ]);

  // Refresh function with force option
  const refreshData = useCallback(async (force: boolean = false): Promise<void> => {
    setIsRefreshing(true);
    
    // Clear cache if force refresh
    if (force && enableCache) {
      jaspelDataManager.clearCache();
      cache.clearCache();
    }

    await fetchData(force);
  }, [fetchData, enableCache, cache]);

  // Regular refresh function
  const refresh = useCallback(async (): Promise<void> => {
    await refreshData(false);
  }, [refreshData]);

  // Retry fetch with exponential backoff
  const retryFetch = useCallback(async (): Promise<void> => {
    const retryDelay = Math.min(1000 * Math.pow(2, performance.jaspelMetrics.errorRate), 10000);
    
    if (retryTimeoutRef.current) {
      clearTimeout(retryTimeoutRef.current);
    }

    retryTimeoutRef.current = setTimeout(() => {
      refreshData(true);
    }, retryDelay);
  }, [refreshData, performance.jaspelMetrics.errorRate]);

  // Clear cache function
  const clearCache = useCallback((): void => {
    jaspelDataManager.clearCache();
    cache.clearCache();
    
    if (enableGaming) {
      badgeManager.showGamingBadge('questPending', {
        autoHide: true,
        customText: 'Cache cleared'
      });
    }
  }, [cache, enableGaming, badgeManager]);

  // Item management functions
  const updateItem = useCallback((id: string | number, updates: Partial<BaseJaspelItem>): void => {
    setData(prevData => 
      prevData.map(item => 
        item.id === id ? { ...item, ...updates } : item
      )
    );

    // Recalculate summary
    setSummary(prevSummary => {
      const updatedData = data.map(item => 
        item.id === id ? { ...item, ...updates } : item
      );
      return calculateSummaryFromItems(updatedData);
    });
  }, [data]);

  const removeItem = useCallback((id: string | number): void => {
    setData(prevData => prevData.filter(item => item.id !== id));
    
    // Recalculate summary
    setSummary(prevSummary => {
      const updatedData = data.filter(item => item.id !== id);
      return calculateSummaryFromItems(updatedData);
    });
  }, [data]);

  const addItem = useCallback((item: BaseJaspelItem): void => {
    setData(prevData => [item, ...prevData]);
    
    // Recalculate summary
    setSummary(prevSummary => {
      const updatedData = [item, ...data];
      return calculateSummaryFromItems(updatedData);
    });

    if (enableGaming) {
      badgeManager.showGamingBadge('rewardClaimed', {
        autoHide: true,
        customText: 'Item added'
      });
    }
  }, [data, enableGaming, badgeManager]);

  // Filtering and sorting functions
  const setFilter = useCallback((filter: (item: BaseJaspelItem) => boolean): void => {
    setCurrentFilter(() => filter);
  }, []);

  const setSortBy = useCallback((sort: (a: BaseJaspelItem, b: BaseJaspelItem) => number): void => {
    setCurrentSort(() => sort);
  }, []);

  // Auto-refresh hook
  const autoRefresh = useJaspelAutoRefresh(refresh, {
    enabled: enableAutoRefresh,
    interval: autoRefreshInterval,
    maxRetries: 3,
    backoffMultiplier: 2
  });

  // Handle real-time updates
  useEffect(() => {
    if (enableRealtime && realtime.notifications.length > 0) {
      const latestNotification = realtime.notifications[0];
      
      if (latestNotification.type === 'success' && latestNotification.data) {
        // Auto-refresh data when real-time update received
        refresh();
      }
    }
  }, [realtime.notifications, enableRealtime, refresh]);

  // Initial data fetch
  useEffect(() => {
    fetchData();

    // Cleanup function
    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
      if (retryTimeoutRef.current) {
        clearTimeout(retryTimeoutRef.current);
      }
    };
  }, [fetchData]);

  // Return comprehensive manager interface
  return {
    // Core data
    data,
    summary,
    loading,
    error,

    // Basic functions
    refresh,
    clearError,

    // Enhanced functionality
    refreshData,
    updateItem,
    removeItem,
    addItem,

    // Filtering and sorting
    filteredData,
    sortedData,
    setFilter,
    setSortBy,

    // Advanced features
    dashboardData,
    realtimeConnected: realtime.connected,
    notifications: realtime.notifications,
    lastUpdateTime,
    isRefreshing: isRefreshing || autoRefresh.isRefreshing,

    // Utility functions
    retryFetch,
    clearCache,

    // Performance metrics (exposed for debugging)
    performance: performance.jaspelMetrics,

    // Gaming features
    badges: enableGaming ? badgeManager.activeBadges : [],
    achievements: cache.getAchievements?.(userId) || [],

    // Auto-refresh controls
    autoRefreshStatus: {
      enabled: enableAutoRefresh,
      isRefreshing: autoRefresh.isRefreshing,
      lastSuccessfulRefresh: autoRefresh.lastSuccessfulRefresh,
      retryCount: autoRefresh.retryCount
    }
  };
};

/**
 * Simplified hook for basic Jaspel data fetching
 */
export const useJaspelData = (variant: JaspelVariant, month?: number, year?: number) => {
  return useJaspelManager({
    variant,
    month,
    year,
    enableCache: true,
    enableRealtime: false,
    enableGaming: false,
    enableAutoRefresh: false
  });
};

/**
 * Gaming-enhanced hook for dokter variant
 */
export const useJaspelGaming = (userId: string, month?: number, year?: number) => {
  return useJaspelManager({
    variant: 'dokter',
    userId,
    month,
    year,
    enableCache: true,
    enableRealtime: true,
    enableGaming: true,
    enableAutoRefresh: true,
    autoRefreshInterval: 60000
  });
};

/**
 * Real-time hook for paramedis variant
 */
export const useJaspelRealtime = (userId: string, month?: number, year?: number) => {
  return useJaspelManager({
    variant: 'paramedis',
    userId,
    month,
    year,
    enableCache: true,
    enableRealtime: true,
    enableGaming: false,
    enableAutoRefresh: true,
    autoRefreshInterval: 30000
  });
};