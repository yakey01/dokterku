import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Crown, Sun, Moon, Calendar, Clock, Users, DollarSign, Activity, MapPin, Bell, Settings } from 'lucide-react';
import JadwalJaga from './JadwalJaga';
import doctorApi from '../../utils/doctorApi';

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
}

interface LoadingState {
  dashboard: boolean;
  error: string | null;
}

const HolisticMedicalDashboardSimple: React.FC<HolisticMedicalDashboardProps> = ({ userData }) => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [activeTab, setActiveTab] = useState<'dashboard' | 'missions' | 'presensi' | 'jaspel' | 'profile'>('dashboard');
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
      totalDays: 30,
      displayText: '0%',
    },
    patients: {
      today: 0,
      thisMonth: 0,
    },
  });
  const [loading, setLoading] = useState<LoadingState>({ dashboard: false, error: null });

  // Simple dashboard data fetching - NO RETRY LOGIC
  useEffect(() => {
    let isMounted = true;

    const fetchDashboardData = async () => {
      try {
        if (!isMounted) return;
        
        setLoading({ dashboard: true, error: null });
        
        console.log('HolisticMedicalDashboardSimple: Starting dashboard data fetch...');
        const dashboardData = await doctorApi.getDashboard();
        
        if (!isMounted) return;
        
        if (dashboardData) {
          console.log('HolisticMedicalDashboardSimple: Dashboard data received:', dashboardData);
          
          // Calculate jaspel growth percentage
          const currentJaspel = dashboardData.jaspel_summary?.current_month || 0;
          const previousJaspel = dashboardData.jaspel_summary?.last_month || 0;
          const growthPercentage = previousJaspel > 0 
            ? ((currentJaspel - previousJaspel) / previousJaspel) * 100
            : 0;
          
          // Calculate jaspel progress percentage (normalized to 0-100)
          const progressPercentage = Math.min(Math.max((currentJaspel / 10000000) * 100, 0), 100);
          
          // Format attendance data
          const attendanceRate = dashboardData.performance?.attendance_rate || 0;
          const totalDays = 30;
          const daysPresent = Math.round((attendanceRate / 100) * totalDays);
          
          // Update metrics only if component is still mounted
          if (isMounted) {
            setDashboardMetrics({
              jaspel: {
                currentMonth: currentJaspel,
                previousMonth: previousJaspel,
                growthPercentage: Math.round(growthPercentage * 10) / 10,
                progressPercentage: Math.round(progressPercentage * 10) / 10,
              },
              attendance: {
                rate: attendanceRate,
                daysPresent: daysPresent,
                totalDays: totalDays,
                displayText: `${attendanceRate}%`,
              },
              patients: {
                today: dashboardData.patient_count?.today || 0,
                thisMonth: dashboardData.patient_count?.this_month || 0,
              },
            });
            
            console.log('HolisticMedicalDashboardSimple: Dashboard metrics updated successfully');
          }
        }
      } catch (error) {
        if (!isMounted) return;
        
        const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
        console.error('HolisticMedicalDashboardSimple: Error fetching dashboard data:', error);
        
        setLoading({ dashboard: false, error: `Failed to load dashboard data: ${errorMessage}` });
        
        // Set fallback data on error
        if (isMounted) {
          setDashboardMetrics({
            jaspel: {
              currentMonth: 0,
              previousMonth: 0,
              growthPercentage: 0,
              progressPercentage: 0,
            },
            attendance: {
              rate: 0,
              daysPresent: 0,
              totalDays: 30,
              displayText: '0%',
            },
            patients: {
              today: 0,
              thisMonth: 0,
            },
          });
          
          console.log('HolisticMedicalDashboardSimple: Fallback data set due to error');
        }
      } finally {
        if (isMounted) {
          setLoading({ dashboard: false, error: null });
        }
      }
    };

    fetchDashboardData();

    // Cleanup function
    return () => {
      isMounted = false;
    };
  }, []); // Empty dependency array - only run once on mount

  // Update current time
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Memoized time formatting
  const formatTime = useCallback((date: Date): string => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  }, []);

  // Memoized greeting calculation
  const getTimeGreeting = useCallback(() => {
    const hour = currentTime.getHours();
    if (hour < 12) return { greeting: "Good Morning, Doctor!", icon: Sun, color: "from-amber-400 to-orange-500" };
    if (hour < 17) return { greeting: "Good Afternoon, Doctor!", icon: Sun, color: "from-blue-400 to-cyan-500" };
    return { greeting: "Good Evening, Doctor!", icon: Moon, color: "from-purple-400 to-indigo-500" };
  }, [currentTime]);

  // Memoized tab content rendering
  const renderTabContent = useCallback(() => {
    switch (activeTab) {
      case 'missions':
        return (
          <div className="w-full">
            <JadwalJaga userData={userData} onNavigate={(tab: string) => setActiveTab(tab as any)} />
          </div>
        );
      case 'presensi':
        return (
          <div className="w-full px-6 pt-8 pb-6">
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
              <h2 className="text-xl font-bold text-white mb-4">Presensi</h2>
              <p className="text-purple-200">Fitur presensi akan segera tersedia</p>
            </div>
          </div>
        );
      case 'jaspel':
        return (
          <div className="w-full px-6 pt-8 pb-6">
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
              <h2 className="text-xl font-bold text-white mb-4">Jaspel</h2>
              <p className="text-purple-200">Fitur jaspel akan segera tersedia</p>
            </div>
          </div>
        );
      case 'profile':
        return (
          <div className="w-full px-6 pt-8 pb-6">
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
              <h2 className="text-xl font-bold text-white mb-4">Profile</h2>
              <p className="text-purple-200">Fitur profile akan segera tersedia</p>
            </div>
          </div>
        );
      default:
        return renderMainDashboard();
    }
  }, [activeTab, userData]);

  // Memoized greeting calculation
  const { greeting, icon: TimeIcon, color } = useMemo(() => getTimeGreeting(), [getTimeGreeting]);

  const renderMainDashboard = () => (
    <>
      {/* Loading State */}
      {loading.dashboard && (
        <div className="px-6 pt-8 pb-6 relative z-10">
          <div className="text-center py-8">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-400 mx-auto mb-4"></div>
            <p className="text-purple-300">Loading dashboard data...</p>
          </div>
        </div>
      )}
      
      {/* Error State */}
      {loading.error && (
        <div className="px-6 pt-8 pb-6 relative z-10">
          <div className="bg-red-900/30 border border-red-500/30 rounded-2xl p-4 text-center">
            <p className="text-red-300 mb-2">Failed to load dashboard data</p>
            <p className="text-red-200 text-sm">Using fallback data</p>
          </div>
        </div>
      )}
      
      {/* Doctor Level Card */}
      <div className="px-6 pt-8 pb-6 relative z-10">
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
                    <Crown className="w-10 h-10 text-white relative z-10 animate-bounce" />
                  </div>
                </div>
                <div>
                  <h1 className="text-2xl font-bold text-white mb-1">{userData?.name || 'Doctor'}</h1>
                  <p className="text-purple-200 text-sm">Level 5 Medical Professional</p>
                </div>
              </div>
              <div className="text-right">
                <div className="text-white text-sm opacity-80">{formatTime(currentTime)}</div>
                <div className="text-purple-200 text-xs">{currentTime.toLocaleDateString('id-ID')}</div>
              </div>
            </div>

            {/* Greeting */}
            <div className="flex items-center space-x-3 mb-6">
              <div className={`p-3 bg-gradient-to-r ${color} rounded-xl`}>
                <TimeIcon className="w-6 h-6 text-white" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-white">{greeting}</h2>
                <p className="text-purple-200 text-sm">Ready for today's challenges?</p>
              </div>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-2 gap-4 mb-6">
              <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl">
                    <Users className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-white text-sm opacity-80">Patients Today</p>
                    <p className="text-white text-xl font-bold">{dashboardMetrics.patients.today}</p>
                  </div>
                </div>
              </div>
              
              <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-xl">
                    <Activity className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-white text-sm opacity-80">Attendance</p>
                    <p className="text-white text-xl font-bold">{dashboardMetrics.attendance.displayText}</p>
                  </div>
                </div>
              </div>
              
              <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl">
                    <DollarSign className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-white text-sm opacity-80">Jaspel This Month</p>
                    <p className="text-white text-xl font-bold">
                      {new Intl.NumberFormat('id-ID', { 
                        style: 'currency', 
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }).format(dashboardMetrics.jaspel.currentMonth)}
                    </p>
                  </div>
                </div>
              </div>
              
              <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-gradient-to-r from-purple-400 to-pink-500 rounded-xl">
                    <Calendar className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-white text-sm opacity-80">Total Patients</p>
                    <p className="text-white text-xl font-bold">{dashboardMetrics.patients.thisMonth}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );

  const renderBottomNavigation = () => (
    <div className="fixed bottom-0 left-0 right-0 bg-white/10 backdrop-blur-2xl border-t border-white/20 px-6 py-4">
      <div className="flex justify-around items-center">
        <button
          onClick={() => setActiveTab('dashboard')}
          className={`flex flex-col items-center space-y-1 p-2 rounded-xl transition-all duration-200 ${
            activeTab === 'dashboard' 
              ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' 
              : 'text-purple-200 hover:text-white'
          }`}
        >
          <Crown className="w-6 h-6" />
          <span className="text-xs font-medium">Dashboard</span>
        </button>
        
        <button
          onClick={() => setActiveTab('missions')}
          className={`flex flex-col items-center space-y-1 p-2 rounded-xl transition-all duration-200 ${
            activeTab === 'missions' 
              ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' 
              : 'text-purple-200 hover:text-white'
          }`}
        >
          <Calendar className="w-6 h-6" />
          <span className="text-xs font-medium">Jadwal</span>
        </button>
        
        <button
          onClick={() => setActiveTab('presensi')}
          className={`flex flex-col items-center space-y-1 p-2 rounded-xl transition-all duration-200 ${
            activeTab === 'presensi' 
              ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' 
              : 'text-purple-200 hover:text-white'
          }`}
        >
          <Clock className="w-6 h-6" />
          <span className="text-xs font-medium">Presensi</span>
        </button>
        
        <button
          onClick={() => setActiveTab('jaspel')}
          className={`flex flex-col items-center space-y-1 p-2 rounded-xl transition-all duration-200 ${
            activeTab === 'jaspel' 
              ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' 
              : 'text-purple-200 hover:text-white'
          }`}
        >
          <DollarSign className="w-6 h-6" />
          <span className="text-xs font-medium">Jaspel</span>
        </button>
        
        <button
          onClick={() => setActiveTab('profile')}
          className={`flex flex-col items-center space-y-1 p-2 rounded-xl transition-all duration-200 ${
            activeTab === 'profile' 
              ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' 
              : 'text-purple-200 hover:text-white'
          }`}
        >
          <Settings className="w-6 h-6" />
          <span className="text-xs font-medium">Profile</span>
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 relative overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>
      </div>
      
      {/* Main Content */}
      <div className="relative z-10 pb-24">
        {renderTabContent()}
      </div>
      
      {/* Bottom Navigation */}
      {renderBottomNavigation()}
    </div>
  );
};

export default HolisticMedicalDashboardSimple;
