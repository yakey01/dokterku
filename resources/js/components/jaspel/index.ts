/**
 * Jaspel Components Barrel Export
 * Unified exports for all Jaspel refactored components
 */

// Main display component
export { default as JaspelDisplay } from './JaspelDisplay';

// Dashboard components
export { 
  default as JaspelSummaryDashboard,
  JaspelSummaryDashboardSkeleton 
} from './JaspelSummaryDashboard';

// Card components
export { 
  default as JaspelCard,
  JaspelCardCompact,
  JaspelCardDetailed,
  JaspelCardList
} from './JaspelCard';

// Skeleton components
export { 
  default as JaspelCardSkeleton,
  JaspelCardSkeletonList 
} from './JaspelCardSkeleton';

// UI components
export { 
  default as GamingBadge,
  GamingBadgeVariants 
} from '../ui/GamingBadge';

// Type exports for convenience
export type {
  BaseJaspelItem,
  DokterJaspelItem,
  ParamedisJaspelItem,
  JaspelSummary,
  DashboardData,
  JaspelVariant,
  JaspelStatus,
  ComplexityLevel,
  BadgeConfig,
  RealtimeNotification,
  JaspelComponentProps,
  UnifiedJaspelItem
} from '../../lib/jaspel/types';

// Utility exports for convenience
export {
  formatCurrency,
  formatDate,
  formatDatePeriod,
  getStatusBadge,
  getComplexityBadge,
  getGamingBadgeConfig,
  calculatePercentageChange,
  calculateCompletionPercentage,
  sortJaspelByDate,
  sortJaspelByAmount,
  filterJaspelByStatus,
  filterJaspelByDateRange,
  getCurrentPeriod,
  calculatePeriodProgress
} from '../../lib/jaspel/utils';

// Hook exports for convenience
export {
  useJaspelPerformanceMonitoring,
  useJaspelCache,
  useJaspelRealtime,
  useJaspelAutoRefresh,
  useJaspelAchievements
} from '../../lib/jaspel/hooks';

export { useBadgeManager } from '../../lib/jaspel/useBadgeManager';

// API exports for convenience
export { 
  JaspelDataManager,
  jaspelDataManager,
  JaspelAPIError 
} from '../../lib/jaspel/api';

// Manager hook exports
export {
  useJaspelManager,
  useJaspelData,
  useJaspelGaming,
  useJaspelRealtime
} from '../../lib/jaspel/useJaspelManager';

// Cache management exports
export {
  jaspelCacheManager,
  EnhancedLRUCache,
  JaspelCacheManager
} from '../../lib/jaspel/cacheManager';

// Error and loading state exports
export {
  JaspelErrorFactory,
  JaspelErrorManager,
  JaspelLoadingManager,
  JaspelStateManager,
  getJaspelStateManager,
  handleJaspelError,
  setJaspelLoading
} from '../../lib/jaspel/errorHandler';

// Error and loading hooks
export {
  useJaspelErrors,
  useJaspelLoading,
  useJaspelState,
  useJaspelApiOperation,
  useJaspelAutoRetry
} from '../../lib/jaspel/useErrorAndLoadingStates';

// Data transformation exports
export {
  JaspelDataTransformer,
  transformJaspelData,
  transformDashboardData
} from '../../lib/jaspel/dataTransformer';