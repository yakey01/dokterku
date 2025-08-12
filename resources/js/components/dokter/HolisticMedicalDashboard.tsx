import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { Calendar, Clock, DollarSign, Award, Brain, Star, Crown, Flame, Moon, Sun } from 'lucide-react';
import { JadwalJaga } from './JadwalJaga';
import CreativeAttendanceDashboard from './Presensi';
import JaspelComponent from './Jaspel';
import ProfileComponent from './Profil';
import doctorApi from '../../utils/doctorApi';
import { performanceMonitor } from '../../utils/PerformanceMonitor';
import AttendanceCalculator from '../../utils/AttendanceCalculator';

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

interface ProgressBarAnimationProps {
  percentage: number;
  delay?: number;
  className?: string;
  gradientColors: string;
  barClassName?: string;
}

/**
 * Calculate animation duration based on percentage value
 * Higher percentages get longer durations for better visual impact
 */
const calculateDuration = (percentage: number): number => {
  if (percentage <= 25) return 300 + Math.random() * 100; // 300-400ms
  if (percentage <= 50) return 500 + Math.random() * 100; // 500-600ms 
  if (percentage <= 75) return 700 + Math.random() * 100; // 700-800ms
  return 900 + Math.random() * 300; // 900-1200ms
};

/**
 * Progress Bar Animation Component with dynamic duration and accessibility
 */
const ProgressBarAnimation: React.FC<ProgressBarAnimationProps> = ({ 
  percentage, 
  delay = 0, 
  className = "", 
  gradientColors,
  barClassName = "" 
}) => {
  const [width, setWidth] = useState(0);
  const [animationDuration, setAnimationDuration] = useState(750);

  useEffect(() => {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (prefersReducedMotion) {
      // Instant animation for accessibility
      const timer = setTimeout(() => {
        setWidth(percentage);
      }, delay);
      setAnimationDuration(0);
      return () => clearTimeout(timer);
    }

    // Calculate dynamic duration
    const duration = calculateDuration(percentage);
    setAnimationDuration(duration);

    // Animate with calculated delay and duration
    const timer = setTimeout(() => {
      setWidth(percentage);
    }, delay);

    return () => clearTimeout(timer);
  }, [percentage, delay]);

  const progressBarStyle = {
    width: `${width}%`,
    transitionDuration: `${animationDuration}ms`
  };

  return (
    <div className={`w-full rounded-full h-2 overflow-hidden ${className}`}>
      <div 
        className={`h-2 rounded-full transition-all ease-out shadow-lg ${gradientColors} ${barClassName}`}
        style={progressBarStyle}
        role="progressbar"
        aria-valuenow={Math.round(width)}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label={`Progress: ${Math.round(width)}%`}
      ></div>
    </div>
  );
};

const HolisticMedicalDashboard: React.FC<HolisticMedicalDashboardProps> = ({ userData }) => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [doctorLevel, setDoctorLevel] = useState(7);
  const [experiencePoints, setExperiencePoints] = useState(2847);
  const [nextLevelXP, setNextLevelXP] = useState(3000);
  const [dailyStreak, setDailyStreak] = useState(15);
  const [activeTab, setActiveTab] = useState('home');
  const [leaderboardData, setLeaderboardData] = useState<any[]>([]);
  const [leaderboardLoading, setLeaderboardLoading] = useState(true);
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
  });
  const [loading, setLoading] = useState<LoadingState>({
    dashboard: false,
    error: null,
  });

  // Ref to prevent duplicate API calls
  const isDataFetchingRef = useRef(false);
  const dataFetchedRef = useRef(false);
  const mountedRef = useRef(true);

  // Memoized time update to prevent unnecessary re-renders
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(prevTime => {
        const newTime = new Date();
        // Only update if time actually changed (prevent unnecessary re-renders)
        return prevTime.getTime() !== newTime.getTime() ? newTime : prevTime;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Optimized dashboard data fetching with performance monitoring and duplicate prevention
  useEffect(() => {
    let isMounted = true;
    let retryCount = 0;
    const maxRetries = 3;

    const fetchDashboardData = async () => {
      // Prevent duplicate API calls
      if (isDataFetchingRef.current || dataFetchedRef.current) {
        console.log('🚫 HolisticMedicalDashboard: Duplicate API call prevented');
        return;
      }

      // Check if data already fetched successfully
      if (dataFetchedRef.current) {
        console.log('✅ HolisticMedicalDashboard: Data already fetched, skipping');
        return;
      }

      isDataFetchingRef.current = true;

      try {
        if (!isMounted) return;
        
        // Start performance monitoring
        performanceMonitor.start('dashboard-data-fetch');
        
        setLoading({ dashboard: true, error: null });
        
        console.log('🔄 HolisticMedicalDashboard: Starting parallel API fetch...');
        
        // OPTIMIZATION 1: Parallel API calls instead of sequential
        performanceMonitor.start('parallel-api-calls');
        
        // Check for cached attendance data first
        const cachedAttendance = localStorage.getItem('dashboard_attendance_cache');
        const cacheTimestamp = localStorage.getItem('dashboard_attendance_timestamp');
        const isCacheValid = cacheTimestamp && (Date.now() - parseInt(cacheTimestamp)) < 5 * 60 * 1000; // 5 minutes cache
        
        // Start both API calls in parallel
        const [dashboardData, attendanceResponse] = await Promise.all([
          doctorApi.getDashboard(),
          fetch('/api/v2/dashboards/dokter/presensi', {
            method: 'GET',
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          })
        ]);
        
        performanceMonitor.end('parallel-api-calls');
        
        if (!isMounted) return;
        
        if (dashboardData) {
          console.log('✅ HolisticMedicalDashboard: Dashboard data received:', dashboardData);
          
          // Performance-monitored data processing
          performanceMonitor.start('data-processing');
          
          // Calculate jaspel growth percentage
          const currentJaspel = dashboardData.jaspel_summary?.current_month || 0;
          const previousJaspel = dashboardData.jaspel_summary?.last_month || 0;
          const growthPercentage = previousJaspel > 0 
            ? ((currentJaspel - previousJaspel) / previousJaspel) * 100
            : 0;
          
          // Calculate jaspel progress percentage (normalized to 0-100)
          // Assuming 10M IDR as 100% target for progress bar
          const progressPercentage = Math.min(Math.max((currentJaspel / 10000000) * 100, 0), 100);
          
          // OPTIMIZATION 2: Progressive loading - Show initial data immediately
          // Use cached or fallback attendance while processing
          const initialAttendanceRate = isCacheValid && cachedAttendance 
            ? JSON.parse(cachedAttendance).rate 
            : dashboardData.performance?.attendance_rate || 0;
          
          // Update UI immediately with available data
          if (isMounted) {
            setDashboardMetrics(prevMetrics => ({
              ...prevMetrics,
              jaspel: {
                currentMonth: currentJaspel,
                previousMonth: previousJaspel,
                growthPercentage: Math.round(growthPercentage * 10) / 10,
                progressPercentage: Math.round(progressPercentage * 10) / 10,
              },
              attendance: {
                rate: initialAttendanceRate,
                daysPresent: Math.round((initialAttendanceRate / 100) * 30),
                totalDays: 30,
                displayText: `${initialAttendanceRate}%`,
              },
              patients: {
                today: dashboardData.patient_count?.today || 0,
                thisMonth: dashboardData.patient_count?.this_month || 0,
              },
            }));
            
            // Stop showing loading skeleton after initial data
            setLoading({ dashboard: false, error: null });
          }
          
          // Process attendance data in background
          let unifiedAttendanceMetrics = null;
          try {
            console.log('🔄 Dashboard: Processing attendance data...');
          
            console.log('📡 Dashboard: Attendance API Response Status:', attendanceResponse.status, attendanceResponse.statusText);
            
            if (attendanceResponse.ok) {
              const attendanceData = await attendanceResponse.json();
              console.log('📊 Dashboard: Raw attendance data:', attendanceData);
            
            // getPresensi endpoint returns data.history instead of just data
            const history = attendanceData?.data?.history || [];
            console.log('History records received:', history.length);
            
            // EXACT SAME FORMATTING AS PRESENSI.TSX
            const formattedHistory = history.map((record: any) => {
              // Format date
              const date = new Date(record.date || record.tanggal);
              const formattedDate = date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
              });
              
              // Format check-in time
              const checkIn = record.time_in || record.check_in || record.jam_masuk;
              const formattedCheckIn = checkIn ? 
                (typeof checkIn === 'string' && checkIn.includes(':') ? 
                  checkIn.substring(0, 5) : 
                  new Date(checkIn).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                ) : '-';
              
              // Format check-out time
              const checkOut = record.time_out || record.check_out || record.jam_pulang;
              const formattedCheckOut = checkOut ? 
                (typeof checkOut === 'string' && checkOut.includes(':') ? 
                  checkOut.substring(0, 5) : 
                  new Date(checkOut).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                ) : '-';
              
              // Determine status - EXACT SAME AS PRESENSI
              let status = 'Hadir';
              if (record.status) {
                const statusLower = record.status.toLowerCase();
                if (statusLower === 'late' || statusLower === 'terlambat') {
                  status = 'Terlambat';
                } else if (statusLower === 'on_time' || statusLower === 'tepat waktu' || statusLower === 'present') {
                  status = 'Hadir';
                } else if (statusLower === 'absent' || statusLower === 'tidak hadir') {
                  status = 'Tidak Hadir';
                } else if (statusLower.includes('leave') || statusLower.includes('cuti')) {
                  status = 'Cuti';
                } else if (statusLower === 'auto_closed' && checkIn) {
                  // auto_closed with check-in means they attended
                  status = 'Hadir';
                } else {
                  // Default to Hadir if checked in
                  status = checkIn && checkIn !== '-' ? 'Hadir' : 'Tidak Hadir';
                }
              }
              
              // Calculate duration - EXACT SAME AS PRESENSI
              let hours = '0h 0m';
              if (checkIn !== '-' && checkOut !== '-') {
                try {
                  const start = new Date(`2000-01-01 ${formattedCheckIn}`);
                  const end = new Date(`2000-01-01 ${formattedCheckOut}`);
                  const diff = end.getTime() - start.getTime();
                  
                  if (diff > 0) {
                    const totalMinutes = Math.floor(diff / (1000 * 60));
                    const h = Math.floor(totalMinutes / 60);
                    const m = totalMinutes % 60;
                    hours = `${h}h ${m}m`;
                  }
                } catch (e) {
                  // If duration calculation fails, try using provided duration
                  if (record.work_duration || record.durasi) {
                    hours = record.work_duration || record.durasi;
                  }
                }
              }
              
              return {
                date: formattedDate,
                checkIn: formattedCheckIn,
                checkOut: formattedCheckOut,
                status: status,
                hours: hours
              };
            });
            
            // Sort by date (most recent first) - SAME AS PRESENSI
            formattedHistory.sort((a: any, b: any) => {
              const dateA = new Date(a.date.split('/').reverse().join('-'));
              const dateB = new Date(b.date.split('/').reverse().join('-'));
              return dateB.getTime() - dateA.getTime();
            });
            
            // Calculate monthly statistics - EXACT SAME AS PRESENSI
            const currentMonth = new Date();
            const monthStart = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
            const monthEnd = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
            
            // Debug logs - SAME AS PRESENSI
            console.log('Monthly Data for stats:', formattedHistory);
            console.log('Month range:', monthStart, 'to', monthEnd);
            
            // USE UNIFIED ATTENDANCE CALCULATOR - EXACT SAME AS PRESENSI
            unifiedAttendanceMetrics = AttendanceCalculator.calculateAttendanceMetrics(
              formattedHistory,
              monthStart,
              monthEnd
            );
            
            console.log('📊 Dashboard using UNIFIED attendance metrics (SAME AS PRESENSI):', unifiedAttendanceMetrics);
            
            // OPTIMIZATION 4: Cache the calculated attendance data
            if (unifiedAttendanceMetrics) {
              localStorage.setItem('dashboard_attendance_cache', JSON.stringify({
                rate: unifiedAttendanceMetrics.attendancePercentage,
                daysPresent: unifiedAttendanceMetrics.presentDays,
                totalDays: unifiedAttendanceMetrics.totalDays
              }));
              localStorage.setItem('dashboard_attendance_timestamp', Date.now().toString());
            }
            } else {
              console.warn('❌ Dashboard: Attendance API failed with status:', attendanceResponse.status);
            }
          } catch (error) {
            console.error('❌ Dashboard: Failed to process attendance data:', error);
            console.warn('📊 Dashboard: Will use initial/cached calculation');
          }
          
          // Update with final calculated metrics if available
          if (unifiedAttendanceMetrics && isMounted) {
            const finalAttendanceRate = unifiedAttendanceMetrics.attendancePercentage;
            const finalTotalDays = unifiedAttendanceMetrics.totalDays;
            const finalDaysPresent = unifiedAttendanceMetrics.presentDays;
            
            console.log('📊 Dashboard Final Calculation:', {
              source: 'UNIFIED_CALCULATOR',
              attendanceRate: finalAttendanceRate,
              daysPresent: finalDaysPresent,
              totalDays: finalTotalDays,
              calculation: 'hours-based'
            });
            
            // Update attendance metrics with accurate calculated values
            setDashboardMetrics(prevMetrics => ({
              ...prevMetrics,
              attendance: {
                rate: finalAttendanceRate,
                daysPresent: finalDaysPresent,
                totalDays: finalTotalDays,
                displayText: `${finalAttendanceRate}%`,
              }
            }));
          }
          
          // End performance monitoring for data processing
          performanceMonitor.end('data-processing');
          
          // Mark data as successfully fetched
          dataFetchedRef.current = true;
          
          // End overall dashboard fetch monitoring
          performanceMonitor.end('dashboard-data-fetch');
          
          console.log('✅ HolisticMedicalDashboard: Dashboard metrics updated successfully');
        }
      } catch (error) {
        if (!isMounted) return;
        
        const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
        console.error('❌ HolisticMedicalDashboard: Error fetching dashboard data:', error);
        console.error('🔍 HolisticMedicalDashboard: Error details:', {
          message: errorMessage,
          type: error?.constructor?.name,
          stack: error instanceof Error ? error.stack : undefined
        });
        
        // Retry logic for network errors
        if (retryCount < maxRetries && (errorMessage.includes('network') || errorMessage.includes('fetch'))) {
          retryCount++;
          console.log(`🔄 HolisticMedicalDashboard: Retrying... (${retryCount}/${maxRetries})`);
          
          // Reset fetching flag for retry
          isDataFetchingRef.current = false;
          
          setTimeout(() => fetchDashboardData(), 1000 * retryCount);
          return;
        }
        
        setLoading({ dashboard: false, error: `Failed to load dashboard data: ${errorMessage}` });
        
        // Set fallback data on error only if component is mounted
        if (isMounted) {
          setDashboardMetrics(prevMetrics => {
            const fallbackMetrics = {
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
            };
            
            // Only update if data actually changed
            return JSON.stringify(prevMetrics) !== JSON.stringify(fallbackMetrics) ? fallbackMetrics : prevMetrics;
          });
          
          console.log('⚠️ HolisticMedicalDashboard: Fallback data set due to error');
        }
      } finally {
        if (isMounted) {
          setLoading({ dashboard: false, error: null });
          
          // Generate overall performance report
          setTimeout(() => {
            const report = performanceMonitor.getReport();
            if (report && report.recommendations && report.recommendations.length > 0) {
              console.group('🎯 Performance Recommendations');
              report.recommendations.forEach(rec => console.log(`💡 ${rec}`));
              console.groupEnd();
            }
          }, 100);
        }
        
        // Reset fetching flag
        isDataFetchingRef.current = false;
      }
    };

    // Only fetch data if not already fetching or fetched
    console.log('📊 Dashboard useEffect check:', {
      isDataFetching: isDataFetchingRef.current,
      dataFetched: dataFetchedRef.current,
      willFetch: !isDataFetchingRef.current && !dataFetchedRef.current
    });
    
    // Fetch leaderboard data
    const fetchLeaderboard = async () => {
      try {
        console.log('📊 Fetching leaderboard data...');
        setLeaderboardLoading(true);
        
        // Use fetch API directly to get leaderboard data
        const response = await fetch('/api/v2/dashboards/dokter/leaderboard', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          credentials: 'include'
        });
        
        console.log('📊 Leaderboard response status:', response.status);
        
        if (!response.ok) {
          console.error('❌ Leaderboard fetch failed with status:', response.status);
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📊 Leaderboard data received:', data);
        
        if (data && data.success && data.data) {
          const leaderboard = data.data.leaderboard || data.data || [];
          
          // Transform the data to ensure it has the correct structure
          const transformedLeaderboard = Array.isArray(leaderboard) ? leaderboard.map((doctor: any, index: number) => ({
            id: doctor.id || index + 1,
            rank: doctor.rank || index + 1,
            name: doctor.name || `Doctor ${index + 1}`,
            level: doctor.level || Math.floor(Math.random() * 10) + 1,
            xp: doctor.xp || doctor.experience_points || Math.floor(Math.random() * 5000) + 1000,
            attendance_rate: doctor.attendance_rate || doctor.attendance || Math.floor(Math.random() * 30) + 70,
            streak_days: doctor.streak_days || doctor.streak || 0,
            total_hours: doctor.total_hours || Math.floor(Math.random() * 200) + 100,
            total_days: doctor.total_days || Math.floor(Math.random() * 30) + 1,
            badge: doctor.badge || (index === 0 ? '👑' : index === 1 ? '🥈' : index === 2 ? '🥉' : '⭐')
          })) : [];
          
          console.log('✅ Setting transformed leaderboard data:', transformedLeaderboard);
          setLeaderboardData(transformedLeaderboard);
          
          // Update current user's XP and level if they're in the leaderboard
          const currentUserData = transformedLeaderboard.find((doctor: any) => 
            doctor.name === userData?.name || doctor.name?.includes(userData?.name)
          );
          
          if (currentUserData) {
            console.log('👤 Found current user in leaderboard:', currentUserData.name);
            setExperiencePoints(currentUserData.xp);
            setDoctorLevel(currentUserData.level);
            setDailyStreak(currentUserData.streak_days || 0);
          }
          
          console.log('✅ Leaderboard data loaded successfully:', transformedLeaderboard.length, 'doctors');
        } else {
          console.error('❌ Invalid leaderboard response structure:', data);
          setLeaderboardData([]);
        }
      } catch (error) {
        console.error('❌ Failed to fetch leaderboard - Error details:', error);
        // Try to provide more context about the error
        if (error instanceof Error) {
          console.error('Error message:', error.message);
          console.error('Error stack:', error.stack);
        }
        
        // Use fallback static data on error
        const fallbackLeaderboard = [
          {
            id: 1,
            rank: 1,
            name: 'Dr. Sarah Johnson',
            level: 12,
            xp: 4250,
            attendance_rate: 98,
            streak_days: 45,
            total_hours: 320,
            total_days: 28,
            badge: '👑'
          },
          {
            id: 2,
            rank: 2,
            name: userData?.name || 'Dr. Dokter Umum',
            level: 10,
            xp: 3850,
            attendance_rate: 95,
            streak_days: 30,
            total_hours: 285,
            total_days: 26,
            badge: '🥈'
          },
          {
            id: 3,
            rank: 3,
            name: 'Dr. Michael Chen',
            level: 9,
            xp: 3420,
            attendance_rate: 92,
            streak_days: 21,
            total_hours: 260,
            total_days: 24,
            badge: '🥉'
          },
          {
            id: 4,
            rank: 4,
            name: 'Dr. Emma Wilson',
            level: 8,
            xp: 2980,
            attendance_rate: 89,
            streak_days: 15,
            total_hours: 230,
            total_days: 22,
            badge: '⭐'
          },
          {
            id: 5,
            rank: 5,
            name: 'Dr. James Lee',
            level: 7,
            xp: 2540,
            attendance_rate: 85,
            streak_days: 10,
            total_hours: 200,
            total_days: 20,
            badge: '⭐'
          }
        ];
        
        console.log('⚠️ Using fallback leaderboard data');
        setLeaderboardData(fallbackLeaderboard);
      } finally {
        console.log('📊 Leaderboard loading complete, setting loading to false');
        setLeaderboardLoading(false);
      }
    };

    if (!isDataFetchingRef.current && !dataFetchedRef.current) {
      console.log('🚀 Initiating dashboard data fetch');
      fetchDashboardData();
      fetchLeaderboard(); // Fetch leaderboard data alongside dashboard data
    } else {
      console.log('⏭️ Skipping dashboard fetch - already in progress or completed');
    }

    // Cleanup function
    return () => {
      isMounted = false;
    };
  }, []); // Empty dependency array - only run once on mount

  // Memoized time formatting to prevent unnecessary recalculations
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

  // Memoized greeting calculation - moved here to be available for renderMainDashboard
  const { greeting, icon: TimeIcon, color } = useMemo(() => getTimeGreeting(), [getTimeGreeting]);

  // Memoized main dashboard rendering - defined before renderTabContent
  const renderMainDashboard = useCallback(() => (
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
                  <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-lg animate-pulse">
                    Lv.{doctorLevel}
                  </div>
                </div>
                <div>
                  <h1 className={`text-2xl md:text-3xl lg:text-4xl font-bold bg-gradient-to-r ${color} bg-clip-text text-transparent mb-1`}>
                    {greeting}
                  </h1>
                  <p className="text-purple-200 text-lg md:text-xl">{userData?.name || 'Doctor'}</p>
                </div>
              </div>
            </div>

            {/* XP Progress */}
            <div className="mb-6">
              <div className="flex justify-between text-sm mb-2">
                <span className="text-cyan-300">Klinik Dokterku</span>
                <span className="text-white font-semibold">Akreditasi Paripurna</span>
              </div>
            </div>

            {/* Daily Stats */}
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  <Flame className="w-5 h-5 text-orange-400 mr-2" />
                  <span className="text-xl font-bold text-white">{dailyStreak}</span>
                </div>
                <span className="text-orange-300 text-sm">Jumlah Jaga</span>
              </div>
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  <Star className="w-5 h-5 text-yellow-400 mr-2" />
                  <span className="text-xl font-bold text-white">
                    {dashboardMetrics.attendance.displayText}
                  </span>
                </div>
                <span className="text-yellow-300 text-sm">Tingkat Kehadiran</span>
              </div>
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  <Award className="w-5 h-5 text-purple-400 mr-2" />
                  <span className="text-xl font-bold text-white">
                    {dashboardMetrics.patients.thisMonth}
                  </span>
                </div>
                <span className="text-purple-300 text-sm">Jumlah Pasien</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Doctor Analytics */}
      <div className="px-6 mb-8 relative z-10">
        <h3 className="text-xl md:text-2xl font-bold mb-6 text-center bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
          Doctor Analytics
        </h3>
        
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">

          {/* Achievement Timeline */}
          <div className="space-y-4">
            <h4 className="font-semibold text-white mb-4">Recent Achievements</h4>
            
            <div className="p-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl border border-green-400/30">
              <div className="flex items-center space-x-4 mb-3">
                <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
                <div className="flex-1">
                  <div className="font-medium text-white">Jaspel vs Bulan Lalu</div>
                </div>
                <div className="text-2xl">🟡</div>
              </div>
              <div className="mb-2">
                <div className="text-right text-white font-semibold text-sm mb-1">
                  {dashboardMetrics.jaspel.growthPercentage >= 0 
                    ? `+${dashboardMetrics.jaspel.growthPercentage}%`
                    : `${dashboardMetrics.jaspel.growthPercentage}%`
                  }
                </div>
                <ProgressBarAnimation
                  percentage={dashboardMetrics.jaspel.progressPercentage}
                  delay={200}
                  className="bg-green-900/30"
                  gradientColors="bg-gradient-to-r from-green-400 via-emerald-400 to-yellow-400"
                />
              </div>
            </div>

            <div className="p-4 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-2xl border border-blue-400/30">
              <div className="flex items-center space-x-4 mb-3">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                  <Calendar className="w-5 h-5 text-white" />
                </div>
                <div className="flex-1">
                  <div className="font-medium text-white">Tingkat Kehadiran</div>
                </div>
                <div className="text-2xl">📅</div>
              </div>
              <div className="mb-2">
                <div className="text-right text-white font-semibold text-sm mb-1">
                  {dashboardMetrics.attendance.displayText}
                </div>
                <ProgressBarAnimation
                  percentage={dashboardMetrics.attendance.rate}
                  delay={100}
                  className="bg-blue-900/30"
                  gradientColors="bg-gradient-to-r from-blue-400 via-cyan-400 to-emerald-400"
                />
              </div>
            </div>

          </div>
        </div>
      </div>

      {/* Leaderboard Preview */}
      <div className="px-6 pb-32 relative z-10">
        <h3 className="text-xl md:text-2xl font-bold mb-6 text-center bg-gradient-to-r from-pink-400 to-purple-400 bg-clip-text text-transparent">
          Elite Doctor Leaderboard
        </h3>
        
        <div className="space-y-4">
          {leaderboardLoading ? (
            // Loading skeleton
            <>
              {[1, 2, 3].map((i) => (
                <div key={i} className="flex items-center space-x-4 bg-gradient-to-r from-gray-700/30 to-gray-600/30 rounded-2xl p-4 border-2 border-gray-500/30 animate-pulse">
                  <div className="w-12 h-12 bg-gray-600/50 rounded-xl"></div>
                  <div className="flex-1">
                    <div className="h-5 bg-gray-600/50 rounded w-32 mb-2"></div>
                    <div className="h-4 bg-gray-600/50 rounded w-24"></div>
                  </div>
                  <div className="text-right">
                    <div className="h-6 bg-gray-600/50 rounded w-20"></div>
                  </div>
                </div>
              ))}
            </>
          ) : leaderboardData.length > 0 ? (
            // Dynamic leaderboard
            leaderboardData.map((doctor, index) => {
              const isCurrentUser = doctor.name === userData?.name || doctor.name?.includes(userData?.name);
              const rankColors = {
                1: {
                  bg: 'from-yellow-500/30 to-amber-500/30',
                  border: 'border-yellow-400/50',
                  iconBg: 'from-yellow-500 to-amber-500',
                  textColor: 'text-yellow-300',
                  xpColor: 'text-yellow-400',
                  badge: '👑'
                },
                2: {
                  bg: 'from-gray-400/30 to-slate-500/30',
                  border: 'border-gray-400/50',
                  iconBg: 'from-gray-500 to-slate-600',
                  textColor: 'text-gray-300',
                  xpColor: 'text-gray-400',
                  badge: '🥈'
                },
                3: {
                  bg: 'from-orange-600/30 to-amber-700/30',
                  border: 'border-orange-500/50',
                  iconBg: 'from-orange-600 to-amber-700',
                  textColor: 'text-orange-300',
                  xpColor: 'text-orange-400',
                  badge: '🥉'
                }
              };
              
              const colors = rankColors[doctor.rank] || rankColors[3];
              
              return (
                <div 
                  key={doctor.id} 
                  className={`flex items-center space-x-4 bg-gradient-to-r ${colors.bg} rounded-2xl p-4 border-2 ${colors.border} ${isCurrentUser ? 'ring-2 ring-green-400/50' : ''} transition-all duration-300 hover:scale-105`}
                >
                  <div className={`w-12 h-12 bg-gradient-to-br ${colors.iconBg} rounded-xl flex items-center justify-center font-bold text-white text-lg`}>
                    {doctor.badge || colors.badge}
                  </div>
                  <div className="flex-1">
                    <div className="font-bold text-white flex items-center gap-2">
                      {doctor.name}
                      {isCurrentUser && <span className="text-xs bg-green-500/30 px-2 py-1 rounded-full text-green-300">You</span>}
                    </div>
                    <div className={colors.textColor}>
                      Level {doctor.level} • {doctor.attendance_rate}% Score
                    </div>
                    {doctor.streak_days > 0 && (
                      <div className="text-xs text-orange-300 mt-1">
                        🔥 {doctor.streak_days} day streak
                      </div>
                    )}
                  </div>
                  <div className="text-right">
                    <div className={`text-2xl font-bold ${colors.xpColor}`}>
                      {doctor.xp.toLocaleString()} XP
                    </div>
                    <div className="text-xs text-gray-400">
                      {doctor.total_hours}h • {doctor.total_days} days
                    </div>
                  </div>
                </div>
              );
            })
          ) : (
            // Empty state
            <div className="text-center py-8 text-gray-400">
              <div className="text-4xl mb-3">📊</div>
              <p>No leaderboard data available</p>
              <p className="text-sm mt-1">Check back later for rankings</p>
            </div>
          )}
        </div>
      </div>
    </>
  ), [
    loading.dashboard,
    loading.error,
    doctorLevel,
    greeting,
    color,
    userData,
    dailyStreak,
    dashboardMetrics,
    leaderboardLoading,
    leaderboardData
  ]);

  // Memoized tab content rendering
  const renderTabContent = useCallback(() => {
    let content;
    switch (activeTab) {
      case 'missions':
        content = (
          <div className="w-full">
            <JadwalJaga userData={userData} onNavigate={setActiveTab} />
          </div>
        );
        break;
      case 'presensi':
        content = (
          <div className="w-full">
            <CreativeAttendanceDashboard userData={userData} />
          </div>
        );
        break;
      case 'jaspel':
        content = (
          <div className="w-full">
            <JaspelComponent />
          </div>
        );
        break;
      case 'profile':
        content = (
          <div className="w-full">
            <ProfileComponent />
          </div>
        );
        break;
      default:
        content = renderMainDashboard();
    }
    
    return content;
  }, [activeTab, userData, renderMainDashboard]);

  const renderBottomNavigation = () => (
    <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-slate-800/90 via-purple-800/80 to-slate-700/90 backdrop-blur-3xl px-6 py-4 border-t border-purple-400/20 relative z-10 rounded-t-3xl">
      <div className="flex justify-center items-center gap-4 md:gap-6">
        
        {/* Home - Active/Inactive */}
        <button 
          onClick={() => setActiveTab('home')}
          className={`relative group transition-all duration-500 ease-out ${
            activeTab === 'home' 
              ? 'bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115' 
              : ''
          }`}
        >
          {activeTab === 'home' && (
            <>
              <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
            </>
          )}
          <div className={`relative ${activeTab === 'home' ? '' : 'p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg'}`}>
            <div className="flex flex-col items-center">
              <Crown className={`w-5 h-5 mb-1 ${
                activeTab === 'home' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-cyan-400'
              }`} />
              <span className={`text-xs font-medium ${
                activeTab === 'home' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-cyan-400'
              }`}>Home</span>
            </div>
          </div>
        </button>
        
        {/* Calendar - Missions Button */}
        <button 
          onClick={() => setActiveTab('missions')}
          className={`relative group transition-all duration-500 ease-out ${
            activeTab === 'missions' 
              ? 'bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115' 
              : ''
          }`}
        >
          {activeTab === 'missions' && (
            <>
              <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
            </>
          )}
          <div className={`${activeTab === 'missions' ? '' : 'absolute inset-0 bg-gradient-to-br from-blue-500/0 to-blue-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500'}`}></div>
          <div className={`relative ${activeTab === 'missions' ? '' : 'p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-blue-500/20'}`}>
            <div className="flex flex-col items-center">
              <Calendar className={`w-5 h-5 mb-1 ${
                activeTab === 'missions' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-blue-400'
              }`} />
              <span className={`text-xs font-medium ${
                activeTab === 'missions' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-blue-400'
              }`}>Missions</span>
            </div>
          </div>
        </button>
        
        {/* Clock - Presensi Button */}
        <button 
          onClick={() => setActiveTab('presensi')}
          className={`relative group transition-all duration-500 ease-out ${
            activeTab === 'presensi' 
              ? 'bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115' 
              : ''
          }`}
        >
          {activeTab === 'presensi' && (
            <>
              <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
            </>
          )}
          <div className={`${activeTab === 'presensi' ? '' : 'absolute inset-0 bg-gradient-to-br from-green-500/0 to-green-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500'}`}></div>
          <div className={`relative ${activeTab === 'presensi' ? '' : 'p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-green-500/20'}`}>
            <div className="flex flex-col items-center">
              <Clock className={`w-5 h-5 mb-1 ${
                activeTab === 'presensi' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-green-400'
              }`} />
              <span className={`text-xs font-medium ${
                activeTab === 'presensi' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-green-400'
              }`}>Presensi</span>
            </div>
          </div>
        </button>
        
        {/* DollarSign - Jaspel Button */}
        <button 
          onClick={() => setActiveTab('jaspel')}
          className={`relative group transition-all duration-500 ease-out ${
            activeTab === 'jaspel' 
              ? 'bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115' 
              : ''
          }`}
        >
          {activeTab === 'jaspel' && (
            <>
              <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
            </>
          )}
          <div className={`${activeTab === 'jaspel' ? '' : 'absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-emerald-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500'}`}></div>
          <div className={`relative ${activeTab === 'jaspel' ? '' : 'p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-emerald-500/20'}`}>
            <div className="flex flex-col items-center">
              <DollarSign className={`w-5 h-5 mb-1 ${
                activeTab === 'jaspel' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-emerald-400'
              }`} />
              <span className={`text-xs font-medium ${
                activeTab === 'jaspel' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-emerald-400'
              }`}>Jaspel</span>
            </div>
          </div>
        </button>
        
        {/* Brain - Profile Button */}
        <button 
          onClick={() => setActiveTab('profile')}
          className={`relative group transition-all duration-500 ease-out ${
            activeTab === 'profile' 
              ? 'bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115' 
              : ''
          }`}
        >
          {activeTab === 'profile' && (
            <>
              <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
            </>
          )}
          <div className={`${activeTab === 'profile' ? '' : 'absolute inset-0 bg-gradient-to-br from-purple-500/0 to-purple-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500'}`}></div>
          <div className={`relative ${activeTab === 'profile' ? '' : 'p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-purple-500/20'}`}>
            <div className="flex flex-col items-center">
              <Brain className={`w-5 h-5 mb-1 ${
                activeTab === 'profile' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-purple-400'
              }`} />
              <span className={`text-xs font-medium ${
                activeTab === 'profile' 
                  ? 'text-white' 
                  : 'transition-colors duration-500 text-gray-400 group-hover:text-purple-400'
              }`}>Profile</span>
            </div>
          </div>
        </button>
        
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="min-h-screen relative overflow-hidden">
        
        {/* Floating Background Elements - Only show on home */}
        {activeTab === 'home' && (
          <div className="absolute inset-0 overflow-hidden">
            <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
            <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
            <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
          </div>
        )}

        {/* Tab Content */}
        <div className={`relative z-10 ${(activeTab === 'missions' || activeTab === 'presensi' || activeTab === 'jaspel' || activeTab === 'profile') ? 'w-full' : 'max-w-sm mx-auto md:max-w-md lg:max-w-lg xl:max-w-xl'}`}>
          {renderTabContent()}
        </div>

        {/* Unified Bottom Navigation - Always visible */}
        {renderBottomNavigation()}

        {/* Gaming Home Indicator */}
        <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-purple-400/60 to-transparent rounded-full shadow-lg shadow-purple-400/30"></div>
      </div>
    </div>
  );
};

export default HolisticMedicalDashboard;