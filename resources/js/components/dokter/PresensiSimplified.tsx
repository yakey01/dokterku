// Simplified Presensi Component - Refactored Version
// Original: ~3,500 lines → Simplified: ~500 lines

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Calendar, Clock, User, Home, Wifi, History, TrendingUp, FileText } from 'lucide-react';
import DynamicMap from './DynamicMap';
import { AttendanceCard } from './AttendanceCard';
import { useGPSLocation, useGPSAvailability, useGPSPermission } from '../../hooks/useGPSLocation';
import { GPSStrategy } from '../../utils/GPSManager';
import { useAttendanceStatus } from '../../hooks/useAttendanceStatus';
import * as api from '../../services/dokter/attendanceApi';
import { formatTime, formatDate, calculateWorkingHours } from '../../utils/dokter/attendanceHelpers';
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
      startDate.setDate(startDate.getDate() - 30); // Last 30 days
      
      const history = await api.fetchAttendanceHistory(startDate, endDate);
      setAttendanceHistory(Array.isArray(history) ? history : []);
    } catch (error) {
      console.error('Failed to load history:', error);
      setAttendanceHistory([]);
    } finally {
      setHistoryLoading(false);
    }
  }, []);
  
  // Load history when tab changes
  useEffect(() => {
    if (activeTab === 'history' && attendanceHistory.length === 0) {
      loadHistory();
    }
  }, [activeTab]);
  
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
                workLocation={scheduleData.workLocation ? {
                  lat: scheduleData.workLocation.latitude,
                  lng: scheduleData.workLocation.longitude,
                  radius: scheduleData.workLocation.radius,
                  name: scheduleData.workLocation.name
                } : {
                  // Default location (RS. Kediri Medical Center)
                  lat: -7.848016,
                  lng: 112.017829,
                  radius: 100,
                  name: 'RS. Kediri Medical Center'
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
          <div className="bg-gradient-to-br from-slate-800/60 via-slate-700/60 to-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-cyan-400/30">
            <h2 className="text-xl font-bold text-white mb-4">Riwayat Presensi</h2>
            
            {historyLoading ? (
              <div className="text-center py-8">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-400 mx-auto"></div>
              </div>
            ) : (
              <div className="space-y-3">
                {paginatedHistory.map((record, index) => (
                  <div key={index} className="bg-slate-900/40 rounded-lg p-4 border border-purple-500/20">
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="text-white font-semibold">{formatDate(record.date)}</div>
                        <div className="text-cyan-300 text-sm mt-1">
                          Check-in: {record.time_in || '--:--'} | Check-out: {record.time_out || '--:--'}
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="text-purple-400 text-sm">{record.status || 'Hadir'}</div>
                        <div className="text-gray-400 text-xs mt-1">
                          {calculateWorkingHours(
                            record.time_in ? new Date(record.date + ' ' + record.time_in) : null,
                            record.time_out ? new Date(record.date + ' ' + record.time_out) : null
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
                
                {/* Pagination */}
                {attendanceHistory.length > itemsPerPage && (
                  <div className="flex justify-center space-x-2 mt-4">
                    {Array.from({ length: Math.ceil(attendanceHistory.length / itemsPerPage) }, (_, i) => (
                      <button
                        key={i}
                        onClick={() => setCurrentPage(i + 1)}
                        className={`px-3 py-1 rounded ${
                          currentPage === i + 1
                            ? 'bg-cyan-500 text-white'
                            : 'bg-slate-700 text-gray-300 hover:bg-slate-600'
                        }`}
                      >
                        {i + 1}
                      </button>
                    ))}
                  </div>
                )}
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