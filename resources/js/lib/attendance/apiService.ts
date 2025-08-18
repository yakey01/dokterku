/**
 * Unified Attendance API Service
 * Shared API functions for both dokter and paramedis attendance systems
 */

import { 
  AttendanceVariant, 
  AttendanceApiResponse, 
  LocationData, 
  AttendanceError,
  UserData,
  MultiShiftStatus,
  WorkLocation
} from './types';

// API base URLs for different variants
const API_BASES: Record<AttendanceVariant, string> = {
  dokter: '/api/v2/dashboards/dokter',
  paramedis: '/api/v2/dashboards/paramedis'
};

/**
 * Retry with exponential backoff utility
 */
export const retryWithBackoff = async <T>(
  fn: () => Promise<T>,
  maxRetries: number = 3,
  baseDelay: number = 1000
): Promise<T> => {
  let lastError: Error | null = null;
  
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      return await fn();
    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));
      
      // Don't retry on client errors (4xx)
      if (lastError.message.includes('HTTP 4')) {
        throw lastError;
      }
      
      if (attempt < maxRetries - 1) {
        const delay = baseDelay * Math.pow(2, attempt);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }
  
  throw lastError || new Error('Max retries exceeded');
};

/**
 * Get common headers for API requests
 */
const getHeaders = (): Record<string, string> => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
  };
};

/**
 * Handle API errors consistently
 */
const handleApiError = (error: any, context: string): AttendanceError => {
  if (error.name === 'TypeError' && error.message.includes('fetch')) {
    return {
      type: 'network',
      message: 'Koneksi bermasalah. Periksa internet Anda.',
      details: error
    };
  }
  
  if (error.message.includes('HTTP 4')) {
    return {
      type: 'validation',
      message: 'Request tidak valid. Silakan refresh halaman.',
      code: error.message.match(/HTTP (\d+)/)?.[1],
      details: error
    };
  }
  
  return {
    type: 'system',
    message: `Error pada ${context}: ${error.message}`,
    details: error
  };
};

/**
 * Base API client class
 */
export class AttendanceApiService {
  private variant: AttendanceVariant;
  private baseUrl: string;

  constructor(variant: AttendanceVariant) {
    this.variant = variant;
    this.baseUrl = API_BASES[variant];
  }

  /**
   * Make authenticated request
   */
  private async makeRequest<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    
    const response = await fetch(url, {
      ...options,
      headers: {
        ...getHeaders(),
        ...options.headers
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data.data || data;
  }

  /**
   * Fetch user dashboard data
   */
  async fetchUserData(): Promise<UserData> {
    try {
      return await retryWithBackoff(() => this.makeRequest<UserData>('/'));
    } catch (error) {
      throw handleApiError(error, 'mengambil data pengguna');
    }
  }

  /**
   * Fetch attendance status
   */
  async fetchAttendanceStatus(): Promise<AttendanceApiResponse> {
    try {
      return await retryWithBackoff(() => 
        this.makeRequest<AttendanceApiResponse>('/attendance/status')
      );
    } catch (error) {
      throw handleApiError(error, 'mengambil status presensi');
    }
  }

  /**
   * Fetch schedule data (dokter variant)
   */
  async fetchScheduleData(): Promise<any> {
    if (this.variant !== 'dokter') {
      throw new Error('Schedule data only available for dokter variant');
    }

    try {
      return await retryWithBackoff(() => 
        this.makeRequest('/jadwal-jaga')
      );
    } catch (error) {
      throw handleApiError(error, 'mengambil jadwal jaga');
    }
  }

  /**
   * Fetch work location status
   */
  async fetchWorkLocationStatus(): Promise<WorkLocation[] | null> {
    try {
      return await retryWithBackoff(() => 
        this.makeRequest<WorkLocation[]>('/work-location/status')
      );
    } catch (error) {
      if (error.message.includes('HTTP 404')) {
        return null; // No work location assigned
      }
      throw handleApiError(error, 'mengambil lokasi kerja');
    }
  }

  /**
   * Check-in with location
   */
  async checkin(location?: LocationData): Promise<AttendanceApiResponse> {
    try {
      const body: any = {};
      
      if (location) {
        body.latitude = location.lat;
        body.longitude = location.lng;
        body.accuracy = location.accuracy;
        body.location_name = location.address || `${location.lat}, ${location.lng}`;
      }

      return await retryWithBackoff(() =>
        this.makeRequest<AttendanceApiResponse>('/checkin', {
          method: 'POST',
          body: JSON.stringify(body)
        })
      );
    } catch (error) {
      throw handleApiError(error, 'check-in');
    }
  }

  /**
   * Check-out with location
   */
  async checkout(location?: LocationData): Promise<AttendanceApiResponse> {
    try {
      const body: any = {};
      
      if (location) {
        body.latitude = location.lat;
        body.longitude = location.lng;
        body.accuracy = location.accuracy;
        body.location_name = location.address || `${location.lat}, ${location.lng}`;
      }

      return await retryWithBackoff(() =>
        this.makeRequest<AttendanceApiResponse>('/checkout', {
          method: 'POST',
          body: JSON.stringify(body)
        })
      );
    } catch (error) {
      throw handleApiError(error, 'check-out');
    }
  }

  /**
   * Fetch multi-shift status (dokter variant)
   */
  async fetchMultiShiftStatus(): Promise<MultiShiftStatus> {
    if (this.variant !== 'dokter') {
      throw new Error('Multi-shift status only available for dokter variant');
    }

    try {
      return await retryWithBackoff(() =>
        this.makeRequest<MultiShiftStatus>('/attendance/multi-shift-status')
      );
    } catch (error) {
      throw handleApiError(error, 'mengambil status multi-shift');
    }
  }

  /**
   * Fetch today's attendance records
   */
  async fetchTodayRecords(): Promise<any[]> {
    try {
      return await retryWithBackoff(() =>
        this.makeRequest<any[]>('/attendance/today')
      );
    } catch (error) {
      throw handleApiError(error, 'mengambil record hari ini');
    }
  }

  /**
   * Fetch server time
   */
  async fetchServerTime(): Promise<string> {
    try {
      const response = await retryWithBackoff(() =>
        this.makeRequest<{ server_time: string }>('/server-time')
      );
      return response.server_time;
    } catch (error) {
      // Fallback to local time if server time fails
      console.warn('Failed to fetch server time, using local time:', error);
      return new Date().toISOString();
    }
  }

  /**
   * Test API connectivity
   */
  async testConnection(): Promise<boolean> {
    try {
      await this.makeRequest('/test');
      return true;
    } catch (error) {
      console.warn('API connection test failed:', error);
      return false;
    }
  }
}

/**
 * Factory function to create API service for specific variant
 */
export const createAttendanceApiService = (variant: AttendanceVariant): AttendanceApiService => {
  return new AttendanceApiService(variant);
};

/**
 * Global API service instances (can be reused)
 */
export const dokterApiService = new AttendanceApiService('dokter');
export const paramedisApiService = new AttendanceApiService('paramedis');

/**
 * Get API service by variant
 */
export const getApiService = (variant: AttendanceVariant): AttendanceApiService => {
  return variant === 'dokter' ? dokterApiService : paramedisApiService;
};