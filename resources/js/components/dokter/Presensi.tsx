import React, { useState, useEffect } from 'react';
import DoctorApi from '../../utils/doctorApi';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Professional custom map pins with modern styling
const createHospitalIcon = () => {
  return L.divIcon({
    className: 'custom-hospital-marker',
    html: `
      <div class="hospital-pin-container">
        <div class="hospital-pin-body">
          <div class="hospital-pin-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
              <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5Z"/>
              <path d="M12 5L8 21l4-7 4 7-4-16"/>
              <path d="M12 8v4M10 10h4"/>
            </svg>
          </div>
        </div>
        <div class="hospital-pin-pulse"></div>
        <div class="hospital-pin-shadow"></div>
      </div>
    `,
    iconSize: [40, 55],
    iconAnchor: [20, 55],
    popupAnchor: [0, -55]
  });
};

const createUserLocationIcon = () => {
  return L.divIcon({
    className: 'custom-user-marker',
    html: `
      <div class="user-pin-container">
        <div class="user-pin-body">
          <div class="user-pin-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <circle cx="12" cy="12" r="4"/>
            </svg>
          </div>
        </div>
        <div class="user-pin-pulse"></div>
        <div class="user-pin-accuracy-ring"></div>
        <div class="user-pin-shadow"></div>
      </div>
    `,
    iconSize: [36, 48],
    iconAnchor: [18, 48],
    popupAnchor: [0, -48]
  });
};
import { Calendar, Clock, DollarSign, User, Home, MapPin, CheckCircle, XCircle, Zap, Heart, Brain, Shield, Target, Award, TrendingUp, Sun, Moon, Coffee, Star, Crown, HandHeart, Hand, Camera, Wifi, WifiOff, AlertTriangle, History, UserCheck, FileText, Settings, Bell, ChevronLeft, ChevronRight, Filter, Plus, Send } from 'lucide-react';

interface CreativeAttendanceDashboardProps {
  userData?: {
    name: string;
    email: string;
    greeting?: string;
    role?: string;
    initials?: string;
  };
}

interface ScheduleData {
  id: number;
  tanggal_jaga: string;
  shift_template?: {
    id: number;
    nama_shift: string;
    jam_masuk: string;
    jam_pulang: string;
    warna?: string;
  };
  work_location?: {
    id: number;
    name: string;
    address: string;
    latitude: number;
    longitude: number;
    radius_meters: number;
    tolerance_settings: {
      late_tolerance_minutes: number;
      checkin_before_shift_minutes: number;
    };
  };
  schedule_status: {
    status: string;
    message: string;
    can_checkin_in?: string;
    window_closes_in?: number;
    is_late?: boolean;
  };
  timing_info: {
    shift_start: string;
    shift_end: string;
    current_time: string;
    check_in_window: {
      start: string;
      end: string;
      is_open: boolean;
    };
    status: string;
    next_action: string;
  };
}

interface ValidationResult {
  validation: {
    valid: boolean;
    message: string;
    code: string;
    can_checkin: boolean;
  };
  schedule_details?: {
    shift_name: string;
    effective_start_time: string;
    effective_end_time: string;
    is_late_checkin: boolean;
  };
}

const CreativeAttendanceDashboard: React.FC<CreativeAttendanceDashboardProps> = ({ userData }) => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [activeTab, setActiveTab] = useState('checkin');
  const [attendanceData, setAttendanceData] = useState({
    checkInTime: null,
    checkOutTime: null,
    workingHours: '00:00:00',
    overtimeHours: '00:00:00',
    breakTime: '00:00:00',
    location: 'RS. Kediri Medical Center'
  });
  const [showLeaveForm, setShowLeaveForm] = useState(false);
  
  // GPS and location states
  const [userLocation, setUserLocation] = useState(null);
  const [gpsStatus, setGpsStatus] = useState('idle'); // idle, loading, success, error
  const [gpsAccuracy, setGpsAccuracy] = useState(null);
  const [distanceToHospital, setDistanceToHospital] = useState(null);
  const [gpsError, setGpsError] = useState(null);
  
  // Schedule and validation states
  const [currentSchedule, setCurrentSchedule] = useState<ScheduleData | null>(null);
  const [scheduleLoading, setScheduleLoading] = useState(false);
  const [scheduleError, setScheduleError] = useState<string | null>(null);
  const [validationResult, setValidationResult] = useState<ValidationResult | null>(null);
  const [validationLoading, setValidationLoading] = useState(false);
  
  // Hospital location (RS. Kediri Medical Center)
  const hospitalLocation = [-7.8481, 112.0178];
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(5);
  const [filterPeriod, setFilterPeriod] = useState('weekly');
  
  // Leave form state
  const [leaveForm, setLeaveForm] = useState({
    type: 'annual',
    startDate: '',
    endDate: '',
    reason: '',
    days: 1
  });

  // Extended Attendance History Data
  const [attendanceHistory] = useState([
    { date: '2025-08-02', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-08-01', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-31', checkIn: '07:45', checkOut: '16:15', status: 'Present', hours: '8h 30m' },
    { date: '2025-07-30', checkIn: '08:00', checkOut: '16:30', status: 'Late', hours: '8h 30m' },
    { date: '2025-07-29', checkIn: '-', checkOut: '-', status: 'Sick Leave', hours: '0h 0m' },
    { date: '2025-07-28', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-27', checkIn: '07:35', checkOut: '16:25', status: 'Present', hours: '8h 50m' },
    { date: '2025-07-26', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-25', checkIn: '08:15', checkOut: '16:30', status: 'Late', hours: '8h 15m' },
    { date: '2025-07-24', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-23', checkIn: '-', checkOut: '-', status: 'Annual Leave', hours: '0h 0m' },
    { date: '2025-07-22', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-21', checkIn: '07:40', checkOut: '16:20', status: 'Present', hours: '8h 40m' },
    { date: '2025-07-20', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-19', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' }
  ]);

  // Monthly Statistics
  const [monthlyStats] = useState({
    totalDays: 22,
    presentDays: 20,
    lateDays: 2,
    absentDays: 0,
    overtimeHours: 15.5,
    leaveBalance: 12
  });

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
      
      // Update working hours if checked in
      if (isCheckedIn && attendanceData.checkInTime) {
        const workingTime = new Date() - new Date(attendanceData.checkInTime);
        const hours = Math.floor(workingTime / (1000 * 60 * 60));
        const minutes = Math.floor((workingTime % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((workingTime % (1000 * 60)) / 1000);
        
        setAttendanceData(prev => ({
          ...prev,
          workingHours: `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
        }));
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [isCheckedIn, attendanceData.checkInTime]);

  // GPS Detection Effect
  useEffect(() => {
    // Auto-detect GPS location when component loads
    detectUserLocation();
    // Load current schedule on component mount
    loadCurrentSchedule();
  }, []);
  
  // Validate check-in when location changes
  useEffect(() => {
    if (userLocation && currentSchedule) {
      validateCheckin();
    }
  }, [userLocation, currentSchedule]);

  // Calculate distance between two points using Haversine formula
  const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // Earth's radius in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c;
    return distance * 1000; // Convert to meters
  };

  // Hospital Location Functions
  const getHospitalLocation = () => {
    // Priority: Use work location from current schedule if available, otherwise use default hospital location
    if (currentSchedule?.work_location?.latitude && currentSchedule?.work_location?.longitude) {
      return [currentSchedule.work_location.latitude, currentSchedule.work_location.longitude];
    }
    return hospitalLocation;
  };
  
  const getHospitalLocationObject = () => {
    // Return detailed hospital location object with coordinates and metadata
    if (currentSchedule?.work_location) {
      return {
        latitude: currentSchedule.work_location.latitude,
        longitude: currentSchedule.work_location.longitude,
        name: currentSchedule.work_location.name || 'Work Location',
        address: currentSchedule.work_location.address || 'Work Address',
        radius: currentSchedule.work_location.radius_meters || 50,
        coordinates: [currentSchedule.work_location.latitude, currentSchedule.work_location.longitude]
      };
    }
    return {
      latitude: hospitalLocation[0],
      longitude: hospitalLocation[1],
      name: 'RS. Kediri Medical Center',
      address: 'Jl. Ahmad Yani No. 123',
      radius: 50,
      coordinates: hospitalLocation
    };
  };

  // GPS Detection Function
  const detectUserLocation = () => {
    setGpsStatus('loading');
    setGpsError(null);

    if (!navigator.geolocation) {
      setGpsStatus('error');
      setGpsError('GPS tidak didukung oleh browser ini');
      return;
    }

    const options = {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 60000
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude, accuracy } = position.coords;
        setUserLocation([latitude, longitude]);
        setGpsAccuracy(accuracy);
        setGpsStatus('success');
        
        // Calculate distance to hospital
        const distance = calculateDistance(
          latitude, longitude,
          hospitalLocation[0], hospitalLocation[1]
        );
        setDistanceToHospital(distance);
      },
      (error) => {
        setGpsStatus('error');
        switch(error.code) {
          case error.PERMISSION_DENIED:
            setGpsError('Akses lokasi ditolak. Silakan aktifkan izin lokasi.');
            break;
          case error.POSITION_UNAVAILABLE:
            setGpsError('Lokasi tidak tersedia. Pastikan GPS aktif.');
            break;
          case error.TIMEOUT:
            setGpsError('Waktu habis. Coba lagi.');
            break;
          default:
            setGpsError('Terjadi kesalahan saat mengakses lokasi.');
            break;
        }
      },
      options
    );
  };

  // Format distance for display
  const formatDistance = (meters) => {
    if (meters < 1000) {
      return `${Math.round(meters)}m`;
    } else {
      return `${(meters / 1000).toFixed(1)}km`;
    }
  };

  // Get GPS status color and icon
  const getGpsStatusDisplay = () => {
    switch (gpsStatus) {
      case 'loading':
        return {
          color: 'text-yellow-400',
          icon: '📍',
          text: 'Mencari lokasi...',
          bgColor: 'bg-yellow-500/10 border-yellow-400/30'
        };
      case 'success':
        return {
          color: 'text-green-400',
          icon: '✅',
          text: `Lokasi ditemukan (±${Math.round(gpsAccuracy)}m)`,
          bgColor: 'bg-green-500/10 border-green-400/30'
        };
      case 'error':
        return {
          color: 'text-red-400',
          icon: '❌',
          text: 'GPS gagal',
          bgColor: 'bg-red-500/10 border-red-400/30'
        };
      default:
        return {
          color: 'text-gray-400',
          icon: '📍',
          text: 'GPS siap',
          bgColor: 'bg-gray-500/10 border-gray-400/30'
        };
    }
  };

  const formatTime = (date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  };

  const formatDate = (date) => {
    return date.toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  // Filter attendance data based on period
  const getFilteredData = () => {
    const now = new Date();
    const filtered = attendanceHistory.filter(record => {
      const recordDate = new Date(record.date);
      
      if (filterPeriod === 'weekly') {
        const weekAgo = new Date(now);
        weekAgo.setDate(now.getDate() - 7);
        return recordDate >= weekAgo;
      } else if (filterPeriod === 'monthly') {
        const monthAgo = new Date(now);
        monthAgo.setMonth(now.getMonth() - 1);
        return recordDate >= monthAgo;
      }
      return true;
    });
    
    return filtered;
  };

  // Pagination logic
  const filteredData = getFilteredData();
  const totalPages = Math.ceil(filteredData.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentData = filteredData.slice(startIndex, endIndex);

  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  const handleFilterChange = (period) => {
    setFilterPeriod(period);
    setCurrentPage(1);
  };

  // Load current schedule
  const loadCurrentSchedule = async () => {
    setScheduleLoading(true);
    setScheduleError(null);
    
    try {
      const scheduleData = await DoctorApi.getCurrentSchedule();
      if (scheduleData) {
        setCurrentSchedule(scheduleData);
      } else {
        setScheduleError('Anda belum memiliki jadwal');
      }
    } catch (error) {
      console.error('Error loading schedule:', error);
      
      // Handle specific API error messages
      let errorMessage = 'Gagal terhubung ke server';
      
      if (error.message) {
        if (error.message.includes('No active schedule found') || 
            error.message.includes('404')) {
          errorMessage = 'Anda belum memiliki jadwal';
        } else if (error.message.includes('401') || error.message.includes('Unauthorized')) {
          errorMessage = 'Sesi telah berakhir. Silakan login kembali.';
        } else {
          errorMessage = error.message;
        }
      }
      
      setScheduleError(errorMessage);
    } finally {
      setScheduleLoading(false);
    }
  };
  
  // Validate check-in with current location
  const validateCheckin = async () => {
    if (!userLocation) return;
    
    setValidationLoading(true);
    
    try {
      const validationData = await DoctorApi.validateCheckin(
        userLocation[0], // latitude
        userLocation[1], // longitude
        gpsAccuracy
      );
      setValidationResult(validationData);
    } catch (error) {
      console.error('Error validating check-in:', error);
    } finally {
      setValidationLoading(false);
    }
  };

  const handleCheckIn = async () => {
    // Only allow check-in if validation passes
    if (!validationResult?.validation?.can_checkin) {
      alert('Check-in tidak diizinkan saat ini. Periksa jadwal dan lokasi Anda.');
      return;
    }
    
    setIsCheckedIn(true);
    const now = new Date();
    setAttendanceData(prev => ({
      ...prev,
      checkInTime: now.toISOString(),
      checkOutTime: null
    }));
  };

  const handleCheckOut = async () => {
    setIsCheckedIn(false);
    const now = new Date();
    setAttendanceData(prev => ({
      ...prev,
      checkOutTime: now.toISOString()
    }));
  };

  const handleLeaveSubmit = () => {
    alert(`Pengajuan cuti ${leaveForm.type} telah dikirim untuk persetujuan`);
    setShowLeaveForm(false);
    setLeaveForm({
      type: 'annual',
      startDate: '',
      endDate: '',
      reason: '',
      days: 1
    });
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Present': return 'text-green-400';
      case 'Late': return 'text-yellow-400';
      case 'Absent': return 'text-red-400';
      case 'Sick Leave': return 'text-blue-400';
      case 'Annual Leave': return 'text-purple-400';
      default: return 'text-gray-400';
    }
  };

  const tabItems = [
    { id: 'checkin', icon: Clock, label: 'Check In' },
    { id: 'history', icon: History, label: 'History' },
    { id: 'stats', icon: TrendingUp, label: 'Stats' },
    { id: 'leave', icon: Calendar, label: 'Leave' }
  ];

  const renderTabContent = () => {
    switch (activeTab) {
      case 'checkin':
        return (
          <div className="space-y-4 md:space-y-6 lg:space-y-8">
            {/* Schedule Status Card - NEW */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 mb-4
                            md:rounded-3xl md:p-6
                            lg:p-8">
              {scheduleLoading ? (
                <div className="text-center">
                  <div className="inline-flex items-center space-x-3">
                    <div className="w-5 h-5 border-2 border-cyan-400 border-t-transparent rounded-full animate-spin"></div>
                    <span className="text-cyan-300">Memuat jadwal...</span>
                  </div>
                </div>
              ) : scheduleError ? (
                <div className="text-center py-2">
                  {scheduleError.includes('Anda belum memiliki jadwal') ? (
                    <div>
                      <div className="inline-flex items-center space-x-3 px-4 py-3 rounded-2xl bg-blue-500/20 border border-blue-400/30">
                        <Calendar className="w-5 h-5 text-blue-400" />
                        <span className="text-blue-300 font-medium">{scheduleError}</span>
                      </div>
                      <div className="text-xs text-gray-400 mt-2">
                        Silakan hubungi admin untuk mendapatkan jadwal jaga
                      </div>
                    </div>
                  ) : (
                    <div className="inline-flex items-center space-x-3 px-4 py-2 rounded-2xl bg-yellow-500/20 border border-yellow-400/50">
                      <AlertTriangle className="w-5 h-5 text-yellow-400" />
                      <span className="text-yellow-300">{scheduleError}</span>
                    </div>
                  )}
                </div>
              ) : currentSchedule ? (
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center space-x-3">
                      <div className="w-10 h-10 rounded-xl flex items-center justify-center" 
                           style={{ backgroundColor: currentSchedule.shift_template?.warna || '#3b82f6' }}>
                        <Clock className="w-5 h-5 text-white" />
                      </div>
                      <div>
                        <div className="font-semibold text-white">
                          {currentSchedule.shift_template?.nama_shift || 'Jadwal Jaga'}
                        </div>
                        <div className="text-sm text-gray-300">
                          {currentSchedule.timing_info.shift_start} - {currentSchedule.timing_info.shift_end}
                        </div>
                      </div>
                    </div>
                    <div className={`px-3 py-1 rounded-full text-xs font-medium ${
                      currentSchedule.schedule_status.status === 'checkin_window' ? 'bg-green-500/20 text-green-400' :
                      currentSchedule.schedule_status.status === 'upcoming' ? 'bg-yellow-500/20 text-yellow-400' :
                      currentSchedule.schedule_status.status === 'in_progress' ? 'bg-blue-500/20 text-blue-400' :
                      'bg-gray-500/20 text-gray-400'
                    }`}>
                      {currentSchedule.schedule_status.status === 'checkin_window' ? '✅ Bisa Check-in' :
                       currentSchedule.schedule_status.status === 'upcoming' ? '⏳ Belum Waktunya' :
                       currentSchedule.schedule_status.status === 'in_progress' ? '🔄 Sedang Jaga' :
                       '📋 ' + currentSchedule.schedule_status.status}
                    </div>
                  </div>
                  
                  <div className="bg-black/20 rounded-xl p-3 mb-3">
                    <div className="text-sm text-white mb-1 font-medium">{currentSchedule.timing_info.status}</div>
                    <div className="text-xs text-gray-300">{currentSchedule.schedule_status.message}</div>
                  </div>
                  
                  {currentSchedule.work_location && (
                    <div className="flex items-center space-x-2 text-xs text-gray-300">
                      <MapPin className="w-4 h-4" />
                      <span>{currentSchedule.work_location.name}</span>
                      <span>•</span>
                      <span>Radius {currentSchedule.work_location.radius_meters}m</span>
                    </div>
                  )}
                </div>
              ) : (
                <div className="text-center py-6">
                  <div className="inline-flex items-center space-x-3 px-4 py-3 rounded-2xl bg-blue-500/20 border border-blue-400/30">
                    <Calendar className="w-5 h-5 text-blue-400" />
                    <span className="text-blue-300 font-medium">Anda belum memiliki jadwal</span>
                  </div>
                  <div className="text-xs text-gray-400 mt-2">
                    Silakan hubungi admin untuk mendapatkan jadwal jaga
                  </div>
                </div>
              )}
            </div>
            
            {/* Validation Status - NEW */}
            {validationResult && (
              <div className={`bg-white/10 backdrop-blur-xl rounded-2xl p-4 border mb-4 ${
                validationResult.validation.valid ? 'border-green-400/50' : 'border-red-400/50'
              }`}>
                <div className="flex items-center space-x-3 mb-2">
                  <div className={`w-5 h-5 rounded-full ${
                    validationResult.validation.valid ? 'bg-green-400' : 'bg-red-400'
                  }`}></div>
                  <div className={`font-medium ${
                    validationResult.validation.valid ? 'text-green-300' : 'text-red-300'
                  }`}>
                    {validationResult.validation.message}
                  </div>
                </div>
                
                {validationResult.schedule_details && (
                  <div className="text-xs text-gray-300">
                    <div>Shift: {validationResult.schedule_details.shift_name}</div>
                    <div>Jadwal: {validationResult.schedule_details.effective_start_time} - {validationResult.schedule_details.effective_end_time}</div>
                    {validationResult.schedule_details.is_late_checkin && (
                      <div className="text-yellow-400 mt-1">⚠️ Check-in terlambat tapi masih dalam toleransi</div>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* Responsive Current Date and Time */}
            <div className="text-center">
              <div className="text-base md:text-lg lg:text-xl text-purple-200 mb-2">
                {formatDate(currentTime)}
              </div>
              <div className="dokter-responsive-title font-bold text-white">
                {formatTime(currentTime)}
              </div>
            </div>

            {/* Responsive Attendance Status Card */}
            <div className="dokter-attendance-card bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20
                            md:rounded-3xl md:p-6
                            lg:p-8">
              <div className="text-center mb-4">
                <div className={`inline-flex items-center space-x-3 px-6 py-3 rounded-2xl transition-all duration-500 ${
                  isCheckedIn 
                    ? 'bg-gradient-to-r from-green-500/30 to-emerald-500/30 border border-green-400/50' 
                    : 'bg-gradient-to-r from-gray-500/30 to-purple-500/30 border border-purple-400/50'
                }`}>
                  <div className={`w-3 h-3 rounded-full ${isCheckedIn ? 'bg-green-400 animate-pulse' : 'bg-purple-400'}`}></div>
                  <span className="text-white font-semibold">
                    {isCheckedIn ? '🚀 Sedang Bekerja' : '😴 Belum Check-in'}
                  </span>
                </div>
              </div>

              {/* Responsive Working Hours Display */}
              {isCheckedIn && (
                <div className="grid grid-cols-3 gap-3 mt-4
                                md:gap-4 md:mt-6
                                lg:gap-6 lg:mt-8">
                  <div className="text-center">
                    <div className="text-base md:text-lg lg:text-xl font-bold text-green-400">
                      {attendanceData.workingHours}
                    </div>
                    <div className="text-xs md:text-sm text-gray-300">Jam Kerja</div>
                  </div>
                  <div className="text-center">
                    <div className="text-base md:text-lg lg:text-xl font-bold text-blue-400">
                      {attendanceData.breakTime}
                    </div>
                    <div className="text-xs md:text-sm text-gray-300">Istirahat</div>
                  </div>
                  <div className="text-center">
                    <div className="text-base md:text-lg lg:text-xl font-bold text-purple-400">
                      {attendanceData.overtimeHours}
                    </div>
                    <div className="text-xs md:text-sm text-gray-300">Overtime</div>
                  </div>
                </div>
              )}

              {/* Check-in/out times */}
              {(attendanceData.checkInTime || attendanceData.checkOutTime) && (
                <div className="mt-4 p-4 bg-black/20 rounded-2xl">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="text-gray-400">Check-in: </span>
                      <span className="text-green-400">
                        {attendanceData.checkInTime ? new Date(attendanceData.checkInTime).toLocaleTimeString('id-ID') : '-'}
                      </span>
                    </div>
                    <div>
                      <span className="text-gray-400">Check-out: </span>
                      <span className="text-red-400">
                        {attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime).toLocaleTimeString('id-ID') : '-'}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Responsive Check-in/out Buttons */}
            <div className="dokter-checkin-buttons grid grid-cols-1 gap-4
                            md:grid-cols-2 md:gap-6
                            lg:gap-8">
              <button 
                onClick={handleCheckIn}
                disabled={isCheckedIn || !validationResult?.validation?.can_checkin || validationLoading}
                className={`dokter-attendance-button dokter-interactive-element relative group p-4 rounded-2xl transition-all duration-500 transform touch-manipulation min-h-[44px]
                            md:p-6 md:rounded-3xl
                            lg:p-8 lg:rounded-3xl
                            ${
                  (isCheckedIn || !validationResult?.validation?.can_checkin || validationLoading)
                    ? 'opacity-50 cursor-not-allowed' 
                    : 'hover:scale-105 active:scale-95'
                }`}
              >
                <div className="absolute inset-0 bg-gradient-to-br from-green-500/30 to-emerald-600/30 rounded-3xl"></div>
                <div className="absolute inset-0 bg-white/5 rounded-3xl border border-green-400/30"></div>
                {!isCheckedIn && (
                  <div className="absolute inset-0 bg-gradient-to-br from-green-400/0 to-green-400/20 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                )}
                <div className="relative text-center">
                  <div className="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center
                                  md:w-16 md:h-16 md:mb-4 md:rounded-2xl
                                  lg:w-20 lg:h-20 lg:mb-6">
                    {validationLoading ? (
                      <div className="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin md:w-8 md:h-8 lg:w-10 lg:h-10"></div>
                    ) : (
                      <Sun className="w-6 h-6 text-white md:w-8 md:h-8 lg:w-10 lg:h-10" />
                    )}
                  </div>
                  <div className="text-white font-bold text-base md:text-lg lg:text-xl">
                    {validationLoading ? 'Validating...' : 'Check In'}
                  </div>
                  <div className="text-green-300 text-sm md:text-base">
                    {validationResult?.validation?.can_checkin ? 'Siap check-in' : 
                     validationResult?.validation?.valid === false ? 'Tidak bisa check-in' :
                     'Mulai bekerja'}
                  </div>
                </div>
              </button>
              
              <button 
                onClick={handleCheckOut}
                disabled={!isCheckedIn}
                className={`dokter-attendance-button dokter-interactive-element relative group p-4 rounded-2xl transition-all duration-500 transform touch-manipulation min-h-[44px]
                            md:p-6 md:rounded-3xl
                            lg:p-8 lg:rounded-3xl
                            ${
                  !isCheckedIn 
                    ? 'opacity-50 cursor-not-allowed' 
                    : 'hover:scale-105 active:scale-95'
                }`}
              >
                <div className="absolute inset-0 bg-gradient-to-br from-purple-500/30 to-pink-600/30 rounded-3xl"></div>
                <div className="absolute inset-0 bg-white/5 rounded-3xl border border-purple-400/30"></div>
                {isCheckedIn && (
                  <div className="absolute inset-0 bg-gradient-to-br from-purple-400/0 to-purple-400/20 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                )}
                <div className="relative text-center">
                  <div className="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center
                                  md:w-16 md:h-16 md:mb-4 md:rounded-2xl
                                  lg:w-20 lg:h-20 lg:mb-6">
                    <Moon className="w-6 h-6 text-white md:w-8 md:h-8 lg:w-10 lg:h-10" />
                  </div>
                  <div className="text-white font-bold text-base md:text-lg lg:text-xl">Check Out</div>
                  <div className="text-purple-300 text-sm md:text-base">Selesai bekerja</div>
                </div>
              </button>
            </div>

            {/* Enhanced Location Map with Professional Pins */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden
                            md:rounded-3xl
                            lg:rounded-3xl">
              <div className="p-3 border-b border-white/10
                              md:p-4
                              lg:p-6">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-xl flex items-center justify-center">
                      <MapPin className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="font-semibold text-white">RS. Kediri Medical Center</div>
                      <div className="text-green-300 text-sm">Jl. Ahmad Yani No. 123</div>
                      {distanceToHospital && (
                        <div className={`text-xs ${
                          currentSchedule?.work_location && distanceToHospital <= currentSchedule.work_location.radius_meters
                            ? 'text-green-300'
                            : 'text-cyan-300'
                        }`}>
                          📏 {formatDistance(distanceToHospital)} dari Anda
                          {currentSchedule?.work_location && distanceToHospital <= currentSchedule.work_location.radius_meters && 
                            ' ✓ Dalam radius'}
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <button
                      onClick={detectUserLocation}
                      disabled={gpsStatus === 'loading'}
                      className="px-3 py-1.5 bg-purple-500/20 border border-purple-400/30 rounded-lg text-xs text-purple-300 hover:bg-purple-500/30 transition-colors disabled:opacity-50"
                    >
                      🔄 GPS
                    </button>
                    <button
                      onClick={loadCurrentSchedule}
                      disabled={scheduleLoading}
                      className="px-3 py-1.5 bg-blue-500/20 border border-blue-400/30 rounded-lg text-xs text-blue-300 hover:bg-blue-500/30 transition-colors disabled:opacity-50"
                    >
                      📅 Jadwal
                    </button>
                    <div className="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                  </div>
                </div>
                
                {/* GPS Status Display */}
                <div className={`mt-3 p-2.5 rounded-xl border ${getGpsStatusDisplay().bgColor}`}>
                  <div className="flex items-center justify-between text-sm">
                    <div className="flex items-center space-x-2">
                      <span>{getGpsStatusDisplay().icon}</span>
                      <span className={getGpsStatusDisplay().color}>
                        {getGpsStatusDisplay().text}
                      </span>
                    </div>
                    {gpsStatus === 'success' && gpsAccuracy && (
                      <div className="text-cyan-300 text-xs">
                        ±{Math.round(gpsAccuracy)}m akurasi
                      </div>
                    )}
                  </div>
                  {gpsError && (
                    <div className="mt-2 text-xs text-red-300">
                      {gpsError}
                    </div>
                  )}
                </div>
              </div>
              
              <div className="relative h-32 md:h-48 lg:h-64 overflow-hidden">
                <style>{`
                  /* Enhanced Leaflet popup styling */
                  .leaflet-popup-content-wrapper {
                    background: rgba(255, 255, 255, 0.98);
                    border-radius: 16px;
                    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(147, 51, 234, 0.2);
                    backdrop-filter: blur(20px);
                  }
                  .leaflet-popup-tip {
                    background: rgba(255, 255, 255, 0.98);
                    border: 1px solid rgba(147, 51, 234, 0.2);
                  }
                  .leaflet-control-zoom {
                    border: none !important;
                  }
                  .leaflet-control-zoom a {
                    background: rgba(0, 0, 0, 0.8) !important;
                    color: white !important;
                    border: 1px solid rgba(147, 51, 234, 0.4) !important;
                    backdrop-filter: blur(15px);
                    border-radius: 8px !important;
                  }
                  .leaflet-control-zoom a:hover {
                    background: rgba(147, 51, 234, 0.9) !important;
                    transform: scale(1.05);
                  }

                  /* Professional Hospital Pin */
                  .hospital-pin-container {
                    position: relative;
                    width: 40px;
                    height: 55px;
                  }
                  .hospital-pin-body {
                    position: absolute;
                    top: 0;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 32px;
                    height: 45px;
                    background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #C084FC 100%);
                    border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.3);
                    border: 2px solid rgba(255, 255, 255, 0.2);
                    animation: hospitalPinBounce 2s ease-in-out infinite;
                  }
                  .hospital-pin-icon {
                    color: white;
                    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
                  }
                  .hospital-pin-pulse {
                    position: absolute;
                    top: 8px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 48px;
                    height: 48px;
                    background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
                    border-radius: 50%;
                    animation: hospitalPulse 2s ease-out infinite;
                  }
                  .hospital-pin-shadow {
                    position: absolute;
                    bottom: -5px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 20px;
                    height: 8px;
                    background: radial-gradient(ellipse, rgba(0, 0, 0, 0.3) 0%, transparent 70%);
                    border-radius: 50%;
                  }

                  /* Professional User Location Pin */
                  .user-pin-container {
                    position: relative;
                    width: 36px;
                    height: 48px;
                  }
                  .user-pin-body {
                    position: absolute;
                    top: 0;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 28px;
                    height: 38px;
                    background: linear-gradient(135deg, #06B6D4 0%, #0EA5E9 50%, #3B82F6 100%);
                    border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.4);
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    animation: userPinBounce 1.5s ease-in-out infinite;
                  }
                  .user-pin-icon {
                    color: white;
                    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
                  }
                  .user-pin-pulse {
                    position: absolute;
                    top: 6px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 40px;
                    height: 40px;
                    background: radial-gradient(circle, rgba(6, 182, 212, 0.4) 0%, transparent 70%);
                    border-radius: 50%;
                    animation: userPulse 1.8s ease-out infinite;
                  }
                  .user-pin-accuracy-ring {
                    position: absolute;
                    top: 2px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 50px;
                    height: 50px;
                    border: 2px solid rgba(6, 182, 212, 0.3);
                    border-radius: 50%;
                    animation: accuracyRing 3s linear infinite;
                  }
                  .user-pin-shadow {
                    position: absolute;
                    bottom: -3px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 16px;
                    height: 6px;
                    background: radial-gradient(ellipse, rgba(0, 0, 0, 0.25) 0%, transparent 70%);
                    border-radius: 50%;
                  }

                  /* Animations */
                  @keyframes hospitalPinBounce {
                    0%, 100% { transform: translateX(-50%) translateY(0); }
                    50% { transform: translateX(-50%) translateY(-4px); }
                  }
                  @keyframes userPinBounce {
                    0%, 100% { transform: translateX(-50%) translateY(0); }
                    50% { transform: translateX(-50%) translateY(-3px); }
                  }
                  @keyframes hospitalPulse {
                    0% { opacity: 1; transform: translateX(-50%) scale(0.8); }
                    100% { opacity: 0; transform: translateX(-50%) scale(1.4); }
                  }
                  @keyframes userPulse {
                    0% { opacity: 1; transform: translateX(-50%) scale(0.7); }
                    100% { opacity: 0; transform: translateX(-50%) scale(1.2); }
                  }
                  @keyframes accuracyRing {
                    0% { opacity: 0.6; transform: translateX(-50%) scale(0.8) rotate(0deg); }
                    50% { opacity: 0.3; transform: translateX(-50%) scale(1.1) rotate(180deg); }
                    100% { opacity: 0.6; transform: translateX(-50%) scale(0.8) rotate(360deg); }
                  }
                `}</style>
                <MapContainer
                  center={userLocation || getHospitalLocation()}
                  zoom={userLocation ? 17 : 16}
                  scrollWheelZoom={false}
                  touchZoom={true}
                  doubleClickZoom={false}
                  dragging={true}
                  className="h-full w-full rounded-none"
                  style={{
                    filter: 'hue-rotate(280deg) saturate(1.2) brightness(0.95) contrast(1.1)',
                    zIndex: 1,
                  }}
                >
                  <TileLayer
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                  />
                  
                  {/* Hospital Marker with Professional Pin */}
                  <Marker position={getHospitalLocation()} icon={createHospitalIcon()}>
                    <Popup
                      closeButton={false}
                      className="custom-popup"
                    >
                      <div className="text-center p-2">
                        <div className="flex items-center space-x-2 mb-2">
                          <div className="w-6 h-6 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                            <span className="text-white text-xs">🏥</span>
                          </div>
                          <strong className="text-purple-800 text-sm">
                            {currentSchedule?.work_location?.name || 'RS. Kediri Medical Center'}
                          </strong>
                        </div>
                        <div className="text-gray-700 text-xs mb-1">
                          {currentSchedule?.work_location?.address || 'Jl. Ahmad Yani No. 123'}
                        </div>
                        <div className="text-green-600 text-xs flex items-center justify-center space-x-1">
                          <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                          <span>
                            {currentSchedule?.work_location ? 'Work Location Verified' : 'Hospital Location Verified'}
                          </span>
                        </div>
                        {distanceToHospital && (
                          <div className={`text-xs mt-1 font-semibold ${
                            currentSchedule?.work_location && distanceToHospital <= currentSchedule.work_location.radius_meters
                              ? 'text-green-600'
                              : 'text-cyan-600'
                          }`}>
                            📏 {formatDistance(distanceToHospital)} dari lokasi Anda
                            {currentSchedule?.work_location && distanceToHospital <= currentSchedule.work_location.radius_meters && 
                              ' ✓ Valid'}
                          </div>
                        )}
                      </div>
                    </Popup>
                  </Marker>

                  {/* User Location Marker with Professional Pin */}
                  {userLocation && (
                    <Marker position={userLocation} icon={createUserLocationIcon()}>
                      <Popup
                        closeButton={false}
                        className="custom-popup"
                      >
                        <div className="text-center p-2">
                          <div className="flex items-center space-x-2 mb-2">
                            <div className="w-6 h-6 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-full flex items-center justify-center">
                              <span className="text-white text-xs">📍</span>
                            </div>
                            <strong className="text-cyan-800 text-sm">Lokasi Anda</strong>
                          </div>
                          <div className="text-gray-700 text-xs mb-1">
                            GPS: {userLocation[0].toFixed(6)}, {userLocation[1].toFixed(6)}
                          </div>
                          <div className="text-blue-600 text-xs flex items-center justify-center space-x-1">
                            <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                            <span>Akurasi ±{Math.round(gpsAccuracy)}m</span>
                          </div>
                          {distanceToHospital && (
                            <div className="text-purple-600 text-xs mt-1 font-semibold">
                              🎯 {formatDistance(distanceToHospital)} ke hospital
                            </div>
                          )}
                        </div>
                      </Popup>
                    </Marker>
                  )}
                </MapContainer>
                
                {/* Enhanced Live Location Indicator */}
                <div className="absolute top-4 left-4 bg-black/80 backdrop-blur-md rounded-2xl p-3 border border-white/20 z-[1000] shadow-lg">
                  <div className="flex items-center space-x-2">
                    <div className={`w-2 h-2 rounded-full animate-pulse ${
                      gpsStatus === 'success' ? 'bg-green-400' :
                      gpsStatus === 'loading' ? 'bg-yellow-400' :
                      gpsStatus === 'error' ? 'bg-red-400' : 'bg-gray-400'
                    }`}></div>
                    <span className="text-white text-sm font-medium">
                      {gpsStatus === 'success' ? '🎯 GPS Aktif' :
                       gpsStatus === 'loading' ? '📍 Mencari...' :
                       gpsStatus === 'error' ? '❌ GPS Error' : '📍 GPS Ready'}
                    </span>
                  </div>
                  {gpsStatus === 'success' && distanceToHospital && (
                    <div className="text-cyan-300 text-xs mt-1 font-medium">
                      📏 {formatDistance(distanceToHospital)} ke RS
                    </div>
                  )}
                </div>

                {/* GPS Accuracy Indicator */}
                {gpsStatus === 'success' && gpsAccuracy && (
                  <div className="absolute top-4 right-4 bg-black/80 backdrop-blur-md rounded-2xl p-2 border border-white/20 z-[1000] shadow-lg">
                    <div className="flex items-center space-x-1">
                      <div className="w-2 h-2 bg-cyan-400 rounded-full"></div>
                      <span className="text-cyan-300 text-xs font-medium">
                        ±{Math.round(gpsAccuracy)}m
                      </span>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Today's Work Summary Card */}
            <div className="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-lg font-semibold text-white flex items-center space-x-2">
                  <Clock className="w-5 h-5 text-blue-400" />
                  <span>Jam Kerja Hari Ini</span>
                </h4>
                <div className="flex items-center space-x-1">
                  <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                  <span className="text-xs text-green-300">Live</span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-4">
                {/* Check In Time */}
                <div className="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-2xl p-4 border border-green-400/30">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center">
                      <Sun className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="text-xl font-bold text-green-400">
                        {attendanceData.checkInTime ? new Date(attendanceData.checkInTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
                      </div>
                      <div className="text-xs text-green-300">Check In</div>
                    </div>
                  </div>
                </div>

                {/* Check Out Time */}
                <div className="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-2xl p-4 border border-purple-400/30">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center">
                      <Moon className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="text-xl font-bold text-purple-400">
                        {attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
                      </div>
                      <div className="text-xs text-purple-300">Check Out</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Progress Bar */}
              <div className="mb-4">
                <div className="flex justify-between text-sm mb-2">
                  <span className="text-gray-300">Progress Hari Ini</span>
                  <span className="text-cyan-400">
                    {(() => {
                      if (!isCheckedIn || !attendanceData.checkInTime) return '0%';
                      const workingTime = new Date() - new Date(attendanceData.checkInTime);
                      const hours = workingTime / (1000 * 60 * 60);
                      const percentage = Math.min((hours / 8) * 100, 100);
                      return `${percentage.toFixed(1)}%`;
                    })()}
                  </span>
                </div>
                <div className="w-full bg-gray-700/50 rounded-full h-3">
                  <div 
                    className="bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-500 h-3 rounded-full transition-all duration-500 relative overflow-hidden"
                    style={{ 
                      width: `${(() => {
                        if (!isCheckedIn || !attendanceData.checkInTime) return 0;
                        const workingTime = new Date() - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        return Math.min((hours / 8) * 100, 100);
                      })()}%` 
                    }}
                  >
                    <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
                  </div>
                </div>
              </div>

              {/* Shortage/Overtime Indicator */}
              <div className="grid grid-cols-2 gap-3">
                <div className={`p-3 rounded-xl border ${
                  (() => {
                    if (!isCheckedIn || !attendanceData.checkInTime) return 'bg-red-500/10 border-red-400/30';
                    const workingTime = new Date() - new Date(attendanceData.checkInTime);
                    const hours = workingTime / (1000 * 60 * 60);
                    return hours >= 8 ? 'bg-green-500/10 border-green-400/30' : 'bg-red-500/10 border-red-400/30';
                  })()
                }`}>
                  <div className="text-center">
                    <div className={`text-lg font-bold ${
                      (() => {
                        if (!isCheckedIn || !attendanceData.checkInTime) return 'text-red-400';
                        const workingTime = new Date() - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        const shortage = Math.max(8 - hours, 0);
                        return shortage > 0 ? 'text-red-400' : 'text-green-400';
                      })()
                    }`}>
                      {(() => {
                        if (!isCheckedIn || !attendanceData.checkInTime) return '8:00:00';
                        const workingTime = new Date() - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        const shortage = Math.max(8 - hours, 0);
                        const shortageHours = Math.floor(shortage);
                        const shortageMinutes = Math.floor((shortage % 1) * 60);
                        const shortageSeconds = Math.floor(((shortage % 1) * 60 % 1) * 60);
                        return shortage > 0 ? 
                          `${shortageHours.toString().padStart(2, '0')}:${shortageMinutes.toString().padStart(2, '0')}:${shortageSeconds.toString().padStart(2, '0')}` :
                          '0:00:00';
                      })()}
                    </div>
                    <div className="text-xs text-gray-300 flex items-center justify-center space-x-1">
                      <AlertTriangle className="w-3 h-3" />
                      <span>Kekurangan</span>
                    </div>
                  </div>
                </div>

                <div className={`p-3 rounded-xl border ${
                  (() => {
                    if (!isCheckedIn || !attendanceData.checkInTime) return 'bg-blue-500/10 border-blue-400/30';
                    const workingTime = new Date() - new Date(attendanceData.checkInTime);
                    const hours = workingTime / (1000 * 60 * 60);
                    return hours > 8 ? 'bg-blue-500/10 border-blue-400/30' : 'bg-blue-500/10 border-blue-400/30';
                  })()
                }`}>
                  <div className="text-center">
                    <div className="text-lg font-bold text-blue-400">
                      {isCheckedIn ? attendanceData.workingHours : '0:00:00'}
                    </div>
                    <div className="text-xs text-gray-300 flex items-center justify-center space-x-1">
                      <Clock className="w-3 h-3" />
                      <span>Jam Kerja</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Quick Tips */}
              <div className="mt-4 p-3 bg-gradient-to-r from-cyan-500/10 via-blue-500/10 to-purple-500/10 rounded-xl border border-cyan-400/20">
                <div className="flex items-start space-x-2">
                  <div className="w-4 h-4 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-xs text-white font-bold">💡</span>
                  </div>
                  <div className="text-xs text-cyan-200">
                    {(() => {
                      if (!isCheckedIn) return 'Jangan lupa check-in untuk memulai perhitungan jam kerja!';
                      if (!attendanceData.checkInTime) return 'Mulai hari kerja Anda dengan semangat!';
                      
                      const workingTime = new Date() - new Date(attendanceData.checkInTime);
                      const hours = workingTime / (1000 * 60 * 60);
                      
                      if (hours < 4) return 'Semangat! Hari masih panjang untuk mencapai target 8 jam.';
                      if (hours < 6) return 'Kerja bagus! Sudah setengah perjalanan menuju target harian.';
                      if (hours < 8) return 'Hampir sampai target! Pertahankan semangat kerja Anda.';
                      if (hours < 9) return 'Target tercapai! Jam kerja tambahan akan dihitung sebagai overtime.';
                      return 'Luar biasa! Anda sudah bekerja melebihi jam standar hari ini.';
                    })()}
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      case 'history':
        return (
          <div className="space-y-3 md:space-y-4 lg:space-y-6">
            <div className="flex flex-col space-y-3 mb-4
                            md:flex-row md:justify-between md:items-center md:space-y-0 md:mb-6
                            lg:mb-8">
              <h3 className="text-lg md:text-xl lg:text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Riwayat Presensi
              </h3>
              <div className="flex items-center space-x-2">
                <Filter className="w-4 h-4 text-purple-300 md:w-5 md:h-5" />
                <select 
                  value={filterPeriod}
                  onChange={(e) => handleFilterChange(e.target.value)}
                  className="bg-white/10 backdrop-blur-xl border border-white/20 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-400
                             md:px-4 md:py-2.5 md:text-base
                             min-h-[44px] touch-manipulation"
                >
                  <option value="weekly" className="bg-gray-800">7 Hari</option>
                  <option value="monthly" className="bg-gray-800">30 Hari</option>
                </select>
              </div>
            </div>

            {currentData.map((record, index) => (
              <div key={index} className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div className="flex justify-between items-center mb-2">
                  <span className="text-white font-semibold">{record.date}</span>
                  <span className={`text-sm font-medium ${getStatusColor(record.status)}`}>
                    {record.status}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-sm">
                  <div>
                    <span className="text-gray-400">Masuk: </span>
                    <span className="text-white">{record.checkIn}</span>
                  </div>
                  <div>
                    <span className="text-gray-400">Keluar: </span>
                    <span className="text-white">{record.checkOut}</span>
                  </div>
                  <div>
                    <span className="text-gray-400">Durasi: </span>
                    <span className="text-white">{record.hours}</span>
                  </div>
                </div>
              </div>
            ))}

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex items-center justify-center space-x-2 mt-6">
                <button
                  onClick={() => handlePageChange(currentPage - 1)}
                  disabled={currentPage === 1}
                  className={`p-2 rounded-xl transition-all ${
                    currentPage === 1 
                      ? 'bg-gray-500/20 text-gray-500 cursor-not-allowed' 
                      : 'bg-white/10 text-white hover:bg-white/20'
                  }`}
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>

                {[...Array(totalPages)].map((_, index) => {
                  const page = index + 1;
                  return (
                    <button
                      key={page}
                      onClick={() => handlePageChange(page)}
                      className={`w-8 h-8 rounded-xl transition-all text-sm font-medium ${
                        currentPage === page
                          ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white'
                          : 'bg-white/10 text-gray-300 hover:bg-white/20'
                      }`}
                    >
                      {page}
                    </button>
                  );
                })}

                <button
                  onClick={() => handlePageChange(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  className={`p-2 rounded-xl transition-all ${
                    currentPage === totalPages 
                      ? 'bg-gray-500/20 text-gray-500 cursor-not-allowed' 
                      : 'bg-white/10 text-white hover:bg-white/20'
                  }`}
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            )}

            <div className="text-center text-sm text-gray-300 mt-4">
              Menampilkan {startIndex + 1}-{Math.min(endIndex, filteredData.length)} dari {filteredData.length} data
            </div>
          </div>
        );

      case 'stats':
        return (
          <div className="space-y-6">
            <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
              Statistik Bulanan
            </h3>
            
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div className="text-center">
                  <div className="text-2xl font-bold text-green-400">{monthlyStats.presentDays}</div>
                  <div className="text-sm text-gray-300">Hari Hadir</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div className="text-center">
                  <div className="text-2xl font-bold text-yellow-400">{monthlyStats.lateDays}</div>
                  <div className="text-sm text-gray-300">Hari Terlambat</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-400">{monthlyStats.overtimeHours}h</div>
                  <div className="text-sm text-gray-300">Overtime</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div className="text-center">
                  <div className="text-2xl font-bold text-purple-400">{monthlyStats.leaveBalance}</div>
                  <div className="text-sm text-gray-300">Sisa Cuti</div>
                </div>
              </div>
            </div>

            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
              <h4 className="text-lg font-semibold text-white mb-4">Tingkat Kehadiran</h4>
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-300">Kehadiran</span>
                  <span className="text-green-400">{((monthlyStats.presentDays / monthlyStats.totalDays) * 100).toFixed(1)}%</span>
                </div>
                <div className="w-full bg-gray-700/50 rounded-full h-2">
                  <div 
                    className="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full"
                    style={{ width: `${(monthlyStats.presentDays / monthlyStats.totalDays) * 100}%` }}
                  ></div>
                </div>
              </div>
            </div>

            {/* Achievement Rings */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
              <h4 className="text-lg font-semibold text-white mb-6 text-center">Achievement Rings</h4>
              
              <div className="flex justify-center space-x-8">
                {/* Days Ring */}
                <div className="relative">
                  <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="url(#gradient1)"
                      strokeWidth="2"
                      strokeDasharray="85, 100"
                      className="animate-pulse"
                    />
                    <defs>
                      <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#10B981" />
                        <stop offset="100%" stopColor="#34D399" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-xl font-bold text-white">28</div>
                      <div className="text-xs text-green-300">Days</div>
                    </div>
                  </div>
                </div>

                {/* Hours Ring */}
                <div className="relative">
                  <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="url(#gradient2)"
                      strokeWidth="2"
                      strokeDasharray="72, 100"
                      className="animate-pulse"
                    />
                    <defs>
                      <linearGradient id="gradient2" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#3B82F6" />
                        <stop offset="100%" stopColor="#60A5FA" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-xl font-bold text-white">7.2</div>
                      <div className="text-xs text-blue-300">Hours</div>
                    </div>
                  </div>
                </div>

                {/* Performance Ring */}
                <div className="relative">
                  <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="url(#gradient3)"
                      strokeWidth="2"
                      strokeDasharray="96, 100"
                      className="animate-pulse"
                    />
                    <defs>
                      <linearGradient id="gradient3" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#8B5CF6" />
                        <stop offset="100%" stopColor="#A78BFA" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-xl font-bold text-white">96%</div>
                      <div className="text-xs text-purple-300">Score</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      case 'leave':
        return (
          <div className="space-y-6">
            <div className="flex justify-between items-center mb-6">
              <h3 className="text-xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Manajemen Cuti
              </h3>
              <button
                onClick={() => setShowLeaveForm(true)}
                className="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 px-4 py-2 rounded-xl flex items-center space-x-2 transition-all"
              >
                <Plus className="w-4 h-4" />
                <span className="text-sm font-medium">Ajukan Cuti</span>
              </button>
            </div>

            {/* Leave Balance Card */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
              <h4 className="text-lg font-semibold text-white mb-4">Saldo Cuti</h4>
              <div className="grid grid-cols-3 gap-4">
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-400">12</div>
                  <div className="text-sm text-gray-300">Cuti Tahunan</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-green-400">5</div>
                  <div className="text-sm text-gray-300">Cuti Sakit</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-purple-400">3</div>
                  <div className="text-sm text-gray-300">Cuti Khusus</div>
                </div>
              </div>
            </div>

            {/* Recent Leave Requests */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
              <h4 className="text-lg font-semibold text-white mb-4">Pengajuan Terakhir</h4>
              <div className="space-y-3">
                {[
                  { date: '15-20 Jul 2025', type: 'Cuti Tahunan', status: 'Approved', days: 4 },
                  { date: '28 Jun 2025', type: 'Cuti Sakit', status: 'Approved', days: 1 },
                  { date: '10-11 Jun 2025', type: 'Cuti Khusus', status: 'Pending', days: 2 }
                ].map((leave, index) => (
                  <div key={index} className="flex justify-between items-center p-3 bg-black/20 rounded-xl">
                    <div>
                      <div className="text-white font-medium">{leave.type}</div>
                      <div className="text-sm text-gray-300">{leave.date} • {leave.days} hari</div>
                    </div>
                    <div className={`px-3 py-1 rounded-full text-xs font-medium ${
                      leave.status === 'Approved' ? 'bg-green-500/20 text-green-400' :
                      leave.status === 'Pending' ? 'bg-yellow-500/20 text-yellow-400' :
                      'bg-red-500/20 text-red-400'
                    }`}>
                      {leave.status}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white">
      {/* Mobile-first responsive container with adaptive width and custom desktop enhancements */}
      <div className="dokter-attendance-container w-full max-w-sm mx-auto min-h-screen relative overflow-hidden
                      md:max-w-2xl lg:max-w-4xl
                      md:px-4 lg:px-6">
        
        {/* Animated Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-10 w-32 h-32 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
          <div className="absolute top-40 right-5 w-24 h-24 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
          <div className="absolute bottom-40 left-5 w-40 h-40 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
        </div>

        {/* Responsive Status Bar */}
        <div className="flex justify-between items-center px-4 pt-3 pb-2 text-white text-sm font-semibold relative z-10
                        md:px-6 lg:px-8">
          {/* Hide status bar on larger screens */}
          <span className="md:hidden"></span>
          <div className="flex items-center space-x-1 md:hidden">
            <div className="w-6 h-3 border border-white rounded-sm relative">
              <div className="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
            </div>
          </div>
        </div>

        {/* Responsive Hero Section */}
        <div className="px-4 pt-6 pb-4 relative z-10
                        md:px-6 md:pt-8 md:pb-6
                        lg:px-8 lg:pt-10 lg:pb-8">
          <div className="text-center mb-6 md:mb-8">
            {/* Responsive typography using clamp */}
            <h1 className="font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent"
                style={{ fontSize: 'clamp(1.875rem, 5vw, 3.5rem)' }}>
              Smart Attendance
            </h1>
            <p className="text-purple-200 text-base md:text-lg lg:text-xl">
              {userData?.name || 'Doctor'}
            </p>
          </div>
        </div>

        {/* Responsive Tab Navigation */}
        <div className="px-4 mb-4 relative z-10
                        md:px-6 md:mb-6
                        lg:px-8 lg:mb-8">
          <div className="dokter-gaming-nav dokter-tab-navigation flex bg-gradient-to-r from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-sm rounded-lg border border-cyan-400/20 p-0.5 shadow-lg shadow-cyan-500/10
                          md:rounded-xl md:p-1
                          lg:rounded-2xl lg:p-1.5">
            
            {/* Active Tab Indicator */}
            <div 
              className={`absolute top-0.5 bottom-0.5 bg-gradient-to-r from-cyan-500/30 via-purple-500/30 to-pink-500/30 backdrop-blur-xl rounded-md border border-cyan-400/40 transition-all duration-300 ease-out ${
                activeTab === 'checkin' ? 'left-0.5 w-[calc(25%-2px)]' :
                activeTab === 'history' ? 'left-[calc(25%+1px)] w-[calc(25%-2px)]' :
                activeTab === 'stats' ? 'left-[calc(50%+1px)] w-[calc(25%-2px)]' :
                'left-[calc(75%+1px)] w-[calc(25%-2px)]'
              }`}
            >
              {/* Glowing edge */}
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-400/20 to-purple-400/20 rounded-md animate-pulse"></div>
            </div>

            {tabItems.map((item, index) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;
              
              return (
                <button
                  key={item.id}
                  onClick={() => setActiveTab(item.id)}
                  className={`dokter-gaming-nav-item dokter-tab-item relative z-10 flex-1 flex items-center justify-center space-x-1.5 px-2 py-1.5 rounded-md transition-all duration-200 group ${
                    isActive 
                      ? 'text-cyan-300 scale-105' 
                      : 'text-gray-400 hover:text-cyan-400 hover:scale-102'
                  }`}
                >
                  {/* Icon with gaming glow */}
                  <div className="relative">
                    <Icon className={`w-3.5 h-3.5 flex-shrink-0 transition-all duration-200 ${
                      isActive ? 'filter drop-shadow-sm drop-shadow-cyan-400/50' : 'group-hover:drop-shadow-sm group-hover:drop-shadow-cyan-400/30'
                    }`} />
                    
                    {/* Gaming particles */}
                    {isActive && (
                      <>
                        <div className="absolute -top-0.5 -right-0.5 w-1 h-1 bg-cyan-400 rounded-full animate-ping opacity-60"></div>
                        <div className="absolute -bottom-0.5 -left-0.5 w-0.5 h-0.5 bg-purple-400 rounded-full animate-ping delay-200 opacity-60"></div>
                      </>
                    )}
                  </div>
                  
                  <span className={`text-xs font-medium truncate transition-all duration-200 ${
                    isActive ? 'text-cyan-300 font-semibold' : 'group-hover:text-cyan-400'
                  }`}>
                    {item.label}
                  </span>
                  
                  {/* Level indicator for active tab */}
                  {isActive && (
                    <div className="absolute -top-1 -right-1 w-2 h-2 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full border border-slate-800 text-[6px] font-bold text-black flex items-center justify-center">
                      •
                    </div>
                  )}
                </button>
              );
            })}
          </div>
          
          {/* Gaming ambient glow */}
          <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/5 via-purple-500/5 to-pink-500/5 rounded-lg blur-xl -z-10"></div>
        </div>

        {/* Responsive Tab Content */}
        <div className="px-4 pb-20 relative z-10
                        md:px-6 md:pb-24
                        lg:px-8 lg:pb-28">
          {renderTabContent()}
        </div>

        {/* Responsive Leave Form Modal */}
        {showLeaveForm && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4
                          md:p-6 lg:p-8">
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 w-full max-w-sm
                            md:rounded-3xl md:p-6 md:max-w-md
                            lg:p-8 lg:max-w-lg">
              <h3 className="text-lg md:text-xl lg:text-2xl font-bold text-white mb-4 text-center
                          md:mb-6 lg:mb-8">Pengajuan Cuti</h3>
              
              <div className="space-y-3 md:space-y-4 lg:space-y-6">
                <div>
                  <label className="block text-sm md:text-base font-medium text-gray-300 mb-2">Jenis Cuti</label>
                  <select 
                    value={leaveForm.type}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, type: e.target.value }))}
                    className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400
                               md:text-base lg:text-lg
                               min-h-[44px] touch-manipulation"
                  >
                    <option value="annual" className="bg-gray-800">Cuti Tahunan</option>
                    <option value="sick" className="bg-gray-800">Cuti Sakit</option>
                    <option value="special" className="bg-gray-800">Cuti Khusus</option>
                  </select>
                </div>

                <div className="grid grid-cols-1 gap-3
                                md:grid-cols-2 md:gap-4
                                lg:gap-6">
                  <div>
                    <label className="block text-sm md:text-base font-medium text-gray-300 mb-2">Tanggal Mulai</label>
                    <input 
                      type="date"
                      value={leaveForm.startDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, startDate: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400
                                 md:text-base lg:text-lg
                                 min-h-[44px] touch-manipulation"
                    />
                  </div>
                  <div>
                    <label className="block text-sm md:text-base font-medium text-gray-300 mb-2">Tanggal Selesai</label>
                    <input 
                      type="date"
                      value={leaveForm.endDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, endDate: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400
                                 md:text-base lg:text-lg
                                 min-h-[44px] touch-manipulation"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm md:text-base font-medium text-gray-300 mb-2">Alasan</label>
                  <textarea 
                    value={leaveForm.reason}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, reason: e.target.value }))}
                    className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400 h-20 resize-none
                               md:text-base md:h-24
                               lg:text-lg lg:h-32
                               touch-manipulation"
                    placeholder="Jelaskan alasan pengajuan cuti..."
                  ></textarea>
                </div>
              </div>

              <div className="flex flex-col space-y-3 mt-4
                              md:flex-row md:space-y-0 md:space-x-3 md:mt-6
                              lg:mt-8">
                <button
                  onClick={() => setShowLeaveForm(false)}
                  className="flex-1 bg-gray-500/20 hover:bg-gray-500/30 px-4 py-3 rounded-xl text-white transition-colors
                             min-h-[44px] touch-manipulation
                             md:text-base lg:text-lg"
                >
                  Batal
                </button>
                <button
                  onClick={handleLeaveSubmit}
                  className="flex-1 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 px-4 py-3 rounded-xl text-white transition-colors flex items-center justify-center space-x-2
                             min-h-[44px] touch-manipulation
                             md:text-base lg:text-lg"
                >
                  <Send className="w-4 h-4" />
                  <span>Kirim</span>
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CreativeAttendanceDashboard;