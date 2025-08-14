// API Service for Dokter Attendance System

import { retryWithBackoff } from '../../utils/dokter/attendanceHelpers';

const API_BASE = '/api/v2/dashboards/dokter';

/**
 * Common headers for API requests with CSRF token
 */
const getHeaders = () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
  };
};

/**
 * Fetch user dashboard data
 */
export const fetchUserData = async () => {
  return retryWithBackoff(async () => {
    const response = await fetch(`${API_BASE}/`, {
      method: 'GET',
      headers: getHeaders(),
      credentials: 'same-origin'
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    return data.data;
  });
};

/**
 * Fetch schedule and shift data
 */
export const fetchScheduleData = async () => {
  const response = await fetch(`${API_BASE}/jadwal-jaga`, {
    method: 'GET',
    headers: getHeaders(),
    credentials: 'same-origin'
  });
  
  if (!response.ok) {
    throw new Error(`Failed to fetch schedule: ${response.statusText}`);
  }
  
  const data = await response.json();
  return data.data;
};

/**
 * Fetch work location status
 */
export const fetchWorkLocationStatus = async () => {
  const response = await fetch(`${API_BASE}/work-location/status`, {
    method: 'GET',
    headers: getHeaders(),
    credentials: 'same-origin'
  });
  
  if (!response.ok) {
    if (response.status === 404) {
      return null; // No work location assigned
    }
    throw new Error(`Failed to fetch work location: ${response.statusText}`);
  }
  
  const data = await response.json();
  return data.data;
};

/**
 * Fetch attendance records
 */
export const fetchAttendanceRecords = async (includeAll: boolean = true) => {
  const url = includeAll ? `${API_BASE}/presensi?include_all=1` : `${API_BASE}/presensi`;
  
  const response = await fetch(url, {
    method: 'GET',
    headers: getHeaders(),
    credentials: 'same-origin'
  });
  
  if (!response.ok) {
    throw new Error(`Failed to fetch attendance: ${response.statusText}`);
  }
  
  const data = await response.json();
  return data.data;
};

/**
 * Fetch server time for synchronization
 */
export const fetchServerTime = async () => {
  const response = await fetch('/api/v2/server-time', {
    method: 'GET',
    headers: getHeaders(),
    credentials: 'same-origin'
  });
  
  if (!response.ok) {
    console.warn('Failed to get server time, using client time');
    return new Date();
  }
  
  const data = await response.json();
  return new Date(data.data.current_time);
};

/**
 * Check in with GPS coordinates
 */
export const performCheckIn = async (params: {
  latitude: number;
  longitude: number;
  accuracy: number;
  jadwal_jaga_id?: number;
}) => {
  const checkinUrl = new URL(`${API_BASE}/checkin`, window.location.origin);
  
  // Add parameters
  if (params.jadwal_jaga_id) {
    checkinUrl.searchParams.append('jadwal_jaga_id', params.jadwal_jaga_id.toString());
  }
  checkinUrl.searchParams.append('latitude', params.latitude.toString());
  checkinUrl.searchParams.append('longitude', params.longitude.toString());
  checkinUrl.searchParams.append('accuracy', params.accuracy.toString());
  
  const response = await fetch(checkinUrl.toString(), {
    method: 'POST',
    headers: getHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify({
      latitude: params.latitude,
      longitude: params.longitude,
      accuracy: params.accuracy,
      jadwal_jaga_id: params.jadwal_jaga_id
    })
  });
  
  const data = await response.json();
  
  if (!response.ok) {
    throw new Error(data.message || `Check-in failed: ${response.statusText}`);
  }
  
  return data;
};

/**
 * Check out with GPS coordinates
 */
export const performCheckOut = async (params: {
  latitude: number;
  longitude: number;
  accuracy: number;
  jadwal_jaga_id?: number;
}) => {
  const checkoutUrl = new URL(`${API_BASE}/checkout`, window.location.origin);
  
  // Add parameters
  if (params.jadwal_jaga_id) {
    checkoutUrl.searchParams.append('jadwal_jaga_id', params.jadwal_jaga_id.toString());
  }
  checkoutUrl.searchParams.append('latitude', params.latitude.toString());
  checkoutUrl.searchParams.append('longitude', params.longitude.toString());
  checkoutUrl.searchParams.append('accuracy', params.accuracy.toString());
  
  const response = await fetch(checkoutUrl.toString(), {
    method: 'POST',
    headers: getHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify({
      latitude: params.latitude,
      longitude: params.longitude,
      accuracy: params.accuracy,
      jadwal_jaga_id: params.jadwal_jaga_id
    })
  });
  
  const data = await response.json();
  
  if (!response.ok) {
    throw new Error(data.message || `Check-out failed: ${response.statusText}`);
  }
  
  return data;
};

/**
 * Fetch attendance history
 */
export const fetchAttendanceHistory = async (startDate: Date, endDate: Date) => {
  const start = startDate.toISOString().split('T')[0];
  const end = endDate.toISOString().split('T')[0];
  
  const response = await fetch(`${API_BASE}/presensi?start=${start}&end=${end}`, {
    method: 'GET',
    headers: getHeaders(),
    credentials: 'same-origin'
  });
  
  if (!response.ok) {
    throw new Error(`Failed to fetch history: ${response.statusText}`);
  }
  
  const data = await response.json();
  
  // 🔍 DEBUG: Log the complete response structure
  console.log('🔍 Complete API Response:', data);
  
  // Fix: Handle the correct response structure
  if (data.success && data.data) {
    // Return both history and today_records for comprehensive data
    return {
      history: data.data.history || [],
      today_records: data.data.today_records || []
    };
  } else if (data.success && data.history) {
    // Fallback: direct history property
    return {
      history: data.history || [],
      today_records: data.today_records || []
    };
  }
  
  // If no valid structure found, return empty arrays
  return {
    history: [],
    today_records: []
  };
};