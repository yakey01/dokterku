import React, { useState, useEffect } from 'react';
import { TrendingUp, TrendingDown, Calendar, Trophy, Zap, Target } from 'lucide-react';

interface JaspelComparisonData {
  current_month: {
    total: number;
    approved: number;
    pending: number;
    count: number;
    month_name: string;
  };
  previous_month: {
    total: number;
    approved: number;
    pending: number;
    count: number;
    month_name: string;
  };
  comparison: {
    percentage_change: number;
    amount_change: number;
    trend: 'up' | 'down' | 'stable';
    status: 'improved' | 'declined' | 'maintained';
  };
}

interface JaspelProgressComparisonProps {
  data?: JaspelComparisonData;
  loading?: boolean;
}

const JaspelProgressComparison: React.FC<JaspelProgressComparisonProps> = ({ 
  data, 
  loading = false 
}) => {
  const [animatedPercentage, setAnimatedPercentage] = useState(0);
  const [animatedCurrentAmount, setAnimatedCurrentAmount] = useState(0);
  const [animatedPreviousAmount, setAnimatedPreviousAmount] = useState(0);
  const [showComparison, setShowComparison] = useState(false);

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  // Animate numbers when data changes
  useEffect(() => {
    if (!data) return;

    const animationDuration = 2000; // 2 seconds
    const steps = 60; // 60 fps
    const intervalTime = animationDuration / steps;

    let currentStep = 0;
    
    const animate = () => {
      currentStep++;
      const progress = Math.min(currentStep / steps, 1);
      
      // Easing function for smooth animation
      const easeOutCubic = (t: number) => 1 - Math.pow(1 - t, 3);
      const easedProgress = easeOutCubic(progress);
      
      // Animate percentage
      setAnimatedPercentage(Math.round(Math.abs(data.comparison.percentage_change) * easedProgress));
      
      // Animate amounts
      setAnimatedCurrentAmount(Math.round(data.current_month.total * easedProgress));
      setAnimatedPreviousAmount(Math.round(data.previous_month.total * easedProgress));
      
      if (progress < 1) {
        setTimeout(animate, intervalTime);
      } else {
        // Show comparison details after main animation
        setTimeout(() => setShowComparison(true), 300);
      }
    };

    // Reset states
    setAnimatedPercentage(0);
    setAnimatedCurrentAmount(0);
    setAnimatedPreviousAmount(0);
    setShowComparison(false);
    
    // Start animation after a brief delay
    setTimeout(animate, 500);
  }, [data]);

  // Loading state
  if (loading) {
    return (
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <div className="animate-pulse">
          <div className="h-6 bg-white/10 rounded-lg mb-4 w-2/3"></div>
          <div className="h-20 bg-white/10 rounded-lg mb-4"></div>
          <div className="space-y-2">
            <div className="h-4 bg-white/10 rounded w-1/2"></div>
            <div className="h-4 bg-white/10 rounded w-3/4"></div>
          </div>
        </div>
      </div>
    );
  }

  // No data state
  if (!data) {
    return (
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <div className="text-center py-8">
          <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-400">Data perbandingan tidak tersedia</p>
          <p className="text-gray-500 text-sm mt-2">Membutuhkan data minimal 2 bulan</p>
        </div>
      </div>
    );
  }

  // Safe destructuring with fallback values
  const current_month = data?.current_month || {
    total: 0,
    approved: 0,
    pending: 0,
    rejected: 0,
    count: 0,
    month_name: 'Loading...',
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear()
  };
  
  const previous_month = data?.previous_month || {
    total: 0,
    approved: 0,
    pending: 0,
    rejected: 0,
    count: 0,
    month_name: 'Loading...',
    month: new Date().getMonth(),
    year: new Date().getFullYear()
  };
  
  const comparison = data?.comparison || {
    total_change: 0,
    total_change_percentage: 0,
    trend: 'stable' as const
  };
  
  // Determine colors based on trend
  const getTrendColors = () => {
    switch (comparison.trend) {
      case 'up':
        return {
          primary: 'from-emerald-500 to-green-400',
          bg: 'bg-emerald-500/10',
          border: 'border-emerald-500/30',
          text: 'text-emerald-400',
          icon: TrendingUp
        };
      case 'down':
        return {
          primary: 'from-red-500 to-pink-400',
          bg: 'bg-red-500/10',
          border: 'border-red-500/30',
          text: 'text-red-400',
          icon: TrendingDown
        };
      default:
        return {
          primary: 'from-blue-500 to-cyan-400',
          bg: 'bg-blue-500/10',
          border: 'border-blue-500/30',
          text: 'text-blue-400',
          icon: Target
        };
    }
  };

  const colors = getTrendColors();
  const TrendIcon = colors.icon;

  return (
    <div className="bg-white/5 backdrop-blur-2xl rounded-3xl border border-white/10 overflow-hidden">
      {/* Header */}
      <div className="p-6 border-b border-white/10">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <div className={`w-12 h-12 rounded-2xl flex items-center justify-center ${colors.bg} ${colors.border} border`}>
              <TrendIcon className={`w-6 h-6 ${colors.text}`} />
            </div>
            <div className="ml-4">
              <h3 className="text-xl font-bold text-white">Recent Achievements</h3>
              <p className="text-gray-400 text-sm">Jaspel Progress Comparison</p>
            </div>
          </div>
          <div className={`px-4 py-2 rounded-full ${colors.bg} ${colors.border} border`}>
            <div className="flex items-center space-x-2">
              <div className={`w-2 h-2 rounded-full bg-gradient-to-r ${colors.primary} animate-pulse`}></div>
              <span className={`text-sm font-medium ${colors.text}`}>
                {comparison.status === 'improved' ? 'Improved' : 
                 comparison.status === 'declined' ? 'Declined' : 'Stable'}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Main Progress Animation */}
      <div className="p-6">
        {/* Percentage Change Display */}
        <div className="text-center mb-8">
          <div className="relative">
            {/* Animated Circle Progress */}
            <div className="relative w-32 h-32 mx-auto">
              <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                {/* Background circle */}
                <circle
                  cx="60"
                  cy="60"
                  r="50"
                  stroke="rgba(255,255,255,0.1)"
                  strokeWidth="8"
                  fill="none"
                />
                {/* Progress circle */}
                <circle
                  cx="60"
                  cy="60"
                  r="50"
                  stroke="url(#gradient)"
                  strokeWidth="8"
                  fill="none"
                  strokeLinecap="round"
                  strokeDasharray={`${Math.min(animatedPercentage * 3.14, 314)} 314`}
                  className="transition-all duration-500 ease-out"
                />
                <defs>
                  <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stopColor={comparison.trend === 'up' ? '#10b981' : '#ef4444'} />
                    <stop offset="100%" stopColor={comparison.trend === 'up' ? '#34d399' : '#f87171'} />
                  </linearGradient>
                </defs>
              </svg>
              
              {/* Center content */}
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="text-center">
                  <div className={`text-3xl font-bold ${colors.text} mb-1`}>
                    {comparison.trend === 'up' ? '+' : comparison.trend === 'down' ? '-' : '±'}
                    {animatedPercentage}%
                  </div>
                  <div className="text-xs text-gray-400">vs Bulan Lalu</div>
                </div>
              </div>
            </div>

            {/* Trend Indicator */}
            <div className="absolute -top-2 -right-2">
              <div className={`w-8 h-8 rounded-full ${colors.bg} ${colors.border} border flex items-center justify-center`}>
                <TrendIcon className={`w-4 h-4 ${colors.text}`} />
              </div>
            </div>
          </div>
        </div>

        {/* Amount Comparison */}
        <div className="space-y-4">
          {/* Current Month */}
          <div className="bg-gradient-to-r from-purple-500/10 to-blue-500/10 rounded-2xl p-4 border border-purple-400/20">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <Trophy className="w-5 h-5 text-purple-400 mr-3" />
                <div>
                  <p className="text-purple-300 text-sm font-medium">{current_month?.month_name || 'Loading...'}</p>
                  <p className="text-xs text-gray-400">Bulan Ini</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-2xl font-bold text-white">
                  {formatCurrency(animatedCurrentAmount)}
                </p>
                <p className="text-xs text-purple-300">{current_month.count} items</p>
              </div>
            </div>
          </div>

          {/* Previous Month */}
          <div className="bg-gradient-to-r from-gray-500/10 to-slate-500/10 rounded-2xl p-4 border border-gray-400/20">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <Calendar className="w-5 h-5 text-gray-400 mr-3" />
                <div>
                  <p className="text-gray-300 text-sm font-medium">{previous_month?.month_name || 'Loading...'}</p>
                  <p className="text-xs text-gray-400">Bulan Lalu</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-2xl font-bold text-white">
                  {formatCurrency(animatedPreviousAmount)}
                </p>
                <p className="text-xs text-gray-300">{previous_month.count} items</p>
              </div>
            </div>
          </div>
        </div>

        {/* Detailed Comparison */}
        {showComparison && (
          <div className="mt-6 pt-6 border-t border-white/10 animate-fade-in">
            <div className="grid grid-cols-2 gap-4">
              {/* Amount Change */}
              <div className={`${colors.bg} rounded-xl p-4 ${colors.border} border`}>
                <div className="flex items-center mb-2">
                  <Zap className={`w-4 h-4 ${colors.text} mr-2`} />
                  <span className="text-sm text-gray-300">Perubahan</span>
                </div>
                <p className={`text-lg font-bold ${colors.text}`}>
                  {comparison.trend === 'up' ? '+' : comparison.trend === 'down' ? '-' : '±'}
                  {formatCurrency(Math.abs(comparison.amount_change))}
                </p>
              </div>

              {/* Growth Rate */}
              <div className={`${colors.bg} rounded-xl p-4 ${colors.border} border`}>
                <div className="flex items-center mb-2">
                  <Target className={`w-4 h-4 ${colors.text} mr-2`} />
                  <span className="text-sm text-gray-300">Growth Rate</span>
                </div>
                <p className={`text-lg font-bold ${colors.text}`}>
                  {comparison.trend === 'up' ? '+' : comparison.trend === 'down' ? '-' : ''}
                  {Math.abs(comparison.percentage_change).toFixed(1)}%
                </p>
              </div>
            </div>

            {/* Performance Message */}
            <div className="mt-4 text-center">
              <p className="text-gray-400 text-sm">
                {comparison.trend === 'up' && '🎉 Great job! Your Jaspel performance is improving'}
                {comparison.trend === 'down' && '📈 Focus on consistency for better results next month'}
                {comparison.trend === 'stable' && '🎯 Maintaining steady performance level'}
              </p>
            </div>
          </div>
        )}
      </div>

      {/* Progress Bar at Bottom */}
      <div className="px-6 pb-6">
        <div className="bg-white/10 rounded-full h-2 overflow-hidden">
          <div 
            className={`h-full bg-gradient-to-r ${colors.primary} transition-all duration-2000 ease-out`}
            style={{ 
              width: `${Math.min((animatedCurrentAmount / Math.max(current_month.total, previous_month.total)) * 100, 100)}%` 
            }}
          ></div>
        </div>
        <div className="flex justify-between mt-2 text-xs text-gray-400">
          <span>Progress</span>
          <span>{Math.round((current_month.total / Math.max(current_month.total, previous_month.total)) * 100)}%</span>
        </div>
      </div>
    </div>
  );
};

export default JaspelProgressComparison;