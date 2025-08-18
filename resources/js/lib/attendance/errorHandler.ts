/**
 * Unified Error Handling for Attendance Systems
 * Standardized error handling patterns and recovery strategies
 */

import { AttendanceError, AttendanceVariant } from './types';

// Error severity levels
export enum ErrorSeverity {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  CRITICAL = 'critical'
}

// Error categories with user-friendly messages
export const ERROR_MESSAGES: Record<string, { message: string; severity: ErrorSeverity; recovery: string[] }> = {
  // Network errors
  'network.connection': {
    message: 'Koneksi internet bermasalah',
    severity: ErrorSeverity.HIGH,
    recovery: ['Periksa koneksi internet', 'Coba lagi dalam beberapa saat', 'Hubungi admin jika masalah berlanjut']
  },
  'network.timeout': {
    message: 'Koneksi timeout. Server tidak merespons',
    severity: ErrorSeverity.MEDIUM,
    recovery: ['Coba lagi', 'Periksa koneksi internet', 'Tunggu beberapa saat']
  },
  'network.server_error': {
    message: 'Server mengalami masalah',
    severity: ErrorSeverity.HIGH,
    recovery: ['Coba lagi dalam beberapa menit', 'Hubungi admin teknis']
  },

  // GPS/Location errors
  'gps.permission_denied': {
    message: 'Izin lokasi diperlukan untuk presensi',
    severity: ErrorSeverity.CRITICAL,
    recovery: ['Aktifkan izin lokasi di browser', 'Refresh halaman', 'Gunakan HTTPS']
  },
  'gps.unavailable': {
    message: 'GPS tidak tersedia di perangkat ini',
    severity: ErrorSeverity.HIGH,
    recovery: ['Gunakan device dengan GPS', 'Coba dengan browser lain', 'Input koordinat manual']
  },
  'gps.timeout': {
    message: 'Gagal mendapatkan lokasi (timeout)',
    severity: ErrorSeverity.MEDIUM,
    recovery: ['Coba lagi', 'Pindah ke area terbuka', 'Periksa sinyal GPS']
  },
  'gps.accuracy_low': {
    message: 'Akurasi GPS rendah',
    severity: ErrorSeverity.MEDIUM,
    recovery: ['Pindah ke area terbuka', 'Tunggu beberapa saat', 'Coba lagi']
  },

  // Validation errors
  'validation.location_invalid': {
    message: 'Anda berada di luar area kerja',
    severity: ErrorSeverity.HIGH,
    recovery: ['Pastikan Anda di lokasi kerja', 'Periksa koordinat lokasi', 'Hubungi admin']
  },
  'validation.time_invalid': {
    message: 'Waktu tidak valid untuk presensi',
    severity: ErrorSeverity.MEDIUM,
    recovery: ['Periksa jam sistem', 'Coba lagi', 'Hubungi admin']
  },
  'validation.already_checked_in': {
    message: 'Anda sudah melakukan check-in hari ini',
    severity: ErrorSeverity.LOW,
    recovery: ['Refresh halaman', 'Lakukan check-out terlebih dahulu']
  },
  'validation.not_checked_in': {
    message: 'Belum melakukan check-in hari ini',
    severity: ErrorSeverity.MEDIUM,
    recovery: ['Lakukan check-in terlebih dahulu']
  },

  // Permission errors
  'permission.unauthorized': {
    message: 'Anda tidak memiliki izin untuk aksi ini',
    severity: ErrorSeverity.HIGH,
    recovery: ['Login ulang', 'Hubungi admin', 'Periksa role akun']
  },
  'permission.session_expired': {
    message: 'Sesi telah berakhir',
    severity: ErrorSeverity.MEDIUM,
    recovery: ['Login ulang', 'Refresh halaman']
  },

  // System errors
  'system.unknown': {
    message: 'Terjadi kesalahan sistem',
    severity: ErrorSeverity.HIGH,
    recovery: ['Coba lagi', 'Refresh halaman', 'Hubungi admin']
  },
  'system.maintenance': {
    message: 'Sistem sedang dalam pemeliharaan',
    severity: ErrorSeverity.HIGH,
    recovery: ['Coba lagi nanti', 'Hubungi admin untuk info pemeliharaan']
  }
};

/**
 * Error handler class for attendance systems
 */
export class AttendanceErrorHandler {
  private variant: AttendanceVariant;
  private errorLog: AttendanceError[] = [];
  private maxLogSize: number = 50;

  constructor(variant: AttendanceVariant) {
    this.variant = variant;
  }

  /**
   * Handle and categorize errors
   */
  handleError(error: any, context?: string): AttendanceError {
    const processedError = this.processError(error, context);
    this.logError(processedError);
    return processedError;
  }

  /**
   * Process raw error into structured format
   */
  private processError(error: any, context?: string): AttendanceError {
    // Network errors
    if (error.name === 'TypeError' && error.message.includes('fetch')) {
      return this.createError('network', 'network.connection', error);
    }

    // HTTP errors
    if (error.message?.includes('HTTP')) {
      const statusCode = error.message.match(/HTTP (\d+)/)?.[1];
      
      switch (statusCode) {
        case '401':
        case '403':
          return this.createError('permission', 'permission.unauthorized', error);
        case '404':
          return this.createError('validation', 'validation.endpoint_not_found', error);
        case '422':
          return this.createError('validation', 'validation.data_invalid', error);
        case '429':
          return this.createError('network', 'network.rate_limit', error);
        case '500':
        case '502':
        case '503':
          return this.createError('system', 'network.server_error', error);
        default:
          return this.createError('system', 'system.unknown', error);
      }
    }

    // GPS errors
    if (error.code) {
      switch (error.code) {
        case 1: // PERMISSION_DENIED
          return this.createError('gps', 'gps.permission_denied', error);
        case 2: // POSITION_UNAVAILABLE
          return this.createError('gps', 'gps.unavailable', error);
        case 3: // TIMEOUT
          return this.createError('gps', 'gps.timeout', error);
        default:
          return this.createError('gps', 'gps.unknown', error);
      }
    }

    // Location validation errors
    if (error.message?.includes('lokasi') || error.message?.includes('location')) {
      return this.createError('validation', 'validation.location_invalid', error);
    }

    // Generic error
    return this.createError('system', 'system.unknown', error, context);
  }

  /**
   * Create structured error object
   */
  private createError(
    type: AttendanceError['type'],
    category: string,
    originalError: any,
    context?: string
  ): AttendanceError {
    const errorInfo = ERROR_MESSAGES[category] || ERROR_MESSAGES['system.unknown'];
    
    return {
      type,
      message: context ? `${errorInfo.message} (${context})` : errorInfo.message,
      code: category,
      details: {
        originalError,
        severity: errorInfo.severity,
        recovery: errorInfo.recovery,
        timestamp: new Date().toISOString(),
        variant: this.variant,
        context
      }
    };
  }

  /**
   * Log error for debugging
   */
  private logError(error: AttendanceError): void {
    this.errorLog.push(error);
    
    // Keep log size manageable
    if (this.errorLog.length > this.maxLogSize) {
      this.errorLog = this.errorLog.slice(-this.maxLogSize);
    }

    // Console logging for development
    if (process.env.NODE_ENV === 'development') {
      console.group(`🚨 Attendance Error [${this.variant}]`);
      console.error('Message:', error.message);
      console.error('Type:', error.type);
      console.error('Code:', error.code);
      console.error('Details:', error.details);
      console.groupEnd();
    }
  }

  /**
   * Get error history
   */
  getErrorHistory(): AttendanceError[] {
    return [...this.errorLog];
  }

  /**
   * Clear error history
   */
  clearErrorHistory(): void {
    this.errorLog = [];
  }

  /**
   * Get recovery suggestions for error
   */
  getRecoverySuggestions(error: AttendanceError): string[] {
    return error.details?.recovery || ERROR_MESSAGES['system.unknown'].recovery;
  }

  /**
   * Check if error is recoverable
   */
  isRecoverable(error: AttendanceError): boolean {
    const criticalErrors = ['gps.permission_denied', 'permission.unauthorized'];
    return !criticalErrors.includes(error.code || '');
  }

  /**
   * Get error severity
   */
  getErrorSeverity(error: AttendanceError): ErrorSeverity {
    return error.details?.severity || ErrorSeverity.MEDIUM;
  }
}

/**
 * Global error handler instances
 */
export const dokterErrorHandler = new AttendanceErrorHandler('dokter');
export const paramedisErrorHandler = new AttendanceErrorHandler('paramedis');

/**
 * Get error handler by variant
 */
export const getErrorHandler = (variant: AttendanceVariant): AttendanceErrorHandler => {
  return variant === 'dokter' ? dokterErrorHandler : paramedisErrorHandler;
};

/**
 * Quick error handling utility
 */
export const handleAttendanceError = (
  error: any,
  variant: AttendanceVariant,
  context?: string
): AttendanceError => {
  return getErrorHandler(variant).handleError(error, context);
};

/**
 * Error display utility
 */
export const formatErrorForDisplay = (error: AttendanceError): string => {
  const recovery = error.details?.recovery?.[0];
  return recovery ? `${error.message}. ${recovery}` : error.message;
};

/**
 * Check if error requires immediate attention
 */
export const requiresImmediateAttention = (error: AttendanceError): boolean => {
  const severity = error.details?.severity || ErrorSeverity.MEDIUM;
  return [ErrorSeverity.HIGH, ErrorSeverity.CRITICAL].includes(severity);
};