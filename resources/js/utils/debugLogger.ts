/**
 * Smart Debug Logger - Only logs in development mode
 * Automatically detects environment and provides controlled debugging
 */

interface DebugConfig {
  enabled: boolean;
  level: 'info' | 'warn' | 'error' | 'debug';
  prefix: string;
}

class DebugLogger {
  private config: DebugConfig;
  
  constructor() {
    // Auto-detect environment
    const isDevelopment = this.isDevelopmentMode();
    
    this.config = {
      enabled: isDevelopment,
      level: 'debug',
      prefix: '🔧'
    };
  }
  
  private isDevelopmentMode(): boolean {
    try {
      // Multiple ways to detect development mode
      return (
        // Vite development mode
        import.meta.env?.DEV === true ||
        // Node environment check
        process?.env?.NODE_ENV === 'development' ||
        // Laravel development mode
        window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1' ||
        window.location.hostname.includes('local') ||
        window.location.port !== '' ||
        // Development domains
        window.location.hostname.includes('dev') ||
        window.location.hostname.includes('staging') ||
        window.location.hostname.includes('test') ||
        // Custom debug flag (for manual testing)
        localStorage.getItem('debug_mode') === 'true' ||
        // URL parameter (for manual testing)
        new URLSearchParams(window.location.search).has('debug') ||
        // Laravel APP_DEBUG equivalent check
        document.querySelector('meta[name="app-debug"]')?.getAttribute('content') === 'true'
      );
    } catch (error) {
      // If any error occurs in detection, assume production (safe mode)
      return false;
    }
  }
  
  // Main logging methods
  log(message: string, data?: any) {
    if (!this.config.enabled) return;
    
    if (data !== undefined) {
      console.log(`${this.config.prefix} ${message}`, data);
    } else {
      console.log(`${this.config.prefix} ${message}`);
    }
  }
  
  info(message: string, data?: any) {
    if (!this.config.enabled) return;
    
    if (data !== undefined) {
      console.info(`ℹ️ ${message}`, data);
    } else {
      console.info(`ℹ️ ${message}`);
    }
  }
  
  warn(message: string, data?: any) {
    if (!this.config.enabled) return;
    
    if (data !== undefined) {
      console.warn(`⚠️ ${message}`, data);
    } else {
      console.warn(`⚠️ ${message}`);
    }
  }
  
  error(message: string, data?: any) {
    if (!this.config.enabled) return;
    
    if (data !== undefined) {
      console.error(`❌ ${message}`, data);
    } else {
      console.error(`❌ ${message}`);
    }
  }
  
  // Specialized debug methods
  api(endpoint: string, response?: any) {
    if (!this.config.enabled) return;
    
    if (response) {
      console.log(`🌐 API Call: ${endpoint}`, response);
    } else {
      console.log(`🌐 API Call: ${endpoint}`);
    }
  }
  
  performance(label: string, duration?: number) {
    if (!this.config.enabled) return;
    
    if (duration !== undefined) {
      console.log(`⚡ Performance: ${label} took ${duration}ms`);
    } else {
      console.log(`⚡ Performance: ${label}`);
    }
  }
  
  state(component: string, state: any) {
    if (!this.config.enabled) return;
    console.log(`📊 State Update [${component}]:`, state);
  }
  
  transform(step: string, data: any) {
    if (!this.config.enabled) return;
    console.log(`🔄 Transform [${step}]:`, data);
  }
  
  // Group logging for complex operations
  group(title: string, callback: () => void) {
    if (!this.config.enabled) {
      callback();
      return;
    }
    
    console.group(`📁 ${title}`);
    callback();
    console.groupEnd();
  }
  
  // Table logging for arrays/objects
  table(label: string, data: any) {
    if (!this.config.enabled) return;
    
    console.log(`📋 ${label}:`);
    console.table(data);
  }
  
  // Toggle debug on/off
  toggle() {
    this.config.enabled = !this.config.enabled;
    localStorage.setItem('debug_mode', this.config.enabled.toString());
    console.log(`🔧 Debug mode ${this.config.enabled ? 'ENABLED' : 'DISABLED'}`);
  }
  
  // Check if debug is enabled
  isEnabled(): boolean {
    return this.config.enabled;
  }
  
  // Production-safe logging (only for critical errors)
  prodError(message: string, data?: any) {
    // Always log critical errors, even in production
    if (data !== undefined) {
      console.error(`🚨 CRITICAL: ${message}`, data);
    } else {
      console.error(`🚨 CRITICAL: ${message}`);
    }
  }
  
  // Performance monitoring (always enabled for optimization)
  prodPerformance(label: string, duration: number) {
    // Only log if performance is concerning (>1000ms)
    if (duration > 1000) {
      console.warn(`⚡ SLOW: ${label} took ${duration}ms`);
    }
  }
  
  // Get environment info for debugging
  getEnvInfo() {
    return {
      isDev: this.config.enabled,
      hostname: window.location.hostname,
      port: window.location.port,
      href: window.location.href,
      userAgent: navigator.userAgent,
      timestamp: new Date().toISOString()
    };
  }
}

// Create singleton instance
const debugLogger = new DebugLogger();

// Export for use throughout the app
export default debugLogger;

// Also export as named exports for convenience
export const debug = debugLogger;
export const log = debugLogger.log.bind(debugLogger);
export const info = debugLogger.info.bind(debugLogger);
export const warn = debugLogger.warn.bind(debugLogger);
export const error = debugLogger.error.bind(debugLogger);
export const api = debugLogger.api.bind(debugLogger);
export const performance = debugLogger.performance.bind(debugLogger);
export const state = debugLogger.state.bind(debugLogger);
export const transform = debugLogger.transform.bind(debugLogger);
export const group = debugLogger.group.bind(debugLogger);
export const table = debugLogger.table.bind(debugLogger);
export const prodError = debugLogger.prodError.bind(debugLogger);
export const prodPerformance = debugLogger.prodPerformance.bind(debugLogger);