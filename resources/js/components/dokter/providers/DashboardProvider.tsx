import React, { createContext, useContext, useReducer, useCallback, useEffect, ReactNode } from 'react';
import doctorApi from '../../../utils/doctorApi';
import { performanceMonitor } from '../../../utils/PerformanceMonitor';
import AttendanceCalculator from '../../../utils/AttendanceCalculator';
import { apiRateLimiter, withRateLimit } from '../../../utils/ApiRateLimiter';

// Dashboard state interfaces
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

interface LeaderboardDoctor {
  id: number;
  rank: number;
  name: string;
  level: number;
  xp: number;
  attendance_rate: number;
  streak_days: number;
  total_hours: number;
  total_days: number;
  total_patients: number;
  consultation_hours: number;
  procedures_count: number;
  badge: string;
  month: number;
  year: number;
  monthLabel: string;
}

interface AttendanceHistory {
  date: string;
  checkIn: string;
  checkOut: string;
  status: string;
  hours: string;
}

interface DashboardState {
  // Loading states
  isLoading: {
    dashboard: boolean;
    leaderboard: boolean;
    attendance: boolean;
  };
  
  // Error states
  errors: {
    dashboard: string | null;
    leaderboard: string | null;
    attendance: string | null;
  };
  
  // Data
  metrics: DashboardMetrics;
  leaderboard: LeaderboardDoctor[];
  attendanceHistory: AttendanceHistory[];
  
  // User info
  doctorLevel: number;
  experiencePoints: number;
  dailyStreak: number;
  
  // Cache info
  lastUpdated: {
    dashboard: number;
    leaderboard: number;
    attendance: number;
  };
}

// Action types
type DashboardAction =
  | { type: 'START_LOADING'; payload: keyof DashboardState['isLoading'] }
  | { type: 'STOP_LOADING'; payload: keyof DashboardState['isLoading'] }
  | { type: 'SET_ERROR'; payload: { key: keyof DashboardState['errors']; error: string | null } }
  | { type: 'SET_METRICS'; payload: DashboardMetrics }
  | { type: 'SET_LEADERBOARD'; payload: LeaderboardDoctor[] }
  | { type: 'SET_ATTENDANCE_HISTORY'; payload: AttendanceHistory[] }
  | { type: 'SET_DOCTOR_INFO'; payload: { level: number; xp: number; streak: number } }
  | { type: 'UPDATE_CACHE_TIME'; payload: { key: keyof DashboardState['lastUpdated'] } }
  | { type: 'RESET_STATE' };

// Initial state
const initialState: DashboardState = {
  isLoading: {
    dashboard: false,
    leaderboard: false,
    attendance: false,
  },
  errors: {
    dashboard: null,
    leaderboard: null,
    attendance: null,
  },
  metrics: {
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
  },
  leaderboard: [],
  attendanceHistory: [],
  doctorLevel: 7,
  experiencePoints: 2847,
  dailyStreak: 15,
  lastUpdated: {
    dashboard: 0,
    leaderboard: 0,
    attendance: 0,
  },
};

// Reducer
function dashboardReducer(state: DashboardState, action: DashboardAction): DashboardState {
  switch (action.type) {
    case 'START_LOADING':
      return {
        ...state,
        isLoading: {
          ...state.isLoading,
          [action.payload]: true,
        },
        errors: {
          ...state.errors,
          [action.payload]: null,
        },
      };
    
    case 'STOP_LOADING':
      return {
        ...state,
        isLoading: {
          ...state.isLoading,
          [action.payload]: false,
        },
      };
    
    case 'SET_ERROR':
      return {
        ...state,
        errors: {
          ...state.errors,
          [action.payload.key]: action.payload.error,
        },
        isLoading: {
          ...state.isLoading,
          [action.payload.key]: false,
        },
      };
    
    case 'SET_METRICS':
      return {
        ...state,
        metrics: action.payload,
      };
    
    case 'SET_LEADERBOARD':
      return {
        ...state,
        leaderboard: action.payload,
      };
    
    case 'SET_ATTENDANCE_HISTORY':
      console.log('🔄 REDUCER: Setting attendance history:', action.payload.length, 'records');
      console.log('🔄 REDUCER: First record:', action.payload[0] || 'none');
      return {
        ...state,
        attendanceHistory: action.payload,
      };
    
    case 'SET_DOCTOR_INFO':
      return {
        ...state,
        doctorLevel: action.payload.level,
        experiencePoints: action.payload.xp,
        dailyStreak: action.payload.streak,
      };
    
    case 'UPDATE_CACHE_TIME':
      return {
        ...state,
        lastUpdated: {
          ...state.lastUpdated,
          [action.payload.key]: Date.now(),
        },
      };
    
    case 'RESET_STATE':
      return initialState;
    
    default:
      return state;
  }
}

// Context
interface DashboardContextType {
  state: DashboardState;
  actions: {
    fetchDashboardData: () => Promise<void>;
    fetchLeaderboard: () => Promise<void>;
    fetchAttendanceHistory: () => Promise<void>; // NEW: For real-time history refresh
    refreshAll: () => Promise<void>;
    clearErrors: () => void;
    resetState: () => void;
  };
  cache: {
    isDashboardCacheValid: () => boolean;
    isLeaderboardCacheValid: () => boolean;
    isAttendanceCacheValid: () => boolean;
  };
}

const DashboardContext = createContext<DashboardContextType | undefined>(undefined);

// Cache validity helpers
const CACHE_DURATION = {
  dashboard: 10 * 60 * 1000, // 10 minutes
  leaderboard: 15 * 60 * 1000, // 15 minutes
  attendance: 5 * 60 * 1000, // 5 minutes
};

// Provider component
interface DashboardProviderProps {
  children: ReactNode;
  userData?: {
    name: string;
    email: string;
    role?: string;
  };
}

export const DashboardProvider: React.FC<DashboardProviderProps> = ({ children, userData }) => {
  const [state, dispatch] = useReducer(dashboardReducer, initialState);

  // Cache validity checkers
  const cache = {
    isDashboardCacheValid: useCallback(() => {
      return Date.now() - state.lastUpdated.dashboard < CACHE_DURATION.dashboard;
    }, [state.lastUpdated.dashboard]),
    
    isLeaderboardCacheValid: useCallback(() => {
      return Date.now() - state.lastUpdated.leaderboard < CACHE_DURATION.leaderboard;
    }, [state.lastUpdated.leaderboard]),
    
    isAttendanceCacheValid: useCallback(() => {
      return Date.now() - state.lastUpdated.attendance < CACHE_DURATION.attendance;
    }, [state.lastUpdated.attendance]),
  };

  // Fetch dashboard data with smart caching and rate limiting
  const fetchDashboardData = useCallback(async () => {
    // Check cache validity
    if (cache.isDashboardCacheValid() && state.metrics.jaspel.currentMonth > 0) {
      console.log('📊 Dashboard: Using cached data');
      return;
    }

    // Check rate limiter
    const permission = apiRateLimiter.canMakeRequest('dashboard');
    if (!permission.allowed) {
      console.warn(`🚫 Dashboard fetch blocked: ${permission.reason}`);
      if (permission.retryAfter) {
        console.warn(`⏰ Retry after: ${permission.retryAfter}ms`);
      }
      return;
    }

    dispatch({ type: 'START_LOADING', payload: 'dashboard' });
    
    try {
      performanceMonitor.start('dashboard-data-fetch');
      
      console.log('🔄 Dashboard: Fetching fresh data...');
      
      // Parallel API calls with error handling and rate limiting
      const [dashboardData, attendanceResponse] = await Promise.all([
        withRateLimit(() => doctorApi.getDashboard(), 'dashboard')().catch(err => {
          console.warn('Dashboard API failed:', err);
          apiRateLimiter.recordFailure(err);
          return null;
        }),
        fetch('/api/v2/dashboards/dokter/presensi', {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          credentials: 'same-origin'
        }).catch(err => {
          console.error('❌ Attendance API network error:', err);
          return null;
        })
      ]);

      if (dashboardData) {
        // Calculate jaspel metrics
        const currentJaspel = dashboardData.jaspel_summary?.current_month || 0;
        const previousJaspel = dashboardData.jaspel_summary?.last_month || 0;
        const growthPercentage = previousJaspel > 0 
          ? ((currentJaspel - previousJaspel) / previousJaspel) * 100
          : 0;
        const progressPercentage = Math.min(Math.max((currentJaspel / 10000000) * 100, 0), 100);

        // Initial metrics with basic attendance
        const metrics: DashboardMetrics = {
          jaspel: {
            currentMonth: currentJaspel,
            previousMonth: previousJaspel,
            growthPercentage: Math.round(growthPercentage * 10) / 10,
            progressPercentage: Math.round(progressPercentage * 10) / 10,
          },
          attendance: {
            rate: dashboardData.performance?.attendance_rate || 0,
            daysPresent: Math.round((dashboardData.performance?.attendance_rate || 0) / 100 * 30),
            totalDays: 30,
            displayText: `${dashboardData.performance?.attendance_rate || 0}%`,
          },
          patients: {
            today: dashboardData.patient_count?.today || 0,
            thisMonth: dashboardData.patient_count?.this_month || 0,
          },
        };

        // Process attendance data if available
        if (attendanceResponse && attendanceResponse.ok) {
          try {
            const attendanceData = await attendanceResponse.json();
            console.log('🔍 RAW ATTENDANCE API RESPONSE:', attendanceData);
            
            // Debug: Check the actual response structure
            console.log('🔍 Response structure check:', {
              hasData: !!attendanceData?.data,
              hasDirectHistory: !!attendanceData?.history,
              dataKeys: attendanceData?.data ? Object.keys(attendanceData.data) : 'no data key',
              rootKeys: Object.keys(attendanceData || {})
            });
            
            const history = attendanceData?.data?.history || attendanceData?.history || [];
            console.log('🔍 EXTRACTED HISTORY:', { 
              historyLength: history.length, 
              firstRecord: history[0] || 'none',
              historyType: typeof history,
              isArray: Array.isArray(history)
            });
            
            if (history.length > 0) {
              // Process attendance history
              const formattedHistory = history.map((record: any) => {
                const dateValue = record?.date || record?.tanggal;
                const checkIn = record?.time_in || record?.check_in || record?.jam_masuk;
                const checkOut = record?.time_out || record?.check_out || record?.jam_pulang;
                
                let formattedDate = '--/--/----';
                if (dateValue) {
                  const date = new Date(dateValue);
                  if (!isNaN(date.getTime())) {
                    formattedDate = date.toLocaleDateString('id-ID');
                  }
                }
                
                let formattedCheckIn = '-';
                if (checkIn && typeof checkIn === 'string' && checkIn.includes(':')) {
                  formattedCheckIn = checkIn.substring(0, 5);
                }
                
                let formattedCheckOut = '-';
                if (checkOut && typeof checkOut === 'string' && checkOut.includes(':')) {
                  formattedCheckOut = checkOut.substring(0, 5);
                }
                
                let status = 'Hadir';
                if (record.status) {
                  const statusLower = String(record.status).toLowerCase();
                  if (statusLower === 'late' || statusLower === 'terlambat') {
                    status = 'Terlambat';
                  } else if (statusLower === 'absent' || statusLower === 'tidak hadir') {
                    status = 'Tidak Hadir';
                  }
                }
                
                let hours = '0h 0m';
                if (formattedCheckIn !== '-' && formattedCheckOut !== '-') {
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
                    // Fallback to provided duration
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
              }).filter(record => record.date !== '--/--/----');

              // Calculate unified attendance metrics
              const currentMonth = new Date();
              const monthStart = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
              const monthEnd = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
              
              const attendanceMetrics = AttendanceCalculator.calculateAttendanceMetrics(
                formattedHistory,
                monthStart,
                monthEnd
              );
              
              if (attendanceMetrics) {
                metrics.attendance = {
                  rate: attendanceMetrics.attendancePercentage,
                  daysPresent: attendanceMetrics.presentDays,
                  totalDays: attendanceMetrics.totalDays,
                  displayText: `${attendanceMetrics.attendancePercentage}%`,
                };
              }

              // Store the formatted attendance history
              console.log('🔄 Dispatching attendance history:', formattedHistory.length, 'records');
              dispatch({ type: 'SET_ATTENDANCE_HISTORY', payload: formattedHistory });
              console.log('✅ Attendance history dispatched successfully');
            }
          } catch (attendanceError) {
            console.error('❌ Failed to process attendance data:', attendanceError);
            console.error('❌ Attendance response was:', attendanceResponse);
          }
        } else {
          console.warn('⚠️ ATTENDANCE API ISSUE:', {
            hasResponse: !!attendanceResponse,
            responseOk: attendanceResponse?.ok,
            status: attendanceResponse?.status,
            statusText: attendanceResponse?.statusText,
            url: attendanceResponse?.url
          });
          
          // Try to read error response body
          if (attendanceResponse && !attendanceResponse.ok) {
            try {
              const errorText = await attendanceResponse.text();
              console.error('❌ Attendance API error response:', errorText);
            } catch (e) {
              console.error('❌ Could not read error response:', e);
            }
          }
        }

        dispatch({ type: 'SET_METRICS', payload: metrics });
        dispatch({ type: 'UPDATE_CACHE_TIME', payload: { key: 'dashboard' } });
        
        console.log('✅ Dashboard: Data updated successfully');
      }
      
      performanceMonitor.end('dashboard-data-fetch');
      dispatch({ type: 'STOP_LOADING', payload: 'dashboard' });
      
    } catch (error) {
      console.error('❌ Dashboard: Failed to fetch data:', error);
      dispatch({ 
        type: 'SET_ERROR', 
        payload: { 
          key: 'dashboard', 
          error: error instanceof Error ? error.message : 'Unknown error' 
        } 
      });
    }
  }, [cache, state.metrics.jaspel.currentMonth]);

  // Fetch leaderboard data with smart caching and rate limiting
  const fetchLeaderboard = useCallback(async () => {
    // Check cache validity
    if (cache.isLeaderboardCacheValid() && state.leaderboard.length > 0) {
      console.log('🏆 Leaderboard: Using cached data');
      return;
    }

    // Check rate limiter
    const permission = apiRateLimiter.canMakeRequest('leaderboard');
    if (!permission.allowed) {
      console.warn(`🚫 Leaderboard fetch blocked: ${permission.reason}`);
      if (permission.retryAfter) {
        console.warn(`⏰ Retry after: ${permission.retryAfter}ms`);
      }
      return;
    }

    dispatch({ type: 'START_LOADING', payload: 'leaderboard' });
    
    try {
      console.log('🔄 Leaderboard: Fetching fresh data...');
      
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1;
      const currentYear = currentDate.getFullYear();
      
      // Use rate limited fetch
      const protectedFetch = withRateLimit(
        () => fetch(`/api/v2/dashboards/dokter/leaderboard?month=${currentMonth}&year=${currentYear}`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          credentials: 'include'
        }),
        'leaderboard'
      );
      
      const response = await protectedFetch();
      
      if (response.ok) {
        const data = await response.json();
        const leaderboard = data?.data?.leaderboard || data?.data || [];
        
        if (Array.isArray(leaderboard)) {
          // ✅ FIXED: Remove dummy fallback data generation - use actual API data only
          const transformedLeaderboard: LeaderboardDoctor[] = leaderboard
            .filter((doctor: any) => {
              // Filter out dummy test doctors and doctors without real data
              const isDummyDoctor = doctor.name && (
                doctor.name.includes('Dr. Dokter Umum') ||
                doctor.name.includes('Dr. Spesialis Penyakit Dalam') ||
                doctor.name.startsWith('Doctor ') ||
                doctor.name === 'Test Doctor'
              );
              
              // Only include doctors with actual patient data or valid attendance
              const hasRealData = (
                (doctor.total_patients !== undefined && doctor.total_patients > 0) ||
                (doctor.attendance_rate !== undefined && doctor.attendance_rate > 0) ||
                (doctor.total_hours !== undefined && doctor.total_hours > 0)
              );
              
              return !isDummyDoctor && hasRealData;
            })
            .map((doctor: any, index: number) => ({
              id: doctor.id,
              rank: doctor.rank || index + 1,
              name: doctor.name,
              level: doctor.level || 1,
              xp: doctor.xp || doctor.experience_points || 0,
              attendance_rate: doctor.attendance_rate || doctor.attendance || 0,
              streak_days: doctor.streak_days || doctor.streak || 0,
              total_hours: doctor.total_hours || 0,
              total_days: doctor.total_days || 0,
              // ✅ CRITICAL FIX: Use only real patient data, no fallback generation
              total_patients: doctor.total_patients || doctor.patient_count || 0,
              consultation_hours: doctor.consultation_hours || 0,
              procedures_count: doctor.procedures_count || doctor.total_procedures || 0,
              badge: doctor.badge || (index === 0 ? '👑' : index === 1 ? '🥈' : index === 2 ? '🥉' : '⭐'),
              month: currentMonth,
              year: currentYear,
              monthLabel: new Date(currentYear, currentMonth - 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
            }));
          
          // Record success for circuit breaker
          apiRateLimiter.recordSuccess();
          
          dispatch({ type: 'SET_LEADERBOARD', payload: transformedLeaderboard });
          
          // Update current user info if found
          const currentUserData = transformedLeaderboard.find(doctor => 
            doctor.name === userData?.name || (userData?.name && doctor.name.includes(userData.name))
          );
          
          if (currentUserData) {
            dispatch({ 
              type: 'SET_DOCTOR_INFO', 
              payload: { 
                level: currentUserData.level, 
                xp: currentUserData.xp, 
                streak: currentUserData.streak_days 
              } 
            });
          }
          
          dispatch({ type: 'UPDATE_CACHE_TIME', payload: { key: 'leaderboard' } });
          console.log('✅ Leaderboard: Data updated successfully');
        }
      } else {
        // Record failure for circuit breaker
        apiRateLimiter.recordFailure({ response });
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      dispatch({ type: 'STOP_LOADING', payload: 'leaderboard' });
      
    } catch (error) {
      console.error('❌ Leaderboard: Failed to fetch data:', error);
      // Record failure for circuit breaker
      apiRateLimiter.recordFailure(error);
      dispatch({ 
        type: 'SET_ERROR', 
        payload: { 
          key: 'leaderboard', 
          error: error instanceof Error ? error.message : 'Unknown error' 
        } 
      });
      
      // ✅ FIXED: No fallback dummy data - use empty array on error
      console.warn('🚫 Using empty leaderboard due to API error');
      dispatch({ type: 'SET_LEADERBOARD', payload: [] });
    }
  }, [cache, state.leaderboard.length, userData]);

  // Fetch attendance history specifically (for real-time updates)
  const fetchAttendanceHistory = useCallback(async () => {
    console.log('🔄 Attendance History: Fetching fresh data...');
    
    dispatch({ type: 'START_LOADING', payload: 'attendance' });
    
    try {
      // Rate limiting check
      const permission = apiRateLimiter.canMakeRequest('attendance-history');
      if (!permission.allowed) {
        console.warn(`🚫 Attendance history fetch blocked: ${permission.reason}`);
        return;
      }

      const response = await withRateLimit(
        () => fetch('/api/v2/dashboards/dokter/presensi', {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'include'
        }),
        'attendance-history'
      )();

      if (response.ok) {
        const attendanceData = await response.json();
        const history = attendanceData?.data?.history || attendanceData?.history || [];
        
        if (history.length > 0) {
          const formattedHistory = history.map((record: any) => {
            const dateValue = record?.date || record?.tanggal;
            const checkIn = record?.time_in || record?.check_in || record?.jam_masuk;
            const checkOut = record?.time_out || record?.check_out || record?.jam_keluar;
            const statusValue = record?.status || record?.keterangan;
            const hoursValue = record?.duration || record?.hours || record?.jam_kerja;

            return {
              date: typeof dateValue === 'string' ? dateValue : (dateValue?.toLocaleDateString?.('id-ID') || 'N/A'),
              checkIn: checkIn || '-',
              checkOut: checkOut || '-',
              status: statusValue || 'Unknown',
              hours: hoursValue || '0h 0m'
            };
          });
          
          // Record success and update state
          apiRateLimiter.recordSuccess();
          dispatch({ type: 'SET_ATTENDANCE_HISTORY', payload: formattedHistory });
          dispatch({ type: 'UPDATE_CACHE_TIME', payload: { key: 'attendance' } });
          
          console.log('✅ Attendance History: Data refreshed successfully', formattedHistory.length, 'records');
        }
      } else {
        apiRateLimiter.recordFailure({ response });
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    } catch (error) {
      console.error('❌ Attendance History: Failed to fetch data:', error);
      apiRateLimiter.recordFailure(error);
      
      dispatch({ 
        type: 'SET_ERROR', 
        payload: { 
          key: 'attendance', 
          error: error instanceof Error ? error.message : 'Gagal memuat riwayat presensi' 
        } 
      });
    } finally {
      dispatch({ type: 'STOP_LOADING', payload: 'attendance' });
    }
  }, []);

  // Actions object
  const actions = {
    fetchDashboardData,
    fetchLeaderboard,
    fetchAttendanceHistory,
    refreshAll: useCallback(async () => {
      await Promise.all([
        fetchDashboardData(),
        fetchLeaderboard(),
        fetchAttendanceHistory()
      ]);
    }, [fetchDashboardData, fetchLeaderboard, fetchAttendanceHistory]),
    clearErrors: useCallback(() => {
      dispatch({ type: 'SET_ERROR', payload: { key: 'dashboard', error: null } });
      dispatch({ type: 'SET_ERROR', payload: { key: 'leaderboard', error: null } });
      dispatch({ type: 'SET_ERROR', payload: { key: 'attendance', error: null } });
    }, []),
    resetState: useCallback(() => {
      dispatch({ type: 'RESET_STATE' });
    }, [])
  };

  // Context value
  const contextValue: DashboardContextType = {
    state,
    actions,
    cache
  };

  return (
    <DashboardContext.Provider value={contextValue}>
      {children}
    </DashboardContext.Provider>
  );
};

// Custom hook to use dashboard context
export const useDashboard = (): DashboardContextType => {
  const context = useContext(DashboardContext);
  if (context === undefined) {
    throw new Error('useDashboard must be used within a DashboardProvider');
  }
  return context;
};

export default DashboardProvider;