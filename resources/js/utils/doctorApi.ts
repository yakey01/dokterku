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
    const response = await UnifiedAuth.makeJsonRequest<{
      success: boolean;
      data: UserData;
    }>('/api/v2/auth/me');
    
    if (!response.success) {
      throw new Error('Failed to fetch user data');
    }
    
    return response.data;
  }

  /**
   * Get doctor dashboard data
   */
  async getDashboard(): Promise<DoctorDashboardData> {
    const response = await UnifiedAuth.makeJsonRequest<{
      success: boolean;
      data: DoctorDashboardData;
    }>('/api/v2/dashboards/dokter');
    
    if (!response.success) {
      throw new Error('Failed to fetch dashboard data');
    }
    
    return response.data;
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
}

export default new DoctorApi();