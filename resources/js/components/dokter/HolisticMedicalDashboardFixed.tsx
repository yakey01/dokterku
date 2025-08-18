import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { Calendar, Clock, DollarSign, Award, Brain, Star, Crown, Flame, Moon, Sun, HeartCrack, User } from 'lucide-react';
import JadwalJaga from './JadwalJaga';
import CreativeAttendanceDashboard from './Presensi';
import JaspelComponent from './Jaspel';
import ProfileComponent from './Profil';
import doctorApi from '../../utils/doctorApi';
import { performanceMonitor } from '../../utils/PerformanceMonitor';
import AttendanceCalculator from '../../utils/AttendanceCalculator';
import ErrorBoundary from '../ErrorBoundary';
import { safeGet, safeHas } from '../../utils/SafeObjectAccess';

interface HolisticMedicalDashboardProps {
  userData?: {
    name: string;
    email: string;
    greeting?: string;
    role?: string;
    initials?: string;
  };
}

interface DashboardMetrics {
  jaspel: {
    currentMonth: number;
    previousMonth: number;
    growthPercentage: number;
    progressPercentage: number;
  };
  attendance: {
    rate: number;
    daysPresent: number;
    totalDays: number;
    displayText: string;
  };
  patients: {
    today: number;
    thisMonth: number;
  };
  shifts: {
    today: boolean;
    thisMonth: number;
    thisWeek: number;
  };
}

interface LoadingState {
  dashboard: boolean;
  error: string | null;
}

const HolisticMedicalDashboardFixed: React.FC<HolisticMedicalDashboardProps> = ({ userData }) => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [doctorLevel, setDoctorLevel] = useState(7);
  const [experiencePoints, setExperiencePoints] = useState(2847);
  const [nextLevelXP, setNextLevelXP] = useState(3000);
  const [dailyStreak, setDailyStreak] = useState(15);
  const [activeTab, setActiveTab] = useState('home');
  const [leaderboardData, setLeaderboardData] = useState<any[]>([]);
  const [leaderboardLoading, setLeaderboardLoading] = useState(true);
  const [doctorInfo, setDoctorInfo] = useState<any>(null);
  const [dashboardMetrics, setDashboardMetrics] = useState<DashboardMetrics>({
    jaspel: {
      currentMonth: 0,
      previousMonth: 0,
      growthPercentage: 0,
      progressPercentage: 0,
    },
    attendance: {
      rate: 0,
      daysPresent: 0,
      totalDays: 0,
      displayText: '0%',
    },
    patients: {
      today: 0,
      thisMonth: 0,
    },
    shifts: {
      today: false,
      thisMonth: 0,
      thisWeek: 0,
    }
  });
  const [loading, setLoading] = useState<LoadingState>({
    dashboard: true,
    error: null,
  });

  // Fetch dashboard data from API
  const fetchDashboardData = useCallback(async () => {
    try {
      const response = await doctorApi.getDashboardData();
      
      if (response.success && response.data) {
        const { stats, user } = response.data;
        
        // Update doctor info from API
        if (user) {
          setDoctorInfo({
            name: user.name || userData?.name || 'Doctor',
            email: user.email || userData?.email,
            role: user.jabatan || 'Dokter',
            spesialis: user.spesialis,
            nomor_sip: user.nomor_sip
          });
        }
        
        // Update dashboard metrics with actual data
        setDashboardMetrics(prev => ({
          ...prev,
          patients: {
            today: stats.patients_today || 0,
            thisMonth: stats.patients_month || 0, // Use the new patients_month field
          },
          shifts: {
            today: stats.attendance_today ? true : false,
            thisMonth: stats.shifts_month || 8, // Use actual shifts data
            thisWeek: stats.shifts_week || 0,
          },
          jaspel: {
            ...prev.jaspel,
            currentMonth: stats.jaspel_month || 0,
          },
          attendance: {
            ...prev.attendance,
            rate: stats.attendance_rate || 95,
            displayText: `${stats.attendance_rate || 95}%`,
          }
        }));
        
        // Set daily streak based on shifts this month
        setDailyStreak(stats.shifts_month || 8);
        
        setLoading({ dashboard: false, error: null });
      }
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      setLoading({ dashboard: false, error: 'Failed to load data' });
    }
  }, [userData]);

  useEffect(() => {
    fetchDashboardData();
    const interval = setInterval(fetchDashboardData, 60000); // Refresh every minute
    return () => clearInterval(interval);
  }, [fetchDashboardData]);

  // Update time every second
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Get personalized greeting based on time and doctor info
  const getPersonalizedGreeting = useCallback(() => {
    const hour = currentTime.getHours();
    const doctorName = doctorInfo?.name || userData?.name || 'Doctor';
    
    // Extract first name or title
    const firstName = doctorName.split(' ')[0] || 'Doctor';
    
    let timeGreeting = '';
    let icon = Sun;
    let color = '';
    
    if (hour < 12) {
      timeGreeting = 'Selamat Pagi';
      icon = Sun;
      color = 'from-amber-400 to-orange-500';
    } else if (hour < 17) {
      timeGreeting = 'Selamat Siang';
      icon = Sun;
      color = 'from-blue-400 to-cyan-500';
    } else {
      timeGreeting = 'Selamat Malam';
      icon = Moon;
      color = 'from-purple-400 to-indigo-500';
    }
    
    // Personalized greeting with doctor's name
    const greeting = `${timeGreeting}, ${firstName}!`;
    
    return { greeting, icon, color };
  }, [currentTime, doctorInfo, userData]);

  const { greeting, icon: TimeIcon, color } = useMemo(() => getPersonalizedGreeting(), [getPersonalizedGreeting]);

  // Render main dashboard
  const renderMainDashboard = useCallback(() => (
    <>
      {/* Doctor Level Card with Personalized Info */}
      <div className="px-6 pt-8 pb-6 relative z-10">
        <div className="relative">
          <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
          <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
          <div className="relative p-8">
            
            {/* Level Badge & Avatar with Personalized Greeting */}
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center space-x-4">
                <div className="relative">
                  <div className="w-20 h-20 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-2xl flex items-center justify-center relative overflow-hidden">
                    <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                    {/* Show User icon for doctor */}
                    <User className="w-10 h-10 text-white relative z-10" />
                  </div>
                  <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-lg animate-pulse">
                    Lv.{doctorLevel}
                  </div>
                </div>
                <div>
                  <h1 className={`text-2xl md:text-3xl lg:text-4xl font-bold bg-gradient-to-r ${color} bg-clip-text text-transparent mb-1`}>
                    {greeting}
                  </h1>
                  <p className="text-purple-200 text-lg md:text-xl">
                    {doctorInfo?.name || userData?.name || 'Doctor'}
                  </p>
                  {doctorInfo?.spesialis && (
                    <p className="text-purple-300 text-sm">{doctorInfo.spesialis}</p>
                  )}
                </div>
              </div>
            </div>

            {/* Clinic Info */}
            <div className="mb-6">
              <div className="flex justify-between text-sm mb-2">
                <span className="text-cyan-300">Klinik Dokterku</span>
                <span className="text-white font-semibold">
                  {dashboardMetrics.shifts.today ? '🟢 Sedang Jaga' : '⚪ Tidak Jaga'}
                </span>
              </div>
            </div>

            {/* Daily Stats with Actual Data */}
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  <Flame className="w-5 h-5 text-orange-400 mr-2" />
                  <span className="text-xl font-bold text-white">{dashboardMetrics.shifts.thisMonth}</span>
                </div>
                <span className="text-orange-300 text-sm">Jadwal Jaga</span>
              </div>
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  <Star className="w-5 h-5 text-yellow-400 mr-2" />
                  <span className="text-xl font-bold text-white">
                    {dashboardMetrics.attendance.displayText}
                  </span>
                </div>
                <span className="text-yellow-300 text-sm">Kehadiran</span>
              </div>
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  <Award className="w-5 h-5 text-purple-400 mr-2" />
                  <span className="text-xl font-bold text-white">
                    {dashboardMetrics.patients.thisMonth}
                  </span>
                </div>
                <span className="text-purple-300 text-sm">Pasien Bulan Ini</span>
              </div>
            </div>

            {/* Today's Patient Count */}
            {dashboardMetrics.patients.today > 0 && (
              <div className="mt-4 p-3 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl border border-green-400/30">
                <div className="flex items-center justify-between">
                  <span className="text-green-300 text-sm">Pasien Hari Ini</span>
                  <span className="text-white font-bold text-lg">{dashboardMetrics.patients.today}</span>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Doctor Analytics */}
      <div className="px-6 mb-8 relative z-10">
        <h3 className="text-xl md:text-2xl font-bold mb-6 text-center bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
          Statistik Dokter
        </h3>
        
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          {/* Jaspel Achievement */}
          <div className="space-y-4">
            <h4 className="font-semibold text-white mb-4">Pencapaian Bulan Ini</h4>
            
            <div className="p-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl border border-green-400/30">
              <div className="flex items-center space-x-4 mb-3">
                <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
                <div className="flex-1">
                  <div className="font-medium text-white">Total JASPEL</div>
                  <div className="text-2xl font-bold text-green-300">
                    Rp {new Intl.NumberFormat('id-ID').format(dashboardMetrics.jaspel.currentMonth)}
                  </div>
                </div>
              </div>
            </div>

            {/* Attendance Achievement */}
            <div className="p-4 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-2xl border border-blue-400/30">
              <div className="flex items-center space-x-4 mb-3">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                  <Calendar className="w-5 h-5 text-white" />
                </div>
                <div className="flex-1">
                  <div className="font-medium text-white">Tingkat Kehadiran</div>
                  <div className="text-xl font-bold text-blue-300">
                    {dashboardMetrics.attendance.displayText}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  ), [greeting, color, doctorLevel, dashboardMetrics, doctorInfo]);

  // Tab content renderer
  const renderTabContent = useCallback(() => {
    switch(activeTab) {
      case 'home':
        return renderMainDashboard();
      case 'missions':
        return <JadwalJaga />;
      case 'presensi':
        return <CreativeAttendanceDashboard />;
      case 'jaspel':
        return <JaspelComponent />;
      case 'profile':
        return <ProfileComponent userData={doctorInfo || userData} />;
      default:
        return renderMainDashboard();
    }
  }, [activeTab, renderMainDashboard, doctorInfo, userData]);

  // Bottom navigation
  const renderBottomNavigation = () => (
    <div className="fixed bottom-0 left-0 right-0 bg-gradient-to-t from-black via-slate-900/95 to-transparent">
      <div className="flex justify-around items-center py-4 px-4">
        {/* Navigation buttons remain the same */}
        <button 
          onClick={() => setActiveTab('home')}
          className={`p-3 rounded-2xl ${activeTab === 'home' ? 'bg-purple-600/20' : ''}`}
        >
          <Brain className="w-5 h-5 text-white" />
          <span className="text-xs text-white">Home</span>
        </button>
        
        <button 
          onClick={() => setActiveTab('missions')}
          className={`p-3 rounded-2xl ${activeTab === 'missions' ? 'bg-purple-600/20' : ''}`}
        >
          <Calendar className="w-5 h-5 text-white" />
          <span className="text-xs text-white">Jadwal</span>
        </button>
        
        <button 
          onClick={() => setActiveTab('presensi')}
          className={`p-3 rounded-2xl ${activeTab === 'presensi' ? 'bg-purple-600/20' : ''}`}
        >
          <Clock className="w-5 h-5 text-white" />
          <span className="text-xs text-white">Presensi</span>
        </button>
        
        <button 
          onClick={() => setActiveTab('jaspel')}
          className={`p-3 rounded-2xl ${activeTab === 'jaspel' ? 'bg-purple-600/20' : ''}`}
        >
          <DollarSign className="w-5 h-5 text-white" />
          <span className="text-xs text-white">Jaspel</span>
        </button>
        
        <button 
          onClick={() => setActiveTab('profile')}
          className={`p-3 rounded-2xl ${activeTab === 'profile' ? 'bg-purple-600/20' : ''}`}
        >
          <User className="w-5 h-5 text-white" />
          <span className="text-xs text-white">Profile</span>
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="min-h-screen relative overflow-hidden">
        {/* Tab Content */}
        <div className="relative z-10">
          {renderTabContent()}
        </div>

        {/* Bottom Navigation */}
        {renderBottomNavigation()}
      </div>
    </div>
  );
};

export default HolisticMedicalDashboardFixed;