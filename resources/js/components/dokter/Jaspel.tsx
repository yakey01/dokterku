import React, { useState, useEffect } from 'react';
import { 
  Trophy, Calendar, Clock, TrendingUp, Award, Target, Activity, Star, 
  ChevronLeft, ChevronRight, Eye, FileText, CreditCard, Stethoscope, 
  Users, MapPin, CheckCircle, RefreshCw, AlertCircle, Gamepad2, 
  Zap, Crown, Medal, Gem, Coins, Gift, Sparkles, Bell, Wifi
} from 'lucide-react';

// Real-time WebSocket integration
declare global {
  interface Window {
    Echo: any;
    Pusher: any;
  }
}

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
  // ✅ ADDED: For coordinating Jumlah Pasien data without double counting
  jumlah_pasien_total?: number;
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
  
  // Validation status state
  const [validationStatus, setValidationStatus] = useState<any>(null);
  const [pendingSummary, setPendingSummary] = useState<any>(null);
  
  // Separate jaga and tindakan data
  const [jaspelJagaData, setJaspelJagaData] = useState<JaspelItem[]>([]);
  const [jaspelTindakanData, setJaspelTindakanData] = useState<JaspelItem[]>([]);
  
  // 🔧 REMOVED: Jumlah Pasien data integration - ValidatedJaspel API now includes all data
  // const [jumlahPasienData, setJumlahPasienData] = useState<any[]>([]);
  // const [loadingJumlahPasien, setLoadingJumlahPasien] = useState(false);
  
  // 🚀 Real-time WebSocket state
  const [realtimeConnected, setRealtimeConnected] = useState(false);
  const [realtimeNotifications, setRealtimeNotifications] = useState<any[]>([]);
  const [lastUpdateTime, setLastUpdateTime] = useState<string>('Never');
  const [newDataAvailable, setNewDataAvailable] = useState(false);
  

  useEffect(() => {
    // Detect iPad for layout adjustments
    const userAgent = navigator.userAgent.toLowerCase();
    setIsIPad(userAgent.includes('ipad'));
    
    // Fetch Jaspel data on component mount
    fetchJaspelData();
    
    // 🚀 REAL-TIME: WebSocket connection for instant updates
    const setupRealtimeConnection = () => {
      try {
        // Check if Echo is available (WebSocket)
        if (typeof window !== 'undefined' && window.Echo) {
          console.log('🔌 Setting up real-time WebSocket connection...');
          
          // Get current user ID from meta tag or localStorage
          const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') ||
                        localStorage.getItem('user_id') ||
                        '13'; // Fallback to Yaya's ID for testing
          
          // Listen to private dokter channel
          window.Echo.private(`dokter.${userId}`)
            .listen('tindakan.validated', (event: any) => {
              console.log('🎯 Real-time validation received:', event);
              
              // Show notification
              showRealtimeNotification(event.notification);
              
              // Fetch fresh data immediately
              fetchJaspelData();
              
              // Update UI state
              setLastUpdateTime(new Date().toLocaleTimeString());
              setNewDataAvailable(true);
              
              // Clear "new data" indicator after 5 seconds
              setTimeout(() => setNewDataAvailable(false), 5000);
            });
            
          // Listen to connection status
          window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ WebSocket connected');
            setRealtimeConnected(true);
          });
          
          window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('❌ WebSocket disconnected');
            setRealtimeConnected(false);
          });
          
        } else {
          console.log('⚠️ Echo not available, falling back to polling...');
          setRealtimeConnected(false);
        }
      } catch (error) {
        console.error('❌ Failed to setup WebSocket:', error);
        setRealtimeConnected(false);
      }
    };
    
    // Setup real-time connection
    setupRealtimeConnection();
    
    // 🔄 INTELLIGENT POLLING: Smart rate-limit-aware auto-refresh
    let refreshAttempts = 0;
    let lastSuccessfulRefresh = Date.now();
    let rateLimitBackoff = 1; // Multiplier for backoff
    
    // 🔄 SIMPLIFIED: Auto-refresh with rate limit protection
    const refreshInterval = setInterval(() => {
      if (!realtimeConnected && !isCurrentlyFetching) {
        const timeSinceLastSuccess = Date.now() - lastSuccessfulRefresh;
        
        // Only refresh if enough time has passed (60 seconds minimum)
        if (timeSinceLastSuccess >= 60000) {
          console.log('🔄 Auto-refreshing JASPEL data (rate-limit protected)...');
          
          fetchJaspelData()
            .then(() => {
              lastSuccessfulRefresh = Date.now();
              console.log('✅ Auto-refresh successful');
            })
            .catch((error) => {
              console.warn('⚠️ Auto-refresh failed:', error.message);
              // Don't retry automatically - wait for next interval
            });
        } else {
          console.log('⏱️ Skipping refresh - rate limit protection active');
        }
      }
    }, 60000); // 60 seconds - safer for rate limits
    
    // Cleanup on unmount
    return () => {
      clearInterval(refreshInterval);
      
      if (window.Echo) {
        try {
          window.Echo.leave(`dokter.${userId}`);
        } catch (error) {
          console.log('Echo cleanup error:', error);
        }
      }
    };
  }, []);

  // Real-time notification handler
  const showRealtimeNotification = (notification: any) => {
    console.log('📢 Showing real-time notification:', notification);
    
    // Add to notifications array
    const newNotification = {
      id: Date.now(),
      ...notification,
      timestamp: new Date().toLocaleTimeString(),
    };
    
    setRealtimeNotifications(prev => [newNotification, ...prev.slice(0, 4)]); // Keep last 5
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
      setRealtimeNotifications(prev => prev.filter(n => n.id !== newNotification.id));
    }, 10000);
  };

  // 🛡️ Request deduplication to prevent multiple simultaneous requests
  const [isCurrentlyFetching, setIsCurrentlyFetching] = useState(false);
  
  const fetchJaspelData = async () => {
    // Prevent duplicate requests
    if (isCurrentlyFetching) {
      console.log('⚠️ Fetch already in progress, skipping duplicate request');
      return;
    }
    
    try {
      setIsCurrentlyFetching(true);
      setLoading(true);
      setError(null);
      
      console.log('📊 Fetching VALIDATED Jaspel data only...');
      
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
      
      // Fetch VALIDATED jaspel data from new secure endpoint
      const response = await fetch(`/api/v2/jaspel/validated/gaming-data?month=${currentMonth}&year=${currentYear}`, {
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
      
      if (data.success && data.data) {
        console.log('✅ VALIDATED Jaspel data received:', data);
        
        // GAMING UI VALIDATION: Only validated data is received
        const gamingStats = data.data.gaming_stats || {};
        const jagaQuests = Array.isArray(data.data.jaga_quests) ? data.data.jaga_quests : [];
        const achievementTindakan = Array.isArray(data.data.achievement_tindakan) ? data.data.achievement_tindakan : [];
        const summaryData = data.data.summary || {
          total: 0, approved: 0, pending: 0, rejected: 0,
          count: { total: 0, approved: 0, pending: 0, rejected: 0 }
        };
        
        console.log('🎮 Gaming stats:', gamingStats);
        console.log('🏆 Validated jaga quests:', jagaQuests.length);
        console.log('🎯 Validated achievements:', achievementTindakan.length);
        
        // Validation guarantee check
        if (data.data.validation_guarantee?.all_amounts_validated) {
          console.log('✅ FINANCIAL ACCURACY GUARANTEED: All amounts are bendahara-validated');
        }
        
        // VALIDATED DATA TRANSFORMATION: Only validated items are processed
        const transformedJagaData: JaspelItem[] = jagaQuests.map((item: any) => {
          const jenisField = item.jenis_jaspel || 'jaga_umum';
          
          // Clean up keterangan to remove duplicate BENDAHARA OFICIAL text
          let cleanKeterangan = item.keterangan || 'Validated by Bendahara';
          let extractedTotalPasien = item.total_pasien;
          
          // Remove redundant BENDAHARA OFICIAL prefix and financial details
          if (cleanKeterangan.includes('BENDAHARA OFICIAL')) {
            // Extract just the essential info (e.g., "Jaspel jaga 08/08/2025 (100 total pasien)")
            const match = cleanKeterangan.match(/Jaspel jaga \d{2}\/\d{2}\/\d{4} \(\d+ total pasien\)/);
            if (match) {
              cleanKeterangan = match[0];
            } else {
              // Fallback: remove BENDAHARA OFICIAL prefix and amount suffix
              cleanKeterangan = cleanKeterangan
                .replace(/^BENDAHARA OFICIAL\s*-\s*/, '')
                .replace(/\s*-\s*Rp\s*[\d,\.]+$/, '');
            }
          }
          
          // Extract total_pasien from keterangan if not already provided
          if (!extractedTotalPasien && cleanKeterangan.includes('total pasien')) {
            const pasienMatch = cleanKeterangan.match(/(\d+)\s*total pasien/);
            if (pasienMatch) {
              extractedTotalPasien = parseInt(pasienMatch[1]);
            }
          }
          
          return {
            id: Number(item.id) || 0,
            tanggal: item.tanggal || new Date().toISOString().split('T')[0],
            jenis_jaspel: jenisField,
            nominal: Number(item.nominal) || 0,
            status_validasi: 'disetujui', // Always approved since validated
            keterangan: cleanKeterangan,
            shift: mapJenisToShift(jenisField),
            jam: getShiftTime(jenisField),
            lokasi: getLocationFromJenis(jenisField),
            tindakan: mapJenisToTindakan(jenisField),
            jenis: jenisField,
            durasi: getDurationFromJenis(jenisField),
            complexity: getComplexityFromJenis(jenisField),
            tim: ['dr. ' + String(item.user_name || 'Dokter')],
            validation_guaranteed: true, // Flag for UI display
            total_pasien: extractedTotalPasien
          };
        });
        
        // 🔧 GROUP ACHIEVEMENTS BY TINDAKAN to match bendahara display
        const groupedByTindakan = achievementTindakan.reduce((acc: any, item: any) => {
          const tindakanId = item.tindakan_id || `manual_${item.id}`;
          
          if (!acc[tindakanId]) {
            acc[tindakanId] = {
              tindakan_id: item.tindakan_id,
              jenis: item.jenis,
              tanggal: item.tanggal,
              total_nominal: 0,
              jaspel_breakdown: [],
              latest_item: item
            };
          }
          
          acc[tindakanId].total_nominal += Number(item.nominal) || 0;
          acc[tindakanId].jaspel_breakdown.push({
            jenis_jaspel: item.jenis_jaspel,
            nominal: Number(item.nominal) || 0,
            source: item.source
          });
          
          return acc;
        }, {});

        const transformedTindakanData: JaspelItem[] = Object.values(groupedByTindakan).map((group: any) => {
          const item = group.latest_item;
          const jenisField = item.jenis_jaspel || 'paramedis';
          
          // Create detailed breakdown for keterangan
          const breakdown = group.jaspel_breakdown.map((b: any) => 
            `${b.jenis_jaspel}: Rp ${Number(b.nominal).toLocaleString()}`
          ).join(', ');
          
          return {
            id: Number(item.id) || 0,
            tanggal: item.tanggal || new Date().toISOString().split('T')[0],
            jenis_jaspel: jenisField,
            nominal: group.total_nominal, // Combined total from all JASPEL types
            status_validasi: 'disetujui', // Always approved since validated
            keterangan: `${item.jenis} - Total: Rp ${group.total_nominal.toLocaleString()} (${breakdown})`,
            shift: mapJenisToShift(jenisField),
            jam: getShiftTime(jenisField),
            lokasi: getLocationFromJenis(jenisField),
            tindakan: item.jenis || mapJenisToTindakan(jenisField),
            jenis: jenisField,
            durasi: getDurationFromJenis(jenisField),
            complexity: getComplexityFromJenis(jenisField),
            tim: ['dr. ' + String(item.user_name || 'Dokter')],
            validation_guaranteed: true,
            // Additional fields for grouped display
            jaspel_breakdown: group.jaspel_breakdown,
            tindakan_id: group.tindakan_id
          };
        });
        
        const transformedData = [...transformedJagaData, ...transformedTindakanData];
        
        // 🔍 DEBUG: Log validated data
        console.log('🔍 Final validated data:', {
          jaga: transformedJagaData.length,
          tindakan: transformedTindakanData.length,
          total: transformedData.length,
          validation_guaranteed: transformedData.every(item => item.validation_guaranteed)
        });
        
        setJaspelData(transformedData);
        setJaspelJagaData(transformedJagaData);
        setJaspelTindakanData(transformedTindakanData);
        setSummary(summaryData);
        
        // Set validation status for UI display
        if (data.data.validation_guarantee) {
          setValidationStatus(data.data.validation_guarantee);
        }
        
        // Show validation success message
        if (data.data.validation_guarantee?.financial_accuracy === 'guaranteed') {
          console.log('🎯 GAMING UI SAFE: All amounts are bendahara-validated');
        }
        
      } else {
        console.warn('⚠️ No validated data available');
        // No fallback - only show validated data
        setJaspelData([]);
        setJaspelJagaData([]);
        setJaspelTindakanData([]);
        setSummary({
          total: 0, approved: 0, pending: 0, rejected: 0,
          count: { total: 0, approved: 0, pending: 0, rejected: 0 }
        });
        setError('No validated JASPEL data available. Please wait for bendahara approval.');
      }
    } catch (err) {
      console.error('❌ Failed to fetch validated JASPEL data:', err);
      setError('Failed to load validated JASPEL data. Only bendahara-approved amounts can be displayed.');
      
      // No fallback data - only validated amounts allowed
      setJaspelData([]);
      setJaspelJagaData([]);
      setJaspelTindakanData([]);
    } finally {
      setLoading(false);
      setIsCurrentlyFetching(false); // 🛡️ Always reset fetch flag
    }
  };
  
  // 🔧 REMOVED: fetchJumlahPasienData function - ValidatedJaspel API now includes all patient count data
  // This prevents double counting and data conflicts
  
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
  
  // ✅ FIX: Proper total calculation without double counting
  // IMPORTANT DATA COORDINATION LOGIC:
  // 1. summary.total comes from ValidatedJaspelController (authoritative source)
  // 2. JumlahPasien data is stored separately to prevent double counting
  // 3. Priority: Validated API total > calculated fallback totals
  // 4. Do NOT add jumlah_pasien_total as it may overlap with validated data
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
                <div className="w-20 h-20 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-2xl flex items-center justify-center relative overflow-hidden shadow-2xl">
                  <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                  <div className="absolute inset-0 bg-gradient-to-br from-yellow-400/20 via-transparent to-purple-600/20 animate-pulse"></div>
                  <Trophy className="w-10 h-10 text-white relative z-10 drop-shadow-lg" />
                  <Sparkles className="w-4 h-4 text-yellow-300 absolute top-2 right-2 animate-pulse" />
                </div>
                <div className="ml-6">
                  <h1 className="text-4xl font-bold bg-gradient-to-r from-yellow-400 via-pink-400 to-purple-400 bg-clip-text text-transparent mb-2 flex items-center gap-3">
                    <Crown className="w-8 h-8 text-yellow-400 drop-shadow-lg" />
                    REWARD
                    <Medal className="w-8 h-8 text-purple-400 drop-shadow-lg" />
                  </h1>
                  <p className="text-gray-300 text-lg flex items-center gap-2">
                    <Gamepad2 className="w-5 h-5 text-pink-400" />
                    Achievement System
                    <Zap className="w-5 h-5 text-yellow-400 animate-pulse" />
                  </p>
                </div>
              </div>
              <div className="flex items-center space-x-3">
                {validationStatus?.all_amounts_validated && (
                  <div className="text-green-400 text-xs bg-green-900/20 px-3 py-1 rounded-full border border-green-500/30 flex items-center gap-1">
                    <CheckCircle className="w-3 h-3" />
                    Fully Validated
                  </div>
                )}
                {error && (
                  <div className="text-red-400 text-xs bg-red-900/20 px-3 py-1 rounded-full border border-red-500/30">
                    Validation Required
                  </div>
                )}
                
                {/* 🚀 Real-time status indicator */}
                <div className={`text-xs px-3 py-1 rounded-full border flex items-center gap-1 ${
                  realtimeConnected 
                    ? 'text-green-400 bg-green-900/20 border-green-500/30' 
                    : 'text-yellow-400 bg-yellow-900/20 border-yellow-500/30'
                }`}>
                  <Wifi className={`w-3 h-3 ${realtimeConnected ? '' : 'opacity-50'}`} />
                  {realtimeConnected ? 'Live' : 'Polling'}
                </div>
                
                {/* New data indicator */}
                {newDataAvailable && (
                  <div className="text-blue-400 text-xs bg-blue-900/20 px-3 py-1 rounded-full border border-blue-500/30 flex items-center gap-1 animate-pulse">
                    <Bell className="w-3 h-3" />
                    New Data
                  </div>
                )}
                
                <button 
                  onClick={() => {
                    fetchJaspelData();
                    setLastUpdateTime(new Date().toLocaleTimeString());
                  }}
                  className="p-3 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 rounded-xl border border-emerald-500/30 transition-colors"
                  title={`Manual refresh - Last update: ${lastUpdateTime}`}
                >
                  <RefreshCw className={`w-5 h-5 ${loading ? 'animate-spin' : ''}`} />
                </button>
              </div>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 rounded-2xl p-6 border border-yellow-400/20 relative overflow-hidden">
                <div className="absolute top-1 right-1">
                  {validationStatus?.all_amounts_validated ? (
                    <CheckCircle className="w-4 h-4 text-green-400 animate-pulse" />
                  ) : (
                    <Gem className="w-4 h-4 text-yellow-400 animate-pulse" />
                  )}
                </div>
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-yellow-300 text-sm mb-1 flex items-center gap-1">
                      <Coins className="w-4 h-4" />
                      Total Gold Earned
                      {validationStatus?.all_amounts_validated && (
                        <span className="text-green-400 text-xs ml-2">✓ Validated</span>
                      )}
                    </p>
                    <p className="text-3xl font-bold text-white">{formatCurrency(grandTotal)}</p>
                  </div>
                  <div className="relative">
                    <Trophy className="w-8 h-8 text-yellow-400" />
                    <Sparkles className="w-3 h-3 text-yellow-200 absolute -top-1 -right-1 animate-pulse" />
                  </div>
                </div>
                <p className="text-xs text-yellow-300 mt-2 flex items-center gap-1">
                  <Medal className="w-3 h-3" />
                  {validationStatus?.bendahara_approved ? 'Bendahara Approved' : 'Quest Rewards + Bonus XP'}
                </p>
              </div>

              <div className="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl p-6 border border-blue-400/20 relative overflow-hidden">
                <div className="absolute top-1 right-1">
                  <Star className="w-4 h-4 text-blue-400 animate-spin" />
                </div>
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-blue-300 text-sm mb-1 flex items-center gap-1">
                      <Award className="w-4 h-4" />
                      Days Worked
                    </p>
                    <p className="text-3xl font-bold text-white">{completedJaga} Days</p>
                  </div>
                  <div className="relative">
                    <Clock className="w-8 h-8 text-blue-400" />
                    <Zap className="w-3 h-3 text-cyan-300 absolute -top-1 -right-1 animate-pulse" />
                  </div>
                </div>
                <p className="text-xs text-blue-300 mt-2 flex items-center gap-1">
                  <Target className="w-3 h-3" />
                  Daily Quests Progress
                </p>
              </div>

              <div className="bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-2xl p-6 border border-purple-400/20 relative overflow-hidden">
                <div className="absolute top-1 right-1">
                  <Crown className="w-4 h-4 text-purple-400 animate-bounce" />
                </div>
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-purple-300 text-sm mb-1 flex items-center gap-1">
                      <Gift className="w-4 h-4" />
                      Special Achievements
                    </p>
                    <p className="text-3xl font-bold text-white">{jaspelTindakanData.length}</p>
                  </div>
                  <div className="relative">
                    <Stethoscope className="w-8 h-8 text-purple-400" />
                    <Medal className="w-3 h-3 text-pink-300 absolute -top-1 -right-1 animate-pulse" />
                  </div>
                </div>
                <p className="text-xs text-purple-300 mt-2 flex items-center gap-1">
                  <Star className="w-3 h-3" />
                  Legendary Skills Unlocked
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-2 mb-8 border border-white/10">
          <div className="grid grid-cols-3 gap-2">
            {[
              { id: 'overview', label: 'Dashboard', icon: TrendingUp },
              { id: 'jaga', label: 'Daily Quests', icon: Target },
              { id: 'tindakan', label: 'Achievements', icon: Award }
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
          <div className="space-y-6">
            {/* Statistics Grid */}
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
                  {jaspelJagaData.length} total jaga • Bendahara validated data
                </div>
              </div>
              {/* Validation Guarantee Notice */}
              {validationStatus && (
                <div className="mt-3 bg-green-500/10 border border-green-500/30 rounded-lg px-3 py-2 flex items-center">
                  <CheckCircle className="w-4 h-4 text-green-400 mr-2 flex-shrink-0" />
                  <span className="text-green-300 text-sm">
                    ✅ JAMINAN FINANSIAL: Hanya menampilkan JASPEL yang sudah divalidasi dan disetujui Bendahara
                  </span>
                </div>
              )}
              {pendingSummary?.pending_count > 0 && (
                <div className="mt-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg px-3 py-2 flex items-center">
                  <AlertCircle className="w-4 h-4 text-yellow-400 mr-2 flex-shrink-0" />
                  <span className="text-yellow-300 text-sm">
                    ⏳ Ada {pendingSummary.pending_count} item JASPEL menunggu validasi Bendahara
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
                          {item.validation_guaranteed && (
                            <div className="flex items-center bg-green-500/20 px-2 py-1 rounded-full">
                              <CheckCircle className="w-4 h-4 text-green-400 mr-1" />
                              <span className="text-green-300 text-xs font-medium">
                                ✅ BENDAHARA VALIDATED
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
                  {jaspelTindakanData.length} total tindakan • {validationStatus?.bendahara_approved ? 'Bendahara Validated' : 'Data dinamis'}
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
                        <Clock className="w-4 h-4 text-blue-400 mr-2" />
                        <span className="text-blue-300">{item.shift}</span>
                        <span className="mx-2 text-gray-400">•</span>
                        <span className="text-gray-300">{item.tanggal}</span>
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

        {/* 🚀 Real-time Notifications */}
        {realtimeNotifications.length > 0 && (
          <div className="mt-8 space-y-3">
            <h3 className="text-white font-bold text-lg flex items-center gap-2">
              <Bell className="w-5 h-5 text-blue-400" />
              Real-time Updates
            </h3>
            {realtimeNotifications.slice(0, 3).map((notification) => (
              <div
                key={notification.id}
                className={`p-4 rounded-xl border backdrop-blur-sm transition-all duration-500 ${
                  notification.type === 'success' 
                    ? 'bg-green-500/10 border-green-500/30 text-green-300' 
                    : notification.type === 'error'
                    ? 'bg-red-500/10 border-red-500/30 text-red-300'
                    : 'bg-blue-500/10 border-blue-500/30 text-blue-300'
                }`}
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="font-semibold mb-1">{notification.title}</div>
                    <div className="text-sm opacity-90">{notification.message}</div>
                  </div>
                  <div className="text-xs opacity-70 ml-4">
                    {notification.timestamp}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Footer Info */}
        <div className="mt-8 text-center">
          <p className="text-gray-500 text-sm">
            JASPEL • Jasa Pelayanan Medis • Dashboard Dokter
            {realtimeConnected && (
              <span className="ml-2 text-green-400">• 🔴 Live Updates</span>
            )}
          </p>
        </div>

      </div>
    </div>
  );
};

export default JaspelComponent;