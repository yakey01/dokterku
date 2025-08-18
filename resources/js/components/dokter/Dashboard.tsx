import React, { useState, useEffect } from 'react';
import { Clock, DollarSign, User, Home, TrendingUp, Award, Target, Heart, Zap, Flame, Coffee, Moon, Sun, Crown, Star, Shield, Calendar, Loader2, Trophy, Medal } from 'lucide-react';
import doctorApi, { UserData, DoctorDashboardData, LeaderboardData } from '../../utils/doctorApi';
import { AttendanceProgressBar, PerformanceProgressBar } from './DynamicProgressBar';
import MedicalProgressDashboard from './MedicalProgressDashboard';
import JaspelCurrentMonthProgress from './JaspelCurrentMonthProgress';

interface DashboardProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

export function Dashboard({ userData, onNavigate }: DashboardProps) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [doctorLevel, setDoctorLevel] = useState(7);
  const [experiencePoints, setExperiencePoints] = useState(2847);
  const [nextLevelXP, setNextLevelXP] = useState(3000);
  const [dailyStreak, setDailyStreak] = useState(15);
  const [progressAnimationsComplete, setProgressAnimationsComplete] = useState(false);
  const [user, setUser] = useState<UserData | null>(null);
  const [dashboardData, setDashboardData] = useState<DoctorDashboardData | null>(null);
  const [leaderboardData, setLeaderboardData] = useState<LeaderboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentMonthJaspelData, setCurrentMonthJaspelData] = useState<any>(null);
  const [currentMonthJaspelLoading, setCurrentMonthJaspelLoading] = useState(false);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    fetchUserData();
  }, []);

  const fetchUserData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      // Fetch user data first
      const currentUser = await doctorApi.getCurrentUser();
      setUser(currentUser);
      
      // Then fetch dashboard data
      const dashboard = await doctorApi.getDashboard();
      setDashboardData(dashboard);
      
      // Fetch current month Jaspel data
      fetchCurrentMonthJaspel();
      
      // Fetch leaderboard data
      try {
        const leaderboard = await doctorApi.getLeaderboard();
        setLeaderboardData(leaderboard);
        
        // Update current user's level and XP from leaderboard if they're in it
        const currentUserInLeaderboard = leaderboard.leaderboard.find(
          doctor => doctor.id === currentUser.id
        );
        
        if (currentUserInLeaderboard) {
          setDoctorLevel(currentUserInLeaderboard.level);
          setExperiencePoints(currentUserInLeaderboard.xp);
          setDailyStreak(currentUserInLeaderboard.streak_days);
        }
      } catch (leaderboardErr) {
        console.error('Failed to fetch leaderboard:', leaderboardErr);
        // Continue without leaderboard data
      }
      
      // Update stats from dashboard data as fallback
      if (!leaderboardData && dashboard.performance?.attendance_rate) {
        // Update performance based on actual attendance rate
        const attendanceRate = dashboard.performance.attendance_rate;
        setDoctorLevel(Math.floor(attendanceRate / 10)); // Example: 96% = Level 9
      }
      
      if (!leaderboardData && dashboard.jaspel_summary?.current_month) {
        // Update XP based on current month's Jaspel (example conversion)
        const xp = Math.floor(dashboard.jaspel_summary.current_month / 1000);
        setExperiencePoints(xp);
      }
      
    } catch (err) {
      console.error('Failed to fetch data:', err);
      setError('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  const fetchCurrentMonthJaspel = async () => {
    try {
      setCurrentMonthJaspelLoading(true);
      console.log('📊 [Dashboard] Fetching current month Jaspel progress...');
      
      const response = await fetch('/api/v2/dashboards/dokter/jaspel/current-month', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      
      if (result.success && result.data) {
        console.log('✅ [Dashboard] Current month Jaspel data received:', result.data);
        setCurrentMonthJaspelData(result.data);
      } else {
        console.error('❌ [Dashboard] Failed to fetch current month Jaspel:', result.message);
      }
    } catch (error) {
      console.error('❌ [Dashboard] Error fetching current month Jaspel:', error);
    } finally {
      setCurrentMonthJaspelLoading(false);
    }
  };

  const formatTime = (date: Date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  };

  const getTimeGreeting = () => {
    const hour = currentTime.getHours();
    const title = userRole === 'dokter' ? 'Doctor' : userRole === 'paramedis' ? 'Paramedis' : 'User';
    if (hour < 12) return { greeting: `Good Morning, ${title}!`, icon: Sun, color: "from-amber-400 to-orange-500" };
    if (hour < 17) return { greeting: `Good Afternoon, ${title}!`, icon: Sun, color: "from-blue-400 to-cyan-500" };
    return { greeting: `Good Evening, ${title}!`, icon: Moon, color: "from-purple-400 to-indigo-500" };
  };

  const { greeting, icon: TimeIcon, color } = getTimeGreeting();

  // Get display name from user data
  const displayName = user?.pegawai?.nama_lengkap || user?.name || 'Doctor';
  const userRole = user?.role || 'dokter';

  if (loading) {
    return (
      <div className="w-full h-screen flex items-center justify-center bg-gray-900">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-blue-400 animate-spin mx-auto mb-4" />
          <p className="text-white text-lg">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="w-full h-screen flex items-center justify-center bg-gray-900">
        <div className="text-center">
          <div className="text-red-400 text-xl mb-4">⚠️</div>
          <p className="text-white text-lg">{error}</p>
          <button 
            onClick={fetchUserData}
            className="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full">
        {/* Main Content Container */}
        <div className="px-4 py-6">
          {/* Responsive Grid: Mobile Single Column, Tablet 2 Cols, Desktop 3 Cols */}
          <div className="content-grid grid grid-cols-1 gap-4 p-4 sm:gap-6 sm:p-6 md:grid-cols-2 md:gap-8 md:p-8 lg:grid-cols-3 lg:gap-10 lg:p-10 xl:max-w-7xl xl:mx-auto max-w-sm mx-auto lg:max-w-none">
        
          {/* Floating Background Elements - Fixed for all breakpoints */}
          <div className="fixed inset-0 overflow-hidden pointer-events-none">
            <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
            <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
            <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
          </div>

          {/* Doctor Profile & Stats Card - Takes full width on mobile, spans 2 cols on tablet+ */}
          <div className="col-span-1 md:col-span-2 lg:col-span-3 relative z-10">
            <div className="relative">
              <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
              <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
              <div className="relative p-8">
                
                {/* Level Badge & Avatar */}
                <div className="flex items-center justify-between mb-6">
                  <div className="flex items-center space-x-4">
                    <div className="relative">
                      <div className="w-20 h-20 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-2xl flex items-center justify-center relative overflow-hidden">
                        <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                        <Crown className="w-10 h-10 text-white relative z-10" />
                      </div>
                      <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-lg">
                        Lv.{doctorLevel}
                      </div>
                    </div>
                    <div>
                      <h1 className={`text-2xl font-bold bg-gradient-to-r ${color} bg-clip-text text-transparent mb-1`}>
                        {greeting}
                      </h1>
                      <p className="text-purple-200 text-lg">{displayName}</p>
                    </div>
                  </div>
                </div>

                {/* XP Progress */}
                <div className="mb-6">
                  <div className="flex justify-between text-sm mb-2">
                    <span className="text-cyan-300">Klinik Dokterku</span>
                    <span className="text-white font-semibold">Sahabat Menuju Sehat</span>
                  </div>
                  <PerformanceProgressBar
                    performance={(experiencePoints / nextLevelXP) * 100}
                    label=""
                    delay={300}
                    variant="info"
                  />
                </div>

                {/* Daily Stats */}
                <div className="grid grid-cols-3 gap-4">
                  <div className="text-center">
                    <div className="flex items-center justify-center mb-2">
                      <Flame className="w-5 h-5 text-orange-400 mr-2" />
                      <span className="text-2xl font-bold text-white">{dailyStreak}</span>
                    </div>
                    <span className="text-orange-300 text-sm">Day Streak</span>
                  </div>
                  <div className="text-center">
                    <div className="flex items-center justify-center mb-2">
                      <Star className="w-5 h-5 text-yellow-400 mr-2" />
                      <span className="text-2xl font-bold text-white">
                        {dashboardData?.performance?.attendance_rate || 0}%
                      </span>
                    </div>
                    <span className="text-yellow-300 text-sm">Attendance</span>
                  </div>
                  <div className="text-center">
                    <div className="flex items-center justify-center mb-2">
                      <Award className="w-5 h-5 text-purple-400 mr-2" />
                      <span className="text-2xl font-bold text-white">12</span>
                    </div>
                    <span className="text-purple-300 text-sm">Achievements</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Enhanced Progress Analytics - Full width on mobile, spans 2 cols on tablet+ */}
          <div className="col-span-1 md:col-span-2 lg:col-span-2 relative z-10">
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10 h-full">
              <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
                Performance Analytics
              </h3>
              
              <div className="space-y-6">
                {/* Current Month Jaspel Progress - NEW ANIMATED DISPLAY */}
                <JaspelCurrentMonthProgress 
                  data={currentMonthJaspelData} 
                  loading={currentMonthJaspelLoading} 
                />
                
                {/* Attendance Progress with Dynamic Animation */}
                <div className="p-4 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl border border-blue-400/20">
                  <div className="flex items-center space-x-3 mb-3">
                    <Calendar className="w-5 h-5 text-blue-400" />
                    <div className="font-medium text-white">Attendance Performance</div>
                  </div>
                  <AttendanceProgressBar
                    attendanceRate={dashboardData?.performance?.attendance_rate || 96.7}
                    delay={800}
                  />
                  <div className="text-blue-300 text-sm mt-1">
                    {Math.round((dashboardData?.performance?.attendance_rate || 96.7) * 30 / 100)}/30 days this month
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Leaderboard Card - Full width on mobile, 1 col on tablet+ */}
          <div className="col-span-1 relative z-10">
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10 h-full">
              <div className="flex items-center justify-between mb-6">
                <h3 className="text-xl font-bold bg-gradient-to-r from-pink-400 to-purple-400 bg-clip-text text-transparent">
                  Top 3 Dokter
                </h3>
                <Trophy className="w-6 h-6 text-yellow-400" />
              </div>
              
              {leaderboardData && leaderboardData.leaderboard.length > 0 ? (
                <div className="space-y-4">
                  {leaderboardData.leaderboard.map((doctor, index) => {
                    const isCurrentUser = doctor.id === user?.id;
                    const rankColors = {
                      1: 'from-yellow-500/30 to-amber-500/30 border-yellow-400/50',
                      2: 'from-gray-400/30 to-slate-500/30 border-gray-400/50',
                      3: 'from-orange-500/30 to-amber-600/30 border-orange-400/50'
                    };
                    const badgeColors = {
                      1: 'from-yellow-500 to-amber-500',
                      2: 'from-gray-500 to-slate-600',
                      3: 'from-orange-500 to-amber-600'
                    };
                    const textColors = {
                      1: 'text-yellow-300',
                      2: 'text-gray-300',
                      3: 'text-orange-300'
                    };
                    const xpColors = {
                      1: 'text-yellow-400',
                      2: 'text-gray-400',
                      3: 'text-orange-400'
                    };
                    
                    return (
                      <div 
                        key={doctor.id}
                        className={`flex items-center space-x-4 bg-gradient-to-r ${rankColors[doctor.rank] || rankColors[3]} rounded-2xl p-4 border-2 ${isCurrentUser ? 'ring-2 ring-green-400' : ''}`}
                      >
                        <div className={`w-12 h-12 bg-gradient-to-br ${badgeColors[doctor.rank] || badgeColors[3]} rounded-xl flex items-center justify-center font-bold text-white text-lg`}>
                          <span className="text-2xl">{doctor.badge}</span>
                        </div>
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <div className="font-bold text-white">
                              {doctor.name}
                            </div>
                            {isCurrentUser && (
                              <span className="text-xs bg-green-500/30 text-green-300 px-2 py-1 rounded-full">
                                Anda
                              </span>
                            )}
                          </div>
                          <div className={textColors[doctor.rank] || textColors[3]}>
                            Level {doctor.level} • {doctor.attendance_rate}% Kehadiran
                          </div>
                          <div className="text-xs text-gray-400 mt-1">
                            {doctor.total_days} hari • {doctor.streak_days} hari berturut-turut
                          </div>
                        </div>
                        <div className="text-right">
                          <div className={`text-2xl font-bold ${xpColors[doctor.rank] || xpColors[3]}`}>
                            {doctor.xp.toLocaleString()} XP
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <div className="text-center py-8">
                  <Medal className="w-12 h-12 text-gray-500 mx-auto mb-3" />
                  <p className="text-gray-400">Loading leaderboard...</p>
                </div>
              )}
              
              {leaderboardData && (
                <div className="mt-4 pt-4 border-t border-white/10">
                  <p className="text-xs text-gray-400 text-center">
                    Periode: {leaderboardData.month} • {leaderboardData.working_days} hari kerja
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Quick Actions Panel - Desktop only (hidden on mobile) */}
          <div className="hidden lg:block col-span-1 relative z-10">
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10 h-full">
              <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">
                Quick Actions
              </h3>
              
              <div className="space-y-4">
                <button 
                  onClick={() => onNavigate?.('jadwal')}
                  className="w-full flex items-center space-x-4 p-4 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-2xl border border-blue-400/30 hover:from-blue-500/30 hover:to-cyan-500/30 transition-all duration-300"
                >
                  <Calendar className="w-8 h-8 text-blue-400" />
                  <div className="text-left">
                    <div className="font-medium text-white">Mission Central</div>
                    <div className="text-blue-300 text-sm">View today's schedule</div>
                  </div>
                </button>

                <button 
                  onClick={() => onNavigate?.('presensi')}
                  className="w-full flex items-center space-x-4 p-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl border border-green-400/30 hover:from-green-500/30 hover:to-emerald-500/30 transition-all duration-300"
                >
                  <Clock className="w-8 h-8 text-green-400" />
                  <div className="text-left">
                    <div className="font-medium text-white">Smart Attendance</div>
                    <div className="text-green-300 text-sm">Check in/out</div>
                  </div>
                </button>

                <button 
                  onClick={() => onNavigate?.('jaspel')}
                  className="w-full flex items-center space-x-4 p-4 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-2xl border border-purple-400/30 hover:from-purple-500/30 hover:to-pink-500/30 transition-all duration-300"
                >
                  <DollarSign className="w-8 h-8 text-purple-400" />
                  <div className="text-left">
                    <div className="font-medium text-white">Rewards Center</div>
                    <div className="text-purple-300 text-sm">View Jaspel</div>
                  </div>
                </button>
              </div>
            </div>
          </div>

          {/* Stats Overview - Spans full width on mobile, 2 cols on tablet, 3 cols on desktop */}
          <div className="col-span-1 md:col-span-2 lg:col-span-3 relative z-10">
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
              <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent">
                Today's Mission Overview
              </h3>
              
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="text-center p-4 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 rounded-2xl border border-blue-400/20">
                  <Heart className="w-8 h-8 text-blue-400 mx-auto mb-2" />
                  <div className="text-2xl font-bold text-white">
                    {dashboardData?.patient_count?.today || 0}
                  </div>
                  <div className="text-blue-300 text-sm">Patients Today</div>
                </div>

                <div className="text-center p-4 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-2xl border border-green-400/20">
                  <Zap className="w-8 h-8 text-green-400 mx-auto mb-2" />
                  <div className="text-2xl font-bold text-white">
                    {dashboardData?.procedures_count?.today || 0}
                  </div>
                  <div className="text-green-300 text-sm">Procedures</div>
                </div>

                <div className="text-center p-4 bg-gradient-to-br from-purple-500/10 to-pink-500/10 rounded-2xl border border-purple-400/20">
                  <Shield className="w-8 h-8 text-purple-400 mx-auto mb-2" />
                  <div className="text-2xl font-bold text-white">95%</div>
                  <div className="text-purple-300 text-sm">Success Rate</div>
                </div>

                <div className="text-center p-4 bg-gradient-to-br from-orange-500/10 to-red-500/10 rounded-2xl border border-orange-400/20">
                  <Coffee className="w-8 h-8 text-orange-400 mx-auto mb-2" />
                  <div className="text-2xl font-bold text-white">6h</div>
                  <div className="text-orange-300 text-sm">Active Hours</div>
                </div>
              </div>
            </div>
          </div>

          </div>
          {/* End of content-grid */}
        </div>
        {/* End of main content container */}
    </div>
  );
}