import React, { useState, useEffect } from 'react';
import { 
  MapPin, 
  Clock, 
  Shield, 
  CheckCircle, 
  XCircle, 
  AlertTriangle, 
  User, 
  Calendar, 
  Activity, 
  Target, 
  Zap, 
  Award,
  Navigation,
  Wifi,
  Battery,
  Signal,
  Eye,
  Lock,
  Camera,
  Fingerprint,
  Star,
  TrendingUp,
  Heart,
  Coffee
} from 'lucide-react';

interface PresensiProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

interface AttendanceRecord {
  id: string;
  date: string;
  checkIn: string;
  checkOut?: string;
  location: string;
  status: 'present' | 'late' | 'absent';
  guardianScore: number;
}

export function Presensi({ userData, onNavigate }: PresensiProps) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [guardianLevel, setGuardianLevel] = useState(4);
  const [attendanceScore, setAttendanceScore] = useState(94.7);
  const [gpsAccuracy, setGpsAccuracy] = useState(12);
  const [isLocationVerified, setIsLocationVerified] = useState(true);
  const [attendanceStreak, setAttendanceStreak] = useState(28);
  const [totalGuardianPoints, setTotalGuardianPoints] = useState(1847);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const formatTime = (date: Date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit',
      second: '2-digit'
    });
  };

  const formatDate = (date: Date) => {
    return date.toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const mockAttendanceHistory: AttendanceRecord[] = [
    {
      id: '1',
      date: '2024-08-04',
      checkIn: '07:45',
      checkOut: '17:30',
      location: 'Klinik Utama',
      status: 'present',
      guardianScore: 100
    },
    {
      id: '2',
      date: '2024-08-03',
      checkIn: '08:15',
      checkOut: '17:15',
      location: 'Klinik Utama',
      status: 'late',
      guardianScore: 85
    },
    {
      id: '3',
      date: '2024-08-02',
      checkIn: '07:30',
      checkOut: '18:00',
      location: 'Klinik Utama',
      status: 'present',
      guardianScore: 100
    }
  ];

  const handleCheckIn = () => {
    if (!isCheckedIn) {
      setIsCheckedIn(true);
      setTotalGuardianPoints(prev => prev + 50);
    }
  };

  const handleCheckOut = () => {
    if (isCheckedIn) {
      setIsCheckedIn(false);
      setTotalGuardianPoints(prev => prev + 100);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'present':
        return <CheckCircle className="w-5 h-5 text-green-400" />;
      case 'late':
        return <AlertTriangle className="w-5 h-5 text-yellow-400" />;
      case 'absent':
        return <XCircle className="w-5 h-5 text-red-400" />;
      default:
        return <Clock className="w-5 h-5 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'present':
        return 'from-green-500/30 to-emerald-500/30 border-green-400/30';
      case 'late':
        return 'from-yellow-500/30 to-amber-500/30 border-yellow-400/30';
      case 'absent':
        return 'from-red-500/30 to-pink-500/30 border-red-400/30';
      default:
        return 'from-gray-500/30 to-slate-500/30 border-gray-400/30';
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-y-auto">
        <div className="pb-32 lg:pb-16">
          <div className="max-w-sm mx-auto min-h-screen relative overflow-hidden">
        
        {/* Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-green-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-purple-500/5 rounded-full blur-3xl animate-pulse"></div>
        </div>

        {/* Header */}
        <div className="px-6 pt-8 pb-6 relative z-10">
          <div className="text-center mb-6">
            <div className="flex items-center justify-center mb-4">
              <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg border-2 border-green-400">
                <Shield className="w-8 h-8 text-white" />
              </div>
            </div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-green-400 to-emerald-400 bg-clip-text text-transparent mb-2">
              Guardian Protocol
            </h1>
            <p className="text-green-200 text-lg">Smart Attendance System</p>
          </div>
        </div>

        {/* Guardian Status Card */}
        <div className="px-6 mb-8 relative z-10">
          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-br from-green-600/30 via-emerald-600/30 to-teal-600/30 rounded-3xl backdrop-blur-2xl"></div>
            <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
            <div className="relative p-8">
              
              {/* Current Time & Date */}
              <div className="text-center mb-6">
                <div className="text-4xl font-bold text-white mb-2">
                  {formatTime(currentTime)}
                </div>
                <div className="text-green-300 text-sm">
                  {formatDate(currentTime)}
                </div>
              </div>

              {/* Guardian Level */}
              <div className="flex items-center justify-between mb-6">
                <div className="flex items-center space-x-4">
                  <div className="relative">
                    <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center relative overflow-hidden border-2 border-green-400">
                      <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                      <Shield className="w-8 h-8 text-white relative z-10" />
                    </div>
                    <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-lg">
                      Lv.{guardianLevel}
                    </div>
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-white mb-1">Guardian Status</h3>
                    <p className="text-green-200">Dr. Naning Paramedis</p>
                  </div>
                </div>
              </div>

              {/* Location Status */}
              <div className="mb-6">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center space-x-2">
                    <MapPin className="w-5 h-5 text-green-400" />
                    <span className="text-white font-medium">Location Verified</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span className="text-green-300 text-sm">{gpsAccuracy}m accuracy</span>
                  </div>
                </div>
                <div className="bg-green-500/20 rounded-2xl p-4 border border-green-400/30">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-white font-medium">Klinik Dokterku</div>
                      <div className="text-green-300 text-sm">Jl. Kesehatan No. 123</div>
                    </div>
                    <Navigation className="w-6 h-6 text-green-400" />
                  </div>
                </div>
              </div>

              {/* Check-in/Check-out Buttons */}
              <div className="grid grid-cols-2 gap-4 mb-6">
                <button
                  onClick={handleCheckIn}
                  disabled={isCheckedIn}
                  className={`relative overflow-hidden rounded-2xl p-4 transition-all duration-300 ${
                    isCheckedIn 
                      ? 'bg-green-500/30 border border-green-400/50' 
                      : 'bg-gradient-to-br from-green-600/40 to-emerald-600/40 hover:from-green-500/50 hover:to-emerald-500/50 border border-green-400/30 hover:border-green-300/50'
                  }`}
                >
                  <div className="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                  <div className="relative flex flex-col items-center space-y-2">
                    <CheckCircle className={`w-6 h-6 ${isCheckedIn ? 'text-green-300' : 'text-white'}`} />
                    <span className={`text-sm font-medium ${isCheckedIn ? 'text-green-300' : 'text-white'}`}>
                      {isCheckedIn ? 'Checked In' : 'Check In'}
                    </span>
                    {isCheckedIn && <span className="text-xs text-green-400">07:45</span>}
                  </div>
                </button>

                <button
                  onClick={handleCheckOut}
                  disabled={!isCheckedIn}
                  className={`relative overflow-hidden rounded-2xl p-4 transition-all duration-300 ${
                    !isCheckedIn 
                      ? 'bg-gray-500/20 border border-gray-400/30' 
                      : 'bg-gradient-to-br from-orange-600/40 to-red-600/40 hover:from-orange-500/50 hover:to-red-500/50 border border-orange-400/30 hover:border-orange-300/50'
                  }`}
                >
                  <div className="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                  <div className="relative flex flex-col items-center space-y-2">
                    <XCircle className={`w-6 h-6 ${!isCheckedIn ? 'text-gray-400' : 'text-white'}`} />
                    <span className={`text-sm font-medium ${!isCheckedIn ? 'text-gray-400' : 'text-white'}`}>
                      Check Out
                    </span>
                  </div>
                </button>
              </div>

              {/* Guardian Stats */}
              <div className="grid grid-cols-3 gap-4">
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <TrendingUp className="w-5 h-5 text-green-400 mr-2" />
                    <span className="text-2xl font-bold text-white">{attendanceScore}%</span>
                  </div>
                  <span className="text-green-300 text-sm">Guardian Score</span>
                </div>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Coffee className="w-5 h-5 text-blue-400 mr-2" />
                    <span className="text-2xl font-bold text-white">{attendanceStreak}</span>
                  </div>
                  <span className="text-blue-300 text-sm">Day Streak</span>
                </div>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Star className="w-5 h-5 text-yellow-400 mr-2" />
                    <span className="text-2xl font-bold text-white">{totalGuardianPoints}</span>
                  </div>
                  <span className="text-yellow-300 text-sm">GP Points</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Security Features */}
        <div className="px-6 mb-8 relative z-10">
          <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
            Security Protocol
          </h3>

          <div className="grid grid-cols-2 gap-4">
            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
              <div className="flex items-center space-x-3 mb-3">
                <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                  <Eye className="w-4 h-4 text-white" />
                </div>
                <span className="text-white font-medium text-sm">Face Recognition</span>
              </div>
              <div className="text-cyan-300 text-xs">Active & Verified</div>
            </div>

            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
              <div className="flex items-center space-x-3 mb-3">
                <div className="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                  <Fingerprint className="w-4 h-4 text-white" />
                </div>
                <span className="text-white font-medium text-sm">Biometric</span>
              </div>
              <div className="text-purple-300 text-xs">Enabled</div>
            </div>

            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
              <div className="flex items-center space-x-3 mb-3">
                <div className="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                  <Lock className="w-4 h-4 text-white" />
                </div>
                <span className="text-white font-medium text-sm">Encryption</span>
              </div>
              <div className="text-green-300 text-xs">AES-256</div>
            </div>

            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
              <div className="flex items-center space-x-3 mb-3">
                <div className="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                  <Signal className="w-4 h-4 text-white" />
                </div>
                <span className="text-white font-medium text-sm">Anti-Spoofing</span>
              </div>
              <div className="text-orange-300 text-xs">Protected</div>
            </div>
          </div>
        </div>

        {/* Attendance History */}
        <div className="px-6 relative z-10">
          <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            Guardian History
          </h3>

          <div className="space-y-4">
            {mockAttendanceHistory.map((record) => (
              <div 
                key={record.id}
                className={`bg-gradient-to-r ${getStatusColor(record.status)} rounded-2xl p-4 border backdrop-blur-sm`}
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center space-x-3">
                    {getStatusIcon(record.status)}
                    <div>
                      <div className="text-white font-medium text-sm">
                        {new Date(record.date).toLocaleDateString('id-ID', { 
                          weekday: 'short', 
                          month: 'short', 
                          day: 'numeric' 
                        })}
                      </div>
                      <div className="text-gray-300 text-xs">{record.location}</div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-white font-bold text-sm">{record.guardianScore} GP</div>
                    <div className="text-xs text-gray-300 capitalize">{record.status}</div>
                  </div>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <div className="text-gray-300">
                    In: {record.checkIn} {record.checkOut && `• Out: ${record.checkOut}`}
                  </div>
                  <div className="flex items-center space-x-1">
                    <Award className="w-3 h-3 text-yellow-400" />
                    <span className="text-yellow-300">{record.guardianScore}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
          </div>
        </div>
        {/* End of main content container */}
        
        {/* Medical RPG Bottom Navigation */}
      </div>
    </div>
  );
}