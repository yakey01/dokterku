import React, { useState, useEffect, createContext, useContext, Suspense } from 'react';
import { createRoot } from 'react-dom/client';

// ============================================
// ERROR BOUNDARY COMPONENT
// ============================================

interface ErrorBoundaryState {
  hasError: boolean;
  error?: Error;
}

class ComponentErrorBoundary extends React.Component<
  { children: React.ReactNode; fallback?: React.ReactNode },
  ErrorBoundaryState
> {
  constructor(props: any) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.warn('Component error caught:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback || (
        <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
          <h3 className="font-semibold text-yellow-800 dark:text-yellow-200">⚠️ Component Error</h3>
          <p className="text-sm text-yellow-600 dark:text-yellow-400 mt-1">
            This component failed to load. Continuing with basic functionality.
          </p>
        </div>
      );
    }
    return this.props.children;
  }
}

// ============================================
// LAZY LOADED COMPONENTS WITH FALLBACKS
// ============================================

// Lazy load AnalyticsColumn with fallback
const AnalyticsColumnLazy = React.lazy(async () => {
  try {
    const module = await import('./components/manajer/AnalyticsCharts');
    return { default: module.AnalyticsColumn };
  } catch (error) {
    console.warn('Failed to load AnalyticsColumn:', error);
    // Return fallback component
    return {
      default: ({ analyticsData, isLoading, onRefresh, onTimeRangeChange }: any) => (
        <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700">
          <h3 className="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
            📊 Analytics (Basic View)
          </h3>
          <div className="space-y-4">
            <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
              <p className="text-blue-800 dark:text-blue-200">📈 Advanced charts loading...</p>
              <p className="text-sm text-blue-600 dark:text-blue-400 mt-2">
                Basic analytics available. Chart.js features will load when ready.
              </p>
            </div>
            <div className="flex gap-2">
              <button 
                onClick={onRefresh}
                className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
              >
                🔄 Refresh Data
              </button>
              <select 
                onChange={(e) => onTimeRangeChange(e.target.value)}
                className="px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-lg"
              >
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
              </select>
            </div>
          </div>
        </div>
      )
    };
  }
});

// Lazy load NotificationBell with fallback
const NotificationBellLazy = React.lazy(async () => {
  try {
    const module = await import('./components/manajer/NotificationCenter');
    return { default: module.NotificationBell };
  } catch (error) {
    console.warn('Failed to load NotificationBell:', error);
    // Return simple fallback
    return {
      default: () => (
        <div className="relative">
          <button className="p-2 text-neutral-600 dark:text-neutral-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-3.403-3.403A8.75 8.75 0 1113.75 2.25V6M16 12a4 4 0 01-8 0" />
            </svg>
            <span className="absolute -top-1 -right-1 h-3 w-3 bg-blue-500 rounded-full"></span>
          </button>
        </div>
      )
    };
  }
});

// ============================================
// TYPE DEFINITIONS
// ============================================

interface DashboardData {
  financial: {
    today_revenue: number;
    today_expenses: number;
    today_profit: number;
    monthly_revenue: number;
    revenue_change_percent: number;
  };
  patients: {
    today_total: number;
    today_umum: number;
    today_bpjs: number;
    avg_revenue_per_patient: number;
  };
  jaspel: {
    avg_doctor_jaspel_today: number;
    total_jaspel_today: number;
  };
  staff: {
    doctors_on_duty: DoctorOnDuty[];
    total_doctors_today: number;
    total_uang_duduk_today: number;
  };
  updated_at: string;
}

interface DoctorOnDuty {
  id: number;
  nama: string;
  shift: string;
  uang_duduk: number;
  status: string;
}

interface AnalyticsData {
  financial_trend: Array<{
    date: string;
    revenue: number;
    expenses: number;
    profit: number;
  }>;
  patient_trend: Array<{
    date: string;
    umum: number;
    bpjs: number;
    total: number;
  }>;
  expense_breakdown: Array<{
    category: string;
    amount: number;
  }>;
}

interface ValidationInsights {
  validation_summary: {
    jaspel: { pending: number; approved: number; rejected: number; };
    tindakan: { pending: number; approved: number; rejected: number; };
  };
  deviations: Array<{
    date: string;
    amount: number;
    deviation_percent: number;
    type: 'spike' | 'dip';
  }>;
  insights: Array<{
    type: 'warning' | 'info' | 'success';
    title: string;
    message: string;
    action: string;
    priority: 'high' | 'medium' | 'low';
  }>;
}

// ============================================
// THEME CONTEXT
// ============================================

const ThemeContext = createContext<{
  isDark: boolean;
  toggleTheme: () => void;
}>({
  isDark: false,
  toggleTheme: () => {},
});

const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isDark, setIsDark] = useState(() => {
    return document.documentElement.classList.contains('dark') ||
           window.matchMedia('(prefers-color-scheme: dark)').matches;
  });

  const toggleTheme = () => {
    setIsDark(!isDark);
    document.documentElement.classList.toggle('dark', !isDark);
  };

  useEffect(() => {
    document.documentElement.classList.toggle('dark', isDark);
  }, [isDark]);

  return (
    <ThemeContext.Provider value={{ isDark, toggleTheme }}>
      {children}
    </ThemeContext.Provider>
  );
};

// ============================================
// TOPBAR HEADER COMPONENT
// ============================================

const TopbarHeader: React.FC<{
  onRefresh: () => void;
  isLoading: boolean;
}> = ({ onRefresh, isLoading }) => {
  const { isDark, toggleTheme } = useContext(ThemeContext);
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  return (
    <header className="sticky top-0 z-50 backdrop-blur-xl bg-white/80 dark:bg-neutral-900/80 border-b border-neutral-200/50 dark:border-neutral-700/50 transition-all duration-300">
      <div className="px-6 py-4 flex items-center justify-between">
        {/* Left: Logo + Date */}
        <div className="flex items-center space-x-6">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div>
              <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                🏢 Executive Manager
              </h1>
              <p className="text-sm text-neutral-500 dark:text-neutral-400">
                Healthcare Management Suite - Full Featured
              </p>
            </div>
          </div>

          {/* Current Date Display */}
          <div className="hidden md:flex items-center space-x-2 px-4 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-lg">
            <svg className="w-4 h-4 text-neutral-600 dark:text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
              📅 {currentTime.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
              })}
            </span>
          </div>
        </div>

        {/* Right: Actions + Profile */}
        <div className="flex items-center space-x-4">
          {/* Refresh Button */}
          <button
            onClick={onRefresh}
            disabled={isLoading}
            className="flex items-center space-x-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-all duration-200 disabled:opacity-50"
          >
            <svg 
              className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span className="text-sm font-medium">🔄 Reload</span>
          </button>

          {/* Notification Bell with Error Boundary */}
          <ComponentErrorBoundary>
            <Suspense fallback={
              <div className="w-5 h-5 bg-neutral-200 dark:bg-neutral-700 rounded animate-pulse"></div>
            }>
              <NotificationBellLazy />
            </Suspense>
          </ComponentErrorBoundary>

          {/* Theme Toggle */}
          <button
            onClick={toggleTheme}
            className="p-2 text-neutral-600 dark:text-neutral-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
          >
            {isDark ? (
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            ) : (
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            )}
          </button>

          {/* Manager Profile */}
          <div className="flex items-center space-x-3 px-4 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-lg">
            <div className="w-8 h-8 bg-gradient-to-br from-green-400 to-green-500 rounded-full flex items-center justify-center">
              <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div className="hidden sm:block">
              <p className="text-sm font-medium text-neutral-900 dark:text-white">👤 Manager</p>
              <p className="text-xs text-neutral-500 dark:text-neutral-400">Executive Access</p>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};

// ============================================
// SUMMARY CARDS COMPONENT
// ============================================

const MetricCard: React.FC<{
  title: string;
  value: string;
  change?: number;
  icon: React.ReactNode;
  color: 'blue' | 'green' | 'purple' | 'orange' | 'red';
  trend?: 'up' | 'down' | 'stable';
}> = ({ title, value, change, icon, color, trend }) => {
  const colorClasses = {
    blue: 'from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-700',
    green: 'from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-700',
    purple: 'from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-700',
    orange: 'from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 border-orange-200 dark:border-orange-700',
    red: 'from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-red-200 dark:border-red-700',
  };

  const iconColors = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
  };

  return (
    <div className={`bg-gradient-to-br ${colorClasses[color]} rounded-xl p-6 border backdrop-blur-sm hover:shadow-lg transition-all duration-300`}>
      <div className="flex items-center justify-between mb-4">
        <div className={`w-12 h-12 ${iconColors[color]} rounded-xl flex items-center justify-center shadow-lg`}>
          {icon}
        </div>
        {trend && (
          <div className={`flex items-center space-x-1 text-sm ${
            trend === 'up' ? 'text-green-600' : trend === 'down' ? 'text-red-600' : 'text-gray-500'
          }`}>
            {trend === 'up' ? '📈' : trend === 'down' ? '📉' : '➡️'}
          </div>
        )}
      </div>
      
      <div>
        <h3 className="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">
          {title}
        </h3>
        <p className="text-2xl font-bold text-neutral-900 dark:text-white mb-2">
          {value}
        </p>
        {change !== undefined && (
          <p className={`text-sm flex items-center ${
            change >= 0 ? 'text-green-600' : 'text-red-600'
          }`}>
            {change >= 0 ? '▲' : '▼'} {Math.abs(change)}% vs last month
          </p>
        )}
      </div>
    </div>
  );
};

const SummaryColumn: React.FC<{ data: DashboardData | null }> = ({ data }) => {
  if (!data) {
    return (
      <div className="space-y-6">
        {[1,2,3,4].map(i => (
          <div key={i} className="bg-white dark:bg-neutral-800 rounded-xl p-6 animate-pulse">
            <div className="h-4 bg-neutral-200 dark:bg-neutral-700 rounded w-3/4 mb-2"></div>
            <div className="h-8 bg-neutral-200 dark:bg-neutral-700 rounded w-1/2"></div>
          </div>
        ))}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <MetricCard
        title="💰 Total Pendapatan Hari Ini"
        value={`Rp ${new Intl.NumberFormat('id-ID').format(data.financial.today_revenue)}`}
        change={data.financial.revenue_change_percent}
        icon={<svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>}
        color="green"
        trend={data.financial.revenue_change_percent > 0 ? 'up' : 'down'}
      />

      <MetricCard
        title="💸 Total Pengeluaran Hari Ini"
        value={`Rp ${new Intl.NumberFormat('id-ID').format(data.financial.today_expenses)}`}
        icon={<svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>}
        color="red"
      />

      <MetricCard
        title="👥 Pasien Umum & BPJS"
        value={`${data.patients.today_total} Pasien`}
        icon={<svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>}
        color="purple"
      />

      <MetricCard
        title="💊 Rata-rata JASPEL Dokter"
        value={`Rp ${new Intl.NumberFormat('id-ID').format(data.jaspel.avg_doctor_jaspel_today)}`}
        icon={<svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
        color="blue"
      />

      {/* Doctors on Duty Card */}
      <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm">
        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
          👨‍⚕️ Dokter Bertugas Hari Ini
        </h3>
        <div className="space-y-3">
          {data.staff.doctors_on_duty.length > 0 ? (
            data.staff.doctors_on_duty.map((doctor) => (
              <div key={doctor.id} className="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-700 rounded-lg">
                <div>
                  <p className="font-medium text-neutral-900 dark:text-white">{doctor.nama}</p>
                  <p className="text-sm text-neutral-500 dark:text-neutral-400">Shift: {doctor.shift}</p>
                </div>
                <div className="text-right">
                  <p className="text-sm font-medium text-green-600">
                    Rp {new Intl.NumberFormat('id-ID').format(doctor.uang_duduk)}
                  </p>
                  <p className="text-xs text-neutral-500">Uang Duduk</p>
                </div>
              </div>
            ))
          ) : (
            <p className="text-center text-neutral-500 dark:text-neutral-400 py-4">
              Tidak ada dokter bertugas hari ini
            </p>
          )}
          <div className="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
            <div className="flex justify-between items-center">
              <span className="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Uang Duduk:</span>
              <span className="text-lg font-bold text-green-600">
                Rp {new Intl.NumberFormat('id-ID').format(data.staff.total_uang_duduk_today)}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// ============================================
// INSIGHTS COLUMN COMPONENT
// ============================================

const InsightsColumn: React.FC<{
  validationData: ValidationInsights | null;
  onExport: (format: string) => void;
  onFilterChange: (filters: any) => void;
}> = ({ validationData, onExport, onFilterChange }) => {
  const [filters, setFilters] = useState({
    dateRange: 'today',
    patientType: 'all',
    unit: 'all',
  });

  const handleFilterChange = (key: string, value: string) => {
    const newFilters = { ...filters, [key]: value };
    setFilters(newFilters);
    onFilterChange(newFilters);
  };

  if (!validationData) {
    return (
      <div className="space-y-6">
        <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 animate-pulse">
          <div className="h-4 bg-neutral-200 dark:bg-neutral-700 rounded w-1/2 mb-4"></div>
          <div className="space-y-3">
            {[1,2,3].map(i => (
              <div key={i} className="h-12 bg-neutral-200 dark:bg-neutral-700 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Filter Controls */}
      <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm">
        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
          🔍 Filter & Kontrol
        </h3>
        
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              📅 Rentang Tanggal
            </label>
            <select
              value={filters.dateRange}
              onChange={(e) => handleFilterChange('dateRange', e.target.value)}
              className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-lg text-sm"
            >
              <option value="today">Hari Ini</option>
              <option value="week">Minggu Ini</option>
              <option value="month">Bulan Ini</option>
              <option value="quarter">Kuartal Ini</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              👥 Jenis Pasien
            </label>
            <select
              value={filters.patientType}
              onChange={(e) => handleFilterChange('patientType', e.target.value)}
              className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-lg text-sm"
            >
              <option value="all">Semua Pasien</option>
              <option value="umum">Pasien Umum</option>
              <option value="bpjs">Pasien BPJS</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              🏥 Unit Pelayanan
            </label>
            <select
              value={filters.unit}
              onChange={(e) => handleFilterChange('unit', e.target.value)}
              className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-lg text-sm"
            >
              <option value="all">Semua Unit</option>
              <option value="umum">Poli Umum</option>
              <option value="gigi">Poli Gigi</option>
              <option value="laboratorium">Laboratorium</option>
            </select>
          </div>
        </div>
      </div>

      {/* Validation Status */}
      <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm">
        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
          ✅ Status Validasi
        </h3>
        
        <div className="space-y-4">
          <div className="grid grid-cols-3 gap-3">
            <div className="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
              <p className="text-xl font-bold text-yellow-600">{validationData.validation_summary.jaspel.pending}</p>
              <p className="text-xs text-neutral-500">⏳ JASPEL Pending</p>
            </div>
            <div className="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <p className="text-xl font-bold text-green-600">{validationData.validation_summary.jaspel.approved}</p>
              <p className="text-xs text-neutral-500">✅ JASPEL Approved</p>
            </div>
            <div className="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
              <p className="text-xl font-bold text-red-600">{validationData.validation_summary.jaspel.rejected}</p>
              <p className="text-xs text-neutral-500">❌ JASPEL Rejected</p>
            </div>
          </div>
        </div>
      </div>

      {/* Insights & Alerts */}
      <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm">
        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
          🧠 Insight & Analisis
        </h3>
        
        <div className="space-y-3">
          {validationData.insights.length > 0 ? (
            validationData.insights.map((insight, index) => (
              <div key={index} className={`p-4 rounded-lg border-l-4 ${
                insight.type === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400' :
                insight.type === 'info' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-400' :
                'bg-green-50 dark:bg-green-900/20 border-green-400'
              }`}>
                <h4 className="font-medium text-neutral-900 dark:text-white">{insight.title}</h4>
                <p className="text-sm text-neutral-600 dark:text-neutral-400 mt-1">{insight.message}</p>
                <p className="text-xs text-neutral-500 dark:text-neutral-500 mt-2">
                  💡 {insight.action}
                </p>
              </div>
            ))
          ) : (
            <div className="text-center py-6">
              <p className="text-neutral-500 dark:text-neutral-400">✨ Tidak ada insight saat ini</p>
            </div>
          )}
        </div>
      </div>

      {/* Export Tools */}
      <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm">
        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
          📤 Ekspor Laporan
        </h3>
        
        <div className="grid grid-cols-2 gap-3">
          <button
            onClick={() => onExport('pdf')}
            className="flex items-center justify-center space-x-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-all duration-200"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span className="text-sm font-medium">PDF</span>
          </button>
          
          <button
            onClick={() => onExport('excel')}
            className="flex items-center justify-center space-x-2 px-4 py-3 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-all duration-200"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span className="text-sm font-medium">Excel</span>
          </button>
        </div>
      </div>
    </div>
  );
};

// ============================================
// MAIN DASHBOARD COMPONENT
// ============================================

const FullManagerDashboard: React.FC = () => {
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [analyticsData, setAnalyticsData] = useState<AnalyticsData | null>(null);
  const [validationData, setValidationData] = useState<ValidationInsights | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date>(new Date());

  // Mock data for demonstration
  const mockDashboardData: DashboardData = {
    financial: {
      today_revenue: 12500000,
      today_expenses: 7500000,
      today_profit: 5000000,
      monthly_revenue: 350000000,
      revenue_change_percent: 12.5
    },
    patients: {
      today_total: 156,
      today_umum: 89,
      today_bpjs: 67,
      avg_revenue_per_patient: 80128
    },
    jaspel: {
      avg_doctor_jaspel_today: 450000,
      total_jaspel_today: 3600000
    },
    staff: {
      doctors_on_duty: [
        { id: 1, nama: "Dr. Ahmad Hidayat", shift: "Pagi", uang_duduk: 200000, status: "active" },
        { id: 2, nama: "Dr. Siti Nurhaliza", shift: "Siang", uang_duduk: 200000, status: "active" },
        { id: 3, nama: "Dr. Budi Santoso", shift: "Malam", uang_duduk: 250000, status: "active" }
      ],
      total_doctors_today: 8,
      total_uang_duduk_today: 1650000
    },
    updated_at: new Date().toISOString()
  };

  const mockValidationData: ValidationInsights = {
    validation_summary: {
      jaspel: { pending: 12, approved: 45, rejected: 3 },
      tindakan: { pending: 8, approved: 67, rejected: 2 }
    },
    deviations: [],
    insights: [
      {
        type: 'info',
        title: 'Performa Harian Baik',
        message: 'Target revenue harian tercapai 125% dari target.',
        action: 'Pertahankan kualitas pelayanan',
        priority: 'medium'
      },
      {
        type: 'warning',
        title: 'Validasi JASPEL Pending',
        message: '12 entri JASPEL menunggu validasi bendahara.',
        action: 'Koordinasi dengan tim bendahara',
        priority: 'high'
      }
    ]
  };

  // Fetch dashboard data
  const fetchDashboardData = async () => {
    try {
      setIsLoading(true);
      
      // Use mock data for now - replace with real API calls later
      await new Promise(resolve => setTimeout(resolve, 1000)); // Simulate loading
      
      setDashboardData(mockDashboardData);
      setValidationData(mockValidationData);
      setLastUpdated(new Date());
      
      console.log('✅ Dashboard data loaded successfully');
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
      // Set mock data as fallback
      setDashboardData(mockDashboardData);
      setValidationData(mockValidationData);
    } finally {
      setIsLoading(false);
    }
  };

  // Refresh data manually
  const handleRefresh = async () => {
    console.log('🔄 Refreshing dashboard data...');
    await fetchDashboardData();
  };

  // Handle analytics period change
  const handleAnalyticsPeriodChange = async (period: string) => {
    console.log('📊 Changing analytics period to:', period);
    // This will be handled by the AnalyticsColumn component
  };

  // Handle export
  const handleExport = async (format: string) => {
    console.log('📤 Exporting data as:', format);
    // Simulate export
    alert(`Eksport ${format.toUpperCase()} akan dimulai...`);
  };

  // Handle filter changes
  const handleFilterChange = (newFilters: any) => {
    console.log('🔍 Filters changed:', newFilters);
    // Implement filter logic here
  };

  // Auto-refresh every 30 seconds
  useEffect(() => {
    fetchDashboardData();
    
    const interval = setInterval(fetchDashboardData, 30000);
    return () => clearInterval(interval);
  }, []);

  return (
    <ThemeProvider>
      <div className="min-h-screen bg-neutral-50 dark:bg-neutral-900 transition-colors duration-300">
        <TopbarHeader onRefresh={handleRefresh} isLoading={isLoading} />
        
        {/* Main Dashboard Grid */}
        <main className="p-6">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-8xl mx-auto">
            {/* Column 1: Summary (3/12) */}
            <div className="lg:col-span-3">
              <ComponentErrorBoundary>
                <SummaryColumn data={dashboardData} />
              </ComponentErrorBoundary>
            </div>

            {/* Column 2: Analytics (6/12) */}
            <div className="lg:col-span-6">
              <ComponentErrorBoundary>
                <Suspense fallback={
                  <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 animate-pulse">
                    <div className="h-4 bg-neutral-200 dark:bg-neutral-700 rounded w-1/3 mb-4"></div>
                    <div className="h-64 bg-neutral-200 dark:bg-neutral-700 rounded"></div>
                  </div>
                }>
                  <AnalyticsColumnLazy 
                    analyticsData={analyticsData}
                    isLoading={isLoading}
                    onRefresh={handleRefresh}
                    onTimeRangeChange={handleAnalyticsPeriodChange}
                  />
                </Suspense>
              </ComponentErrorBoundary>
            </div>

            {/* Column 3: Insights (3/12) */}
            <div className="lg:col-span-3">
              <ComponentErrorBoundary>
                <InsightsColumn 
                  validationData={validationData}
                  onExport={handleExport}
                  onFilterChange={handleFilterChange}
                />
              </ComponentErrorBoundary>
            </div>
          </div>
          
          {/* Last Updated Indicator */}
          <div className="mt-6 text-center">
            <p className="text-sm text-neutral-500 dark:text-neutral-400">
              🕐 Last updated: {lastUpdated.toLocaleTimeString('id-ID')} | 
              Next update in: <span className="font-medium">30s</span>
            </p>
          </div>
        </main>
      </div>
    </ThemeProvider>
  );
};

// ============================================
// APP INITIALIZATION - ROBUST MOUNTING
// ============================================

const initializeFullDashboard = () => {
  console.log('🔍 Initializing full-featured dashboard...');
  
  const container = document.getElementById('manajer-dashboard-root');
  const loading = document.getElementById('dashboard-loading');
  
  console.log('📍 Container found:', !!container);
  console.log('📍 Loading element found:', !!loading);
  
  if (container) {
    // Show dashboard container immediately
    container.classList.remove('hidden');
    container.style.display = 'block';
    container.style.minHeight = '100vh';
    
    // Hide loading state
    if (loading) {
      loading.style.display = 'none';
    }
    
    // Mount React app
    try {
      const root = createRoot(container);
      root.render(<FullManagerDashboard />);
      console.log('✅ Full Manager Dashboard mounted successfully');
    } catch (error) {
      console.error('❌ React mounting error:', error);
      container.innerHTML = `
        <div class="flex items-center justify-center min-h-screen bg-red-50">
          <div class="text-center p-8">
            <h1 class="text-2xl font-bold text-red-600 mb-4">React Mounting Error</h1>
            <p class="text-red-500">${error.message}</p>
            <button onclick="window.location.reload()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
              🔄 Reload Page
            </button>
          </div>
        </div>
      `;
    }
  } else {
    console.error('❌ Container #manajer-dashboard-root not found');
    console.log('📝 Available elements:', document.querySelectorAll('[id*="dashboard"], [id*="manajer"]'));
  }
};

// Multiple initialization strategies
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeFullDashboard);
} else {
  // DOM already loaded
  initializeFullDashboard();
}

// Fallback after 1 second
setTimeout(() => {
  const container = document.getElementById('manajer-dashboard-root');
  if (container && !container.children.length) {
    console.log('🔄 Fallback initialization...');
    initializeFullDashboard();
  }
}, 1000);

export default FullManagerDashboard;