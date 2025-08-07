import React, { useState, useEffect } from 'react';
import { 
  DollarSign, Calendar, Clock, TrendingUp, Award, Target, Activity, Star, 
  ChevronLeft, ChevronRight, Eye, FileText, CreditCard, Stethoscope, 
  Users, MapPin, CheckCircle
} from 'lucide-react';

// Mock data untuk jaspel jaga
const jaspelJagaData = [
  {
    id: 1,
    tanggal: '2024-01-15',
    shift: 'Pagi',
    jam: '07:00 - 14:00',
    lokasi: 'IGD',
    tarif: 750000,
    bonus: 150000,
    status: 'completed',
    keterangan: 'Jaga normal, tidak ada kasus emergency'
  },
  {
    id: 2,
    tanggal: '2024-01-18',
    shift: 'Malam',
    jam: '20:00 - 07:00',
    lokasi: 'ICU',
    tarif: 950000,
    bonus: 200000,
    status: 'completed',
    keterangan: 'Jaga malam dengan 2 kasus critical'
  },
  {
    id: 3,
    tanggal: '2024-01-22',
    shift: 'Siang',
    jam: '14:00 - 20:00',
    lokasi: 'Poli Umum',
    tarif: 650000,
    bonus: 100000,
    status: 'pending',
    keterangan: 'Jaga siang hari, antrian padat'
  },
  {
    id: 4,
    tanggal: '2024-01-25',
    shift: 'Pagi',
    jam: '07:00 - 14:00',
    lokasi: 'Ruang Bedah',
    tarif: 850000,
    bonus: 180000,
    status: 'scheduled',
    keterangan: 'Stanby untuk operasi elektif'
  },
  {
    id: 5,
    tanggal: '2024-01-28',
    shift: 'Malam',
    jam: '20:00 - 07:00',
    lokasi: 'IGD',
    tarif: 950000,
    bonus: 250000,
    status: 'scheduled',
    keterangan: 'Weekend shift, tarif premium'
  },
  {
    id: 6,
    tanggal: '2024-01-30',
    shift: 'Siang',
    jam: '14:00 - 20:00',
    lokasi: 'Poli Spesialis',
    tarif: 700000,
    bonus: 120000,
    status: 'scheduled',
    keterangan: 'Konsultasi spesialis kardio'
  }
];

// Mock data untuk jaspel tindakan
const jaspelTindakanData = [
  {
    id: 1,
    tanggal: '2024-01-16',
    tindakan: 'Operasi Appendektomi',
    jenis: 'Bedah Mayor',
    durasi: '2.5 jam',
    tarif: 2500000,
    complexity: 'high',
    tim: ['dr. Andi', 'dr. Budi', 'Sister Citra'],
    status: 'completed'
  },
  {
    id: 2,
    tanggal: '2024-01-19',
    tindakan: 'Konsultasi Kardiologi',
    jenis: 'Konsultasi',
    durasi: '45 menit',
    tarif: 350000,
    complexity: 'low',
    tim: ['dr. Andi'],
    status: 'completed'
  },
  {
    id: 3,
    tanggal: '2024-01-21',
    tindakan: 'Endoskopi',
    jenis: 'Diagnostik',
    durasi: '1 jam',
    tarif: 850000,
    complexity: 'medium',
    tim: ['dr. Andi', 'Nurse Dewi'],
    status: 'completed'
  },
  {
    id: 4,
    tanggal: '2024-01-24',
    tindakan: 'Emergency Surgery',
    jenis: 'Bedah Darurat',
    durasi: '4 jam',
    tarif: 4200000,
    complexity: 'critical',
    tim: ['dr. Andi', 'dr. Budi', 'dr. Celine', 'Sister Fiona'],
    status: 'completed'
  },
  {
    id: 5,
    tanggal: '2024-01-26',
    tindakan: 'Pemeriksaan Rutin',
    jenis: 'Check-up',
    durasi: '30 menit',
    tarif: 200000,
    complexity: 'low',
    tim: ['dr. Andi'],
    status: 'pending'
  },
  {
    id: 6,
    tanggal: '2024-01-29',
    tindakan: 'Operasi Hernia',
    jenis: 'Bedah Minor',
    durasi: '1.5 jam',
    tarif: 1800000,
    complexity: 'medium',
    tim: ['dr. Andi', 'Sister Gina'],
    status: 'scheduled'
  },
  {
    id: 7,
    tanggal: '2024-02-01',
    tindakan: 'Konsultasi Multidisiplin',
    jenis: 'Konsultasi Tim',
    durasi: '2 jam',
    tarif: 650000,
    complexity: 'high',
    tim: ['dr. Andi', 'dr. Hendra', 'dr. Ina'],
    status: 'scheduled'
  }
];

const JaspelComponent = () => {
  const [activeTab, setActiveTab] = useState('overview');
  const [currentPageJaga, setCurrentPageJaga] = useState(1);
  const [currentPageTindakan, setCurrentPageTindakan] = useState(1);
  const itemsPerPage = 3;
  const [isIPad, setIsIPad] = useState(false);

  useEffect(() => {
    // Detect iPad for layout adjustments
    const userAgent = navigator.userAgent.toLowerCase();
    setIsIPad(userAgent.includes('ipad'));
  }, []);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      completed: { bg: 'bg-green-500/20', text: 'text-green-400', border: 'border-green-500/30', label: 'Selesai' },
      pending: { bg: 'bg-yellow-500/20', text: 'text-yellow-400', border: 'border-yellow-500/30', label: 'Tertunda' },
      scheduled: { bg: 'bg-blue-500/20', text: 'text-blue-400', border: 'border-blue-500/30', label: 'Terjadwal' }
    };
    
    const config = statusConfig[status] || statusConfig.pending;
    
    return (
      <div className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border ${config.bg} ${config.text} ${config.border}`}>
        {config.label}
      </div>
    );
  };

  const getComplexityBadge = (complexity) => {
    const complexityConfig = {
      low: { bg: 'bg-emerald-500/20', text: 'text-emerald-400', border: 'border-emerald-500/30', label: 'Rendah', icon: '●' },
      medium: { bg: 'bg-yellow-500/20', text: 'text-yellow-400', border: 'border-yellow-500/30', label: 'Sedang', icon: '●●' },
      high: { bg: 'bg-orange-500/20', text: 'text-orange-400', border: 'border-orange-500/30', label: 'Tinggi', icon: '●●●' },
      critical: { bg: 'bg-red-500/20', text: 'text-red-400', border: 'border-red-500/30', label: 'Kritis', icon: '●●●●' }
    };
    
    const config = complexityConfig[complexity] || complexityConfig.low;
    
    return (
      <div className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border ${config.bg} ${config.text} ${config.border}`}>
        <span className="mr-2">{config.icon}</span>
        {config.label}
      </div>
    );
  };

  const totalJaspelJaga = jaspelJagaData.reduce((sum, item) => sum + item.tarif + item.bonus, 0);
  const totalJaspelTindakan = jaspelTindakanData.reduce((sum, item) => sum + item.tarif, 0);
  const grandTotal = totalJaspelJaga + totalJaspelTindakan;

  const completedJaga = jaspelJagaData.filter(item => item.status === 'completed').length;
  const completedTindakan = jaspelTindakanData.filter(item => item.status === 'completed').length;

  // Pagination logic
  const paginateJaga = () => {
    const startIndex = (currentPageJaga - 1) * itemsPerPage;
    return jaspelJagaData.slice(startIndex, startIndex + itemsPerPage);
  };

  const paginateTindakan = () => {
    const startIndex = (currentPageTindakan - 1) * itemsPerPage;
    return jaspelTindakanData.slice(startIndex, startIndex + itemsPerPage);
  };

  const totalPagesJaga = Math.ceil(jaspelJagaData.length / itemsPerPage);
  const totalPagesTindakan = Math.ceil(jaspelTindakanData.length / itemsPerPage);

  const PaginationControls = ({ currentPage, totalPages, onPageChange, type }) => (
    <div className="flex items-center justify-between mt-6 px-4">
      <div className="text-sm text-gray-400">
        Halaman {currentPage} dari {totalPages}
      </div>
      <div className="flex items-center space-x-2">
        <button 
          onClick={() => onPageChange(Math.max(1, currentPage - 1))}
          disabled={currentPage === 1}
          className={`p-2 rounded-lg border ${
            currentPage === 1 
              ? 'border-gray-600 text-gray-600 cursor-not-allowed' 
              : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'
          } transition-colors`}
        >
          <ChevronLeft className="w-4 h-4" />
        </button>
        <div className="flex items-center space-x-1">
          {[...Array(totalPages)].map((_, index) => (
            <button
              key={index}
              onClick={() => onPageChange(index + 1)}
              className={`w-8 h-8 rounded-lg border transition-colors ${
                currentPage === index + 1
                  ? 'border-emerald-500 bg-emerald-500/20 text-emerald-400'
                  : 'border-gray-600 text-gray-400 hover:border-emerald-500/50'
              }`}
            >
              {index + 1}
            </button>
          ))}
        </div>
        <button 
          onClick={() => onPageChange(Math.min(totalPages, currentPage + 1))}
          disabled={currentPage === totalPages}
          className={`p-2 rounded-lg border ${
            currentPage === totalPages 
              ? 'border-gray-600 text-gray-600 cursor-not-allowed' 
              : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'
          } transition-colors`}
        >
          <ChevronRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      {/* Responsive container with full-width assumption and self-managed padding */}
      <div className="w-full max-w-none px-4 md:px-6 lg:px-8 pt-8 pb-32">
        
        {/* Header Card */}
        <div className="relative mb-8">
          <div className="absolute inset-0 bg-gradient-to-br from-emerald-600/30 via-green-600/30 to-teal-600/30 rounded-3xl backdrop-blur-2xl"></div>
          <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
          <div className="relative p-8">
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center">
                <div className="w-20 h-20 bg-gradient-to-br from-emerald-400 via-green-500 to-teal-500 rounded-2xl flex items-center justify-center relative overflow-hidden shadow-2xl">
                  <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                  <DollarSign className="w-10 h-10 text-white relative z-10" />
                </div>
                <div className="ml-6">
                  <h1 className="text-4xl font-bold bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent mb-2">
                    Jasa Pelayanan (JASPEL)
                  </h1>
                  <p className="text-gray-300 text-lg">Dashboard Pendapatan Dokter</p>
                </div>
              </div>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-gradient-to-r from-emerald-500/10 to-green-500/10 rounded-2xl p-6 border border-emerald-400/20">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-emerald-300 text-sm mb-1">Total Pendapatan</p>
                    <p className="text-3xl font-bold text-white">{formatCurrency(grandTotal)}</p>
                  </div>
                  <CreditCard className="w-8 h-8 text-emerald-400" />
                </div>
                <p className="text-xs text-emerald-300 mt-2">Jaga + Tindakan</p>
              </div>

              <div className="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl p-6 border border-blue-400/20">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-blue-300 text-sm mb-1">Shift Selesai</p>
                    <p className="text-3xl font-bold text-white">{completedJaga}/{jaspelJagaData.length}</p>
                  </div>
                  <Clock className="w-8 h-8 text-blue-400" />
                </div>
                <p className="text-xs text-blue-300 mt-2">Jaga terlaksana</p>
              </div>

              <div className="bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-2xl p-6 border border-purple-400/20">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-purple-300 text-sm mb-1">Tindakan Selesai</p>
                    <p className="text-3xl font-bold text-white">{completedTindakan}/{jaspelTindakanData.length}</p>
                  </div>
                  <Stethoscope className="w-8 h-8 text-purple-400" />
                </div>
                <p className="text-xs text-purple-300 mt-2">Prosedur medis</p>
              </div>
            </div>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-2 mb-8 border border-white/10">
          <div className="grid grid-cols-3 gap-2">
            {[
              { id: 'overview', label: 'Ringkasan', icon: TrendingUp },
              { id: 'jaga', label: 'Jaga', icon: Clock },
              { id: 'tindakan', label: 'Tindakan', icon: Activity }
            ].map(({ id, label, icon: Icon }) => (
              <button
                key={id}
                onClick={() => setActiveTab(id)}
                className={`flex items-center justify-center px-6 py-3 rounded-xl font-medium transition-all duration-300 ${
                  activeTab === id
                    ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-lg'
                    : 'text-gray-300 hover:text-white hover:bg-white/5'
                }`}
              >
                <Icon className="w-5 h-5 mr-2" />
                {label}
              </button>
            ))}
          </div>
        </div>

        {/* Content based on active tab */}
        {activeTab === 'overview' && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Statistik Jaga */}
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
              <div className="flex items-center mb-6">
                <Clock className="w-6 h-6 text-blue-400 mr-3" />
                <h3 className="text-xl font-bold text-white">Statistik Jaga</h3>
              </div>
              <div className="space-y-4">
                <div className="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl p-4 border border-blue-400/20">
                  <div className="flex justify-between items-center">
                    <span className="text-blue-300">Total Pendapatan Jaga</span>
                    <span className="text-white font-bold">{formatCurrency(totalJaspelJaga)}</span>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-green-500/10 rounded-xl p-4 border border-green-400/20">
                    <p className="text-green-300 text-sm">Selesai</p>
                    <p className="text-2xl font-bold text-white">{completedJaga}</p>
                  </div>
                  <div className="bg-yellow-500/10 rounded-xl p-4 border border-yellow-400/20">
                    <p className="text-yellow-300 text-sm">Tertunda</p>
                    <p className="text-2xl font-bold text-white">
                      {jaspelJagaData.filter(item => item.status === 'pending').length}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Statistik Tindakan */}
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
              <div className="flex items-center mb-6">
                <Activity className="w-6 h-6 text-purple-400 mr-3" />
                <h3 className="text-xl font-bold text-white">Statistik Tindakan</h3>
              </div>
              <div className="space-y-4">
                <div className="bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-2xl p-4 border border-purple-400/20">
                  <div className="flex justify-between items-center">
                    <span className="text-purple-300">Total Pendapatan Tindakan</span>
                    <span className="text-white font-bold">{formatCurrency(totalJaspelTindakan)}</span>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-green-500/10 rounded-xl p-4 border border-green-400/20">
                    <p className="text-green-300 text-sm">Selesai</p>
                    <p className="text-2xl font-bold text-white">{completedTindakan}</p>
                  </div>
                  <div className="bg-orange-500/10 rounded-xl p-4 border border-orange-400/20">
                    <p className="text-orange-300 text-sm">Kompleks</p>
                    <p className="text-2xl font-bold text-white">
                      {jaspelTindakanData.filter(item => item.complexity === 'high' || item.complexity === 'critical').length}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'jaga' && (
          <div className="bg-white/5 backdrop-blur-2xl rounded-3xl border border-white/10 overflow-hidden">
            <div className="p-6 border-b border-white/10">
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <Clock className="w-6 h-6 text-blue-400 mr-3" />
                  <h3 className="text-xl font-bold text-white">Jadwal Jaga</h3>
                </div>
                <div className="text-sm text-gray-400">
                  {jaspelJagaData.length} total jaga
                </div>
              </div>
            </div>
            
            <div className="divide-y divide-white/10">
              {paginateJaga().map((item) => (
                <div key={item.id} className="p-6 hover:bg-white/5 transition-colors">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1">
                      <div className="flex items-center mb-2">
                        <Calendar className="w-4 h-4 text-emerald-400 mr-2" />
                        <span className="text-white font-semibold">{item.tanggal}</span>
                        <span className="mx-2 text-gray-400">•</span>
                        <span className="text-blue-300">{item.shift}</span>
                      </div>
                      <div className="flex items-center mb-2">
                        <MapPin className="w-4 h-4 text-purple-400 mr-2" />
                        <span className="text-gray-300">{item.lokasi}</span>
                        <span className="mx-2 text-gray-400">•</span>
                        <span className="text-gray-300">{item.jam}</span>
                      </div>
                      <p className="text-gray-400 text-sm">{item.keterangan}</p>
                    </div>
                    <div className="text-right ml-6">
                      <div className="mb-2">{getStatusBadge(item.status)}</div>
                      <div className="text-white font-bold text-lg">{formatCurrency(item.tarif + item.bonus)}</div>
                      <div className="text-xs text-gray-400">
                        Tarif: {formatCurrency(item.tarif)} + Bonus: {formatCurrency(item.bonus)}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            
            <PaginationControls 
              currentPage={currentPageJaga}
              totalPages={totalPagesJaga}
              onPageChange={setCurrentPageJaga}
              type="jaga"
            />
          </div>
        )}

        {activeTab === 'tindakan' && (
          <div className="bg-white/5 backdrop-blur-2xl rounded-3xl border border-white/10 overflow-hidden">
            <div className="p-6 border-b border-white/10">
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <Activity className="w-6 h-6 text-purple-400 mr-3" />
                  <h3 className="text-xl font-bold text-white">Tindakan Medis</h3>
                </div>
                <div className="text-sm text-gray-400">
                  {jaspelTindakanData.length} total tindakan
                </div>
              </div>
            </div>
            
            <div className="divide-y divide-white/10">
              {paginateTindakan().map((item) => (
                <div key={item.id} className="p-6 hover:bg-white/5 transition-colors">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1">
                      <div className="flex items-center mb-2">
                        <FileText className="w-4 h-4 text-emerald-400 mr-2" />
                        <span className="text-white font-semibold">{item.tindakan}</span>
                      </div>
                      <div className="flex items-center mb-2">
                        <Target className="w-4 h-4 text-blue-400 mr-2" />
                        <span className="text-blue-300">{item.jenis}</span>
                        <span className="mx-2 text-gray-400">•</span>
                        <span className="text-gray-300">{item.durasi}</span>
                        <span className="mx-2 text-gray-400">•</span>
                        <span className="text-gray-300">{item.tanggal}</span>
                      </div>
                      <div className="flex items-center mb-2">
                        <Users className="w-4 h-4 text-purple-400 mr-2" />
                        <span className="text-gray-300 text-sm">Tim: {item.tim.join(', ')}</span>
                      </div>
                      <div className="flex items-center space-x-3">
                        {getComplexityBadge(item.complexity)}
                        {getStatusBadge(item.status)}
                      </div>
                    </div>
                    <div className="text-right ml-6">
                      <div className="text-white font-bold text-xl">{formatCurrency(item.tarif)}</div>
                      <div className="text-xs text-gray-400 mt-1">Fee tindakan</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            
            <PaginationControls 
              currentPage={currentPageTindakan}
              totalPages={totalPagesTindakan}
              onPageChange={setCurrentPageTindakan}
              type="tindakan"
            />
          </div>
        )}

        {/* Footer Info */}
        <div className="mt-8 text-center">
          <p className="text-gray-500 text-sm">
            JASPEL • Jasa Pelayanan Medis • Dashboard Dokter
          </p>
        </div>

      </div>
    </div>
  );
};

export default JaspelComponent;