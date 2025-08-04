import * as React from 'react';
import { useState, useEffect } from 'react';
import UnifiedAuth from '../../utils/UnifiedAuth';
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
  Crown,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';

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
  employee_name: string; // Enhanced: Include employee name from API
  shift_template?: {
    id: number;
    nama_shift: string;
    jam_masuk: string;
    jam_pulang: string;
  };
}

export function MedicalMissionPage() {
  const [selectedMission, setSelectedMission] = useState<Mission | null>(null);
  const [missions, setMissions] = useState<Mission[]>([]);
  const [loading, setLoading] = useState(false); // Set to false initially for gaming UI
  const [error, setError] = useState<string | null>(null);
  const [totalShifts, setTotalShifts] = useState(0);
  const [completedShifts, setCompletedShifts] = useState(0);
  const [upcomingShifts, setUpcomingShifts] = useState(0);
  const [totalHours, setTotalHours] = useState(0);
  const [authenticated, setAuthenticated] = useState(false);
  const [debugInfo, setDebugInfo] = useState<any>(null);
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState<'portrait' | 'landscape'>('portrait');
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(6); // Default 6 items per page

  useEffect(() => {
    checkAuthAndFetchData();
  }, []);

  useEffect(() => {
    const checkDevice = () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      setIsIpad(width >= 768);
      setOrientation(width > height ? 'landscape' : 'portrait');
      
      // Adjust items per page based on device
      if (width >= 1280) {
        setItemsPerPage(9); // 3x3 grid on large screens
      } else if (width >= 768) {
        setItemsPerPage(6); // 2x3 or 3x2 grid on tablets
      } else {
        setItemsPerPage(3); // 3 items on mobile
      }
    };
    
    checkDevice();
    window.addEventListener('resize', checkDevice);
    window.addEventListener('orientationchange', checkDevice);
    
    return () => {
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
  }, []);

  // Reset to page 1 when missions change
  useEffect(() => {
    setCurrentPage(1);
  }, [missions.length]);

  // Pagination calculations
  const totalPages = Math.ceil(missions.length / itemsPerPage);
  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentMissions = missions.slice(indexOfFirstItem, indexOfLastItem);

  // Handle page change
  const handlePageChange = (pageNumber: number) => {
    if (pageNumber >= 1 && pageNumber <= totalPages) {
      setCurrentPage(pageNumber);
      // Scroll to top of mission grid
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const checkAuthAndFetchData = async () => {
    try {
      // Check if user is authenticated
      const isAuth = await UnifiedAuth.isAuthenticated();
      if (isAuth) {
        setAuthenticated(true);
        await fetchJadwalJaga();
      } else {
        // Try to get token from URL or storage
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token') || UnifiedAuth.getToken();
        
        if (token) {
          setAuthenticated(true);
          await fetchJadwalJaga();
        } else {
          setError('Please login to access mission central');
          setLoading(false);
        }
      }
    } catch (err) {
      console.error('Auth check failed:', err);
      setError('Authentication check failed');
      setLoading(false);
    }
  };

  const fetchJadwalJaga = async () => {
    try {
      setLoading(true);
      
      // Debug: Show what auth token we're using
      const token = UnifiedAuth.getToken();
      console.log('Using token:', token ? token.substring(0, 20) + '...' : 'No token');
      
      // Use unified auth system
      console.log('🔍 Making request to /dokter/web-api/jadwal-jaga');
      console.log('Auth headers:', UnifiedAuth.getAuthHeaders());
      
      const response = await UnifiedAuth.makeRequest('/dokter/web-api/jadwal-jaga');
      console.log('Response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('API Error:', response.status, errorText);
        
        // More helpful error messages
        if (response.status === 401) {
          throw new Error('Authentication required. Please login first.');
        } else if (response.status === 404) {
          throw new Error('API endpoint not found. Check route configuration.');
        }
        throw new Error(`Failed to fetch: ${response.status} - ${errorText}`);
      }

      const result = await response.json();
      console.log('Jadwal API response:', result);
      
      // Deep debug the response structure
      if (result.data) {
        console.log('🔍 Debug - Response data structure:', {
          total_shifts_type: typeof result.data.total_shifts,
          total_shifts_value: result.data.total_shifts,
          completed_shifts_type: typeof result.data.completed_shifts,
          completed_shifts_value: result.data.completed_shifts,
          upcoming_shifts_type: typeof result.data.upcoming_shifts,
          upcoming_shifts_value: result.data.upcoming_shifts,
          total_hours_type: typeof result.data.total_hours,
          total_hours_value: result.data.total_hours
        });
        
        // Check if any value is an object with jadwal properties
        ['total_shifts', 'completed_shifts', 'upcoming_shifts', 'total_hours'].forEach(key => {
          const value = result.data[key];
          if (value && typeof value === 'object' && 'tanggal_jaga' in value) {
            console.error(`🚨 ERROR: ${key} is a jadwal object!`, value);
          }
        });
      }
      
      // Store debug info
      setDebugInfo({
        endpoint: '/dokter/web-api/jadwal-jaga',
        token: token ? 'Present' : 'Missing',
        response: result,
        timestamp: new Date().toISOString()
      });

      if (result.success && result.data) {
        const { missions: missionData, total_shifts, completed_shifts, upcoming_shifts, total_hours } = result.data;
        
        console.log('Mission data received:', {
          missions: missionData?.length || 0,
          total_shifts,
          completed_shifts,
          upcoming_shifts,
          total_hours
        });
        
        // Ensure all values are numbers, not objects
        const safeNumber = (value: any): number => {
          if (typeof value === 'number') return value;
          if (typeof value === 'string') return parseInt(value) || 0;
          if (value && typeof value === 'object' && 'value' in value) return safeNumber(value.value);
          if (value && typeof value === 'object' && 'count' in value) return safeNumber(value.count);
          return 0;
        };
        
        // Validate missions data
        const validMissions = (missionData || []).map((mission: any) => {
          // Log if mission has jadwal-like properties
          if (mission && typeof mission === 'object' && 'tanggal_jaga' in mission) {
            console.warn('⚠️ Found jadwal object in missions:', mission);
          }
          return mission;
        });
        
        // For testing pagination, add dummy missions if we have less than needed
        if (validMissions.length < 10) {
          console.log('🎮 Adding test missions for pagination demo');
          const testMissions = [];
          for (let i = 1; i <= 15; i++) {
            testMissions.push({
              id: 1000 + i,
              title: 'Dokter Jaga',
              subtitle: ['Pagi', 'Siang', 'Malam'][i % 3],
              date: `Aug ${String(i).padStart(2, '0')}`,
              full_date: `2024-08-${String(i).padStart(2, '0')}`,
              day_name: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][i % 7],
              time: ['07:00 - 14:00', '14:00 - 21:00', '21:00 - 07:00'][i % 3],
              location: 'Yaya Mulyana, M.Kes',
              type: 'regular' as const,
              difficulty: 'medium' as const,
              status: 'available' as const,
              status_jaga: ['Aktif', 'OnCall', 'Cuti', 'Izin'][i % 4],
              description: `Test mission ${i}`,
              requirements: [],
              peran: ['Dokter', 'Paramedis'][i % 2],
              employee_name: 'dr Test User',
              shift_template: {
                id: i,
                nama_shift: ['Pagi', 'Siang', 'Malam'][i % 3],
                jam_masuk: ['07:00', '14:00', '21:00'][i % 3],
                jam_pulang: ['14:00', '21:00', '07:00'][i % 3]
              }
            });
          }
          validMissions.push(...testMissions);
        }
        
        setMissions(validMissions);
        setTotalShifts(safeNumber(total_shifts));
        setCompletedShifts(safeNumber(completed_shifts));
        setUpcomingShifts(safeNumber(upcoming_shifts));
        setTotalHours(safeNumber(total_hours));
        
        // Debug pagination
        console.log('📄 Pagination Debug:', {
          totalMissions: validMissions.length,
          itemsPerPage,
          totalPages: Math.ceil(validMissions.length / itemsPerPage),
          shouldShowPagination: validMissions.length > itemsPerPage
        });
      } else {
        console.warn('API returned unsuccessful or no data:', result);
        setMissions([]);
      }
      
      setError(null);
    } catch (err) {
      console.error('Error fetching jadwal:', err);
      setError(`Gagal memuat jadwal jaga: ${err instanceof Error ? err.message : 'Unknown error'}`);
      setMissions([]);
    } finally {
      setLoading(false);
    }
  };

  // Helper function to get mission icon based on type and difficulty
  const getMissionIcon = (mission: Mission) => {
    if (mission.difficulty === 'legendary') return Shield;
    if (mission.type === 'urgent') return Activity;
    if (mission.type === 'special') return Zap;
    if (mission.type === 'training') return Target;
    if (mission.subtitle.toLowerCase().includes('malam')) return Shield;
    if (mission.subtitle.toLowerCase().includes('pagi')) return Heart;
    return Users;
  };

  // Enhanced helper functions for gaming-style appearance
  const getStatusIcon = (mission: Mission) => {
    switch (mission.status_jaga) {
      case 'Aktif': return CheckCircle;
      case 'OnCall': return Phone;
      case 'Cuti': return Coffee;
      case 'Izin': return UserCheck;
      default: return Clock;
    }
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

  const getMissionBorderGlow = (mission: Mission) => {
    switch (mission.status_jaga) {
      case 'Aktif': return 'border-green-400/50 shadow-green-500/30';
      case 'OnCall': return 'border-red-400/50 shadow-red-500/30';
      case 'Cuti': return 'border-purple-400/50 shadow-purple-500/30';
      case 'Izin': return 'border-blue-400/50 shadow-blue-500/30';
      default: return 'border-indigo-400/50 shadow-indigo-500/30';
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

  const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
      case 'easy': return { text: 'text-green-400', bg: 'bg-green-400/20', border: 'border-green-400/50' };
      case 'medium': return { text: 'text-blue-400', bg: 'bg-blue-400/20', border: 'border-blue-400/50' };
      case 'hard': return { text: 'text-orange-400', bg: 'bg-orange-400/20', border: 'border-orange-400/50' };
      case 'legendary': return { text: 'text-purple-400', bg: 'bg-purple-400/20', border: 'border-purple-400/50' };
      default: return { text: 'text-gray-400', bg: 'bg-gray-400/20', border: 'border-gray-400/50' };
    }
  };

  const getXpReward = (mission: Mission) => {
    const baseXp = {
      'easy': 250,
      'medium': 420,
      'hard': 750,
      'legendary': 1200
    };
    return baseXp[mission.difficulty] || 300;
  };

  const getStatusBadge = (mission: Mission) => {
    switch (mission.status_jaga) {
      case 'Aktif':
        return (
          <div className="flex items-center space-x-2 bg-green-500/30 backdrop-blur-sm px-3 py-1.5 rounded-full border border-green-400/50">
            <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            <span className="text-green-400 text-xs font-bold">SEDANG JAGA</span>
          </div>
        );
      case 'OnCall':
        return (
          <div className="flex items-center space-x-2 bg-red-500/30 backdrop-blur-sm px-3 py-1.5 rounded-full border border-red-400/50">
            <Phone className="w-3 h-3 text-red-400" />
            <span className="text-red-400 text-xs font-bold">ON CALL</span>
          </div>
        );
      case 'Cuti':
        return (
          <div className="flex items-center space-x-2 bg-purple-500/30 backdrop-blur-sm px-3 py-1.5 rounded-full border border-purple-400/50">
            <Coffee className="w-3 h-3 text-purple-400" />
            <span className="text-purple-400 text-xs font-bold">CUTI</span>
          </div>
        );
      case 'Izin':
        return (
          <div className="flex items-center space-x-2 bg-blue-500/30 backdrop-blur-sm px-3 py-1.5 rounded-full border border-blue-400/50">
            <UserCheck className="w-3 h-3 text-blue-400" />
            <span className="text-blue-400 text-xs font-bold">IZIN</span>
          </div>
        );
      default:
        return (
          <div className="flex items-center space-x-2 bg-gray-500/30 backdrop-blur-sm px-3 py-1.5 rounded-full border border-gray-400/50">
            <div className="w-2 h-2 bg-gray-400 rounded-full"></div>
            <span className="text-gray-400 text-xs font-bold">TERJADWAL</span>
          </div>
        );
    }
  };

  const getShiftIcon = (mission: Mission) => {
    const time = mission.shift_template?.jam_masuk || mission.time;
    if (time.includes('06:') || time.includes('07:') || time.includes('08:')) return '🌅';
    if (time.includes('14:') || time.includes('15:') || time.includes('16:')) return '🌆';
    if (time.includes('21:') || time.includes('22:') || time.includes('20:')) return '🌙';
    return '⏰';
  };


  return (
    <div className="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white min-h-full">
      <div className="w-full relative">
        
        {/* Dynamic Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 bg-blue-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '10%', width: '30vw', maxWidth: '400px', height: '30vw', maxHeight: '400px' }}></div>
          <div className="absolute top-60 bg-purple-500 bg-opacity-5 rounded-full blur-2xl animate-bounce" style={{ right: '5%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }}></div>
          <div className="absolute bottom-80 bg-pink-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '15%', width: '28vw', maxWidth: '380px', height: '28vw', maxHeight: '380px' }}></div>
        </div>

        {/* Header */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pt-8 pb-6">
          <div className="text-center mb-6">
            <h1 className={`font-bold bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mb-2
              ${isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl sm:text-3xl'}
            `}>
              Medical Mission Central
            </h1>
            <p className={`text-purple-200 ${isIpad ? 'text-lg md:text-xl' : 'text-base'}`}>
              Elite Doctor Duty Assignments
            </p>
            <div className="flex items-center justify-center mt-4">
              <button
                onClick={() => {
                  UnifiedAuth.setToken('1145|ayMC5CESKV6MBlwD4AoAhFyLBmToY5M0DVUoJnpV94c3cc66');
                  fetchJadwalJaga();
                }}
                className="text-xs bg-purple-600/20 hover:bg-purple-600/40 border border-purple-400/30 text-purple-300 px-4 py-2 rounded-xl transition-colors backdrop-blur-sm"
              >
                🔐 Test Auth
              </button>
            </div>
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

        {/* Error State */}
        {error && (
          <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 py-6">
            <div className="bg-red-900/20 border border-red-500/20 rounded-2xl p-6 backdrop-blur-xl">
              <div className="flex items-center">
                <AlertCircle className="w-6 h-6 text-red-400 mr-3" />
                <div>
                  <h3 className="text-red-400 font-semibold">Mission Database Error</h3>
                  <p className="text-gray-300 text-sm">{error}</p>
                </div>
              </div>
              <div className="mt-4 flex flex-wrap gap-2">
                <button
                  onClick={fetchJadwalJaga}
                  className="bg-red-600/80 hover:bg-red-600 text-white px-4 py-2 rounded-xl transition-colors backdrop-blur-sm"
                >
                  🔄 Retry Mission Sync
                </button>
                <button
                  onClick={() => {
                    UnifiedAuth.setToken('1145|ayMC5CESKV6MBlwD4AoAhFyLBmToY5M0DVUoJnpV94c3cc66');
                    fetchJadwalJaga();
                  }}
                  className="bg-purple-600/80 hover:bg-purple-600 text-white px-4 py-2 rounded-xl transition-colors backdrop-blur-sm"
                >
                  🎮 Use Debug Token
                </button>
              </div>
              
              {/* Debug Info */}
              {debugInfo && (
                <div className="mt-4 p-3 bg-gray-800/50 rounded-lg backdrop-blur-sm">
                  <p className="text-gray-400 text-xs font-mono">
                    <strong>Debug:</strong> {debugInfo.endpoint} | 
                    Token: {debugInfo.token} | 
                    Time: {new Date(debugInfo.timestamp).toLocaleTimeString()}
                  </p>
                  <details className="mt-2">
                    <summary className="text-gray-400 text-xs cursor-pointer">Response Data</summary>
                    <pre className="text-xs text-gray-500 mt-1 overflow-auto max-h-32">
                      {JSON.stringify(debugInfo.response, null, 2)}
                    </pre>
                  </details>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Mission Cards Grid */}
        {!loading && !error && (
          <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-8">
            <div className={`
              grid gap-6 md:gap-8
              ${isIpad && orientation === 'landscape' 
                ? 'lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3' 
                : 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3'
              }
            `}>
              {currentMissions.map((mission) => {
                const Icon = getMissionIcon(mission);
                const isLocked = mission.status === 'locked';
                const difficultyColors = getDifficultyColor(mission.difficulty);
                const gradient = getMissionGradient(mission);
                const borderGlow = getMissionBorderGlow(mission);
                const bgGlow = getMissionBgGlow(mission);
                const xpReward = getXpReward(mission);
                
                return (
                  <div
                    key={mission.id}
                    onClick={() => setSelectedMission(mission)}
                    className={`
                      relative group cursor-pointer transform transition-all duration-500
                      ${isLocked ? 'opacity-60' : 'hover:scale-[1.015] hover:-translate-y-1'}
                    `}
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
                              Dokter Jaga
                            </h3>
                            <p className={`text-gray-300 font-medium ${isIpad ? 'text-sm' : 'text-xs'}`}>
                              {mission.shift_template?.nama_shift || 'Pagi'}
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
                              {mission.shift_template ? 
                                `${mission.shift_template.jam_masuk} - ${mission.shift_template.jam_pulang}` : 
                                mission.time}
                            </div>
                            <div className={`text-cyan-300 font-medium ${isIpad ? 'text-xs' : 'text-xs'}`}>
                              👤 {mission.employee_name}
                            </div>
                          </div>
                        </div>
                      </div>

                      {/* Gaming-style Glow Effect */}
                      <div className={`
                        absolute inset-0 bg-gradient-to-br ${gradient} opacity-0 
                        group-hover:opacity-20 rounded-3xl transition-all duration-500 blur-xl
                      `}></div>
                    </div>
                  </div>
                );
              })}
            </div>
            
            {/* Empty State */}
            {missions.length === 0 && (
              <div className="text-center py-12">
                <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-8 border border-purple-400/20 mx-auto max-w-md">
                  <Calendar className="h-16 w-16 mx-auto mb-4 text-purple-400" />
                  <p className="text-purple-300 text-lg font-semibold">No Active Missions</p>
                  <p className="text-purple-200/70 text-sm mt-2">Your duty assignments will appear here</p>
                </div>
              </div>
            )}
            
            {/* Gaming-Style Pagination */}
            {console.log('🎯 Pagination Check:', { 
              missionsLength: missions.length, 
              itemsPerPage, 
              showPagination: missions.length > itemsPerPage,
              totalPages: Math.ceil(missions.length / itemsPerPage)
            })}
            {missions.length > itemsPerPage && (
              <div className="mt-8 mb-4">
                <div className="flex items-center justify-center space-x-2">
                  {/* Previous Button */}
                  <button
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                    className={`
                      relative group px-4 py-2 rounded-xl font-semibold text-sm
                      ${currentPage === 1 
                        ? 'bg-white/5 text-gray-500 cursor-not-allowed' 
                        : 'bg-white/10 text-purple-300 hover:bg-purple-600/30 hover:text-white'}
                      backdrop-blur-xl border transition-all duration-300
                      ${currentPage === 1 
                        ? 'border-gray-600/30' 
                        : 'border-purple-400/30 hover:border-purple-400/50 hover:shadow-lg hover:shadow-purple-500/20'}
                    `}
                  >
                    <ChevronLeft className="w-4 h-4 inline mr-1" />
                    <span className={isIpad ? '' : 'hidden sm:inline'}>Previous</span>
                  </button>

                  {/* Page Numbers */}
                  <div className="flex items-center space-x-1">
                    {[...Array(totalPages)].map((_, index) => {
                      const pageNum = index + 1;
                      const isActive = pageNum === currentPage;
                      
                      // Show only a subset of pages on mobile
                      if (!isIpad && totalPages > 5) {
                        if (pageNum > 1 && pageNum < totalPages && 
                            Math.abs(pageNum - currentPage) > 1) {
                          if (pageNum === 2 || pageNum === totalPages - 1) {
                            return (
                              <span key={pageNum} className="text-gray-400 px-1">
                                ...
                              </span>
                            );
                          }
                          return null;
                        }
                      }
                      
                      return (
                        <button
                          key={pageNum}
                          onClick={() => handlePageChange(pageNum)}
                          className={`
                            relative w-10 h-10 rounded-xl font-bold text-sm
                            transition-all duration-300 transform
                            ${isActive 
                              ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white scale-110 shadow-xl shadow-purple-500/30' 
                              : 'bg-white/10 text-purple-300 hover:bg-purple-600/30 hover:text-white hover:scale-105'}
                            backdrop-blur-xl border
                            ${isActive 
                              ? 'border-cyan-300/50' 
                              : 'border-purple-400/30 hover:border-purple-400/50'}
                          `}
                        >
                          {isActive && (
                            <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-xl blur-md"></div>
                          )}
                          <span className="relative z-10">{pageNum}</span>
                        </button>
                      );
                    })}
                  </div>

                  {/* Next Button */}
                  <button
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage === totalPages}
                    className={`
                      relative group px-4 py-2 rounded-xl font-semibold text-sm
                      ${currentPage === totalPages 
                        ? 'bg-white/5 text-gray-500 cursor-not-allowed' 
                        : 'bg-white/10 text-purple-300 hover:bg-purple-600/30 hover:text-white'}
                      backdrop-blur-xl border transition-all duration-300
                      ${currentPage === totalPages 
                        ? 'border-gray-600/30' 
                        : 'border-purple-400/30 hover:border-purple-400/50 hover:shadow-lg hover:shadow-purple-500/20'}
                    `}
                  >
                    <span className={isIpad ? '' : 'hidden sm:inline'}>Next</span>
                    <ChevronRight className="w-4 h-4 inline ml-1" />
                  </button>
                </div>

                {/* Page Info */}
                <div className="text-center mt-4">
                  <p className="text-purple-300/70 text-sm">
                    <span className="text-purple-400 font-semibold">{missions.length}</span> Total Missions • 
                    Page <span className="text-purple-400 font-semibold">{currentPage}</span> of <span className="text-purple-400 font-semibold">{totalPages}</span>
                  </p>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Gaming-Style Mission Detail Modal */}
        {selectedMission && (
          <div className="fixed inset-0 bg-black/90 backdrop-blur-lg z-50 flex items-center justify-center p-4">
            <div 
              className="bg-white/10 backdrop-blur-2xl rounded-3xl max-w-md w-full overflow-hidden border border-white/20"
              style={{ maxHeight: '85vh' }}
            >
              <div className={`bg-gradient-to-br ${getMissionGradient(selectedMission)} p-6 relative overflow-hidden`}>
                {/* Close Button */}
                <button
                  onClick={() => setSelectedMission(null)}
                  className="absolute top-4 right-4 p-2 bg-white/20 rounded-xl hover:bg-white/30 transition-colors backdrop-blur-sm"
                >
                  <X className="w-5 h-5 text-white" />
                </button>
                
                {/* Back Button */}
                <button
                  onClick={() => setSelectedMission(null)}
                  className="flex items-center space-x-2 p-2 bg-white/20 rounded-xl hover:bg-white/30 transition-colors backdrop-blur-sm mb-4"
                >
                  <ArrowLeft className="w-5 h-5 text-white" />
                  <span className="text-white font-medium text-sm">Kembali</span>
                </button>
                
                {/* Mission Header */}
                <div className="text-center">
                  <div className="bg-white/20 backdrop-blur-sm rounded-2xl p-3 mb-4 inline-block">
                    <span className="text-white font-bold text-lg">🎮 MISSION BRIEFING</span>
                  </div>
                  <h2 className="text-2xl font-bold text-white mb-2">Medical Duty Assignment</h2>
                  <p className="text-white/90 font-medium">{selectedMission.shift_template?.nama_shift || 'Shift'} - {selectedMission.peran}</p>
                </div>
              </div>
              
              <div className="p-6 space-y-4 overflow-y-auto">
                {/* Gaming-Style Info Cards */}
                <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10">
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <Calendar className="w-4 h-4 text-purple-400" />
                        <span className="text-gray-300 font-medium">Mission Date</span>
                      </div>
                      <span className="text-white font-bold">{selectedMission.date}</span>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <Clock className="w-4 h-4 text-cyan-400" />
                        <span className="text-gray-300 font-medium">Time Frame</span>
                      </div>
                      <span className="text-white font-bold">
                        {selectedMission.shift_template ? 
                          `${selectedMission.shift_template.jam_masuk} - ${selectedMission.shift_template.jam_pulang}` : 
                          selectedMission.time}
                      </span>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <MapPin className="w-4 h-4 text-green-400" />
                        <span className="text-gray-300 font-medium">Location</span>
                      </div>
                      <span className="text-white font-bold">{selectedMission.location}</span>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <User className="w-4 h-4 text-yellow-400" />
                        <span className="text-gray-300 font-medium">Assigned Doctor</span>
                      </div>
                      <span className="text-white font-bold">{selectedMission.employee_name}</span>
                    </div>
                  </div>
                </div>
                
                {/* Mission Status Badge */}
                <div className="flex justify-center">
                  {getStatusBadge(selectedMission)}
                </div>
                
                {/* Mission Description */}
                {selectedMission.description && (
                  <div className="bg-white/5 backdrop-blur-xl rounded-2xl p-4 border border-white/10">
                    <div className="flex items-center space-x-2 mb-3">
                      <FileText className="w-4 h-4 text-blue-400" />
                      <span className="text-gray-300 font-medium">Mission Details</span>
                    </div>
                    <p className="text-gray-200 text-sm leading-relaxed">{selectedMission.description}</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

      </div>
    </div>
  );
}