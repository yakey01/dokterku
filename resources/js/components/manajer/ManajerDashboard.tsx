import React, { useState, useEffect } from 'react';
import { 
  Bell, 
  Calendar, 
  RefreshCw, 
  User, 
  LogOut, 
  TrendingUp, 
  TrendingDown, 
  Users, 
  DollarSign, 
  UserCheck,
  Activity,
  BarChart3,
  PieChart,
  Download,
  Filter,
  AlertTriangle,
  CheckCircle,
  Clock,
  Eye,
  Calculator,
  Zap,
  Home,
  Wallet,
  CreditCard,
  Settings,
  Search,
  ArrowUpDown,
  FileText,
  Star,
  Edit,
  Shield,
  MapPin,
  Award
} from 'lucide-react';

const ManagerDashboard = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [notifications, setNotifications] = useState(5);
  const [selectedDateRange, setSelectedDateRange] = useState('today');
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');

  // Simulate real-time data updates
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const handleRefresh = () => {
    setIsRefreshing(true);
    setTimeout(() => setIsRefreshing(false), 2000);
  };

  // Mock data for dashboard
  const todayStats = {
    revenue: 45750000,
    expenses: 12340000,
    generalPatients: 156,
    bpjsPatients: 89,
    avgDoctorFee: 2500000,
    doctorsOnDuty: 8
  };

  const recentInputs = [
    { id: 1, staff: 'Dr. Ahmad Santoso', type: 'Pendapatan', amount: 2500000, status: 'validated', time: '08:30' },
    { id: 2, staff: 'Perawat Siti', type: 'Pengeluaran', amount: 450000, status: 'pending', time: '09:15' },
    { id: 3, staff: 'Admin Budi', type: 'Pasien BPJS', amount: 0, status: 'validated', time: '10:20' },
    { id: 4, staff: 'Dr. Lisa Putri', type: 'Jaspel', amount: 1800000, status: 'review', time: '11:45' }
  ];

  const financeData = {
    monthlyRevenue: 1275000000,
    monthlyExpenses: 425000000,
    profit: 850000000,
    transactions: [
      { id: 1, type: 'Pendapatan', category: 'Konsultasi', amount: 2500000, date: '2025-08-18', status: 'completed' },
      { id: 2, type: 'Pengeluaran', category: 'Obat-obatan', amount: -450000, date: '2025-08-18', status: 'completed' },
      { id: 3, type: 'Pendapatan', category: 'Rawat Jalan', amount: 1800000, date: '2025-08-17', status: 'pending' },
      { id: 4, type: 'Pengeluaran', category: 'Gaji Karyawan', amount: -15000000, date: '2025-08-17', status: 'completed' }
    ]
  };

  const attendanceData = {
    totalEmployees: 45,
    presentToday: 42,
    lateToday: 3,
    absentToday: 0,
    employees: [
      { id: 1, name: 'Dr. Ahmad Santoso', position: 'Dokter Umum', checkIn: '07:30', checkOut: '-', status: 'present', location: 'Ruang 1' },
      { id: 2, name: 'Perawat Siti Nurhaliza', position: 'Perawat', checkIn: '08:15', checkOut: '-', status: 'late', location: 'IGD' },
      { id: 3, name: 'Dr. Lisa Putri', position: 'Dokter Gigi', checkIn: '08:00', checkOut: '-', status: 'present', location: 'Ruang Gigi' },
      { id: 4, name: 'Admin Budi Santoso', position: 'Admin', checkIn: '07:50', checkOut: '-', status: 'present', location: 'Loket' }
    ]
  };

  const jaspelData = {
    totalJaspelMonth: 125000000,
    averagePerDoctor: 15625000,
    topPerformer: 'Dr. Ahmad Santoso',
    doctors: [
      { id: 1, name: 'Dr. Ahmad Santoso', patients: 45, jaspel: 22500000, bonus: 2000000, total: 24500000, rank: 1 },
      { id: 2, name: 'Dr. Lisa Putri', patients: 38, jaspel: 19000000, bonus: 1500000, total: 20500000, rank: 2 },
      { id: 3, name: 'Dr. Budi Prakoso', patients: 32, jaspel: 16000000, bonus: 1000000, total: 17000000, rank: 3 },
      { id: 4, name: 'Dr. Sari Indah', patients: 28, jaspel: 14000000, bonus: 800000, total: 14800000, rank: 4 }
    ]
  };

  const chartData = {
    revenue: [30, 45, 35, 50, 49, 60, 70, 91, 125, 140, 150, 170],
    expenses: [15, 25, 20, 30, 25, 35, 40, 45, 50, 55, 60, 65]
  };

  // Tab content renderer
  const renderTabContent = () => {
    switch(activeTab) {
      case 'dashboard':
        return <DashboardView />;
      case 'finance':
        return <FinanceView />;
      case 'attendance':
        return <AttendanceView />;
      case 'jaspel':
        return <JaspelView />;
      case 'profile':
        return <ProfileView />;
      default:
        return <DashboardView />;
    }
  };

  // Dashboard View Component
  const DashboardView = () => (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      {/* Column 1: Key Metrics */}
      <div className="space-y-4">
        <h2 className="text-xl font-bold text-slate-800 flex items-center">
          <Zap className="w-5 h-5 mr-2 text-yellow-500" />
          Ringkasan Hari Ini
        </h2>
        
        {/* Revenue Card */}
        <div className="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg transform hover:scale-105 transition-all duration-300">
          <div className="flex items-center justify-between mb-4">
            <div className="p-2 bg-white/20 rounded-xl">
              <TrendingUp className="w-6 h-6" />
            </div>
            <span className="text-sm opacity-80">+12.5%</span>
          </div>
          <h3 className="text-2xl font-bold mb-1">
            Rp {todayStats.revenue.toLocaleString('id-ID')}
          </h3>
          <p className="text-sm opacity-90">Total Pendapatan</p>
        </div>

        {/* Expenses Card */}
        <div className="bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl p-6 text-white shadow-lg transform hover:scale-105 transition-all duration-300">
          <div className="flex items-center justify-between mb-4">
            <div className="p-2 bg-white/20 rounded-xl">
              <TrendingDown className="w-6 h-6" />
            </div>
            <span className="text-sm opacity-80">+3.2%</span>
          </div>
          <h3 className="text-2xl font-bold mb-1">
            Rp {todayStats.expenses.toLocaleString('id-ID')}
          </h3>
          <p className="text-sm opacity-90">Total Pengeluaran</p>
        </div>

        {/* Patients Stats */}
        <div className="grid grid-cols-2 gap-3">
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200/50">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-blue-100 rounded-lg">
                <Users className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <p className="text-2xl font-bold text-slate-800">{todayStats.generalPatients}</p>
                <p className="text-xs text-slate-500">Pasien Umum</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200/50">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-green-100 rounded-lg">
                <UserCheck className="w-5 h-5 text-green-600" />
              </div>
              <div>
                <p className="text-2xl font-bold text-slate-800">{todayStats.bpjsPatients}</p>
                <p className="text-xs text-slate-500">Pasien BPJS</p>
              </div>
            </div>
          </div>
        </div>

        {/* Doctor Stats */}
        <div className="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl p-6 text-white shadow-lg">
          <div className="flex items-center justify-between mb-4">
            <div className="p-2 bg-white/20 rounded-xl">
              <DollarSign className="w-6 h-6" />
            </div>
            <Calculator className="w-5 h-5 opacity-80" />
          </div>
          <h3 className="text-xl font-bold mb-1">
            Rp {todayStats.avgDoctorFee.toLocaleString('id-ID')}
          </h3>
          <p className="text-sm opacity-90 mb-3">Rata-rata Jaspel Dokter</p>
          <div className="flex items-center text-sm">
            <UserCheck className="w-4 h-4 mr-1" />
            {todayStats.doctorsOnDuty} Dokter Bertugas
          </div>
        </div>
      </div>

      {/* Column 2: Analytics & Charts */}
      <div className="space-y-4">
        <h2 className="text-xl font-bold text-slate-800 flex items-center">
          <BarChart3 className="w-5 h-5 mr-2 text-blue-500" />
          Analitik & Grafik
        </h2>
        
        {/* Revenue vs Expenses Chart */}
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800 mb-4">Tren Pendapatan vs Pengeluaran</h3>
          <div className="h-48 bg-gradient-to-t from-slate-50 to-white rounded-xl flex items-end justify-between p-4">
            {chartData.revenue.map((value, index) => (
              <div key={index} className="flex flex-col items-center space-y-1">
                <div className="flex flex-col items-center space-y-1">
                  <div 
                    className="bg-gradient-to-t from-emerald-500 to-emerald-400 rounded-t-sm w-3 transition-all duration-500 hover:from-emerald-400 hover:to-emerald-300"
                    style={{ height: `${(value / 200) * 100}px` }}
                  />
                  <div 
                    className="bg-gradient-to-t from-orange-500 to-orange-400 rounded-t-sm w-3 transition-all duration-500 hover:from-orange-400 hover:to-orange-300"
                    style={{ height: `${(chartData.expenses[index] / 200) * 100}px` }}
                  />
                </div>
                <span className="text-xs text-slate-400">{index + 1}</span>
              </div>
            ))}
          </div>
          <div className="flex justify-center space-x-6 mt-4">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-emerald-500 rounded-full"></div>
              <span className="text-sm text-slate-600">Pendapatan</span>
            </div>
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-orange-500 rounded-full"></div>
              <span className="text-sm text-slate-600">Pengeluaran</span>
            </div>
          </div>
        </div>

        {/* Patient Distribution Donut */}
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800 mb-4 flex items-center">
            <PieChart className="w-5 h-5 mr-2" />
            Distribusi Pasien
          </h3>
          <div className="flex items-center justify-center">
            <div className="relative w-32 h-32">
              <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="35" fill="none" stroke="#e2e8f0" strokeWidth="8"/>
                <circle 
                  cx="50" cy="50" r="35" fill="none" 
                  stroke="#3b82f6" strokeWidth="8"
                  strokeDasharray={`${(todayStats.generalPatients / (todayStats.generalPatients + todayStats.bpjsPatients)) * 220} 220`}
                  className="transition-all duration-1000"
                />
                <circle 
                  cx="50" cy="50" r="35" fill="none" 
                  stroke="#10b981" strokeWidth="8"
                  strokeDasharray={`${(todayStats.bpjsPatients / (todayStats.generalPatients + todayStats.bpjsPatients)) * 220} 220`}
                  strokeDashoffset={`-${(todayStats.generalPatients / (todayStats.generalPatients + todayStats.bpjsPatients)) * 220}`}
                  className="transition-all duration-1000"
                />
              </svg>
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="text-center">
                  <p className="text-2xl font-bold text-slate-800">{todayStats.generalPatients + todayStats.bpjsPatients}</p>
                  <p className="text-xs text-slate-500">Total</p>
                </div>
              </div>
            </div>
          </div>
          <div className="flex justify-center space-x-6 mt-4">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
              <span className="text-sm text-slate-600">Umum ({todayStats.generalPatients})</span>
            </div>
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-emerald-500 rounded-full"></div>
              <span className="text-sm text-slate-600">BPJS ({todayStats.bpjsPatients})</span>
            </div>
          </div>
        </div>

        {/* Performance Insights */}
        <div className="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
          <h3 className="text-lg font-semibold mb-3 flex items-center">
            <Eye className="w-5 h-5 mr-2" />
            Insight Kinerja
          </h3>
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-sm opacity-90">Target Pendapatan</span>
              <span className="text-sm font-semibold">92%</span>
            </div>
            <div className="w-full bg-white/20 rounded-full h-2">
              <div className="bg-white rounded-full h-2 transition-all duration-1000" style={{ width: '92%' }}></div>
            </div>
            <p className="text-xs opacity-80 mt-2">Pencapaian sangat baik! Target bulan ini kemungkinan akan terlampaui.</p>
          </div>
        </div>
      </div>

      {/* Column 3: Tables & Insights */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-bold text-slate-800 flex items-center">
            <Activity className="w-5 h-5 mr-2 text-green-500" />
            Data & Validasi
          </h2>
          <div className="flex space-x-2">
            <button className="p-2 bg-white rounded-xl shadow-sm border border-slate-200/50 hover:bg-slate-50 transition-colors">
              <Filter className="w-4 h-4 text-slate-600" />
            </button>
            <button className="p-2 bg-white rounded-xl shadow-sm border border-slate-200/50 hover:bg-slate-50 transition-colors">
              <Download className="w-4 h-4 text-slate-600" />
            </button>
          </div>
        </div>

        {/* Recent Inputs Table */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden">
          <div className="p-4 bg-slate-50 border-b border-slate-200/50">
            <h3 className="font-semibold text-slate-800">Input Terbaru Petugas</h3>
          </div>
          <div className="divide-y divide-slate-200/50">
            {recentInputs.map((input) => (
              <div key={input.id} className="p-4 hover:bg-slate-50/50 transition-colors">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center">
                      <span className="text-xs font-bold text-white">{input.staff.charAt(0)}</span>
                    </div>
                    <div>
                      <p className="font-semibold text-slate-800 text-sm">{input.staff}</p>
                      <p className="text-xs text-slate-500">{input.type}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-slate-800 text-sm">
                      {input.amount > 0 ? `Rp ${input.amount.toLocaleString('id-ID')}` : '-'}
                    </p>
                    <p className="text-xs text-slate-500">{input.time}</p>
                  </div>
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    {input.status === 'validated' && (
                      <>
                        <CheckCircle className="w-4 h-4 text-green-500" />
                        <span className="text-xs text-green-600 font-medium">Tervalidasi</span>
                      </>
                    )}
                    {input.status === 'pending' && (
                      <>
                        <Clock className="w-4 h-4 text-yellow-500" />
                        <span className="text-xs text-yellow-600 font-medium">Menunggu</span>
                      </>
                    )}
                    {input.status === 'review' && (
                      <>
                        <AlertTriangle className="w-4 h-4 text-orange-500" />
                        <span className="text-xs text-orange-600 font-medium">Review</span>
                      </>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="grid grid-cols-2 gap-3">
          <button className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-4 text-white shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
            <Calculator className="w-6 h-6 mb-2" />
            <p className="text-sm font-semibold">Kalkulasi Jaspel</p>
          </button>
          
          <button className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
            <BarChart3 className="w-6 h-6 mb-2" />
            <p className="text-sm font-semibold">Simulasi Skema</p>
          </button>
        </div>
      </div>
    </div>
  );

  // Finance View Component
  const FinanceView = () => {
    const [activeFinanceTab, setActiveFinanceTab] = useState('overview');

    const renderFinanceContent = () => {
      switch(activeFinanceTab) {
        case 'overview':
          return <FinanceOverview />;
        case 'monthly':
          return <MonthlyReport />;
        case 'budget':
          return <BudgetPlanning />;
        case 'analysis':
          return <CostAnalysis />;
        case 'export':
          return <ExportData />;
        default:
          return <FinanceOverview />;
      }
    };

    const FinanceOverview = () => (
      <div className="space-y-6">
        {/* Financial Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
            <div className="flex items-center justify-between mb-4">
              <TrendingUp className="w-8 h-8" />
              <span className="text-sm opacity-80">Bulan Ini</span>
            </div>
            <h3 className="text-2xl font-bold mb-1">
              Rp {financeData.monthlyRevenue.toLocaleString('id-ID')}
            </h3>
            <p className="text-sm opacity-90">Total Pendapatan</p>
          </div>

          <div className="bg-gradient-to-r from-red-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg">
            <div className="flex items-center justify-between mb-4">
              <TrendingDown className="w-8 h-8" />
              <span className="text-sm opacity-80">Bulan Ini</span>
            </div>
            <h3 className="text-2xl font-bold mb-1">
              Rp {financeData.monthlyExpenses.toLocaleString('id-ID')}
            </h3>
            <p className="text-sm opacity-90">Total Pengeluaran</p>
          </div>

          <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
            <div className="flex items-center justify-between mb-4">
              <DollarSign className="w-8 h-8" />
              <span className="text-sm opacity-80">Profit</span>
            </div>
            <h3 className="text-2xl font-bold mb-1">
              Rp {financeData.profit.toLocaleString('id-ID')}
            </h3>
            <p className="text-sm opacity-90">Keuntungan Bersih</p>
          </div>
        </div>

        {/* Recent Transactions */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden">
          <div className="p-6 bg-slate-50 border-b border-slate-200/50 flex items-center justify-between">
            <h3 className="text-lg font-semibold text-slate-800">Transaksi Terbaru</h3>
            <button className="text-blue-600 text-sm font-medium hover:text-blue-700">Lihat Semua</button>
          </div>
          <div className="divide-y divide-slate-200/50">
            {financeData.transactions.map((transaction) => (
              <div key={transaction.id} className="p-6 hover:bg-slate-50/50 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${
                      transaction.type === 'Pendapatan' 
                        ? 'bg-emerald-100 text-emerald-600' 
                        : 'bg-red-100 text-red-600'
                    }`}>
                      {transaction.type === 'Pendapatan' ? 
                        <TrendingUp className="w-6 h-6" /> : 
                        <TrendingDown className="w-6 h-6" />
                      }
                    </div>
                    <div>
                      <p className="font-semibold text-slate-800">{transaction.category}</p>
                      <p className="text-sm text-slate-500">{transaction.date}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className={`text-lg font-bold ${
                      transaction.amount > 0 ? 'text-emerald-600' : 'text-red-600'
                    }`}>
                      {transaction.amount > 0 ? '+' : ''}Rp {Math.abs(transaction.amount).toLocaleString('id-ID')}
                    </p>
                    <div className="flex items-center space-x-2">
                      {transaction.status === 'completed' ? (
                        <CheckCircle className="w-4 h-4 text-green-500" />
                      ) : (
                        <Clock className="w-4 h-4 text-yellow-500" />
                      )}
                      <span className={`text-xs font-medium ${
                        transaction.status === 'completed' ? 'text-green-600' : 'text-yellow-600'
                      }`}>
                        {transaction.status === 'completed' ? 'Selesai' : 'Pending'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );

    const MonthlyReport = () => {
      const monthlyData = [
        { month: 'Jan', revenue: 1200000000, expenses: 400000000, profit: 800000000 },
        { month: 'Feb', revenue: 1150000000, expenses: 380000000, profit: 770000000 },
        { month: 'Mar', revenue: 1300000000, expenses: 420000000, profit: 880000000 },
        { month: 'Apr', revenue: 1275000000, expenses: 425000000, profit: 850000000 },
        { month: 'Mei', revenue: 1400000000, expenses: 450000000, profit: 950000000 },
        { month: 'Jun', revenue: 1350000000, expenses: 440000000, profit: 910000000 }
      ];

      return (
        <div className="space-y-6">
          <div className="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
            <h3 className="text-xl font-bold mb-4">Laporan Keuangan Bulanan</h3>
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center">
                <p className="text-2xl font-bold">Rp 7.68M</p>
                <p className="text-sm opacity-80">Total Revenue (6 Bulan)</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold">Rp 2.51M</p>
                <p className="text-sm opacity-80">Total Expenses (6 Bulan)</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold">Rp 5.16M</p>
                <p className="text-sm opacity-80">Total Profit (6 Bulan)</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-4">Performa 6 Bulan Terakhir</h3>
            <div className="h-64 bg-gradient-to-t from-slate-50 to-white rounded-xl flex items-end justify-between p-4">
              {monthlyData.map((data, index) => (
                <div key={index} className="flex flex-col items-center space-y-2">
                  <div className="flex flex-col items-center space-y-1">
                    <div 
                      className="bg-gradient-to-t from-emerald-500 to-emerald-400 rounded-t-sm w-4 transition-all duration-500"
                      style={{ height: `${(data.revenue / 15000000) * 100}px` }}
                      title={`Revenue: Rp ${data.revenue.toLocaleString('id-ID')}`}
                    />
                    <div 
                      className="bg-gradient-to-t from-red-500 to-red-400 rounded-t-sm w-4 transition-all duration-500"
                      style={{ height: `${(data.expenses / 15000000) * 100}px` }}
                      title={`Expenses: Rp ${data.expenses.toLocaleString('id-ID')}`}
                    />
                    <div 
                      className="bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-sm w-4 transition-all duration-500"
                      style={{ height: `${(data.profit / 15000000) * 100}px` }}
                      title={`Profit: Rp ${data.profit.toLocaleString('id-ID')}`}
                    />
                  </div>
                  <span className="text-xs text-slate-600 font-medium">{data.month}</span>
                </div>
              ))}
            </div>
            <div className="flex justify-center space-x-6 mt-4">
              <div className="flex items-center space-x-2">
                <div className="w-3 h-3 bg-emerald-500 rounded-full"></div>
                <span className="text-sm text-slate-600">Revenue</span>
              </div>
              <div className="flex items-center space-x-2">
                <div className="w-3 h-3 bg-red-500 rounded-full"></div>
                <span className="text-sm text-slate-600">Expenses</span>
              </div>
              <div className="flex items-center space-x-2">
                <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span className="text-sm text-slate-600">Profit</span>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-white rounded-xl p-6 shadow-sm border border-slate-200/50">
              <h4 className="font-semibold text-slate-800 mb-4">Top Revenue Categories</h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-slate-600">Konsultasi Dokter</span>
                  <span className="font-semibold text-emerald-600">45%</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-slate-600">Rawat Jalan</span>
                  <span className="font-semibold text-emerald-600">35%</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-slate-600">Obat-obatan</span>
                  <span className="font-semibold text-emerald-600">20%</span>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl p-6 shadow-sm border border-slate-200/50">
              <h4 className="font-semibold text-slate-800 mb-4">Top Expense Categories</h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-slate-600">Gaji Karyawan</span>
                  <span className="font-semibold text-red-600">60%</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-slate-600">Operasional</span>
                  <span className="font-semibold text-red-600">25%</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-slate-600">Maintenance</span>
                  <span className="font-semibold text-red-600">15%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      );
    };

    const BudgetPlanning = () => {
      const budgetCategories = [
        { name: 'Gaji Karyawan', budget: 250000000, actual: 230000000, percentage: 92 },
        { name: 'Obat-obatan', budget: 80000000, actual: 85000000, percentage: 106 },
        { name: 'Operasional', budget: 60000000, actual: 55000000, percentage: 92 },
        { name: 'Marketing', budget: 30000000, actual: 25000000, percentage: 83 },
        { name: 'Maintenance', budget: 25000000, actual: 30000000, percentage: 120 }
      ];

      return (
        <div className="space-y-6">
          <div className="bg-gradient-to-r from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg">
            <h3 className="text-xl font-bold mb-4">Budget Planning & Monitoring</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center">
                <p className="text-2xl font-bold">Rp 445M</p>
                <p className="text-sm opacity-80">Total Budget Bulan Ini</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold">Rp 425M</p>
                <p className="text-sm opacity-80">Actual Spending</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-6">Budget vs Actual Spending</h3>
            <div className="space-y-6">
              {budgetCategories.map((category, index) => (
                <div key={index} className="space-y-2">
                  <div className="flex justify-between items-center">
                    <span className="font-medium text-slate-700">{category.name}</span>
                    <div className="text-right">
                      <span className={`font-bold ${
                        category.percentage <= 100 ? 'text-emerald-600' : 'text-red-600'
                      }`}>
                        {category.percentage}%
                      </span>
                      <p className="text-xs text-slate-500">
                        Rp {category.actual.toLocaleString('id-ID')} / Rp {category.budget.toLocaleString('id-ID')}
                      </p>
                    </div>
                  </div>
                  <div className="relative">
                    <div className="w-full bg-slate-200 rounded-full h-3">
                      <div 
                        className={`h-3 rounded-full transition-all duration-1000 ${
                          category.percentage <= 100 ? 'bg-emerald-500' : 'bg-red-500'
                        }`}
                        style={{ width: `${Math.min(category.percentage, 100)}%` }}
                      ></div>
                    </div>
                    {category.percentage > 100 && (
                      <div className="absolute top-0 right-0 w-2 h-3 bg-red-600 rounded-r-full"></div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-white rounded-xl p-6 shadow-sm border border-slate-200/50">
              <h4 className="font-semibold text-slate-800 mb-4 flex items-center">
                <CheckCircle className="w-5 h-5 mr-2 text-emerald-500" />
                Budget Recommendations
              </h4>
              <div className="space-y-3">
                <div className="p-3 bg-emerald-50 rounded-lg">
                  <p className="text-sm text-emerald-700">Alokasi marketing dapat ditingkatkan 17% untuk bulan depan</p>
                </div>
                <div className="p-3 bg-blue-50 rounded-lg">
                  <p className="text-sm text-blue-700">Efisiensi gaji karyawan sangat baik (92% dari budget)</p>
                </div>
                <div className="p-3 bg-yellow-50 rounded-lg">
                  <p className="text-sm text-yellow-700">Monitor ketat budget obat-obatan (over 6%)</p>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl p-6 shadow-sm border border-slate-200/50">
              <h4 className="font-semibold text-slate-800 mb-4">Next Month Forecast</h4>
              <div className="space-y-4">
                <div className="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                  <span className="text-sm text-slate-600">Projected Revenue</span>
                  <span className="font-semibold text-emerald-600">Rp 1.35B</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                  <span className="text-sm text-slate-600">Projected Expenses</span>
                  <span className="font-semibold text-red-600">Rp 450M</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                  <span className="text-sm text-slate-600">Projected Profit</span>
                  <span className="font-bold text-blue-600">Rp 900M</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      );
    };

    const CostAnalysis = () => {
      const costBreakdown = [
        { category: 'Gaji Karyawan', amount: 230000000, percentage: 54, trend: '+2%' },
        { category: 'Obat-obatan', amount: 85000000, percentage: 20, trend: '+8%' },
        { category: 'Operasional', amount: 55000000, percentage: 13, trend: '-3%' },
        { category: 'Marketing', amount: 30000000, percentage: 7, trend: '+15%' },
        { category: 'Maintenance', amount: 25000000, percentage: 6, trend: '-12%' }
      ];

      return (
        <div className="space-y-6">
          <div className="bg-gradient-to-r from-orange-500 to-red-600 rounded-2xl p-6 text-white shadow-lg">
            <h3 className="text-xl font-bold mb-4">Analisis Biaya Operasional</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center">
                <p className="text-2xl font-bold">Rp 425M</p>
                <p className="text-sm opacity-80">Total Biaya Bulan Ini</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold">+3.5%</p>
                <p className="text-sm opacity-80">Vs Bulan Lalu</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-6">Breakdown Biaya per Kategori</h3>
            <div className="space-y-4">
              {costBreakdown.map((cost, index) => (
                <div key={index} className="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                  <div className="flex items-center space-x-4">
                    <div className="w-4 h-4 rounded-full" style={{ backgroundColor: `hsl(${index * 60}, 70%, 50%)` }}></div>
                    <div>
                      <p className="font-semibold text-slate-800">{cost.category}</p>
                      <p className="text-sm text-slate-500">Rp {cost.amount.toLocaleString('id-ID')}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-xl font-bold text-slate-800">{cost.percentage}%</p>
                    <p className={`text-sm font-medium ${
                      cost.trend.startsWith('+') ? 'text-red-600' : 'text-emerald-600'
                    }`}>
                      {cost.trend}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-4">Cost Optimization Insights</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h4 className="font-medium text-slate-700 mb-3">Opportunities</h4>
                <div className="space-y-3">
                  <div className="p-3 bg-emerald-50 rounded-lg flex items-start space-x-2">
                    <TrendingDown className="w-4 h-4 text-emerald-600 mt-0.5" />
                    <p className="text-sm text-emerald-700">Marketing cost turun 12% - efisiensi campaign digital</p>
                  </div>
                  <div className="p-3 bg-emerald-50 rounded-lg flex items-start space-x-2">
                    <TrendingDown className="w-4 h-4 text-emerald-600 mt-0.5" />
                    <p className="text-sm text-emerald-700">Operasional turun 3% - optimasi penggunaan listrik</p>
                  </div>
                </div>
              </div>
              <div>
                <h4 className="font-medium text-slate-700 mb-3">Concerns</h4>
                <div className="space-y-3">
                  <div className="p-3 bg-red-50 rounded-lg flex items-start space-x-2">
                    <TrendingUp className="w-4 h-4 text-red-600 mt-0.5" />
                    <p className="text-sm text-red-700">Maintenance naik 15% - perlu evaluasi kontrak vendor</p>
                  </div>
                  <div className="p-3 bg-yellow-50 rounded-lg flex items-start space-x-2">
                    <TrendingUp className="w-4 h-4 text-yellow-600 mt-0.5" />
                    <p className="text-sm text-yellow-700">Obat-obatan naik 8% - review supplier contracts</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      );
    };

    const ExportData = () => {
      const [exportFormat, setExportFormat] = useState('pdf');
      const [dateRange, setDateRange] = useState('month');
      const [categories, setCategories] = useState(['revenue', 'expenses']);

      return (
        <div className="space-y-6">
          <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
            <h3 className="text-xl font-bold mb-4">Export Data Keuangan</h3>
            <p className="text-sm opacity-80">Generate laporan keuangan dalam berbagai format untuk keperluan analisis dan presentasi</p>
          </div>

          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-6">Export Configuration</h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">Format Export</label>
                  <div className="grid grid-cols-2 gap-2">
                    <button 
                      onClick={() => setExportFormat('pdf')}
                      className={`p-3 rounded-xl border transition-all ${
                        exportFormat === 'pdf' 
                          ? 'bg-red-100 border-red-300 text-red-700' 
                          : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                      }`}
                    >
                      <FileText className="w-5 h-5 mx-auto mb-1" />
                      <span className="text-sm font-medium">PDF</span>
                    </button>
                    <button 
                      onClick={() => setExportFormat('excel')}
                      className={`p-3 rounded-xl border transition-all ${
                        exportFormat === 'excel' 
                          ? 'bg-emerald-100 border-emerald-300 text-emerald-700' 
                          : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                      }`}
                    >
                      <BarChart3 className="w-5 h-5 mx-auto mb-1" />
                      <span className="text-sm font-medium">Excel</span>
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">Periode Data</label>
                  <select 
                    value={dateRange}
                    onChange={(e) => setDateRange(e.target.value)}
                    className="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="week">Minggu Ini</option>
                    <option value="month">Bulan Ini</option>
                    <option value="quarter">Kuartal Ini</option>
                    <option value="year">Tahun Ini</option>
                    <option value="custom">Custom Range</option>
                  </select>
                </div>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">Data Categories</label>
                  <div className="space-y-2">
                    {[
                      { id: 'revenue', label: 'Revenue Data' },
                      { id: 'expenses', label: 'Expense Data' },
                      { id: 'profit', label: 'Profit Analysis' },
                      { id: 'budget', label: 'Budget Comparison' },
                      { id: 'trends', label: 'Trend Analysis' }
                    ].map((category) => (
                      <label key={category.id} className="flex items-center space-x-2">
                        <input 
                          type="checkbox" 
                          checked={categories.includes(category.id)}
                          onChange={(e) => {
                            if (e.target.checked) {
                              setCategories([...categories, category.id]);
                            } else {
                              setCategories(categories.filter(c => c !== category.id));
                            }
                          }}
                          className="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                        />
                        <span className="text-sm text-slate-700">{category.label}</span>
                      </label>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-white rounded-xl p-6 shadow-sm border border-slate-200/50">
              <h4 className="font-semibold text-slate-800 mb-4">Quick Export Templates</h4>
              <div className="space-y-3">
                <button className="w-full p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-medium hover:bg-emerald-100 transition-colors">
                  📊 Executive Summary (PDF)
                </button>
                <button className="w-full p-3 bg-blue-50 text-blue-700 rounded-xl text-sm font-medium hover:bg-blue-100 transition-colors">
                  📈 Detailed Financial Report (Excel)
                </button>
                <button className="w-full p-3 bg-purple-50 text-purple-700 rounded-xl text-sm font-medium hover:bg-purple-100 transition-colors">
                  💰 Budget vs Actual (PDF)
                </button>
              </div>
            </div>

            <div className="bg-white rounded-xl p-6 shadow-sm border border-slate-200/50">
              <h4 className="font-semibold text-slate-800 mb-4">Export Preview</h4>
              <div className="space-y-2 text-sm text-slate-600">
                <p><strong>Format:</strong> {exportFormat.toUpperCase()}</p>
                <p><strong>Periode:</strong> {dateRange === 'month' ? 'Bulan Ini' : dateRange}</p>
                <p><strong>Categories:</strong> {categories.length} selected</p>
                <p><strong>Estimated Size:</strong> ~2.5 MB</p>
              </div>
              <button className="w-full mt-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                <Download className="w-4 h-4 inline mr-2" />
                Generate Export
              </button>
            </div>
          </div>

          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-4">Recent Exports</h3>
            <div className="space-y-3">
              {[
                { name: 'Financial_Report_Aug2025.pdf', date: '2025-08-18', size: '2.1 MB' },
                { name: 'Budget_Analysis_Q3.xlsx', date: '2025-08-15', size: '1.8 MB' },
                { name: 'Revenue_Trends_Jul2025.pdf', date: '2025-08-10', size: '1.2 MB' }
              ].map((file, index) => (
                <div key={index} className="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                  <div className="flex items-center space-x-3">
                    <FileText className="w-5 h-5 text-slate-500" />
                    <div>
                      <p className="font-medium text-slate-800">{file.name}</p>
                      <p className="text-xs text-slate-500">{file.date} • {file.size}</p>
                    </div>
                  </div>
                  <button className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                    <Download className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>
          </div>
        </div>
      );
    };

    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-bold text-slate-800 flex items-center">
            <Wallet className="w-6 h-6 mr-3 text-emerald-500" />
            Finance Management
          </h2>
        </div>

        {/* Finance Sub Navigation */}
        <div className="flex space-x-2 overflow-x-auto pb-2">
          {[
            { id: 'overview', label: 'Overview', icon: Eye },
            { id: 'monthly', label: 'Laporan Bulanan', icon: FileText },
            { id: 'budget', label: 'Budget Planning', icon: Calculator },
            { id: 'analysis', label: 'Analisis Biaya', icon: PieChart },
            { id: 'export', label: 'Export Data', icon: Download }
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveFinanceTab(tab.id)}
              className={`flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap ${
                activeFinanceTab === tab.id
                  ? 'bg-emerald-100 text-emerald-700'
                  : 'bg-white text-slate-600 hover:bg-slate-50'
              }`}
            >
              <tab.icon className="w-4 h-4" />
              <span>{tab.label}</span>
            </button>
          ))}
        </div>

        {/* Finance Content */}
        {renderFinanceContent()}
      </div>
    );
  };

  // Attendance View Component
  const AttendanceView = () => {
    const attendancePercentage = Math.round((attendanceData.presentToday / attendanceData.totalEmployees) * 100);
    const monthlyAttendance = [95, 92, 88, 94, 96, 89, 93, 91, 97, 90, 95, 94];
    const departmentAttendance = [
      { name: 'Dokter', present: 8, total: 8, percentage: 100 },
      { name: 'Perawat', present: 15, total: 16, percentage: 94 },
      { name: 'Admin', present: 12, total: 12, percentage: 100 },
      { name: 'Security', present: 3, total: 4, percentage: 75 },
      { name: 'Cleaning', present: 4, total: 5, percentage: 80 }
    ];

    // Top Performers (Kehadiran Terbaik)
    const topPerformers = [
      { name: 'Dr. Ahmad Santoso', department: 'Dokter', attendance: 100, streak: 30 },
      { name: 'Perawat Siti Nurhaliza', department: 'Perawat', attendance: 98, streak: 25 },
      { name: 'Admin Budi Santoso', department: 'Admin', attendance: 97, streak: 28 },
      { name: 'Dr. Lisa Putri', department: 'Dokter Gigi', attendance: 96, streak: 22 },
      { name: 'Perawat Rina', department: 'Perawat', attendance: 95, streak: 20 }
    ];

    // Poor Performers (Kehadiran Buruk)
    const poorPerformers = [
      { name: 'Security Andi', department: 'Security', attendance: 75, absences: 8, lateCount: 5 },
      { name: 'Cleaning Service Maya', department: 'Cleaning', attendance: 80, absences: 6, lateCount: 3 },
      { name: 'Admin Doni', department: 'Admin', attendance: 85, absences: 4, lateCount: 4 },
      { name: 'Perawat Dewi', department: 'Perawat', attendance: 87, absences: 3, lateCount: 6 }
    ];

    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-bold text-slate-800 flex items-center">
            <BarChart3 className="w-6 h-6 mr-3 text-blue-500" />
            Tingkat Kehadiran
          </h2>
          <div className="flex space-x-2">
            <button className="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors">
              Hari Ini
            </button>
            <button className="px-4 py-2 bg-white text-slate-700 rounded-xl text-sm font-medium border border-slate-200 hover:bg-slate-50 transition-colors">
              Minggu Ini
            </button>
            <button className="px-4 py-2 bg-white text-slate-700 rounded-xl text-sm font-medium border border-slate-200 hover:bg-slate-50 transition-colors">
              Bulan Ini
            </button>
          </div>
        </div>

        {/* Overall Attendance Rate */}
        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-8 text-white shadow-lg">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h3 className="text-3xl font-bold mb-2">{attendancePercentage}%</h3>
              <p className="text-lg opacity-90">Tingkat Kehadiran Hari Ini</p>
              <p className="text-sm opacity-75">{attendanceData.presentToday} dari {attendanceData.totalEmployees} karyawan hadir</p>
            </div>
            <div className="relative w-24 h-24">
              <svg className="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="35" fill="none" stroke="rgba(255,255,255,0.2)" strokeWidth="8"/>
                <circle 
                  cx="50" cy="50" r="35" fill="none" 
                  stroke="white" strokeWidth="8"
                  strokeDasharray={`${(attendancePercentage / 100) * 220} 220`}
                  strokeLinecap="round"
                  className="transition-all duration-1000"
                />
              </svg>
              <div className="absolute inset-0 flex items-center justify-center">
                <CheckCircle className="w-8 h-8 text-white" />
              </div>
            </div>
          </div>
        </div>

        {/* Attendance Summary Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200/50">
            <div className="flex items-center justify-between mb-3">
              <CheckCircle className="w-8 h-8 text-emerald-500" />
              <span className="text-2xl font-bold text-emerald-600">{attendanceData.presentToday}</span>
            </div>
            <p className="text-sm font-medium text-slate-700">Hadir</p>
            <p className="text-xs text-slate-500">{Math.round((attendanceData.presentToday/attendanceData.totalEmployees)*100)}% dari total</p>
          </div>

          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200/50">
            <div className="flex items-center justify-between mb-3">
              <Clock className="w-8 h-8 text-yellow-500" />
              <span className="text-2xl font-bold text-yellow-600">{attendanceData.lateToday}</span>
            </div>
            <p className="text-sm font-medium text-slate-700">Terlambat</p>
            <p className="text-xs text-slate-500">{Math.round((attendanceData.lateToday/attendanceData.totalEmployees)*100)}% dari total</p>
          </div>

          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200/50">
            <div className="flex items-center justify-between mb-3">
              <AlertTriangle className="w-8 h-8 text-red-500" />
              <span className="text-2xl font-bold text-red-600">{attendanceData.absentToday}</span>
            </div>
            <p className="text-sm font-medium text-slate-700">Tidak Hadir</p>
            <p className="text-xs text-slate-500">{Math.round((attendanceData.absentToday/attendanceData.totalEmployees)*100)}% dari total</p>
          </div>

          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200/50">
            <div className="flex items-center justify-between mb-3">
              <TrendingUp className="w-8 h-8 text-blue-500" />
              <span className="text-2xl font-bold text-blue-600">93%</span>
            </div>
            <p className="text-sm font-medium text-slate-700">Rata-rata Bulanan</p>
            <p className="text-xs text-slate-500">Trend meningkat 2%</p>
          </div>
        </div>

        {/* Top & Poor Performers */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Top Performers */}
          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-4 flex items-center">
              <Award className="w-5 h-5 mr-2 text-emerald-500" />
              Top Kehadiran (Bulan Ini)
            </h3>
            <div className="space-y-4">
              {topPerformers.map((employee, index) => (
                <div key={index} className="flex items-center justify-between p-4 bg-emerald-50 rounded-xl">
                  <div className="flex items-center space-x-4">
                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white ${
                      index === 0 ? 'bg-gradient-to-r from-yellow-400 to-yellow-500' :
                      index === 1 ? 'bg-gradient-to-r from-gray-400 to-gray-500' :
                      index === 2 ? 'bg-gradient-to-r from-orange-400 to-orange-500' :
                      'bg-gradient-to-r from-emerald-400 to-emerald-500'
                    }`}>
                      #{index + 1}
                    </div>
                    <div>
                      <p className="font-semibold text-slate-800">{employee.name}</p>
                      <p className="text-sm text-slate-500">{employee.department}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-xl font-bold text-emerald-600">{employee.attendance}%</p>
                    <p className="text-xs text-emerald-600">🔥 {employee.streak} hari berturut</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Poor Performers */}
          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
            <h3 className="text-lg font-semibold text-slate-800 mb-4 flex items-center">
              <AlertTriangle className="w-5 h-5 mr-2 text-red-500" />
              Perlu Perhatian (Bulan Ini)
            </h3>
            <div className="space-y-4">
              {poorPerformers.map((employee, index) => (
                <div key={index} className="flex items-center justify-between p-4 bg-red-50 rounded-xl">
                  <div className="flex items-center space-x-4">
                    <div className="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                      <AlertTriangle className="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                      <p className="font-semibold text-slate-800">{employee.name}</p>
                      <p className="text-sm text-slate-500">{employee.department}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-xl font-bold text-red-600">{employee.attendance}%</p>
                    <p className="text-xs text-red-600">{employee.absences} tidak hadir, {employee.lateCount} terlambat</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Monthly Attendance Trend */}
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800 mb-4">Tren Kehadiran 12 Bulan Terakhir</h3>
          <div className="h-48 bg-gradient-to-t from-slate-50 to-white rounded-xl flex items-end justify-between p-4">
            {monthlyAttendance.map((percentage, index) => (
              <div key={index} className="flex flex-col items-center space-y-2">
                <div 
                  className="bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-lg w-6 transition-all duration-500 hover:from-blue-400 hover:to-blue-300"
                  style={{ height: `${(percentage / 100) * 120}px` }}
                />
                <span className="text-xs text-slate-600 font-medium">{percentage}%</span>
                <span className="text-xs text-slate-400">{index + 1}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Department Attendance Breakdown */}
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800 mb-4">Kehadiran per Departemen</h3>
          <div className="space-y-4">
            {departmentAttendance.map((dept, index) => (
              <div key={index} className="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                <div className="flex items-center space-x-4">
                  <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${
                    dept.percentage >= 95 ? 'bg-emerald-100 text-emerald-600' :
                    dept.percentage >= 85 ? 'bg-yellow-100 text-yellow-600' :
                    'bg-red-100 text-red-600'
                  }`}>
                    <Users className="w-6 h-6" />
                  </div>
                  <div>
                    <p className="font-semibold text-slate-800">{dept.name}</p>
                    <p className="text-sm text-slate-500">{dept.present}/{dept.total} karyawan</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className={`text-2xl font-bold ${
                    dept.percentage >= 95 ? 'text-emerald-600' :
                    dept.percentage >= 85 ? 'text-yellow-600' :
                    'text-red-600'
                  }`}>
                    {dept.percentage}%
                  </p>
                  <div className="w-20 bg-slate-200 rounded-full h-2 mt-1">
                    <div 
                      className={`h-2 rounded-full transition-all duration-1000 ${
                        dept.percentage >= 95 ? 'bg-emerald-500' :
                        dept.percentage >= 85 ? 'bg-yellow-500' :
                        'bg-red-500'
                      }`}
                      style={{ width: `${dept.percentage}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Attendance Insights */}
        <div className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
          <h3 className="text-lg font-semibold mb-4 flex items-center">
            <Eye className="w-5 h-5 mr-2" />
            Insights Kehadiran
          </h3>
          <div className="space-y-3">
            <div className="flex items-center space-x-3">
              <TrendingUp className="w-5 h-5" />
              <p className="text-sm">Tingkat kehadiran meningkat 2% dibanding bulan lalu</p>
            </div>
            <div className="flex items-center space-x-3">
              <Star className="w-5 h-5" />
              <p className="text-sm">Dr. Ahmad Santoso mempertahankan 100% kehadiran selama 30 hari</p>
            </div>
            <div className="flex items-center space-x-3">
              <AlertTriangle className="w-5 h-5" />
              <p className="text-sm">4 karyawan perlu perhatian khusus untuk improvement kehadiran</p>
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Jaspel View Component
  const JaspelView = () => (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-slate-800 flex items-center">
          <CreditCard className="w-6 h-6 mr-3 text-emerald-500" />
          Jasa Pelayanan (Jaspel)
        </h2>
        <div className="flex space-x-2">
          <button className="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors">
            Bulan Ini
          </button>
          <button className="p-2 bg-white rounded-xl shadow-sm border border-slate-200/50 hover:bg-slate-50 transition-colors">
            <Calculator className="w-4 h-4 text-slate-600" />
          </button>
        </div>
      </div>

      {/* Jaspel Summary */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
          <div className="flex items-center justify-between mb-4">
            <DollarSign className="w-8 h-8" />
            <TrendingUp className="w-6 h-6 opacity-80" />
          </div>
          <h3 className="text-2xl font-bold mb-1">
            Rp {jaspelData.totalJaspelMonth.toLocaleString('id-ID')}
          </h3>
          <p className="text-sm opacity-90">Total Jaspel Bulan Ini</p>
        </div>

        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
          <div className="flex items-center justify-between mb-4">
            <Calculator className="w-8 h-8" />
          </div>
          <h3 className="text-2xl font-bold mb-1">
            Rp {jaspelData.averagePerDoctor.toLocaleString('id-ID')}
          </h3>
          <p className="text-sm opacity-90">Rata-rata per Dokter</p>
        </div>

        <div className="bg-gradient-to-r from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg">
          <div className="flex items-center justify-between mb-4">
            <Award className="w-8 h-8" />
            <Star className="w-6 h-6 opacity-80" />
          </div>
          <h3 className="text-lg font-bold mb-1">{jaspelData.topPerformer}</h3>
          <p className="text-sm opacity-90">Top Performer</p>
        </div>
      </div>

      {/* Doctor Performance Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden">
        <div className="p-6 bg-slate-50 border-b border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800">Ranking Jaspel Dokter</h3>
        </div>
        <div className="divide-y divide-slate-200/50">
          {jaspelData.doctors.map((doctor) => (
            <div key={doctor.id} className="p-6 hover:bg-slate-50/50 transition-colors">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <div className={`w-12 h-12 rounded-xl flex items-center justify-center font-bold text-white ${
                    doctor.rank === 1 ? 'bg-gradient-to-r from-yellow-400 to-yellow-500' :
                    doctor.rank === 2 ? 'bg-gradient-to-r from-gray-400 to-gray-500' :
                    doctor.rank === 3 ? 'bg-gradient-to-r from-orange-400 to-orange-500' :
                    'bg-gradient-to-r from-blue-400 to-blue-500'
                  }`}>
                    #{doctor.rank}
                  </div>
                  <div>
                    <p className="font-semibold text-slate-800">{doctor.name}</p>
                    <p className="text-sm text-slate-500">{doctor.patients} pasien bulan ini</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-lg font-bold text-emerald-600">
                    Rp {doctor.total.toLocaleString('id-ID')}
                  </p>
                  <div className="text-xs text-slate-500 space-y-1">
                    <div>Jaspel: Rp {doctor.jaspel.toLocaleString('id-ID')}</div>
                    <div>Bonus: Rp {doctor.bonus.toLocaleString('id-ID')}</div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Jaspel Calculator */}
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
        <h3 className="text-lg font-semibold text-slate-800 mb-4 flex items-center">
          <Calculator className="w-5 h-5 mr-2" />
          Kalkulator Jaspel
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">Jumlah Pasien</label>
              <input 
                type="number" 
                className="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="Masukkan jumlah pasien"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">Tipe Pasien</label>
              <select className="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option>Pasien Umum</option>
                <option>Pasien BPJS</option>
                <option>Pasien Gigi</option>
              </select>
            </div>
          </div>
          <div className="flex items-center justify-center">
            <button className="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-8 py-4 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
              Hitung Jaspel
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  // Profile View Component
  const ProfileView = () => (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-slate-800 flex items-center">
          <User className="w-6 h-6 mr-3 text-indigo-500" />
          Profil Manager
        </h2>
        <button className="p-2 bg-white rounded-xl shadow-sm border border-slate-200/50 hover:bg-slate-50 transition-colors">
          <Edit className="w-4 h-4 text-slate-600" />
        </button>
      </div>

      {/* Profile Header */}
      <div className="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white shadow-lg">
        <div className="flex items-center space-x-6">
          <div className="w-24 h-24 bg-white/20 rounded-2xl flex items-center justify-center">
            <User className="w-12 h-12 text-white" />
          </div>
          <div>
            <h3 className="text-2xl font-bold mb-2">Dr. Manager Utama</h3>
            <p className="text-lg opacity-90">Manager Klinik</p>
            <p className="text-sm opacity-80">ID: MGR-001 | Bergabung: Jan 2020</p>
          </div>
        </div>
      </div>

      {/* Profile Information */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800 mb-4">Informasi Personal</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
              <input 
                type="text" 
                value="Dr. Manager Utama" 
                readOnly
                className="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 text-slate-600"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Email</label>
              <input 
                type="email" 
                value="manager@klinik.com" 
                readOnly
                className="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 text-slate-600"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Nomor Telepon</label>
              <input 
                type="tel" 
                value="+62 812-3456-7890" 
                readOnly
                className="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 text-slate-600"
              />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
          <h3 className="text-lg font-semibold text-slate-800 mb-4">Pengaturan Akun</h3>
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium text-slate-700">Notifikasi Email</p>
                <p className="text-sm text-slate-500">Terima notifikasi via email</p>
              </div>
              <div className="w-12 h-6 bg-emerald-500 rounded-full relative">
                <div className="w-5 h-5 bg-white rounded-full absolute top-0.5 right-0.5"></div>
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium text-slate-700">Notifikasi Push</p>
                <p className="text-sm text-slate-500">Terima notifikasi push</p>
              </div>
              <div className="w-12 h-6 bg-emerald-500 rounded-full relative">
                <div className="w-5 h-5 bg-white rounded-full absolute top-0.5 right-0.5"></div>
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium text-slate-700">Mode Gelap</p>
                <p className="text-sm text-slate-500">Aktifkan tema gelap</p>
              </div>
              <div className="w-12 h-6 bg-slate-300 rounded-full relative">
                <div className="w-5 h-5 bg-white rounded-full absolute top-0.5 left-0.5"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Manager Permissions */}
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/50">
        <h3 className="text-lg font-semibold text-slate-800 mb-4 flex items-center">
          <Shield className="w-5 h-5 mr-2" />
          Hak Akses Manager
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="flex items-center space-x-3 p-4 bg-emerald-50 rounded-xl">
            <CheckCircle className="w-5 h-5 text-emerald-600" />
            <span className="text-slate-700">Lihat Semua Data Keuangan</span>
          </div>
          <div className="flex items-center space-x-3 p-4 bg-emerald-50 rounded-xl">
            <CheckCircle className="w-5 h-5 text-emerald-600" />
            <span className="text-slate-700">Monitor Kehadiran Karyawan</span>
          </div>
          <div className="flex items-center space-x-3 p-4 bg-emerald-50 rounded-xl">
            <CheckCircle className="w-5 h-5 text-emerald-600" />
            <span className="text-slate-700">Kelola Skema Jaspel</span>
          </div>
          <div className="flex items-center space-x-3 p-4 bg-emerald-50 rounded-xl">
            <CheckCircle className="w-5 h-5 text-emerald-600" />
            <span className="text-slate-700">Export Laporan</span>
          </div>
          <div className="flex items-center space-x-3 p-4 bg-red-50 rounded-xl">
            <AlertTriangle className="w-5 h-5 text-red-600" />
            <span className="text-slate-700">Edit Data Input Petugas</span>
          </div>
          <div className="flex items-center space-x-3 p-4 bg-red-50 rounded-xl">
            <AlertTriangle className="w-5 h-5 text-red-600" />
            <span className="text-slate-700">Validasi Bendahara</span>
          </div>
        </div>
      </div>

      {/* Logout Button */}
      <div className="flex justify-center">
        <button className="bg-gradient-to-r from-red-500 to-red-600 text-white px-8 py-4 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300 flex items-center space-x-2">
          <LogOut className="w-5 h-5" />
          <span>Logout</span>
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 pb-20">
      {/* Sticky Header with Glassmorphism */}
      <header className="sticky top-0 z-50 backdrop-blur-md bg-white/70 border-b border-white/20 shadow-sm">
        <div className="flex items-center justify-between p-4">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
              <Activity className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-slate-800">Manager Dashboard</h1>
              <p className="text-xs text-slate-500 flex items-center">
                <Calendar className="w-3 h-3 mr-1" />
                {currentTime.toLocaleDateString('id-ID', { 
                  weekday: 'long', 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
                })}
              </p>
            </div>
          </div>
          
          <div className="flex items-center space-x-3">
            <button 
              onClick={handleRefresh}
              className={`p-2 rounded-xl bg-white/60 hover:bg-white/80 transition-all duration-300 ${isRefreshing ? 'animate-spin' : ''}`}
            >
              <RefreshCw className="w-5 h-5 text-slate-600" />
            </button>
            
            <div className="relative">
              <button className="p-2 rounded-xl bg-white/60 hover:bg-white/80 transition-all duration-300">
                <Bell className="w-5 h-5 text-slate-600" />
              </button>
              {notifications > 0 && (
                <span className="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-xs text-white animate-pulse">
                  {notifications}
                </span>
              )}
            </div>
            
            <div className="flex items-center space-x-2 bg-white/60 rounded-xl px-3 py-2">
              <div className="w-8 h-8 bg-gradient-to-r from-emerald-400 to-cyan-500 rounded-lg flex items-center justify-center">
                <User className="w-4 h-4 text-white" />
              </div>
              <div className="text-xs">
                <p className="font-semibold text-slate-700">Dr. Manager</p>
                <p className="text-slate-500">Admin</p>
              </div>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="p-4 space-y-6">
        {renderTabContent()}
      </main>

      {/* Bottom Navigation */}
      <nav className="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-slate-200/50 shadow-lg">
        <div className="flex items-center justify-around py-2">
          <button 
            onClick={() => setActiveTab('dashboard')}
            className={`flex flex-col items-center space-y-1 p-3 rounded-xl transition-all duration-300 ${
              activeTab === 'dashboard' 
                ? 'bg-blue-100 text-blue-600' 
                : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            <Home className="w-6 h-6" />
            <span className="text-xs font-medium">Dashboard</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('finance')}
            className={`flex flex-col items-center space-y-1 p-3 rounded-xl transition-all duration-300 ${
              activeTab === 'finance' 
                ? 'bg-emerald-100 text-emerald-600' 
                : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            <Wallet className="w-6 h-6" />
            <span className="text-xs font-medium">Finance</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('attendance')}
            className={`flex flex-col items-center space-y-1 p-3 rounded-xl transition-all duration-300 ${
              activeTab === 'attendance' 
                ? 'bg-blue-100 text-blue-600' 
                : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            <Clock className="w-6 h-6" />
            <span className="text-xs font-medium">Kehadiran</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('jaspel')}
            className={`flex flex-col items-center space-y-1 p-3 rounded-xl transition-all duration-300 ${
              activeTab === 'jaspel' 
                ? 'bg-emerald-100 text-emerald-600' 
                : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            <CreditCard className="w-6 h-6" />
            <span className="text-xs font-medium">Jaspel</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('profile')}
            className={`flex flex-col items-center space-y-1 p-3 rounded-xl transition-all duration-300 ${
              activeTab === 'profile' 
                ? 'bg-indigo-100 text-indigo-600' 
                : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            <Settings className="w-6 h-6" />
            <span className="text-xs font-medium">Profil</span>
          </button>
        </div>
      </nav>
    </div>
  );
};

export default ManagerDashboard;