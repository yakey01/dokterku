import UnifiedAuth from './UnifiedAuth';

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

class DoctorApi {
  /**
   * Get current user info
   */
  async getCurrentUser(): Promise<UserData> {
    try {
      const response = await UnifiedAuth.makeJsonRequest<{
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
    try {
      const response = await UnifiedAuth.makeJsonRequest<{
        success: boolean;
        data: DoctorDashboardData;
      }>('/api/v2/dashboards/dokter');
      
      if (!response.success) {
        throw new Error('Failed to fetch dashboard data');
      }
      
      return response.data;
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
      throw error;
    }
  }

  /**
   * Get doctor's Jaspel data
   */
  async getJaspel(): Promise<any> {
    const response = await UnifiedAuth.makeJsonRequest('/api/v2/dashboards/dokter/jaspel');
    return response.data;
  }

  /**
   * Get doctor's attendance status
   */
  async getAttendanceStatus(): Promise<any> {
    const response = await UnifiedAuth.makeJsonRequest('/api/v2/dashboards/dokter/attendance/status');
    return response.data;
  }

  /**
   * Get doctor's schedule (jadwal jaga)
   */
  async getSchedule(): Promise<any> {
    const response = await UnifiedAuth.makeJsonRequest('/api/v2/dashboards/dokter/jadwal-jaga');
    return response.data;
  }

  /**
   * Get doctor's current active schedule
   */
  async getCurrentSchedule(): Promise<any> {
    try {
      const response = await UnifiedAuth.makeJsonRequest('/api/v2/jadwal-jaga/current');
      return response.data || response; // Handle different response structures
    } catch (error) {
      console.error('Error fetching current schedule:', error);
      // Return a more specific error message for schedule issues
      if (error.message.includes('404')) {
        throw new Error('No active schedule found for today');
      } else if (error.message.includes('401') || error.message.includes('Unauthorized')) {
        throw new Error('Authentication required. Please login again.');
      }
      throw error;
    }
  }

  /**
   * Get doctor's today schedule
   */
  async getTodaySchedule(date?: string): Promise<any> {
    const params = date ? `?date=${date}` : '';
    const response = await UnifiedAuth.makeJsonRequest(`/api/v2/jadwal-jaga/today${params}`);
    return response.data;
  }

  /**
   * Get doctor's weekly schedule
   */
  async getWeeklySchedule(weekStart?: string): Promise<any> {
    const params = weekStart ? `?week_start=${weekStart}` : '';
    const response = await UnifiedAuth.makeJsonRequest(`/api/v2/jadwal-jaga/week${params}`);
    return response.data;
  }

  /**
   * Validate check-in for current location
   */
  async validateCheckin(latitude: number, longitude: number, accuracy?: number, date?: string): Promise<any> {
    try {
      const response = await UnifiedAuth.makeJsonRequest('/api/v2/jadwal-jaga/validate-checkin', {
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
      throw error;
    }
  }
}

export default new DoctorApi();