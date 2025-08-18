/**
 * API Type Definitions
 * Standardized API response types and error handling interfaces
 */

// Generic API response wrapper
export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
  meta?: ApiMetadata;
}

// API metadata for pagination and additional info
export interface ApiMetadata {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  from?: number;
  to?: number;
  path?: string;
  timestamp?: string;
  version?: string;
}

// Standard API error response
export interface ApiError {
  message: string;
  code?: string | number;
  statusCode?: number;
  errors?: Record<string, string[]>;
  stack?: string;
  timestamp?: string;
}

// Dashboard API response
export interface DashboardApiResponse {
  success: boolean;
  data: {
    metrics: {
      jaspel: {
        current_month: number;
        previous_month: number;
        growth_percentage: number;
        progress_percentage: number;
      };
      attendance: {
        rate: number;
        days_present: number;
        total_days: number;
        display_text: string;
      };
      patients: {
        today: number;
        this_month: number;
      };
    };
    doctor_level?: number;
    experience_points?: number;
    daily_streak?: number;
    last_updated?: string;
  };
  message?: string;
}

// Leaderboard API response
export interface LeaderboardApiResponse {
  success: boolean;
  data: {
    leaderboard: Array<{
      id: number;
      rank: number;
      name: string;
      level: number;
      xp: number;
      attendance_rate: number;
      streak_days: number;
      total_hours: number;
      total_days: number;
      total_patients: number;
      consultation_hours: number;
      procedures_count: number;
      badge: string;
      month: number;
      year: number;
      month_label: string;
    }>;
    current_user_rank?: number;
    total_participants?: number;
  };
  message?: string;
}

// Attendance API response
export interface AttendanceApiResponse {
  success: boolean;
  data: {
    attendance_history: Array<{
      date: string;
      check_in: string;
      check_out: string;
      status: string;
      hours: string;
      location?: string;
      notes?: string;
    }>;
    summary: {
      total_days: number;
      present_days: number;
      absent_days: number;
      late_days: number;
      overtime_hours: number;
    };
  };
  message?: string;
}

// Jadwal Jaga (Schedule) API response
export interface JadwalJagaApiResponse {
  success: boolean;
  data: {
    calendar_events: Array<{
      id: number;
      title: string;
      start: string;
      end: string;
      description?: string;
      shift_info?: {
        id: number;
        nama_shift: string;
        jam_masuk: string;
        jam_pulang: string;
        unit_kerja: string;
        peran: string;
        employee_name: string;
        status: string;
      };
    }>;
    weekly_schedule: Array<{
      id: number;
      tanggal_jaga: string;
      shift_template: {
        id: number;
        nama_shift: string;
        jam_masuk: string;
        jam_pulang: string;
      };
      unit_kerja: string;
      status_jaga: string;
      keterangan?: string;
      peran: string;
      employee_name: string;
    }>;
    schedule_stats?: {
      total_shifts: number;
      completed: number;
      upcoming: number;
      total_hours: number;
    };
    attendance_records?: Array<{
      jadwal_jaga_id: number;
      time_in?: string;
      time_out?: string;
      check_in_time?: string;
      check_out_time?: string;
      status?: string;
    }>;
  };
  message?: string;
}

// JASPEL API response
export interface JaspelApiResponse {
  success: boolean;
  data: {
    jaspel_summary: {
      current_month: number;
      previous_month: number;
      year_to_date: number;
      average_monthly: number;
    };
    jaspel_details: Array<{
      id: number;
      period: string;
      amount: number;
      status: 'paid' | 'pending' | 'processing';
      payment_date?: string;
      breakdown?: {
        base: number;
        incentive: number;
        deduction: number;
      };
    }>;
    projections?: {
      next_month: number;
      next_quarter: number;
    };
  };
  message?: string;
}

// User/Profile API response
export interface UserApiResponse {
  success: boolean;
  data: {
    user: {
      id: number;
      name: string;
      email: string;
      role: string;
      nip?: string;
      department?: string;
      position?: string;
      phone?: string;
      avatar?: string;
      initials?: string;
    };
    permissions?: string[];
    settings?: Record<string, any>;
  };
  message?: string;
}

// API request configurations
export interface ApiRequestConfig {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
  headers?: Record<string, string>;
  body?: any;
  params?: Record<string, any>;
  timeout?: number;
  retries?: number;
  cache?: boolean;
  credentials?: RequestCredentials;
}

// API endpoint definitions
export const API_ENDPOINTS = {
  // Dashboard endpoints
  DASHBOARD: '/api/v2/dashboards/dokter',
  DASHBOARD_METRICS: '/api/v2/dashboards/dokter/metrics',
  DASHBOARD_SUMMARY: '/api/v2/dashboards/dokter/summary',
  
  // Leaderboard endpoints
  LEADERBOARD: '/api/v2/dashboards/dokter/leaderboard',
  LEADERBOARD_MONTHLY: '/api/v2/dashboards/dokter/leaderboard/monthly',
  
  // Attendance endpoints
  ATTENDANCE: '/api/v2/dashboards/dokter/attendance',
  ATTENDANCE_HISTORY: '/api/v2/dashboards/dokter/attendance/history',
  ATTENDANCE_CHECK_IN: '/api/v2/dashboards/dokter/attendance/checkin',
  ATTENDANCE_CHECK_OUT: '/api/v2/dashboards/dokter/attendance/checkout',
  
  // Schedule endpoints
  JADWAL_JAGA: '/api/v2/dashboards/dokter/jadwal-jaga',
  JADWAL_JAGA_CALENDAR: '/api/v2/dashboards/dokter/jadwal-jaga/calendar',
  
  // JASPEL endpoints
  JASPEL: '/api/v2/dashboards/dokter/jaspel',
  JASPEL_SUMMARY: '/api/v2/dashboards/dokter/jaspel/summary',
  JASPEL_DETAILS: '/api/v2/dashboards/dokter/jaspel/details',
  
  // User/Profile endpoints
  USER_PROFILE: '/api/v2/user/profile',
  USER_SETTINGS: '/api/v2/user/settings',
} as const;

// API error codes
export enum ApiErrorCode {
  // Client errors
  BAD_REQUEST = 'BAD_REQUEST',
  UNAUTHORIZED = 'UNAUTHORIZED',
  FORBIDDEN = 'FORBIDDEN',
  NOT_FOUND = 'NOT_FOUND',
  VALIDATION_ERROR = 'VALIDATION_ERROR',
  RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED',
  
  // Server errors
  INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR',
  SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE',
  GATEWAY_TIMEOUT = 'GATEWAY_TIMEOUT',
  
  // Network errors
  NETWORK_ERROR = 'NETWORK_ERROR',
  TIMEOUT = 'TIMEOUT',
  OFFLINE = 'OFFLINE',
  
  // Application errors
  UNKNOWN_ERROR = 'UNKNOWN_ERROR',
  PARSE_ERROR = 'PARSE_ERROR',
  CACHE_ERROR = 'CACHE_ERROR',
}

// HTTP status codes
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE_ENTITY: 422,
  TOO_MANY_REQUESTS: 429,
  INTERNAL_SERVER_ERROR: 500,
  SERVICE_UNAVAILABLE: 503,
  GATEWAY_TIMEOUT: 504,
} as const;

// API error handler class
export class ApiErrorHandler {
  /**
   * Parse API error response
   */
  static parseError(error: any): ApiError {
    if (error.response) {
      // Server responded with error
      const { data, status } = error.response;
      return {
        message: data?.message || this.getDefaultMessage(status),
        code: this.getErrorCode(status),
        statusCode: status,
        errors: data?.errors,
        timestamp: new Date().toISOString(),
      };
    } else if (error.request) {
      // Request made but no response
      return {
        message: 'No response from server. Please check your connection.',
        code: ApiErrorCode.NETWORK_ERROR,
        timestamp: new Date().toISOString(),
      };
    } else {
      // Something else happened
      return {
        message: error.message || 'An unexpected error occurred',
        code: ApiErrorCode.UNKNOWN_ERROR,
        timestamp: new Date().toISOString(),
      };
    }
  }

  /**
   * Get error code from HTTP status
   */
  static getErrorCode(status: number): ApiErrorCode {
    switch (status) {
      case HTTP_STATUS.BAD_REQUEST:
        return ApiErrorCode.BAD_REQUEST;
      case HTTP_STATUS.UNAUTHORIZED:
        return ApiErrorCode.UNAUTHORIZED;
      case HTTP_STATUS.FORBIDDEN:
        return ApiErrorCode.FORBIDDEN;
      case HTTP_STATUS.NOT_FOUND:
        return ApiErrorCode.NOT_FOUND;
      case HTTP_STATUS.UNPROCESSABLE_ENTITY:
        return ApiErrorCode.VALIDATION_ERROR;
      case HTTP_STATUS.TOO_MANY_REQUESTS:
        return ApiErrorCode.RATE_LIMIT_EXCEEDED;
      case HTTP_STATUS.INTERNAL_SERVER_ERROR:
        return ApiErrorCode.INTERNAL_SERVER_ERROR;
      case HTTP_STATUS.SERVICE_UNAVAILABLE:
        return ApiErrorCode.SERVICE_UNAVAILABLE;
      case HTTP_STATUS.GATEWAY_TIMEOUT:
        return ApiErrorCode.GATEWAY_TIMEOUT;
      default:
        return ApiErrorCode.UNKNOWN_ERROR;
    }
  }

  /**
   * Get default error message for status code
   */
  static getDefaultMessage(status: number): string {
    switch (status) {
      case HTTP_STATUS.BAD_REQUEST:
        return 'Invalid request. Please check your input.';
      case HTTP_STATUS.UNAUTHORIZED:
        return 'You are not authorized. Please login again.';
      case HTTP_STATUS.FORBIDDEN:
        return 'You do not have permission to access this resource.';
      case HTTP_STATUS.NOT_FOUND:
        return 'The requested resource was not found.';
      case HTTP_STATUS.UNPROCESSABLE_ENTITY:
        return 'Validation failed. Please check your input.';
      case HTTP_STATUS.TOO_MANY_REQUESTS:
        return 'Too many requests. Please try again later.';
      case HTTP_STATUS.INTERNAL_SERVER_ERROR:
        return 'Internal server error. Please try again later.';
      case HTTP_STATUS.SERVICE_UNAVAILABLE:
        return 'Service is temporarily unavailable. Please try again later.';
      case HTTP_STATUS.GATEWAY_TIMEOUT:
        return 'Request timeout. Please try again.';
      default:
        return 'An unexpected error occurred. Please try again.';
    }
  }

  /**
   * Check if error is retryable
   */
  static isRetryable(error: ApiError): boolean {
    const retryableCodes = [
      ApiErrorCode.NETWORK_ERROR,
      ApiErrorCode.TIMEOUT,
      ApiErrorCode.SERVICE_UNAVAILABLE,
      ApiErrorCode.GATEWAY_TIMEOUT,
      ApiErrorCode.RATE_LIMIT_EXCEEDED,
    ];
    
    return retryableCodes.includes(error.code as ApiErrorCode);
  }
}

// Type guard functions
export const isApiResponse = <T>(obj: any): obj is ApiResponse<T> => {
  return obj && typeof obj === 'object' && 'success' in obj;
};

export const isApiError = (obj: any): obj is ApiError => {
  return obj && typeof obj === 'object' && 'message' in obj;
};