import React from 'react';
import { Clock, Search, TrendingUp, Zap } from 'lucide-react';

interface SearchPerformanceIndicatorProps {
  searchTime: number;
  totalResults: number;
  totalSearchableItems: number;
  recentSearches: number;
  className?: string;
}

const SearchPerformanceIndicator: React.FC<SearchPerformanceIndicatorProps> = ({
  searchTime,
  totalResults,
  totalSearchableItems,
  recentSearches,
  className = ''
}) => {
  const getPerformanceColor = (time: number) => {
    if (time < 10) return 'text-green-400';
    if (time < 50) return 'text-yellow-400';
    return 'text-red-400';
  };

  const formatSearchTime = (time: number) => {
    if (time < 1) return '<1ms';
    return `${time.toFixed(1)}ms`;
  };

  return (
    <div className={`bg-gray-800/50 rounded-lg p-3 border border-gray-700 ${className}`}>
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          {/* Search Speed */}
          <div className="flex items-center space-x-2">
            <Zap className={`w-4 h-4 ${getPerformanceColor(searchTime)}`} />
            <div className="text-xs">
              <div className="text-gray-400">Speed</div>
              <div className={`font-semibold ${getPerformanceColor(searchTime)}`}>
                {formatSearchTime(searchTime)}
              </div>
            </div>
          </div>

          {/* Results Count */}
          <div className="flex items-center space-x-2">
            <Search className="w-4 h-4 text-blue-400" />
            <div className="text-xs">
              <div className="text-gray-400">Results</div>
              <div className="font-semibold text-white">{totalResults}</div>
            </div>
          </div>

          {/* Total Items */}
          <div className="flex items-center space-x-2">
            <TrendingUp className="w-4 h-4 text-purple-400" />
            <div className="text-xs">
              <div className="text-gray-400">Total Items</div>
              <div className="font-semibold text-white">{totalSearchableItems}</div>
            </div>
          </div>

          {/* Recent Searches */}
          <div className="flex items-center space-x-2">
            <Clock className="w-4 h-4 text-orange-400" />
            <div className="text-xs">
              <div className="text-gray-400">Recent</div>
              <div className="font-semibold text-white">{recentSearches}</div>
            </div>
          </div>
        </div>

        {/* Performance Badge */}
        <div className={`px-2 py-1 rounded-full text-xs font-medium ${
          searchTime < 10 
            ? 'bg-green-600/20 text-green-400' 
            : searchTime < 50 
              ? 'bg-yellow-600/20 text-yellow-400'
              : 'bg-red-600/20 text-red-400'
        }`}>
          {searchTime < 10 ? 'Fast' : searchTime < 50 ? 'Good' : 'Slow'}
        </div>
      </div>
    </div>
  );
};

export default React.memo(SearchPerformanceIndicator);