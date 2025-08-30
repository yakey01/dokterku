/**
 * Shared Performance Monitoring Hooks
 * Custom hooks for performance tracking across schedule components
 */

import React, { useState, useCallback, useRef, useEffect } from 'react';
import { 
  PerformanceMetrics, 
  CacheEntry, 
  UsePerformanceMonitoringReturn,
  UseCacheReturn 
} from './types';
import { performanceUtils } from './utils';

/**
 * Performance monitoring hook for component render tracking
 */
export const usePerformanceMonitoring = (componentName: string): UsePerformanceMonitoringReturn => {
  const [metrics, setMetrics] = useState<PerformanceMetrics>({
    renderTime: 0,
    apiResponseTime: 0,
    cacheHits: 0,
    totalRequests: 0,
    memoryUsage: 0
  });

  const activeMarks = useRef<Map<string, number>>(new Map());

  const startMeasure = useCallback((name: string) => {
    const markName = `${componentName}-${name}-start`;
    performanceUtils.mark(markName);
    activeMarks.current.set(name, performance.now());
  }, [componentName]);

  const endMeasure = useCallback((name: string) => {
    const startTime = activeMarks.current.get(name);
    if (!startTime) return;

    const endTime = performance.now();
    const duration = endTime - startTime;
    
    const markName = `${componentName}-${name}`;
    performanceUtils.mark(`${markName}-end`);
    performanceUtils.measure(markName, `${markName}-start`, `${markName}-end`);

    // Warn about slow operations
    if (duration > 100) {
      console.warn(`🐌 Slow ${name} detected: ${componentName} took ${duration.toFixed(2)}ms`);
    }

    // Update specific metric
    if (name === 'render') {
      setMetrics(prev => ({ ...prev, renderTime: duration }));
    } else if (name === 'api') {
      setMetrics(prev => ({ 
        ...prev, 
        apiResponseTime: duration,
        totalRequests: prev.totalRequests + 1
      }));
    }

    activeMarks.current.delete(name);
  }, [componentName]);

  const updateMetric = useCallback((key: keyof PerformanceMetrics, value: number) => {
    setMetrics(prev => ({ ...prev, [key]: value }));
  }, []);

  const resetMetrics = useCallback(() => {
    setMetrics({
      renderTime: 0,
      apiResponseTime: 0,
      cacheHits: 0,
      totalRequests: 0,
      memoryUsage: 0
    });
    activeMarks.current.clear();
  }, []);

  // Track memory usage periodically
  useEffect(() => {
    const interval = setInterval(() => {
      const memoryUsage = performanceUtils.getMemoryUsage();
      if (memoryUsage > 0) {
        setMetrics(prev => ({ ...prev, memoryUsage }));
      }
    }, 5000); // Update every 5 seconds

    return () => clearInterval(interval);
  }, []);

  // Track component render time
  useEffect(() => {
    startMeasure('render');
    return () => {
      endMeasure('render');
    };
  });

  return {
    metrics,
    startMeasure,
    endMeasure,
    updateMetric,
    resetMetrics
  };
};

/**
 * Intelligent cache hook with TTL and size limits
 */
export const useCache = <T = any>(
  defaultTTL: number = 300000, // 5 minutes
  maxSize: number = 50
): UseCacheReturn<T> => {
  const cacheMap = useRef<Map<string, CacheEntry<T>>>(new Map());
  const [hitRate, setHitRate] = useState(0);
  const hits = useRef(0);
  const misses = useRef(0);

  const updateHitRate = useCallback(() => {
    const total = hits.current + misses.current;
    setHitRate(total > 0 ? (hits.current / total) * 100 : 0);
  }, []);

  const get = useCallback((key: string): T | null => {
    const entry = cacheMap.current.get(key);
    
    if (!entry) {
      misses.current++;
      updateHitRate();
      return null;
    }

    const now = Date.now();
    const isExpired = entry.ttl && (now - entry.timestamp > entry.ttl);
    
    if (isExpired) {
      cacheMap.current.delete(key);
      misses.current++;
      updateHitRate();
      return null;
    }

    hits.current++;
    updateHitRate();
    return entry.data;
  }, [updateHitRate]);

  const set = useCallback((key: string, data: T, ttl?: number): void => {
    // Clean up expired entries
    const now = Date.now();
    for (const [k, entry] of cacheMap.current.entries()) {
      const entryTTL = entry.ttl || defaultTTL;
      if (now - entry.timestamp > entryTTL) {
        cacheMap.current.delete(k);
      }
    }

    // Remove oldest entry if cache is full
    if (cacheMap.current.size >= maxSize) {
      const firstKey = cacheMap.current.keys().next().value;
      if (firstKey) {
        cacheMap.current.delete(firstKey);
      }
    }

    cacheMap.current.set(key, {
      data,
      timestamp: now,
      ttl: ttl || defaultTTL
    });
  }, [defaultTTL, maxSize]);

  const clear = useCallback((key?: string): void => {
    if (key) {
      cacheMap.current.delete(key);
    } else {
      cacheMap.current.clear();
      hits.current = 0;
      misses.current = 0;
      setHitRate(0);
    }
  }, []);

  const size = cacheMap.current.size;

  return {
    get,
    set,
    clear,
    size,
    hitRate
  };
};

/**
 * Device detection and responsive hook
 */
export const useDevice = () => {
  const [deviceInfo, setDeviceInfo] = useState(() => ({
    isMobile: typeof window !== 'undefined' && window.innerWidth < 768,
    isTablet: typeof window !== 'undefined' && window.innerWidth >= 768 && window.innerWidth < 1024,
    isDesktop: typeof window !== 'undefined' && window.innerWidth >= 1024,
    orientation: (typeof window !== 'undefined' && window.innerWidth > window.innerHeight) 
      ? 'landscape' as const 
      : 'portrait' as const,
    screenSize: 'md' as 'sm' | 'md' | 'lg' | 'xl'
  }));

  useEffect(() => {
    const updateDeviceInfo = () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      
      setDeviceInfo({
        isMobile: width < 768,
        isTablet: width >= 768 && width < 1024,
        isDesktop: width >= 1024,
        orientation: width > height ? 'landscape' : 'portrait',
        screenSize: width < 640 ? 'sm' : 
                   width < 768 ? 'md' : 
                   width < 1024 ? 'lg' : 'xl'
      });
    };

    updateDeviceInfo();
    window.addEventListener('resize', updateDeviceInfo);
    window.addEventListener('orientationchange', updateDeviceInfo);

    return () => {
      window.removeEventListener('resize', updateDeviceInfo);
      window.removeEventListener('orientationchange', updateDeviceInfo);
    };
  }, []);

  return deviceInfo;
};

/**
 * Enhanced HOC for performance monitoring
 */
export const withPerformanceMonitoring = <P extends object>(
  Component: React.ComponentType<P>,
  componentName: string
) => {
  return React.memo((props: P) => {
    const { metrics, startMeasure, endMeasure } = usePerformanceMonitoring(componentName);
    const renderStartTime = useRef(performance.now());
    
    useEffect(() => {
      const endTime = performance.now();
      const renderTime = endTime - renderStartTime.current;
      
      if (renderTime > 100) {
        console.warn(`🐌 Slow render detected: ${componentName} took ${renderTime.toFixed(2)}ms`);
      }
      
      console.log(`⚡ ${componentName} render time: ${renderTime.toFixed(2)}ms`);
    });

    return React.createElement(Component, props);
  });
};

/**
 * Mobile touch optimization hook
 */
export const useTouchOptimization = () => {
  const { isMobile } = useDevice();

  const handleTouchStart = useCallback((e: React.TouchEvent, elementId?: string) => {
    if (!isMobile) return;

    // Haptic feedback on supported devices
    if ('vibrate' in navigator) {
      navigator.vibrate(50);
    }
    
    // Add visual feedback
    const target = e.currentTarget as HTMLElement;
    target.style.transform = 'scale(0.98)';
    target.style.transition = 'transform 0.1s ease-out';
  }, [isMobile]);

  const handleTouchEnd = useCallback((e: React.TouchEvent) => {
    if (!isMobile) return;

    const target = e.currentTarget as HTMLElement;
    setTimeout(() => {
      target.style.transform = 'scale(1)';
    }, 100);
  }, [isMobile]);

  const getTouchClasses = useCallback(() => {
    return isMobile ? 'active:scale-95' : 'hover:scale-[1.02]';
  }, [isMobile]);

  return {
    handleTouchStart,
    handleTouchEnd,
    getTouchClasses,
    isMobile
  };
};

/**
 * Responsive classes hook for consistent breakpoint handling
 */
export const useResponsiveClasses = () => {
  const { isMobile, isTablet, orientation } = useDevice();

  const getResponsiveClasses = useCallback((baseClasses: string = 'space-y-4') => {
    if (isTablet && orientation === 'landscape') {
      return `${baseClasses} lg:space-y-6 xl:space-y-8`;
    } else if (isMobile) {
      return `${baseClasses} space-y-3`;
    } else {
      return `${baseClasses} md:space-y-6 lg:space-y-8`;
    }
  }, [isMobile, isTablet, orientation]);

  const getCardClasses = useCallback(() => {
    let classes = "shadow-lg hover:shadow-xl transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced group-hover:bg-white/90 dark:group-hover:bg-gray-900/90 overflow-hidden";
    
    if (isMobile) {
      classes += " active:scale-95";
    } else {
      classes += " hover:scale-[1.02]";
    }
    
    return classes;
  }, [isMobile]);

  return {
    getResponsiveClasses,
    getCardClasses,
    isMobile,
    isTablet,
    orientation
  };
};