/**
 * ResizeObserver Performance Monitor
 * 
 * Real-time monitoring and alerting for ResizeObserver-related performance issues
 * Specifically optimized for medical dashboard components
 */

interface PerformanceMetrics {
  resizeLoopCount: number;
  memoryUsage: number;
  memoryBaseline: number;
  cpuUsage: number;
  frameRate: number;
  averageRenderTime: number;
  componentMetrics: Map<string, ComponentMetric>;
}

interface ComponentMetric {
  name: string;
  resizeEventCount: number;
  averageRenderTime: number;
  memoryImpact: number;
  errorCount: number;
  lastError?: string;
}

interface AlertConfig {
  memoryLeakThreshold: number; // MB
  resizeLoopThreshold: number; // events per minute
  frameRateThreshold: number; // fps
  renderTimeThreshold: number; // ms
}

export class ResizeObserverPerformanceMonitor {
  private static instance: ResizeObserverPerformanceMonitor;
  private metrics: PerformanceMetrics;
  private alertConfig: AlertConfig;
  private monitoringActive = false;
  private performanceObserver?: PerformanceObserver;
  private frameCountTimer?: NodeJS.Timer;
  private memoryTimer?: NodeJS.Timer;

  // Component-specific tracking
  private componentObservers = new Map<string, {
    startTime: number;
    resizeCount: number;
    memoryStart: number;
  }>();

  private constructor() {
    this.metrics = {
      resizeLoopCount: 0,
      memoryUsage: 0,
      memoryBaseline: 0,
      cpuUsage: 0,
      frameRate: 60,
      averageRenderTime: 0,
      componentMetrics: new Map()
    };

    this.alertConfig = {
      memoryLeakThreshold: 50, // 50MB
      resizeLoopThreshold: 30, // 30 events per minute
      frameRateThreshold: 45, // 45fps minimum
      renderTimeThreshold: 200 // 200ms
    };
  }

  static getInstance(): ResizeObserverPerformanceMonitor {
    if (!this.instance) {
      this.instance = new ResizeObserverPerformanceMonitor();
    }
    return this.instance;
  }

  /**
   * Start monitoring ResizeObserver performance
   */
  startMonitoring(): void {
    if (this.monitoringActive) return;
    
    this.monitoringActive = true;
    this.recordMemoryBaseline();
    this.setupResizeObserverInterception();
    this.startFrameRateMonitoring();
    this.startMemoryMonitoring();
    this.setupPerformanceObserver();

    console.log('🔍 ResizeObserver Performance Monitoring Started');
  }

  /**
   * Stop monitoring
   */
  stopMonitoring(): void {
    if (!this.monitoringActive) return;

    this.monitoringActive = false;
    
    if (this.performanceObserver) {
      this.performanceObserver.disconnect();
    }
    
    if (this.frameCountTimer) {
      clearInterval(this.frameCountTimer);
    }
    
    if (this.memoryTimer) {
      clearInterval(this.memoryTimer);
    }

    console.log('⏹️ ResizeObserver Performance Monitoring Stopped');
  }

  /**
   * Record memory baseline
   */
  private recordMemoryBaseline(): void {
    if ('memory' in performance) {
      this.metrics.memoryBaseline = (performance as any).memory.usedJSHeapSize;
      this.metrics.memoryUsage = this.metrics.memoryBaseline;
    }
  }

  /**
   * Intercept ResizeObserver loops via console.error monitoring
   */
  private setupResizeObserverInterception(): void {
    const originalConsoleError = console.error;
    
    console.error = (...args: any[]) => {
      // Check for ResizeObserver loop error
      const errorMessage = args[0]?.toString?.() || '';
      if (errorMessage.includes('ResizeObserver loop')) {
        this.handleResizeObserverLoop(errorMessage);
      }
      
      // Call original console.error
      originalConsoleError.apply(console, args);
    };

    // Also monitor via window.onerror for additional coverage
    const originalOnError = window.onerror;
    window.onerror = (message, source, lineno, colno, error) => {
      if (typeof message === 'string' && message.includes('ResizeObserver')) {
        this.handleResizeObserverLoop(message);
      }
      
      if (originalOnError) {
        return originalOnError(message, source, lineno, colno, error);
      }
      return false;
    };
  }

  /**
   * Handle ResizeObserver loop detection
   */
  private handleResizeObserverLoop(errorMessage: string): void {
    this.metrics.resizeLoopCount++;
    
    const now = Date.now();
    const component = this.identifyComponentFromError(errorMessage);
    
    // Update component metrics
    if (component) {
      const componentMetric = this.getOrCreateComponentMetric(component);
      componentMetric.resizeEventCount++;
    }

    // Check if we need to alert
    this.checkAlertThresholds();

    // Log performance impact
    this.logPerformanceImpact();
  }

  /**
   * Try to identify component from error stack or context
   */
  private identifyComponentFromError(errorMessage: string): string | null {
    // Try to extract component name from error context
    const stackTrace = new Error().stack;
    
    if (stackTrace) {
      // Look for common medical dashboard components
      const componentPatterns = [
        /HolisticMedicalDashboard/,
        /ChartContainer/,
        /ProgressBarAnimation/,
        /LeafletMap/,
        /JaspelComponent/,
        /CreativeAttendanceDashboard/
      ];

      for (const pattern of componentPatterns) {
        if (pattern.test(stackTrace)) {
          return pattern.toString().slice(1, -1); // Remove regex delimiters
        }
      }
    }

    return 'Unknown';
  }

  /**
   * Get or create component metric
   */
  private getOrCreateComponentMetric(componentName: string): ComponentMetric {
    if (!this.metrics.componentMetrics.has(componentName)) {
      this.metrics.componentMetrics.set(componentName, {
        name: componentName,
        resizeEventCount: 0,
        averageRenderTime: 0,
        memoryImpact: 0,
        errorCount: 0
      });
    }
    return this.metrics.componentMetrics.get(componentName)!;
  }

  /**
   * Start monitoring frame rate
   */
  private startFrameRateMonitoring(): void {
    let frameCount = 0;
    let lastTime = performance.now();

    const countFrames = () => {
      if (!this.monitoringActive) return;
      
      frameCount++;
      const currentTime = performance.now();
      
      if (currentTime - lastTime >= 1000) {
        this.metrics.frameRate = frameCount / ((currentTime - lastTime) / 1000);
        frameCount = 0;
        lastTime = currentTime;

        // Check frame rate threshold
        if (this.metrics.frameRate < this.alertConfig.frameRateThreshold) {
          this.alertLowFrameRate();
        }
      }
      
      requestAnimationFrame(countFrames);
    };

    requestAnimationFrame(countFrames);
  }

  /**
   * Start monitoring memory usage
   */
  private startMemoryMonitoring(): void {
    this.memoryTimer = setInterval(() => {
      if ('memory' in performance) {
        const currentMemory = (performance as any).memory.usedJSHeapSize;
        this.metrics.memoryUsage = currentMemory;
        
        const memoryIncrease = (currentMemory - this.metrics.memoryBaseline) / (1024 * 1024);
        
        if (memoryIncrease > this.alertConfig.memoryLeakThreshold) {
          this.alertMemoryLeak(memoryIncrease);
        }
      }
    }, 5000); // Check every 5 seconds
  }

  /**
   * Setup performance observer for render timing
   */
  private setupPerformanceObserver(): void {
    if ('PerformanceObserver' in window) {
      this.performanceObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        
        let totalDuration = 0;
        let renderCount = 0;
        
        entries.forEach(entry => {
          if (entry.entryType === 'measure' || entry.entryType === 'paint') {
            totalDuration += entry.duration;
            renderCount++;
          }
        });

        if (renderCount > 0) {
          this.metrics.averageRenderTime = totalDuration / renderCount;
        }
      });

      this.performanceObserver.observe({
        entryTypes: ['measure', 'paint', 'navigation']
      });
    }
  }

  /**
   * Check alert thresholds and trigger alerts
   */
  private checkAlertThresholds(): void {
    const recentLoops = this.getRecentResizeLoops();
    
    if (recentLoops > this.alertConfig.resizeLoopThreshold) {
      this.alertExcessiveResizeLoops(recentLoops);
    }
  }

  /**
   * Get recent resize loops count (last minute)
   */
  private getRecentResizeLoops(): number {
    // Simplified - in real implementation, track timestamps
    return this.metrics.resizeLoopCount;
  }

  /**
   * Alert handlers
   */
  private alertMemoryLeak(memoryIncrease: number): void {
    const alert = {
      type: 'MEMORY_LEAK',
      severity: memoryIncrease > 100 ? 'CRITICAL' : 'WARNING',
      message: `ResizeObserver memory leak detected: +${memoryIncrease.toFixed(1)}MB`,
      metrics: this.getMetrics(),
      recommendations: [
        'Check for uncleaned ResizeObserver instances',
        'Verify proper cleanup in useEffect return functions',
        'Consider using OptimizedResizeObserver utility'
      ]
    };

    this.sendAlert(alert);
  }

  private alertExcessiveResizeLoops(loopCount: number): void {
    const alert = {
      type: 'EXCESSIVE_RESIZE_LOOPS',
      severity: loopCount > 50 ? 'CRITICAL' : 'WARNING',
      message: `Excessive ResizeObserver loops: ${loopCount} in last minute`,
      metrics: this.getMetrics(),
      recommendations: [
        'Implement debouncing for resize handlers',
        'Check for circular dependencies in resize callbacks',
        'Use fixed dimensions where possible'
      ]
    };

    this.sendAlert(alert);
  }

  private alertLowFrameRate(): void {
    const alert = {
      type: 'LOW_FRAME_RATE',
      severity: this.metrics.frameRate < 30 ? 'CRITICAL' : 'WARNING',
      message: `Low frame rate detected: ${this.metrics.frameRate.toFixed(1)}fps`,
      metrics: this.getMetrics(),
      recommendations: [
        'Optimize ResizeObserver callback performance',
        'Reduce DOM mutations during resize events',
        'Consider using CSS transforms instead of layout changes'
      ]
    };

    this.sendAlert(alert);
  }

  /**
   * Send alert to monitoring systems
   */
  private sendAlert(alert: any): void {
    console.warn('🚨 ResizeObserver Performance Alert:', alert);

    // Send to external monitoring (implement based on your monitoring stack)
    if (typeof window !== 'undefined') {
      // Google Analytics 4 Event
      if ('gtag' in window) {
        (window as any).gtag('event', 'performance_alert', {
          event_category: 'ResizeObserver',
          event_label: alert.type,
          value: this.metrics.resizeLoopCount,
          custom_parameters: {
            severity: alert.severity,
            frame_rate: this.metrics.frameRate,
            memory_usage: this.metrics.memoryUsage
          }
        });
      }

      // Custom monitoring endpoint
      if (process.env.NODE_ENV === 'production') {
        fetch('/api/monitoring/performance-alert', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(alert)
        }).catch(console.error);
      }
    }
  }

  /**
   * Log performance impact details
   */
  private logPerformanceImpact(): void {
    const memoryIncrease = (this.metrics.memoryUsage - this.metrics.memoryBaseline) / (1024 * 1024);
    
    console.group('📊 ResizeObserver Performance Impact');
    console.log('Resize Loops:', this.metrics.resizeLoopCount);
    console.log('Memory Impact:', `+${memoryIncrease.toFixed(2)}MB`);
    console.log('Frame Rate:', `${this.metrics.frameRate.toFixed(1)}fps`);
    console.log('Average Render Time:', `${this.metrics.averageRenderTime.toFixed(1)}ms`);
    
    if (this.metrics.componentMetrics.size > 0) {
      console.log('Component Breakdown:');
      this.metrics.componentMetrics.forEach((metric, component) => {
        console.log(`  ${component}: ${metric.resizeEventCount} events`);
      });
    }
    
    console.groupEnd();
  }

  /**
   * Get current metrics
   */
  getMetrics(): PerformanceMetrics {
    return { ...this.metrics };
  }

  /**
   * Get medical dashboard specific health score
   */
  getMedicalDashboardHealthScore(): {
    score: number;
    status: 'healthy' | 'warning' | 'critical';
    issues: string[];
  } {
    let score = 100;
    const issues: string[] = [];

    // Memory impact
    const memoryIncrease = (this.metrics.memoryUsage - this.metrics.memoryBaseline) / (1024 * 1024);
    if (memoryIncrease > 20) {
      score -= 25;
      issues.push(`High memory usage: +${memoryIncrease.toFixed(1)}MB`);
    }

    // Frame rate
    if (this.metrics.frameRate < 45) {
      score -= 20;
      issues.push(`Low frame rate: ${this.metrics.frameRate.toFixed(1)}fps`);
    }

    // Resize loops
    if (this.metrics.resizeLoopCount > 10) {
      score -= 15;
      issues.push(`Excessive resize loops: ${this.metrics.resizeLoopCount}`);
    }

    // Render performance
    if (this.metrics.averageRenderTime > 100) {
      score -= 10;
      issues.push(`Slow rendering: ${this.metrics.averageRenderTime.toFixed(1)}ms`);
    }

    const status = score > 80 ? 'healthy' : score > 60 ? 'warning' : 'critical';

    return { score: Math.max(0, score), status, issues };
  }

  /**
   * Generate performance report for medical dashboard
   */
  generateMedicalDashboardReport(): any {
    const health = this.getMedicalDashboardHealthScore();
    const metrics = this.getMetrics();

    return {
      timestamp: new Date().toISOString(),
      health,
      metrics: {
        resizeLoops: metrics.resizeLoopCount,
        memoryUsage: `${((metrics.memoryUsage - metrics.memoryBaseline) / (1024 * 1024)).toFixed(2)}MB`,
        frameRate: `${metrics.frameRate.toFixed(1)}fps`,
        renderTime: `${metrics.averageRenderTime.toFixed(1)}ms`
      },
      componentBreakdown: Array.from(metrics.componentMetrics.entries()).map(([name, metric]) => ({
        component: name,
        resizeEvents: metric.resizeEventCount,
        renderTime: `${metric.averageRenderTime.toFixed(1)}ms`,
        errors: metric.errorCount
      })),
      recommendations: this.getRecommendations(health.score)
    };
  }

  /**
   * Get optimization recommendations based on performance score
   */
  private getRecommendations(score: number): string[] {
    const recommendations = [];

    if (score < 60) {
      recommendations.push('URGENT: Implement OptimizedResizeObserver utility');
      recommendations.push('URGENT: Check for ResizeObserver memory leaks');
    }

    if (score < 80) {
      recommendations.push('Add debouncing to chart resize handlers');
      recommendations.push('Optimize progress bar animations');
      recommendations.push('Review map component resize behavior');
    }

    recommendations.push('Monitor performance regularly');
    recommendations.push('Set up automated performance alerts');

    return recommendations;
  }
}

// Initialize global monitoring instance
const performanceMonitor = ResizeObserverPerformanceMonitor.getInstance();

// Auto-start in development mode
if (process.env.NODE_ENV === 'development') {
  performanceMonitor.startMonitoring();
}

export default performanceMonitor;