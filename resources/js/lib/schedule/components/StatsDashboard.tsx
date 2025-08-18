/**
 * StatsDashboard Component
 * Gaming-style statistics dashboard with professional variant support
 */

import React from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent } from '../../ui/card';
import { 
  Activity, 
  CheckCircle, 
  Clock, 
  TrendingUp,
  Zap,
  Target,
  BarChart3,
  AlertTriangle
} from 'lucide-react';

import { StatsDashboardProps } from '../types';

export const StatsDashboard: React.FC<StatsDashboardProps> = ({
  stats,
  variant = 'gaming',
  performanceMetrics,
  className = ''
}) => {
  const totalSchedules = stats.total;
  const completionRate = totalSchedules > 0 ? Math.round((stats.completed / totalSchedules) * 100) : 0;

  // Animation variants
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

  if (variant === 'professional') {
    return (
      <motion.div 
        variants={container}
        initial="hidden"
        animate="show"
        className={`grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 ${className}`}
      >
        {/* Total Schedules */}
        <motion.div variants={item}>
          <Card className="border border-gray-200 dark:border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                    Total Jadwal
                  </p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">
                    {totalSchedules}
                  </p>
                </div>
                <BarChart3 className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>
        </motion.div>

        {/* Completed */}
        <motion.div variants={item}>
          <Card className="border border-gray-200 dark:border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                    Selesai
                  </p>
                  <p className="text-2xl font-bold text-green-600">
                    {stats.completed}
                  </p>
                </div>
                <CheckCircle className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>
        </motion.div>

        {/* Active */}
        <motion.div variants={item}>
          <Card className="border border-gray-200 dark:border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                    Aktif
                  </p>
                  <p className="text-2xl font-bold text-blue-600">
                    {stats.active}
                  </p>
                </div>
                <Activity className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>
        </motion.div>

        {/* Completion Rate */}
        <motion.div variants={item}>
          <Card className="border border-gray-200 dark:border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                    Tingkat Penyelesaian
                  </p>
                  <p className="text-2xl font-bold text-purple-600">
                    {completionRate}%
                  </p>
                </div>
                <TrendingUp className="h-8 w-8 text-purple-600" />
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>
    );
  }

  // Gaming variant (default)
  return (
    <section role="region" aria-labelledby="stats-heading" className={className}>
      <h2 id="stats-heading" className="sr-only">Ringkasan statistik jadwal</h2>
      
      {/* Main Stats Grid */}
      <motion.div 
        variants={container}
        initial="hidden"
        animate="show"
        className="grid grid-cols-3 gap-4 mb-6"
      >
        {/* Upcoming Schedules */}
        <motion.div variants={item}>
          <Card 
            className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-purple-400/20 hover:border-purple-400/40 transition-all duration-300 card-enhanced group"
            role="group"
            aria-labelledby="upcoming-label"
          >
            <CardContent className="p-4 text-center">
              <div className="flex items-center justify-between mb-2">
                <Clock className="w-5 h-5 text-purple-400" aria-hidden="true" />
                <div className="w-4 h-4 bg-purple-300/50 rounded-full animate-pulse" aria-hidden="true"></div>
              </div>
              <div className="text-2xl font-bold text-purple-400" aria-describedby="upcoming-label">
                {stats.upcoming}
              </div>
              <div id="upcoming-label" className="text-xs text-purple-300/80">
                Upcoming Shifts
              </div>
            </CardContent>
          </Card>
        </motion.div>
        
        {/* Completed Schedules */}
        <motion.div variants={item}>
          <Card 
            className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-green-400/20 hover:border-green-400/40 transition-all duration-300 card-enhanced group"
            role="group"
            aria-labelledby="completed-label"
          >
            <CardContent className="p-4 text-center">
              <div className="flex items-center justify-between mb-2">
                <CheckCircle className="w-5 h-5 text-green-400" aria-hidden="true" />
                <div className="w-4 h-4 bg-green-300/50 rounded-full" aria-hidden="true"></div>
              </div>
              <div className="text-2xl font-bold text-green-400" aria-describedby="completed-label">
                {stats.completed}
              </div>
              <div id="completed-label" className="text-xs text-green-300/80">
                Completed
              </div>
            </CardContent>
          </Card>
        </motion.div>
        
        {/* Active Schedules */}
        <motion.div variants={item}>
          <Card 
            className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-cyan-400/20 hover:border-cyan-400/40 transition-all duration-300 card-enhanced group"
            role="group"
            aria-labelledby="active-label"
          >
            <CardContent className="p-4 text-center">
              <div className="flex items-center justify-between mb-2">
                <Activity className="w-5 h-5 text-cyan-400" aria-hidden="true" />
                <div className="w-4 h-4 bg-cyan-300/50 rounded-full animate-pulse" aria-hidden="true"></div>
              </div>
              <div className="text-2xl font-bold text-cyan-400" aria-describedby="active-label">
                {stats.active}
              </div>
              <div id="active-label" className="text-xs text-cyan-300/80">
                Active Now
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>
      
      {/* Epic Progress Bar */}
      <motion.div variants={item}>
        <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10 mb-6">
          <div className="flex items-center justify-between mb-2">
            <div className="flex items-center gap-2">
              <Target className="w-5 h-5 text-white" aria-hidden="true" />
              <span className="text-white font-semibold">Mission Progress</span>
            </div>
            <span className="text-cyan-400 font-bold">
              {stats.completed} / {totalSchedules}
            </span>
          </div>
          
          {/* Progress Bar */}
          <div className="bg-gray-800/50 rounded-full h-3 relative overflow-hidden">
            <motion.div 
              className="absolute inset-0 bg-gradient-to-r from-cyan-500 via-purple-500 to-pink-500 rounded-full"
              initial={{ width: 0 }}
              animate={{ width: `${completionRate}%` }}
              transition={{ duration: 1.5, ease: "easeOut" }}
            >
              <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
            </motion.div>
          </div>
          
          {/* Progress Info */}
          <div className="mt-2 text-xs text-gray-400 flex justify-between">
            <span>🟢 Aktif: {stats.active}</span>
            <span>🔄 Upcoming: {stats.upcoming}</span>
            <span>❌ Expired: {stats.expired}</span>
          </div>
          
          {/* Completion Rate Badge */}
          <div className="mt-2 flex justify-center">
            <div className="bg-gradient-to-r from-purple-500 to-pink-500 rounded-full px-3 py-1">
              <span className="text-white text-xs font-bold">
                {completionRate}% Complete
              </span>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Performance Metrics (if provided) */}
      {performanceMetrics && (
        <motion.div variants={item}>
          <Card className="bg-white/5 backdrop-blur-2xl rounded-2xl border border-orange-400/20 hover:border-orange-400/40 transition-all duration-300">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <Zap className="w-4 h-4 text-orange-400" aria-hidden="true" />
                <span className="text-white font-medium text-sm">Performance Metrics</span>
              </div>
              
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div className="text-center">
                  <div className="text-orange-400 font-bold">
                    {performanceMetrics.apiResponseTime.toFixed(0)}ms
                  </div>
                  <div className="text-orange-300/80">API Response</div>
                </div>
                
                <div className="text-center">
                  <div className="text-orange-400 font-bold">
                    {performanceMetrics.totalRequests > 0 
                      ? ((performanceMetrics.cacheHits / performanceMetrics.totalRequests) * 100).toFixed(0)
                      : 0
                    }%
                  </div>
                  <div className="text-orange-300/80">Cache Hit Rate</div>
                </div>
                
                {performanceMetrics.memoryUsage > 0 && (
                  <div className="text-center">
                    <div className="text-orange-400 font-bold">
                      {performanceMetrics.memoryUsage}MB
                    </div>
                    <div className="text-orange-300/80">Memory Usage</div>
                  </div>
                )}
                
                <div className="text-center">
                  <div className="text-orange-400 font-bold">
                    {performanceMetrics.totalRequests}
                  </div>
                  <div className="text-orange-300/80">Total Requests</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Warning for Low Completion Rate */}
      {completionRate < 50 && totalSchedules > 0 && (
        <motion.div variants={item}>
          <Card className="bg-red-500/10 border border-red-400/30 rounded-2xl">
            <CardContent className="p-4">
              <div className="flex items-center gap-2">
                <AlertTriangle className="w-5 h-5 text-red-400" aria-hidden="true" />
                <span className="text-red-300 font-medium text-sm">
                  Low completion rate detected. Consider reviewing schedule management.
                </span>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}
    </section>
  );
};

/**
 * Compact StatsDashboard for mobile or limited space
 */
export const CompactStatsDashboard: React.FC<StatsDashboardProps> = ({
  stats,
  className = ''
}) => {
  const totalSchedules = stats.total;
  const completionRate = totalSchedules > 0 ? Math.round((stats.completed / totalSchedules) * 100) : 0;

  return (
    <div className={`bg-white/5 backdrop-blur-2xl rounded-xl p-3 border border-white/10 ${className}`}>
      <div className="flex items-center justify-between mb-2">
        <span className="text-white font-medium text-sm">Quick Stats</span>
        <span className="text-cyan-400 font-bold text-sm">{completionRate}%</span>
      </div>
      
      <div className="grid grid-cols-3 gap-2 text-xs text-center">
        <div>
          <div className="text-purple-400 font-bold">{stats.upcoming}</div>
          <div className="text-purple-300/80">Upcoming</div>
        </div>
        <div>
          <div className="text-cyan-400 font-bold">{stats.active}</div>
          <div className="text-cyan-300/80">Active</div>
        </div>
        <div>
          <div className="text-green-400 font-bold">{stats.completed}</div>
          <div className="text-green-300/80">Done</div>
        </div>
      </div>
    </div>
  );
};

export default StatsDashboard;