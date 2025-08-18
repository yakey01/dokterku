import React, { useState } from 'react';
import { Wifi, WifiOff, Activity, Database, Clock, Info, RefreshCw } from 'lucide-react';

interface RealtimeStatusIndicatorProps {
  connectionStatus: {
    connected: boolean;
    reconnecting: boolean;
    attempts: number;
    health: 'healthy' | 'recovering' | 'disconnected';
  };
  performanceMetrics: {
    cacheHitRate: number;
    averageResponseTime: number;
    totalRequests: number;
    storageUsage: {
      memory: number;
      localStorage: number;
      total: number;
    };
    memoryEntries: number;
    websocketHealth: string;
  };
  onRefresh: () => void;
  className?: string;
}

const RealtimeStatusIndicator: React.FC<RealtimeStatusIndicatorProps> = ({
  connectionStatus,
  performanceMetrics,
  onRefresh,
  className = ''
}) => {
  const [showDetails, setShowDetails] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const getStatusColor = () => {
    switch (connectionStatus.health) {
      case 'healthy':
        return 'text-green-400';
      case 'recovering':
        return 'text-yellow-400 animate-pulse';
      case 'disconnected':
        return 'text-red-400';
      default:
        return 'text-gray-400';
    }
  };

  const getStatusIcon = () => {
    if (connectionStatus.reconnecting) {
      return <Activity className="w-4 h-4 animate-spin" />;
    }
    
    switch (connectionStatus.health) {
      case 'healthy':
        return <Wifi className="w-4 h-4" />;
      case 'recovering':
        return <Activity className="w-4 h-4" />;
      case 'disconnected':
        return <WifiOff className="w-4 h-4" />;
      default:
        return <WifiOff className="w-4 h-4" />;
    }
  };

  const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  };

  const formatResponseTime = (ms: number): string => {
    return ms < 1000 ? `${Math.round(ms)}ms` : `${(ms / 1000).toFixed(1)}s`;
  };

  const getCacheHealthColor = (): string => {
    const hitRate = performanceMetrics.cacheHitRate;
    if (hitRate >= 80) return 'text-green-400';
    if (hitRate >= 60) return 'text-yellow-400';
    return 'text-red-400';
  };

  const getResponseTimeColor = (): string => {
    const time = performanceMetrics.averageResponseTime;
    if (time <= 200) return 'text-green-400';
    if (time <= 500) return 'text-yellow-400';
    return 'text-red-400';
  };

  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      await onRefresh();
    } finally {
      // Add small delay for better UX
      setTimeout(() => setIsRefreshing(false), 500);
    }
  };

  return (
    <div className={`relative ${className}`}>
      {/* Main Status Indicator */}
      <div className="flex items-center space-x-2">
        {/* Connection Status */}
        <div 
          className={`flex items-center space-x-1 ${getStatusColor()} cursor-pointer transition-colors hover:opacity-80`}
          onClick={() => setShowDetails(!showDetails)}
          title={`WebSocket: ${connectionStatus.health} | Cache: ${performanceMetrics.cacheHitRate.toFixed(1)}% hit rate`}
        >
          {getStatusIcon()}
          <span className="text-xs font-medium hidden sm:inline">
            {connectionStatus.health === 'healthy' ? 'Live' : 
             connectionStatus.health === 'recovering' ? 'Sync...' : 'Offline'}
          </span>
        </div>

        {/* Cache Performance Indicator */}
        <div className={`flex items-center space-x-1 ${getCacheHealthColor()}`}>
          <Database className="w-3 h-3" />
          <span className="text-xs hidden sm:inline">
            {performanceMetrics.cacheHitRate.toFixed(0)}%
          </span>
        </div>

        {/* Response Time Indicator */}
        <div className={`flex items-center space-x-1 ${getResponseTimeColor()}`}>
          <Clock className="w-3 h-3" />
          <span className="text-xs hidden sm:inline">
            {formatResponseTime(performanceMetrics.averageResponseTime)}
          </span>
        </div>

        {/* Refresh Button */}
        <button
          onClick={handleRefresh}
          disabled={isRefreshing}
          className="p-1 text-gray-400 hover:text-white transition-colors disabled:opacity-50"
          title="Force refresh all data"
        >
          <RefreshCw className={`w-3 h-3 ${isRefreshing ? 'animate-spin' : ''}`} />
        </button>

        {/* Details Toggle */}
        <button
          onClick={() => setShowDetails(!showDetails)}
          className="p-1 text-gray-400 hover:text-white transition-colors"
          title="Show detailed metrics"
        >
          <Info className="w-3 h-3" />
        </button>
      </div>

      {/* Detailed Status Panel */}
      {showDetails && (
        <div className="absolute top-full right-0 mt-2 w-80 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50">
          <div className="p-4 space-y-4">
            {/* Header */}
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-semibold text-white">Real-time Status</h3>
              <button
                onClick={() => setShowDetails(false)}
                className="text-gray-400 hover:text-white"
              >
                ×
              </button>
            </div>

            {/* WebSocket Status */}
            <div className="space-y-2">
              <h4 className="text-xs font-medium text-gray-300 flex items-center">
                <Wifi className="w-3 h-3 mr-1" />
                WebSocket Connection
              </h4>
              <div className="grid grid-cols-2 gap-2 text-xs">
                <div className="flex justify-between">
                  <span className="text-gray-400">Status:</span>
                  <span className={getStatusColor()}>
                    {connectionStatus.health}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Reconnects:</span>
                  <span className="text-white">{connectionStatus.attempts}</span>
                </div>
              </div>
            </div>

            {/* Cache Performance */}
            <div className="space-y-2">
              <h4 className="text-xs font-medium text-gray-300 flex items-center">
                <Database className="w-3 h-3 mr-1" />
                Cache Performance
              </h4>
              <div className="grid grid-cols-2 gap-2 text-xs">
                <div className="flex justify-between">
                  <span className="text-gray-400">Hit Rate:</span>
                  <span className={getCacheHealthColor()}>
                    {performanceMetrics.cacheHitRate.toFixed(1)}%
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Requests:</span>
                  <span className="text-white">{performanceMetrics.totalRequests}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Avg Response:</span>
                  <span className={getResponseTimeColor()}>
                    {formatResponseTime(performanceMetrics.averageResponseTime)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Entries:</span>
                  <span className="text-white">{performanceMetrics.memoryEntries}</span>
                </div>
              </div>
            </div>

            {/* Storage Usage */}
            <div className="space-y-2">
              <h4 className="text-xs font-medium text-gray-300">Storage Usage</h4>
              <div className="space-y-1 text-xs">
                <div className="flex justify-between">
                  <span className="text-gray-400">Memory:</span>
                  <span className="text-white">
                    {formatBytes(performanceMetrics.storageUsage.memory)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">LocalStorage:</span>
                  <span className="text-white">
                    {formatBytes(performanceMetrics.storageUsage.localStorage)}
                  </span>
                </div>
                <div className="flex justify-between font-medium">
                  <span className="text-gray-300">Total:</span>
                  <span className="text-white">
                    {formatBytes(performanceMetrics.storageUsage.total)}
                  </span>
                </div>
              </div>
            </div>

            {/* Performance Indicators */}
            <div className="space-y-2">
              <h4 className="text-xs font-medium text-gray-300">Performance</h4>
              <div className="space-y-1">
                {/* Cache Hit Rate Bar */}
                <div className="flex items-center space-x-2">
                  <span className="text-xs text-gray-400 w-16">Cache:</span>
                  <div className="flex-1 bg-gray-700 rounded-full h-2">
                    <div 
                      className={`h-2 rounded-full transition-all duration-300 ${
                        performanceMetrics.cacheHitRate >= 80 ? 'bg-green-400' :
                        performanceMetrics.cacheHitRate >= 60 ? 'bg-yellow-400' : 'bg-red-400'
                      }`}
                      style={{ width: `${Math.min(performanceMetrics.cacheHitRate, 100)}%` }}
                    />
                  </div>
                  <span className="text-xs text-white w-8 text-right">
                    {performanceMetrics.cacheHitRate.toFixed(0)}%
                  </span>
                </div>

                {/* Response Time Indicator */}
                <div className="flex items-center space-x-2">
                  <span className="text-xs text-gray-400 w-16">Speed:</span>
                  <div className="flex-1 bg-gray-700 rounded-full h-2">
                    <div 
                      className={`h-2 rounded-full transition-all duration-300 ${getResponseTimeColor().replace('text-', 'bg-')}`}
                      style={{ 
                        width: `${Math.max(10, Math.min(100 - (performanceMetrics.averageResponseTime / 10), 100))}%` 
                      }}
                    />
                  </div>
                  <span className="text-xs text-white w-12 text-right">
                    {formatResponseTime(performanceMetrics.averageResponseTime)}
                  </span>
                </div>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="pt-2 border-t border-gray-700">
              <button
                onClick={handleRefresh}
                disabled={isRefreshing}
                className="w-full px-3 py-2 text-xs bg-blue-600 hover:bg-blue-700 disabled:bg-blue-800 disabled:opacity-50 text-white rounded-md transition-colors flex items-center justify-center space-x-1"
              >
                <RefreshCw className={`w-3 h-3 ${isRefreshing ? 'animate-spin' : ''}`} />
                <span>{isRefreshing ? 'Refreshing...' : 'Force Refresh'}</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Connection Status Dot for Mobile */}
      <div className="sm:hidden">
        <div className={`w-2 h-2 rounded-full ${
          connectionStatus.health === 'healthy' ? 'bg-green-400' :
          connectionStatus.health === 'recovering' ? 'bg-yellow-400 animate-pulse' :
          'bg-red-400'
        }`} />
      </div>
    </div>
  );
};

export default React.memo(RealtimeStatusIndicator);