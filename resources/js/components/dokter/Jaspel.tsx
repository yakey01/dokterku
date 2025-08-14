import React, { useState, useEffect } from 'react';
import { 
  DollarSign, Calendar, Clock, TrendingUp, Award, Target, Activity, Star, 
  ChevronLeft, ChevronRight, Eye, FileText, CreditCard, Stethoscope, 
  Users, MapPin, CheckCircle, RefreshCw, AlertCircle
} from 'lucide-react';

interface JaspelItem {
  id: number;
  tanggal: string;
  jenis_jaspel: string;
  nominal: number;
  status_validasi: string;
  keterangan?: string;
  shift?: string;
  jam?: string;
  lokasi?: string;
  tindakan?: string;
  jenis?: string;
  durasi?: string;
  complexity?: string;
  tim?: string[];
  // ✅ ADDED: Correct fields from database
  jumlah?: number;
  status?: string;
  source?: string;
  tindakan_id?: number;
  shift_id?: number;
}

interface JaspelSummary {
  total: number;
  approved: number;
  pending: number;
  rejected: number;
  count: {
    total: number;
    approved: number;
    pending: number;
    rejected: number;
  };
}

const JaspelComponent = () => {
  const [activeTab, setActiveTab] = useState('overview');
  const [currentPageJaga, setCurrentPageJaga] = useState(1);
  const [currentPageTindakan, setCurrentPageTindakan] = useState(1);
  const itemsPerPage = 3;
  const [isIPad, setIsIPad] = useState(false);
  
  // State for dynamic data
  const [jaspelData, setJaspelData] = useState<JaspelItem[]>([]);
  const [summary, setSummary] = useState<JaspelSummary>({
    total: 0,
    approved: 0,
    pending: 0,
    rejected: 0,
    count: {
      total: 0,
      approved: 0,
      pending: 0,
      rejected: 0,
    }
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Separate jaga and tindakan data
  const [jaspelJagaData, setJaspelJagaData] = useState<JaspelItem[]>([]);
  const [jaspelTindakanData, setJaspelTindakanData] = useState<JaspelItem[]>([]);
  
  // State for Jumlah Pasien data integration
  const [jumlahPasienData, setJumlahPasienData] = useState<any[]>([]);
  const [loadingJumlahPasien, setLoadingJumlahPasien] = useState(false);

  useEffect(() => {
    // Detect iPad for layout adjustments
    const userAgent = navigator.userAgent.toLowerCase();
    setIsIPad(userAgent.includes('ipad'));
    
    // Fetch Jaspel data on component mount
    fetchJaspelData();
    // Fetch Jumlah Pasien data for Jaga integration
    fetchJumlahPasienData();
  }, []);

  const fetchJaspelData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('📊 Fetching Jaspel data...');
      
      // Get current month and year
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1;
      const currentYear = currentDate.getFullYear();
      
      // Get authentication tokens
      const token = localStorage.getItem('auth_token') || 
                   localStorage.getItem('dokterku_auth_token') ||
                   localStorage.getItem('api_token') ||
                   sessionStorage.getItem('auth_token') ||
                   sessionStorage.getItem('dokterku_auth_token') ||
                   sessionStorage.getItem('api_token') ||
                   document.querySelector('meta[name="api-token"]')?.getAttribute('content');
                   
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      console.log('🔑 Token found:', !!token, token ? token.substring(0, 10) + '...' : 'null');
      console.log('🔒 CSRF token found:', !!csrfToken);
      
      // Build headers with authentication
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      };
      
      // Add Bearer token if available
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
      
      // Add CSRF token if available
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }
      
      // Fetch comprehensive jaspel data from correct endpoint
      const response = await fetch(`/api/v2/jaspel/mobile-data-alt?month=${currentMonth}&year=${currentYear}`, {
        method: 'GET',
        headers,
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      // COMPREHENSIVE DATA VALIDATION
      if (!data) {
        throw new Error('No response data received from server');
      }
      
      if (!data.success) {
        throw new Error(data.message || 'API request unsuccessful');
      }
      
      if (!data.data) {
        console.warn('⚠️ No data object in API response, using fallback');
        // Continue with empty data rather than failing
      }
      
      if (data.success && (data.data || data.success)) {
        console.log('✅ Jaspel data received:', data);
        
        // SAFE DATA EXTRACTION: Handle undefined/null jaspel_items
        const jaspelItems = Array.isArray(data.data.jaspel_items) ? data.data.jaspel_items : [];
        const summaryData = data.data.summary || {
          total: 0, approved: 0, pending: 0, rejected: 0,
          count: { total: 0, approved: 0, pending: 0, rejected: 0 }
        };
        
        console.log('📊 Jaspel items count:', jaspelItems.length);
        
        // BULLETPROOF TRANSFORMATION: Comprehensive validation for each item
        const transformedData: JaspelItem[] = jaspelItems.map((item: any) => {
          // Validate each item is an object
          if (!item || typeof item !== 'object') {
            console.warn('⚠️ Invalid jaspel item detected:', item);
            return null;
          }
          
          // Extract and validate jenis field (handle both jenis_jaspel and jenis)
          const jenisField = (item.jenis_jaspel && typeof item.jenis_jaspel === 'string') 
                           ? item.jenis_jaspel 
                           : (item.jenis && typeof item.jenis === 'string')
                           ? item.jenis
                           : '';
          
          // 🔍 DEBUG: Log each item transformation
          console.log('🔍 Transforming jaspel item:', {
            original: item,
            jenisField,
            nominal: item.nominal || item.jumlah,
            jenis_jaspel: item.jenis_jaspel
          });
          
          return {
            id: Number(item.id) || 0,
            tanggal: (item.tanggal && typeof item.tanggal === 'string') 
                    ? item.tanggal 
                    : new Date().toISOString().split('T')[0],
            jenis_jaspel: jenisField,
            nominal: Number(item.nominal || item.jumlah) || 0, // Handle both nominal and jumlah
            status_validasi: String(item.status_validasi || item.status || 'pending'),
            keterangan: String(item.keterangan || ''),
            
            // BULLETPROOF HELPER CALLS: All helper functions now handle any input type
            shift: mapJenisToShift(jenisField),
            jam: getShiftTime(jenisField),
            lokasi: getLocationFromJenis(jenisField),
            tindakan: mapJenisToTindakan(jenisField),
            jenis: jenisField,
            durasi: getDurationFromJenis(jenisField),
            complexity: getComplexityFromJenis(jenisField),
            tim: ['dr. ' + String(item.user_name || 'Dokter')]
          };
        }).filter(Boolean) as JaspelItem[]; // Remove null entries and assert type
        
        // 🔍 DEBUG: Log final transformed data
        console.log('🔍 Final transformed data:', transformedData);
        console.log('🔍 Data structure check:', {
          hasTarif: transformedData.some(item => 'tarif' in item),
          hasBonus: transformedData.some(item => 'bonus' in item),
          hasJenisJaspel: transformedData.some(item => 'jenis_jaspel' in item),
          sampleItem: transformedData[0]
        });
        
        // BULLETPROOF DATA SEPARATION: Ultra-safe string operations
        const jagaData = transformedData.filter(item => {
          if (!item || typeof item !== 'object') return false;
          const jenis = (item.jenis_jaspel && typeof item.jenis_jaspel === 'string') 
                       ? item.jenis_jaspel.toLowerCase() 
                       : '';
          
          try {
            return jenis.includes('jaga') || jenis.includes('shift');
          } catch (error) {
            console.warn('⚠️ Filter error in jagaData:', error, 'item:', item);
            return false;
          }
        });
        
        const tindakanData = transformedData.filter(item => {
          if (!item || typeof item !== 'object') return false;
          const jenis = (item.jenis_jaspel && typeof item.jenis_jaspel === 'string') 
                       ? item.jenis_jaspel.toLowerCase() 
                       : '';
          
          try {
            return !jenis.includes('jaga') && !jenis.includes('shift');
          } catch (error) {
            console.warn('⚠️ Filter error in tindakanData:', error, 'item:', item);
            return false;
          }
        });
        
        setJaspelData(transformedData);
        setJaspelJagaData(jagaData);
        setJaspelTindakanData(tindakanData);
        setSummary(summaryData);
        
      } else {
        console.warn('⚠️ Invalid response structure, using fallback data');
        // Use fallback instead of throwing error
        setJaspelData([]);
        setJaspelJagaData([]);
        setJaspelTindakanData([]);
        setSummary({
          total: 0, approved: 0, pending: 0, rejected: 0,
          count: { total: 0, approved: 0, pending: 0, rejected: 0 }
        });
      }
    } catch (err) {
      console.error('❌ Failed to fetch Jaspel data:', err);
      setError(err instanceof Error ? err.message : 'Failed to load data');
      
      // Use fallback data if API fails
      setJaspelJagaData([]);
      setJaspelTindakanData([]);
    } finally {
      setLoading(false);
    }
  };
  
  // Fetch Jumlah Pasien data from Bendahara validation system
  const fetchJumlahPasienData = async () => {
    try {
      setLoadingJumlahPasien(true);
      
      console.log('📊 Fetching Jumlah Pasien data for Jaga integration...');
      
      // Get current month and year
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1;
      const currentYear = currentDate.getFullYear();
      
      // Get authentication tokens
      const token = localStorage.getItem('auth_token') || 
                   localStorage.getItem('dokterku_auth_token') ||
                   localStorage.getItem('api_token') ||
                   sessionStorage.getItem('auth_token') ||
                   sessionStorage.getItem('dokterku_auth_token') ||
                   sessionStorage.getItem('api_token') ||
                   document.querySelector('meta[name="api-token"]')?.getAttribute('content');
                   
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      // Build headers with authentication
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      };
      
      // Add Bearer token if available
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
      
      // Add CSRF token if available
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }
      
      // Fetch Jumlah Pasien data from new endpoint
      const response = await fetch(`/api/v2/jumlah-pasien/jaspel-jaga?month=${currentMonth}&year=${currentYear}`, {
        method: 'GET',
        headers,
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.success && data.data) {
        console.log('✅ Jumlah Pasien data received:', data);
        
        const jumlahPasienItems = data.data.jumlah_pasien_items || [];
        
        // Transform Jumlah Pasien data to Jaspel format for Jaga tab
        // NOTE: These are only Bendahara-validated (approved) entries
        const transformedJagaData = jumlahPasienItems.map((item: any) => ({
          id: item.id,
          tanggal: item.tanggal,
          jenis_jaspel: item.jenis_jaga || 'jaga_umum',
          nominal: item.estimated_jaspel || 0,
          status_validasi: 'disetujui', // Always approved since API now filters for approved only
          keterangan: `Jaga ${item.poli} - ${item.total_pasien} pasien (${item.jumlah_pasien_umum} umum, ${item.jumlah_pasien_bpjs} BPJS) ✓ Tervalidasi Bendahara`,
          shift: item.shift,
          jam: item.jam,
          lokasi: item.lokasi,
          tarif: item.tarif_base,
          bonus: item.bonus,
          // Additional patient data fields
          dokter_nama: item.dokter_nama,
          total_pasien: item.total_pasien,
          poli: item.poli,
          validasi_by: item.validasi_by,
          validasi_at: item.validasi_at,
          is_bendahara_validated: true, // Flag to indicate Bendahara validation
        }));
        
        // Merge with existing jaspelJagaData or replace it
        setJumlahPasienData(jumlahPasienItems);
        
        // Combine with existing Jaspel Jaga data
        // Priority to Jumlah Pasien data as it's from Bendahara validation
        const mergedJagaData = [...transformedJagaData, ...jaspelJagaData.filter(item => 
          !transformedJagaData.find(jp => jp.tanggal === item.tanggal)
        )];
        
        setJaspelJagaData(mergedJagaData);
        
        // Update summary with Jumlah Pasien statistics
        if (data.data.summary) {
          setSummary(prev => ({
            ...prev,
            total: prev.total + (data.data.summary.total_estimated_jaspel || 0),
          }));
        }
      }
    } catch (err) {
      console.error('❌ Failed to fetch Jumlah Pasien data:', err);
      // Don't set error state as this is supplementary data
      // The main Jaspel data will still work
    } finally {
      setLoadingJumlahPasien(false);
    }
  };
  
  // BULLETPROOF HELPER FUNCTIONS: Triple-layer safety checks
  const mapJenisToShift = (jenis: any): string => {
    // Triple-layer safety: null check + type check + string conversion
    if (!jenis || typeof jenis !== 'string') return 'Pagi';
    const safeJenis = String(jenis).toLowerCase();
    
    try {
      if (safeJenis.includes('pagi')) return 'Pagi';
      if (safeJenis.includes('siang')) return 'Siang';
      if (safeJenis.includes('malam')) return 'Malam';
    } catch (error) {
      console.warn('⚠️ mapJenisToShift error with jenis:', jenis, error);
    }
    return 'Pagi';
  };
  
  const getShiftTime = (jenis: any): string => {
    if (!jenis || typeof jenis !== 'string') return '07:00 - 14:00';
    const safeJenis = String(jenis).toLowerCase();
    
    try {
      if (safeJenis.includes('pagi')) return '07:00 - 14:00';
      if (safeJenis.includes('siang')) return '14:00 - 20:00';
      if (safeJenis.includes('malam')) return '20:00 - 07:00';
    } catch (error) {
      console.warn('⚠️ getShiftTime error with jenis:', jenis, error);
    }
    return '07:00 - 14:00';
  };
  
  const getLocationFromJenis = (jenis: any): string => {
    if (!jenis || typeof jenis !== 'string') return 'Klinik';
    const safeJenis = String(jenis).toLowerCase();
    
    try {
      if (safeJenis.includes('igd') || safeJenis.includes('emergency')) return 'IGD';
      if (safeJenis.includes('icu')) return 'ICU';
      if (safeJenis.includes('poli')) return 'Poli Umum';
      if (safeJenis.includes('bedah')) return 'Ruang Bedah';
    } catch (error) {
      console.warn('⚠️ getLocationFromJenis error with jenis:', jenis, error);
    }
    return 'Klinik';
  };
  
  const mapJenisToTindakan = (jenis: any): string => {
    if (!jenis || typeof jenis !== 'string') return 'Tindakan Medis';
    const safeJenis = String(jenis).toLowerCase();
    
    try {
      if (safeJenis.includes('konsultasi')) return 'Konsultasi Medis';
      if (safeJenis.includes('emergency')) return 'Tindakan Emergency';
      if (safeJenis.includes('operasi') || safeJenis.includes('bedah')) return 'Tindakan Bedah';
      
      // Safe string transformation
      const transformed = String(jenis)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
      return transformed || 'Tindakan Medis';
    } catch (error) {
      console.warn('⚠️ mapJenisToTindakan error with jenis:', jenis, error);
      return 'Tindakan Medis';
    }
  };
  
  const getDurationFromJenis = (jenis: any): string => {
    if (!jenis || typeof jenis !== 'string') return '1 jam';
    const safeJenis = String(jenis).toLowerCase();
    
    try {
      if (safeJenis.includes('konsultasi')) return '30-45 menit';
      if (safeJenis.includes('emergency')) return '2-4 jam';
      if (safeJenis.includes('jaga')) return '7 jam';
    } catch (error) {
      console.warn('⚠️ getDurationFromJenis error with jenis:', jenis, error);
    }
    return '1 jam';
  };
  
  const getComplexityFromJenis = (jenis: any): string => {
    if (!jenis || typeof jenis !== 'string') return 'low';
    const safeJenis = String(jenis).toLowerCase();
    
    try {
      if (safeJenis.includes('emergency') || safeJenis.includes('critical')) return 'critical';
      if (safeJenis.includes('khusus') || safeJenis.includes('operasi')) return 'high';
      if (safeJenis.includes('konsultasi')) return 'medium';
    } catch (error) {
      console.warn('⚠️ getComplexityFromJenis error with jenis:', jenis, error);
    }
    return 'low';
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      // API status values
      disetujui: { bg: 'bg-green-500/20', text: 'text-green-400', border: 'border-green-500/30', label: 'Disetujui' },
      pending: { bg: 'bg-yellow-500/20', text: 'text-yellow-400', border: 'border-yellow-500/30', label: 'Tertunda' },
      ditolak: { bg: 'bg-red-500/20', text: 'text-red-400', border: 'border-red-500/30', label: 'Ditolak' },
      // Legacy status values for fallback
      completed: { bg: 'bg-green-500/20', text: 'text-green-400', border: 'border-green-500/30', label: 'Selesai' },
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

  const totalJaspelJaga = jaspelJagaData.reduce((sum, item) => sum + (item.nominal || (item.tarif || 0) + (item.bonus || 0)), 0);
  const totalJaspelTindakan = jaspelTindakanData.reduce((sum, item) => sum + (item.nominal || item.tarif || 0), 0);
  const grandTotal = summary.total || (totalJaspelJaga + totalJaspelTindakan);

  const completedJaga = jaspelJagaData.filter(item => item.status_validasi === 'disetujui' || item.status === 'completed').length;
  const completedTindakan = jaspelTindakanData.filter(item => item.status_validasi === 'disetujui' || item.status === 'completed').length;

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

  // Loading state
  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white flex items-center justify-center">
        <div className="text-center">
          <RefreshCw className="w-12 h-12 text-emerald-400 animate-spin mx-auto mb-4" />
          <p className="text-emerald-300 text-xl">Memuat data JASPEL...</p>
          <p className="text-gray-400 text-sm mt-2">Mengambil data terbaru dari server</p>
        </div>
      </div>
    );
  }
  
  // Error state
  if (error && jaspelData.length === 0) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white flex items-center justify-center">
        <div className="text-center max-w-md mx-auto px-6">
          <AlertCircle className="w-12 h-12 text-red-400 mx-auto mb-4" />
          <h2 className="text-red-300 text-xl mb-4">Gagal Memuat Data</h2>
          <p className="text-gray-400 text-sm mb-6">{error}</p>
          <button 
            onClick={fetchJaspelData}
            className="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors flex items-center mx-auto"
          >
            <RefreshCw className="w-4 h-4 mr-2" />
            Coba Lagi
          </button>
        </div>
      </div>
    );
  }

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
              <div className="flex items-center space-x-3">
                {error && (
                  <div className="text-yellow-400 text-xs bg-yellow-900/20 px-3 py-1 rounded-full border border-yellow-500/30">
                    Data lokal
                  </div>
                )}
                <button 
                  onClick={() => {
                    fetchJaspelData();
                    fetchJumlahPasienData();
                  }}
                  className="p-3 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 rounded-xl border border-emerald-500/30 transition-colors"
                  title="Refresh data"
                >
                  <RefreshCw className={`w-5 h-5 ${loading || loadingJumlahPasien ? 'animate-spin' : ''}`} />
                </button>
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
                      {jaspelJagaData.filter(item => item.status_validasi === 'pending' || item.status === 'pending').length}
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
                  {jaspelJagaData.length} total jaga • Data dinamis
                  {jumlahPasienData.length > 0 && (
                    <span className="ml-2 text-emerald-400">
                      • {jumlahPasienData.length} tervalidasi Bendahara
                    </span>
                  )}
                </div>
              </div>
              {/* Bendahara Validation Notice */}
              {jumlahPasienData.length > 0 && (
                <div className="mt-3 bg-green-500/10 border border-green-500/30 rounded-lg px-3 py-2 flex items-center">
                  <CheckCircle className="w-4 h-4 text-green-400 mr-2 flex-shrink-0" />
                  <span className="text-green-300 text-sm">
                    Hanya menampilkan data jaga yang sudah divalidasi dan disetujui oleh Bendahara untuk perhitungan Jaspel
                  </span>
                </div>
              )}
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
                      {item.total_pasien && (
                        <div className="flex items-center mt-2 space-x-4">
                          <div className="flex items-center">
                            <Users className="w-4 h-4 text-emerald-400 mr-1" />
                            <span className="text-emerald-300 text-sm font-medium">
                              {item.total_pasien} pasien
                            </span>
                          </div>
                          {item.is_bendahara_validated && (
                            <div className="flex items-center bg-green-500/20 px-2 py-1 rounded-full">
                              <CheckCircle className="w-4 h-4 text-green-400 mr-1" />
                              <span className="text-green-300 text-xs font-medium">
                                ✓ Tervalidasi Bendahara
                              </span>
                            </div>
                          )}
                          {item.validasi_by && !item.is_bendahara_validated && (
                            <div className="flex items-center">
                              <CheckCircle className="w-4 h-4 text-green-400 mr-1" />
                              <span className="text-green-300 text-xs">
                                Validasi: {item.validasi_by}
                              </span>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                    <div className="text-right ml-6">
                      <div className="mb-2">{getStatusBadge(item.status_validasi || item.status)}</div>
                      <div className="text-white font-bold text-lg">{formatCurrency(item.nominal || item.jumlah)}</div>
                      <div className="text-xs text-gray-400">
                        {item.jenis_jaspel ? (
                          <>Jenis: {item.jenis_jaspel}</>
                        ) : (
                          <>Nominal: {formatCurrency(item.nominal || item.jumlah)}</>
                        )}
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
                  {jaspelTindakanData.length} total tindakan • Data dinamis
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
                        {getStatusBadge(item.status_validasi || item.status)}
                      </div>
                    </div>
                    <div className="text-right ml-6">
                      <div className="text-white font-bold text-xl">{formatCurrency(item.nominal || item.tarif)}</div>
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