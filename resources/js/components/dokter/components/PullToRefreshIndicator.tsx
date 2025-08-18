import React from 'react';
import { RefreshCw, ArrowDown } from 'lucide-react';

interface PullToRefreshIndicatorProps {
  isRefreshing: boolean;
  progress: number; // 0-1
  isReady: boolean; // true when threshold is reached
  className?: string;
}

const PullToRefreshIndicator: React.FC<PullToRefreshIndicatorProps> = ({
  isRefreshing,
  progress,
  isReady,
  className = ''
}) => {
  const getIndicatorContent = () => {
    if (isRefreshing) {
      return (
        <div className="flex items-center space-x-2">
          <RefreshCw className="w-5 h-5 animate-spin text-blue-400" />
          <span className="text-sm font-medium text-white">Refreshing...</span>
        </div>
      );
    }

    if (isReady) {
      return (
        <div className="flex items-center space-x-2">
          <div className="w-5 h-5 text-green-400">
            <RefreshCw className="w-full h-full" />
          </div>
          <span className="text-sm font-medium text-green-400">Release to refresh</span>
        </div>
      );
    }

    return (
      <div className="flex items-center space-x-2">
        <div 
          className="w-5 h-5 text-gray-400 transition-transform duration-150"
          style={{ 
            transform: `rotate(${progress * 180}deg)`,
            opacity: Math.max(0.3, progress)
          }}
        >
          <ArrowDown className="w-full h-full" />
        </div>
        <span className="text-sm text-gray-400">Pull to refresh</span>
      </div>
    );
  };

  const getBackgroundClass = () => {
    if (isRefreshing) return 'bg-blue-500/20 border-blue-400/30';
    if (isReady) return 'bg-green-500/20 border-green-400/30';
    return 'bg-gray-800/40 border-gray-600/30';
  };

  return (
    <div
      id="pull-refresh-indicator"
      className={`
        fixed top-0 left-1/2 transform -translate-x-1/2 -translate-y-full
        px-6 py-3 rounded-b-xl border-t-0 border-2
        backdrop-blur-sm transition-all duration-200 z-50
        ${getBackgroundClass()}
        ${className}
      `}
      style={{
        transform: `translateX(-50%) translateY(${-40 + (progress * 60)}px)`,
        opacity: Math.min(progress * 2, 1)
      }}
    >
      {getIndicatorContent()}
      
      {/* Progress bar */}
      <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-700/50 rounded-full overflow-hidden">
        <div 
          className={`h-full transition-all duration-150 ${
            isReady ? 'bg-green-400' : 'bg-blue-400'
          }`}
          style={{ width: `${Math.min(progress * 100, 100)}%` }}
        />
      </div>
    </div>
  );
};

export default React.memo(PullToRefreshIndicator);