/**
 * Unified Jaspel Summary Dashboard Component
 * Combines dashboard patterns from both dokter and paramedis variants
 */

import React from 'react';
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Clock, 
  CheckCircle, 
  XCircle,
  Calendar,
  Target,
  Award,
  Activity
} from 'lucide-react';
import { motion } from 'framer-motion';
import { 
  JaspelSummary, 
  DashboardData, 
  JaspelVariant, 
  PeriodInfo 
} from '../../lib/jaspel/types';
import { 
  formatCurrency, 
  formatDatePeriod, 
  calculatePercentageChange, 
  calculateCompletionPercentage,
  calculatePeriodProgress,
  getCurrentPeriod,
  getGrowthIcon,
  getGrowthColor,
  getAchievementText,
  getMotivationalMessage
} from '../../lib/jaspel/utils';
import GamingBadge, { GamingBadgeVariants } from '../ui/GamingBadge';

interface JaspelSummaryDashboardProps {
  summary: JaspelSummary;
  variant: JaspelVariant;
  dashboardData?: DashboardData;
  periodInfo?: PeriodInfo;
  loading?: boolean;
  className?: string;
  onRefresh?: () => void;
}

const JaspelSummaryDashboard: React.FC<JaspelSummaryDashboardProps> = ({
  summary,
  variant,
  dashboardData,
  periodInfo,
  loading = false,
  className = '',
  onRefresh
}) => {
  const isDokter = variant === 'dokter';
  const currentPeriod = periodInfo || { ...getCurrentPeriod(), ...calculatePeriodProgress() };
  
  // Calculate derived metrics
  const completionPercentage = calculateCompletionPercentage(summary);
  const totalAmount = summary.total || 0;
  const approvedAmount = summary.approved || 0;
  const pendingAmount = summary.pending || 0;
  const rejectedAmount = summary.rejected || 0;
  
  // Growth calculation
  const lastMonthTotal = dashboardData?.last_month_total || 0;
  const growthPercent = dashboardData?.growth_percent || 
    calculatePercentageChange(totalAmount, lastMonthTotal);
  
  const GrowthIcon = getGrowthIcon(growthPercent);
  const growthColor = getGrowthColor(growthPercent);

  // Gaming elements for dokter variant
  const achievementText = isDokter ? getAchievementText(totalAmount) : '';
  const motivationalMessage = isDokter ? getMotivationalMessage() : '';

  // Container styling based on variant
  const containerClasses = [
    'rounded-xl p-6 transition-all duration-300',
    isDokter 
      ? 'bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50' 
      : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800',
    className
  ].filter(Boolean).join(' ');

  // Animation variants
  const containerVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { 
      opacity: 1, 
      y: 0,
      transition: {
        duration: 0.6,
        staggerChildren: 0.1
      }
    }
  };

  const itemVariants = {
    hidden: { opacity: 0, x: -20 },
    visible: { opacity: 1, x: 0 }
  };

  if (loading) {
    return <JaspelSummaryDashboardSkeleton variant={variant} className={className} />;
  }

  return (
    <motion.div
      className={containerClasses}
      variants={containerVariants}
      initial="hidden"
      animate="visible"
    >
      {/* Header */}
      <motion.div className="flex items-center justify-between mb-6" variants={itemVariants}>
        <div>
          <h2 className={`text-2xl font-bold ${
            isDokter ? 'text-white' : 'text-gray-900 dark:text-white'
          }`}>
            {isDokter ? '🎮 Gaming Dashboard' : 'Jaspel Summary'}
          </h2>
          <p className={`text-sm ${
            isDokter ? 'text-slate-300' : 'text-gray-600 dark:text-gray-400'
          }`}>
            {formatDatePeriod(currentPeriod.current_month, currentPeriod.current_year)}
          </p>
        </div>
        
        {/* Refresh button */}
        {onRefresh && (
          <button
            onClick={onRefresh}
            className={`p-2 rounded-lg transition-colors ${
              isDokter 
                ? 'hover:bg-slate-700 text-slate-400 hover:text-white' 
                : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400'
            }`}
          >
            <Activity className="w-5 h-5" />
          </button>
        )}
      </motion.div>

      {/* Gaming Achievement Section (Dokter only) */}
      {isDokter && (
        <motion.div 
          className="mb-6 p-4 bg-gradient-to-r from-purple-900/30 to-blue-900/30 rounded-lg border border-purple-500/20"
          variants={itemVariants}
        >
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-purple-300 mb-1">
                {achievementText}
              </h3>
              <p className="text-sm text-purple-200">
                {motivationalMessage}
              </p>
            </div>
            <Award className="w-8 h-8 text-purple-400" />
          </div>
        </motion.div>
      )}

      {/* Main Stats Grid */}
      <motion.div 
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6"
        variants={itemVariants}
      >
        {/* Total Amount */}
        <div className={`p-4 rounded-lg ${
          isDokter 
            ? 'bg-blue-900/30 border border-blue-500/20' 
            : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800'
        }`}>
          <div className="flex items-center justify-between">
            <div>
              <p className={`text-sm font-medium ${
                isDokter ? 'text-blue-300' : 'text-blue-600 dark:text-blue-400'
              }`}>
                Total {isDokter ? 'Rewards' : 'Jaspel'}
              </p>
              <p className={`text-2xl font-bold ${
                isDokter ? 'text-white' : 'text-gray-900 dark:text-white'
              }`}>
                {formatCurrency(totalAmount)}
              </p>
            </div>
            <DollarSign className={`w-8 h-8 ${
              isDokter ? 'text-blue-400' : 'text-blue-500'
            }`} />
          </div>
        </div>

        {/* Approved Amount */}
        <div className={`p-4 rounded-lg ${
          isDokter 
            ? 'bg-green-900/30 border border-green-500/20' 
            : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
        }`}>
          <div className="flex items-center justify-between">
            <div>
              <p className={`text-sm font-medium ${
                isDokter ? 'text-green-300' : 'text-green-600 dark:text-green-400'
              }`}>
                {isDokter ? 'Earned' : 'Tervalidasi'}
              </p>
              <p className={`text-2xl font-bold ${
                isDokter ? 'text-white' : 'text-gray-900 dark:text-white'
              }`}>
                {formatCurrency(approvedAmount)}
              </p>
            </div>
            <CheckCircle className={`w-8 h-8 ${
              isDokter ? 'text-green-400' : 'text-green-500'
            }`} />
          </div>
        </div>

        {/* Pending Amount */}
        <div className={`p-4 rounded-lg ${
          isDokter 
            ? 'bg-yellow-900/30 border border-yellow-500/20' 
            : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'
        }`}>
          <div className="flex items-center justify-between">
            <div>
              <p className={`text-sm font-medium ${
                isDokter ? 'text-yellow-300' : 'text-yellow-600 dark:text-yellow-400'
              }`}>
                {isDokter ? 'Pending Quests' : 'Menunggu'}
              </p>
              <p className={`text-2xl font-bold ${
                isDokter ? 'text-white' : 'text-gray-900 dark:text-white'
              }`}>
                {formatCurrency(pendingAmount)}
              </p>
            </div>
            <Clock className={`w-8 h-8 ${
              isDokter ? 'text-yellow-400' : 'text-yellow-500'
            }`} />
          </div>
        </div>

        {/* Growth/Completion */}
        <div className={`p-4 rounded-lg ${
          isDokter 
            ? 'bg-purple-900/30 border border-purple-500/20' 
            : 'bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800'
        }`}>
          <div className="flex items-center justify-between">
            <div>
              <p className={`text-sm font-medium ${
                isDokter ? 'text-purple-300' : 'text-purple-600 dark:text-purple-400'
              }`}>
                {isDokter ? 'Growth' : 'Completion'}
              </p>
              <div className="flex items-center gap-2">
                <p className={`text-2xl font-bold ${
                  isDokter ? 'text-white' : 'text-gray-900 dark:text-white'
                }`}>
                  {isDokter ? `${growthPercent > 0 ? '+' : ''}${growthPercent}%` : `${completionPercentage}%`}
                </p>
                {isDokter && GrowthIcon && (
                  <GrowthIcon className={`w-6 h-6 ${growthColor}`} />
                )}
              </div>
            </div>
            <Target className={`w-8 h-8 ${
              isDokter ? 'text-purple-400' : 'text-purple-500'
            }`} />
          </div>
        </div>
      </motion.div>

      {/* Progress Section */}
      <motion.div className="space-y-4" variants={itemVariants}>
        {/* Period Progress */}
        <div>
          <div className="flex items-center justify-between mb-2">
            <span className={`text-sm font-medium ${
              isDokter ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'
            }`}>
              Month Progress
            </span>
            <span className={`text-sm ${
              isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
            }`}>
              {currentPeriod.days_passed}/{currentPeriod.days_in_month} days
            </span>
          </div>
          <div className={`w-full bg-gray-200 rounded-full h-2 ${
            isDokter ? 'dark:bg-gray-700' : 'dark:bg-gray-700'
          }`}>
            <div
              className={`h-2 rounded-full transition-all duration-500 ${
                isDokter 
                  ? 'bg-gradient-to-r from-blue-500 to-purple-500' 
                  : 'bg-blue-600 dark:bg-blue-500'
              }`}
              style={{ width: `${currentPeriod.month_progress}%` }}
            ></div>
          </div>
        </div>

        {/* Completion Progress */}
        <div>
          <div className="flex items-center justify-between mb-2">
            <span className={`text-sm font-medium ${
              isDokter ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'
            }`}>
              {isDokter ? 'Quest Completion' : 'Validation Progress'}
            </span>
            <span className={`text-sm ${
              isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
            }`}>
              {summary.count.approved}/{summary.count.total} items
            </span>
          </div>
          <div className={`w-full bg-gray-200 rounded-full h-2 ${
            isDokter ? 'dark:bg-gray-700' : 'dark:bg-gray-700'
          }`}>
            <div
              className={`h-2 rounded-full transition-all duration-500 ${
                isDokter 
                  ? 'bg-gradient-to-r from-green-500 to-emerald-500' 
                  : 'bg-green-600 dark:bg-green-500'
              }`}
              style={{ width: `${completionPercentage}%` }}
            ></div>
          </div>
        </div>
      </motion.div>

      {/* Additional Dashboard Data */}
      {dashboardData && (
        <motion.div 
          className={`mt-6 pt-6 border-t ${
            isDokter ? 'border-slate-700' : 'border-gray-200 dark:border-gray-700'
          }`}
          variants={itemVariants}
        >
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 text-center">
            {dashboardData.daily_average && (
              <div>
                <p className={`text-2xl font-bold ${
                  isDokter ? 'text-blue-400' : 'text-blue-600 dark:text-blue-400'
                }`}>
                  {formatCurrency(dashboardData.daily_average)}
                </p>
                <p className={`text-sm ${
                  isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
                }`}>
                  Daily Average
                </p>
              </div>
            )}
            
            {dashboardData.jaspel_weekly && (
              <div>
                <p className={`text-2xl font-bold ${
                  isDokter ? 'text-green-400' : 'text-green-600 dark:text-green-400'
                }`}>
                  {formatCurrency(dashboardData.jaspel_weekly)}
                </p>
                <p className={`text-sm ${
                  isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
                }`}>
                  Weekly Total
                </p>
              </div>
            )}
            
            {dashboardData.attendance_rate && (
              <div>
                <p className={`text-2xl font-bold ${
                  isDokter ? 'text-yellow-400' : 'text-yellow-600 dark:text-yellow-400'
                }`}>
                  {dashboardData.attendance_rate}%
                </p>
                <p className={`text-sm ${
                  isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
                }`}>
                  Attendance
                </p>
              </div>
            )}
            
            {dashboardData.shifts_this_month && (
              <div>
                <p className={`text-2xl font-bold ${
                  isDokter ? 'text-purple-400' : 'text-purple-600 dark:text-purple-400'
                }`}>
                  {dashboardData.shifts_this_month}
                </p>
                <p className={`text-sm ${
                  isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
                }`}>
                  Shifts This Month
                </p>
              </div>
            )}
          </div>
        </motion.div>
      )}
    </motion.div>
  );
};

/**
 * Loading skeleton for the summary dashboard
 */
const JaspelSummaryDashboardSkeleton: React.FC<{
  variant: JaspelVariant;
  className?: string;
}> = ({ variant, className = '' }) => {
  const isDokter = variant === 'dokter';
  
  const containerClasses = [
    'rounded-xl p-6 animate-pulse',
    isDokter 
      ? 'bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50' 
      : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800',
    className
  ].filter(Boolean).join(' ');

  const shimmerClasses = isDokter 
    ? 'bg-slate-700 animate-pulse'
    : 'bg-gray-200 dark:bg-gray-700 animate-pulse';

  return (
    <div className={containerClasses}>
      {/* Header skeleton */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <div className={`h-8 ${shimmerClasses} rounded mb-2`} style={{ width: '200px' }}></div>
          <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '120px' }}></div>
        </div>
        <div className={`h-9 w-9 ${shimmerClasses} rounded-lg`}></div>
      </div>

      {/* Achievement section skeleton (dokter only) */}
      {isDokter && (
        <div className="mb-6 p-4 bg-purple-900/30 rounded-lg border border-purple-500/20">
          <div className="flex items-center justify-between">
            <div>
              <div className={`h-6 ${shimmerClasses} rounded mb-2`} style={{ width: '150px' }}></div>
              <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '200px' }}></div>
            </div>
            <div className={`h-8 w-8 ${shimmerClasses} rounded`}></div>
          </div>
        </div>
      )}

      {/* Stats grid skeleton */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {Array.from({ length: 4 }, (_, i) => (
          <div key={i} className="p-4 rounded-lg bg-opacity-30">
            <div className="flex items-center justify-between">
              <div>
                <div className={`h-4 ${shimmerClasses} rounded mb-2`} style={{ width: '80px' }}></div>
                <div className={`h-8 ${shimmerClasses} rounded`} style={{ width: '100px' }}></div>
              </div>
              <div className={`h-8 w-8 ${shimmerClasses} rounded`}></div>
            </div>
          </div>
        ))}
      </div>

      {/* Progress section skeleton */}
      <div className="space-y-4">
        {Array.from({ length: 2 }, (_, i) => (
          <div key={i}>
            <div className="flex items-center justify-between mb-2">
              <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '120px' }}></div>
              <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '80px' }}></div>
            </div>
            <div className={`w-full h-2 ${shimmerClasses} rounded-full`}></div>
          </div>
        ))}
      </div>
    </div>
  );
};

export { JaspelSummaryDashboard, JaspelSummaryDashboardSkeleton };
export default JaspelSummaryDashboard;