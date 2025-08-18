/**
 * Unified Jaspel Display Component
 * Main orchestrator component that brings together all Jaspel functionality
 */

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { 
  Search, 
  Filter, 
  Grid, 
  List, 
  Download, 
  RefreshCw,
  Settings,
  Bell,
  Calendar,
  ChevronDown,
  X
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  BaseJaspelItem, 
  JaspelSummary, 
  DashboardData,
  JaspelVariant,
  JaspelStatus,
  RealtimeNotification,
  isDokterJaspelItem
} from '../../lib/jaspel/types';
import { 
  sortJaspelByDate, 
  sortJaspelByAmount, 
  filterJaspelByStatus, 
  filterJaspelByDateRange,
  getCurrentPeriod,
  debounce
} from '../../lib/jaspel/utils';
import { useJaspelCache, useJaspelRealtime } from '../../lib/jaspel/hooks';
import { useBadgeManager } from '../../lib/jaspel/useBadgeManager';

// Import the unified components
import JaspelSummaryDashboard from './JaspelSummaryDashboard';
import JaspelCard, { JaspelCardList } from './JaspelCard';
import { JaspelCardSkeletonList } from './JaspelCardSkeleton';
import GamingBadge, { GamingBadgeVariants } from '../ui/GamingBadge';

interface JaspelDisplayProps {
  // Data props
  data: BaseJaspelItem[];
  summary: JaspelSummary;
  variant: JaspelVariant;
  dashboardData?: DashboardData;
  
  // State props
  loading?: boolean;
  error?: string | null;
  
  // Event handlers
  onRefresh?: () => void;
  onItemClick?: (item: BaseJaspelItem) => void;
  onExport?: (data: BaseJaspelItem[]) => void;
  
  // Configuration
  userId?: string;
  enableRealtime?: boolean;
  enableGaming?: boolean;
  defaultViewMode?: 'grid' | 'list';
  showDashboard?: boolean;
  className?: string;
}

type SortOption = 'date' | 'amount' | 'status';
type ViewMode = 'grid' | 'list';

const JaspelDisplay: React.FC<JaspelDisplayProps> = ({
  data,
  summary,
  variant,
  dashboardData,
  loading = false,
  error = null,
  onRefresh,
  onItemClick,
  onExport,
  userId,
  enableRealtime = true,
  enableGaming = true,
  defaultViewMode = 'grid',
  showDashboard = true,
  className = ''
}) => {
  const isDokter = variant === 'dokter';
  
  // View state
  const [viewMode, setViewMode] = useState<ViewMode>(defaultViewMode);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<JaspelStatus | 'all'>('all');
  const [sortBy, setSortBy] = useState<SortOption>('date');
  const [showFilters, setShowFilters] = useState(false);
  const [selectedDateRange, setSelectedDateRange] = useState<{
    start: string;
    end: string;
  } | null>(null);

  // Hooks
  const cache = useJaspelCache();
  const realtime = useJaspelRealtime(userId || '', variant);
  const badgeManager = useBadgeManager({
    variant,
    enableAnimations: enableGaming
  });

  // Debounced search
  const debouncedSearch = useMemo(
    () => debounce((query: string) => setSearchQuery(query), 300),
    []
  );

  // Filtered and sorted data
  const processedData = useMemo(() => {
    let result = [...data];

    // Apply search filter
    if (searchQuery) {
      result = result.filter(item => 
        item.jenis.toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.keterangan?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (isDokterJaspelItem(item) && item.tindakan?.toLowerCase().includes(searchQuery.toLowerCase()))
      );
    }

    // Apply status filter
    if (statusFilter !== 'all') {
      result = filterJaspelByStatus(result, statusFilter);
    }

    // Apply date range filter
    if (selectedDateRange) {
      result = filterJaspelByDateRange(result, selectedDateRange.start, selectedDateRange.end);
    }

    // Apply sorting
    switch (sortBy) {
      case 'date':
        result = sortJaspelByDate(result);
        break;
      case 'amount':
        result = sortJaspelByAmount(result);
        break;
      case 'status':
        result = result.sort((a, b) => a.status.localeCompare(b.status));
        break;
    }

    return result;
  }, [data, searchQuery, statusFilter, selectedDateRange, sortBy]);

  // Handle real-time notifications
  useEffect(() => {
    if (enableRealtime && realtime.notifications.length > 0) {
      const latestNotification = realtime.notifications[0];
      
      if (latestNotification.type === 'success' && latestNotification.title.includes('Jaspel')) {
        badgeManager.showGamingBadge('goldEarned', {
          autoHide: true,
          customText: 'Data Updated!'
        });
      }
    }
  }, [realtime.notifications, enableRealtime, badgeManager]);

  // Handle search input
  const handleSearchChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    debouncedSearch(e.target.value);
  }, [debouncedSearch]);

  // Handle refresh
  const handleRefresh = useCallback(() => {
    if (onRefresh) {
      onRefresh();
      badgeManager.showGamingBadge('questPending', {
        autoHide: true,
        customText: 'Refreshing...'
      });
    }
  }, [onRefresh, badgeManager]);

  // Handle export
  const handleExport = useCallback(() => {
    if (onExport) {
      onExport(processedData);
      badgeManager.showGamingBadge('rewardClaimed', {
        autoHide: true,
        customText: 'Data Exported!'
      });
    }
  }, [onExport, processedData, badgeManager]);

  // Container styling
  const containerClasses = [
    'space-y-6',
    className
  ].filter(Boolean).join(' ');

  return (
    <div className={containerClasses}>
      {/* Real-time Notifications */}
      <AnimatePresence>
        {enableRealtime && realtime.notifications.length > 0 && (
          <motion.div
            initial={{ opacity: 0, y: -50 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -50 }}
            className="fixed top-4 right-4 z-50 space-y-2"
          >
            {realtime.notifications.slice(0, 3).map((notification) => (
              <div
                key={notification.id}
                className={`p-4 rounded-lg shadow-lg border ${
                  isDokter 
                    ? 'bg-slate-800 border-slate-700 text-white' 
                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                }`}
              >
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-semibold">{notification.title}</h4>
                    <p className="text-sm opacity-80">{notification.message}</p>
                  </div>
                  <Bell className="w-5 h-5 text-blue-500" />
                </div>
              </div>
            ))}
          </motion.div>
        )}
      </AnimatePresence>

      {/* Gaming Badges */}
      <AnimatePresence>
        {enableGaming && badgeManager.activeBadges.length > 0 && (
          <motion.div
            initial={{ opacity: 0, scale: 0.8 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.8 }}
            className="fixed top-20 right-4 z-40 space-y-2"
          >
            {badgeManager.activeBadges.map(({ id, config }) => (
              <GamingBadge
                key={id}
                config={config}
                variant={variant}
                size="md"
                onClick={() => badgeManager.removeBadge(id)}
              />
            ))}
          </motion.div>
        )}
      </AnimatePresence>

      {/* Dashboard Summary */}
      {showDashboard && (
        <JaspelSummaryDashboard
          summary={summary}
          variant={variant}
          dashboardData={dashboardData}
          loading={loading}
          onRefresh={handleRefresh}
        />
      )}

      {/* Controls Section */}
      <div className={`flex flex-col sm:flex-row gap-4 p-4 rounded-lg ${
        isDokter 
          ? 'bg-slate-900/50 border border-slate-700' 
          : 'bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700'
      }`}>
        {/* Search */}
        <div className="flex-1 relative">
          <Search className={`absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 ${
            isDokter ? 'text-slate-400' : 'text-gray-400'
          }`} />
          <input
            type="text"
            placeholder="Search jaspel..."
            onChange={handleSearchChange}
            className={`w-full pl-10 pr-4 py-2 rounded-lg border ${
              isDokter 
                ? 'bg-slate-800 border-slate-600 text-white placeholder-slate-400' 
                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600'
            }`}
          />
        </div>

        {/* View Mode Toggle */}
        <div className={`flex rounded-lg border ${
          isDokter ? 'border-slate-600' : 'border-gray-300 dark:border-gray-600'
        }`}>
          <button
            onClick={() => setViewMode('grid')}
            className={`p-2 ${
              viewMode === 'grid'
                ? isDokter ? 'bg-blue-600 text-white' : 'bg-blue-500 text-white'
                : isDokter ? 'text-slate-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            <Grid className="w-4 h-4" />
          </button>
          <button
            onClick={() => setViewMode('list')}
            className={`p-2 ${
              viewMode === 'list'
                ? isDokter ? 'bg-blue-600 text-white' : 'bg-blue-500 text-white'
                : isDokter ? 'text-slate-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            <List className="w-4 h-4" />
          </button>
        </div>

        {/* Action Buttons */}
        <div className="flex gap-2">
          <button
            onClick={() => setShowFilters(!showFilters)}
            className={`p-2 rounded-lg transition-colors ${
              isDokter 
                ? 'text-slate-400 hover:text-white hover:bg-slate-700' 
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            }`}
          >
            <Filter className="w-4 h-4" />
          </button>
          
          {onExport && (
            <button
              onClick={handleExport}
              className={`p-2 rounded-lg transition-colors ${
                isDokter 
                  ? 'text-slate-400 hover:text-white hover:bg-slate-700' 
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
              }`}
            >
              <Download className="w-4 h-4" />
            </button>
          )}
          
          <button
            onClick={handleRefresh}
            disabled={loading}
            className={`p-2 rounded-lg transition-colors ${
              loading ? 'animate-spin' : ''
            } ${
              isDokter 
                ? 'text-slate-400 hover:text-white hover:bg-slate-700' 
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            }`}
          >
            <RefreshCw className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Filters Panel */}
      <AnimatePresence>
        {showFilters && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className={`p-4 rounded-lg border ${
              isDokter 
                ? 'bg-slate-900/50 border-slate-700' 
                : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700'
            }`}
          >
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {/* Status Filter */}
              <div>
                <label className={`block text-sm font-medium mb-2 ${
                  isDokter ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'
                }`}>
                  Status
                </label>
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value as JaspelStatus | 'all')}
                  className={`w-full p-2 rounded-lg border ${
                    isDokter 
                      ? 'bg-slate-800 border-slate-600 text-white' 
                      : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600'
                  }`}
                >
                  <option value="all">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="disetujui">Approved</option>
                  <option value="ditolak">Rejected</option>
                </select>
              </div>

              {/* Sort By */}
              <div>
                <label className={`block text-sm font-medium mb-2 ${
                  isDokter ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'
                }`}>
                  Sort By
                </label>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value as SortOption)}
                  className={`w-full p-2 rounded-lg border ${
                    isDokter 
                      ? 'bg-slate-800 border-slate-600 text-white' 
                      : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600'
                  }`}
                >
                  <option value="date">Date</option>
                  <option value="amount">Amount</option>
                  <option value="status">Status</option>
                </select>
              </div>

              {/* Reset Filters */}
              <div className="flex items-end">
                <button
                  onClick={() => {
                    setStatusFilter('all');
                    setSortBy('date');
                    setSelectedDateRange(null);
                    setSearchQuery('');
                  }}
                  className={`w-full p-2 rounded-lg transition-colors ${
                    isDokter 
                      ? 'bg-slate-700 text-white hover:bg-slate-600' 
                      : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600'
                  }`}
                >
                  Reset Filters
                </button>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Error Display */}
      {error && (
        <div className={`p-4 rounded-lg border ${
          isDokter 
            ? 'bg-red-900/30 border-red-500/30 text-red-400' 
            : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400'
        }`}>
          <div className="flex items-center justify-between">
            <p>{error}</p>
            <button
              onClick={handleRefresh}
              className="ml-4 text-sm underline hover:no-underline"
            >
              Try Again
            </button>
          </div>
        </div>
      )}

      {/* Data Display */}
      {loading ? (
        <JaspelCardSkeletonList
          variant={variant}
          compact={viewMode === 'grid'}
          count={6}
        />
      ) : (
        <>
          {/* Results Summary */}
          <div className={`text-sm ${
            isDokter ? 'text-slate-400' : 'text-gray-600 dark:text-gray-400'
          }`}>
            Showing {processedData.length} of {data.length} items
            {searchQuery && ` for "${searchQuery}"`}
          </div>

          {/* Data List */}
          {processedData.length > 0 ? (
            <JaspelCardList
              items={processedData}
              variant={variant}
              onItemClick={onItemClick}
              compact={viewMode === 'grid'}
              animated={true}
            />
          ) : (
            <div className={`text-center py-12 ${
              isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
            }`}>
              <Calendar className="w-12 h-12 mx-auto mb-4 opacity-50" />
              <p className="text-lg font-medium mb-2">No Data Found</p>
              <p className="text-sm">
                {searchQuery || statusFilter !== 'all' 
                  ? 'Try adjusting your filters or search query'
                  : 'No jaspel data available for this period'
                }
              </p>
            </div>
          )}
        </>
      )}

      {/* Real-time Connection Status */}
      {enableRealtime && (
        <div className={`fixed bottom-4 right-4 text-xs ${
          isDokter ? 'text-slate-400' : 'text-gray-500'
        }`}>
          <div className="flex items-center gap-2">
            <div className={`w-2 h-2 rounded-full ${
              realtime.connected ? 'bg-green-500 animate-pulse' : 'bg-red-500'
            }`}></div>
            {realtime.connected ? 'Connected' : 'Disconnected'}
          </div>
        </div>
      )}
    </div>
  );
};

export default JaspelDisplay;