import React, { useState, useEffect } from 'react';
import { debug, api, transform, performance, state, prodError } from '../../utils/debugLogger';
import getUnifiedAuthInstance from '../../utils/UnifiedAuth';
import { 
  Calendar, 
  Clock, 
  MapPin, 
  Award, 
  TrendingUp, 
  Star, 
  Target,
  Shield,
  Zap,
  Activity,
  Heart,
  Users,
  AlertCircle,
  ChevronRight,
  Trophy,
  Flame,
  Loader2,
  CheckCircle,
  Phone,
  Coffee,
  UserCheck,
  ArrowLeft,
  Eye,
  FileText,
  User,
  X,
  Crown
} from 'lucide-react';

interface JadwalJagaProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

interface Mission {
  id: number;
  title: string;
  subtitle: string;
  date: string;
  full_date: string;
  day_name: string;
  time: string;
  location: string;
  type: 'regular' | 'urgent' | 'special' | 'training';
  difficulty: 'easy' | 'medium' | 'hard' | 'legendary';
  status: 'available' | 'in-progress' | 'completed' | 'locked';
  status_jaga: string;
  description: string;
  requirements?: string[];
  peran: string;
  employee_name: string;
  shift_template?: {
    id: number;
    nama_shift: string;
    jam_masuk: string;
    jam_pulang: string;
  };
}

export function JadwalJaga({ userData, onNavigate }: JadwalJagaProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [missions, setMissions] = useState<Mission[]>([]);
  const [totalShifts, setTotalShifts] = useState(12);
  const [completedShifts, setCompletedShifts] = useState(8);
  const [upcomingShifts, setUpcomingShifts] = useState(4);
  const [totalHours, setTotalHours] = useState(96);
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState('portrait');
  const [lastFetch, setLastFetch] = useState<number>(0);

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 6;

  // API Data Fetching function (extracted for reusability)
  const fetchJadwalJaga = async (isRefresh = false) => {
    debug.log(`JadwalJaga: ${isRefresh ? 'Refreshing' : 'Starting'} API fetch at ${new Date().toLocaleTimeString()}`);
    
    try {
      if (!isRefresh) setLoading(true);
      setError(null);
        
        // Use web session authentication (no Sanctum token needed)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        // Use real API endpoint for jadwal jaga data with web session auth
        debug.log('Making API call to /api/v2/dashboards/dokter/jadwal-jaga with web session auth');
        const cacheBuster = isRefresh ? `?refresh=${Date.now()}` : '';
        const response = await fetch(`/api/v2/dashboards/dokter/jadwal-jaga${cacheBuster}`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin' // Important for web session auth
        });

        api('jadwal-jaga', { status: response.status, ok: response.ok });

        if (!response.ok) {
          if (response.status === 401) {
            throw new Error('Authentication required. Please login again.');
          } else if (response.status === 404) {
            throw new Error('API endpoint not found. Please check configuration.');
          } else {
            throw new Error(`API Error: ${response.status} - ${response.statusText}`);
          }
        }

        const data = await response.json();
        debug.log('API Data received:', { 
          hasData: !!data.data,
          calendarEventsCount: data.data?.calendar_events?.length || 0,
          weeklyScheduleCount: data.data?.weekly_schedule?.length || 0,
          hasScheduleStats: !!data.data?.schedule_stats,
          success: data.success,
          message: data.message
        });
        
        // Check if API response is successful
        if (!data.success) {
          throw new Error(data.message || 'API returned unsuccessful response');
        }
        
        // NEW: Extract schedule statistics from API
        if (data.data?.schedule_stats) {
          const stats = data.data.schedule_stats;
          debug.log('Schedule stats from API:', stats);
          
          setCompletedShifts(stats.completed || 0);
          setUpcomingShifts(stats.upcoming || 0);
          setTotalHours(stats.total_hours || 0);
          setTotalShifts(stats.total_shifts || 0);
        }
        
        // Combine both weekly_schedule and calendar_events to show all schedules
        const weeklySchedules = data.data?.weekly_schedule || [];
        const calendarEvents = data.data?.calendar_events || [];
        
        // Combine and deduplicate by ID to avoid duplicate cards
        const combinedSchedules = [...weeklySchedules, ...calendarEvents];
        const seenIds = new Set();
        const apiSchedules = combinedSchedules.filter(schedule => {
          const id = schedule.id;
          if (seenIds.has(id)) {
            return false; // Skip duplicate
          }
          seenIds.add(id);
          return true;
        });
        
        debug.log('Schedule sources:', {
          weeklySchedulesCount: weeklySchedules.length,
          calendarEventsCount: calendarEvents.length,
          totalCombined: combinedSchedules.length,
          afterDeduplication: apiSchedules.length,
          duplicatesRemoved: combinedSchedules.length - apiSchedules.length
        });
        
        const transformedMissions = transformApiData(apiSchedules);
        transform('missions', { count: transformedMissions.length });
        
        // IMPORTANT: Only use fallback if there's a clear authentication issue
        if (transformedMissions.length === 0) {
          debug.warn('No API schedules found - checking if this is expected');
          
          // Check if user has any schedules in database
          const hasSchedules = data.data?.schedule_stats?.total_shifts > 0;
          
          if (hasSchedules) {
            debug.log('User has schedules but none returned - this might be a data issue');
            setError('Schedules exist but cannot be displayed. Please contact administrator.');
          } else {
            debug.log('No schedules found for user - this is expected if user has no assignments');
            setError('No schedules assigned. Please contact administrator for duty assignments.');
          }
          
          // Don't use fallback data - show empty state instead
          setMissions([]);
        } else {
          debug.log(`Using real API schedule data - ${transformedMissions.length} schedules loaded`);
          debug.log('Schedule cards that will be displayed:', transformedMissions.map(m => ({
            id: m.id,
            title: m.title,
            shift: m.shift_template?.nama_shift,
            time: m.time,
            date: m.date
          })));
          
          // Check if data has changed
          const dataChanged = JSON.stringify(transformedMissions) !== JSON.stringify(missions);
          if (dataChanged) {
            state('JadwalJaga', `Schedule data has changed, updating UI with ${transformedMissions.length} cards`);
          }
          setMissions(transformedMissions);
        }
        debug.log('JadwalJaga: Data loaded successfully');
    } catch (err) {
      // Use prodError for critical API failures
      prodError('Failed to fetch jadwal jaga:', err);
      debug.error('Failed to fetch jadwal jaga:', err);
      
      const errorMessage = err instanceof Error ? err.message : 'Unknown error occurred';
      setError(`Failed to load schedule data: ${errorMessage}`);
      
      // Don't use fallback data - show error state instead
      setMissions([]);
      debug.log('Showing error state instead of fallback data');
    } finally {
      setLoading(false);
      debug.log('JadwalJaga: Loading complete');
      setLastFetch(Date.now());
    }
  };

  // Initial data fetch
  useEffect(() => {
    fetchJadwalJaga();
  }, []);

  // Auto-refresh every 60 seconds to catch new schedules (optimized for performance)
  useEffect(() => {
    const refreshInterval = setInterval(() => {
      debug.log('Auto-refresh: Fetching latest schedule data...');
      fetchJadwalJaga(true).catch((err) => {
        debug.error('Auto-refresh failed:', err);
        // Don't show prod error for auto-refresh failures to avoid spam
      });
    }, 60000); // 60 seconds (optimized from 30 seconds to reduce server load)

    return () => clearInterval(refreshInterval);
  }, []);

  // Manual refresh function for external use
  const refreshSchedules = async () => {
    debug.log('Manual refresh triggered');
    setLoading(true);
    await fetchJadwalJaga(true);
  };

  // Force refresh function for immediate updates
  const forceRefresh = async () => {
    debug.log('Force refresh triggered');
    setLoading(true);
    setError(null);
    
    try {
      const token = userData?.token || 
                    localStorage.getItem('auth_token') || 
                    document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
                    '';
      
      const response = await fetch(`/api/v2/dashboards/dokter/jadwal-jaga?refresh=${Date.now()}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Cache-Control': 'no-cache, no-store, must-revalidate',
          'Pragma': 'no-cache',
          'Expires': '0'
        }
      });

      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }

      const data = await response.json();
      debug.log('Force refresh data received:', data);
      
      // Update missions with new data
      const weeklySchedules = data.data?.weekly_schedule || [];
      const calendarEvents = data.data?.calendar_events || [];
      const combinedSchedules = [...weeklySchedules, ...calendarEvents];
      const seenIds = new Set();
      const apiSchedules = combinedSchedules.filter(schedule => {
        const id = schedule.id;
        if (seenIds.has(id)) return false;
        seenIds.add(id);
        return true;
      });
      
      const transformedMissions = transformApiData(apiSchedules);
      setMissions(transformedMissions);
      
      // Update stats
      if (data.data?.schedule_stats) {
        const stats = data.data.schedule_stats;
        setCompletedShifts(stats.completed || 0);
        setUpcomingShifts(stats.upcoming || 0);
        setTotalHours(stats.total_hours || 0);
        setTotalShifts(stats.total_shifts || 0);
      }
      
      debug.log('Force refresh completed successfully');
    } catch (err) {
      debug.error('Force refresh failed:', err);
      setError('Gagal memperbarui jadwal');
    } finally {
      setLoading(false);
    }
  };

  // Enhanced transform API data to Mission format with better relationship handling
  const transformApiData = (apiSchedules: any[]): Mission[] => {
    transform('starting', apiSchedules);
    
    const transformedMissions = apiSchedules.map((schedule, index) => {
      debug.log(`Processing schedule ${index}:`, schedule);
      debug.log(`Schedule details:`, {
        hasTime: !!schedule.time,
        timeValue: schedule.time || 'no time field',
        start: schedule.start,
        end: schedule.end,
        shift_info: schedule.shift_info,
        shift_template: schedule.shift_template
      });
      
      // Handle both calendar_events and weekly_schedule formats
      const isCalendarEvent = schedule.start && schedule.title;
      const isWeeklySchedule = schedule.tanggal_jaga || schedule.shift_template;
      
      debug.log(`Schedule type:`, { 
        isCalendarEvent, 
        isWeeklySchedule,
        hasShiftTemplate: !!schedule.shift_template,
        hasShiftInfo: !!schedule.shift_info,
        shiftData: schedule.shift_template || schedule.shift_info || null
      });
      
      let mission: Mission;
      
      if (isCalendarEvent) {
        // Calendar event format - enhanced with proper shift_info handling
        const shiftInfo = schedule.shift_info || {};
        
        // Format time correctly - extract time from datetime or format time string
        const formatTime = (time: string) => {
          if (!time) return time;
          
          // If it's a full datetime (2025-08-06T09:00), extract just the time part
          if (time.includes('T')) {
            const timePart = time.split('T')[1];
            if (timePart) {
              return timePart.split(':').slice(0, 2).join(':');
            }
          }
          
          // If time has seconds (HH:MM:SS), remove them
          return time.split(':').slice(0, 2).join(':');
        };
        
        // Override any existing time field from API
        const formattedJamMasuk = formatTime(shiftInfo.jam_masuk) || '08:00';
        const formattedJamPulang = formatTime(shiftInfo.jam_pulang) || '16:00';
        const shiftTime = `${formattedJamMasuk} - ${formattedJamPulang}`;
        
        debug.log('Time formatting:', {
          originalJamMasuk: shiftInfo.jam_masuk,
          originalJamPulang: shiftInfo.jam_pulang,
          formattedJamMasuk,
          formattedJamPulang,
          finalTime: shiftTime
        });
        
        mission = {
          id: schedule.id || index + 1,
          title: schedule.title || shiftInfo.nama_shift || "Dokter Jaga",
          subtitle: getShiftSubtitle(shiftInfo.nama_shift),
          date: new Date(schedule.start).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
          full_date: schedule.start,
          day_name: new Date(schedule.start).toLocaleDateString('id-ID', { weekday: 'long' }),
          time: shiftTime,
          location: shiftInfo.unit_kerja || schedule.description || 'Unit Kerja',
          type: getShiftType(shiftInfo.nama_shift),
          difficulty: 'easy' as const,
          status: 'available' as const,
          status_jaga: mapApiStatus(shiftInfo.status || 'aktif'),
          description: schedule.description || `${shiftInfo.nama_shift || 'Shift'} duty assignment`,
          peran: shiftInfo.peran || "Dokter Jaga",
          employee_name: shiftInfo.employee_name || userData?.name || "dr. Medical Officer",
          shift_template: {
            id: shiftInfo.id || index + 1,
            nama_shift: shiftInfo.nama_shift || 'Pagi',
            jam_masuk: formatTime(shiftInfo.jam_masuk) || '08:00',
            jam_pulang: formatTime(shiftInfo.jam_pulang) || '16:00'
          }
        };
      } else if (isWeeklySchedule) {
        // Weekly schedule format (JadwalJaga model) - enhanced with proper relationship data
        const shiftTemplate = schedule.shift_template || {};
        const scheduleDate = schedule.tanggal_jaga || schedule.date;
        
        // Format time correctly - extract time from datetime or format time string
        const formatTime = (time: string) => {
          if (!time) return time;
          
          // If it's a full datetime (2025-08-06T09:00), extract just the time part
          if (time.includes('T')) {
            const timePart = time.split('T')[1];
            if (timePart) {
              return timePart.split(':').slice(0, 2).join(':');
            }
          }
          
          // If time has seconds (HH:MM:SS), remove them
          return time.split(':').slice(0, 2).join(':');
        };
        
        // Override any existing time field from API
        const formattedJamMasuk = formatTime(shiftTemplate.jam_masuk) || '08:00';
        const formattedJamPulang = formatTime(shiftTemplate.jam_pulang) || '16:00';
        const shiftTime = `${formattedJamMasuk} - ${formattedJamPulang}`;
        
        debug.log('Weekly schedule time formatting:', {
          originalJamMasuk: shiftTemplate.jam_masuk,
          originalJamPulang: shiftTemplate.jam_pulang,
          formattedJamMasuk,
          formattedJamPulang,
          finalTime: shiftTime
        });
        
        mission = {
          id: schedule.id || index + 1,
          title: shiftTemplate.nama_shift || "Dokter Jaga",
          subtitle: getShiftSubtitle(shiftTemplate.nama_shift),
          date: new Date(scheduleDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
          full_date: scheduleDate,
          day_name: new Date(scheduleDate).toLocaleDateString('id-ID', { weekday: 'long' }),
          time: shiftTime,
          location: schedule.unit_kerja || 'Unit Kerja',
          type: getShiftType(shiftTemplate.nama_shift),
          difficulty: 'easy' as const,
          status: 'available' as const,
          status_jaga: mapApiStatus(schedule.status_jaga || 'aktif'),
          description: schedule.keterangan || `${shiftTemplate.nama_shift || 'Shift'} duty assignment`,
          peran: schedule.peran || "Dokter Jaga",
          employee_name: schedule.employee_name || userData?.name || "dr. Medical Officer",
          shift_template: {
            id: shiftTemplate.id || index + 1,
            nama_shift: shiftTemplate.nama_shift || 'Pagi',
            jam_masuk: formatTime(shiftTemplate.jam_masuk) || '08:00',
            jam_pulang: formatTime(shiftTemplate.jam_pulang) || '16:00'
          }
        };
      } else {
        // Fallback for unknown format
        debug.warn('Unknown schedule format, using fallback:', schedule);
        mission = {
          id: schedule.id || index + 1,
          title: "Dokter Jaga",
          subtitle: "General Medical Duty",
          date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
          full_date: new Date().toISOString(),
          day_name: new Date().toLocaleDateString('id-ID', { weekday: 'long' }),
          time: '08:00 - 16:00',
          location: 'Unit Kerja',
          type: 'regular' as const,
          difficulty: 'easy' as const,
          status: 'available' as const,
          status_jaga: 'Terjadwal',
          description: 'Medical duty assignment',
          peran: "Dokter Jaga",
          employee_name: userData?.name || "dr. Medical Officer",
          shift_template: {
            id: index + 1,
            nama_shift: 'Pagi',
            jam_masuk: '08:00',
            jam_pulang: '16:00'
          }
        };
      }
      
      debug.log('Created mission:', mission);
      debug.log('Mission time details:', {
        id: mission.id,
        shift: mission.shift_template?.nama_shift,
        displayTime: mission.time,
        jam_masuk: mission.shift_template?.jam_masuk,
        jam_pulang: mission.shift_template?.jam_pulang,
        originalScheduleTime: schedule.time || 'no time in schedule'
      });
      return mission;
    }).filter(mission => mission !== null);
    
    transform('final missions', transformedMissions);
    return transformedMissions;
  };

  // Helper function to get shift subtitle
  const getShiftSubtitle = (namaShift: string): string => {
    switch (namaShift?.toLowerCase()) {
      case 'malam': return "Emergency Critical Care";
      case 'pagi': return "Morning Patient Rounds";
      case 'siang': return "Afternoon Clinical Care";
      case 'sore': return "Evening Medical Service";
      default: return "General Outpatient Care";
    }
  };

  // Helper function to get shift type
  const getShiftType = (namaShift: string): 'regular' | 'urgent' | 'special' | 'training' => {
    switch (namaShift?.toLowerCase()) {
      case 'malam': return 'urgent';
      case 'emergency': return 'urgent';
      case 'training': return 'training';
      case 'workshop': return 'training';
      default: return 'regular';
    }
  };

  // Map API status to display status
  const mapApiStatus = (apiStatus: string): string => {
    const statusMap: Record<string, string> = {
      'selesai': 'Completed',
      'completed': 'Completed',
      'aktif': 'Aktif',
      'active': 'Aktif',
      'berlangsung': 'Aktif',
      'upcoming': 'Terjadwal',
      'terjadwal': 'Terjadwal',
      'cuti': 'Cuti',
      'izin': 'Izin',
      'oncall': 'OnCall'
    };
    return statusMap[apiStatus?.toLowerCase()] || 'Terjadwal';
  };

  // Fallback missions for demo/offline mode
  const getFallbackMissions = (): Mission[] => {
    return [
      {
        id: 1,
        title: "Dokter Jaga",
        subtitle: "Emergency Critical Care",
        date: "1 Agustus 2025",
        full_date: "2025-08-01",
        day_name: "Jumat",
        time: "21:00 - 07:00",
        location: "IGD - Trauma Center",
        type: "urgent" as const,
        difficulty: "easy" as const,
        status: "available" as const,
        status_jaga: "Aktif",
        description: "Siaga tinggi - malam weekend dengan tingkat emergensi tinggi",
        peran: "Dokter Jaga",
        employee_name: "dr. Ahmad Rizki, M.Kes",
        shift_template: {
          id: 1,
          nama_shift: "Malam",
          jam_masuk: "21:00",
          jam_pulang: "07:00"
        }
      },
      {
        id: 2,
        title: "Dokter Jaga",
        subtitle: "Morning Patient Rounds",
        date: "4 Agustus 2025",
        full_date: "2025-08-04",
        day_name: "Minggu",
        time: "07:00 - 14:00",
        location: "Ward 3A - Internal Medicine",
        type: "regular" as const,
        difficulty: "easy" as const,
        status: "available" as const,
        status_jaga: "Terjadwal",
        description: "Visite rutin weekend dengan monitoring pasien rawat inap",
        peran: "Dokter Jaga",
        employee_name: userData?.name || "Dokter",
        shift_template: {
          id: 2,
          nama_shift: "Pagi",
          jam_masuk: "07:00",
          jam_pulang: "14:00"
        }
      },
      {
        id: 3,
        title: "Dokter Jaga",
        subtitle: "General Outpatient Care",
        date: "5 Agustus 2025",
        full_date: "2025-08-05",
        day_name: "Selasa",
        time: "08:00 - 15:00",
        location: "Outpatient Clinic 2",
        type: "regular" as const,
        difficulty: "easy" as const,
        status: "available" as const,
        status_jaga: "Terjadwal",
        description: "Pelayanan rawat jalan rutin dengan jadwal reguler",
        peran: "Dokter Jaga",
        employee_name: "dr. Budi Santoso, M.Kes",
        shift_template: {
          id: 3,
          nama_shift: "Pagi",
          jam_masuk: "08:00",
          jam_pulang: "15:00"
        }
      },
      {
        id: 4,
        title: "Dokter Jaga",
        subtitle: "Intensive Care Unit",
        date: "7 Agustus 2025",
        full_date: "2025-08-07",
        day_name: "Kamis",
        time: "20:00 - 08:00",
        location: "ICU - Level 2",
        type: "urgent" as const,
        difficulty: "easy" as const,
        status: "available" as const,
        status_jaga: "Aktif",
        description: "ICU monitoring 24/7 dengan pasien kritis",
        peran: "Dokter Jaga",
        employee_name: "dr. Sari Dewi, Sp.An",
        shift_template: {
          id: 4,
          nama_shift: "Malam",
          jam_masuk: "20:00",
          jam_pulang: "08:00"
        }
      },
      {
        id: 5,
        title: "Dokter Jaga",
        subtitle: "Evening Medical Service",
        date: "10 Agustus 2025",
        full_date: "2025-08-10",
        day_name: "Minggu",
        time: "16:00 - 21:00",
        location: "Conference Hall A",
        type: "regular" as const,
        difficulty: "easy" as const,
        status: "available" as const,
        status_jaga: "Terjadwal",
        description: "Evening shift medical service",
        peran: "Dokter Jaga",
        employee_name: "dr. Rahman Ali, M.Kes",
        shift_template: {
          id: 5,
          nama_shift: "Sore",
          jam_masuk: "16:00",
          jam_pulang: "21:00"
        }
      },
      {
        id: 6,
        title: "Dokter Jaga",
        subtitle: "Emergency Night Shift",
        date: "12 Agustus 2025",
        full_date: "2025-08-12",
        day_name: "Selasa",
        time: "22:00 - 06:00",
        location: "IGD - Emergency Room",
        type: "urgent" as const,
        difficulty: "easy" as const,
        status: "available" as const,
        status_jaga: "Aktif",
        description: "Jaga malam IGD dengan tingkat kesiapsiagaan tinggi",
        peran: "Dokter Jaga",
        employee_name: "dr. Maya Sari, M.Kes",
        shift_template: {
          id: 6,
          nama_shift: "Malam",
          jam_masuk: "22:00",
          jam_pulang: "06:00"
        }
      }
    ].filter(mission => mission.shift_template?.nama_shift !== "Sore");
  };

  useEffect(() => {
    const checkDevice = () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      setIsIpad(width >= 768);
      setOrientation(width > height ? 'landscape' : 'portrait');
    };
    
    checkDevice();
    window.addEventListener('resize', checkDevice);
    window.addEventListener('orientationchange', checkDevice);
    
    return () => {
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
  }, []);

  // Fallback: Update stats based on loaded missions (only when API doesn't provide schedule_stats)
  useEffect(() => {
    if (missions.length > 0 && (completedShifts === 8 && upcomingShifts === 4 && totalHours === 96)) {
      // Only recalculate if we still have default values (means API didn't provide schedule_stats)
      debug.log('Calculating stats from missions as fallback');
      setTotalShifts(missions.length);
      setCompletedShifts(missions.filter(m => m.status_jaga === 'Completed').length);
      setUpcomingShifts(missions.filter(m => m.status_jaga === 'Terjadwal').length);
      // Calculate total hours from shift templates
      const totalHoursCalc = missions.reduce((sum, mission) => {
        if (mission.shift_template) {
          const start = mission.shift_template.jam_masuk.split(':');
          const end = mission.shift_template.jam_pulang.split(':');
          const startMinutes = parseInt(start[0]) * 60 + parseInt(start[1]);
          const endMinutes = parseInt(end[0]) * 60 + parseInt(end[1]);
          let duration = endMinutes - startMinutes;
          if (duration < 0) duration += 24 * 60; // Handle overnight shifts
          return sum + (duration / 60);
        }
        // Don't assume default hours - only count actual shift template data
        return sum;
      }, 0);
      setTotalHours(Math.round(totalHoursCalc));
    } else if (missions.length > 0) {
      debug.log('Using API-provided schedule stats, skipping mission-based calculation');
    }
  }, [missions, completedShifts, upcomingShifts, totalHours]);

  // Pagination calculations
  const totalPages = Math.ceil(missions.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentMissions = missions.slice(startIndex, endIndex);

  const goToPage = (page: number) => {
    setCurrentPage(page);
  };

  const goToPrevious = () => {
    if (currentPage > 1) {
      setCurrentPage(currentPage - 1);
    }
  };

  const goToNext = () => {
    if (currentPage < totalPages) {
      setCurrentPage(currentPage + 1);
    }
  };

  // Enhanced helper functions for gaming-style appearance
  const getMissionIcon = (mission: Mission) => {
    if (mission.difficulty === 'legendary') return Shield;
    if (mission.type === 'urgent') return Activity;
    if (mission.type === 'special') return Zap;
    if (mission.type === 'training') return Target;
    if (mission.subtitle.toLowerCase().includes('malam')) return Shield;
    if (mission.subtitle.toLowerCase().includes('pagi')) return Heart;
    return Users;
  };

  const getMissionGradient = (mission: Mission) => {
    switch (mission.status_jaga) {
      case 'Aktif': return 'from-green-600 to-emerald-600';
      case 'OnCall': return 'from-red-600 to-orange-600';
      case 'Cuti': return 'from-purple-600 to-pink-600';
      case 'Izin': return 'from-blue-600 to-cyan-600';
      default: return 'from-indigo-600 to-blue-600';
    }
  };

  const getMissionBgGlow = (mission: Mission) => {
    switch (mission.status_jaga) {
      case 'Aktif': return 'from-green-500/20 to-emerald-500/20';
      case 'OnCall': return 'from-red-500/20 to-orange-500/20';
      case 'Cuti': return 'from-purple-500/20 to-pink-500/20';
      case 'Izin': return 'from-blue-500/20 to-cyan-500/20';
      default: return 'from-indigo-500/20 to-blue-500/20';
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-hidden">
        
        {/* Dynamic Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 bg-blue-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '10%', width: '30vw', maxWidth: '400px', height: '30vw', maxHeight: '400px' }}></div>
          <div className="absolute top-60 bg-purple-500 bg-opacity-5 rounded-full blur-2xl animate-bounce" style={{ right: '5%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }}></div>
          <div className="absolute bottom-80 bg-pink-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '15%', width: '28vw', maxWidth: '380px', height: '28vw', maxHeight: '380px' }}></div>
        </div>

        {/* Header */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pt-8 pb-6">
          <div className="text-center mb-6">
            <div className="flex items-center justify-center mb-2">
              <h1 className={`font-bold bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent
                ${isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl sm:text-3xl'}
              `}>
                Medical Mission Central
              </h1>
              <button
                onClick={forceRefresh}
                disabled={loading}
                className="ml-4 p-2 bg-white/10 hover:bg-white/20 rounded-xl border border-white/20 transition-all duration-300 disabled:opacity-50"
                title="Force refresh schedules (immediate update)"
              >
                <Loader2 className={`w-5 h-5 text-cyan-400 ${loading ? 'animate-spin' : ''}`} />
              </button>
            </div>
            <p className={`text-purple-200 ${isIpad ? 'text-lg md:text-xl' : 'text-base'}`}>
              Elite Doctor Duty Assignments
            </p>
            {error && (
              <div className="flex items-center justify-center mt-4">
                <div className="text-xs bg-red-600/20 hover:bg-red-600/40 border border-red-400/30 text-red-300 px-4 py-2 rounded-xl backdrop-blur-sm">
                  ⚠️ {error}
                </div>
              </div>
            )}
            {lastFetch > 0 && (
              <div className="text-xs text-gray-400 mt-2">
                Last updated: {new Date(lastFetch).toLocaleTimeString()}
              </div>
            )}
          </div>
          
          {/* Gaming-Style Stats Dashboard */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            {/* Total Shifts */}
            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-purple-400/20 hover:border-purple-400/40 transition-all duration-300">
              <div className="flex items-center justify-between mb-2">
                <Calendar className="w-5 h-5 text-purple-400" />
                <Crown className="w-4 h-4 text-purple-300/50" />
              </div>
              <div className="text-2xl font-bold text-purple-400">{totalShifts}</div>
              <div className="text-xs text-purple-300/80">Total Missions</div>
            </div>
            
            {/* Completed Shifts */}
            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-green-400/20 hover:border-green-400/40 transition-all duration-300">
              <div className="flex items-center justify-between mb-2">
                <Trophy className="w-5 h-5 text-green-400" />
                <Star className="w-4 h-4 text-green-300/50" />
              </div>
              <div className="text-2xl font-bold text-green-400">{completedShifts}</div>
              <div className="text-xs text-green-300/80">Completed</div>
            </div>
            
            {/* Upcoming Shifts */}
            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-orange-400/20 hover:border-orange-400/40 transition-all duration-300">
              <div className="flex items-center justify-between mb-2">
                <Zap className="w-5 h-5 text-orange-400" />
                <Flame className="w-4 h-4 text-orange-300/50" />
              </div>
              <div className="text-2xl font-bold text-orange-400">{upcomingShifts}</div>
              <div className="text-xs text-orange-300/80">Upcoming</div>
            </div>
            
            {/* Total Hours */}
            <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-blue-400/20 hover:border-blue-400/40 transition-all duration-300">
              <div className="flex items-center justify-between mb-2">
                <Activity className="w-5 h-5 text-blue-400" />
                <TrendingUp className="w-4 h-4 text-blue-300/50" />
              </div>
              <div className="text-2xl font-bold text-blue-400">{totalHours}</div>
              <div className="text-xs text-blue-300/80">Total Hours</div>
            </div>
          </div>
          
          {/* Epic Progress Bar */}
          <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
            <div className="flex items-center justify-between mb-2">
              <span className="text-white font-semibold">Mission Progress</span>
              <span className="text-cyan-400 font-bold">{completedShifts} / {totalShifts}</span>
            </div>
            <div className="bg-gray-800/50 rounded-full h-3 relative overflow-hidden">
              <div 
                className="absolute inset-0 bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 rounded-full transition-all duration-1000 ease-out"
                style={{ width: `${totalShifts > 0 ? (completedShifts / totalShifts) * 100 : 0}%` }}
              >
                <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
              </div>
            </div>
          </div>
        </div>

        {/* Loading State */}
        {loading && (
          <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 py-12">
            <div className="flex items-center justify-center">
              <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-8 border border-purple-400/20">
                <Loader2 className="w-8 h-8 animate-spin text-purple-400 mx-auto mb-3" />
                <span className="text-purple-300 text-center block">Loading Mission Database...</span>
              </div>
            </div>
          </div>
        )}

        {/* Mission Cards Grid */}
        {!loading && (
          <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-32">
            <div className={`
              grid gap-6 md:gap-8
              ${isIpad && orientation === 'landscape' 
                ? 'lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3' 
                : 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3'
              }
            `}>
              {currentMissions.map((mission) => {
                const Icon = getMissionIcon(mission);
                const gradient = getMissionGradient(mission);
                const bgGlow = getMissionBgGlow(mission);
                
                return (
                  <div
                    key={mission.id}
                    className="relative group cursor-default transform transition-all duration-300 hover:scale-[1.01]"
                  >
                    {/* Compact Card */}
                    <div className={`
                      relative bg-white/8 backdrop-blur-xl rounded-2xl overflow-hidden
                      border border-white/15 group-hover:border-white/25
                      transition-all duration-300 group-hover:bg-white/10
                      ${isIpad ? 'p-4' : 'p-3'}
                    `}>
                      
                      {/* Elegant Background Glow */}
                      <div className={`
                        absolute inset-0 bg-gradient-to-br ${bgGlow} opacity-0 
                        group-hover:opacity-30 transition-opacity duration-400
                      `}></div>

                      {/* Compact Header */}
                      <div className="relative z-10 mb-3">
                        <div className="flex items-start space-x-3 mb-3">
                          {/* Small Icon */}
                          <div className={`
                            bg-gradient-to-br ${gradient} rounded-xl flex items-center justify-center
                            shadow-sm transition-all duration-300
                            ${isIpad ? 'w-10 h-10 p-2.5' : 'w-8 h-8 p-2'}
                          `}>
                            <Icon className={`text-white ${isIpad ? 'w-5 h-5' : 'w-4 h-4'}`} />
                          </div>
                          
                          {/* Compact Title */}
                          <div className="flex-1">
                            <h3 className={`font-semibold text-white mb-1 ${isIpad ? 'text-base' : 'text-sm'}`}>
                              {mission.shift_template?.nama_shift || mission.title || 'Dokter Jaga'}
                            </h3>
                            <p className={`text-gray-300 font-medium ${isIpad ? 'text-sm' : 'text-xs'}`}>
                              {mission.subtitle || 'Shift Jaga'}
                            </p>
                          </div>
                        </div>

                        {/* Compact Date & Time */}
                        <div className={`
                          bg-white/8 backdrop-blur-md rounded-xl border border-white/10
                          ${isIpad ? 'p-3' : 'p-2.5'}
                        `}>
                          <div className="text-center space-y-1">
                            <div className={`text-gray-300 font-medium ${isIpad ? 'text-sm' : 'text-xs'}`}>
                              {new Date(mission.full_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit' })}
                            </div>
                            <div className={`text-gray-400 ${isIpad ? 'text-xs' : 'text-xs'}`}>
                              {mission.peran}
                            </div>
                            <div className={`text-white font-semibold ${isIpad ? 'text-base' : 'text-sm'}`}>
                              {mission.time}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
            
            {/* Empty State */}
            {currentMissions.length === 0 && !loading && (
              <div className="text-center py-12">
                <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-8 border border-white/10 max-w-md mx-auto">
                  <Shield className="h-16 w-16 mx-auto mb-4 text-purple-400" />
                  <h3 className="text-xl font-bold text-white mb-2">
                    {error ? 'Error Loading Schedules' : 'No Schedules Available'}
                  </h3>
                  <p className="text-gray-400 text-sm mb-4">
                    {error || 'No medical schedules available for this page'}
                  </p>
                  {error && (
                    <button
                      onClick={forceRefresh}
                      className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200"
                    >
                      Try Again
                    </button>
                  )}
                </div>
              </div>
            )}

            {/* World-Class Minimalist Pagination */}
            {totalPages > 1 && (
              <div className="flex flex-col items-center mt-10 space-y-4">
                {/* Main Pagination Container - Glass Morphism Design */}
                <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10 shadow-2xl shadow-purple-500/10">
                  <div className="flex items-center space-x-3">
                    {/* Previous Button - Enhanced Accessibility */}
                    <button
                      onClick={goToPrevious}
                      disabled={currentPage === 1}
                      className={`
                        group relative flex items-center justify-center w-12 h-12 rounded-xl
                        transition-all duration-300 transform font-medium
                        ${currentPage === 1 
                          ? 'bg-white/5 text-gray-500 cursor-not-allowed opacity-50' 
                          : 'bg-gradient-to-br from-cyan-500/70 via-purple-500/70 to-pink-500/70 text-white hover:from-cyan-400 hover:via-purple-400 hover:to-pink-400 hover:scale-110 hover:shadow-xl hover:shadow-purple-500/30 active:scale-95'}
                      `}
                      aria-label="Previous page"
                    >
                      <ChevronRight className="w-5 h-5 rotate-180 transition-transform duration-300 group-hover:-translate-x-0.5" />
                      {currentPage !== 1 && (
                        <>
                          <div className="absolute inset-0 bg-gradient-to-br from-white/20 to-white/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                          <div className="absolute inset-0 bg-gradient-to-br from-cyan-500/20 via-purple-500/20 to-pink-500/20 rounded-xl blur opacity-0 group-hover:opacity-60 transition-opacity duration-300 -z-10 scale-150"></div>
                        </>
                      )}
                    </button>

                    {/* Page Numbers - Gaming Style with Improved UX */}
                    <div className="flex items-center space-x-2">
                      {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                        <button
                          key={page}
                          onClick={() => goToPage(page)}
                          className={`
                            group relative w-12 h-12 rounded-xl font-bold text-sm
                            transition-all duration-300 transform overflow-hidden
                            focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:ring-offset-2 focus:ring-offset-transparent
                            ${currentPage === page
                              ? 'bg-gradient-to-br from-cyan-500 via-purple-500 to-pink-500 text-white scale-110 shadow-xl shadow-purple-500/40 border border-white/20'
                              : 'bg-white/5 text-purple-200 hover:bg-white/10 hover:text-white hover:scale-105 hover:shadow-lg hover:shadow-white/10 border border-white/10 hover:border-white/20'}
                          `}
                          aria-label={`Go to page ${page}`}
                          aria-current={currentPage === page ? 'page' : undefined}
                        >
                          {/* Active page premium glow effect */}
                          {currentPage === page && (
                            <>
                              <div className="absolute inset-0 bg-gradient-to-br from-white/30 via-white/10 to-transparent animate-pulse rounded-xl"></div>
                              <div className="absolute inset-0 bg-gradient-to-br from-cyan-500/30 via-purple-500/30 to-pink-500/30 rounded-xl blur opacity-60 -z-10 scale-150"></div>
                            </>
                          )}
                          
                          {/* Sophisticated hover effect for inactive pages */}
                          {currentPage !== page && (
                            <>
                              <div className="absolute inset-0 bg-gradient-to-br from-cyan-500/10 via-purple-500/10 to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                              <div className="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                            </>
                          )}
                          
                          <span className="relative z-10 transition-all duration-300 group-hover:scale-110">
                            {page}
                          </span>
                        </button>
                      ))}
                    </div>

                    {/* Next Button - Enhanced Accessibility */}
                    <button
                      onClick={goToNext}
                      disabled={currentPage === totalPages}
                      className={`
                        group relative flex items-center justify-center w-12 h-12 rounded-xl
                        transition-all duration-300 transform font-medium
                        ${currentPage === totalPages 
                          ? 'bg-white/5 text-gray-500 cursor-not-allowed opacity-50' 
                          : 'bg-gradient-to-br from-cyan-500/70 via-purple-500/70 to-pink-500/70 text-white hover:from-cyan-400 hover:via-purple-400 hover:to-pink-400 hover:scale-110 hover:shadow-xl hover:shadow-purple-500/30 active:scale-95'}
                      `}
                      aria-label="Next page"
                    >
                      <ChevronRight className="w-5 h-5 transition-transform duration-300 group-hover:translate-x-0.5" />
                      {currentPage !== totalPages && (
                        <>
                          <div className="absolute inset-0 bg-gradient-to-br from-white/20 to-white/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                          <div className="absolute inset-0 bg-gradient-to-br from-cyan-500/20 via-purple-500/20 to-pink-500/20 rounded-xl blur opacity-0 group-hover:opacity-60 transition-opacity duration-300 -z-10 scale-150"></div>
                        </>
                      )}
                    </button>
                  </div>
                </div>

                {/* Premium Info Badge - Medical Theme */}
                <div className="bg-white/5 backdrop-blur-xl rounded-full px-5 py-2.5 border border-white/10 shadow-lg">
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
                    <span className="text-purple-200 text-sm font-medium tracking-wide">
                      Medical Mission {startIndex + 1}-{Math.min(endIndex, missions.length)} of {missions.length}
                    </span>
                    <div className="w-2 h-2 bg-gradient-to-r from-purple-400 to-pink-400 rounded-full animate-pulse"></div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}