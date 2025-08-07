import React, { useState, useEffect } from 'react';
import { Calendar, Clock, DollarSign, User, Home, MapPin, CheckCircle, XCircle, Zap, Heart, Brain, Shield, Target, Award, TrendingUp, Sun, Moon, Coffee, Star, Crown, Hand, Camera, Wifi, WifiOff, AlertTriangle, History, UserCheck, FileText, Settings, Bell, ChevronLeft, ChevronRight, Filter, Plus, Send } from 'lucide-react';
import DynamicMap from './DynamicMap';
import '../../../css/map-styles.css';

const CreativeAttendanceDashboard = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [activeTab, setActiveTab] = useState('checkin');
  const [attendanceData, setAttendanceData] = useState({
    checkInTime: null as string | null,
    checkOutTime: null as string | null,
    workingHours: '00:00:00',
    overtimeHours: '00:00:00',
    breakTime: '00:00:00',
    location: 'RS. Kediri Medical Center'
  });
  
  // Hospital Location Data (Dynamic - dari API)
  const [hospitalLocation, setHospitalLocation] = useState({
    lat: -7.8481, // Default Kediri coordinates
    lng: 112.0178,
    name: 'Loading...',
    address: 'Loading...',
    radius: 50 // meters
  });
  
  // Load hospital data from API
  useEffect(() => {
    const loadHospitalData = async () => {
      try {
        // Try to get from API first
        const response = await fetch('/api/v2/hospital/location', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        
        if (response.ok) {
          const responseData = await response.json();
          const data = responseData.data || responseData;
          setHospitalLocation({
            lat: data.latitude || -7.8481,
            lng: data.longitude || 112.0178,
            name: data.name || 'RS. Kediri Medical Center',
            address: data.address || 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
            radius: data.radius || 50
          });
        } else {
          // Fallback to default data if API fails
          console.log('Using default hospital location data');
          setHospitalLocation({
            lat: -7.8481,
            lng: 112.0178,
            name: 'RS. Kediri Medical Center',
            address: 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
            radius: 50
          });
        }
      } catch (error) {
        console.error('Error loading hospital data:', error);
        // Fallback to default data
        setHospitalLocation({
          lat: -7.8481,
          lng: 112.0178,
          name: 'RS. Kediri Medical Center',
          address: 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
          radius: 50
        });
      }
    };
    
    loadHospitalData();
  }, []);
  
  // GPS and Location State
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number; accuracy?: number } | null>(null);
  const [gpsStatus, setGpsStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [distanceToHospital, setDistanceToHospital] = useState<number | null>(null);
  
  // Auto-detect GPS on component mount
  useEffect(() => {
    if (navigator.geolocation) {
      setGpsStatus('loading');
      
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude, accuracy } = position.coords;
          const location = { lat: latitude, lng: longitude, accuracy };
          setUserLocation(location);
          setGpsStatus('success');
          
          // Calculate initial distance
          const R = 6371e3;
          const φ1 = latitude * Math.PI / 180;
          const φ2 = hospitalLocation.lat * Math.PI / 180;
          const Δφ = (hospitalLocation.lat - latitude) * Math.PI / 180;
          const Δλ = (hospitalLocation.lng - longitude) * Math.PI / 180;

          const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
          const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

          const distance = R * c;
          setDistanceToHospital(distance);
        },
        (error) => {
          console.error('GPS Error:', error);
          setGpsStatus('error');
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 30000
        }
      );
    } else {
      setGpsStatus('error');
    }
  }, [hospitalLocation.lat, hospitalLocation.lng]);
  const [showLeaveForm, setShowLeaveForm] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [isTablet, setIsTablet] = useState(false);
  const [isDesktop, setIsDesktop] = useState(false);
  
  // Check screen size on mount and resize
  useEffect(() => {
    const checkScreenSize = () => {
      const width = window.innerWidth;
      setIsMobile(width < 768);
      setIsTablet(width >= 768 && width < 1024);
      setIsDesktop(width >= 1024);
    };
    
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    return () => window.removeEventListener('resize', checkScreenSize);
  }, []);
  
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

  const handleCheckIn = async () => {
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
          <div className="space-y-4 sm:space-y-6 md:space-y-8">
            {/* Current Date and Time - Responsive Typography */}
            <div className="text-center">
              <div className="text-base sm:text-lg md:text-xl text-purple-200 mb-2">{formatDate(currentTime)}</div>
              <div className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white">{formatTime(currentTime)}</div>
            </div>

            {/* Attendance Status Card - Responsive Padding and Layout */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-white/20">
              <div className="text-center mb-4">
                <div className={`inline-flex items-center space-x-2 sm:space-x-3 px-4 sm:px-6 py-2 sm:py-3 rounded-xl sm:rounded-2xl transition-all duration-500 ${
                  isCheckedIn 
                    ? 'bg-gradient-to-r from-green-500/30 to-emerald-500/30 border border-green-400/50' 
                    : 'bg-gradient-to-r from-gray-500/30 to-purple-500/30 border border-purple-400/50'
                }`}>
                  <div className={`w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full ${isCheckedIn ? 'bg-green-400 animate-pulse' : 'bg-purple-400'}`}></div>
                  <span className="text-sm sm:text-base md:text-lg text-white font-semibold">
                    {isCheckedIn ? '🚀 Sedang Bekerja' : '😴 Belum Check-in'}
                  </span>
                </div>
              </div>

              {/* Working Hours Display - Responsive Grid */}
              {isCheckedIn && (
                <div className="grid grid-cols-3 gap-2 sm:gap-4 md:gap-6 mt-4 sm:mt-6">
                  <div className="text-center">
                    <div className="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-green-400">{attendanceData.workingHours}</div>
                    <div className="text-xs sm:text-sm md:text-base text-gray-300">Jam Kerja</div>
                  </div>
                  <div className="text-center">
                    <div className="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-blue-400">{attendanceData.breakTime}</div>
                    <div className="text-xs sm:text-sm md:text-base text-gray-300">Istirahat</div>
                  </div>
                  <div className="text-center">
                    <div className="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-purple-400">{attendanceData.overtimeHours}</div>
                    <div className="text-xs sm:text-sm md:text-base text-gray-300">Overtime</div>
                  </div>
                </div>
              )}

              {/* Check-in/out times - Responsive Font Sizes */}
              {(attendanceData.checkInTime || attendanceData.checkOutTime) && (
                <div className="mt-4 p-3 sm:p-4 md:p-5 bg-black/20 rounded-xl sm:rounded-2xl">
                  <div className="grid grid-cols-2 gap-2 sm:gap-4 text-xs sm:text-sm md:text-base">
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

            {/* Check-in/out Buttons - Responsive Grid and Sizing */}
            <div className="grid grid-cols-2 gap-3 sm:gap-4 md:gap-6 lg:gap-8">
              <button 
                onClick={handleCheckIn}
                disabled={isCheckedIn}
                className={`relative group p-4 sm:p-5 md:p-6 lg:p-8 rounded-2xl sm:rounded-3xl transition-all duration-500 transform ${
                  isCheckedIn 
                    ? 'opacity-50 cursor-not-allowed' 
                    : 'hover:scale-105 active:scale-95'
                }`}
              >
                <div className="absolute inset-0 bg-gradient-to-br from-green-500/30 to-emerald-600/30 rounded-2xl sm:rounded-3xl"></div>
                <div className="absolute inset-0 bg-white/5 rounded-2xl sm:rounded-3xl border border-green-400/30"></div>
                {!isCheckedIn && (
                  <div className="absolute inset-0 bg-gradient-to-br from-green-400/0 to-green-400/20 rounded-2xl sm:rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                )}
                <div className="relative text-center">
                  <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 lg:w-20 lg:h-20 mx-auto mb-2 sm:mb-3 md:mb-4 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl sm:rounded-2xl flex items-center justify-center">
                    <Sun className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 lg:w-10 lg:h-10 text-white" />
                  </div>
                  <div className="text-white font-bold text-sm sm:text-base md:text-lg lg:text-xl">Check In</div>
                  <div className="text-green-300 text-xs sm:text-sm md:text-base">Mulai bekerja</div>
                </div>
              </button>
              
              <button 
                onClick={handleCheckOut}
                disabled={!isCheckedIn}
                className={`relative group p-4 sm:p-5 md:p-6 lg:p-8 rounded-2xl sm:rounded-3xl transition-all duration-500 transform ${
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
                  <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 lg:w-20 lg:h-20 mx-auto mb-2 sm:mb-3 md:mb-4 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl sm:rounded-2xl flex items-center justify-center">
                    <Moon className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 lg:w-10 lg:h-10 text-white" />
                  </div>
                  <div className="text-white font-bold text-sm sm:text-base md:text-lg lg:text-xl">Check Out</div>
                  <div className="text-purple-300 text-xs sm:text-sm md:text-base">Selesai bekerja</div>
                </div>
              </button>
            </div>

            {/* Dynamic Location Map with Leaflet.js & OSM */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl border border-white/20 overflow-hidden">
              <div className="p-4 border-b border-white/10">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-xl flex items-center justify-center">
                      <MapPin className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="font-semibold text-white">{hospitalLocation.name}</div>
                      <div className="text-green-300 text-sm">{hospitalLocation.address}</div>
                      {distanceToHospital && (
                        <div className="text-xs text-cyan-300">
                          📏 {distanceToHospital < 1000 ? `${Math.round(distanceToHospital)}m` : `${(distanceToHospital / 1000).toFixed(1)}km`} dari Anda
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <div className={`w-2 h-2 rounded-full ${
                      gpsStatus === 'success' ? 'bg-green-400 animate-pulse' :
                      gpsStatus === 'error' ? 'bg-red-400' :
                      gpsStatus === 'loading' ? 'bg-yellow-400 animate-pulse' :
                      'bg-gray-400'
                    }`}></div>
                    <span className="text-xs text-gray-300">
                      {gpsStatus === 'success' ? 'GPS Aktif' :
                       gpsStatus === 'error' ? 'GPS Error' :
                       gpsStatus === 'loading' ? 'Mendeteksi...' :
                       'GPS Off'}
                    </span>
                  </div>
                </div>
              </div>
              
              <div className="h-64 sm:h-80">
                <DynamicMap
                  hospitalLocation={hospitalLocation}
                  userLocation={userLocation}
                  onLocationUpdate={(location) => {
                    // Only update if we have valid coordinates (not loading state)
                    if (location.lat !== 0 && location.lng !== 0) {
                      setUserLocation(location);
                      setGpsStatus('success');
                      
                      // Calculate distance
                      const R = 6371e3; // Earth's radius in meters
                      const φ1 = location.lat * Math.PI / 180;
                      const φ2 = hospitalLocation.lat * Math.PI / 180;
                      const Δφ = (hospitalLocation.lat - location.lat) * Math.PI / 180;
                      const Δλ = (hospitalLocation.lng - location.lng) * Math.PI / 180;

                      const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                                Math.cos(φ1) * Math.cos(φ2) *
                                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                      const distance = R * c;
                      setDistanceToHospital(distance);
                    } else {
                      // Loading state
                      setGpsStatus('loading');
                    }
                  }}
                  showUserLocation={true}
                  className="h-full w-full"
                />
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
          <div className="space-y-4">
            {/* Header with Filter - Responsive Layout */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
              <h3 className="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Riwayat Presensi
              </h3>
              <div className="flex items-center space-x-2">
                <Filter className="w-3 h-3 sm:w-4 sm:h-4 text-purple-300" />
                <select 
                  value={filterPeriod}
                  onChange={(e) => handleFilterChange(e.target.value)}
                  className="bg-white/10 backdrop-blur-xl border border-white/20 rounded-lg sm:rounded-xl px-2 sm:px-3 py-1 text-xs sm:text-sm text-white focus:outline-none focus:border-purple-400"
                >
                  <option value="weekly" className="bg-gray-800">7 Hari</option>
                  <option value="monthly" className="bg-gray-800">30 Hari</option>
                </select>
              </div>
            </div>

            {/* History Cards - Responsive Spacing and Typography */}
            {currentData.map((record, index) => (
              <div key={index} className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="flex justify-between items-center mb-2">
                  <span className="text-sm sm:text-base md:text-lg text-white font-semibold">{record.date}</span>
                  <span className={`text-xs sm:text-sm font-medium ${getStatusColor(record.status)}`}>
                    {record.status}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-1 sm:gap-2 text-xs sm:text-sm md:text-base">
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
          <div className="space-y-4 sm:space-y-6">
            <h3 className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 text-center bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
              Statistik Bulanan
            </h3>
            
            {/* Stats Grid - Responsive Columns */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-green-400">{monthlyStats.presentDays}</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Hari Hadir</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-yellow-400">{monthlyStats.lateDays}</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Hari Terlambat</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-blue-400">{monthlyStats.overtimeHours}h</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Overtime</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-purple-400">{monthlyStats.leaveBalance}</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Sisa Cuti</div>
                </div>
              </div>
            </div>

            {/* Attendance Rate - Responsive Padding */}
            <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 border border-white/20">
              <h4 className="text-base sm:text-lg md:text-xl font-semibold text-white mb-3 sm:mb-4">Tingkat Kehadiran</h4>
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
          <div className="space-y-4 sm:space-y-6">
            {/* Header with Add Button - Responsive Layout */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
              <h3 className="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Manajemen Cuti
              </h3>
              <button
                onClick={() => setShowLeaveForm(true)}
                className="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg sm:rounded-xl flex items-center space-x-1.5 sm:space-x-2 transition-all"
              >
                <Plus className="w-3 h-3 sm:w-4 sm:h-4" />
                <span className="text-xs sm:text-sm font-medium">Ajukan Cuti</span>
              </button>
            </div>

            {/* Leave Balance Card - Responsive Grid and Typography */}
            <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 border border-white/20">
              <h4 className="text-base sm:text-lg md:text-xl font-semibold text-white mb-3 sm:mb-4">Saldo Cuti</h4>
              <div className="grid grid-cols-3 gap-2 sm:gap-3 md:gap-4">
                <div className="text-center">
                  <div className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-blue-400">12</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Cuti Tahunan</div>
                </div>
                <div className="text-center">
                  <div className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-green-400">5</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Cuti Sakit</div>
                </div>
                <div className="text-center">
                  <div className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-purple-400">3</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Cuti Khusus</div>
                </div>
              </div>
            </div>

            {/* Recent Leave Requests - Responsive Cards */}
            <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 border border-white/20">
              <h4 className="text-base sm:text-lg md:text-xl font-semibold text-white mb-3 sm:mb-4">Pengajuan Terakhir</h4>
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
      {/* Responsive Container - Mobile First with Tablet/Desktop Breakpoints */}
      <div className="w-full max-w-full sm:max-w-sm md:max-w-2xl lg:max-w-4xl xl:max-w-6xl 2xl:max-w-7xl mx-auto min-h-screen relative overflow-hidden">
        
        {/* Animated Background Elements - Responsive Sizing */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-10 w-24 h-24 sm:w-32 sm:h-32 md:w-40 md:h-40 lg:w-48 lg:h-48 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
          <div className="absolute top-40 right-5 w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 lg:w-40 lg:h-40 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
          <div className="absolute bottom-40 left-5 w-32 h-32 sm:w-40 sm:h-40 md:w-48 md:h-48 lg:w-56 lg:h-56 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
        </div>

        {/* Status Bar - Hide on Desktop */}
        <div className={`flex justify-between items-center px-4 sm:px-6 pt-3 pb-2 text-white text-sm font-semibold relative z-10 ${isDesktop ? 'lg:hidden' : ''}`}>
          <span className="text-xs sm:text-sm">{formatTime(currentTime)}</span>
          <div className="flex items-center space-x-1">
            <Wifi className="w-3 h-3 sm:w-4 sm:h-4" />
            <div className="w-5 h-2.5 sm:w-6 sm:h-3 border border-white rounded-sm relative">
              <div className="w-3.5 h-1.5 sm:w-4 sm:h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
            </div>
          </div>
        </div>

        {/* Hero Section - Responsive Typography and Spacing */}
        <div className="px-4 sm:px-6 md:px-8 lg:px-12 pt-4 sm:pt-6 md:pt-8 lg:pt-10 pb-4 sm:pb-6 relative z-10">
          <div className="text-center mb-6 sm:mb-8">
            <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
              Smart Attendance
            </h1>
            <p className="text-sm sm:text-base md:text-lg lg:text-xl text-purple-200">Dr. Naning Paramedis</p>
          </div>
        </div>

        {/* Tab Navigation - Responsive Layout */}
        <div className="px-4 sm:px-6 md:px-8 lg:px-12 mb-4 sm:mb-6 relative z-10">
          <div className="flex bg-gradient-to-r from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-sm rounded-lg border border-cyan-400/20 p-0.5 sm:p-1 shadow-lg shadow-cyan-500/10">
            
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
                  className={`relative z-10 flex-1 flex items-center justify-center space-x-1 sm:space-x-1.5 md:space-x-2 px-1 sm:px-2 md:px-3 py-1.5 sm:py-2 md:py-2.5 rounded-md transition-all duration-200 group ${
                    isActive 
                      ? 'text-cyan-300 scale-105' 
                      : 'text-gray-400 hover:text-cyan-400 hover:scale-102'
                  }`}
                >
                  {/* Icon with gaming glow - Responsive Sizing */}
                  <div className="relative">
                    <Icon className={`w-3 h-3 sm:w-3.5 sm:h-3.5 md:w-4 md:h-4 lg:w-5 lg:h-5 flex-shrink-0 transition-all duration-200 ${
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
                  
                  {/* Tab Label - Hide on small mobile, show on larger screens */}
                  <span className={`hidden sm:inline text-xs md:text-sm lg:text-base font-medium truncate transition-all duration-200 ${
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

        {/* Tab Content - Responsive Padding and Layout */}
        <div className="px-4 sm:px-6 md:px-8 lg:px-12 pb-16 sm:pb-20 md:pb-24 relative z-10">
          {renderTabContent()}
        </div>

        {/* Leave Form Modal - Responsive Sizing */}
        {showLeaveForm && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 sm:p-6">
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-white/20 w-full max-w-sm sm:max-w-md md:max-w-lg">
              <h3 className="text-xl font-bold text-white mb-6 text-center">Pengajuan Cuti</h3>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">Jenis Cuti</label>
                  <select 
                    value={leaveForm.type}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, type: e.target.value }))}
                    className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400"
                  >
                    <option value="annual" className="bg-gray-800">Cuti Tahunan</option>
                    <option value="sick" className="bg-gray-800">Cuti Sakit</option>
                    <option value="special" className="bg-gray-800">Cuti Khusus</option>
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">Tanggal Mulai</label>
                    <input 
                      type="date"
                      value={leaveForm.startDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, startDate: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">Tanggal Selesai</label>
                    <input 
                      type="date"
                      value={leaveForm.endDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, endDate: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">Alasan</label>
                  <textarea 
                    value={leaveForm.reason}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, reason: e.target.value }))}
                    className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400 h-20 resize-none"
                    placeholder="Jelaskan alasan pengajuan cuti..."
                  ></textarea>
                </div>
              </div>

              <div className="flex space-x-3 mt-6">
                <button
                  onClick={() => setShowLeaveForm(false)}
                  className="flex-1 bg-gray-500/20 hover:bg-gray-500/30 px-4 py-3 rounded-xl text-white transition-colors"
                >
                  Batal
                </button>
                <button
                  onClick={handleLeaveSubmit}
                  className="flex-1 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 px-4 py-3 rounded-xl text-white transition-colors flex items-center justify-center space-x-2"
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