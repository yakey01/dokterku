/**
 * Standardized Error Handling and Loading States
 * Unified error management system for all Jaspel components
 */

import { JaspelVariant } from './types';

// Error types
export type ErrorSeverity = 'low' | 'medium' | 'high' | 'critical';
export type ErrorCategory = 'network' | 'authentication' | 'validation' | 'permission' | 'system' | 'unknown';

export interface JaspelError {
  id: string;
  message: string;
  userMessage: string;
  category: ErrorCategory;
  severity: ErrorSeverity;
  code?: string | number;
  timestamp: number;
  context?: Record<string, any>;
  stack?: string;
  retryable: boolean;
  retryCount: number;
  maxRetries: number;
}

export interface LoadingState {
  isLoading: boolean;
  loadingMessage?: string;
  progress?: number;
  stage?: string;
  startTime?: number;
  estimatedDuration?: number;
}

export interface ErrorState {
  hasError: boolean;
  errors: JaspelError[];
  lastError?: JaspelError;
  errorCount: number;
  recoverySuggestions: string[];
}

// Error factory functions
export class JaspelErrorFactory {
  private static generateId(): string {
    return `err_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  static createNetworkError(
    message: string,
    context?: Record<string, any>
  ): JaspelError {
    return {
      id: this.generateId(),
      message,
      userMessage: 'Gagal terhubung ke server. Periksa koneksi internet Anda.',
      category: 'network',
      severity: 'medium',
      timestamp: Date.now(),
      context,
      retryable: true,
      retryCount: 0,
      maxRetries: 3
    };
  }

  static createAuthenticationError(
    message: string,
    context?: Record<string, any>
  ): JaspelError {
    return {
      id: this.generateId(),
      message,
      userMessage: 'Sesi telah berakhir. Silakan login kembali.',
      category: 'authentication',
      severity: 'high',
      timestamp: Date.now(),
      context,
      retryable: false,
      retryCount: 0,
      maxRetries: 0
    };
  }

  static createPermissionError(
    message: string,
    context?: Record<string, any>
  ): JaspelError {
    return {
      id: this.generateId(),
      message,
      userMessage: 'Tidak memiliki izin untuk mengakses data ini.',
      category: 'permission',
      severity: 'high',
      timestamp: Date.now(),
      context,
      retryable: false,
      retryCount: 0,
      maxRetries: 0
    };
  }

  static createValidationError(
    message: string,
    context?: Record<string, any>
  ): JaspelError {
    return {
      id: this.generateId(),
      message,
      userMessage: 'Data tidak valid. Periksa kembali input Anda.',
      category: 'validation',
      severity: 'low',
      timestamp: Date.now(),
      context,
      retryable: false,
      retryCount: 0,
      maxRetries: 0
    };
  }

  static createSystemError(
    message: string,
    context?: Record<string, any>
  ): JaspelError {
    return {
      id: this.generateId(),
      message,
      userMessage: 'Terjadi kesalahan sistem. Tim teknis telah diberitahu.',
      category: 'system',
      severity: 'critical',
      timestamp: Date.now(),
      context,
      retryable: true,
      retryCount: 0,
      maxRetries: 2
    };
  }

  static createUnknownError(
    message: string,
    context?: Record<string, any>
  ): JaspelError {
    return {
      id: this.generateId(),
      message,
      userMessage: 'Terjadi kesalahan yang tidak diketahui.',
      category: 'unknown',
      severity: 'medium',
      timestamp: Date.now(),
      context,
      retryable: true,
      retryCount: 0,
      maxRetries: 1
    };
  }

  static fromHttpStatus(
    status: number,
    message: string,
    endpoint?: string
  ): JaspelError {
    const context = { status, endpoint };

    switch (status) {
      case 401:
        return this.createAuthenticationError(message, context);
      case 403:
        return this.createPermissionError(message, context);
      case 422:
        return this.createValidationError(message, context);
      case 500:
      case 502:
      case 503:
        return this.createSystemError(message, context);
      case 0:
      case 408:
      case 504:
        return this.createNetworkError(message, context);
      default:
        return this.createUnknownError(message, context);
    }
  }

  static fromException(error: Error, context?: Record<string, any>): JaspelError {
    const baseContext = {
      errorName: error.name,
      ...context
    };

    if (error.name === 'TypeError' && error.message.includes('fetch')) {
      return this.createNetworkError(error.message, baseContext);
    }

    if (error.name === 'AbortError') {
      return this.createNetworkError('Request was cancelled', baseContext);
    }

    return this.createUnknownError(error.message, {
      ...baseContext,
      stack: error.stack
    });
  }
}

// Error manager class
export class JaspelErrorManager {
  private errors: Map<string, JaspelError> = new Map();
  private listeners: Set<(errorState: ErrorState) => void> = new Set();
  private maxErrors: number = 10;

  constructor(maxErrors: number = 10) {
    this.maxErrors = maxErrors;
  }

  addError(error: JaspelError): void {
    this.errors.set(error.id, error);

    // Remove oldest errors if we exceed max
    if (this.errors.size > this.maxErrors) {
      const oldestKey = this.errors.keys().next().value;
      this.errors.delete(oldestKey);
    }

    this.notifyListeners();
  }

  removeError(errorId: string): void {
    this.errors.delete(errorId);
    this.notifyListeners();
  }

  clearErrors(): void {
    this.errors.clear();
    this.notifyListeners();
  }

  getError(errorId: string): JaspelError | undefined {
    return this.errors.get(errorId);
  }

  getAllErrors(): JaspelError[] {
    return Array.from(this.errors.values());
  }

  getErrorsByCategory(category: ErrorCategory): JaspelError[] {
    return this.getAllErrors().filter(error => error.category === category);
  }

  getErrorsBySeverity(severity: ErrorSeverity): JaspelError[] {
    return this.getAllErrors().filter(error => error.severity === severity);
  }

  getRetryableErrors(): JaspelError[] {
    return this.getAllErrors().filter(error => 
      error.retryable && error.retryCount < error.maxRetries
    );
  }

  incrementRetryCount(errorId: string): void {
    const error = this.errors.get(errorId);
    if (error) {
      error.retryCount++;
      this.notifyListeners();
    }
  }

  getErrorState(): ErrorState {
    const errors = this.getAllErrors();
    const lastError = errors.length > 0 ? errors[errors.length - 1] : undefined;
    
    return {
      hasError: errors.length > 0,
      errors,
      lastError,
      errorCount: errors.length,
      recoverySuggestions: this.getRecoverySuggestions(lastError)
    };
  }

  private getRecoverySuggestions(error?: JaspelError): string[] {
    if (!error) return [];

    const suggestions: string[] = [];

    switch (error.category) {
      case 'network':
        suggestions.push('Periksa koneksi internet Anda');
        suggestions.push('Coba refresh halaman');
        suggestions.push('Periksa pengaturan firewall atau proxy');
        break;
      case 'authentication':
        suggestions.push('Login kembali ke sistem');
        suggestions.push('Periksa kredensial login Anda');
        suggestions.push('Hubungi administrator jika masalah berlanjut');
        break;
      case 'permission':
        suggestions.push('Hubungi administrator untuk akses yang diperlukan');
        suggestions.push('Periksa role dan permission akun Anda');
        break;
      case 'validation':
        suggestions.push('Periksa kembali data yang diinput');
        suggestions.push('Pastikan format data sudah benar');
        break;
      case 'system':
        suggestions.push('Coba lagi dalam beberapa saat');
        suggestions.push('Hubungi tim teknis jika masalah berlanjut');
        break;
      default:
        suggestions.push('Refresh halaman dan coba lagi');
        suggestions.push('Hubungi tim teknis jika masalah berlanjut');
    }

    if (error.retryable && error.retryCount < error.maxRetries) {
      suggestions.unshift('Klik tombol "Coba Lagi"');
    }

    return suggestions;
  }

  subscribe(listener: (errorState: ErrorState) => void): () => void {
    this.listeners.add(listener);
    
    return () => {
      this.listeners.delete(listener);
    };
  }

  private notifyListeners(): void {
    const errorState = this.getErrorState();
    this.listeners.forEach(listener => listener(errorState));
  }
}

// Loading state manager
export class JaspelLoadingManager {
  private loadingStates: Map<string, LoadingState> = new Map();
  private listeners: Set<(loadingStates: Map<string, LoadingState>) => void> = new Set();

  setLoading(
    key: string, 
    isLoading: boolean, 
    options: {
      message?: string;
      progress?: number;
      stage?: string;
      estimatedDuration?: number;
    } = {}
  ): void {
    if (isLoading) {
      const loadingState: LoadingState = {
        isLoading: true,
        loadingMessage: options.message,
        progress: options.progress,
        stage: options.stage,
        startTime: Date.now(),
        estimatedDuration: options.estimatedDuration
      };
      
      this.loadingStates.set(key, loadingState);
    } else {
      this.loadingStates.delete(key);
    }

    this.notifyListeners();
  }

  updateProgress(key: string, progress: number, stage?: string): void {
    const state = this.loadingStates.get(key);
    if (state) {
      state.progress = progress;
      if (stage) state.stage = stage;
      this.notifyListeners();
    }
  }

  isLoading(key?: string): boolean {
    if (key) {
      return this.loadingStates.has(key);
    }
    return this.loadingStates.size > 0;
  }

  getLoadingState(key: string): LoadingState | undefined {
    return this.loadingStates.get(key);
  }

  getAllLoadingStates(): Map<string, LoadingState> {
    return new Map(this.loadingStates);
  }

  clearLoading(key?: string): void {
    if (key) {
      this.loadingStates.delete(key);
    } else {
      this.loadingStates.clear();
    }
    this.notifyListeners();
  }

  subscribe(listener: (loadingStates: Map<string, LoadingState>) => void): () => void {
    this.listeners.add(listener);
    
    return () => {
      this.listeners.delete(listener);
    };
  }

  private notifyListeners(): void {
    this.listeners.forEach(listener => listener(this.getAllLoadingStates()));
  }
}

// Combined error and loading manager
export class JaspelStateManager {
  private static instances: Map<JaspelVariant, JaspelStateManager> = new Map();

  public readonly errorManager: JaspelErrorManager;
  public readonly loadingManager: JaspelLoadingManager;

  private constructor(variant: JaspelVariant) {
    this.errorManager = new JaspelErrorManager();
    this.loadingManager = new JaspelLoadingManager();
  }

  static getInstance(variant: JaspelVariant): JaspelStateManager {
    if (!this.instances.has(variant)) {
      this.instances.set(variant, new JaspelStateManager(variant));
    }
    return this.instances.get(variant)!;
  }

  // Convenience methods
  handleError(error: any, context?: Record<string, any>): JaspelError {
    let jaspelError: JaspelError;

    if (error instanceof Error) {
      jaspelError = JaspelErrorFactory.fromException(error, context);
    } else if (typeof error === 'object' && error.status) {
      jaspelError = JaspelErrorFactory.fromHttpStatus(
        error.status,
        error.message || 'HTTP Error',
        context?.endpoint
      );
    } else {
      jaspelError = JaspelErrorFactory.createUnknownError(
        String(error),
        context
      );
    }

    this.errorManager.addError(jaspelError);
    return jaspelError;
  }

  setLoading(key: string, isLoading: boolean, options?: {
    message?: string;
    progress?: number;
    stage?: string;
    estimatedDuration?: number;
  }): void {
    this.loadingManager.setLoading(key, isLoading, options);
  }

  clearError(errorId: string): void {
    this.errorManager.removeError(errorId);
  }

  clearAllErrors(): void {
    this.errorManager.clearErrors();
  }

  clearAllLoading(): void {
    this.loadingManager.clearLoading();
  }

  getState(): {
    errors: ErrorState;
    loading: Map<string, LoadingState>;
  } {
    return {
      errors: this.errorManager.getErrorState(),
      loading: this.loadingManager.getAllLoadingStates()
    };
  }

  // Recovery methods
  retryError(errorId: string, retryFunction: () => Promise<void>): Promise<void> {
    const error = this.errorManager.getError(errorId);
    
    if (!error || !error.retryable || error.retryCount >= error.maxRetries) {
      return Promise.reject(new Error('Error is not retryable'));
    }

    this.errorManager.incrementRetryCount(errorId);
    
    return retryFunction().catch(newError => {
      // If retry fails, add new error
      this.handleError(newError, { originalErrorId: errorId });
      throw newError;
    });
  }

  // Cleanup
  destroy(): void {
    this.errorManager.clearErrors();
    this.loadingManager.clearLoading();
  }
}

// Export convenience functions
export const getJaspelStateManager = (variant: JaspelVariant): JaspelStateManager => {
  return JaspelStateManager.getInstance(variant);
};

export const handleJaspelError = (
  variant: JaspelVariant,
  error: any,
  context?: Record<string, any>
): JaspelError => {
  const manager = getJaspelStateManager(variant);
  return manager.handleError(error, context);
};

export const setJaspelLoading = (
  variant: JaspelVariant,
  key: string,
  isLoading: boolean,
  options?: {
    message?: string;
    progress?: number;
    stage?: string;
    estimatedDuration?: number;
  }
): void => {
  const manager = getJaspelStateManager(variant);
  manager.setLoading(key, isLoading, options);
};