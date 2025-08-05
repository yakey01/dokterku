"use client";

import '../../react-preamble';
import React, { useState, useEffect } from 'react';
import { Calendar, Clock, DollarSign, User, Home, MapPin, CheckCircle, XCircle, Zap, Heart, Brain, Shield, Target, Award, TrendingUp, Sun, Moon, Coffee, Star, Crown, HandHeart, Hand, Camera, Wifi, WifiOff, AlertTriangle, History, UserCheck, FileText, Settings, Bell, ChevronLeft, ChevronRight, Filter, Plus, Send } from 'lucide-react';

const CreativeAttendanceDashboard = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [activeTab, setActiveTab] = useState('checkin');
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState<'portrait' | 'landscape'>('portrait');
  const [attendanceData, setAttendanceData] = useState({
    checkInTime: null,
    checkOutTime: null,
    workingHours: '00:00:00',
    overtimeHours: '00:00:00',
    breakTime: '00:00:00',
    location: 'RS. Kediri Medical Center'
  });
  const [faceVerification, setFaceVerification] = useState(false);
  const [showCamera, setShowCamera] = useState(false);
  const [showLeaveForm, setShowLeaveForm] = useState(false);
  
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

    // Device detection following mobile_layout.md guidelines
    const checkDevice = () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      setIsIpad(width >= 768); // iPad threshold at 768px as per documentation
      setOrientation(width > height ? 'landscape' : 'portrait');
    };

    checkDevice();
    window.addEventListener('resize', checkDevice);
    window.addEventListener('orientationchange', checkDevice);

    return () => {
      clearInterval(timer);
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
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
    setShowCamera(true);
    setTimeout(() => {
      setFaceVerification(true);
      setShowCamera(false);
      setIsCheckedIn(true);
      const now = new Date();
      setAttendanceData(prev => ({
        ...prev,
        checkInTime: now.toISOString(),
        checkOutTime: null
      }));
    }, 3000);
  };

  const handleCheckOut = async () => {
    setShowCamera(true);
    setTimeout(() => {
      setFaceVerification(true);
      setShowCamera(false);
      setIsCheckedIn(false);
      const now = new Date();
      setAttendanceData(prev => ({
        ...prev,
        checkOutTime: now.toISOString()
      }));
    }, 3000);
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
          <div className={`space-y-6 ${isIpad ? 'space-y-8 md:space-y-10' : ''}`}>
            {/* Current Date and Time */}
            <div className="text-center">
              <div className={`text-purple-200 mb-2 ${
                isIpad ? 'text-xl md:text-2xl lg:text-3xl' : 'text-lg'
              }`}>{formatDate(currentTime)}</div>
              <div className={`font-bold text-white ${
                isIpad ? 'text-5xl md:text-6xl lg:text-7xl' : 'text-4xl'
              }`}>{formatTime(currentTime)}</div>
            </div>

            {/* Attendance Status Card */}
            <div className={`bg-white/10 backdrop-blur-xl rounded-3xl border border-white/20 ${
              isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
            }`}>
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

              {/* Working Hours Display */}
              {isCheckedIn && (
                <div className={`grid grid-cols-3 mt-6 ${
                  isIpad ? 'gap-6 md:gap-8 lg:gap-10 mt-8' : 'gap-4'
                }`}>
                  <div className="text-center">
                    <div className={`font-bold text-green-400 ${
                      isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-lg'
                    }`}>{attendanceData.workingHours}</div>
                    <div className={`text-gray-300 ${
                      isIpad ? 'text-sm md:text-base' : 'text-xs'
                    }`}>Jam Kerja</div>
                  </div>
                  <div className="text-center">
                    <div className={`font-bold text-blue-400 ${
                      isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-lg'
                    }`}>{attendanceData.breakTime}</div>
                    <div className={`text-gray-300 ${
                      isIpad ? 'text-sm md:text-base' : 'text-xs'
                    }`}>Istirahat</div>
                  </div>
                  <div className="text-center">
                    <div className={`font-bold text-purple-400 ${
                      isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-lg'
                    }`}>{attendanceData.overtimeHours}</div>
                    <div className={`text-gray-300 ${
                      isIpad ? 'text-sm md:text-base' : 'text-xs'
                    }`}>Overtime</div>
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

            {/* Check-in/out Buttons */}
            {/* Check-in/out Buttons - Enhanced Grid System */}
            <div className={`grid gap-6 md:gap-8 lg:gap-10 ${
              isIpad && orientation === 'landscape' 
                ? 'lg:grid-cols-2 xl:grid-cols-2' 
                : 'grid-cols-1 sm:grid-cols-2'
            }`}>
              <button 
                onClick={handleCheckIn}
                disabled={isCheckedIn}
                className={`relative group rounded-3xl transition-all duration-500 transform ${
                  isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
                } ${
                  isCheckedIn 
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
                  <div className={`mx-auto mb-4 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center ${
                    isIpad ? 'w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 mb-6 md:mb-8' : 'w-16 h-16'
                  }`}>
                    <Sun className={`text-white ${
                      isIpad ? 'w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14' : 'w-8 h-8'
                    }`} />
                  </div>
                  <div className={`text-white font-bold ${
                    isIpad ? 'text-xl md:text-2xl lg:text-3xl' : 'text-lg'
                  }`}>Check In</div>
                  <div className={`text-green-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Mulai bekerja</div>
                </div>
              </button>
              
              <button 
                onClick={handleCheckOut}
                disabled={!isCheckedIn}
                className={`relative group rounded-3xl transition-all duration-500 transform ${
                  isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
                } ${
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
                  <div className={`mx-auto mb-4 bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl flex items-center justify-center ${
                    isIpad ? 'w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 mb-6 md:mb-8' : 'w-16 h-16'
                  }`}>
                    <Moon className={`text-white ${
                      isIpad ? 'w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14' : 'w-8 h-8'
                    }`} />
                  </div>
                  <div className={`text-white font-bold ${
                    isIpad ? 'text-xl md:text-2xl lg:text-3xl' : 'text-lg'
                  }`}>Check Out</div>
                  <div className={`text-purple-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Selesai bekerja</div>
                </div>
              </button>
            </div>

            {/* Location Map */}
            <div className="bg-white/10 backdrop-blur-xl rounded-3xl border border-white/20 overflow-hidden">
              <div className="p-4 border-b border-white/10">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-xl flex items-center justify-center">
                      <MapPin className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="font-semibold text-white">RS. Kediri Medical Center</div>
                      <div className="text-green-300 text-sm">Jl. Ahmad Yani No. 123</div>
                    </div>
                  </div>
                  <div className="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                </div>
              </div>
              
              <div className={`relative bg-gradient-to-br from-blue-500/20 to-green-500/20 ${
                isIpad ? 'h-64 md:h-80 lg:h-96' : 'h-48'
              }`}>
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="text-center text-white">
                    <MapPin className="w-16 h-16 mx-auto mb-4 text-green-400" />
                    <div className="text-lg font-semibold">Lokasi Kantor</div>
                    <div className="text-sm text-gray-300">RS. Kediri Medical Center</div>
                  </div>
                </div>
                
                <div className="absolute top-4 left-4 bg-black/60 backdrop-blur-md rounded-2xl p-3 border border-white/20">
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span className="text-white text-sm font-medium">Live Location</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Today's Work Summary Card */}
            <div className={`bg-white/10 backdrop-blur-xl rounded-3xl border border-white/20 ${
              isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
            }`}>
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
          <div className={`space-y-4 ${isIpad ? 'space-y-6 md:space-y-8' : ''}`}>
            <div className="flex justify-between items-center mb-6">
              <h3 className={`font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent ${
                isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl'
              }`}>
                Riwayat Presensi
              </h3>
              <div className="flex items-center space-x-2">
                <Filter className={`text-purple-300 ${
                  isIpad ? 'w-5 h-5 md:w-6 md:h-6' : 'w-4 h-4'
                }`} />
                <select 
                  value={filterPeriod}
                  onChange={(e) => handleFilterChange(e.target.value)}
                  className={`bg-white/10 backdrop-blur-xl border border-white/20 rounded-xl text-white focus:outline-none focus:border-purple-400 ${
                    isIpad ? 'px-4 py-2 text-base md:text-lg' : 'px-3 py-1 text-sm'
                  }`}
                >
                  <option value="weekly" className="bg-gray-800">7 Hari</option>
                  <option value="monthly" className="bg-gray-800">30 Hari</option>
                </select>
              </div>
            </div>

            <div className={`space-y-4 ${
              isIpad && orientation === 'landscape' 
                ? 'grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-6 md:gap-8 space-y-0' 
                : ''
            }`}>
              {currentData.map((record, index) => (
                <div key={index} className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
                  isIpad ? 'p-6 md:p-8 lg:p-10' : 'p-4'
                }`}>
                  <div className="flex justify-between items-center mb-2">
                    <span className={`text-white font-semibold ${
                      isIpad ? 'text-lg md:text-xl' : 'text-base'
                    }`}>{record.date}</span>
                    <span className={`font-medium ${getStatusColor(record.status)} ${
                      isIpad ? 'text-base md:text-lg' : 'text-sm'
                    }`}>
                      {record.status}
                    </span>
                  </div>
                  <div className={`grid grid-cols-3 gap-2 ${
                    isIpad ? 'gap-4 md:gap-6 text-base md:text-lg' : 'text-sm'
                  }`}>
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
            </div>

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
          <div className={`space-y-6 ${isIpad ? 'space-y-8 md:space-y-10' : ''}`}>
            <h3 className={`font-bold mb-6 text-center bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent ${
              isIpad ? 'text-2xl md:text-3xl lg:text-4xl mb-8 md:mb-10' : 'text-xl'
            }`}>
              Statistik Bulanan
            </h3>
            
            {/* Statistics Grid - Enhanced for iPad */}
            <div className={`grid gap-4 md:gap-6 lg:gap-8 ${
              isIpad 
                ? 'grid-cols-2 lg:grid-cols-4 xl:grid-cols-4' 
                : 'grid-cols-2'
            }`}>
              <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
                isIpad ? 'p-6 md:p-8 lg:p-10' : 'p-4'
              }`}>
                <div className="text-center">
                  <div className={`font-bold text-green-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>{monthlyStats.presentDays}</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Hari Hadir</div>
                </div>
              </div>
              <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
                isIpad ? 'p-6 md:p-8 lg:p-10' : 'p-4'
              }`}>
                <div className="text-center">
                  <div className={`font-bold text-yellow-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>{monthlyStats.lateDays}</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Hari Terlambat</div>
                </div>
              </div>
              <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
                isIpad ? 'p-6 md:p-8 lg:p-10' : 'p-4'
              }`}>
                <div className="text-center">
                  <div className={`font-bold text-blue-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>{monthlyStats.overtimeHours}h</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Overtime</div>
                </div>
              </div>
              <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
                isIpad ? 'p-6 md:p-8 lg:p-10' : 'p-4'
              }`}>
                <div className="text-center">
                  <div className={`font-bold text-purple-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>{monthlyStats.leaveBalance}</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Sisa Cuti</div>
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
            <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
              isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
            }`}>
              <h4 className={`font-semibold text-white mb-6 text-center ${
                isIpad ? 'text-xl md:text-2xl lg:text-3xl mb-8 md:mb-10' : 'text-lg'
              }`}>Achievement Rings</h4>
              
              <div className={`flex justify-center ${
                isIpad ? 'space-x-12 md:space-x-16 lg:space-x-20' : 'space-x-8'
              }`}>
                {/* Days Ring */}
                <div className="relative">
                  <svg className={`transform -rotate-90 ${
                    isIpad ? 'w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32' : 'w-20 h-20'
                  }`} viewBox="0 0 36 36">
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
                      <div className={`font-bold text-white ${
                        isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl'
                      }`}>28</div>
                      <div className={`text-green-300 ${
                        isIpad ? 'text-sm md:text-base' : 'text-xs'
                      }`}>Days</div>
                    </div>
                  </div>
                </div>

                {/* Hours Ring */}
                <div className="relative">
                  <svg className={`transform -rotate-90 ${
                    isIpad ? 'w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32' : 'w-20 h-20'
                  }`} viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
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
                      <div className={`font-bold text-white ${
                        isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl'
                      }`}>7.2</div>
                      <div className={`text-blue-300 ${
                        isIpad ? 'text-sm md:text-base' : 'text-xs'
                      }`}>Hours</div>
                    </div>
                  </div>
                </div>

                {/* Performance Ring */}
                <div className="relative">
                  <svg className={`transform -rotate-90 ${
                    isIpad ? 'w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32' : 'w-20 h-20'
                  }`} viewBox="0 0 36 36">
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
                      <div className={`font-bold text-white ${
                        isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl'
                      }`}>96%</div>
                      <div className={`text-purple-300 ${
                        isIpad ? 'text-sm md:text-base' : 'text-xs'
                      }`}>Score</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      case 'leave':
        return (
          <div className={`space-y-6 ${isIpad ? 'space-y-8 md:space-y-10' : ''}`}>
            <div className="flex justify-between items-center mb-6">
              <h3 className={`font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent ${
                isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl'
              }`}>
                Manajemen Cuti
              </h3>
              <button
                onClick={() => setShowLeaveForm(true)}
                className={`bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 rounded-xl flex items-center space-x-2 transition-all ${
                  isIpad ? 'px-6 py-3 md:px-8 md:py-4' : 'px-4 py-2'
                }`}
              >
                <Plus className={`${
                  isIpad ? 'w-5 h-5 md:w-6 md:h-6' : 'w-4 h-4'
                }`} />
                <span className={`font-medium ${
                  isIpad ? 'text-base md:text-lg' : 'text-sm'
                }`}>Ajukan Cuti</span>
              </button>
            </div>

            {/* Leave Balance Card */}
            <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
              isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
            }`}>
              <h4 className={`font-semibold text-white mb-4 ${
                isIpad ? 'text-xl md:text-2xl lg:text-3xl mb-6 md:mb-8' : 'text-lg'
              }`}>Saldo Cuti</h4>
              <div className={`grid gap-4 ${
                isIpad ? 'grid-cols-3 gap-6 md:gap-8 lg:gap-10' : 'grid-cols-3'
              }`}>
                <div className="text-center">
                  <div className={`font-bold text-blue-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>12</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Cuti Tahunan</div>
                </div>
                <div className="text-center">
                  <div className={`font-bold text-green-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>5</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Cuti Sakit</div>
                </div>
                <div className="text-center">
                  <div className={`font-bold text-purple-400 ${
                    isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl'
                  }`}>3</div>
                  <div className={`text-gray-300 ${
                    isIpad ? 'text-base md:text-lg' : 'text-sm'
                  }`}>Cuti Khusus</div>
                </div>
              </div>
            </div>

            {/* Recent Leave Requests */}
            <div className={`bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 ${
              isIpad ? 'p-8 md:p-10 lg:p-12' : 'p-6'
            }`}>
              <h4 className={`font-semibold text-white mb-4 ${
                isIpad ? 'text-xl md:text-2xl lg:text-3xl mb-6 md:mb-8' : 'text-lg'
              }`}>Pengajuan Terakhir</h4>
              <div className={`space-y-3 ${
                isIpad ? 'space-y-4 md:space-y-6' : ''
              }`}>
                {[
                  { date: '15-20 Jul 2025', type: 'Cuti Tahunan', status: 'Approved', days: 4 },
                  { date: '28 Jun 2025', type: 'Cuti Sakit', status: 'Approved', days: 1 },
                  { date: '10-11 Jun 2025', type: 'Cuti Khusus', status: 'Pending', days: 2 }
                ].map((leave, index) => (
                  <div key={index} className={`flex justify-between items-center bg-black/20 rounded-xl ${
                    isIpad ? 'p-4 md:p-5 lg:p-6' : 'p-3'
                  }`}>
                    <div>
                      <div className={`text-white font-medium ${
                        isIpad ? 'text-lg md:text-xl' : 'text-base'
                      }`}>{leave.type}</div>
                      <div className={`text-gray-300 ${
                        isIpad ? 'text-base md:text-lg' : 'text-sm'
                      }`}>{leave.date} • {leave.days} hari</div>
                    </div>
                    <div className={`rounded-full font-medium ${
                      isIpad ? 'px-4 py-2 text-sm md:text-base' : 'px-3 py-1 text-xs'
                    } ${
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
    <div className="w-full bg-gray-900 min-h-screen relative">
      <div className={`min-h-screen relative overflow-hidden ${
        isIpad 
          ? 'relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-32' 
          : 'max-w-sm mx-auto'
      }`}>
        
        {/* Animated Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          {/* Base background elements for all devices */}
          <div className="absolute top-20 left-10 w-32 h-32 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
          <div className="absolute top-40 right-5 w-24 h-24 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
          <div className="absolute bottom-40 left-5 w-40 h-40 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
          
          {/* Additional decorative elements only on iPad */}
          {isIpad && (
            <>
              <div className="absolute bg-cyan-400/10 rounded-full blur-2xl animate-pulse" 
                   style={{ top: '40%', right: '20%', width: '20vw', maxWidth: '300px', height: '20vw', maxHeight: '300px' }} />
              <div className="absolute bg-emerald-400/10 rounded-full blur-3xl animate-bounce" 
                   style={{ bottom: '25%', left: '15%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }} />
            </>
          )}
        </div>

        {/* Status Bar - Mobile only */}
        {!isIpad && (
          <div className="flex justify-between items-center px-6 pt-3 pb-2 text-white text-sm font-semibold relative z-10">
            <span>{formatTime(currentTime)}</span>
            <div className="flex items-center space-x-1">
              <div className="flex space-x-1">
                <div className="w-1 h-3 bg-white rounded-full"></div>
                <div className="w-1 h-3 bg-white rounded-full"></div>
                <div className="w-1 h-3 bg-white rounded-full"></div>
                <div className="w-1 h-3 bg-gray-500 rounded-full"></div>
              </div>
              <div className={`border border-white rounded-sm relative ${
                isIpad ? 'w-8 h-4 md:w-10 md:h-5' : 'w-6 h-3'
              }`}>
                <div className="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
              </div>
            </div>
          </div>
        )}

        {/* Hero Section */}
        <div className="pt-8 pb-6 relative z-10">
          <div className="text-center mb-8">
            <h1 className={`font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent ${
              isIpad ? 'text-4xl md:text-5xl lg:text-6xl' : 'text-3xl'
            }`}>
              Smart Attendance
            </h1>
            <p className={`text-purple-200 ${
              isIpad ? 'text-lg md:text-xl' : 'text-base'
            }`}>Dr. Naning Paramedis</p>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="mb-6 relative z-10">
          <div className={`bg-gradient-to-r from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-sm rounded-lg border border-cyan-400/20 shadow-lg shadow-cyan-500/10 ${
            isIpad 
              ? 'flex p-1 md:p-1.5' 
              : 'flex p-0.5'
          }`}>
            
            {/* Active Tab Indicator */}
            <div 
              className={`absolute bg-gradient-to-r from-cyan-500/30 via-purple-500/30 to-pink-500/30 backdrop-blur-xl rounded-md border border-cyan-400/40 transition-all duration-300 ease-out ${
                isIpad 
                  ? 'top-1 bottom-1' 
                  : 'top-0.5 bottom-0.5'
              } ${
                activeTab === 'checkin' ? (isIpad ? 'left-1 w-[calc(25%-8px)]' : 'left-0.5 w-[calc(25%-2px)]') :
                activeTab === 'history' ? (isIpad ? 'left-[calc(25%+4px)] w-[calc(25%-8px)]' : 'left-[calc(25%+1px)] w-[calc(25%-2px)]') :
                activeTab === 'stats' ? (isIpad ? 'left-[calc(50%+4px)] w-[calc(25%-8px)]' : 'left-[calc(50%+1px)] w-[calc(25%-2px)]') :
                (isIpad ? 'left-[calc(75%+4px)] w-[calc(25%-8px)]' : 'left-[calc(75%+1px)] w-[calc(25%-2px)]')
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
                  className={`relative z-10 flex-1 flex items-center justify-center rounded-md transition-all duration-200 group ${
                    isIpad 
                      ? 'space-x-2 px-4 py-3 md:px-6 md:py-4' 
                      : 'space-x-1.5 px-2 py-1.5'
                  } ${
                    isActive 
                      ? 'text-cyan-300 scale-105' 
                      : 'text-gray-400 hover:text-cyan-400 hover:scale-102'
                  }`}
                >
                  {/* Icon with gaming glow */}
                  <div className="relative">
                    <Icon className={`flex-shrink-0 transition-all duration-200 ${
                      isIpad ? 'w-5 h-5 md:w-6 md:h-6' : 'w-3.5 h-3.5'
                    } ${
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
                  
                  <span className={`font-medium truncate transition-all duration-200 ${
                    isIpad ? 'text-sm md:text-base' : 'text-xs'
                  } ${
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

        {/* Tab Content */}
        <div className={`pb-32 relative z-10 ${
          !isIpad ? 'px-6' : ''
        }`}>
          {renderTabContent()}
        </div>

        {/* Leave Form Modal */}
        {showLeaveForm && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-6">
            <div className={`bg-white/10 backdrop-blur-xl rounded-3xl border border-white/20 w-full ${
              isIpad ? 'max-w-2xl p-8 md:p-10 lg:p-12' : 'max-w-sm p-6'
            }`}>
              <h3 className={`font-bold text-white mb-6 text-center ${
                isIpad ? 'text-2xl md:text-3xl mb-8 md:mb-10' : 'text-xl'
              }`}>Pengajuan Cuti</h3>
              
              <div className={`space-y-4 ${
                isIpad ? 'space-y-6 md:space-y-8' : ''
              }`}>
                <div>
                  <label className={`block font-medium text-gray-300 mb-2 ${
                    isIpad ? 'text-base md:text-lg mb-3 md:mb-4' : 'text-sm'
                  }`}>Jenis Cuti</label>
                  <select 
                    value={leaveForm.type}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, type: e.target.value }))}
                    className={`w-full bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-purple-400 ${
                      isIpad ? 'px-6 py-4 md:px-8 md:py-5 text-base md:text-lg' : 'px-4 py-3'
                    }`}
                  >
                    <option value="annual" className="bg-gray-800">Cuti Tahunan</option>
                    <option value="sick" className="bg-gray-800">Cuti Sakit</option>
                    <option value="special" className="bg-gray-800">Cuti Khusus</option>
                  </select>
                </div>

                <div className={`grid grid-cols-2 gap-4 ${
                  isIpad ? 'gap-6 md:gap-8' : ''
                }`}>
                  <div>
                    <label className={`block font-medium text-gray-300 mb-2 ${
                      isIpad ? 'text-base md:text-lg mb-3 md:mb-4' : 'text-sm'
                    }`}>Tanggal Mulai</label>
                    <input 
                      type="date"
                      value={leaveForm.startDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, startDate: e.target.value }))}
                      className={`w-full bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-purple-400 ${
                        isIpad ? 'px-6 py-4 md:px-8 md:py-5 text-base md:text-lg' : 'px-4 py-3'
                      }`}
                    />
                  </div>
                  <div>
                    <label className={`block font-medium text-gray-300 mb-2 ${
                      isIpad ? 'text-base md:text-lg mb-3 md:mb-4' : 'text-sm'
                    }`}>Tanggal Selesai</label>
                    <input 
                      type="date"
                      value={leaveForm.endDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, endDate: e.target.value }))}
                      className={`w-full bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-purple-400 ${
                        isIpad ? 'px-6 py-4 md:px-8 md:py-5 text-base md:text-lg' : 'px-4 py-3'
                      }`}
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

              <div className={`flex space-x-3 mt-6 ${
                isIpad ? 'space-x-4 md:space-x-6 mt-8 md:mt-10' : ''
              }`}>
                <button
                  onClick={() => setShowLeaveForm(false)}
                  className={`flex-1 bg-gray-500/20 hover:bg-gray-500/30 rounded-xl text-white transition-colors ${
                    isIpad ? 'px-6 py-4 md:px-8 md:py-5 text-base md:text-lg' : 'px-4 py-3'
                  }`}
                >
                  Batal
                </button>
                <button
                  onClick={handleLeaveSubmit}
                  className={`flex-1 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 rounded-xl text-white transition-colors flex items-center justify-center space-x-2 ${
                    isIpad ? 'px-6 py-4 md:px-8 md:py-5 text-base md:text-lg' : 'px-4 py-3'
                  }`}
                >
                  <Send className={`${
                    isIpad ? 'w-5 h-5 md:w-6 md:h-6' : 'w-4 h-4'
                  }`} />
                  <span>Kirim</span>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Camera Modal for Face Verification */}
        {showCamera && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div className={`bg-white/10 backdrop-blur-xl rounded-3xl border border-white/20 text-center ${
              isIpad ? 'p-12 md:p-16 lg:p-20' : 'p-8'
            }`}>
              <Camera className={`text-blue-400 mx-auto mb-4 animate-pulse ${
                isIpad ? 'w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 mb-6 md:mb-8' : 'w-16 h-16'
              }`} />
              <h3 className={`font-bold text-white mb-2 ${
                isIpad ? 'text-2xl md:text-3xl lg:text-4xl mb-4 md:mb-6' : 'text-xl'
              }`}>Verifikasi Wajah</h3>
              <p className={`text-gray-300 mb-6 ${
                isIpad ? 'text-lg md:text-xl mb-8 md:mb-10' : 'text-base'
              }`}>Posisikan wajah Anda di dalam frame</p>
              <div className={`mx-auto border-4 border-blue-400 rounded-full flex items-center justify-center mb-6 ${
                isIpad ? 'w-64 h-64 md:w-72 md:h-72 lg:w-80 lg:h-80 mb-8 md:mb-10' : 'w-48 h-48'
              }`}>
                <div className={`border-2 border-dashed border-blue-300 rounded-full flex items-center justify-center ${
                  isIpad ? 'w-56 h-56 md:w-64 md:h-64 lg:w-72 lg:h-72' : 'w-40 h-40'
                }`}>
                  <User className={`text-blue-300 ${
                    isIpad ? 'w-28 h-28 md:w-32 md:h-32 lg:w-36 lg:h-36' : 'w-20 h-20'
                  }`} />
                </div>
              </div>
              <div className={`text-blue-300 ${
                isIpad ? 'text-lg md:text-xl' : 'text-base'
              }`}>Memindai...</div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CreativeAttendanceDashboard;