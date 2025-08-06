import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Clock, 
  CheckCircle, 
  RefreshCw,
  Sparkles,
  CreditCard,
  Wallet
} from 'lucide-react';

interface DashboardData {
  jaspel_monthly: number;
  pending_jaspel: number;
  approved_jaspel: number;
  growth_percent: number;
  paramedis_name: string;
  last_month_total: number;
  daily_average?: number;
  jaspel_weekly?: number;
  attendance_rate?: number;
  shifts_this_month?: number;
  period_info?: {
    month_progress: number;
    days_passed: number;
    days_in_month: number;
    current_month: number;
    current_year: number;
  };
}

export function JaspelDashboardCard() {
  const [data, setData] = useState<DashboardData>({
    jaspel_monthly: 0,
    pending_jaspel: 0,
    approved_jaspel: 0,
    growth_percent: 0,
    paramedis_name: 'Loading...',
    last_month_total: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  const fetchData = async () => {
    try {
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      
      // Try test endpoint first
      let response = await fetch('/test-paramedis-dashboard-api', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        }
      });

      // If test endpoint fails, try API endpoint
      if (!response.ok) {
        console.log('Test endpoint failed, trying API endpoint...');
        response = await fetch('/api/new-paramedis/dashboard', {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`
          }
        });
      }

      if (response.ok) {
        const result = await response.json();
        console.log('🌟 Dashboard Data:', result);
        setData(result);
        setLastUpdated(new Date());
      } else {
        console.warn('Failed to fetch dashboard data:', response.status);
      }
    } catch (error) {
      console.error('❌ Failed to fetch dashboard data:', error);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  const handleRefresh = async () => {
    setIsRefreshing(true);
    await fetchData();
  };

  useEffect(() => {
    fetchData();
    // Auto refresh every 30 seconds
    const interval = setInterval(fetchData, 30000);
    return () => clearInterval(interval);
  }, []);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const getGrowthColor = (percent: number) => {
    if (percent > 0) return 'text-emerald-500';
    if (percent < 0) return 'text-red-500';
    return 'text-gray-500';
  };

  const getGrowthIcon = (percent: number) => {
    if (percent > 0) return <TrendingUp className="w-4 h-4" />;
    if (percent < 0) return <TrendingDown className="w-4 h-4" />;
    return null;
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        {/* Loading Card */}
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-purple-600 to-blue-800 p-8 shadow-2xl">
          <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
          <div className="relative z-10">
            <div className="flex items-center justify-center space-x-4">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
              <span className="text-white font-semibold text-lg">Loading Dashboard...</span>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Main Jaspel Card - WORLD CLASS DESIGN */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6 }}
        className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-8 shadow-2xl"
      >
        {/* Animated Background Pattern */}
        <div className="absolute inset-0 opacity-20">
          <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
          <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
          <div className="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full -ml-12 -mb-12"></div>
        </div>

        <div className="relative z-10">
          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center space-x-3">
              <div className="flex items-center justify-center w-12 h-12 bg-white/20 rounded-xl backdrop-blur-sm">
                <Wallet className="w-6 h-6 text-white" />
              </div>
              <div>
                <h2 className="text-white font-bold text-xl">Jaspel Dashboard</h2>
                <p className="text-white/80 text-sm">Pendapatan layanan medis</p>
              </div>
            </div>
            
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={handleRefresh}
              disabled={isRefreshing}
              className="flex items-center justify-center w-10 h-10 bg-white/20 rounded-lg backdrop-blur-sm hover:bg-white/30 transition-colors"
            >
              <RefreshCw className={`w-5 h-5 text-white ${isRefreshing ? 'animate-spin' : ''}`} />
            </motion.button>
          </div>

          {/* Main Amount */}
          <div className="mb-6">
            <div className="flex items-baseline space-x-2 mb-2">
              <span className="text-white/70 text-sm font-medium">
                Jaspel {data.period_info ? 
                  new Date(data.period_info.current_year, data.period_info.current_month - 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : 
                  'Bulan Ini'
                }
              </span>
              <Sparkles className="w-4 h-4 text-yellow-300" />
            </div>
            <div className="text-4xl font-bold text-white mb-2">
              {formatCurrency(data.jaspel_monthly)}
            </div>
            
            {/* Growth Indicator */}
            <div className={`flex items-center space-x-2 ${getGrowthColor(data.growth_percent)}`}>
              {getGrowthIcon(data.growth_percent)}
              <span className="text-white font-semibold">
                {data.growth_percent > 0 ? '+' : ''}{data.growth_percent}%
              </span>
              <span className="text-white/70 text-sm">vs bulan lalu</span>
            </div>
          </div>

          {/* Enhanced Breakdown Cards */}
          <div className="grid grid-cols-2 gap-4 mb-4">
            {/* Pending Card */}
            <motion.div
              whileHover={{ scale: 1.02 }}
              className="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20"
            >
              <div className="flex items-center space-x-3 mb-2">
                <div className="flex items-center justify-center w-8 h-8 bg-orange-500/20 rounded-lg">
                  <Clock className="w-4 h-4 text-orange-300" />
                </div>
                <span className="text-white/80 text-sm font-medium">Pending</span>
              </div>
              <div className="text-white font-bold text-lg">
                {formatCurrency(data.pending_jaspel)}
              </div>
            </motion.div>

            {/* Approved Card */}
            <motion.div
              whileHover={{ scale: 1.02 }}
              className="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20"
            >
              <div className="flex items-center space-x-3 mb-2">
                <div className="flex items-center justify-center w-8 h-8 bg-emerald-500/20 rounded-lg">
                  <CheckCircle className="w-4 h-4 text-emerald-300" />
                </div>
                <span className="text-white/80 text-sm font-medium">Disetujui</span>
              </div>
              <div className="text-white font-bold text-lg">
                {formatCurrency(data.approved_jaspel)}
              </div>
            </motion.div>
          </div>

          {/* Progress Indicator */}
          {data.period_info && (
            <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 mb-4">
              <div className="flex justify-between items-center mb-2">
                <span className="text-white/80 text-sm font-medium">Progress Bulan Ini</span>
                <span className="text-white font-bold text-sm">{data.period_info.month_progress}%</span>
              </div>
              <div className="w-full bg-white/20 rounded-full h-2">
                <div 
                  className="bg-gradient-to-r from-yellow-400 to-orange-400 h-2 rounded-full transition-all duration-500"
                  style={{ width: `${data.period_info.month_progress}%` }}
                ></div>
              </div>
              <div className="flex justify-between text-xs text-white/70 mt-1">
                <span>Hari ke-{data.period_info.days_passed}</span>
                <span>{data.period_info.days_in_month} hari total</span>
              </div>
            </div>
          )}

          {/* Additional Stats Grid */}
          <div className="grid grid-cols-2 gap-3">
            <div className="bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
              <div className="text-white/70 text-xs font-medium">Harian</div>
              <div className="text-white font-bold text-sm">
                {formatCurrency(data.daily_average || 0)}
              </div>
            </div>
            <div className="bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
              <div className="text-white/70 text-xs font-medium">Mingguan</div>
              <div className="text-white font-bold text-sm">
                {formatCurrency(data.jaspel_weekly || 0)}
              </div>
            </div>
          </div>

          {/* User Info with Last Updated */}
          <div className="mt-6 pt-4 border-t border-white/20">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <CreditCard className="w-4 h-4 text-white/70" />
                <span className="text-white/70 text-sm">
                  Data untuk: <span className="text-white font-medium">{data.paramedis_name}</span>
                </span>
              </div>
              {lastUpdated && (
                <div className="text-white/60 text-xs">
                  Update: {lastUpdated.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                  })}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Floating Elements */}
        <div className="absolute top-4 right-4">
          <div className="w-2 h-2 bg-white/30 rounded-full animate-pulse"></div>
        </div>
        <div className="absolute bottom-4 left-4">
          <div className="w-1 h-1 bg-white/40 rounded-full animate-pulse" style={{ animationDelay: '1s' }}></div>
        </div>
      </motion.div>

      {/* Quick Stats Row */}
      <div className="grid grid-cols-2 gap-4">
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5, delay: 0.2 }}
          className="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl p-4 shadow-lg"
        >
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
              <TrendingUp className="w-5 h-5 text-white" />
            </div>
            <div>
              <div className="text-white/80 text-xs font-medium">Growth Rate</div>
              <div className="text-white font-bold text-lg">+{data.growth_percent}%</div>
            </div>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5, delay: 0.3 }}
          className="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-4 shadow-lg"
        >
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
              <DollarSign className="w-5 h-5 text-white" />
            </div>
            <div>
              <div className="text-white/80 text-xs font-medium">Bulan Lalu</div>
              <div className="text-white font-bold text-lg">
                {formatCurrency(data.last_month_total)}
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
}