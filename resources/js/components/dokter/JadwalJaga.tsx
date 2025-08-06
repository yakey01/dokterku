import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Calendar, 
  Clock, 
  Users, 
  MapPin, 
  Trophy, 
  Star, 
  ChevronLeft, 
  ChevronRight,
  Filter,
  Search,
  Target,
  Zap,
  Shield,
  Heart,
  Brain,
  Eye,
  Activity
} from 'lucide-react';

interface JadwalJagaProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

// Interface untuk data jadwal
interface JadwalData {
  id: number;
  tanggal: string;
  waktu_mulai: string;
  waktu_selesai: string;
  lokasi: string;
  status: 'upcoming' | 'completed' | 'in-progress';
  jenis_shift: string;
  pasien_count?: number;
  tindakan_count?: number;
  revenue?: number;
}

// Mock data untuk demo
const mockJadwalData: JadwalData[] = [
  {
    id: 1,
    tanggal: '2024-08-05',
    waktu_mulai: '08:00',
    waktu_selesai: '16:00',
    lokasi: 'Klinik Utama',
    status: 'upcoming',
    jenis_shift: 'Shift Pagi',
    pasien_count: 15,
    tindakan_count: 8,
    revenue: 1200000
  },
  {
    id: 2,
    tanggal: '2024-08-04',
    waktu_mulai: '14:00',
    waktu_selesai: '22:00',
    lokasi: 'Klinik Cabang',
    status: 'completed',
    jenis_shift: 'Shift Sore',
    pasien_count: 12,
    tindakan_count: 6,
    revenue: 950000
  },
  {
    id: 3,
    tanggal: '2024-08-06',
    waktu_mulai: '22:00',
    waktu_selesai: '06:00',
    lokasi: 'Klinik 24 Jam',
    status: 'upcoming',
    jenis_shift: 'Shift Malam',
    pasien_count: 8,
    tindakan_count: 4,
    revenue: 800000
  },
  {
    id: 4,
    tanggal: '2024-08-03',
    waktu_mulai: '08:00',
    waktu_selesai: '16:00',
    lokasi: 'Klinik Utama',
    status: 'completed',
    jenis_shift: 'Shift Pagi',
    pasien_count: 18,
    tindakan_count: 10,
    revenue: 1500000
  },
  {
    id: 5,
    tanggal: '2024-08-07',
    waktu_mulai: '14:00',
    waktu_selesai: '22:00',
    lokasi: 'Klinik Cabang',
    status: 'upcoming',
    jenis_shift: 'Shift Sore',
    pasien_count: 14,
    tindakan_count: 7,
    revenue: 1100000
  },
  {
    id: 6,
    tanggal: '2024-08-02',
    waktu_mulai: '22:00',
    waktu_selesai: '06:00',
    lokasi: 'Klinik 24 Jam',
    status: 'completed',
    jenis_shift: 'Shift Malam',
    pasien_count: 6,
    tindakan_count: 3,
    revenue: 600000
  },
  {
    id: 7,
    tanggal: '2024-08-08',
    waktu_mulai: '08:00',
    waktu_selesai: '16:00',
    lokasi: 'Klinik Utama',
    status: 'upcoming',
    jenis_shift: 'Shift Pagi',
    pasien_count: 16,
    tindakan_count: 9,
    revenue: 1350000
  },
  {
    id: 8,
    tanggal: '2024-08-01',
    waktu_mulai: '14:00',
    waktu_selesai: '22:00',
    lokasi: 'Klinik Cabang',
    status: 'completed',
    jenis_shift: 'Shift Sore',
    pasien_count: 11,
    tindakan_count: 5,
    revenue: 875000
  }
];

export function JadwalJaga({ userData, onNavigate }: JadwalJagaProps) {
  const [currentPage, setCurrentPage] = useState(1);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<'all' | 'upcoming' | 'completed' | 'in-progress'>('all');
  const [selectedMission, setSelectedMission] = useState<JadwalData | null>(null);
  const [isDesktop, setIsDesktop] = useState(false);

  const itemsPerPage = 6;

  // Check if we're on desktop/iPad
  useEffect(() => {
    const checkIsDesktop = () => {
      setIsDesktop(window.innerWidth >= 768);
    };
    
    checkIsDesktop();
    window.addEventListener('resize', checkIsDesktop);
    
    return () => window.removeEventListener('resize', checkIsDesktop);
  }, []);

  // Filter dan search jadwal
  const filteredJadwal = mockJadwalData.filter(jadwal => {
    const matchesSearch = jadwal.lokasi.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         jadwal.jenis_shift.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesFilter = filterStatus === 'all' || jadwal.status === filterStatus;
    return matchesSearch && matchesFilter;
  });

  // Pagination
  const totalPages = Math.ceil(filteredJadwal.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentJadwal = filteredJadwal.slice(startIndex, startIndex + itemsPerPage);

  // Stats calculation
  const totalShifts = mockJadwalData.length;
  const completedShifts = mockJadwalData.filter(j => j.status === 'completed').length;
  const upcomingShifts = mockJadwalData.filter(j => j.status === 'upcoming').length;
  const totalHours = mockJadwalData.reduce((acc, jadwal) => {
    const start = new Date(`2024-01-01 ${jadwal.waktu_mulai}`);
    const end = new Date(`2024-01-01 ${jadwal.waktu_selesai}`);
    let hours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
    if (hours < 0) hours += 24; // Handle overnight shifts
    return acc + hours;
  }, 0);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'from-green-500 to-emerald-600';
      case 'upcoming':
        return 'from-blue-500 to-cyan-600';
      case 'in-progress':
        return 'from-yellow-500 to-orange-600';
      default:
        return 'from-gray-500 to-gray-600';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <Trophy className="w-4 h-4" />;
      case 'upcoming':
        return <Target className="w-4 h-4" />;
      case 'in-progress':
        return <Zap className="w-4 h-4" />;
      default:
        return <Clock className="w-4 h-4" />;
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      <div className="w-full min-h-screen relative overflow-y-auto">
        <div className="pb-32 lg:pb-16 p-4">
      {/* Header Section */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="mb-6"
      >
        <div className="text-center mb-6">
          <h1 className="text-3xl font-bold text-white mb-2 flex items-center justify-center gap-2">
            <Target className="w-8 h-8 text-purple-400" />
            Medical Mission Central
          </h1>
          <p className="text-purple-200">Kelola jadwal dan misi medis Anda</p>
        </div>

        {/* Gaming Stats Dashboard */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <motion.div
            whileHover={{ scale: 1.05 }}
            className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-4 text-center"
          >
            <div className="flex items-center justify-center mb-2">
              <Calendar className="w-6 h-6 text-blue-200" />
            </div>
            <div className="text-2xl font-bold text-white">{totalShifts}</div>
            <div className="text-blue-200 text-sm">Total Shifts</div>
          </motion.div>

          <motion.div
            whileHover={{ scale: 1.05 }}
            className="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-4 text-center"
          >
            <div className="flex items-center justify-center mb-2">
              <Trophy className="w-6 h-6 text-green-200" />
            </div>
            <div className="text-2xl font-bold text-white">{completedShifts}</div>
            <div className="text-green-200 text-sm">Completed</div>
          </motion.div>

          <motion.div
            whileHover={{ scale: 1.05 }}
            className="bg-gradient-to-r from-yellow-600 to-yellow-700 rounded-xl p-4 text-center"
          >
            <div className="flex items-center justify-center mb-2">
              <Target className="w-6 h-6 text-yellow-200" />
            </div>
            <div className="text-2xl font-bold text-white">{upcomingShifts}</div>
            <div className="text-yellow-200 text-sm">Upcoming</div>
          </motion.div>

          <motion.div
            whileHover={{ scale: 1.05 }}
            className="bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-4 text-center"
          >
            <div className="flex items-center justify-center mb-2">
              <Clock className="w-6 h-6 text-purple-200" />
            </div>
            <div className="text-2xl font-bold text-white">{totalHours.toFixed(0)}</div>
            <div className="text-purple-200 text-sm">Total Hours</div>
          </motion.div>
        </div>

        {/* Search and Filter */}
        <div className="flex flex-col md:flex-row gap-4 mb-6">
          <div className="relative flex-1">
            <Search className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
            <input
              type="text"
              placeholder="Cari lokasi atau jenis shift..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-300 focus:border-purple-400 focus:outline-none transition-colors"
            />
          </div>
          
          <div className="relative">
            <Filter className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value as any)}
              className="pl-10 pr-8 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:border-purple-400 focus:outline-none appearance-none min-w-[150px]"
            >
              <option value="all">Semua Status</option>
              <option value="upcoming">Mendatang</option>
              <option value="completed">Selesai</option>
              <option value="in-progress">Berlangsung</option>
            </select>
          </div>
        </div>
      </motion.div>

      {/* Mission Cards Grid */}
      <div className={`grid gap-4 mb-6 ${isDesktop ? 'grid-cols-2 lg:grid-cols-3' : 'grid-cols-1'}`}>
        <AnimatePresence mode="wait">
          {currentJadwal.map((jadwal, index) => (
            <motion.div
              key={jadwal.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ delay: index * 0.1 }}
              whileHover={{ scale: 1.02 }}
              onClick={() => setSelectedMission(jadwal)}
              className="bg-gradient-to-r from-white/10 to-white/5 backdrop-blur-sm border border-white/20 rounded-xl p-6 cursor-pointer hover:border-purple-400 transition-all"
            >
              {/* Mission Status Badge */}
              <div className="flex items-center justify-between mb-4">
                <div className={`px-3 py-1 rounded-full bg-gradient-to-r ${getStatusColor(jadwal.status)} text-white text-sm font-medium flex items-center gap-2`}>
                  {getStatusIcon(jadwal.status)}
                  {jadwal.status === 'completed' ? 'Selesai' : 
                   jadwal.status === 'upcoming' ? 'Mendatang' : 'Berlangsung'}
                </div>
                <Star className="w-5 h-5 text-yellow-400" />
              </div>

              {/* Mission Title */}
              <h3 className="text-lg font-bold text-white mb-2">{jadwal.jenis_shift}</h3>
              <p className="text-purple-200 text-sm mb-4">{formatDate(jadwal.tanggal)}</p>

              {/* Mission Details */}
              <div className="space-y-2 mb-4">
                <div className="flex items-center gap-2 text-gray-300">
                  <Clock className="w-4 h-4" />
                  <span className="text-sm">{jadwal.waktu_mulai} - {jadwal.waktu_selesai}</span>
                </div>
                <div className="flex items-center gap-2 text-gray-300">
                  <MapPin className="w-4 h-4" />
                  <span className="text-sm">{jadwal.lokasi}</span>
                </div>
                {jadwal.pasien_count && (
                  <div className="flex items-center gap-2 text-gray-300">
                    <Users className="w-4 h-4" />
                    <span className="text-sm">{jadwal.pasien_count} Pasien</span>
                  </div>
                )}
              </div>

              {/* Mission Stats */}
              {jadwal.status === 'completed' && (
                <div className="border-t border-white/10 pt-4">
                  <div className="grid grid-cols-2 gap-4 text-center">
                    <div>
                      <div className="text-xl font-bold text-green-400">{jadwal.tindakan_count}</div>
                      <div className="text-xs text-gray-400">Tindakan</div>
                    </div>
                    <div>
                      <div className="text-xl font-bold text-blue-400">{formatCurrency(jadwal.revenue || 0)}</div>
                      <div className="text-xs text-gray-400">Revenue</div>
                    </div>
                  </div>
                </div>
              )}

              {/* Gaming Elements */}
              <div className="flex items-center justify-between mt-4 pt-4 border-t border-white/10">
                <div className="flex items-center gap-1">
                  <Heart className="w-4 h-4 text-red-400" />
                  <div className="text-xs text-gray-400">+{Math.floor(Math.random() * 20 + 10)} HP</div>
                </div>
                <div className="flex items-center gap-1">
                  <Brain className="w-4 h-4 text-blue-400" />
                  <div className="text-xs text-gray-400">+{Math.floor(Math.random() * 15 + 5)} XP</div>
                </div>
                <div className="flex items-center gap-1">
                  <Zap className="w-4 h-4 text-yellow-400" />
                  <div className="text-xs text-gray-400">Level {Math.floor(Math.random() * 5 + 1)}</div>
                </div>
              </div>
            </motion.div>
          ))}
        </AnimatePresence>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="flex items-center justify-center gap-2 mb-16"
        >
          <button
            onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
            disabled={currentPage === 1}
            className="p-2 bg-white/10 border border-white/20 rounded-lg text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-white/20 transition-colors"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>
          
          <div className="flex items-center gap-1">
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                onClick={() => setCurrentPage(page)}
                className={`px-3 py-1 rounded-lg text-sm font-medium transition-colors ${
                  page === currentPage
                    ? 'bg-purple-600 text-white'
                    : 'bg-white/10 text-gray-300 hover:bg-white/20'
                }`}
              >
                {page}
              </button>
            ))}
          </div>

          <button
            onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
            disabled={currentPage === totalPages}
            className="p-2 bg-white/10 border border-white/20 rounded-lg text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-white/20 transition-colors"
          >
            <ChevronRight className="w-5 h-5" />
          </button>
        </motion.div>
      )}

      {/* Mission Detail Modal */}
      <AnimatePresence>
        {selectedMission && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onClick={() => setSelectedMission(null)}
          >
            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              onClick={(e) => e.stopPropagation()}
              className="bg-gradient-to-br from-slate-800 to-purple-900 rounded-xl p-6 max-w-md w-full max-h-[80vh] overflow-y-auto"
            >
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-xl font-bold text-white">Detail Misi</h3>
                <button
                  onClick={() => setSelectedMission(null)}
                  className="text-gray-400 hover:text-white transition-colors"
                >
                  ✕
                </button>
              </div>

              <div className="space-y-4">
                <div className={`px-4 py-2 rounded-lg bg-gradient-to-r ${getStatusColor(selectedMission.status)} text-white text-center font-medium`}>
                  {selectedMission.jenis_shift}
                </div>

                <div className="space-y-3 text-gray-300">
                  <div className="flex items-center gap-3">
                    <Calendar className="w-5 h-5 text-purple-400" />
                    <span>{formatDate(selectedMission.tanggal)}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <Clock className="w-5 h-5 text-purple-400" />
                    <span>{selectedMission.waktu_mulai} - {selectedMission.waktu_selesai}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <MapPin className="w-5 h-5 text-purple-400" />
                    <span>{selectedMission.lokasi}</span>
                  </div>
                  {selectedMission.pasien_count && (
                    <div className="flex items-center gap-3">
                      <Users className="w-5 h-5 text-purple-400" />
                      <span>{selectedMission.pasien_count} Pasien</span>
                    </div>
                  )}
                </div>

                {selectedMission.status === 'completed' && (
                  <div className="border-t border-white/20 pt-4">
                    <h4 className="text-lg font-semibold text-white mb-3">Mission Results</h4>
                    <div className="grid grid-cols-2 gap-4">
                      <div className="bg-green-500/20 rounded-lg p-3 text-center">
                        <div className="text-2xl font-bold text-green-400">{selectedMission.tindakan_count}</div>
                        <div className="text-sm text-green-300">Tindakan</div>
                      </div>
                      <div className="bg-blue-500/20 rounded-lg p-3 text-center">
                        <div className="text-lg font-bold text-blue-400">{formatCurrency(selectedMission.revenue || 0)}</div>
                        <div className="text-sm text-blue-300">Revenue</div>
                      </div>
                    </div>

                    <div className="mt-4 space-y-2">
                      <div className="flex items-center justify-between bg-white/5 rounded-lg p-2">
                        <div className="flex items-center gap-2">
                          <Heart className="w-4 h-4 text-red-400" />
                          <span className="text-sm text-gray-300">Health Points</span>
                        </div>
                        <span className="text-red-400 font-medium">+{Math.floor(Math.random() * 20 + 10)}</span>
                      </div>
                      <div className="flex items-center justify-between bg-white/5 rounded-lg p-2">
                        <div className="flex items-center gap-2">
                          <Brain className="w-4 h-4 text-blue-400" />
                          <span className="text-sm text-gray-300">Experience</span>
                        </div>
                        <span className="text-blue-400 font-medium">+{Math.floor(Math.random() * 15 + 5)}</span>
                      </div>
                      <div className="flex items-center justify-between bg-white/5 rounded-lg p-2">
                        <div className="flex items-center gap-2">
                          <Star className="w-4 h-4 text-yellow-400" />
                          <span className="text-sm text-gray-300">Rating</span>
                        </div>
                        <span className="text-yellow-400 font-medium">★★★★★</span>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
        </div>
        {/* End of main content container */}
        
        {/* Medical RPG Bottom Navigation */}
      </div>
    </div>
  );
}