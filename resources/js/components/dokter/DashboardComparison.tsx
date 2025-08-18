import React, { useState, useEffect } from 'react';
import { performanceMonitor } from '../../utils/PerformanceMonitor';
import RefactoredDashboard from './RefactoredDashboard';
import HolisticMedicalDashboard from './HolisticMedicalDashboard';

interface PerformanceMetrics {
  loadTime: number;
  renderTime: number;
  memoryUsage?: number;
  totalApiCalls: number;
}

interface DashboardComparisonProps {
  userData?: {
    name: string;
    email: string;
    role?: string;
  };
  enableComparison?: boolean;
}

const DashboardComparison: React.FC<DashboardComparisonProps> = ({ 
  userData, 
  enableComparison = false 
}) => {
  const [activeVersion, setActiveVersion] = useState<'refactored' | 'original'>('refactored');
  const [metrics, setMetrics] = useState<{
    refactored: PerformanceMetrics | null;
    original: PerformanceMetrics | null;
  }>({
    refactored: null,
    original: null
  });
  const [showMetrics, setShowMetrics] = useState(false);

  // Performance measurement hook
  useEffect(() => {
    const startTime = performance.now();
    
    // Clear previous metrics for fresh measurement
    performanceMonitor.clear();
    
    return () => {
      const endTime = performance.now();
      const loadTime = endTime - startTime;
      
      // Get performance metrics
      const allMetrics = performanceMonitor.getMetrics();
      const apiCalls = allMetrics.filter(m => m.name.includes('api') || m.name.includes('fetch')).length;
      
      // Calculate memory usage if available
      let memoryUsage: number | undefined;
      if ('memory' in performance) {
        const memory = (performance as any).memory;
        memoryUsage = Math.round(memory.usedJSHeapSize / 1024 / 1024); // MB
      }
      
      const newMetrics: PerformanceMetrics = {
        loadTime: Math.round(loadTime * 100) / 100,
        renderTime: allMetrics.reduce((sum, m) => sum + (m.duration || 0), 0),
        memoryUsage,
        totalApiCalls: apiCalls
      };
      
      setMetrics(prev => ({
        ...prev,
        [activeVersion]: newMetrics
      }));
      
      // Log performance results
      console.log(`📊 ${activeVersion} Dashboard Performance:`, newMetrics);
    };
  }, [activeVersion]);

  // Calculate improvement percentage
  const getImprovement = (originalValue: number, refactoredValue: number): string => {
    const improvement = ((originalValue - refactoredValue) / originalValue) * 100;
    return improvement > 0 ? `${improvement.toFixed(1)}% faster` : `${Math.abs(improvement).toFixed(1)}% slower`;
  };

  // Don't show comparison interface in production
  if (!enableComparison) {
    return <RefactoredDashboard userData={userData} />;
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900">
      {/* Performance Comparison Header */}
      <div className="fixed top-0 left-0 right-0 bg-black/80 backdrop-blur-sm z-50 border-b border-gray-700">
        <div className="flex items-center justify-between p-4">
          <div className="flex items-center space-x-4">
            <h1 className="text-white font-bold text-lg">Dashboard Performance Comparison</h1>
            <div className="flex bg-gray-800 rounded-lg p-1">
              <button
                onClick={() => setActiveVersion('refactored')}
                className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                  activeVersion === 'refactored'
                    ? 'bg-green-600 text-white'
                    : 'text-gray-300 hover:text-white'
                }`}
              >
                Refactored (New)
              </button>
              <button
                onClick={() => setActiveVersion('original')}
                className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                  activeVersion === 'original'
                    ? 'bg-red-600 text-white'
                    : 'text-gray-300 hover:text-white'
                }`}
              >
                Original (Old)
              </button>
            </div>
          </div>
          
          <div className="flex items-center space-x-4">
            <button
              onClick={() => setShowMetrics(!showMetrics)}
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
            >
              {showMetrics ? 'Hide Metrics' : 'Show Metrics'}
            </button>
            <button
              onClick={() => {
                performanceMonitor.clear();
                setMetrics({ refactored: null, original: null });
                window.location.reload();
              }}
              className="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition-colors"
            >
              Reset Test
            </button>
          </div>
        </div>
        
        {/* Performance Metrics Panel */}
        {showMetrics && (
          <div className="bg-gray-900/95 border-t border-gray-700 p-4">
            <div className="grid grid-cols-2 gap-6">
              {/* Refactored Metrics */}
              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                <h3 className="text-green-300 font-bold mb-3">Refactored Dashboard</h3>
                {metrics.refactored ? (
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-300">Load Time:</span>
                      <span className="text-white font-mono">{metrics.refactored.loadTime}ms</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-300">Render Time:</span>
                      <span className="text-white font-mono">{metrics.refactored.renderTime.toFixed(2)}ms</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-300">API Calls:</span>
                      <span className="text-white font-mono">{metrics.refactored.totalApiCalls}</span>
                    </div>
                    {metrics.refactored.memoryUsage && (
                      <div className="flex justify-between">
                        <span className="text-gray-300">Memory:</span>
                        <span className="text-white font-mono">{metrics.refactored.memoryUsage}MB</span>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="text-gray-400 text-sm">Switch to Refactored to measure</div>
                )}
              </div>
              
              {/* Original Metrics */}
              <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                <h3 className="text-red-300 font-bold mb-3">Original Dashboard</h3>
                {metrics.original ? (
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-300">Load Time:</span>
                      <span className="text-white font-mono">{metrics.original.loadTime}ms</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-300">Render Time:</span>
                      <span className="text-white font-mono">{metrics.original.renderTime.toFixed(2)}ms</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-300">API Calls:</span>
                      <span className="text-white font-mono">{metrics.original.totalApiCalls}</span>
                    </div>
                    {metrics.original.memoryUsage && (
                      <div className="flex justify-between">
                        <span className="text-gray-300">Memory:</span>
                        <span className="text-white font-mono">{metrics.original.memoryUsage}MB</span>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="text-gray-400 text-sm">Switch to Original to measure</div>
                )}
              </div>
            </div>
            
            {/* Performance Improvements */}
            {metrics.refactored && metrics.original && (
              <div className="mt-4 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                <h3 className="text-blue-300 font-bold mb-3">Performance Improvements</h3>
                <div className="grid grid-cols-3 gap-4 text-sm">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-400">
                      {getImprovement(metrics.original.loadTime, metrics.refactored.loadTime)}
                    </div>
                    <div className="text-gray-300">Load Time</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-400">
                      {getImprovement(metrics.original.renderTime, metrics.refactored.renderTime)}
                    </div>
                    <div className="text-gray-300">Render Time</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-400">
                      {Math.max(0, metrics.original.totalApiCalls - metrics.refactored.totalApiCalls)} fewer
                    </div>
                    <div className="text-gray-300">API Calls</div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
      
      {/* Dashboard Content */}
      <div className={showMetrics ? 'pt-48' : 'pt-20'}>
        {activeVersion === 'refactored' ? (
          <RefactoredDashboard userData={userData} />
        ) : (
          <HolisticMedicalDashboard userData={userData} />
        )}
      </div>
      
      {/* Performance Indicator */}
      <div className="fixed bottom-4 right-4 bg-black/80 backdrop-blur-sm rounded-lg px-4 py-2 text-white z-40">
        <div className="flex items-center space-x-2">
          <div className={`w-3 h-3 rounded-full ${
            activeVersion === 'refactored' ? 'bg-green-500' : 'bg-red-500'
          }`}></div>
          <span className="text-sm font-medium">
            {activeVersion === 'refactored' ? 'Refactored' : 'Original'} Dashboard
          </span>
        </div>
      </div>
    </div>
  );
};

export default DashboardComparison;