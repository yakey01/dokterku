import React, { useState } from 'react';
import { 
  FileText, 
  TrendingUp, 
  Calendar, 
  DollarSign, 
  Activity, 
  Award, 
  Target, 
  BarChart3, 
  PieChart, 
  Clock,
  Users,
  Star,
  Shield,
  Brain,
  Zap,
  Heart,
  Trophy,
  Download,
  Filter,
  Eye
} from 'lucide-react';

interface LaporanProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

export function Laporan({ userData, onNavigate }: LaporanProps) {
  const [selectedPeriod, setSelectedPeriod] = useState('monthly');
  const [selectedReport, setSelectedReport] = useState('performance');

  const reportTypes = [
    { id: 'performance', label: 'Performance Report', icon: TrendingUp, color: 'from-blue-500 to-cyan-500' },
    { id: 'financial', label: 'Financial Report', icon: DollarSign, color: 'from-green-500 to-emerald-500' },
    { id: 'attendance', label: 'Attendance Report', icon: Shield, color: 'from-purple-500 to-violet-500' },
    { id: 'patient', label: 'Patient Report', icon: Heart, color: 'from-red-500 to-pink-500' }
  ];

  const mockReportData = {
    totalPatients: 247,
    totalProcedures: 189,
    totalRevenue: 12500000,
    averageRating: 4.8,
    attendanceRate: 96.5,
    completionRate: 94.2
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-y-auto">
        <div className="pb-32 lg:pb-16 p-4">
          
          {/* Floating Background Elements */}
          <div className="absolute inset-0 overflow-hidden pointer-events-none">
            <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
            <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
            <div className="absolute bottom-80 left-6 w-36 h-36 bg-green-500/5 rounded-full blur-3xl animate-pulse"></div>
          </div>

          {/* Header */}
          <div className="relative z-10 text-center mb-8">
            <div className="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600/20 to-purple-600/20 backdrop-blur-xl border border-blue-400/30 rounded-2xl px-6 py-3 mb-4">
              <FileText className="w-8 h-8 text-blue-400" />
              <div>
                <h1 className="text-2xl font-bold text-white">Medical Reports</h1>
                <p className="text-blue-200 text-sm">Analytics & Performance Dashboard</p>
              </div>
            </div>
          </div>

          {/* Period Selector */}
          <div className="flex justify-center mb-6">
            <div className="bg-slate-800/50 backdrop-blur-xl rounded-2xl p-1 border border-purple-400/20">
              {['weekly', 'monthly', 'yearly'].map((period) => (
                <button
                  key={period}
                  onClick={() => setSelectedPeriod(period)}
                  className={`px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 ${
                    selectedPeriod === period
                      ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg'
                      : 'text-gray-300 hover:text-white hover:bg-white/10'
                  }`}
                >
                  {period.charAt(0).toUpperCase() + period.slice(1)}
                </button>
              ))}
            </div>
          </div>

          {/* Report Type Cards */}
          <div className="grid grid-cols-2 gap-4 mb-6">
            {reportTypes.map((report) => {
              const Icon = report.icon;
              return (
                <button
                  key={report.id}
                  onClick={() => setSelectedReport(report.id)}
                  className={`relative p-4 rounded-2xl border transition-all duration-300 ${
                    selectedReport === report.id
                      ? 'bg-gradient-to-r ' + report.color + '/20 border-blue-400/50 scale-105'
                      : 'bg-slate-800/30 border-slate-600/30 hover:border-purple-400/50'
                  }`}
                >
                  <Icon className="w-6 h-6 text-blue-400 mb-2" />
                  <div className="text-sm font-medium text-white">{report.label}</div>
                </button>
              );
            })}
          </div>

          {/* Stats Overview */}
          <div className="bg-gradient-to-r from-slate-800/50 to-purple-800/50 backdrop-blur-xl rounded-3xl p-6 border border-purple-400/20 mb-6">
            <h3 className="text-lg font-bold text-white mb-4 flex items-center gap-2">
              <BarChart3 className="w-5 h-5 text-purple-400" />
              Performance Overview
            </h3>
            
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-blue-500/10 rounded-2xl p-4 border border-blue-400/20">
                <div className="flex items-center gap-2 mb-2">
                  <Users className="w-4 h-4 text-blue-400" />
                  <span className="text-blue-300 text-sm">Patients</span>
                </div>
                <div className="text-2xl font-bold text-white">{mockReportData.totalPatients}</div>
                <div className="text-blue-300 text-xs">Total this month</div>
              </div>

              <div className="bg-green-500/10 rounded-2xl p-4 border border-green-400/20">
                <div className="flex items-center gap-2 mb-2">
                  <Activity className="w-4 h-4 text-green-400" />
                  <span className="text-green-300 text-sm">Procedures</span>
                </div>
                <div className="text-2xl font-bold text-white">{mockReportData.totalProcedures}</div>
                <div className="text-green-300 text-xs">Completed</div>
              </div>

              <div className="bg-purple-500/10 rounded-2xl p-4 border border-purple-400/20">
                <div className="flex items-center gap-2 mb-2">
                  <DollarSign className="w-4 h-4 text-purple-400" />
                  <span className="text-purple-300 text-sm">Revenue</span>
                </div>
                <div className="text-lg font-bold text-white">{formatCurrency(mockReportData.totalRevenue)}</div>
                <div className="text-purple-300 text-xs">This period</div>
              </div>

              <div className="bg-yellow-500/10 rounded-2xl p-4 border border-yellow-400/20">
                <div className="flex items-center gap-2 mb-2">
                  <Star className="w-4 h-4 text-yellow-400" />
                  <span className="text-yellow-300 text-sm">Rating</span>
                </div>
                <div className="text-2xl font-bold text-white">{mockReportData.averageRating}</div>
                <div className="text-yellow-300 text-xs">Average score</div>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="grid grid-cols-2 gap-4">
            <button className="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg">
              <Download className="w-5 h-5" />
              <span>Export Report</span>
            </button>
            
            <button className="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg">
              <Eye className="w-5 h-5" />
              <span>View Details</span>
            </button>
          </div>

        </div>
        {/* End of main content container */}
        
        {/* Medical RPG Bottom Navigation */}
      </div>
    </div>
  );
}