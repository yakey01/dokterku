import React from 'react';

/**
 * Reusable skeleton components for progressive loading states
 * Provides consistent loading animations across the dashboard
 */

// Base skeleton animation classes
const skeletonClasses = 'animate-pulse bg-gray-600/50 rounded';

// Individual skeleton building blocks
export const SkeletonBox: React.FC<{ className?: string }> = ({ className = '' }) => (
  <div className={`${skeletonClasses} ${className}`}></div>
);

export const SkeletonText: React.FC<{ 
  width?: string; 
  height?: string; 
  className?: string;
}> = ({ 
  width = 'w-32', 
  height = 'h-4', 
  className = '' 
}) => (
  <div className={`${skeletonClasses} ${width} ${height} ${className}`}></div>
);

export const SkeletonCircle: React.FC<{ 
  size?: string; 
  className?: string;
}> = ({ 
  size = 'w-12 h-12', 
  className = '' 
}) => (
  <div className={`${skeletonClasses} rounded-full ${size} ${className}`}></div>
);

// Dashboard-specific skeleton components
export const DashboardHeaderSkeleton: React.FC = () => (
  <div className="px-6 pt-8 pb-6 relative z-10">
    <div className="relative">
      <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
      <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
      <div className="relative p-8">
        {/* Level Badge & Avatar */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center space-x-4">
            <SkeletonBox className="w-20 h-20 rounded-2xl" />
            <div>
              <SkeletonText width="w-48" height="h-8" className="mb-2" />
              <SkeletonText width="w-32" height="h-6" />
            </div>
          </div>
        </div>
        
        {/* Clinic Info */}
        <div className="mb-6">
          <SkeletonText width="w-full" height="h-4" className="mb-2" />
        </div>
        
        {/* Daily Stats */}
        <div className="grid grid-cols-3 gap-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="text-center">
              <SkeletonText width="w-full" height="h-8" className="mb-2" />
              <SkeletonText width="w-full" height="h-4" />
            </div>
          ))}
        </div>
      </div>
    </div>
  </div>
);

export const AnalyticsCardSkeleton: React.FC = () => (
  <div className="px-6 mb-8 relative z-10">
    <SkeletonText width="w-48" height="h-6" className="mb-6 mx-auto" />
    <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
      <div className="space-y-4">
        <SkeletonText width="w-32" height="h-4" className="mb-4" />
        {[1, 2].map((i) => (
          <div key={i} className="p-4 bg-gray-600/20 rounded-2xl border border-gray-500/30">
            <div className="flex items-center space-x-4 mb-3">
              <SkeletonBox className="w-10 h-10 rounded-xl" />
              <div className="flex-1">
                <SkeletonText width="w-32" height="h-4" />
              </div>
              <SkeletonBox className="w-8 h-8 rounded" />
            </div>
            <div className="mb-2">
              <SkeletonText width="w-16" height="h-3" className="mb-1" />
              <SkeletonText width="w-full" height="h-2" />
            </div>
          </div>
        ))}
      </div>
    </div>
  </div>
);

export const LeaderboardSkeleton: React.FC = () => (
  <div className="px-6 pb-32 relative z-10">
    <SkeletonText width="w-48" height="h-6" className="mb-3 mx-auto" />
    
    {/* Month Period Indicator */}
    <div className="text-center mb-6">
      <SkeletonText width="w-32" height="h-6" className="mx-auto mb-2" />
      <SkeletonText width="w-64" height="h-4" className="mx-auto" />
    </div>
    
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <div key={i} className="flex items-center space-x-4 bg-gradient-to-r from-gray-700/30 to-gray-600/30 rounded-2xl p-4 border-2 border-gray-500/30">
          <SkeletonBox className="w-12 h-12 rounded-xl" />
          <div className="flex-1">
            <SkeletonText width="w-32" height="h-5" className="mb-2" />
            <SkeletonText width="w-24" height="h-4" />
          </div>
          <div className="text-right">
            <SkeletonText width="w-20" height="h-6" />
          </div>
        </div>
      ))}
    </div>
  </div>
);

// Comprehensive dashboard skeleton
export const DashboardSkeleton: React.FC = () => (
  <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900">
    <DashboardHeaderSkeleton />
    <AnalyticsCardSkeleton />
    <LeaderboardSkeleton />
  </div>
);

// Loading state with progress indicator
export const ProgressiveLoadingSkeleton: React.FC<{
  loadingPhase: 'initial' | 'dashboard' | 'analytics' | 'leaderboard' | 'complete';
  progress?: number;
}> = ({ loadingPhase, progress = 0 }) => {
  const getLoadingMessage = () => {
    switch (loadingPhase) {
      case 'initial': return 'Memuat dashboard...';
      case 'dashboard': return 'Mengambil data dokter...';
      case 'analytics': return 'Menghitung analytics...';
      case 'leaderboard': return 'Memuat leaderboard...';
      case 'complete': return 'Selesai!';
      default: return 'Memuat...';
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 flex flex-col">
      {/* Progress Header */}
      <div className="px-6 py-4 text-center">
        <div className="text-white text-lg font-semibold mb-2">
          {getLoadingMessage()}
        </div>
        <div className="w-full bg-gray-700/50 rounded-full h-2 mb-4">
          <div 
            className="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-300"
            style={{ width: `${progress}%` }}
          ></div>
        </div>
        <div className="text-gray-400 text-sm">
          {progress}% selesai
        </div>
      </div>

      {/* Skeleton Content */}
      <div className="flex-1">
        <DashboardHeaderSkeleton />
        
        {loadingPhase !== 'initial' && (
          <AnalyticsCardSkeleton />
        )}
        
        {(loadingPhase === 'leaderboard' || loadingPhase === 'complete') && (
          <LeaderboardSkeleton />
        )}
      </div>
    </div>
  );
};

// Hook for managing progressive loading states
export const useProgressiveLoading = () => {
  const [loadingPhase, setLoadingPhase] = React.useState<'initial' | 'dashboard' | 'analytics' | 'leaderboard' | 'complete'>('initial');
  const [progress, setProgress] = React.useState(0);

  const updateProgress = React.useCallback((phase: typeof loadingPhase, progressValue: number) => {
    setLoadingPhase(phase);
    setProgress(progressValue);
  }, []);

  const resetProgress = React.useCallback(() => {
    setLoadingPhase('initial');
    setProgress(0);
  }, []);

  return {
    loadingPhase,
    progress,
    updateProgress,
    resetProgress
  };
};

export default DashboardSkeleton;