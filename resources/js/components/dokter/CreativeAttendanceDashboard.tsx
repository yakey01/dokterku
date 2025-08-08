import React, { useState, useEffect } from 'react';
import { 
  Calendar, Clock, MapPin, Users, TrendingUp, Star, Award, 
  CheckCircle, XCircle, Coffee, Sun, Moon, Zap, Heart, 
  Camera, User, ChevronLeft, ChevronRight, Filter, Plus, 
  Send, AlertTriangle, History, Bell, Settings 
} from 'lucide-react';

const CreativeAttendanceDashboard = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [activeTab, setActiveTab] = useState('attendance');
  const [checkInStatus, setCheckInStatus] = useState('out'); // 'in', 'out', 'break'
  const [attendanceData, setAttendanceData] = useState({
    todayHours: '0h 0m',
    weeklyHours: '32h 15m',
    monthlyHours: '145h 30m',
    totalProjects: 8,
    completedTasks: 24,
    efficiency: 94
  });
  
  const [showFaceCamera, setShowFaceCamera] = useState(false);
  const [faceVerified, setFaceVerified] = useState(false);
  const [currentLocation, setCurrentLocation] = useState('Getting location...');
  const [userData, setUserData] = useState<{
    name: string;
    email: string;
    role: string;
  } | null>(null);

  // Stats data
  const [attendanceStats] = useState({
    thisWeek: { present: 4, late: 1, absent: 0, total: 5 },
    thisMonth: { present: 18, late: 2, absent: 1, total: 21 },
    streak: 15,
    avgHours: 8.2,
    punctuality: 87
  });

  // History data  
  const [attendanceHistory] = useState([
    { date: '2025-08-05', checkIn: '08:30', checkOut: '17:15', status: 'Present', break: '1h', total: '7h 45m' },
    { date: '2025-08-02', checkIn: '08:15', checkOut: '17:30', status: 'Present', break: '1h', total: '8h 15m' },
    { date: '2025-08-01', checkIn: '09:00', checkOut: '17:45', status: 'Late', break: '45m', total: '7h 45m' },
    { date: '2025-07-31', checkIn: '08:00', checkOut: '17:00', status: 'Present', break: '1h', total: '8h' },
    { date: '2025-07-30', checkIn: '-', checkOut: '-', status: 'Leave', break: '-', total: '0h' },
    { date: '2025-07-29', checkIn: '08:30', checkOut: '18:00', status: 'Present', break: '1h 30m', total: '8h' }
  ]);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);

    // Simulate getting location
    setTimeout(() => {
      setCurrentLocation('Klinik Dokterku - Kediri');
    }, 2000);

    return () => clearInterval(timer);
  }, []);

  // Load user data
  useEffect(() => {
    const loadUserData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v2/dashboards/dokter/', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });

        if (response.ok) {
          const data = await response.json();
          if (data.success && data.data?.user) {
            setUserData(data.data.user);
          }
        }
      } catch (error) {
        console.error('Error loading user data:', error);
      }
    };

    loadUserData();
  }, []);

  const formatTime = (date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit',
      second: '2-digit'
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

  const handleCheckIn = () => {
    setShowFaceCamera(true);
    setTimeout(() => {
      setFaceVerified(true);
      setShowFaceCamera(false);
      setCheckInStatus('in');
      // Update today's hours logic would go here
    }, 3000);
  };

  const handleCheckOut = () => {
    setShowFaceCamera(true);
    setTimeout(() => {
      setFaceVerified(true);
      setShowFaceCamera(false);
      setCheckInStatus('out');
      setAttendanceData(prev => ({
        ...prev,
        todayHours: '8h 15m'
      }));
    }, 3000);
  };

  const handleBreak = () => {
    setCheckInStatus(checkInStatus === 'break' ? 'in' : 'break');
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Present': return 'text-green-400';
      case 'Late': return 'text-yellow-400'; 
      case 'Leave': return 'text-blue-400';
      case 'Absent': return 'text-red-400';
      default: return 'text-gray-400';
    }
  };

  const getStatusBg = (status) => {
    switch (status) {
      case 'Present': return 'bg-green-500/20 border-green-500/50';
      case 'Late': return 'bg-yellow-500/20 border-yellow-500/50'; 
      case 'Leave': return 'bg-blue-500/20 border-blue-500/50';
      case 'Absent': return 'bg-red-500/20 border-red-500/50';
      default: return 'bg-gray-500/20 border-gray-500/50';
    }
  };

  const renderAttendanceTab = () => (
    <div className="space-y-6">
      {/* Current Time & Date */}
      <div className="text-center space-y-2">
        <div className="text-5xl font-bold text-white font-mono tracking-tight">
          {formatTime(currentTime)}
        </div>
        <div className="text-gray-300 text-sm">
          {formatDate(currentTime)}
        </div>
        <div className="flex items-center justify-center space-x-2 text-xs text-gray-400">
          <MapPin className="w-3 h-3" />
          <span>{currentLocation}</span>
        </div>
      </div>

      {/* Status Card */}
      <div className="bg-gradient-to-br from-purple-900/50 to-pink-900/50 backdrop-blur-xl rounded-3xl p-6 border border-purple-500/30">
        <div className="text-center space-y-4">
          <div className={`inline-flex items-center space-x-3 px-6 py-3 rounded-2xl ${
            checkInStatus === 'in' ? 'bg-green-500/20 border border-green-500/50' :
            checkInStatus === 'break' ? 'bg-yellow-500/20 border border-yellow-500/50' :
            'bg-gray-500/20 border border-gray-500/50'
          }`}>
            <div className={`w-3 h-3 rounded-full ${
              checkInStatus === 'in' ? 'bg-green-400 animate-pulse' :
              checkInStatus === 'break' ? 'bg-yellow-400 animate-pulse' :
              'bg-gray-400'
            }`}></div>
            <span className="text-white font-semibold">
              {checkInStatus === 'in' ? '🚀 Sedang Bekerja' :
               checkInStatus === 'break' ? '☕ Istirahat' :
               '😴 Belum Check-in'}
            </span>
          </div>

          {/* Working Hours Display */}
          <div className="grid grid-cols-3 gap-4 mt-6">
            <div className="space-y-1">
              <div className="text-2xl font-bold text-blue-400">{attendanceData.todayHours}</div>
              <div className="text-xs text-gray-300">Hari Ini</div>
            </div>
            <div className="space-y-1">
              <div className="text-2xl font-bold text-purple-400">{attendanceData.weeklyHours}</div>
              <div className="text-xs text-gray-300">Minggu Ini</div>
            </div>
            <div className="space-y-1">
              <div className="text-2xl font-bold text-pink-400">{attendanceData.monthlyHours}</div>
              <div className="text-xs text-gray-300">Bulan Ini</div>
            </div>
          </div>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="grid grid-cols-2 gap-4">
        <button 
          onClick={handleCheckIn}
          disabled={checkInStatus === 'in' || checkInStatus === 'break'}
          className={`relative group p-6 rounded-3xl transition-all duration-300 ${
            checkInStatus === 'out' 
              ? 'bg-gradient-to-br from-green-500/20 to-emerald-500/20 border border-green-500/30 hover:scale-105 active:scale-95' 
              : 'bg-gray-500/10 border border-gray-500/20 opacity-50 cursor-not-allowed'
          }`}
        >
          <div className="text-center space-y-3">
            <div className="w-16 h-16 mx-auto bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center">
              <Clock className="w-8 h-8 text-white" />
            </div>
            <div>
              <div className="text-white font-bold text-lg">Check In</div>
              <div className="text-green-300 text-sm">Mulai bekerja</div>
            </div>
          </div>
        </button>
        
        <button 
          onClick={handleCheckOut}
          disabled={checkInStatus === 'out'}
          className={`relative group p-6 rounded-3xl transition-all duration-300 ${
            checkInStatus !== 'out' 
              ? 'bg-gradient-to-br from-red-500/20 to-pink-500/20 border border-red-500/30 hover:scale-105 active:scale-95' 
              : 'bg-gray-500/10 border border-gray-500/20 opacity-50 cursor-not-allowed'
          }`}
        >
          <div className="text-center space-y-3">
            <div className="w-16 h-16 mx-auto bg-gradient-to-br from-red-400 to-pink-500 rounded-2xl flex items-center justify-center">
              <XCircle className="w-8 h-8 text-white" />
            </div>
            <div>
              <div className="text-white font-bold text-lg">Check Out</div>
              <div className="text-red-300 text-sm">Selesai bekerja</div>
            </div>
          </div>
        </button>
      </div>

      {/* Break Button */}
      <button 
        onClick={handleBreak}
        disabled={checkInStatus === 'out'}
        className={`w-full p-4 rounded-2xl transition-all duration-300 ${
          checkInStatus !== 'out' 
            ? checkInStatus === 'break'
              ? 'bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-blue-500/30' 
              : 'bg-gradient-to-r from-yellow-500/20 to-orange-500/20 border border-yellow-500/30 hover:scale-105'
            : 'bg-gray-500/10 border border-gray-500/20 opacity-50 cursor-not-allowed'
        }`}
      >
        <div className="flex items-center justify-center space-x-3">
          <Coffee className="w-5 h-5 text-white" />
          <span className="text-white font-semibold">
            {checkInStatus === 'break' ? 'Kembali Bekerja' : 'Istirahat'}
          </span>
        </div>
      </button>

      {/* Quick Stats */}
      <div className="grid grid-cols-3 gap-3">
        <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10 text-center">
          <div className="text-xl font-bold text-blue-400">{attendanceData.totalProjects}</div>
          <div className="text-xs text-gray-300">Projects</div>
        </div>
        <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10 text-center">
          <div className="text-xl font-bold text-green-400">{attendanceData.completedTasks}</div>
          <div className="text-xs text-gray-300">Tasks</div>
        </div>
        <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10 text-center">
          <div className="text-xl font-bold text-purple-400">{attendanceData.efficiency}%</div>
          <div className="text-xs text-gray-300">Efficiency</div>
        </div>
      </div>
    </div>
  );

  const renderHistoryTab = () => (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-white">Riwayat Kehadiran</h3>
        <button className="p-2 bg-white/10 rounded-xl">
          <Filter className="w-4 h-4 text-gray-300" />
        </button>
      </div>

      <div className="space-y-3">
        {attendanceHistory.map((record, index) => (
          <div key={index} className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10">
            <div className="flex items-center justify-between mb-3">
              <span className="text-white font-semibold">{record.date}</span>
              <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusBg(record.status)} ${getStatusColor(record.status)}`}>
                {record.status}
              </span>
            </div>
            <div className="grid grid-cols-4 gap-2 text-sm">
              <div>
                <div className="text-gray-400 text-xs">Masuk</div>
                <div className="text-white font-medium">{record.checkIn}</div>
              </div>
              <div>
                <div className="text-gray-400 text-xs">Keluar</div>
                <div className="text-white font-medium">{record.checkOut}</div>
              </div>
              <div>
                <div className="text-gray-400 text-xs">Break</div>
                <div className="text-white font-medium">{record.break}</div>
              </div>
              <div>
                <div className="text-gray-400 text-xs">Total</div>
                <div className="text-white font-medium">{record.total}</div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  const renderStatsTab = () => (
    <div className="space-y-6">
      <h3 className="text-xl font-bold text-white text-center">Statistik Kehadiran</h3>
      
      {/* Weekly Stats */}
      <div className="bg-gradient-to-br from-blue-900/50 to-purple-900/50 backdrop-blur-xl rounded-3xl p-6 border border-blue-500/30">
        <h4 className="text-lg font-semibold text-white mb-4 flex items-center space-x-2">
          <Calendar className="w-5 h-5 text-blue-400" />
          <span>Minggu Ini</span>
        </h4>
        <div className="grid grid-cols-4 gap-3">
          <div className="text-center">
            <div className="text-2xl font-bold text-green-400">{attendanceStats.thisWeek.present}</div>
            <div className="text-xs text-gray-300">Hadir</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-yellow-400">{attendanceStats.thisWeek.late}</div>
            <div className="text-xs text-gray-300">Terlambat</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-red-400">{attendanceStats.thisWeek.absent}</div>
            <div className="text-xs text-gray-300">Tidak Hadir</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-blue-400">{attendanceStats.thisWeek.total}</div>
            <div className="text-xs text-gray-300">Total</div>
          </div>
        </div>
      </div>

      {/* Monthly Stats */}
      <div className="bg-gradient-to-br from-purple-900/50 to-pink-900/50 backdrop-blur-xl rounded-3xl p-6 border border-purple-500/30">
        <h4 className="text-lg font-semibold text-white mb-4 flex items-center space-x-2">
          <TrendingUp className="w-5 h-5 text-purple-400" />
          <span>Bulan Ini</span>
        </h4>
        <div className="grid grid-cols-4 gap-3">
          <div className="text-center">
            <div className="text-2xl font-bold text-green-400">{attendanceStats.thisMonth.present}</div>
            <div className="text-xs text-gray-300">Hadir</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-yellow-400">{attendanceStats.thisMonth.late}</div>
            <div className="text-xs text-gray-300">Terlambat</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-red-400">{attendanceStats.thisMonth.absent}</div>
            <div className="text-xs text-gray-300">Tidak Hadir</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-purple-400">{attendanceStats.thisMonth.total}</div>
            <div className="text-xs text-gray-300">Total</div>
          </div>
        </div>
      </div>

      {/* Achievement Cards */}
      <div className="grid grid-cols-2 gap-4">
        <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10 text-center">
          <Award className="w-8 h-8 text-yellow-400 mx-auto mb-2" />
          <div className="text-xl font-bold text-yellow-400">{attendanceStats.streak}</div>
          <div className="text-xs text-gray-300">Hari Beruntun</div>
        </div>
        <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10 text-center">
          <Clock className="w-8 h-8 text-blue-400 mx-auto mb-2" />
          <div className="text-xl font-bold text-blue-400">{attendanceStats.avgHours}h</div>
          <div className="text-xs text-gray-300">Rata-rata Jam</div>
        </div>
      </div>

      {/* Punctuality Score */}
      <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
        <h4 className="text-lg font-semibold text-white mb-4 flex items-center space-x-2">
          <Star className="w-5 h-5 text-yellow-400" />
          <span>Skor Ketepatan Waktu</span>
        </h4>
        <div className="space-y-3">
          <div className="flex justify-between text-sm">
            <span className="text-gray-300">Tingkat Kedisiplinan</span>
            <span className="text-yellow-400 font-bold">{attendanceStats.punctuality}%</span>
          </div>
          <div className="w-full bg-gray-700/50 rounded-full h-3">
            <div 
              className="bg-gradient-to-r from-yellow-400 to-orange-500 h-3 rounded-full transition-all duration-1000"
              style={{ width: `${attendanceStats.punctuality}%` }}
            ></div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderLeaveTab = () => (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-white">Manajemen Cuti</h3>
        <button className="bg-gradient-to-r from-green-500 to-emerald-500 px-4 py-2 rounded-xl flex items-center space-x-2">
          <Plus className="w-4 h-4" />
          <span className="text-sm font-medium">Ajukan</span>
        </button>
      </div>

      {/* Leave Balance */}
      <div className="bg-gradient-to-br from-green-900/50 to-emerald-900/50 backdrop-blur-xl rounded-3xl p-6 border border-green-500/30">
        <h4 className="text-lg font-semibold text-white mb-4">Saldo Cuti</h4>
        <div className="grid grid-cols-3 gap-4">
          <div className="text-center">
            <div className="text-2xl font-bold text-green-400">12</div>
            <div className="text-xs text-gray-300">Cuti Tahunan</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-blue-400">5</div>
            <div className="text-xs text-gray-300">Cuti Sakit</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-purple-400">3</div>
            <div className="text-xs text-gray-300">Cuti Khusus</div>
          </div>
        </div>
      </div>

      {/* Recent Requests */}
      <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
        <h4 className="text-lg font-semibold text-white mb-4">Pengajuan Terakhir</h4>
        <div className="space-y-3">
          {[
            { type: 'Cuti Tahunan', period: '15-20 Jul', status: 'Approved', days: 4 },
            { type: 'Cuti Sakit', period: '28 Jun', status: 'Approved', days: 1 },
            { type: 'Cuti Khusus', period: '10-11 Jun', status: 'Pending', days: 2 }
          ].map((request, index) => (
            <div key={index} className="flex justify-between items-center p-3 bg-black/20 rounded-xl">
              <div>
                <div className="text-white font-medium">{request.type}</div>
                <div className="text-sm text-gray-300">{request.period} • {request.days} hari</div>
              </div>
              <div className={`px-3 py-1 rounded-full text-xs font-medium ${
                request.status === 'Approved' ? 'bg-green-500/20 text-green-400' :
                request.status === 'Pending' ? 'bg-yellow-500/20 text-yellow-400' :
                'bg-red-500/20 text-red-400'
              }`}>
                {request.status}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="max-w-md mx-auto min-h-screen relative overflow-hidden">
        
        {/* Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
        </div>

        {/* Header */}
        <div className="px-6 pt-12 pb-6 text-center relative z-10">
          <h1 className="text-3xl font-bold mb-2 bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
            Creative Attendance
          </h1>
          <p className="text-gray-300">
            {userData?.name || 'Loading...'}
          </p>
        </div>

        {/* Tab Navigation */}
        <div className="px-6 mb-6 relative z-10">
          <div className="flex bg-white/5 backdrop-blur-xl rounded-2xl p-1 border border-white/10">
            {[
              { id: 'attendance', icon: Clock, label: 'Check' },
              { id: 'history', icon: History, label: 'History' },
              { id: 'stats', icon: TrendingUp, label: 'Stats' },
              { id: 'leave', icon: Calendar, label: 'Leave' }
            ].map((tab) => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex-1 flex items-center justify-center space-x-2 py-3 px-2 rounded-xl transition-all duration-300 ${
                    activeTab === tab.id
                      ? 'bg-gradient-to-r from-blue-500/30 to-purple-500/30 border border-blue-400/50 text-white'
                      : 'text-gray-400 hover:text-white hover:bg-white/5'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  <span className="text-sm font-medium">{tab.label}</span>
                </button>
              );
            })}
          </div>
        </div>

        {/* Tab Content */}
        <div className="px-6 pb-32 relative z-10">
          {activeTab === 'attendance' && renderAttendanceTab()}
          {activeTab === 'history' && renderHistoryTab()}
          {activeTab === 'stats' && renderStatsTab()}
          {activeTab === 'leave' && renderLeaveTab()}
        </div>

        {/* Face Camera Modal */}
        {showFaceCamera && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div className="bg-white/10 backdrop-blur-xl rounded-3xl p-8 border border-white/20 text-center max-w-sm mx-4">
              <div className="w-32 h-32 mx-auto mb-6 relative">
                <div className="w-full h-full border-4 border-blue-400 rounded-full animate-pulse flex items-center justify-center">
                  <Camera className="w-16 h-16 text-blue-400" />
                </div>
                <div className="absolute inset-0 border-4 border-transparent border-t-blue-400 rounded-full animate-spin"></div>
              </div>
              <h3 className="text-xl font-bold text-white mb-2">Verifikasi Wajah</h3>
              <p className="text-gray-300 mb-4">Posisikan wajah dalam frame kamera</p>
              <div className="text-blue-400 text-sm animate-pulse">Memproses...</div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CreativeAttendanceDashboard;