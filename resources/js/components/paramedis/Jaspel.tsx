import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import { Trophy, Calendar, TrendingUp, Eye, Wallet, CreditCard, PiggyBank, RefreshCw, AlertCircle, Crown, Medal, Gem, Coins, Gift, Sparkles, Gamepad2, Zap, Award } from 'lucide-react';

interface JaspelItem {
  id: string;
  tanggal: string;
  jenis: string;
  jumlah: number;
  status: 'pending' | 'paid' | 'rejected';
  keterangan?: string;
  validated_by?: string;
  validated_at?: string;
}

interface JaspelSummary {
  total_paid: number;
  total_pending: number;
  total_rejected: number;
  count_paid: number;
  count_pending: number;
  count_rejected: number;
}

interface JaspelApiResponse {
  success: boolean;
  message: string;
  data: {
    jaspel_items: JaspelItem[];
    summary: JaspelSummary;
  };
  meta: {
    month: number;
    year: number;
    user_name: string;
  };
}

export function Jaspel() {
  const [jaspelData, setJaspelData] = useState<JaspelItem[]>([]);
  
  // BULLETPROOF: Data normalization methods to handle different API formats
  const normalizeJaspelItems = (items: any[]): JaspelItem[] => {
    if (!Array.isArray(items)) return [];
    
    return items.map(item => {
      if (!item || typeof item !== 'object') return null;
      
      return {
        id: String(item.id || Math.random()),
        tanggal: String(item.tanggal || new Date().toISOString().split('T')[0]),
        jenis: String(item.jenis_jaspel || item.jenis || ''),
        jumlah: Number(item.nominal || item.jumlah) || 0,
        status: String(item.status_validasi || item.status || 'pending'),
        keterangan: String(item.keterangan || ''),
        validated_by: item.validated_by || null,
        validated_at: item.validated_at || null
      };
    }).filter(Boolean) as JaspelItem[];
  };
  
  const normalizeLegacyJaspelItems = (items: any[]): JaspelItem[] => {
    if (!Array.isArray(items)) return [];
    
    return items.map(item => {
      if (!item || typeof item !== 'object') return null;
      
      return {
        id: String(item.id || Math.random()),
        tanggal: String(item.tanggal || new Date().toISOString().split('T')[0]),
        jenis: String(item.jenis || ''),
        jumlah: Number(item.jumlah) || 0,
        status: String(item.status || 'pending'),
        keterangan: String(item.keterangan || ''),
        validated_by: item.validated_by || null,
        validated_at: item.validated_at || null
      };
    }).filter(Boolean) as JaspelItem[];
  };
  
  const calculateSummaryFromItems = (items: JaspelItem[]): JaspelSummary => {
    const summary = {
      total_paid: 0,
      total_pending: 0,
      total_rejected: 0,
      count_paid: 0,
      count_pending: 0,
      count_rejected: 0
    };
    
    items.forEach(item => {
      const amount = item.jumlah || 0;
      
      if (item.status === 'paid' || item.status === 'disetujui') {
        summary.total_paid += amount;
        summary.count_paid++;
      } else if (item.status === 'pending') {
        summary.total_pending += amount;
        summary.count_pending++;
      } else if (item.status === 'rejected' || item.status === 'ditolak') {
        summary.total_rejected += amount;
        summary.count_rejected++;
      }
    });
    
    return summary;
  };
  const [summary, setSummary] = useState<JaspelSummary>({
    total_paid: 0,
    total_pending: 0,
    total_rejected: 0,
    count_paid: 0,
    count_pending: 0,
    count_rejected: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentMonth] = useState(new Date().getMonth() + 1);
  const [currentYear] = useState(new Date().getFullYear());

  // WORLD-CLASS: Fetch Jaspel data with multiple endpoint fallback
  const fetchJaspelData = async (month?: number, year?: number) => {
    console.log('🔍 [JASPEL DEBUG] Starting WORLD-CLASS fetchJaspelData...', { month, year });
    setIsLoading(true);
    setError(null);
    
    try {
      // Try multiple token sources
      const token = localStorage.getItem('token') || 
                   sessionStorage.getItem('token') || 
                   document.querySelector('meta[name="api-token"]')?.getAttribute('content');
      
      console.log('🔑 [JASPEL DEBUG] Token found:', !!token, token ? token.substring(0, 10) + '...' : 'null');

      const params = new URLSearchParams();
      if (month) params.append('month', month.toString());
      if (year) params.append('year', year.toString());

      // WORLD-CLASS: Try multiple endpoints for maximum reliability (FIXED ROUTING)
      const urls = [
        `/paramedis/api/v2/jaspel/mobile-data?${params}`, // Now exists! Fixed in routes/web.php
        `/api/v2/jaspel/mobile-data-alt?${params}`,       // Fallback universal endpoint
        `/api/v2/dashboards/paramedis/jaspel?${params}`   // Legacy paramedis endpoint
      ];
      
      console.log('🔍 [DEEP DEBUG] All URLs to try:', urls);
      console.log('🔍 [DEEP DEBUG] Current window location:', window.location.href);
      console.log('🔍 [DEEP DEBUG] Base URL:', window.location.origin);
      
      let lastError = null;
      
      for (const url of urls) {
        try {
          console.log('📡 [JASPEL DEBUG] Trying endpoint:', url);
          console.log('🔍 [DEEP DEBUG] Full URL being requested:', window.location.origin + url);

          const headers: any = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          };

          if (token) {
            headers['Authorization'] = `Bearer ${token}`;
          }

          console.log('📋 [JASPEL DEBUG] Request headers:', headers);

          const response = await fetch(url, {
            method: 'GET',
            headers,
            credentials: 'include', // Include cookies for session auth
          });

          console.log('📊 [JASPEL DEBUG] Response status:', response.status);
          console.log('🔍 [DEEP DEBUG] Response URL:', response.url);
          console.log('🔍 [DEEP DEBUG] Response headers:', Object.fromEntries(response.headers.entries()));

          if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ [JASPEL DEBUG] API Error:', response.status, errorText);
            console.error('❌ [JASPEL DEBUG] Response headers:', Object.fromEntries(response.headers.entries()));
            console.error('❌ [JASPEL DEBUG] Request URL was:', response.url);
            
            // Store error for potential fallback
            lastError = new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            
            if (response.status === 401) {
              console.error('🔐 [AUTH ERROR] 401 Unauthorized - This is likely the root cause!');
              lastError = new Error('Sesi telah berakhir, silakan login kembali');
            }
            
            if (response.status === 403) {
              console.error('🚫 [PERMISSION ERROR] 403 Forbidden - Role permission issue!');
              lastError = new Error('Tidak memiliki izin untuk mengakses data Jaspel');
            }
            
            // Try next endpoint
            continue;
          }

          const result: any = await response.json();
          console.log('✅ [JASPEL DEBUG] API Success with', url, ':', result);
          
          // BULLETPROOF: Handle different API response formats
          if (result.success || result.jaspel) {
            // Normalize different API response formats
            let jaspelItems: JaspelItem[] = [];
            let summaryData = {
              total_paid: 0,
              total_pending: 0, 
              total_rejected: 0,
              count_paid: 0,
              count_pending: 0,
              count_rejected: 0
            };
            
            // Handle unified format (from new endpoint)
            if (result.data && result.data.jaspel_items) {
              jaspelItems = normalizeJaspelItems(result.data.jaspel_items);
              summaryData = result.data.summary || summaryData;
            }
            // Handle legacy paramedis format 
            else if (result.jaspel) {
              jaspelItems = normalizeLegacyJaspelItems(result.jaspel);
              summaryData = calculateSummaryFromItems(jaspelItems);
            }
            // Handle direct data array
            else if (Array.isArray(result.data)) {
              jaspelItems = normalizeJaspelItems(result.data);
              summaryData = calculateSummaryFromItems(jaspelItems);
            }
            
            console.log('📋 [JASPEL DEBUG] Normalized data:', {
              items: jaspelItems.length,
              summary: summaryData,
              endpoint_used: url,
              original_format: result.data ? 'unified' : result.jaspel ? 'legacy' : 'unknown'
            });
            
            setJaspelData(jaspelItems);
            setSummary(summaryData);
            return; // Success, exit the function
          } else {
            lastError = new Error(result.message || 'Gagal mengambil data Jaspel');
            continue; // Try next endpoint
          }
        } catch (endpointError) {
          console.error('🔄 [JASPEL DEBUG] Endpoint failed:', url, endpointError);
          lastError = endpointError instanceof Error ? endpointError : new Error('Network error');
          continue; // Try next endpoint
        }
      }
      
      // If we reach here, all endpoints failed
      console.error('🚨 [CRITICAL] All API endpoints failed for Jaspel data');
      throw lastError || new Error('Semua endpoint gagal diakses');
    } catch (err) {
      console.error('🔥 [JASPEL ERROR] Final catch - API call completely failed:', err);
      console.error('🔥 [JASPEL ERROR] Error type:', typeof err);
      console.error('🔥 [JASPEL ERROR] Error message:', err instanceof Error ? err.message : String(err));
      console.error('🔥 [JASPEL ERROR] This is why data is showing as empty!');
      setError(err instanceof Error ? err.message : 'Terjadi kesalahan saat mengambil data');
      
      // Fallback to empty data instead of hardcoded data
      setJaspelData([]);
      setSummary({
        total_paid: 0,
        total_pending: 0,
        total_rejected: 0,
        count_paid: 0,
        count_pending: 0,
        count_rejected: 0
      });
    } finally {
      setIsLoading(false);
    }
  };

  // Initial load
  useEffect(() => {
    fetchJaspelData(currentMonth, currentYear);
  }, [currentMonth, currentYear]);

  // Refresh data handler
  const handleRefresh = () => {
    fetchJaspelData(currentMonth, currentYear);
  };

  // Use summary data from API instead of calculating from array
  const totalPending = summary.total_pending;
  const totalPaid = summary.total_paid;

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/50 dark:to-yellow-800/50 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700';
      case 'paid': return 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
      case 'rejected': return 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  };

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

  // Debug info
  console.log('🎯 [JASPEL DEBUG] Render state:', {
    isLoading,
    error,
    jaspelDataLength: jaspelData.length,
    summary,
    totalPaid,
    totalPending
  });

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6 theme-transition"
    >
      {/* Header */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 dark:from-purple-600 dark:to-orange-700 border-0 shadow-xl card-enhanced relative overflow-hidden">
          <div className="absolute inset-0 bg-gradient-to-r from-yellow-400/10 via-transparent to-purple-600/10 animate-pulse"></div>
          <CardContent className="p-6 relative z-10">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center relative">
                  <Trophy className="w-6 h-6" />
                  <Sparkles className="w-3 h-3 text-yellow-300 absolute -top-1 -right-1 animate-pulse" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile flex items-center gap-2">
                    <Crown className="w-5 h-5 text-yellow-400" />
                    REWARD
                    <Medal className="w-5 h-5 text-purple-300" />
                  </h2>
                  <p className="text-purple-100 dark:text-purple-200 text-sm font-medium text-mobile-friendly flex items-center gap-1">
                    <Gamepad2 className="w-4 h-4 text-pink-300" />
                    Achievement System
                    <Zap className="w-4 h-4 text-yellow-300 animate-pulse" />
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <motion.div
                  whileHover={{ scale: 1.1 }}
                  whileTap={{ scale: 0.9 }}
                >
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={handleRefresh}
                    disabled={isLoading}
                    className="bg-white/10 border-white/20 text-white hover:bg-white/20"
                  >
                    <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
                  </Button>
                </motion.div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Error State */}
      {error && (
        <motion.div variants={item}>
          <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-red-800 dark:text-red-200">
                <AlertCircle className="w-5 h-5" />
                <div>
                  <p className="font-medium">Gagal memuat data</p>
                  <p className="text-sm">{error}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Loading State - WORLD-CLASS UX */}
      {isLoading && (
        <motion.div variants={item}>
          <Card className="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-center gap-4 text-blue-800 dark:text-blue-200">
                <div className="relative">
                  <RefreshCw className="w-6 h-6 animate-spin" />
                  <div className="absolute inset-0 w-6 h-6 border-2 border-blue-300 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <div>
                  <p className="font-semibold text-lg">Memuat data Jaspel...</p>
                  <p className="text-sm text-blue-600 dark:text-blue-300">Mengambil data terbaru dari sistem WORLD-CLASS</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Clean Empty State - Only Orange Card */}
      {!isLoading && !error && jaspelData.length === 0 && (
        <motion.div 
          variants={item}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.6 }}
          className="space-y-6"
        >
          {/* Gaming Empty State Card */}
          <motion.div
            className="relative overflow-hidden rounded-xl bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 p-6 shadow-lg"
            whileHover={{ 
              scale: 1.01,
              boxShadow: "0 8px 25px -8px rgba(168, 85, 247, 0.4)"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-yellow-400/10 via-transparent to-purple-600/10 animate-pulse"></div>
            <div className="flex items-center gap-4 relative z-10">
              {/* Gaming Icon */}
              <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 relative">
                <Gamepad2 className="w-5 h-5 text-white" />
                <Sparkles className="w-3 h-3 text-yellow-300 absolute -top-1 -right-1 animate-pulse" />
              </div>
              
              {/* Gaming Content */}
              <div className="flex-1">
                <h4 className="text-white font-semibold text-base mb-2 flex items-center gap-2">
                  <Trophy className="w-4 h-4 text-yellow-400" />
                  No Rewards Yet!
                </h4>
                <p className="text-purple-100 text-sm leading-relaxed flex items-center gap-1">
                  <Gift className="w-3 h-3 text-pink-300" />
                  Complete quests to unlock rewards
                </p>
              </div>
            </div>
          </motion.div>
        </motion.div>
      )}

      {/* Summary Cards - Only show if we have data */}
      {!isLoading && !error && (
        <motion.div variants={item} className="grid grid-cols-2 gap-4">
        <motion.div
          whileHover={{ scale: 1.02, y: -2 }}
          transition={{ duration: 0.2 }}
        >
          <Card className="bg-gradient-to-br from-yellow-500 to-orange-500 dark:from-yellow-600 dark:to-orange-700 border-0 shadow-lg card-enhanced relative overflow-hidden">
            <div className="absolute top-1 right-1">
              <Gem className="w-3 h-3 text-yellow-200 animate-pulse" />
            </div>
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-white">
                <div className="w-10 h-10 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center relative">
                  <Trophy className="w-5 h-5" />
                  <Sparkles className="w-2 h-2 text-yellow-200 absolute -top-0.5 -right-0.5 animate-pulse" />
                </div>
                <div>
                  <p className="text-sm text-yellow-100 dark:text-yellow-200 font-medium flex items-center gap-1">
                    <Coins className="w-3 h-3" />
                    Gold Earned
                  </p>
                  <p className="text-lg font-semibold">{formatCurrency(totalPaid)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>

        <motion.div
          whileHover={{ scale: 1.02, y: -2 }}
          transition={{ duration: 0.2 }}
        >
          <Card className="bg-gradient-to-br from-purple-500 to-pink-500 dark:from-purple-600 dark:to-pink-700 border-0 shadow-lg card-enhanced relative overflow-hidden">
            <div className="absolute top-1 right-1">
              <Crown className="w-3 h-3 text-purple-200 animate-bounce" />
            </div>
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-white">
                <div className="w-10 h-10 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center relative">
                  <Gift className="w-5 h-5" />
                  <Medal className="w-2 h-2 text-pink-200 absolute -top-0.5 -right-0.5 animate-pulse" />
                </div>
                <div>
                  <p className="text-sm text-purple-100 dark:text-purple-200 font-medium flex items-center gap-1">
                    <Award className="w-3 h-3" />
                    Quest Pending
                  </p>
                  <p className="text-lg font-semibold">{formatCurrency(totalPending)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
        </motion.div>
      )}

      {/* Tabs - Only show if we have data */}
      {!isLoading && !error && jaspelData.length > 0 && (
        <motion.div variants={item}>
        <Tabs defaultValue="semua" className="w-full">
          <TabsList className="grid w-full grid-cols-3 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700">
            <TabsTrigger value="semua" className="data-[state=active]:bg-white dark:data-[state=active]:bg-gray-900 data-[state=active]:shadow-sm font-medium transition-colors duration-300">
              🎮 All Rewards
            </TabsTrigger>
            <TabsTrigger value="pending" className="data-[state=active]:bg-white dark:data-[state=active]:bg-gray-900 data-[state=active]:shadow-sm font-medium transition-colors duration-300">
              ⏳ Pending XP
            </TabsTrigger>
            <TabsTrigger value="paid" className="data-[state=active]:bg-white dark:data-[state=active]:bg-gray-900 data-[state=active]:shadow-sm font-medium transition-colors duration-300">
              🏆 Claimed
            </TabsTrigger>
          </TabsList>

          <TabsContent value="semua" className="space-y-4 mt-4">
            <motion.div variants={container} className="space-y-3">
              {jaspelData.map((dataItem, index) => (
                <motion.div
                  key={dataItem.id}
                  variants={item}
                  whileHover={{ scale: 1.01, y: -1 }}
                  whileTap={{ scale: 0.99 }}
                  transition={{ duration: 0.2 }}
                >
                  <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                    <CardContent className="p-4">
                      <div className="flex justify-between items-start mb-3">
                        <div>
                          <h4 className="text-base font-semibold text-high-contrast">{dataItem.jenis}</h4>
                          <p className="text-sm text-muted-foreground font-medium">{dataItem.keterangan}</p>
                        </div>
                        <div className="flex flex-col items-end gap-1">
                          <Badge className={`${getStatusColor(dataItem.status)} border font-medium`}>
                            {dataItem.status === 'pending' && '⏳ Quest Active'}
                            {dataItem.status === 'paid' && '🏆 Reward Claimed'}
                            {dataItem.status === 'rejected' && '❌ Mission Failed'}
                          </Badge>
                          {dataItem.validated_by && (
                            <span className="text-xs text-muted-foreground">
                              oleh {dataItem.validated_by}
                            </span>
                          )}
                        </div>
                      </div>
                      
                      <div className="flex justify-between items-center">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                            <Calendar className="w-3 h-3 text-blue-600 dark:text-blue-400" />
                          </div>
                          <span className="text-sm text-muted-foreground font-medium">{formatDate(dataItem.tanggal)}</span>
                        </div>
                        <div className="flex items-center gap-3">
                          <span className="text-lg font-semibold text-high-contrast">{formatCurrency(dataItem.jumlah)}</span>
                          <motion.div
                            whileHover={{ scale: 1.1 }}
                            whileTap={{ scale: 0.9 }}
                          >
                            <Button variant="outline" size="sm" className="w-8 h-8 p-0 rounded-full hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors duration-300">
                              <Eye className="w-4 h-4" />
                            </Button>
                          </motion.div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </motion.div>
              ))}
            </motion.div>
          </TabsContent>

          <TabsContent value="pending" className="space-y-4 mt-4">
            <motion.div variants={container} className="space-y-3">
              {jaspelData
                .filter(dataItem => dataItem.status === 'pending')
                .map((dataItem, index) => (
                  <motion.div
                    key={dataItem.id}
                    variants={item}
                    whileHover={{ scale: 1.01, y: -1 }}
                    transition={{ duration: 0.2 }}
                  >
                    <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                      <CardContent className="p-4">
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h4 className="text-base font-semibold text-high-contrast">{dataItem.jenis}</h4>
                            <p className="text-sm text-muted-foreground font-medium">{dataItem.keterangan}</p>
                          </div>
                          <Badge className={`${getStatusColor(dataItem.status)} font-medium`}>Menunggu</Badge>
                        </div>
                        
                        <div className="flex justify-between items-center">
                          <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-muted-foreground" />
                            <span className="text-sm font-medium">{formatDate(dataItem.tanggal)}</span>
                          </div>
                          <span className="text-lg font-semibold text-high-contrast">{formatCurrency(dataItem.jumlah)}</span>
                        </div>
                      </CardContent>
                    </Card>
                  </motion.div>
                ))}
            </motion.div>
          </TabsContent>

          <TabsContent value="paid" className="space-y-4 mt-4">
            <motion.div variants={container} className="space-y-3">
              {jaspelData
                .filter(dataItem => dataItem.status === 'paid')
                .map((dataItem, index) => (
                  <motion.div
                    key={dataItem.id}
                    variants={item}
                    whileHover={{ scale: 1.01, y: -1 }}
                    transition={{ duration: 0.2 }}
                  >
                    <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                      <CardContent className="p-4">
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h4 className="text-base font-semibold text-high-contrast">{dataItem.jenis}</h4>
                            <p className="text-sm text-muted-foreground font-medium">{dataItem.keterangan}</p>
                          </div>
                          <Badge className={`${getStatusColor(dataItem.status)} font-medium`}>Tervalidasi</Badge>
                        </div>
                        
                        <div className="flex justify-between items-center">
                          <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-muted-foreground" />
                            <span className="text-sm font-medium">{formatDate(dataItem.tanggal)}</span>
                          </div>
                          <span className="text-lg font-semibold text-high-contrast">{formatCurrency(dataItem.jumlah)}</span>
                        </div>
                      </CardContent>
                    </Card>
                  </motion.div>
                ))}
            </motion.div>
          </TabsContent>
        </Tabs>
        </motion.div>
      )}
    </motion.div>
  );
}