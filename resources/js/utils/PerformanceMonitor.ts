import React from 'react';

/**
 * Performance Monitor for Dashboard
 * Tracks and reports performance metrics with React integration
 */

interface PerformanceMetric {
  name: string;
  startTime: number;
  endTime?: number;
  duration?: number;
  status: 'pending' | 'success' | 'error';
  metadata?: Record<string, any>;
}

class PerformanceMonitor {
  private metrics: Map<string, PerformanceMetric> = new Map();
  private duplicateCalls: Map<string, number> = new Map();
  
  /**
   * Start tracking a performance metric
   */
  start(name: string, metadata?: Record<string, any>): void {
    // Check for duplicate calls
    const callCount = this.duplicateCalls.get(name) || 0;
    this.duplicateCalls.set(name, callCount + 1);
    
    if (callCount > 0) {
      console.warn(`⚠️ DUPLICATE CALL DETECTED: "${name}" called ${callCount + 1} times`);
    }
    
    this.metrics.set(name, {
      name,
      startTime: performance.now(),
      status: 'pending',
      metadata
    });
    
    console.log(`⏱️ Performance: "${name}" started`, metadata || '');
  }
  
  /**
   * End tracking and report duration
   */
  end(name: string, status: 'success' | 'error' = 'success'): number {
    const metric = this.metrics.get(name);
    
    if (!metric) {
      console.warn(`⚠️ No metric found for: ${name}`);
      return 0;
    }
    
    metric.endTime = performance.now();
    metric.duration = metric.endTime - metric.startTime;
    metric.status = status;
    
    // Color code based on duration
    const emoji = this.getPerformanceEmoji(metric.duration);
    const color = this.getPerformanceColor(metric.duration);
    
    console.log(
      `%c${emoji} Performance: "${name}" completed in ${metric.duration.toFixed(2)}ms`,
      `color: ${color}; font-weight: bold;`
    );
    
    // Warn if too slow
    if (metric.duration > 1000) {
      console.warn(`🐌 SLOW: "${name}" took ${(metric.duration / 1000).toFixed(2)} seconds!`);
    }
    
    return metric.duration;
  }
  
  /**
   * Get performance emoji based on duration
   */
  private getPerformanceEmoji(duration: number): string {
    if (duration < 100) return '🟢';
    if (duration < 500) return '🟡';
    if (duration < 1000) return '🟠';
    return '🔴';
  }
  
  /**
   * Get performance color based on duration
   */
  private getPerformanceColor(duration: number): string {
    if (duration < 100) return '#10b981';  // green
    if (duration < 500) return '#f59e0b';  // yellow
    if (duration < 1000) return '#fb923c'; // orange
    return '#ef4444'; // red
  }
  
  /**
   * Get summary report
   */
  getReport(): void {
    console.group('📊 Performance Report');
    
    let totalDuration = 0;
    const slowOperations: string[] = [];
    
    this.metrics.forEach((metric) => {
      if (metric.duration) {
        totalDuration += metric.duration;
        
        if (metric.duration > 500) {
          slowOperations.push(`${metric.name}: ${metric.duration.toFixed(2)}ms`);
        }
      }
    });
    
    console.log(`Total operations: ${this.metrics.size}`);
    console.log(`Total duration: ${totalDuration.toFixed(2)}ms`);
    console.log(`Average duration: ${(totalDuration / this.metrics.size).toFixed(2)}ms`);
    
    if (slowOperations.length > 0) {
      console.warn('🐌 Slow operations:', slowOperations);
    }
    
    // Report duplicate calls
    const duplicates: string[] = [];
    this.duplicateCalls.forEach((count, name) => {
      if (count > 1) {
        duplicates.push(`${name}: ${count} calls`);
      }
    });
    
    if (duplicates.length > 0) {
      console.info('ℹ️ Cache optimization: Duplicate calls prevented:', duplicates);
    }
    
    console.groupEnd();
  }
  
  /**
   * Clear all metrics
   */
  clear(): void {
    this.metrics.clear();
    this.duplicateCalls.clear();
  }
  
  /**
   * Get metrics for analysis
   */
  getMetrics(): PerformanceMetric[] {
    return Array.from(this.metrics.values());
  }
}

// Export singleton instance
export const performanceMonitor = new PerformanceMonitor();

// React hook for component performance monitoring
export const usePerformanceMonitor = (componentName: string) => {
  React.useEffect(() => {
    const startTime = performance.now();
    
    return () => {
      const endTime = performance.now();
      const duration = endTime - startTime;
      
      if (duration > 16.67) { // More than one frame at 60fps
        console.warn(`🐌 ${componentName} exceeded 16ms render budget: ${duration.toFixed(2)}ms`);
      }
    };
  });
};

// Measure async function performance
export const measureAsync = async <T>(
  name: string, 
  fn: () => Promise<T>
): Promise<T> => {
  performanceMonitor.start(name);
  try {
    const result = await fn();
    performanceMonitor.end(name, 'success');
    return result;
  } catch (error) {
    performanceMonitor.end(name, 'error');
    throw error;
  }
};

// Auto-report on page unload
if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', () => {
    performanceMonitor.getReport();
  });
  
  // Add to window for debugging
  (window as any).performanceMonitor = performanceMonitor;
}