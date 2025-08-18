/**
 * Jaspel-Specific Performance Monitoring Hooks
 * Extends the schedule hooks system for Jaspel components
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import { 
  usePerformanceMonitoring, 
  useCache, 
  useDevice 
} from '../schedule/hooks';
import { 
  JaspelPerformanceMetrics, 
  CacheConfig, 
  RealtimeNotification,
  JaspelVariant 
} from './types';

/**
 * Enhanced performance monitoring specifically for Jaspel operations
 */
export const useJaspelPerformanceMonitoring = (variant: JaspelVariant) => {
  const baseMetrics = usePerformanceMonitoring(`Jaspel-${variant}`);
  const [jaspelMetrics, setJaspelMetrics] = useState<JaspelPerformanceMetrics>({
    apiResponseTime: 0,
    totalRequests: 0,
    cacheHits: 0,
    errorRate: 0,
    avgLoadTime: 0,
    realtimeLatency: 0
  });

  const updateJaspelMetric = useCallback((metric: keyof JaspelPerformanceMetrics, value: number) => {
    setJaspelMetrics(prev => ({
      ...prev,
      [metric]: value
    }));
    
    // Also update base metrics
    baseMetrics.updateMetric(metric, value);
  }, [baseMetrics]);

  const recordApiCall = useCallback((duration: number, cached: boolean = false) => {
    updateJaspelMetric('apiResponseTime', duration);
    updateJaspelMetric('totalRequests', jaspelMetrics.totalRequests + 1);
    
    if (cached) {
      updateJaspelMetric('cacheHits', jaspelMetrics.cacheHits + 1);
    }
    
    // Calculate average load time
    const totalCalls = jaspelMetrics.totalRequests + 1;
    const newAvg = ((jaspelMetrics.avgLoadTime * jaspelMetrics.totalRequests) + duration) / totalCalls;
    updateJaspelMetric('avgLoadTime', newAvg);
  }, [jaspelMetrics, updateJaspelMetric]);

  const recordError = useCallback(() => {
    const totalCalls = jaspelMetrics.totalRequests;
    const errorCount = Math.floor(jaspelMetrics.errorRate * totalCalls / 100) + 1;
    const newErrorRate = totalCalls > 0 ? (errorCount / totalCalls) * 100 : 0;
    updateJaspelMetric('errorRate', newErrorRate);
  }, [jaspelMetrics, updateJaspelMetric]);

  const recordRealtimeLatency = useCallback((latency: number) => {
    updateJaspelMetric('realtimeLatency', latency);
  }, [updateJaspelMetric]);

  return {
    ...baseMetrics,
    jaspelMetrics,
    recordApiCall,
    recordError,
    recordRealtimeLatency,
    updateJaspelMetric
  };
};

/**
 * Jaspel-specific caching hook with gaming data support
 */
export const useJaspelCache = (config: Partial<CacheConfig> = {}) => {
  const defaultConfig: CacheConfig = {
    ttl: 300000, // 5 minutes
    maxSize: 50,
    strategy: 'lru',
    ...config
  };

  const baseCache = useCache(defaultConfig.ttl);
  const [gamingCache, setGamingCache] = useState<Map<string, any>>(new Map());
  const [achievementCache, setAchievementCache] = useState<Map<string, any>>(new Map());

  const setGamingData = useCallback((key: string, data: any) => {
    setGamingCache(prev => {
      const newCache = new Map(prev);
      
      // Implement LRU eviction if needed
      if (newCache.size >= defaultConfig.maxSize && !newCache.has(key)) {
        const firstKey = newCache.keys().next().value;
        if (firstKey) newCache.delete(firstKey);
      }
      
      newCache.set(key, {
        data,
        timestamp: Date.now(),
        ttl: defaultConfig.ttl
      });
      
      return newCache;
    });
  }, [defaultConfig]);

  const getGamingData = useCallback((key: string) => {
    const cached = gamingCache.get(key);
    
    if (!cached) return null;
    
    // Check if expired
    if (Date.now() - cached.timestamp > cached.ttl) {
      setGamingCache(prev => {
        const newCache = new Map(prev);
        newCache.delete(key);
        return newCache;
      });
      return null;
    }
    
    return cached.data;
  }, [gamingCache]);

  const cacheAchievement = useCallback((userId: string, achievement: any) => {
    const key = `achievement_${userId}`;
    setAchievementCache(prev => new Map(prev.set(key, {
      ...achievement,
      cachedAt: Date.now()
    })));
  }, []);

  const getAchievements = useCallback((userId: string) => {
    return achievementCache.get(`achievement_${userId}`);
  }, [achievementCache]);

  const clearGamingCache = useCallback(() => {
    setGamingCache(new Map());
    setAchievementCache(new Map());
  }, []);

  return {
    ...baseCache,
    setGamingData,
    getGamingData,
    cacheAchievement,
    getAchievements,
    clearGamingCache,
    gamingCacheSize: gamingCache.size,
    achievementCacheSize: achievementCache.size
  };
};

/**
 * Real-time connection monitoring for Jaspel WebSocket integration
 */
export const useJaspelRealtime = (userId: string, variant: JaspelVariant) => {
  const [connected, setConnected] = useState(false);
  const [notifications, setNotifications] = useState<RealtimeNotification[]>([]);
  const [lastUpdateTime, setLastUpdateTime] = useState<string>('Never');
  const [connectionAttempts, setConnectionAttempts] = useState(0);
  
  const echoRef = useRef<any>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout>();
  const performance = useJaspelPerformanceMonitoring(variant);

  const addNotification = useCallback((notification: Omit<RealtimeNotification, 'id'>) => {
    const newNotification: RealtimeNotification = {
      ...notification,
      id: Date.now(),
      timestamp: new Date().toLocaleTimeString()
    };
    
    setNotifications(prev => [newNotification, ...prev.slice(0, 4)]); // Keep last 5
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
      setNotifications(prev => prev.filter(n => n.id !== newNotification.id));
    }, 10000);
  }, []);

  const setupRealtimeConnection = useCallback(() => {
    if (typeof window === 'undefined' || !window.Echo) {
      console.log('⚠️ WebSocket not available, using polling mode');
      setConnected(false);
      return;
    }

    try {
      const startTime = performance.now();
      
      // Listen to private channel
      const channel = window.Echo.private(`${variant}.${userId}`);
      echoRef.current = channel;
      
      // Listen for validation events
      channel.listen('jaspel.validated', (event: any) => {
        const latency = performance.now() - startTime;
        performance.recordRealtimeLatency(latency);
        
        console.log('🎯 Real-time Jaspel validation received:', event);
        
        addNotification({
          type: 'success',
          title: 'Jaspel Updated!',
          message: event.message || 'Your Jaspel data has been updated',
          data: event
        });
        
        setLastUpdateTime(new Date().toLocaleTimeString());
      });

      // Listen for achievement unlocks
      channel.listen('achievement.unlocked', (event: any) => {
        console.log('🏆 Achievement unlocked:', event);
        
        addNotification({
          type: 'success',
          title: 'Achievement Unlocked!',
          message: `🏆 ${event.achievement_name}`,
          data: event
        });
      });

      // Connection status monitoring
      window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Jaspel WebSocket connected');
        setConnected(true);
        setConnectionAttempts(0);
      });

      window.Echo.connector.pusher.connection.bind('disconnected', () => {
        console.log('❌ Jaspel WebSocket disconnected');
        setConnected(false);
        
        // Attempt reconnection with exponential backoff
        const delay = Math.min(1000 * Math.pow(2, connectionAttempts), 30000);
        reconnectTimeoutRef.current = setTimeout(() => {
          setConnectionAttempts(prev => prev + 1);
          setupRealtimeConnection();
        }, delay);
      });

    } catch (error) {
      console.error('❌ Failed to setup Jaspel WebSocket:', error);
      setConnected(false);
      performance.recordError();
    }
  }, [userId, variant, addNotification, performance, connectionAttempts]);

  const disconnect = useCallback(() => {
    if (echoRef.current) {
      try {
        window.Echo.leave(`${variant}.${userId}`);
      } catch (error) {
        console.log('WebSocket cleanup error:', error);
      }
    }
    
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
    }
    
    setConnected(false);
  }, [userId, variant]);

  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  // Setup connection on mount
  useEffect(() => {
    setupRealtimeConnection();
    return disconnect;
  }, [setupRealtimeConnection, disconnect]);

  return {
    connected,
    notifications,
    lastUpdateTime,
    connectionAttempts,
    addNotification,
    clearNotifications,
    reconnect: setupRealtimeConnection,
    disconnect
  };
};

/**
 * Auto-refresh hook with intelligent rate limiting
 */
export const useJaspelAutoRefresh = (
  refreshFunction: () => Promise<void>,
  options: {
    enabled?: boolean;
    interval?: number;
    maxRetries?: number;
    backoffMultiplier?: number;
  } = {}
) => {
  const {
    enabled = false,
    interval = 60000, // 1 minute
    maxRetries = 3,
    backoffMultiplier = 2
  } = options;

  const [isRefreshing, setIsRefreshing] = useState(false);
  const [retryCount, setRetryCount] = useState(0);
  const [lastSuccessfulRefresh, setLastSuccessfulRefresh] = useState(Date.now());
  
  const intervalRef = useRef<NodeJS.Timeout>();
  const device = useDevice();

  const performRefresh = useCallback(async () => {
    if (isRefreshing) return;
    
    setIsRefreshing(true);
    
    try {
      await refreshFunction();
      setLastSuccessfulRefresh(Date.now());
      setRetryCount(0);
      console.log('✅ Jaspel auto-refresh successful');
    } catch (error) {
      console.warn('⚠️ Jaspel auto-refresh failed:', error);
      setRetryCount(prev => prev + 1);
    } finally {
      setIsRefreshing(false);
    }
  }, [refreshFunction, isRefreshing]);

  const startAutoRefresh = useCallback(() => {
    if (!enabled) return;
    
    // Adjust interval based on device and network conditions
    let adjustedInterval = interval;
    
    if (device.isMobile) {
      adjustedInterval *= 1.5; // Slower refresh on mobile
    }
    
    if (retryCount > 0) {
      adjustedInterval *= Math.pow(backoffMultiplier, Math.min(retryCount, maxRetries));
    }

    intervalRef.current = setTimeout(() => {
      const timeSinceLastSuccess = Date.now() - lastSuccessfulRefresh;
      
      // Only refresh if enough time has passed and we haven't exceeded retries
      if (timeSinceLastSuccess >= interval && retryCount < maxRetries) {
        performRefresh().then(() => {
          startAutoRefresh(); // Schedule next refresh
        });
      } else {
        startAutoRefresh(); // Schedule next check
      }
    }, adjustedInterval);
  }, [enabled, interval, retryCount, maxRetries, backoffMultiplier, lastSuccessfulRefresh, performRefresh, device.isMobile]);

  const stopAutoRefresh = useCallback(() => {
    if (intervalRef.current) {
      clearTimeout(intervalRef.current);
    }
  }, []);

  // Start/stop based on enabled state
  useEffect(() => {
    if (enabled) {
      startAutoRefresh();
    } else {
      stopAutoRefresh();
    }
    
    return stopAutoRefresh;
  }, [enabled, startAutoRefresh, stopAutoRefresh]);

  return {
    isRefreshing,
    retryCount,
    lastSuccessfulRefresh: new Date(lastSuccessfulRefresh),
    manualRefresh: performRefresh,
    start: startAutoRefresh,
    stop: stopAutoRefresh
  };
};

/**
 * Gaming achievement tracking hook
 */
export const useJaspelAchievements = (userId: string, variant: JaspelVariant) => {
  const [achievements, setAchievements] = useState<any[]>([]);
  const [unlockedToday, setUnlockedToday] = useState<any[]>([]);
  const [totalXP, setTotalXP] = useState(0);
  const [level, setLevel] = useState(1);
  
  const cache = useJaspelCache();

  const addAchievement = useCallback((achievement: any) => {
    setAchievements(prev => {
      const exists = prev.find(a => a.id === achievement.id);
      if (exists) return prev;
      
      const newAchievements = [...prev, {
        ...achievement,
        unlockedAt: new Date(),
        variant
      }];
      
      // Cache the achievement
      cache.cacheAchievement(userId, achievement);
      
      return newAchievements;
    });
    
    // Add to today's unlocked achievements
    setUnlockedToday(prev => [...prev, achievement]);
    
    // Update XP and level
    const xpGained = achievement.xp || 100;
    setTotalXP(prev => prev + xpGained);
    
    // Simple level calculation
    const newLevel = Math.floor((totalXP + xpGained) / 1000) + 1;
    if (newLevel > level) {
      setLevel(newLevel);
      
      // Level up notification would be handled by the calling component
      console.log(`🎉 Level up! You are now level ${newLevel}`);
    }
  }, [userId, variant, cache, totalXP, level]);

  const getAchievementsByType = useCallback((type: string) => {
    return achievements.filter(a => a.type === type);
  }, [achievements]);

  const getTodaysAchievements = useCallback(() => {
    const today = new Date().toDateString();
    return achievements.filter(a => 
      new Date(a.unlockedAt).toDateString() === today
    );
  }, [achievements]);

  return {
    achievements,
    unlockedToday,
    totalXP,
    level,
    addAchievement,
    getAchievementsByType,
    getTodaysAchievements,
    achievementCount: achievements.length
  };
};

/**
 * Export all Jaspel-specific hooks
 */
export {
  usePerformanceMonitoring,
  useCache,
  useDevice
} from '../schedule/hooks';