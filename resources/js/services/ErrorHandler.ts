/**
 * Centralized Error Handler
 * Standardized error handling with recovery strategies and user-friendly messages
 */

import { ApiError, ApiErrorCode } from '../components/dokter/types/api';

// Error severity levels
export enum ErrorSeverity {
  LOW = 'low',        // Informational, can be ignored
  MEDIUM = 'medium',  // Warning, should be addressed
  HIGH = 'high',      // Error, needs attention
  CRITICAL = 'critical' // Critical, immediate action required
}

// Error categories
export enum ErrorCategory {
  NETWORK = 'network',
  AUTHENTICATION = 'authentication',
  VALIDATION = 'validation',
  PERMISSION = 'permission',
  SERVER = 'server',
  CLIENT = 'client',
  UNKNOWN = 'unknown'
}

// Recovery strategies
export enum RecoveryStrategy {
  RETRY = 'retry',
  REFRESH_TOKEN = 'refresh_token',
  RELOAD_PAGE = 'reload_page',
  CLEAR_CACHE = 'clear_cache',
  LOGOUT = 'logout',
  IGNORE = 'ignore',
  NOTIFY_USER = 'notify_user'
}

// Enhanced error interface
export interface EnhancedError extends ApiError {
  id: string;
  severity: ErrorSeverity;
  category: ErrorCategory;
  recovery: RecoveryStrategy[];
  userMessage: string;
  technicalMessage: string;
  context?: Record<string, any>;
  retry?: {
    attempts: number;
    maxAttempts: number;
    delay: number;
  };
}

// Error handler options
export interface ErrorHandlerOptions {
  logToConsole?: boolean;
  logToServer?: boolean;
  showToUser?: boolean;
  autoRecover?: boolean;
  maxRetries?: number;
}

class ErrorHandler {
  private static instance: ErrorHandler;
  private errorLog: EnhancedError[] = [];
  private errorCount: Map<string, number> = new Map();
  private recoveryInProgress: Map<string, boolean> = new Map();
  private options: ErrorHandlerOptions = {
    logToConsole: true,
    logToServer: false,
    showToUser: true,
    autoRecover: true,
    maxRetries: 3
  };

  private constructor() {
    this.setupGlobalErrorHandling();
  }

  static getInstance(): ErrorHandler {
    if (!ErrorHandler.instance) {
      ErrorHandler.instance = new ErrorHandler();
    }
    return ErrorHandler.instance;
  }

  /**
   * Setup global error handling
   */
  private setupGlobalErrorHandling(): void {
    if (typeof window === 'undefined') return;

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      this.handle(event.reason, {
        context: { type: 'unhandledrejection' }
      });
      event.preventDefault();
    });

    // Handle global errors
    window.addEventListener('error', (event) => {
      this.handle(event.error, {
        context: { 
          type: 'global_error',
          filename: event.filename,
          lineno: event.lineno,
          colno: event.colno
        }
      });
    });
  }

  /**
   * Configure error handler
   */
  configure(options: Partial<ErrorHandlerOptions>): void {
    this.options = { ...this.options, ...options };
  }

  /**
   * Handle error with standardized processing
   */
  handle(error: any, options?: Partial<ErrorHandlerOptions>): EnhancedError {
    const enhancedError = this.enhanceError(error);
    const mergedOptions = { ...this.options, ...options };

    // Log error
    this.logError(enhancedError, mergedOptions);

    // Track error frequency
    this.trackError(enhancedError);

    // Show to user if needed
    if (mergedOptions.showToUser) {
      this.showToUser(enhancedError);
    }

    // Auto-recover if enabled
    if (mergedOptions.autoRecover && !this.recoveryInProgress.get(enhancedError.id)) {
      this.attemptRecovery(enhancedError);
    }

    return enhancedError;
  }

  /**
   * Enhance error with additional metadata
   */
  private enhanceError(error: any): EnhancedError {
    const baseError = this.parseError(error);
    const category = this.categorizeError(baseError);
    const severity = this.determineSeverity(baseError, category);
    const recovery = this.determineRecoveryStrategies(baseError, category);
    const userMessage = this.generateUserMessage(baseError, category);

    return {
      ...baseError,
      id: this.generateErrorId(baseError),
      severity,
      category,
      recovery,
      userMessage,
      technicalMessage: baseError.message,
      timestamp: new Date().toISOString()
    };
  }

  /**
   * Parse error into standard format
   */
  private parseError(error: any): ApiError {
    if (error instanceof Error) {
      return {
        message: error.message,
        code: 'CLIENT_ERROR',
        stack: error.stack,
        timestamp: new Date().toISOString()
      };
    }

    if (typeof error === 'string') {
      return {
        message: error,
        code: 'STRING_ERROR',
        timestamp: new Date().toISOString()
      };
    }

    if (error?.response) {
      // Axios error
      return {
        message: error.response.data?.message || error.message,
        code: error.response.status,
        statusCode: error.response.status,
        errors: error.response.data?.errors,
        timestamp: new Date().toISOString()
      };
    }

    return {
      message: 'An unknown error occurred',
      code: 'UNKNOWN_ERROR',
      timestamp: new Date().toISOString()
    };
  }

  /**
   * Categorize error
   */
  private categorizeError(error: ApiError): ErrorCategory {
    const code = error.statusCode || error.code;

    if (typeof code === 'number') {
      if (code === 401 || code === 403) return ErrorCategory.AUTHENTICATION;
      if (code === 422) return ErrorCategory.VALIDATION;
      if (code >= 500) return ErrorCategory.SERVER;
      if (code >= 400) return ErrorCategory.CLIENT;
    }

    if (error.message?.toLowerCase().includes('network')) {
      return ErrorCategory.NETWORK;
    }

    if (error.message?.toLowerCase().includes('permission')) {
      return ErrorCategory.PERMISSION;
    }

    return ErrorCategory.UNKNOWN;
  }

  /**
   * Determine error severity
   */
  private determineSeverity(error: ApiError, category: ErrorCategory): ErrorSeverity {
    // Critical errors
    if (category === ErrorCategory.AUTHENTICATION) return ErrorSeverity.CRITICAL;
    if (error.statusCode === 500) return ErrorSeverity.CRITICAL;

    // High severity
    if (category === ErrorCategory.SERVER) return ErrorSeverity.HIGH;
    if (category === ErrorCategory.NETWORK) return ErrorSeverity.HIGH;

    // Medium severity
    if (category === ErrorCategory.VALIDATION) return ErrorSeverity.MEDIUM;
    if (category === ErrorCategory.PERMISSION) return ErrorSeverity.MEDIUM;

    // Low severity
    return ErrorSeverity.LOW;
  }

  /**
   * Determine recovery strategies
   */
  private determineRecoveryStrategies(error: ApiError, category: ErrorCategory): RecoveryStrategy[] {
    const strategies: RecoveryStrategy[] = [];

    switch (category) {
      case ErrorCategory.NETWORK:
        strategies.push(RecoveryStrategy.RETRY, RecoveryStrategy.NOTIFY_USER);
        break;
      case ErrorCategory.AUTHENTICATION:
        strategies.push(RecoveryStrategy.REFRESH_TOKEN, RecoveryStrategy.LOGOUT);
        break;
      case ErrorCategory.VALIDATION:
        strategies.push(RecoveryStrategy.NOTIFY_USER);
        break;
      case ErrorCategory.SERVER:
        strategies.push(RecoveryStrategy.RETRY, RecoveryStrategy.NOTIFY_USER);
        break;
      default:
        strategies.push(RecoveryStrategy.NOTIFY_USER);
    }

    return strategies;
  }

  /**
   * Generate user-friendly message
   */
  private generateUserMessage(error: ApiError, category: ErrorCategory): string {
    const messages: Record<ErrorCategory, string> = {
      [ErrorCategory.NETWORK]: 'Koneksi bermasalah. Mohon periksa jaringan Anda.',
      [ErrorCategory.AUTHENTICATION]: 'Sesi Anda telah berakhir. Silakan login kembali.',
      [ErrorCategory.VALIDATION]: 'Data yang Anda masukkan tidak valid. Mohon periksa kembali.',
      [ErrorCategory.PERMISSION]: 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
      [ErrorCategory.SERVER]: 'Server sedang mengalami gangguan. Mohon coba beberapa saat lagi.',
      [ErrorCategory.CLIENT]: 'Terjadi kesalahan. Mohon coba lagi.',
      [ErrorCategory.UNKNOWN]: 'Terjadi kesalahan yang tidak diketahui.'
    };

    return messages[category] || messages[ErrorCategory.UNKNOWN];
  }

  /**
   * Generate unique error ID
   */
  private generateErrorId(error: ApiError): string {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substr(2, 9);
    const code = error.code || 'UNKNOWN';
    return `${code}-${timestamp}-${random}`;
  }

  /**
   * Log error
   */
  private logError(error: EnhancedError, options: ErrorHandlerOptions): void {
    // Add to error log
    this.errorLog.push(error);
    
    // Keep only last 100 errors
    if (this.errorLog.length > 100) {
      this.errorLog.shift();
    }

    // Console logging
    if (options.logToConsole) {
      const emoji = this.getSeverityEmoji(error.severity);
      const color = this.getSeverityColor(error.severity);
      
      console.group(`${emoji} Error: ${error.id}`);
      console.log(`%cSeverity: ${error.severity}`, `color: ${color}; font-weight: bold;`);
      console.log(`Category: ${error.category}`);
      console.log(`Message: ${error.message}`);
      console.log(`User Message: ${error.userMessage}`);
      console.log(`Recovery Strategies:`, error.recovery);
      
      if (error.context) {
        console.log('Context:', error.context);
      }
      
      if (error.stack) {
        console.log('Stack:', error.stack);
      }
      
      console.groupEnd();
    }

    // Server logging (if enabled)
    if (options.logToServer) {
      this.logToServer(error);
    }
  }

  /**
   * Track error frequency
   */
  private trackError(error: EnhancedError): void {
    const key = `${error.category}-${error.code}`;
    const count = this.errorCount.get(key) || 0;
    this.errorCount.set(key, count + 1);

    // Alert if error is recurring frequently
    if (count > 5) {
      console.warn(`⚠️ Recurring error detected: ${key} (${count} times)`);
    }
  }

  /**
   * Show error to user
   */
  private showToUser(error: EnhancedError): void {
    // This would integrate with your UI notification system
    // For now, we'll use console.warn
    console.warn(`📢 User notification: ${error.userMessage}`);
    
    // You can dispatch an event for UI components to listen to
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('app:error', { 
        detail: {
          id: error.id,
          message: error.userMessage,
          severity: error.severity
        }
      }));
    }
  }

  /**
   * Attempt automatic recovery
   */
  private async attemptRecovery(error: EnhancedError): Promise<void> {
    this.recoveryInProgress.set(error.id, true);

    for (const strategy of error.recovery) {
      try {
        console.log(`🔧 Attempting recovery: ${strategy}`);
        
        switch (strategy) {
          case RecoveryStrategy.RETRY:
            // Implement retry logic
            await this.retryOperation(error);
            break;
            
          case RecoveryStrategy.REFRESH_TOKEN:
            // Implement token refresh
            await this.refreshToken();
            break;
            
          case RecoveryStrategy.CLEAR_CACHE:
            // Clear cache
            await this.clearCache();
            break;
            
          case RecoveryStrategy.RELOAD_PAGE:
            // Reload page after delay
            setTimeout(() => window.location.reload(), 2000);
            break;
            
          case RecoveryStrategy.LOGOUT:
            // Implement logout
            await this.logout();
            break;
            
          case RecoveryStrategy.NOTIFY_USER:
            // Already handled in showToUser
            break;
            
          case RecoveryStrategy.IGNORE:
            // Do nothing
            break;
        }
        
        // If recovery succeeded, stop trying other strategies
        console.log(`✅ Recovery successful: ${strategy}`);
        break;
        
      } catch (recoveryError) {
        console.error(`❌ Recovery failed: ${strategy}`, recoveryError);
      }
    }

    this.recoveryInProgress.set(error.id, false);
  }

  /**
   * Retry operation
   */
  private async retryOperation(error: EnhancedError): Promise<void> {
    // This would be implemented based on your specific retry logic
    await new Promise(resolve => setTimeout(resolve, 1000));
  }

  /**
   * Refresh authentication token
   */
  private async refreshToken(): Promise<void> {
    // This would integrate with your auth system
    console.log('Refreshing token...');
  }

  /**
   * Clear cache
   */
  private async clearCache(): Promise<void> {
    const { default: CacheManager } = await import('./CacheManager');
    await CacheManager.clear();
  }

  /**
   * Logout user
   */
  private async logout(): Promise<void> {
    // This would integrate with your auth system
    console.log('Logging out...');
  }

  /**
   * Log error to server
   */
  private async logToServer(error: EnhancedError): Promise<void> {
    // This would send error to your logging service
    console.log('Logging to server:', error.id);
  }

  /**
   * Get severity emoji
   */
  private getSeverityEmoji(severity: ErrorSeverity): string {
    const emojis: Record<ErrorSeverity, string> = {
      [ErrorSeverity.LOW]: 'ℹ️',
      [ErrorSeverity.MEDIUM]: '⚠️',
      [ErrorSeverity.HIGH]: '❌',
      [ErrorSeverity.CRITICAL]: '🚨'
    };
    return emojis[severity];
  }

  /**
   * Get severity color
   */
  private getSeverityColor(severity: ErrorSeverity): string {
    const colors: Record<ErrorSeverity, string> = {
      [ErrorSeverity.LOW]: '#3b82f6',    // blue
      [ErrorSeverity.MEDIUM]: '#f59e0b', // yellow
      [ErrorSeverity.HIGH]: '#ef4444',   // red
      [ErrorSeverity.CRITICAL]: '#dc2626' // dark red
    };
    return colors[severity];
  }

  /**
   * Get error statistics
   */
  getStats(): {
    total: number;
    byCategory: Record<ErrorCategory, number>;
    bySeverity: Record<ErrorSeverity, number>;
    recent: EnhancedError[];
  } {
    const byCategory: Record<ErrorCategory, number> = {
      [ErrorCategory.NETWORK]: 0,
      [ErrorCategory.AUTHENTICATION]: 0,
      [ErrorCategory.VALIDATION]: 0,
      [ErrorCategory.PERMISSION]: 0,
      [ErrorCategory.SERVER]: 0,
      [ErrorCategory.CLIENT]: 0,
      [ErrorCategory.UNKNOWN]: 0
    };

    const bySeverity: Record<ErrorSeverity, number> = {
      [ErrorSeverity.LOW]: 0,
      [ErrorSeverity.MEDIUM]: 0,
      [ErrorSeverity.HIGH]: 0,
      [ErrorSeverity.CRITICAL]: 0
    };

    this.errorLog.forEach(error => {
      byCategory[error.category]++;
      bySeverity[error.severity]++;
    });

    return {
      total: this.errorLog.length,
      byCategory,
      bySeverity,
      recent: this.errorLog.slice(-10)
    };
  }

  /**
   * Clear error log
   */
  clearLog(): void {
    this.errorLog = [];
    this.errorCount.clear();
  }
}

// Export singleton instance
export default ErrorHandler.getInstance();