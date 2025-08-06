import React, { useState, useEffect } from 'react';
import { 
  DollarSign, 
  Calendar, 
  Clock, 
  TrendingUp, 
  Award, 
  Target, 
  Activity, 
  Star, 
  ChevronLeft, 
  ChevronRight,
  Eye,
  FileText,
  CreditCard,
  Stethoscope,
  Users,
  MapPin,
  CheckCircle
} from 'lucide-react';

interface JaspelComponentProps {
  userData?: {
    name: string;
    email: string;
    greeting?: string;
    role?: string;
    initials?: string;
  };
}

const JaspelComponent: React.FC<JaspelComponentProps> = ({ userData }) => {
  const [activeTab, setActiveTab] = useState('overview');
  const [currentJagaPage, setCurrentJagaPage] = useState(1);
  const [currentTindakanPage, setCurrentTindakanPage] = useState(1);
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState('portrait');

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

  // Data Jaspel Jaga
  const jaspelJagaData = [
    {
      id: 1,
      tanggal: "1 Agustus 2025",
      hari: "Jumat",
      shift: "Malam",
      waktu: "21:00 - 07:00",
      lokasi: "IGD - Trauma Center",
      durasi: "10 jam",
      tarif: 150000,
      bonus: 50000,
      total: 200000,
      status: "Dibayar"
    },
    {
      id: 2,
      tanggal: "2 Agustus 2025",
      hari: "Sabtu",
      shift: "Pagi",
      waktu: "07:00 - 14:00",
      lokasi: "Ward 3A",
      durasi: "7 jam",
      tarif: 105000,
      bonus: 25000,
      total: 130000,
      status: "Pending"
    },
    {
      id: 3,
      tanggal: "5 Agustus 2025",
      hari: "Selasa",
      shift: "Sore",
      waktu: "14:00 - 21:00",
      lokasi: "Poliklinik Umum",
      durasi: "7 jam",
      tarif: 105000,
      bonus: 15000,
      total: 120000,
      status: "Dibayar"
    },
    {
      id: 4,
      tanggal: "7 Agustus 2025",
      hari: "Kamis",
      shift: "Malam",
      waktu: "20:00 - 08:00",
      lokasi: "ICU Level 2",
      durasi: "12 jam",
      tarif: 180000,
      bonus: 70000,
      total: 250000,
      status: "Pending"
    },
    {
      id: 5,
      tanggal: "10 Agustus 2025",
      hari: "Minggu",
      shift: "Pagi",
      waktu: "08:00 - 15:00",
      lokasi: "Emergency Room",
      durasi: "7 jam",
      tarif: 105000,
      bonus: 35000,
      total: 140000,
      status: "Dibayar"
    },
    {
      id: 6,
      tanggal: "12 Agustus 2025",
      hari: "Selasa",
      shift: "Sore",
      waktu: "15:00 - 22:00",
      lokasi: "OR Suite 2",
      durasi: "7 jam",
      tarif: 105000,
      bonus: 45000,
      total: 150000,
      status: "Pending"
    }
  ];

  // Data Jaspel Tindakan
  const jaspelTindakanData = [
    {
      id: 1,
      tanggal: "1 Agustus 2025",
      tindakan: "Operasi Jantung Bypass",
      kategori: "Bedah Kardio",
      pasien: "Tn. Ahmad Wijaya",
      durasi: "4 jam",
      kompleksitas: "Tinggi",
      tarif: 2500000,
      bonus: 500000,
      total: 3000000,
      status: "Dibayar"
    },
    {
      id: 2,
      tanggal: "3 Agustus 2025",
      tindakan: "Endoskopi Saluran Cerna",
      kategori: "Diagnostik",
      pasien: "Ny. Sari Indah",
      durasi: "1.5 jam",
      kompleksitas: "Sedang",
      tarif: 800000,
      bonus: 100000,
      total: 900000,
      status: "Dibayar"
    },
    {
      id: 3,
      tanggal: "5 Agustus 2025",
      tindakan: "Operasi Laparoskopi",
      kategori: "Bedah Umum",
      pasien: "Tn. Budi Santoso",
      durasi: "2.5 jam",
      kompleksitas: "Sedang",
      tarif: 1200000,
      bonus: 200000,
      total: 1400000,
      status: "Pending"
    },
    {
      id: 4,
      tanggal: "8 Agustus 2025",
      tindakan: "Kateterisasi Jantung",
      kategori: "Intervensional",
      pasien: "Ny. Maya Dewi",
      durasi: "3 jam",
      kompleksitas: "Tinggi",
      tarif: 1800000,
      bonus: 300000,
      total: 2100000,
      status: "Pending"
    },
    {
      id: 5,
      tanggal: "10 Agustus 2025",
      tindakan: "Bronkoskopi Diagnostik",
      kategori: "Paru",
      pasien: "Tn. Rahman Ali",
      durasi: "1 jam",
      kompleksitas: "Rendah",
      tarif: 600000,
      bonus: 75000,
      total: 675000,
      status: "Dibayar"
    },
    {
      id: 6,
      tanggal: "12 Agustus 2025",
      tindakan: "Operasi Tumor Otak",
      kategori: "Bedah Saraf",
      pasien: "Ny. Indira Putri",
      durasi: "6 jam",
      kompleksitas: "Sangat Tinggi",
      tarif: 4000000,
      bonus: 800000,
      total: 4800000,
      status: "Pending"
    },
    {
      id: 7,
      tanggal: "15 Agustus 2025",
      tindakan: "Colonoscopy",
      kategori: "Diagnostik",
      pasien: "Tn. Fahri Hamzah",
      durasi: "1.5 jam",
      kompleksitas: "Sedang",
      tarif: 750000,
      bonus: 100000,
      total: 850000,
      status: "Dibayar"
    }
  ];

  // Pagination settings
  const itemsPerPageJaga = 3;
  const itemsPerPageTindakan = 4;
  
  const totalJagaPages = Math.ceil(jaspelJagaData.length / itemsPerPageJaga);
  const totalTindakanPages = Math.ceil(jaspelTindakanData.length / itemsPerPageTindakan);
  
  const currentJagaData = jaspelJagaData.slice(
    (currentJagaPage - 1) * itemsPerPageJaga, 
    currentJagaPage * itemsPerPageJaga
  );
  
  const currentTindakanData = jaspelTindakanData.slice(
    (currentTindakanPage - 1) * itemsPerPageTindakan, 
    currentTindakanPage * itemsPerPageTindakan
  );

  // Calculate totals
  const totalJaspelBulan = jaspelJagaData.reduce((sum, item) => sum + item.total, 0) + 
                          jaspelTindakanData.reduce((sum, item) => sum + item.total, 0);
  const totalJaspelJaga = jaspelJagaData.reduce((sum, item) => sum + item.total, 0);
  const totalJaspelTindakan = jaspelTindakanData.reduce((sum, item) => sum + item.total, 0);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const getStatusBadge = (status) => {
    if (status === 'Dibayar') {
      return (
        <div className="flex items-center space-x-1 bg-green-500/20 px-2 py-1 rounded-full border border-green-400/50">
          <CheckCircle className="w-3 h-3 text-green-400" />
          <span className="text-green-400 text-xs font-medium">Dibayar</span>
        </div>
      );
    }
    return (
      <div className="flex items-center space-x-1 bg-yellow-500/20 px-2 py-1 rounded-full border border-yellow-400/50">
        <Clock className="w-3 h-3 text-yellow-400" />
        <span className="text-yellow-400 text-xs font-medium">Pending</span>
      </div>
    );
  };

  const getKompleksitasColor = (kompleksitas) => {
    switch (kompleksitas) {
      case 'Rendah': return 'text-green-400 bg-green-400/20 border-green-400/50';
      case 'Sedang': return 'text-blue-400 bg-blue-400/20 border-blue-400/50';
      case 'Tinggi': return 'text-orange-400 bg-orange-400/20 border-orange-400/50';
      case 'Sangat Tinggi': return 'text-red-400 bg-red-400/20 border-red-400/50';
      default: return 'text-gray-400 bg-gray-400/20 border-gray-400/50';
    }
  };

  const PaginationControls = ({ currentPage, totalPages, onPageChange, prefix }) => (
    <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
      <div className="flex items-center justify-center space-x-4">
        <button 
          onClick={() => onPageChange(Math.max(1, currentPage - 1))}
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
              onClick={() => onPageChange(page)}
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
          onClick={() => onPageChange(Math.min(totalPages, currentPage + 1))}
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
      
      <div className="text-center mt-3">
        <span className="text-gray-400 text-sm">
          Halaman {currentPage} dari {totalPages}
        </span>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-hidden">
        
        {/* Dynamic Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 bg-emerald-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '10%', width: '30vw', maxWidth: '400px', height: '30vw', maxHeight: '400px' }}></div>
          <div className="absolute top-60 bg-cyan-500 bg-opacity-5 rounded-full blur-2xl animate-bounce" style={{ right: '5%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }}></div>
          <div className="absolute bottom-80 bg-blue-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '15%', width: '28vw', maxWidth: '380px', height: '28vw', maxHeight: '380px' }}></div>
        </div>

        {/* Header */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pt-8 pb-6">
          <div className="text-center mb-8">
            <h1 className={`font-bold bg-gradient-to-r from-emerald-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent mb-2
              ${isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl sm:text-3xl'}
            `}>
              Jasa Pelayanan (JASPEL)
            </h1>
            <p className={`text-cyan-200 ${isIpad ? 'text-lg md:text-xl' : 'text-base'}`}>
              {userData?.name || 'Doctor'}
            </p>
          </div>

          {/* Tab Navigation */}
          <div className="flex justify-center mb-8">
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-2 border border-white/20">
              <div className="flex space-x-2">
                {[
                  { id: 'overview', label: 'Overview', icon: TrendingUp },
                  { id: 'jaga', label: 'Jaspel Jaga', icon: Clock },
                  { id: 'tindakan', label: 'Jaspel Tindakan', icon: Stethoscope }
                ].map((tab) => {
                  const Icon = tab.icon;
                  return (
                    <button
                      key={tab.id}
                      onClick={() => setActiveTab(tab.id)}
                      className={`
                        flex items-center space-x-2 px-4 py-2 rounded-xl transition-all duration-300
                        ${activeTab === tab.id 
                          ? 'bg-purple-600 text-white shadow-lg' 
                          : 'text-gray-300 hover:text-white hover:bg-white/10'
                        }
                      `}
                    >
                      <Icon className="w-4 h-4" />
                      <span className="text-sm font-medium">{tab.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-32">
          
          {/* Overview Tab */}
          {activeTab === 'overview' && (
            <div className="space-y-8">
              {/* Monthly Summary */}
              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 md:p-8 border border-white/20">
                <h2 className="text-2xl font-bold text-white mb-6 text-center">
                  Ringkasan Jaspel Bulan Ini
                </h2>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  {/* Total Jaspel */}
                  <div className="bg-gradient-to-br from-emerald-600/20 to-cyan-600/20 rounded-2xl p-6 border border-emerald-400/30">
                    <div className="flex items-center space-x-4 mb-4">
                      <div className="w-12 h-12 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-xl flex items-center justify-center">
                        <DollarSign className="w-6 h-6 text-white" />
                      </div>
                      <div>
                        <h3 className="text-emerald-400 font-semibold">Total Jaspel</h3>
                        <p className="text-gray-400 text-sm">Bulan Agustus</p>
                      </div>
                    </div>
                    <div className="text-3xl font-bold text-white mb-2">
                      {formatCurrency(totalJaspelBulan)}
                    </div>
                    <div className="text-emerald-300 text-sm">
                      +12.5% dari bulan lalu
                    </div>
                  </div>

                  {/* Jaspel Jaga */}
                  <div className="bg-gradient-to-br from-blue-600/20 to-purple-600/20 rounded-2xl p-6 border border-blue-400/30">
                    <div className="flex items-center space-x-4 mb-4">
                      <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center">
                        <Clock className="w-6 h-6 text-white" />
                      </div>
                      <div>
                        <h3 className="text-blue-400 font-semibold">Jaspel Jaga</h3>
                        <p className="text-gray-400 text-sm">{jaspelJagaData.length} shift</p>
                      </div>
                    </div>
                    <div className="text-3xl font-bold text-white mb-2">
                      {formatCurrency(totalJaspelJaga)}
                    </div>
                    <div className="text-blue-300 text-sm">
                      Rata-rata {formatCurrency(totalJaspelJaga / jaspelJagaData.length)}/shift
                    </div>
                  </div>

                  {/* Jaspel Tindakan */}
                  <div className="bg-gradient-to-br from-purple-600/20 to-pink-600/20 rounded-2xl p-6 border border-purple-400/30">
                    <div className="flex items-center space-x-4 mb-4">
                      <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                        <Stethoscope className="w-6 h-6 text-white" />
                      </div>
                      <div>
                        <h3 className="text-purple-400 font-semibold">Jaspel Tindakan</h3>
                        <p className="text-gray-400 text-sm">{jaspelTindakanData.length} tindakan</p>
                      </div>
                    </div>
                    <div className="text-3xl font-bold text-white mb-2">
                      {formatCurrency(totalJaspelTindakan)}
                    </div>
                    <div className="text-purple-300 text-sm">
                      Rata-rata {formatCurrency(totalJaspelTindakan / jaspelTindakanData.length)}/tindakan
                    </div>
                  </div>
                </div>
              </div>

              {/* Achievement Stats */}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Award className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">96.5%</div>
                  <div className="text-yellow-300 text-sm">Attendance Rate</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Target className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">125</div>
                  <div className="text-green-300 text-sm">Total Jam Kerja</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Activity className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">42</div>
                  <div className="text-blue-300 text-sm">Pasien Ditangani</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Star className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">4.9</div>
                  <div className="text-purple-300 text-sm">Rating Pasien</div>
                </div>
              </div>
            </div>
          )}

          {/* Jaspel Jaga Tab */}
          {activeTab === 'jaga' && (
            <div className="space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-6">
                Daftar Jaspel Jaga
              </h2>
              
              <div className="space-y-4">
                {currentJagaData.map((item) => (
                  <div key={item.id} className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-blue-400/50 transition-all duration-300">
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center space-x-4">
                        <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                          <Clock className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="text-lg font-bold text-white">{item.tanggal}</h3>
                          <p className="text-blue-300">{item.hari} • {item.shift}</p>
                        </div>
                      </div>
                      {getStatusBadge(item.status)}
                    </div>
                    
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <Clock className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Waktu</span>
                        </div>
                        <p className="text-white font-medium">{item.waktu}</p>
                        <p className="text-gray-400 text-xs">{item.durasi}</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <MapPin className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Lokasi</span>
                        </div>
                        <p className="text-white font-medium">{item.lokasi}</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <DollarSign className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Tarif</span>
                        </div>
                        <p className="text-white font-medium">{formatCurrency(item.tarif)}</p>
                        <p className="text-green-400 text-xs">+{formatCurrency(item.bonus)} bonus</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <CreditCard className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Total</span>
                        </div>
                        <p className="text-2xl font-bold text-emerald-400">{formatCurrency(item.total)}</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              
              <PaginationControls 
                currentPage={currentJagaPage}
                totalPages={totalJagaPages}
                onPageChange={setCurrentJagaPage}
                prefix="jaga"
              />
            </div>
          )}

          {/* Jaspel Tindakan Tab */}
          {activeTab === 'tindakan' && (
            <div className="space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-6">
                Daftar Jaspel Tindakan
              </h2>
              
              <div className="space-y-4">
                {currentTindakanData.map((item) => (
                  <div key={item.id} className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-purple-400/50 transition-all duration-300">
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center space-x-4">
                        <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                          <Stethoscope className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="text-lg font-bold text-white">{item.tindakan}</h3>
                          <p className="text-purple-300">{item.kategori}</p>
                        </div>
                      </div>
                      {getStatusBadge(item.status)}
                    </div>
                    
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <Calendar className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Tanggal</span>
                        </div>
                        <p className="text-white font-medium">{item.tanggal}</p>
                        <p className="text-gray-400 text-xs">{item.durasi}</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <Users className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Pasien</span>
                        </div>
                        <p className="text-white font-medium">{item.pasien}</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <Target className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Kompleksitas</span>
                        </div>
                        <div className={`px-2 py-1 rounded-full text-xs font-medium border ${getKompleksitasColor(item.kompleksitas)}`}>
                          {item.kompleksitas}
                        </div>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <DollarSign className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Tarif</span>
                        </div>
                        <p className="text-white font-medium">{formatCurrency(item.tarif)}</p>
                        <p className="text-green-400 text-xs">+{formatCurrency(item.bonus)} bonus</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <CreditCard className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Total</span>
                        </div>
                        <p className="text-2xl font-bold text-emerald-400">{formatCurrency(item.total)}</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              
              <PaginationControls 
                currentPage={currentTindakanPage}
                totalPages={totalTindakanPages}
                onPageChange={setCurrentTindakanPage}
                prefix="tindakan"
              />
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default JaspelComponent;