import getUnifiedAuth from './UnifiedAuth';

export interface UserData {
  id: number;
  name: string;
  email: string;
  role: string;
  pegawai?: {
    nama_lengkap: string;
    jabatan?: string;
  };
}

export interface DoctorDashboardData {
  user: UserData;
  attendance_today: {
    status: string;
    check_in_time?: string;
    check_out_time?: string;
  };
  jaspel_summary: {
    current_month: number;
    last_month: number;
  };
  patient_count: {
    today: number;
    this_week: number;
    this_month: number;
  };
  procedures_count: {
    today: number;
    this_week: number;
    this_month: number;
  };
  performance: {
    attendance_rate: number;
    patient_satisfaction?: number;
  };
}

export interface LeaderboardDoctor {
  id: number;
  name: string;
  role: string;
  attendance_rate: number;
  level: number;
  xp: number;
  total_days: number;
  total_hours: number;
  avatar?: string;
  department: string;
  streak_days: number;
  rank: number;
  badge: string;
}

export interface LeaderboardData {
  leaderboard: LeaderboardDoctor[];
  month: string;
  working_days: number;
  last_updated: string;
}

export interface GPSDebugData {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: string;
  distance_to_work?: number;
  is_in_radius?: boolean;
  work_location?: {
    latitude: number;
    longitude: number;
    name: string;
    radius_meters: number;
  };
  browser_info?: {
    user_agent: string;
    timezone: string;
    language: string;
  };
  vpn_indicators?: {
    timezone_mismatch: boolean;
    coordinates_outside_indonesia: boolean;
    distance_from_expected: number;
    confidence_score: number;
  };
}

class DoctorApi {
  /**
   * Get current user info
   */
  async getCurrentUser(): Promise<UserData> {
    try {
      const response = await getUnifiedAuth().makeJsonRequest<{
        success: boolean;
        data: UserData;
      }>('/api/v2/auth/me');
      
      if (!response.success) {
        throw new Error('Failed to fetch user data');
      }
      
      return response.data;
    } catch (error) {
      console.error('Error fetching current user:', error);
      throw error;
    }
  }

  /**
   * Get doctor dashboard data
   */
  async getDashboard(): Promise<DoctorDashboardData> {
    const maxRetries = 3;
    let lastError: Error | null = null;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        console.log(`DoctorApi: Attempting to fetch dashboard data (attempt ${attempt}/${maxRetries})`);
        
        const response = await getUnifiedAuth().makeJsonRequest<{
          success: boolean;
          data: DoctorDashboardData;
        }>('/api/v2/dashboards/dokter');
        
        if (!response.success) {
          throw new Error('Failed to fetch dashboard data');
        }
        
        console.log('DoctorApi: Dashboard data fetched successfully');
        return response.data;
      } catch (error) {
        lastError = error as Error;
        console.warn(`DoctorApi: Attempt ${attempt} failed:`, error);
        
        if (attempt < maxRetries) {
          // Wait before retrying (exponential backoff)
          const delay = Math.min(1000 * Math.pow(2, attempt - 1), 5000);
          console.log(`DoctorApi: Retrying in ${delay}ms...`);
          await new Promise(resolve => setTimeout(resolve, delay));
        }
      }
    }

    // All retries failed
    console.error('DoctorApi: All attempts to fetch dashboard data failed:', lastError);
    throw new Error(`Failed to fetch dashboard data after ${maxRetries} attempts: ${lastError?.message || 'Unknown error'}`);
  }

  /**
   * Get doctor's Jaspel data
   */
  async getJaspel(): Promise<any> {
    const response = await getUnifiedAuth().makeJsonRequest('/api/v2/dashboards/dokter/jaspel');
    return response.data;
  }

  /**
   * Get doctor's attendance status
   */
  async getAttendanceStatus(): Promise<any> {
    const response = await getUnifiedAuth().makeJsonRequest('/api/v2/dashboards/dokter/attendance/status');
    return response.data;
  }

  /**
   * Get doctor's schedule (jadwal jaga)
   */
  async getSchedule(): Promise<any> {
    const response = await getUnifiedAuth().makeJsonRequest('/api/v2/dashboards/dokter/jadwal-jaga');
    return response.data;
  }

  /**
   * Get doctor's current active schedule with retry mechanism
   */
  async getCurrentSchedule(): Promise<any> {
    const maxRetries = 2;
    let lastError: any = null;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        console.log(`DoctorApi: Attempting to fetch current schedule (attempt ${attempt}/${maxRetries})`);
        
        const response = await getUnifiedAuth().makeJsonRequest('/api/v2/jadwal-jaga/current');
        
        // Success - return the data
        console.log('DoctorApi: Current schedule fetched successfully');
        return response.data || response;
        
      } catch (error) {
        lastError = error;
        console.warn(`DoctorApi: Attempt ${attempt} failed:`, error);
        
        // Handle 404 error gracefully - this is expected when no schedule exists
        if (error && typeof error === 'object' && 'message' in error) {
          const errorMsg = (error as any).message;
          
          if (errorMsg.includes('404') || errorMsg.includes('No active schedule found')) {
            console.log('No active schedule found for today - this is normal');
            return {
              message: 'No active schedule found for today',
              hasSchedule: false,
              schedule: null,
              isNormalCase: true
            };
          }
          
          // Handle authentication errors immediately
          if (errorMsg.includes('401') || errorMsg.includes('Unauthorized')) {
            throw new Error('Authentication required. Please login again.');
          }
        }
        
        // Retry logic for other errors
        if (attempt < maxRetries) {
          const delay = Math.min(1000 * Math.pow(2, attempt - 1), 3000);
          console.log(`DoctorApi: Retrying in ${delay}ms...`);
          await new Promise(resolve => setTimeout(resolve, delay));
        }
      }
    }

    // All retries failed
    console.error('DoctorApi: All attempts to fetch current schedule failed:', lastError);
    
    // Return a graceful fallback instead of throwing
    return {
      message: 'Unable to fetch schedule at this time',
      hasSchedule: false,
      schedule: null,
      isError: true,
      error: lastError?.message || 'Unknown error'
    };
  }

  /**
   * Get doctor's today schedule
   */
  async getTodaySchedule(date?: string): Promise<any> {
    const params = date ? `?date=${date}` : '';
    const response = await getUnifiedAuth().makeJsonRequest(`/api/v2/jadwal-jaga/today${params}`);
    return response.data;
  }

  /**
   * Get doctor's weekly schedule
   */
  async getWeeklySchedule(weekStart?: string): Promise<any> {
    const params = weekStart ? `?week_start=${weekStart}` : '';
    const response = await getUnifiedAuth().makeJsonRequest(`/api/v2/jadwal-jaga/week${params}`);
    return response.data;
  }

  /**
   * Validate check-in for current location with enhanced error handling
   */
  async validateCheckin(latitude: number, longitude: number, accuracy?: number, date?: string): Promise<any> {
    try {
      const response = await getUnifiedAuth().makeJsonRequest('/api/v2/jadwal-jaga/validate-checkin', {
        method: 'POST',
        body: JSON.stringify({
          latitude,
          longitude,
          accuracy: accuracy || 50,
          date: date || new Date().toISOString().split('T')[0]
        })
      });
      return response.data || response; // Handle different response structures
    } catch (error) {
      console.error('Error validating check-in:', error);
      
      // Enhance error messages for better user experience
      if (error.message) {
        // Handle shift compatibility errors
        if (error.message.includes('SHIFT_NOT_ALLOWED') || error.message.includes('shift') && error.message.includes('allowed')) {
          throw new Error('❌ Shift Anda tidak sesuai dengan lokasi kerja yang dikonfigurasi. Silakan hubungi administrator untuk penyesuaian jadwal.');
        } else if (error.message.includes('work_location')) {
          throw new Error('📍 Lokasi kerja belum dikonfigurasi. Silakan hubungi administrator.');
        } else if (error.message.includes('radius') || error.message.includes('distance')) {
          throw new Error('📏 Anda berada di luar radius lokasi kerja yang diizinkan.');
        } else if (error.message.includes('schedule')) {
          throw new Error('📅 Jadwal kerja tidak ditemukan untuk hari ini.');
        } else if (error.message.includes('401') || error.message.includes('Unauthorized')) {
          throw new Error('🔐 Sesi telah berakhir. Silakan login kembali.');
        } else if (error.message.includes('time') || error.message.includes('window')) {
          throw new Error('⏰ Check-in hanya dapat dilakukan pada jam kerja yang telah ditentukan.');
        }
      }
      
      throw error;
    }
  }

  /**
   * Get top 3 doctors leaderboard by attendance rate
   */
  async getLeaderboard(month?: number, year?: number): Promise<LeaderboardData> {
    try {
      let url = '/api/v2/dashboards/dokter/leaderboard';
      
      // Add month/year parameters if provided
      if (month && year) {
        url += `?month=${month}&year=${year}`;
      }
      
      const response = await getUnifiedAuth().makeJsonRequest(url);
      return response.data;
    } catch (error) {
      console.error('Error fetching leaderboard:', error);
      throw error;
    }
  }

  /**
   * Submit GPS debugging data for analysis
   */
  async submitGPSDebugData(debugData: GPSDebugData): Promise<any> {
    try {
      const response = await getUnifiedAuth().makeJsonRequest('/api/v2/gps/debug', {
        method: 'POST',
        body: JSON.stringify(debugData)
      });
      return response.data || response;
    } catch (error) {
      console.error('Error submitting GPS debug data:', error);
      throw error;
    }
  }

  /**
   * Get GPS debugging history for current user
   */
  async getGPSDebugHistory(limit: number = 50): Promise<any> {
    try {
      const response = await getUnifiedAuth().makeJsonRequest(`/api/v2/gps/debug/history?limit=${limit}`);
      return response.data || response;
    } catch (error) {
      console.error('Error fetching GPS debug history:', error);
      throw error;
    }
  }

  /**
   * Validate check-in with enhanced GPS debugging
   */
  async validateCheckinWithDebug(debugData: GPSDebugData): Promise<any> {
    try {
      const response = await getUnifiedAuth().makeJsonRequest('/api/v2/jadwal-jaga/validate-checkin-debug', {
        method: 'POST',
        body: JSON.stringify(debugData)
      });
      return response.data || response;
    } catch (error) {
      console.error('Error validating check-in with debug:', error);
      
      // Enhanced error messages for GPS debugging
      if (error.message) {
        if (error.message.includes('distance')) {
          throw new Error(`Distance validation failed: ${error.message}. Current coordinates: ${debugData.latitude}, ${debugData.longitude}`);
        } else if (error.message.includes('vpn') || error.message.includes('proxy')) {
          throw new Error('VPN/Proxy detected. Please disable VPN and try again with your actual location.');
        } else if (error.message.includes('accuracy')) {
          throw new Error(`GPS accuracy too low (±${debugData.accuracy}m). Please move to an area with better GPS signal.`);
        } else if (error.message.includes('work_location')) {
          throw new Error('Work location not configured. Please contact administrator.');
        }
      }
      
      throw error;
    }
  }

  /**
   * Get work location details for GPS validation
   */
  async getWorkLocationForGPS(): Promise<any> {
    try {
      const response = await getUnifiedAuth().makeJsonRequest('/api/v2/work-location/current');
      return response.data || response;
    } catch (error) {
      console.error('Error fetching work location for GPS:', error);
      throw error;
    }
  }
}

export default new DoctorApi();