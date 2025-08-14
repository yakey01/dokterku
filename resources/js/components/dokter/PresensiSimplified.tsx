// Simplified Presensi Component - Refactored Version
// Original: ~3,500 lines → Simplified: ~500 lines

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Calendar, Clock, User, Home, Wifi, History, TrendingUp, FileText, Shield, Trophy, Star, AlertCircle, Filter, ChevronLeft, ChevronRight } from 'lucide-react';
import DynamicMap from './DynamicMap';
import { AttendanceCard } from './AttendanceCard';
import { useGPSLocation, useGPSAvailability, useGPSPermission } from '../../hooks/useGPSLocation';
import { GPSStrategy } from '../../utils/GPSManager';
import { useAttendanceStatus } from '../../hooks/useAttendanceStatus';
import * as api from '../../services/dokter/attendanceApi';
import { formatTime, formatDate, calculateWorkingHours } from '../../utils/dokter/attendanceHelpers';
import { safeGet } from '../../utils/SafeObjectAccess';
import '../../../css/map-styles.css';

const PresensiSimplified: React.FC = () => {
  // Time and UI State
  const [currentTime, setCurrentTime] = useState(new Date());
  const [activeTab, setActiveTab] = useState<'checkin' | 'history' | 'stats' | 'leave'>('checkin');
  const [isMobile, setIsMobile] = useState(window.innerWidth < 640);
  
  // Attendance Management Hook
  const {
    isCheckedIn,
    attendanceData,
    todayRecords,
    userData,
    scheduleData,
    isOperationInProgress,
    serverOffsetRef,
    setIsCheckedIn,
    setAttendanceData,
    setIsOperationInProgress,
    setLastKnownState,
    loadAttendanceRecords,
    validateCurrentStatus
  } = useAttendanceStatus();
  
  // GPS Integration
  const gpsAvailability = useGPSAvailability();
  const gpsPermission = useGPSPermission();
  const {
    location: gpsLocation,
    error: gpsError,
    isLoading: gpsLoading,
    accuracy: gpsAccuracy,
    refreshLocation: refreshGPS,
    requestPermission: requestGPSPermission
  } = useGPSLocation({
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 30000,
    continuous: true,
    fallbackStrategies: [GPSStrategy.HIGH_ACCURACY, GPSStrategy.MEDIUM_ACCURACY],
    onError: (error) => console.error('🚨 GPS Error:', error),
    onPermissionDenied: () => console.warn('⚠️ GPS Permission Denied')
  });
  
  // History State
  const [attendanceHistory, setAttendanceHistory] = useState<any[]>([]);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(5);
  const [filterPeriod, setFilterPeriod] = useState('weekly');
  

  
  // Clock Update
  useEffect(() => {
    const timer = setInterval(() => {
      const now = new Date();
      if (serverOffsetRef.current) {
        now.setTime(now.getTime() + serverOffsetRef.current);
      }
      setCurrentTime(now);
    }, 1000);
    
    return () => clearInterval(timer);
  }, [serverOffsetRef]);
  
  // Responsive Detection
  useEffect(() => {
    const checkScreenSize = () => {
      setIsMobile(window.innerWidth < 640);
    };
    
    window.addEventListener('resize', checkScreenSize);
    return () => window.removeEventListener('resize', checkScreenSize);
  }, []);
  
  // Load Work Location
  useEffect(() => {
    const loadWorkLocation = async () => {
      try {
        const workLocation = await api.fetchWorkLocationStatus();
        if (workLocation) {
          // Properly update scheduleData state
          setScheduleData(prev => ({
            ...prev,
            workLocation: workLocation
          }));
        }
      } catch (error) {
        console.error('Failed to load work location:', error);
      }
    };
    
    loadWorkLocation();
  }, [setScheduleData]);
  
  // Handle Check-in
  const handleCheckIn = useCallback(async () => {
    if (!gpsLocation || isOperationInProgress) return;
    
    setIsOperationInProgress(true);
    setLastKnownState({
      isCheckedIn,
      checkInTime: attendanceData.checkInTime,
      checkOutTime: attendanceData.checkOutTime
    });
    
    try {
      // Get current shift ID
      const shiftId = scheduleData.currentShift?.id || 
                     scheduleData.currentShift?.jadwal_jaga_id;
      
      // Perform check-in
      const result = await api.performCheckIn({
        latitude: gpsLocation.latitude,
        longitude: gpsLocation.longitude,
        accuracy: gpsAccuracy || 10,
        jadwal_jaga_id: shiftId
      });
      
      // Update state optimistically
      setIsCheckedIn(true);
      setAttendanceData(prev => ({
        ...prev,
        checkInTime: new Date().toISOString(),
        checkOutTime: null
      }));
      
      // Reload attendance records
      await loadAttendanceRecords();
      await validateCurrentStatus();
      
      // Show success message
      alert('✅ Check-in berhasil!');
      
    } catch (error: any) {
      console.error('Check-in error:', error);
      alert(`❌ Check-in gagal: ${error.message}`);
      
      // Rollback on error
      setIsCheckedIn(false);
      setAttendanceData(prev => ({
        ...prev,
        checkInTime: null
      }));
    } finally {
      setIsOperationInProgress(false);
    }
  }, [gpsLocation, isOperationInProgress, scheduleData, gpsAccuracy]);
  
  // Handle Check-out
  const handleCheckOut = useCallback(async () => {
    if (!gpsLocation || isOperationInProgress) return;
    
    setIsOperationInProgress(true);
    
    try {
      // Get current shift ID
      const shiftId = scheduleData.currentShift?.id || 
                     scheduleData.currentShift?.jadwal_jaga_id;
      
      // Perform check-out
      const result = await api.performCheckOut({
        latitude: gpsLocation.latitude,
        longitude: gpsLocation.longitude,
        accuracy: gpsAccuracy || 10,
        jadwal_jaga_id: shiftId
      });
      
      // Update state
      setIsCheckedIn(false);
      setAttendanceData(prev => ({
        ...prev,
        checkOutTime: new Date().toISOString()
      }));
      
      // Reload attendance records
      await loadAttendanceRecords();
      await validateCurrentStatus();
      
      // Show success message
      alert('✅ Check-out berhasil!');
      
    } catch (error: any) {
      console.error('Check-out error:', error);
      alert(`❌ Check-out gagal: ${error.message}`);
    } finally {
      setIsOperationInProgress(false);
    }
  }, [gpsLocation, isOperationInProgress, scheduleData, gpsAccuracy]);
  
  // Load Attendance History
  const loadHistory = useCallback(async () => {
    setHistoryLoading(true);
    try {
      const endDate = new Date();
      const startDate = new Date();
      // ✅ ENHANCED: Request 90 days of history instead of just 30
      startDate.setDate(startDate.getDate() - 90);
      
      console.log('🔍 Requesting history from:', startDate.toISOString().split('T')[0], 'to:', endDate.toISOString().split('T')[0]);
      
      const historyData = await api.fetchAttendanceHistory(startDate, endDate);
      
      // 🔍 DEBUG: Log the received data
      console.log('🔍 Attendance History Data Received:', historyData);
      
      // CRITICAL FIX: Handle both history array and today_records
      let allRecords: any[] = [];
      
      if (Array.isArray(historyData)) {
        allRecords = [...historyData];
      } else if (historyData && typeof historyData === 'object') {
        // If it's an object with history and today_records properties
        const history = historyData.history || [];
        const todayRecords = historyData.today_records || [];
        
        allRecords = [...history];
        
        // Add today's records if they're not already in history
        const todayDate = new Date().toISOString().split('T')[0];
        todayRecords.forEach((todayRecord: any) => {
          const existsInHistory = history.some((h: any) => h.id === todayRecord.id);
          if (!existsInHistory && todayRecord.time_in) {
            // Convert today_record format to history format
            const historyRecord = {
              ...todayRecord,
              date: todayRecord.date || todayDate
            };
            allRecords.push(historyRecord);
          }
        });
      }
      
      console.log('🔍 Raw records count:', allRecords.length);
      
      // ✅ ENHANCED: Process records to ensure shift_info is properly structured
      const processedRecords = allRecords.map((record: any) => {
        // Ensure date is properly formatted
        if (record.date) {
          record.date = typeof record.date === 'string' ? record.date : new Date(record.date).toISOString().split('T')[0];
        }
        
        // ✅ ENHANCED: Better shift_info handling
        if (record.shift_info) {
          // Ensure all required fields are present
          record.shift_info = {
            shift_name: record.shift_info.shift_name || 'Shift Jaga',
            shift_start: record.shift_info.shift_start || record.shift_info.jam_masuk || '--:--',
            shift_end: record.shift_info.shift_end || record.shift_info.jam_pulang || '--:--',
            shift_duration: record.shift_info.shift_duration || '8j 0m',
            jam_jaga: record.shift_info.jam_jaga || 
                      `${record.shift_info.shift_start || '08:00'} - ${record.shift_info.shift_end || '16:00'}`,
            unit_kerja: record.shift_info.unit_kerja || 'Dokter Jaga',
            peran: record.shift_info.peran || 'Dokter',
            status_jaga: record.shift_info.status_jaga || 'Aktif',
            is_custom_schedule: record.shift_info.is_custom_schedule || false,
            custom_reason: record.shift_info.custom_reason || null,
            is_time_mismatch: record.shift_info.is_time_mismatch || false,
            actual_attendance_time: record.shift_info.actual_attendance_time || record.time_in || '--:--'
          };
        } else {
          // ✅ ENHANCED: Create comprehensive fallback shift_info
          record.shift_info = {
            shift_name: record.mission_info?.mission_title || 'Shift Default',
            shift_start: record.shift_start || record.mission_info?.scheduled_time?.split(' - ')[0] || '08:00',
            shift_end: record.shift_end || record.mission_info?.scheduled_time?.split(' - ')[1] || '16:00',
            shift_duration: record.shift_duration || record.mission_info?.shift_duration || '8j 0m',
            jam_jaga: record.shift_start && record.shift_end 
              ? `${record.shift_start} - ${record.shift_end}`
              : record.mission_info?.scheduled_time || '08:00 - 16:00',
            unit_kerja: record.mission_info?.mission_subtitle || 'Dokter Jaga',
            peran: 'Dokter',
            status_jaga: 'Aktif',
            is_custom_schedule: false,
            custom_reason: null,
            is_time_mismatch: false,
            actual_attendance_time: record.time_in || '--:--'
          };
        }
        
        return record;
      });
      
      console.log('🔍 Total records to display:', processedRecords.length);
      console.log('🔍 Sample Record:', processedRecords[0]);
      console.log('🔍 Shift Info in Sample:', safeGet(processedRecords[0], 'shift_info'));
      
      setAttendanceHistory(processedRecords);
    } catch (error) {
      console.error('Failed to load history:', error);
      setAttendanceHistory([]);
    } finally {
      setHistoryLoading(false);
    }
  }, []);
  
  // Load history when tab changes
  useEffect(() => {
    if (activeTab === 'history') {
      // Always load history when history tab is opened, even if data exists
      loadHistory();
    }
  }, [activeTab, loadHistory]);
  
  // Paginated history
  const paginatedHistory = useMemo(() => {
    const startIndex = (currentPage - 1) * itemsPerPage;
    return attendanceHistory.slice(startIndex, startIndex + itemsPerPage);
  }, [attendanceHistory, currentPage, itemsPerPage]);
  
  // Tab content renderer
  const renderTabContent = () => {
    switch (activeTab) {
      case 'checkin':
        return (
          <div className="space-y-6">
            {/* Map Component - Always show with default or actual location */}
            <div className="h-48 sm:h-56 md:h-64 rounded-xl overflow-hidden border border-cyan-400/30">
              <DynamicMap
                userLocation={gpsLocation ? {
                  lat: gpsLocation.latitude,
                  lng: gpsLocation.longitude,
                  accuracy: gpsAccuracy || 10
                } : null}
                hospitalLocation={scheduleData.workLocation ? {
                  lat: scheduleData.workLocation.latitude,
                  lng: scheduleData.workLocation.longitude,
                  radius: scheduleData.workLocation.radius,
                  name: scheduleData.workLocation.name,
                  address: scheduleData.workLocation.name
                } : {
                  // Default location (RS. Kediri Medical Center)
                  lat: -7.848016,
                  lng: 112.017829,
                  radius: 100,
                  name: 'RS. Kediri Medical Center',
                  address: 'RS. Kediri Medical Center'
                }}
              />
            </div>
            
            {/* Attendance Card */}
            <AttendanceCard
              isCheckedIn={isCheckedIn}
              attendanceData={attendanceData}
              scheduleData={scheduleData}
              currentTime={currentTime}
              gpsStatus={{
                isLoading: gpsLoading,
                error: gpsError,
                accuracy: gpsAccuracy
              }}
              onCheckIn={handleCheckIn}
              onCheckOut={handleCheckOut}
              onRefreshGPS={refreshGPS}
              isOperationInProgress={isOperationInProgress}
            />
          </div>
        );
        
      case 'history':
        return (
          <div className="space-y-6">
            {/* Header dengan Filter */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
              <h3 className="text-lg sm:text-xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Riwayat Presensi
              </h3>
              <div className="flex items-center space-x-2">
                <Filter className="w-3 h-3 sm:w-4 sm:h-4 text-purple-300" />
                <select 
                  value={filterPeriod}
                  onChange={(e) => {
                    setFilterPeriod(e.target.value);
                    setCurrentPage(1);
                  }}
                  className="bg-white/10 backdrop-blur-xl border border-white/20 rounded-lg sm:rounded-xl px-2 sm:px-3 py-1 text-xs sm:text-sm text-white focus:outline-none focus:border-purple-400"
                >
                  <option value="weekly" className="bg-gray-800">7 Hari</option>
                  <option value="monthly" className="bg-gray-800">30 Hari</option>
                </select>
              </div>
            </div>

            {/* Gaming Stats Dashboard */}
            {attendanceHistory.length > 0 && (
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                {/* Total Missions */}
                <div className="bg-gradient-to-br from-blue-600/30 to-blue-800/30 backdrop-blur-xl rounded-xl p-3 border border-blue-400/20">
                  <div className="flex items-center justify-between mb-1">
                    <Clock className="w-5 h-5 text-blue-400" />
                    <span className="text-xl font-bold text-white">{attendanceHistory.length}</span>
                  </div>
                  <p className="text-blue-300 text-xs">Total Missions</p>
                </div>
                
                {/* Perfect Rate */}
                <div className="bg-gradient-to-br from-green-600/30 to-green-800/30 backdrop-blur-xl rounded-xl p-3 border border-green-400/20">
                  <div className="flex items-center justify-between mb-1">
                    <Trophy className="w-5 h-5 text-green-400" />
                    <span className="text-xl font-bold text-white">
                      {Math.round((attendanceHistory.filter(r => r.status === 'perfect' || r.status === 'good').length / attendanceHistory.length) * 100)}%
                    </span>
                  </div>
                  <p className="text-green-300 text-xs">Success Rate</p>
                </div>
                
                {/* Total XP */}
                <div className="bg-gradient-to-br from-amber-600/30 to-amber-800/30 backdrop-blur-xl rounded-xl p-3 border border-amber-400/20">
                  <div className="flex items-center justify-between mb-1">
                    <Star className="w-5 h-5 text-amber-400" />
                    <span className="text-xl font-bold text-white">
                      {attendanceHistory.reduce((sum, r) => sum + (r.points_earned || 0), 0)}
                    </span>
                  </div>
                  <p className="text-amber-300 text-xs">Total XP</p>
                </div>
                
                {/* Streak */}
                <div className="bg-gradient-to-br from-purple-600/30 to-purple-800/30 backdrop-blur-xl rounded-xl p-3 border border-purple-400/20">
                  <div className="flex items-center justify-between mb-1">
                    <TrendingUp className="w-5 h-5 text-purple-400" />
                    <span className="text-xl font-bold text-white">
                      {attendanceHistory.filter(r => r.status === 'perfect').length}
                    </span>
                  </div>
                  <p className="text-purple-300 text-xs">Perfect Streak</p>
                </div>
              </div>
            )}
            
            {historyLoading ? (
              <div className="text-center py-8">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-400 mx-auto"></div>
                <p className="text-gray-400 mt-2">Memuat riwayat presensi...</p>
              </div>
            ) : attendanceHistory.length === 0 ? (
              <div className="text-center py-8">
                <div className="text-gray-400 text-lg mb-2">📋</div>
                <p className="text-gray-400">Belum ada riwayat presensi</p>
                <p className="text-gray-500 text-sm mt-1">Riwayat akan muncul setelah Anda melakukan check-in/check-out</p>
              </div>
            ) : (
              <div className="space-y-4">
                {paginatedHistory.map((record, index) => {
                  // Format data sesuai script user
                  // Format: DD-MM-YY (contoh: 13-08-25)
                  const dateObj = new Date(record.date);
                  const day = String(dateObj.getDate()).padStart(2, '0');
                  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                  const year = String(dateObj.getFullYear()).slice(-2);
                  const formattedDate = `${day}-${month}-${year}`;
                  
                  const shiftTime = record.mission_info?.scheduled_time || '08:00-16:00';
                  
                  const checkInTime = record.check_in_time ? 
                    new Date(record.check_in_time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'}) :
                    record.actual_check_in || record.time_in || '--:--';
                    
                  const checkOutTime = record.check_out_time ? 
                    new Date(record.check_out_time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'}) :
                    record.actual_check_out || record.time_out || '--:--';
                  
                  const duration = record.working_duration || '8h 0m';
                  
                  const status = record.status === 'perfect' || record.status === 'good' || record.status_legacy === 'present' ? 'Hadir' : 
                              record.status_legacy === 'late' ? 'Terlambat' : 'Tidak Hadir';
                  
                  // ✅ SOPHISTICATED: Use calculated shortage from backend (multiple field support)
                  const shortageMinutes = record.shortage_minutes || record.shortfall_minutes || 0;
                  
                  return (
                    <div key={index} className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 sm:p-5 border border-white/20 relative">
                      {/* Gaming accent line di pojok kiri atas */}
                      <div className="absolute top-0 left-0 w-12 sm:w-16 h-1 bg-gradient-to-r from-cyan-500/60 to-purple-500/60 rounded-tr-2xl"></div>
                      
                      {/* Emoji badge di pojok kanan atas */}
                      <div className="absolute -top-1 sm:-top-2 -right-1 sm:-right-2 w-6 h-6 sm:w-8 sm:h-8 bg-black/40 backdrop-blur-md rounded-full flex items-center justify-center border-2 border-white/30 shadow-lg">
                        <span className="text-sm sm:text-lg">
                          {shortageMinutes === 0 ? '👍' : '👎'}
                        </span>
                      </div>
                      
                      {/* Header dengan tanggal, jam jaga dan status */}
                      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4 mb-4">
                        <div className="flex items-center space-x-2 sm:space-x-3 flex-wrap">
                          <div className="text-white font-bold text-base sm:text-lg">{formattedDate}</div>
                          <span className="text-xs px-2 py-1 rounded-lg font-medium bg-orange-500/20 text-orange-400 whitespace-nowrap">
                            {shiftTime}
                          </span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <span className={`text-xs sm:text-sm px-2 sm:px-3 py-1 rounded-lg font-medium ${
                            status === 'Hadir' ? 'bg-green-500/20 text-green-400' :
                            status === 'Terlambat' ? 'bg-yellow-500/20 text-yellow-400' :
                            'bg-red-500/20 text-red-400'
                          }`}>
                            {status}
                          </span>
                        </div>
                      </div>

                      {/* Detail informasi dalam grid responsive */}
                      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-xs sm:text-sm">
                        <div className="text-center">
                          <span className="text-gray-400 block mb-1">Masuk:</span>
                          <span className="text-white font-semibold text-sm sm:text-base">{checkInTime}</span>
                        </div>
                        <div className="text-center">
                          <span className="text-gray-400 block mb-1">Keluar:</span>
                          <span className="text-white font-semibold text-sm sm:text-base">{checkOutTime}</span>
                        </div>
                        <div className="text-center">
                          <span className="text-gray-400 block mb-1">Durasi:</span>
                          <span className="text-white font-semibold text-sm sm:text-base">{duration}</span>
                        </div>
                        <div className="text-center">
                          <span className="text-gray-400 block mb-1">Kekurangan:</span>
                          <span className={`font-semibold text-xs sm:text-sm ${
                            shortageMinutes === 0 ? 'text-green-400' : 'text-red-400'
                          }`}>
                            {shortageMinutes} menit
                          </span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        );
        
      case 'stats':
        return (
          <div className="bg-gradient-to-br from-slate-800/60 via-slate-700/60 to-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-cyan-400/30">
            <h2 className="text-xl font-bold text-white mb-4">Statistik Bulanan</h2>
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-slate-900/40 rounded-lg p-4 border border-green-500/20">
                <div className="text-green-400 text-sm">Hari Hadir</div>
                <div className="text-white text-2xl font-bold">20</div>
              </div>
              <div className="bg-slate-900/40 rounded-lg p-4 border border-red-500/20">
                <div className="text-red-400 text-sm">Terlambat</div>
                <div className="text-white text-2xl font-bold">2</div>
              </div>
              <div className="bg-slate-900/40 rounded-lg p-4 border border-purple-500/20">
                <div className="text-purple-400 text-sm">Lembur (Jam)</div>
                <div className="text-white text-2xl font-bold">15.5</div>
              </div>
              <div className="bg-slate-900/40 rounded-lg p-4 border border-cyan-500/20">
                <div className="text-cyan-400 text-sm">Sisa Cuti</div>
                <div className="text-white text-2xl font-bold">12</div>
              </div>
            </div>
          </div>
        );
        
      case 'leave':
        return (
          <div className="bg-gradient-to-br from-slate-800/60 via-slate-700/60 to-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-cyan-400/30">
            <h2 className="text-xl font-bold text-white mb-4">Pengajuan Cuti</h2>
            <p className="text-gray-400">Form pengajuan cuti akan ditambahkan</p>
          </div>
        );
    }
  };
  
  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white">
      <div className="w-full max-w-full sm:max-w-sm md:max-w-2xl lg:max-w-4xl xl:max-w-6xl mx-auto min-h-screen relative overflow-hidden">
        
        {/* Animated Background */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-10 w-32 h-32 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
          <div className="absolute top-40 right-5 w-24 h-24 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
          <div className="absolute bottom-40 left-5 w-40 h-40 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
        </div>
        
        {/* Status Bar */}
        {isMobile && (
          <div className="flex justify-between items-center px-4 pt-3 pb-2 text-white text-sm font-semibold relative z-10">
            <span>{formatTime(currentTime)}</span>
            <div className="flex items-center space-x-1">
              <Wifi className="w-4 h-4" />
              <div className="w-6 h-3 border border-white rounded-sm relative">
                <div className="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
              </div>
            </div>
          </div>
        )}
        
        {/* Header */}
        <div className="px-4 sm:px-6 md:px-8 pt-4 sm:pt-6 pb-4 relative z-10">
          <div className="text-center mb-6">
            <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
              Smart Attendance
            </h1>
            <p className="text-sm sm:text-base md:text-lg text-purple-200">
              {userData?.name || 'Loading...'}
            </p>
          </div>
        </div>
        
        {/* Tab Navigation */}
        <div className="px-4 sm:px-6 md:px-8 mb-4 relative z-10">
          <div className="flex bg-gradient-to-r from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-sm rounded-lg border border-cyan-400/20 p-1">
            {(['checkin', 'history', 'stats', 'leave'] as const).map((tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`flex-1 py-2 px-3 rounded-md transition-all duration-300 flex items-center justify-center space-x-2 ${
                  activeTab === tab
                    ? 'bg-gradient-to-r from-cyan-500/30 to-purple-500/30 text-white'
                    : 'text-gray-400 hover:text-white'
                }`}
              >
                {tab === 'checkin' && <Clock className="w-4 h-4" />}
                {tab === 'history' && <History className="w-4 h-4" />}
                {tab === 'stats' && <TrendingUp className="w-4 h-4" />}
                {tab === 'leave' && <FileText className="w-4 h-4" />}
                <span className="hidden sm:block text-sm">
                  {tab === 'checkin' && 'Check-in'}
                  {tab === 'history' && 'Riwayat'}
                  {tab === 'stats' && 'Statistik'}
                  {tab === 'leave' && 'Cuti'}
                </span>
              </button>
            ))}
          </div>
        </div>
        
        {/* Tab Content */}
        <div className="px-4 sm:px-6 md:px-8 pb-8 relative z-10">
          {renderTabContent()}
        </div>
      </div>
    </div>
  );
};

export default PresensiSimplified;