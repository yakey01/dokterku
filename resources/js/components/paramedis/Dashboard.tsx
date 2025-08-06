import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Progress } from '../ui/progress';
import { 
  Calendar, 
  Clock, 
  MapPin, 
  Activity, 
  TrendingUp, 
  TrendingDown, 
  CheckCircle, 
  XCircle, 
  DollarSign, 
  Users, 
  Award, 
  ArrowRight,
  AlertCircle,
  Timer,
  RefreshCw,
  Navigation
} from 'lucide-react';

interface JadwalItem {
  id: string;
  tanggal: string;
  waktu: string;
  lokasi: string;
  jenis: 'pagi' | 'siang' | 'malam';
  status: 'scheduled' | 'completed' | 'missed';
}

interface DashboardProps {
  userData?: {
    name: string;
    greeting?: string;
  };
}

export function Dashboard({ userData: propUserData }: DashboardProps) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [userData, setUserData] = useState<any>(null);
  const [jadwalMendatang, setJadwalMendatang] = useState<JadwalItem[]>([]);
  const [loadingSchedules, setLoadingSchedules] = useState(true);
  const [dashboardStats, setDashboardStats] = useState<any>(null);
  const [loadingStats, setLoadingStats] = useState(true);
  const [lastLocationUpdate, setLastLocationUpdate] = useState<string | null>(null);
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Fetch real dashboard stats from API - moved outside useEffect for scope
  const fetchDashboardStats = async (forceRefresh = false) => {
    try {
      setLoadingStats(true);
      
      // Get CSRF token and API token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
      
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      };
      
      // Add Bearer token if available
      if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
      }

      // Use the real API endpoint for dashboard data
      const url = '/api/v2/dashboards/paramedis';
      
      const response = await fetch(url, {
        credentials: 'include',
        headers
      });
      if (response.ok) {
        const result = await response.json();
        // Handle both API formats: test API returns direct data, real API has success/data structure
        const data = result.success ? result.data : result;
        if (data) {
          // Check if work location data has changed
          const newLocationUpdate = data.user?.work_location?.updated_at;
          if (newLocationUpdate && newLocationUpdate !== lastLocationUpdate) {
            setLastLocationUpdate(newLocationUpdate);
            
            // Only log in development
            if (process.env.NODE_ENV === 'development') {
              console.log('📍 Location updated');
            }
          }
          
          // Transform test API data to match expected dashboard format
          const transformedData = {
            stats: {
              jaspel_month: data.jaspel_monthly || data.stats?.jaspel_month || 0,
              jaspel_last_month: data.last_month_total || data.stats?.jaspel_last_month || 0,
              jaspel_growth_percent: data.growth_percent || data.stats?.jaspel_growth_percent || 0,
              patients_today: data.stats?.patients_today || 0,
              tindakan_today: data.stats?.tindakan_today || 0,
              shifts_week: data.stats?.shifts_week || 0
            },
            user: data.user || { name: data.paramedis_name || 'Loading...' },
            performance: data.performance || {}
          };
          
          setDashboardStats(transformedData);
          console.log('✅ Dashboard stats loaded');
        }
      } else {
        console.error('Failed to fetch dashboard stats:', response.status, response.statusText);
        if (response.status === 401) {
          console.error('Authentication required. Please ensure you are logged in.');
        }
      }
    } catch (error) {
      console.error('Error fetching dashboard stats:', error);
    } finally {
      setLoadingStats(false);
    }
  };

  // Manual refresh function for user
  const handleManualRefresh = async () => {
    setIsRefreshing(true);
    await fetchDashboardStats(true);
    setTimeout(() => setIsRefreshing(false), 1000);
  };

  // Fetch real schedule data from API with optimized logging
  const fetchSchedules = async () => {
    try {
      setLoadingSchedules(true);
      
      // Get CSRF token and API token for authentication
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
      
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      };
      
      // Add Bearer token if available
      if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
      }
      
      // Use the real API endpoint for schedules
      const response = await fetch('/api/v2/dashboards/paramedis/schedules', {
        credentials: 'include',
        headers
      });
      
      if (response.ok) {
        const schedules = await response.json();
        
        // Debug full response structure 
        console.log('🔍 PARAMEDIS: Full API response:', schedules);
        console.log('🔍 PARAMEDIS: Response type:', typeof schedules);
        console.log('🔍 PARAMEDIS: Is array:', Array.isArray(schedules));
        
        // Ensure we have an array
        if (Array.isArray(schedules)) {
          setJadwalMendatang(schedules);
          console.log('✅ PARAMEDIS: Loaded', schedules.length, 'schedules');
          
          // Only log first schedule for debugging
          if (schedules.length > 0) {
            console.log('📋 PARAMEDIS: Next schedule:', {
              tanggal: schedules[0].tanggal,
              waktu: schedules[0].waktu,
              lokasi: schedules[0].lokasi,
              jenis: schedules[0].jenis
            });
          }
        } else {
          console.error('❌ PARAMEDIS: Invalid response format:', {
            responseType: typeof schedules,
            responseValue: schedules,
            isArray: Array.isArray(schedules)
          });
          setJadwalMendatang([]);
        }
      } else {
        console.error('❌ PARAMEDIS: API error:', response.status);
        setJadwalMendatang([]);
      }
    } catch (error) {
      console.error('❌ PARAMEDIS: Network error:', error);
      setJadwalMendatang([]);
    } finally {
      setLoadingSchedules(false);
    }
  };

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    
    // Get user data from meta tag
    const userDataMeta = document.querySelector('meta[name="user-data"]');
    if (userDataMeta) {
      try {
        const data = JSON.parse(userDataMeta.getAttribute('content') || '{}');
        setUserData(data);
      } catch (e) {
        console.error('Failed to parse user data:', e);
      }
    }
    
    fetchDashboardStats();
    fetchSchedules();

    // Set up interval to check for location updates every 30 seconds
    const locationCheckInterval = setInterval(() => {
      fetchDashboardStats(false); // Check for updates without forcing refresh
    }, 30000);
    
    return () => {
      clearInterval(timer);
      clearInterval(locationCheckInterval);
    };
  }, []);

  const getShiftColor = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white dark:from-yellow-500 dark:to-yellow-600';
      case 'siang': return 'bg-gradient-to-r from-orange-400 to-orange-500 text-white dark:from-orange-500 dark:to-orange-600';
      case 'malam': return 'bg-gradient-to-r from-purple-400 to-purple-500 text-white dark:from-purple-500 dark:to-purple-600';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
    }
  };

  const formatTanggal = (tanggal: string) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long'
    });
  };

  const getShiftIcon = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return '☀️';
      case 'siang': return '🌤️';
      case 'malam': return '🌙';
      default: return '⏰';
    }
  };

  const nextSchedule = jadwalMendatang.length > 0 ? jadwalMendatang[0] : null;

  const container = {
    hidden: { opacity: 0 },
    show: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1
      }
    }
  };

  const item = {
    hidden: { opacity: 0, y: 20 },
    show: { opacity: 1, y: 0 }
  };

  // Use real data from API or fallback to dummy data
  const stats = dashboardStats ? {
    attendance: {
      current: Math.round(dashboardStats.performance?.attendance_rate || 0),
      target: 90,
      change: +5
    },
    performance: {
      score: dashboardStats.performance?.patient_satisfaction || 92,
      change: +3,
      attendance_rank: dashboardStats.performance?.attendance_rank,
      total_staff: dashboardStats.performance?.total_staff
    },
    jaspel: {
      thisMonth: dashboardStats.stats?.jaspel_month || 0,
      lastMonth: dashboardStats.stats?.jaspel_last_month || 0,
      change: dashboardStats.stats?.jaspel_growth_percent || 0
    }
  } : {
    attendance: {
      current: 0,
      target: 90,
      change: 0
    },
    performance: {
      score: 0,
      change: 0,
      attendance_rank: null,
      total_staff: 0
    },
    jaspel: {
      thisMonth: 0,
      lastMonth: 0,
      change: 0
    }
  };

  // Minimal debug log for attendance data (only when needed)
  if (process.env.NODE_ENV === 'development') {
    console.log('📊 Stats loaded:', { attendance: stats.attendance.current });
  }

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6 theme-transition"
    >
      {/* Header Welcome */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="text-white">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <Activity className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Dashboard</h2>
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">
                    {propUserData?.greeting || userData?.greeting || 'Selamat datang kembali'}, {propUserData?.name || userData?.name || 'Paramedis'}
                  </p>
                </div>
              </div>
              
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">Waktu Sekarang</p>
                  <p className="text-lg font-semibold text-white text-subheading-mobile">
                    {currentTime.toLocaleTimeString('id-ID', { 
                      hour: '2-digit', 
                      minute: '2-digit' 
                    })}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">
                    {currentTime.toLocaleDateString('id-ID', {
                      weekday: 'long',
                      day: 'numeric',
                      month: 'long'
                    })}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Work Location Card - Only show if work location exists */}
      {dashboardStats?.user?.work_location && (
        <motion.div variants={item}>
          <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-green-50/50 dark:from-gray-900 dark:to-green-950/30 backdrop-blur-sm overflow-hidden card-enhanced">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                    <Navigation className="w-5 h-5 text-green-600 dark:text-green-400" />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold text-high-contrast text-subheading-mobile">Lokasi Kerja</h3>
                    <p className="text-sm text-medium-contrast font-medium text-mobile-friendly">Area validasi presensi</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Button
                    onClick={handleManualRefresh}
                    disabled={isRefreshing}
                    variant="outline"
                    size="sm"
                    className="h-8 px-3 text-xs hover:bg-green-50 dark:hover:bg-green-950/50 hover:border-green-200 dark:hover:border-green-700"
                  >
                    <RefreshCw className={`w-3 h-3 mr-1 ${isRefreshing ? 'animate-spin' : ''}`} />
                    {isRefreshing ? 'Refresh...' : 'Refresh'}
                  </Button>
                </div>
              </div>

              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl p-4 text-white relative overflow-hidden"
              >
                {/* Background decoration */}
                <div className="absolute top-0 right-0 w-20 h-20 bg-white/10 dark:bg-white/15 rounded-full -translate-y-10 translate-x-10" />
                
                <div className="relative z-10">
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <h4 className="font-semibold text-lg">{dashboardStats.user.work_location.name}</h4>
                      <Badge className="bg-white/20 text-white border-white/30 text-xs">
                        {dashboardStats.user.work_location.is_active ? '✅ Aktif' : '❌ Nonaktif'}
                      </Badge>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <MapPin className="w-4 h-4 text-green-200 dark:text-green-300" />
                      <span className="text-sm text-green-100 dark:text-green-200">
                        {dashboardStats.user.work_location.address}
                      </span>
                    </div>
                    
                    <div className="flex items-center justify-between text-sm">
                      <div className="flex items-center gap-2">
                        <div className="w-2 h-2 bg-green-200 rounded-full"></div>
                        <span className="text-green-100">Radius: {dashboardStats.user.work_location.radius_meters}m</span>
                      </div>
                      <div className="text-green-100 text-xs">
                        {lastLocationUpdate && `Updated: ${new Date(lastLocationUpdate).toLocaleTimeString('id-ID')}`}
                      </div>
                    </div>
                  </div>
                </div>
              </motion.div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Next Schedule Card */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 dark:from-gray-900 dark:to-blue-950/30 backdrop-blur-sm overflow-hidden card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Calendar className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-high-contrast text-subheading-mobile">Jadwal Jaga Berikutnya</h3>
                  <p className="text-sm text-medium-contrast font-medium text-mobile-friendly">Shift yang akan datang</p>
                </div>
              </div>
              <Badge variant="outline" className="border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 font-medium">
                Segera
              </Badge>
            </div>

            {loadingSchedules ? (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.2 }}
                className="bg-gradient-to-r from-gray-400 to-gray-500 dark:from-gray-600 dark:to-gray-700 rounded-xl p-5 text-white relative overflow-hidden"
              >
                <div className="flex items-center justify-center space-x-3">
                  <div className="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  <span className="text-sm font-medium">Memuat jadwal...</span>
                </div>
              </motion.div>
            ) : nextSchedule ? (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.2 }}
                className="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl p-5 text-white relative overflow-hidden"
              >
                {/* Background decoration */}
                <div className="absolute top-0 right-0 w-24 h-24 bg-white/10 dark:bg-white/15 rounded-full -translate-y-12 translate-x-12" />
                <div className="absolute bottom-0 left-0 w-16 h-16 bg-white/5 dark:bg-white/10 rounded-full translate-y-8 -translate-x-8" />
                
                <div className="relative z-10">
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-3">
                      <div className="text-2xl">{getShiftIcon(nextSchedule.jenis)}</div>
                      <div>
                        <h4 className="text-lg font-semibold text-subheading-mobile">{formatTanggal(nextSchedule.tanggal)}</h4>
                        <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">Shift {nextSchedule.jenis}</p>
                      </div>
                    </div>
                    <Badge className={`${getShiftColor(nextSchedule.jenis)} text-xs font-semibold`}>
                      {nextSchedule.jenis.charAt(0).toUpperCase() + nextSchedule.jenis.slice(1)}
                    </Badge>
                  </div>
                  
                  <div className="space-y-3">
                    <div className="flex items-center gap-3">
                      <Clock className="w-4 h-4 text-blue-200 dark:text-blue-300" />
                      <span className="text-sm font-medium text-blue-100 dark:text-blue-200">
                        {nextSchedule.waktu}
                      </span>
                    </div>
                    <div className="flex items-center gap-3">
                      <MapPin className="w-4 h-4 text-blue-200 dark:text-blue-300" />
                      <span className="text-sm font-medium text-blue-100 dark:text-blue-200">
                        {nextSchedule.lokasi}
                      </span>
                    </div>
                  </div>
                </div>
              </motion.div>
            ) : (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.2 }}
                className="bg-gradient-to-r from-amber-400 to-amber-500 dark:from-amber-500 dark:to-amber-600 rounded-xl p-5 text-white relative overflow-hidden"
              >
                <div className="flex items-center justify-center space-x-3">
                  <AlertCircle className="w-6 h-6" />
                  <div className="text-center">
                    <p className="font-medium">Tidak ada jadwal jaga</p>
                    <p className="text-sm text-amber-100 dark:text-amber-200">Hubungi admin untuk penjadwalan</p>
                  </div>
                </div>
              </motion.div>
            )}

            <motion.div 
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.3 }}
              className="mt-4"
            >
              <Button 
                variant="outline" 
                className="w-full hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700 group font-medium"
              >
                <span>Lihat Semua Jadwal</span>
                <ArrowRight className="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Performance Stats */}
      <motion.div variants={item} className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4 lg:gap-6">
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardContent className="p-4 md:p-5">
            <div className="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
              <div className="w-8 h-8 md:w-10 md:h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                <CheckCircle className="w-4 h-4 md:w-5 md:h-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-high-contrast text-mobile-friendly">Persentase Kehadiran</h4>
                <p className="text-2xl font-bold text-green-600 dark:text-green-400 text-heading-mobile">
                  {loadingStats ? (
                    <span className="animate-pulse">⏳</span>
                  ) : (
                    `${stats.attendance.current}%`
                  )}
                </p>
              </div>
            </div>
            <Progress value={stats.attendance.current} className="h-2 mb-2" />
            <div className="flex items-center gap-1 text-xs">
              <TrendingUp className="w-3 h-3 text-green-500 dark:text-green-400" />
              <span className="font-semibold text-green-600 dark:text-green-400">+{stats.attendance.change}%</span>
              <span className="text-medium-contrast font-medium">dari bulan lalu</span>
            </div>
          </CardContent>
        </Card>

        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardContent className="p-4 md:p-5">
            <div className="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
              <div className="w-8 h-8 md:w-10 md:h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                <Award className="w-4 h-4 md:w-5 md:h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-high-contrast text-mobile-friendly">Urutan Kehadiran</h4>
                <p className="text-2xl font-bold text-blue-600 dark:text-blue-400 text-heading-mobile">
                  {loadingStats ? (
                    <span className="animate-pulse">⏳</span>
                  ) : (
                    `#${stats.performance.attendance_rank || '--'}`
                  )}
                </p>
              </div>
            </div>
            <Progress value={stats.performance.attendance_rank ? Math.max(0, 100 - ((stats.performance.attendance_rank / stats.performance.total_staff) * 100)) : 0} className="h-2 mb-2" />
            <div className="flex items-center gap-1 text-xs">
              <Award className="w-3 h-3 text-blue-500 dark:text-blue-400" />
              <span className="font-semibold text-blue-600 dark:text-blue-400">
                dari {loadingStats ? '...' : (stats.performance.total_staff || 0)}
              </span>
              <span className="text-medium-contrast font-medium">paramedis</span>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Enhanced Jaspel Summary with Progress */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center">
                  <DollarSign className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-high-contrast text-subheading-mobile">Jaspel Bulan Ini</h3>
                  <p className="text-sm text-medium-contrast font-medium text-mobile-friendly">
                    {dashboardStats?.period_info && (
                      <>Progress {dashboardStats.period_info.month_progress}% bulan ini</>
                    )}
                  </p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 text-heading-mobile">
                  Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
                </p>
                <div className="flex items-center gap-1 text-xs">
                  <TrendingUp className="w-3 h-3 text-emerald-500 dark:text-emerald-400" />
                  <span className="font-semibold text-emerald-600 dark:text-emerald-400">+{stats.jaspel.change}%</span>
                </div>
              </div>
            </div>
            
            {/* Progress Bar */}
            {dashboardStats?.period_info && (
              <div className="mb-4">
                <div className="flex justify-between text-xs text-medium-contrast mb-1">
                  <span>Hari ke-{dashboardStats.period_info.days_passed}</span>
                  <span>{dashboardStats.period_info.days_in_month} hari total</span>
                </div>
                <Progress 
                  value={dashboardStats.period_info.month_progress} 
                  className="h-2 bg-emerald-100 dark:bg-emerald-950/30" 
                />
              </div>
            )}
            
            <div className="grid grid-cols-2 gap-3 mb-4">
              <div className="bg-emerald-50 dark:bg-emerald-950/30 rounded-lg p-3">
                <div className="text-xs text-emerald-700 dark:text-emerald-300 font-medium">Rata-rata Harian</div>
                <div className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                  Rp {(dashboardStats?.daily_average || 0).toLocaleString('id-ID')}
                </div>
              </div>
              <div className="bg-emerald-50 dark:bg-emerald-950/30 rounded-lg p-3">
                <div className="text-xs text-emerald-700 dark:text-emerald-300 font-medium">Estimasi Mingguan</div>
                <div className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                  Rp {(dashboardStats?.jaspel_weekly || 0).toLocaleString('id-ID')}
                </div>
              </div>
            </div>
            
            <div className="bg-emerald-50 dark:bg-emerald-950/30 rounded-lg p-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-emerald-700 dark:text-emerald-300">Bulan Lalu</span>
                <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                  Rp {stats.jaspel.lastMonth.toLocaleString('id-ID')}
                </span>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Actions */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Timer className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Aksi Cepat
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700 font-medium">
                <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                </div>
                Check In/Out Sekarang
              </Button>
            </motion.div>
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 hover:border-emerald-200 dark:hover:border-emerald-700 font-medium">
                <div className="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center">
                  <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                Lihat Jadwal Minggu Ini
              </Button>
            </motion.div>

            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-purple-50 dark:hover:bg-purple-950/50 hover:border-purple-200 dark:hover:border-purple-700 font-medium">
                <div className="w-8 h-8 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                  <Activity className="w-4 h-4 text-purple-600 dark:text-purple-400" />
                </div>
                Lihat Laporan Kinerja
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Upcoming Schedules Preview */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Calendar className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Jadwal Minggu Ini
            </CardTitle>
          </CardHeader>
          <CardContent>
            {loadingSchedules ? (
              <div className="flex items-center justify-center py-8">
                <div className="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mr-3"></div>
                <span className="text-sm text-medium-contrast">Memuat jadwal...</span>
              </div>
            ) : jadwalMendatang.length === 0 ? (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.5 }}
                className="text-center py-12"
              >
                <motion.div
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ delay: 0.2, duration: 0.4 }}
                  className="relative mb-6"
                >
                  <div className="relative inline-flex items-center justify-center w-24 h-24 mx-auto mb-4">
                    <div className="absolute inset-0 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-full opacity-20 animate-pulse"></div>
                    <div className="relative bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/50 dark:to-teal-900/50 rounded-full p-6 border border-emerald-100 dark:border-emerald-800/50">
                      <Calendar className="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
                    </div>
                  </div>
                  <div className="text-4xl mb-2">🏥</div>
                </motion.div>

                <motion.div
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ delay: 0.4, duration: 0.4 }}
                  className="space-y-2"
                >
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                    Tidak ada jadwal minggu ini
                  </h3>
                  <p className="text-sm text-gray-500 dark:text-gray-400 max-w-xs mx-auto leading-relaxed">
                    Jadwal jaga Anda untuk minggu ini belum tersedia. Hubungi admin untuk pengaturan jadwal.
                  </p>
                </motion.div>

                <motion.div
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ delay: 0.6, duration: 0.4 }}
                  className="mt-6"
                >
                  <div className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-full border border-emerald-100 dark:border-emerald-800/50">
                    <Timer className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    <span className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                      Segera diperbarui
                    </span>
                  </div>
                </motion.div>
              </motion.div>
            ) : (
              <div className="space-y-3">
                {jadwalMendatang.slice(0, 3).map((schedule, index) => (
                <motion.div
                  key={schedule.id}
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: index * 0.1 }}
                  className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/70 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div className="text-lg">{getShiftIcon(schedule.jenis)}</div>
                    <div>
                      <p className="text-sm font-medium text-high-contrast">{formatTanggal(schedule.tanggal)}</p>
                      <p className="text-xs text-muted-foreground font-medium">{schedule.waktu}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-medium text-high-contrast">{schedule.lokasi}</p>
                    <Badge 
                      variant="outline" 
                      className={`text-xs font-medium ${
                        schedule.jenis === 'pagi' ? 'border-yellow-300 dark:border-yellow-600 text-yellow-700 dark:text-yellow-400' :
                        schedule.jenis === 'siang' ? 'border-orange-300 dark:border-orange-600 text-orange-700 dark:text-orange-400' :
                        'border-purple-300 dark:border-purple-600 text-purple-700 dark:text-purple-400'
                      }`}
                    >
                      {schedule.jenis}
                    </Badge>
                  </div>
                </motion.div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}