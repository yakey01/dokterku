import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { RefreshCw, AlertCircle, CheckCircle } from 'lucide-react';

// Context for sharing data across components
interface DataContextType {
  refreshData: () => Promise<void>;
  isRefreshing: boolean;
  lastRefresh: Date | null;
  error: string | null;
  clearError: () => void;
}

const DataContext = createContext<DataContextType | null>(null);

// Hook to use the data context
export const useDataContext = () => {
  const context = useContext(DataContext);
  if (!context) {
    throw new Error('useDataContext must be used within a DataRefreshWrapper');
  }
  return context;
};

interface DataRefreshWrapperProps {
  children: ReactNode;
  refreshInterval?: number; // in milliseconds
  onRefresh?: () => Promise<void>;
  showRefreshButton?: boolean;
  autoRefresh?: boolean;
}

export const DataRefreshWrapper: React.FC<DataRefreshWrapperProps> = ({
  children,
  refreshInterval = 30000, // 30 seconds default
  onRefresh,
  showRefreshButton = true,
  autoRefresh = true
}) => {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [lastRefresh, setLastRefresh] = useState<Date | null>(null);
  const [error, setError] = useState<string | null>(null);

  const refreshData = useCallback(async () => {
    if (!onRefresh) return;
    
    try {
      setIsRefreshing(true);
      setError(null);
      
      await onRefresh();
      setLastRefresh(new Date());
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to refresh data';
      setError(errorMessage);
      console.error('Data refresh error:', err);
    } finally {
      setIsRefreshing(false);
    }
  }, [onRefresh]);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // Auto refresh on interval
  useEffect(() => {
    if (!autoRefresh || !onRefresh) return;

    const interval = setInterval(() => {
      refreshData();
    }, refreshInterval);

    return () => clearInterval(interval);
  }, [autoRefresh, onRefresh, refreshInterval, refreshData]);

  // Initial refresh on mount
  useEffect(() => {
    if (onRefresh) {
      refreshData();
    }
  }, [onRefresh, refreshData]);

  const contextValue: DataContextType = {
    refreshData,
    isRefreshing,
    lastRefresh,
    error,
    clearError
  };

  return (
    <DataContext.Provider value={contextValue}>
      <div className="relative">
        {/* Error Banner */}
        {error && (
          <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
            <div className="flex items-center">
              <AlertCircle className="h-5 w-5 text-red-400 mr-2" />
              <div className="flex-1">
                <p className="text-sm text-red-700">{error}</p>
              </div>
              <button
                onClick={clearError}
                className="text-red-400 hover:text-red-600"
              >
                ×
              </button>
            </div>
          </div>
        )}

        {/* Refresh Status */}
        {showRefreshButton && (
          <div className="flex items-center justify-between mb-4 p-2 bg-gray-50 rounded-lg">
            <div className="flex items-center space-x-2">
              {isRefreshing ? (
                <RefreshCw className="h-4 w-4 text-blue-500 animate-spin" />
              ) : lastRefresh ? (
                <CheckCircle className="h-4 w-4 text-green-500" />
              ) : (
                <div className="h-4 w-4 bg-gray-300 rounded-full" />
              )}
              <span className="text-sm text-gray-600">
                {isRefreshing 
                  ? 'Refreshing...' 
                  : lastRefresh 
                    ? `Last updated: ${lastRefresh.toLocaleTimeString()}`
                    : 'No data loaded'
                }
              </span>
            </div>
            
            <button
              onClick={refreshData}
              disabled={isRefreshing}
              className="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1"
            >
              <RefreshCw className={`h-3 w-3 ${isRefreshing ? 'animate-spin' : ''}`} />
              <span>Refresh</span>
            </button>
          </div>
        )}

        {/* Children */}
        {children}
      </div>
    </DataContext.Provider>
  );
};

// Higher-order component for components that need data refresh
export const withDataRefresh = <P extends object>(
  Component: React.ComponentType<P>,
  refreshConfig?: {
    refreshInterval?: number;
    showRefreshButton?: boolean;
    autoRefresh?: boolean;
  }
) => {
  return (props: P) => {
    const [refreshKey, setRefreshKey] = useState(0);

    const handleRefresh = useCallback(async () => {
      // Force component re-render by updating key
      setRefreshKey(prev => prev + 1);
    }, []);

    return (
      <DataRefreshWrapper
        refreshInterval={refreshConfig?.refreshInterval}
        onRefresh={handleRefresh}
        showRefreshButton={refreshConfig?.showRefreshButton}
        autoRefresh={refreshConfig?.autoRefresh}
      >
        <Component key={refreshKey} {...props} />
      </DataRefreshWrapper>
    );
  };
};

// Utility component for showing loading states
export const LoadingState: React.FC<{ 
  loading: boolean; 
  error?: string | null;
  children: ReactNode;
  fallback?: ReactNode;
}> = ({ loading, error, children, fallback }) => {
  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="flex items-center space-x-2">
          <RefreshCw className="h-5 w-5 animate-spin text-blue-500" />
          <span className="text-gray-600">Loading...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-center">
          <AlertCircle className="h-8 w-8 text-red-500 mx-auto mb-2" />
          <p className="text-red-600">{error}</p>
        </div>
      </div>
    );
  }

  return <>{children}</>;
};

// Utility component for empty states
export const EmptyState: React.FC<{
  title: string;
  description?: string;
  icon?: ReactNode;
  action?: ReactNode;
}> = ({ title, description, icon, action }) => {
  return (
    <div className="flex flex-col items-center justify-center p-8 text-center">
      {icon && <div className="mb-4">{icon}</div>}
      <h3 className="text-lg font-medium text-gray-900 mb-2">{title}</h3>
      {description && (
        <p className="text-gray-500 mb-4 max-w-sm">{description}</p>
      )}
      {action && <div>{action}</div>}
    </div>
  );
};
