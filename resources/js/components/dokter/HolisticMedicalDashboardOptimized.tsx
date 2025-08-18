import React, { useState, useEffect, useRef, Suspense, lazy } from 'react';
import { Calendar, Clock, DollarSign, Award, Brain, Star, Crown, Flame, Moon, Sun, HeartCrack } from 'lucide-react';
import { DashboardProvider, useDashboard } from '../../contexts/DashboardContext';
import { performanceMonitor } from '../../utils/PerformanceMonitor';
import AttendanceCalculator from '../../utils/AttendanceCalculator';
import ErrorBoundary from '../ErrorBoundary';
import { safeGet, safeHas } from '../../utils/SafeObjectAccess';

// Lazy load heavy components for better initial load
const JadwalJaga = lazy(() => import('./JadwalJaga'));
const CreativeAttendanceDashboard = lazy(() => import('./Presensi'));
const JaspelComponent = lazy(() => import('./Jaspel'));
const ProfileComponent = lazy(() => import('./Profil'));

// Loading skeleton component
const LoadingSkeleton = () => (
  <div className="animate-pulse">
    <div className="h-32 bg-gray-700 rounded-lg mb-4"></div>
    <div className="h-64 bg-gray-700 rounded-lg"></div>
  </div>
);

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

// Main dashboard content component (uses context)
const DashboardContent: React.FC<{ userData: any }> = ({ userData }) => {
  const {
    dashboardData,
    attendanceData,
    jadwalJagaData,
    jaspelData,
    isLoading,
    errors,
    refreshData,
    isInitialLoad,
  } = useDashboard();

  const [activeTab, setActiveTab] = useState('jadwal');
  const [currentTime, setCurrentTime] = useState(new Date());
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

  // Animation states
  const [animationProgress, setAnimationProgress] = useState({
    jaspel: 0,
    attendance: 0,
    patients: 0,
  });

  // Update metrics when dashboard data changes
  useEffect(() => {
    if (!dashboardData) return;

    performanceMonitor.start('metrics-calculation');

    // Calculate jaspel metrics
    const currentJaspel = dashboardData.jaspel_summary?.current_month || 0;
    const previousJaspel = dashboardData.jaspel_summary?.last_month || 0;
    const growthPercentage = previousJaspel > 0 
      ? ((currentJaspel - previousJaspel) / previousJaspel) * 100
      : 0;
    const progressPercentage = Math.min(Math.max((currentJaspel / 10000000) * 100, 0), 100);

    // Calculate attendance metrics (use cached or initial data)
    const attendanceRate = dashboardData.performance?.attendance_rate || 0;

    setDashboardMetrics({
      jaspel: {
        currentMonth: currentJaspel,
        previousMonth: previousJaspel,
        growthPercentage: Math.round(growthPercentage * 10) / 10,
        progressPercentage: Math.round(progressPercentage * 10) / 10,
      },
      attendance: {
        rate: attendanceRate,
        daysPresent: Math.round((attendanceRate / 100) * 30),
        totalDays: 30,
        displayText: `${attendanceRate}%`,
      },
      patients: {
        today: dashboardData.patient_count?.today || 0,
        thisMonth: dashboardData.patient_count?.this_month || 0,
      },
    });

    performanceMonitor.end('metrics-calculation');
  }, [dashboardData]);

  // Update attendance metrics when attendance data arrives (background load)
  useEffect(() => {
    if (!attendanceData) return;

    performanceMonitor.start('attendance-processing');

    try {
      const history = attendanceData?.data?.history || attendanceData?.history || [];
      
      if (history.length > 0) {
        // Use AttendanceCalculator static methods for unified metrics
        const { start, end } = AttendanceCalculator.getCurrentMonthRange();
        const unifiedMetrics = AttendanceCalculator.calculateAttendanceMetrics(history, start, end);

        setDashboardMetrics(prev => ({
          ...prev,
          attendance: {
            rate: unifiedMetrics.attendancePercentage,
            daysPresent: unifiedMetrics.presentDays,
            totalDays: unifiedMetrics.totalDays,
            displayText: `${unifiedMetrics.attendancePercentage}%`,
          },
        }));
      }
    } catch (error) {
      console.warn('Error processing attendance data:', error);
    }

    performanceMonitor.end('attendance-processing');
  }, [attendanceData]);

  // Smooth animations for metrics
  useEffect(() => {
    const animationDuration = isInitialLoad ? 0 : 1500; // Skip animation on initial load
    const steps = 60;
    const stepDuration = animationDuration / steps;

    let step = 0;
    const animationInterval = setInterval(() => {
      if (step >= steps) {
        clearInterval(animationInterval);
        return;
      }

      const progress = step / steps;
      const easeOutQuart = 1 - Math.pow(1 - progress, 4);

      setAnimationProgress({
        jaspel: Math.round(dashboardMetrics.jaspel.progressPercentage * easeOutQuart),
        attendance: Math.round(dashboardMetrics.attendance.rate * easeOutQuart),
        patients: Math.round(dashboardMetrics.patients.today * easeOutQuart),
      });

      step++;
    }, stepDuration);

    return () => clearInterval(animationInterval);
  }, [dashboardMetrics, isInitialLoad]);

  // Update clock
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(prevTime => {
        const newTime = new Date();
        return prevTime.getTime() !== newTime.getTime() ? newTime : prevTime;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  const getTimeOfDayIcon = () => {
    const hour = currentTime.getHours();
    if (hour >= 5 && hour < 12) return <Sun className="w-6 h-6 text-yellow-400" />;
    if (hour >= 12 && hour < 18) return <Flame className="w-6 h-6 text-orange-400" />;
    return <Moon className="w-6 h-6 text-blue-400" />;
  };

  const getGreeting = () => {
    const hour = currentTime.getHours();
    const name = userData?.name || 'Doctor';
    
    if (hour >= 5 && hour < 12) return `Good Morning, ${name}!`;
    if (hour >= 12 && hour < 18) return `Good Afternoon, ${name}!`;
    return `Good Evening, ${name}!`;
  };

  const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const navItems = [
    { id: 'jadwal', label: 'Jadwal Jaga', icon: Calendar },
    { id: 'attendance', label: 'Attendance', icon: Clock },
    { id: 'jaspel', label: 'Jaspel', icon: DollarSign },
    { id: 'profile', label: 'Profile', icon: Award },
  ];

  // Show initial loading state
  if (isInitialLoad && isLoading.dashboard) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-violet-800 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-purple-500 mx-auto"></div>
          <p className="text-white mt-4">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-violet-800">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-800 to-violet-900 shadow-2xl border-b border-purple-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="py-4 flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                {getTimeOfDayIcon()}
                <h1 className="text-2xl font-bold text-white">{getGreeting()}</h1>
              </div>
              <div className="flex items-center text-gray-300">
                <Clock className="w-4 h-4 mr-1" />
                <span className="text-sm">{currentTime.toLocaleTimeString()}</span>
              </div>
            </div>
            {/* Refresh button */}
            <button
              onClick={() => refreshData('all')}
              className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
              disabled={isLoading.dashboard}
            >
              {isLoading.dashboard ? 'Refreshing...' : 'Refresh'}
            </button>
          </div>
        </div>
      </div>

      {/* Metrics Cards - Progressive Loading */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Jaspel Card */}
          <div className="bg-gradient-to-br from-purple-800 to-purple-900 rounded-xl p-6 shadow-xl">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center">
                <DollarSign className="w-8 h-8 text-green-400" />
                <h3 className="ml-2 text-lg font-semibold text-white">Jaspel This Month</h3>
              </div>
              {dashboardMetrics.jaspel.growthPercentage !== 0 && (
                <span className={`text-sm ${dashboardMetrics.jaspel.growthPercentage > 0 ? 'text-green-400' : 'text-red-400'}`}>
                  {dashboardMetrics.jaspel.growthPercentage > 0 ? '↑' : '↓'} {Math.abs(dashboardMetrics.jaspel.growthPercentage)}%
                </span>
              )}
            </div>
            <div className="space-y-2">
              <p className="text-3xl font-bold text-white">
                {formatCurrency(dashboardMetrics.jaspel.currentMonth)}
              </p>
              <div className="w-full bg-gray-700 rounded-full h-2">
                <div 
                  className="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full transition-all duration-1500 ease-out"
                  style={{ width: `${animationProgress.jaspel}%` }}
                />
              </div>
              <p className="text-sm text-gray-300">
                Previous: {formatCurrency(dashboardMetrics.jaspel.previousMonth)}
              </p>
            </div>
          </div>

          {/* Attendance Card */}
          <div className="bg-gradient-to-br from-blue-800 to-blue-900 rounded-xl p-6 shadow-xl">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center">
                <Clock className="w-8 h-8 text-blue-400" />
                <h3 className="ml-2 text-lg font-semibold text-white">Attendance Rate</h3>
              </div>
            </div>
            <div className="space-y-2">
              <p className="text-3xl font-bold text-white">{animationProgress.attendance}%</p>
              <div className="w-full bg-gray-700 rounded-full h-2">
                <div 
                  className="bg-gradient-to-r from-blue-400 to-cyan-500 h-2 rounded-full transition-all duration-1500 ease-out"
                  style={{ width: `${animationProgress.attendance}%` }}
                />
              </div>
              <p className="text-sm text-gray-300">
                {dashboardMetrics.attendance.daysPresent} of {dashboardMetrics.attendance.totalDays} days
              </p>
            </div>
          </div>

          {/* Patients Card */}
          <div className="bg-gradient-to-br from-pink-800 to-pink-900 rounded-xl p-6 shadow-xl">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center">
                <HeartCrack className="w-8 h-8 text-pink-400" />
                <h3 className="ml-2 text-lg font-semibold text-white">Patients Today</h3>
              </div>
            </div>
            <div className="space-y-2">
              <p className="text-3xl font-bold text-white">{animationProgress.patients}</p>
              <div className="w-full bg-gray-700 rounded-full h-2">
                <div 
                  className="bg-gradient-to-r from-pink-400 to-rose-500 h-2 rounded-full transition-all duration-1500 ease-out"
                  style={{ width: `${Math.min((animationProgress.patients / 50) * 100, 100)}%` }}
                />
              </div>
              <p className="text-sm text-gray-300">
                This month: {dashboardMetrics.patients.thisMonth}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex space-x-1 bg-gray-800/50 p-1 rounded-xl backdrop-blur-sm">
          {navItems.map(item => (
            <button
              key={item.id}
              onClick={() => setActiveTab(item.id)}
              className={`flex-1 flex items-center justify-center space-x-2 py-3 px-4 rounded-lg transition-all duration-200 ${
                activeTab === item.id
                  ? 'bg-gradient-to-r from-purple-600 to-violet-600 text-white shadow-lg'
                  : 'text-gray-400 hover:text-white hover:bg-gray-700/50'
              }`}
            >
              <item.icon className="w-5 h-5" />
              <span className="font-medium">{item.label}</span>
            </button>
          ))}
        </div>
      </div>

      {/* Content Area with Lazy Loading */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <ErrorBoundary>
          <Suspense fallback={<LoadingSkeleton />}>
            {activeTab === 'jadwal' && (
              <div className="transition-all duration-300 ease-in-out">
                <JadwalJaga 
                  userData={userData} 
                  onNavigate={setActiveTab}
                  jadwalData={jadwalJagaData}
                  isLoading={isLoading.jadwalJaga}
                />
              </div>
            )}

            {activeTab === 'attendance' && (
              <div className="transition-all duration-300 ease-in-out">
                <CreativeAttendanceDashboard 
                  userData={userData}
                  attendanceData={attendanceData}
                  isLoading={isLoading.attendance}
                />
              </div>
            )}

            {activeTab === 'jaspel' && (
              <div className="transition-all duration-300 ease-in-out">
                <JaspelComponent 
                  jaspelData={jaspelData}
                  isLoading={isLoading.jaspel}
                />
              </div>
            )}

            {activeTab === 'profile' && (
              <div className="transition-all duration-300 ease-in-out">
                <ProfileComponent />
              </div>
            )}
          </Suspense>
        </ErrorBoundary>
      </div>
    </div>
  );
};

// Main component with provider
const HolisticMedicalDashboardOptimized: React.FC<{ userData?: any }> = ({ userData }) => {
  // Use provided userData or fetch from context
  const [finalUserData, setFinalUserData] = useState(userData);

  useEffect(() => {
    // If no userData provided, it will be fetched by the context
    if (!userData) {
      console.log('User data will be fetched by context');
    } else {
      setFinalUserData(userData);
    }
  }, [userData]);

  return (
    <DashboardProvider prefetch={true}>
      <DashboardContent userData={finalUserData} />
    </DashboardProvider>
  );
};

export default HolisticMedicalDashboardOptimized;