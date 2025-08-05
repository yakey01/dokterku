/** @jsxRuntime automatic */
import { useState, useEffect } from 'react';
import { Calendar, Clock, DollarSign, User, Home, Crown, Shield, Star, Brain, MapPin, Sword, Target, Award, Flame, ChevronLeft, ChevronRight, Eye, Zap } from 'lucide-react';

export function JadwalJagaTraditional() {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [selectedMonth, setSelectedMonth] = useState(new Date());
  const [jadwalData, setJadwalData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('missions');
  const [currentPage, setCurrentPage] = useState(1);
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState<'portrait' | 'landscape'>('portrait');

  const checkDevice = () => {
    const width = window.innerWidth;
    const height = window.innerHeight;
    setIsIpad(width >= 768);
    setOrientation(width > height ? 'landscape' : 'portrait');
  };

  useEffect(() => {
    checkDevice();
    window.addEventListener('resize', checkDevice);
    window.addEventListener('orientationchange', checkDevice);
    
    return () => {
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
  }, []);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    fetchJadwalData();
  }, [selectedMonth]);

  const fetchJadwalData = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/v2/dashboards/dokter/jadwal-jaga', {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          setJadwalData(data.data);
        }
      }
    } catch (error) {
      console.error('Error fetching jadwal data:', error);
    } finally {
      setLoading(false);
    }
  };

  // Transform schedule data into mission format
  const transformToMissionData = (scheduleData: any[]) => {
    return scheduleData.map((jadwal) => ({
      id: jadwal.id,
      missionName: `Shift ${jadwal.shift}`,
      date: new Date(jadwal.tanggal).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
      }),
      day: jadwal.hari,
      shift: jadwal.shift,
      time: `${jadwal.jam_masuk} - ${jadwal.jam_keluar}`,
      location: `${jadwal.unit_kerja} - ${jadwal.ruangan}`,
      supervisor: jadwal.supervisor,
      contact: jadwal.kontak_supervisor,
      difficulty: getDifficultyFromShift(jadwal.shift, jadwal.unit_kerja),
      xpReward: calculateXpReward(jadwal.shift, jadwal.unit_kerja),
      status: jadwal.status,
      missionType: getMissionType(jadwal.unit_kerja),
      urgency: getUrgency(jadwal.unit_kerja, jadwal.catatan),
      notes: jadwal.catatan
    }));
  };

  const getDifficultyFromShift = (shift: string, unit: string) => {
    if (unit === 'IGD' && shift === 'Malam') return 'Legendary';
    if (unit === 'IGD') return 'Elite';
    if (shift === 'Malam') return 'Elite';
    if (shift === 'Sore') return 'Hard';
    return 'Normal';
  };

  const calculateXpReward = (shift: string, unit: string) => {
    let baseXp = 200;
    if (shift === 'Malam') baseXp += 300;
    if (shift === 'Sore') baseXp += 150;
    if (unit === 'IGD') baseXp += 250;
    if (unit === 'Rawat Inap') baseXp += 100;
    return baseXp;
  };

  const getMissionType = (unit: string) => {
    if (unit === 'IGD') return 'emergency';
    if (unit === 'Rawat Inap') return 'ward';
    if (unit === 'Poliklinik') return 'clinic';
    return 'clinic';
  };

  const getUrgency = (unit: string, notes: string) => {
    if (unit === 'IGD') return 'critical';
    if (notes && notes !== '-' && notes.includes('siaga')) return 'high';
    return 'low';
  };

  // Mock data for demonstration
  const mockJadwalData = [
    {
      id: 1,
      tanggal: '2025-08-01',
      hari: 'Jumat',
      shift: 'Pagi',
      jam_masuk: '07:00',
      jam_keluar: '14:00',
      unit_kerja: 'IGD',
      ruangan: 'Ruang Emergency A',
      supervisor: 'Dr. Ahmad Rizki, Sp.Em',
      kontak_supervisor: '+62 812-3456-7890',
      status: 'scheduled',
      catatan: 'Libur nasional - siaga tinggi'
    },
    {
      id: 2,
      tanggal: '2025-08-02',
      hari: 'Sabtu',
      shift: 'Sore',
      jam_masuk: '14:00',
      jam_keluar: '21:00',
      unit_kerja: 'Poliklinik',
      ruangan: 'Ruang Konsultasi 1',
      supervisor: 'Dr. Siti Nurhaliza, Sp.PD',
      kontak_supervisor: '+62 813-9876-5432',
      status: 'scheduled',
      catatan: '-'
    },
    {
      id: 3,
      tanggal: '2025-08-04',
      hari: 'Senin',
      shift: 'Malam',
      jam_masuk: '21:00',
      jam_keluar: '07:00',
      unit_kerja: 'IGD',
      ruangan: 'Ruang Observasi',
      supervisor: 'Dr. Budi Santoso, Sp.An',
      kontak_supervisor: '+62 811-2233-4455',
      status: 'scheduled',
      catatan: 'Koordinasi dengan tim anastesi'
    },
    {
      id: 4,
      tanggal: '2025-08-05',
      hari: 'Selasa',
      shift: 'Pagi',
      jam_masuk: '07:00',
      jam_keluar: '14:00',
      unit_kerja: 'Rawat Inap',
      ruangan: 'Ruang Perawatan Lt.2',
      supervisor: 'Dr. Maya Indah, Sp.JP',
      kontak_supervisor: '+62 812-5566-7788',
      status: 'scheduled',
      catatan: 'Visite rutin pasien'
    }
  ];

  const dataToDisplay = jadwalData.length > 0 ? jadwalData : mockJadwalData;
  const missionData = transformToMissionData(dataToDisplay);

  const itemsPerPage = 3;
  const totalPages = Math.ceil(missionData.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentData = missionData.slice(startIndex, startIndex + itemsPerPage);

  const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
      case 'Normal': return 'from-green-400 to-emerald-500';
      case 'Hard': return 'from-orange-400 to-red-500';
      case 'Elite': return 'from-purple-400 to-pink-500';
      case 'Legendary': return 'from-yellow-400 to-amber-500';
      default: return 'from-gray-400 to-gray-500';
    }
  };

  const getMissionIcon = (type: string) => {
    switch (type) {
      case 'emergency': return Sword;
      case 'clinic': return Shield;
      case 'ward': return Crown;
      case 'icu': return Star;
      default: return Target;
    }
  };

  const navItems = [
    { id: 'home', icon: Crown, label: 'Home' },
    { id: 'missions', icon: Calendar, label: 'Missions' },
    { id: 'guardian', icon: Shield, label: 'Guardian' },
    { id: 'rewards', icon: Star, label: 'Rewards' },
    { id: 'profile', icon: Brain, label: 'Profile' }
  ];

  const totalActiveXp = missionData.reduce((sum, mission) => sum + mission.xpReward, 0);

  return (
    <div className="w-full bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white min-h-screen relative">
      <div className={`${isIpad ? 'max-w-none' : 'max-w-sm'} mx-auto min-h-screen relative overflow-hidden`}>
        
        {/* Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
          {isIpad && (
            <>
              <div className="absolute top-40 right-20 w-28 h-28 bg-cyan-500/5 rounded-full blur-2xl animate-pulse"></div>
              <div className="absolute bottom-60 right-8 w-32 h-32 bg-yellow-500/5 rounded-full blur-3xl animate-bounce"></div>
            </>
          )}
        </div>

        {/* Status Bar */}
        <div className="flex justify-between items-center px-6 pt-3 pb-2 text-white text-sm font-semibold relative z-10">
          <span>{currentTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
          <div className="flex items-center space-x-1">
            <div className="flex space-x-1">
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-gray-500 rounded-full"></div>
            </div>
            <div className="w-6 h-3 border border-white rounded-sm relative">
              <div className="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
            </div>
          </div>
        </div>

        {/* Mission Header */}
        <div className={`px-6 pt-8 pb-6 relative z-10 ${isIpad ? 'px-8 md:px-12' : ''}`}>
          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
            <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
            <div className={`relative ${isIpad ? 'p-8 md:p-10' : 'p-8'}`}>
              
              <div className="text-center mb-6">
                <div className={`${isIpad ? 'w-24 h-24 md:w-28 md:h-28' : 'w-20 h-20'} bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-2xl flex items-center justify-center mx-auto mb-4 relative overflow-hidden`}>
                  <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                  <Sword className={`${isIpad ? 'w-12 h-12 md:w-14 md:h-14' : 'w-10 h-10'} text-white relative z-10`} />
                </div>
                <h1 className={`${isIpad ? 'text-4xl md:text-5xl' : 'text-3xl'} font-bold bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mb-2`}>
                  Mission Central
                </h1>
                <p className={`text-purple-200 ${isIpad ? 'text-lg' : ''}`}>Medical Duty Assignments</p>
              </div>

              {/* Mission Stats */}
              <div className={`grid grid-cols-3 gap-4 ${isIpad ? 'gap-6 md:gap-8' : ''}`}>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Target className={`${isIpad ? 'w-6 h-6' : 'w-5 h-5'} text-cyan-400 mr-2`} />
                    <span className={`${isIpad ? 'text-3xl md:text-4xl' : 'text-2xl'} font-bold text-white`}>{missionData.length}</span>
                  </div>
                  <span className={`text-cyan-300 ${isIpad ? 'text-base' : 'text-sm'}`}>Active Missions</span>
                </div>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Flame className={`${isIpad ? 'w-6 h-6' : 'w-5 h-5'} text-orange-400 mr-2`} />
                    <span className={`${isIpad ? 'text-3xl md:text-4xl' : 'text-2xl'} font-bold text-white`}>{totalActiveXp.toLocaleString()}</span>
                  </div>
                  <span className={`text-orange-300 ${isIpad ? 'text-base' : 'text-sm'}`}>Total XP</span>
                </div>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Award className={`${isIpad ? 'w-6 h-6' : 'w-5 h-5'} text-yellow-400 mr-2`} />
                    <span className={`${isIpad ? 'text-3xl md:text-4xl' : 'text-2xl'} font-bold text-white`}>Elite</span>
                  </div>
                  <span className={`text-yellow-300 ${isIpad ? 'text-base' : 'text-sm'}`}>Rank Status</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Mission Cards */}
        <div className={`px-6 pb-40 relative z-10 ${isIpad ? 'px-8 md:px-12' : ''}`}>
          <h3 className={`${isIpad ? 'text-2xl md:text-3xl' : 'text-xl'} font-bold mb-6 text-center bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent`}>
            Upcoming Missions
          </h3>
          
          {loading ? (
            <div className="text-center py-12">
              <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-400"></div>
              <p className="mt-4 text-purple-200">Loading missions...</p>
            </div>
          ) : (
            <div className={`${isIpad ? (orientation === 'landscape' ? 'grid grid-cols-2 xl:grid-cols-3 gap-6' : 'grid grid-cols-2 gap-6') : 'space-y-6'}`}>
              {currentData.map((mission) => {
                const MissionIcon = getMissionIcon(mission.missionType);
                
                return (
                  <div key={mission.id} className="relative group">
                    <div className="absolute inset-0 bg-gradient-to-br from-slate-800/80 via-purple-800/60 to-slate-700/80 rounded-3xl backdrop-blur-xl"></div>
                    <div className="absolute inset-0 bg-white/5 rounded-3xl border border-purple-400/30 group-hover:border-purple-400/60 transition-all duration-500"></div>
                    <div className={`relative ${isIpad ? 'p-8' : 'p-6'}`}>
                      
                      {/* Mission Header */}
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center space-x-4">
                          <div className={`${isIpad ? 'w-20 h-20' : 'w-16 h-16'} bg-gradient-to-br ${getDifficultyColor(mission.difficulty)} rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300`}>
                            <MissionIcon className={`${isIpad ? 'w-10 h-10' : 'w-8 h-8'} text-white`} />
                          </div>
                          <div>
                            <h4 className={`${isIpad ? 'text-2xl' : 'text-xl'} font-bold text-white mb-1`}>{mission.missionName}</h4>
                            <p className={`text-purple-200 ${isIpad ? 'text-base' : ''}`}>{mission.date} • {mission.day}</p>
                            <div className="flex items-center space-x-2 mt-1">
                              <span className={`px-2 py-1 text-xs font-semibold rounded-lg bg-gradient-to-r ${getDifficultyColor(mission.difficulty)} text-white`}>
                                {mission.difficulty}
                              </span>
                              <span className="text-yellow-400 text-sm font-bold">+{mission.xpReward} XP</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      {/* Mission Details */}
                      <div className={`bg-slate-700/50 rounded-2xl ${isIpad ? 'p-6' : 'p-4'} mb-4 backdrop-blur-sm`}>
                        <div className={`grid grid-cols-1 ${isIpad ? 'gap-4' : 'gap-3'}`}>
                          <div>
                            <div className="flex items-center space-x-2 mb-2">
                              <Clock className={`${isIpad ? 'w-5 h-5' : 'w-4 h-4'} text-blue-400`} />
                              <span className={`text-white font-semibold ${isIpad ? 'text-base' : ''}`}>{mission.time}</span>
                            </div>
                            <div className={`text-gray-400 ${isIpad ? 'text-base' : 'text-sm'}`}>{mission.shift} Shift</div>
                          </div>
                          <div>
                            <div className="flex items-center space-x-2 mb-2">
                              <MapPin className={`${isIpad ? 'w-5 h-5' : 'w-4 h-4'} text-green-400`} />
                              <span className={`text-white font-semibold ${isIpad ? 'text-base' : ''}`}>{mission.location}</span>
                            </div>
                            <div className={`text-gray-400 ${isIpad ? 'text-base' : 'text-sm'}`}>Mission Zone</div>
                          </div>
                          {mission.supervisor && (
                            <div>
                              <div className="flex items-center space-x-2 mb-2">
                                <User className={`${isIpad ? 'w-5 h-5' : 'w-4 h-4'} text-purple-400`} />
                                <span className={`text-white font-semibold ${isIpad ? 'text-base' : ''}`}>{mission.supervisor}</span>
                              </div>
                              <div className={`text-gray-400 ${isIpad ? 'text-base' : 'text-sm'}`}>Mission Commander</div>
                            </div>
                          )}
                          {mission.notes && mission.notes !== '-' && (
                            <div className="mt-2 p-3 bg-amber-500/10 rounded-xl border border-amber-500/20">
                              <div className="flex items-start space-x-2">
                                <Eye className="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0" />
                                <span className={`text-amber-200 ${isIpad ? 'text-base' : 'text-sm'}`}>{mission.notes}</span>
                              </div>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* Mission Pagination */}
          {totalPages > 1 && (
            <div className="mt-8">
              <div className="bg-slate-800/80 backdrop-blur-xl rounded-2xl p-4 border border-purple-400/30">
                <div className="flex items-center justify-center space-x-4">
                  <button 
                    onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                    disabled={currentPage === 1}
                    className={`p-2 rounded-xl transition-colors ${
                      currentPage === 1 
                        ? 'text-gray-600 cursor-not-allowed' 
                        : 'text-purple-400 hover:text-white hover:bg-purple-700/50'
                    }`}
                  >
                    <ChevronLeft className="w-5 h-5" />
                  </button>
                  
                  <div className="flex items-center space-x-2">
                    {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                      <button
                        key={page}
                        onClick={() => setCurrentPage(page)}
                        className={`w-10 h-10 rounded-xl text-sm font-medium transition-colors ${
                          currentPage === page
                            ? 'bg-purple-600 text-white shadow-lg'
                            : 'text-purple-400 hover:text-white hover:bg-purple-700/50'
                        }`}
                      >
                        {page}
                      </button>
                    ))}
                  </div>
                  
                  <button 
                    onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                    disabled={currentPage === totalPages}
                    className={`p-2 rounded-xl transition-colors ${
                      currentPage === totalPages 
                        ? 'text-gray-600 cursor-not-allowed' 
                        : 'text-purple-400 hover:text-white hover:bg-purple-700/50'
                    }`}
                  >
                    <ChevronRight className="w-5 h-5" />
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Medical RPG Bottom Navigation */}
        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-slate-800/90 via-purple-800/80 to-slate-700/90 backdrop-blur-3xl px-6 py-4 border-t border-purple-400/20 relative z-10">
          <div className={`flex justify-between items-center ${isIpad ? 'px-4' : ''}`}>
            
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;
              
              return (
                <button
                  key={item.id}
                  onClick={() => setActiveTab(item.id)}
                  className="relative group transition-all duration-500 ease-out"
                >
                  {isActive ? (
                    <>
                      <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
                      <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
                      <div className="relative bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115">
                        <div className="flex flex-col items-center">
                          <Icon className={`${isIpad ? 'w-6 h-6' : 'w-5 h-5'} text-white mb-1`} />
                          <span className={`${isIpad ? 'text-sm' : 'text-xs'} text-white font-medium`}>{item.label}</span>
                        </div>
                      </div>
                    </>
                  ) : (
                    <div className="relative p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg">
                      <div className="flex flex-col items-center">
                        <Icon className={`${isIpad ? 'w-6 h-6' : 'w-5 h-5'} transition-colors duration-500 text-gray-400 group-hover:text-purple-300 mb-1`} />
                        <span className={`${isIpad ? 'text-sm' : 'text-xs'} transition-colors duration-500 text-gray-400 group-hover:text-purple-300 font-medium`}>{item.label}</span>
                      </div>
                    </div>
                  )}
                </button>
              );
            })}
          </div>
        </div>

        {/* Gaming Home Indicator */}
        <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-purple-400/60 to-transparent rounded-full shadow-lg shadow-purple-400/30"></div>
      </div>
    </div>
  );
}