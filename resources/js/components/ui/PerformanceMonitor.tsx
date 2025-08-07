/**
 * 🚀 World-Class Performance Monitoring Dashboard
 * 
 * Features:
 * - Real-time performance metrics
 * - Memory usage monitoring
 * - Frame rate tracking
 * - Asset loading performance
 * - ResizeObserver optimization metrics
 * - Interactive charts and visualizations
 * - Responsive design with glassmorphism
 */

import React, { useState, useEffect, useRef } from 'react';

interface PerformanceMetrics {
    fps: number;
    memoryUsage: number;
    totalMemory: number;
    loadTime: number;
    domNodes: number;
    resizeObserverMetrics: {
        activeObservers: number;
        performanceScore: number;
        loopErrors: number;
        averageCallbackTime: number;
    };
    assetMetrics: {
        successRate: number;
        cacheHitRate: number;
        fallbackUsage: number;
        averageLoadTime: number;
        generatedAssets: number;
    };
    networkMetrics: {
        connectionType: string;
        downlink: number;
        effectiveType: string;
        rtt: number;
    };
}

interface PerformanceMonitorProps {
    enabled?: boolean;
    updateInterval?: number;
    showDetailedMetrics?: boolean;
    theme?: 'glass' | 'dark' | 'light';
    position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
    minimizable?: boolean;
}

const PerformanceMonitor: React.FC<PerformanceMonitorProps> = ({
    enabled = true,
    updateInterval = 1000,
    showDetailedMetrics = false,
    theme = 'glass',
    position = 'top-right',
    minimizable = true
}) => {
    const [metrics, setMetrics] = useState<PerformanceMetrics | null>(null);
    const [isMinimized, setIsMinimized] = useState(false);
    const [history, setHistory] = useState<PerformanceMetrics[]>([]);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);
    const frameCountRef = useRef(0);
    const lastTimeRef = useRef(performance.now());

    useEffect(() => {
        if (!enabled) return;

        const collectMetrics = (): PerformanceMetrics => {
            const now = performance.now();
            const deltaTime = now - lastTimeRef.current;
            
            // Calculate FPS
            frameCountRef.current++;
            const fps = deltaTime > 0 ? Math.round(1000 / deltaTime) : 0;
            lastTimeRef.current = now;

            // Memory metrics
            const memory = (performance as any).memory;
            const memoryUsage = memory ? Math.round(memory.usedJSHeapSize / 1024 / 1024) : 0;
            const totalMemory = memory ? Math.round(memory.totalJSHeapSize / 1024 / 1024) : 0;

            // Performance timing
            const loadTime = performance.timing 
                ? performance.timing.loadEventEnd - performance.timing.navigationStart
                : 0;

            // DOM metrics
            const domNodes = document.querySelectorAll('*').length;

            // ResizeObserver metrics
            const resizeObserverMetrics = {
                activeObservers: 0,
                performanceScore: 100,
                loopErrors: 0,
                averageCallbackTime: 0,
                ...(window as any).getResizeObserverMetrics?.() || {}
            };

            // Asset metrics
            const assetMetrics = {
                successRate: 100,
                cacheHitRate: 0,
                fallbackUsage: 0,
                averageLoadTime: 0,
                generatedAssets: 0,
                ...(window as any).getAssetMetrics?.() || {}
            };

            // Network metrics
            const connection = (navigator as any).connection || (navigator as any).mozConnection || (navigator as any).webkitConnection;
            const networkMetrics = {
                connectionType: connection?.type || 'unknown',
                downlink: connection?.downlink || 0,
                effectiveType: connection?.effectiveType || 'unknown',
                rtt: connection?.rtt || 0
            };

            return {
                fps,
                memoryUsage,
                totalMemory,
                loadTime,
                domNodes,
                resizeObserverMetrics,
                assetMetrics,
                networkMetrics
            };
        };

        const updateMetrics = () => {
            const newMetrics = collectMetrics();
            setMetrics(newMetrics);
            
            setHistory(prev => {
                const updated = [...prev, newMetrics];
                return updated.slice(-60); // Keep last 60 data points (1 minute at 1s intervals)
            });
        };

        // Initial collection
        updateMetrics();

        // Set up interval
        intervalRef.current = setInterval(updateMetrics, updateInterval);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [enabled, updateInterval]);

    if (!enabled || !metrics) return null;

    const getThemeStyles = () => {
        const themes = {
            glass: {
                background: 'rgba(255, 255, 255, 0.1)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255, 255, 255, 0.2)',
                color: '#2d3748',
                shadow: '0 8px 32px 0 rgba(31, 38, 135, 0.37)'
            },
            dark: {
                background: 'rgba(26, 32, 44, 0.95)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255, 255, 255, 0.1)',
                color: '#ffffff',
                shadow: '0 8px 32px 0 rgba(0, 0, 0, 0.5)'
            },
            light: {
                background: 'rgba(255, 255, 255, 0.95)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(0, 0, 0, 0.1)',
                color: '#2d3748',
                shadow: '0 8px 32px 0 rgba(0, 0, 0, 0.1)'
            }
        };
        return themes[theme];
    };

    const getPositionStyles = () => {
        const positions = {
            'top-left': { top: '20px', left: '20px' },
            'top-right': { top: '20px', right: '20px' },
            'bottom-left': { bottom: '20px', left: '20px' },
            'bottom-right': { bottom: '20px', right: '20px' }
        };
        return positions[position];
    };

    const getStatusColor = (value: number, thresholds: { good: number; warning: number }) => {
        if (value >= thresholds.good) return '#10b981';
        if (value >= thresholds.warning) return '#f59e0b';
        return '#ef4444';
    };

    const styles = getThemeStyles();
    const positionStyles = getPositionStyles();

    return (
        <div
            style={{
                position: 'fixed',
                ...positionStyles,
                zIndex: 9999,
                minWidth: isMinimized ? '200px' : '320px',
                maxWidth: '400px',
                background: styles.background,
                backdropFilter: styles.backdropFilter,
                border: styles.border,
                borderRadius: '16px',
                boxShadow: styles.shadow,
                color: styles.color,
                fontFamily: 'system-ui, -apple-system, sans-serif',
                fontSize: '14px',
                padding: isMinimized ? '12px' : '16px',
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                transform: isMinimized ? 'scale(0.9)' : 'scale(1)',
                overflow: 'hidden'
            }}
        >
            {/* Header */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: isMinimized ? '0' : '16px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <div
                        style={{
                            width: '8px',
                            height: '8px',
                            borderRadius: '50%',
                            background: getStatusColor(metrics.fps, { good: 50, warning: 30 }),
                            animation: 'pulse 2s infinite'
                        }}
                    />
                    <h3 style={{ margin: '0', fontWeight: '600', fontSize: '16px' }}>
                        🚀 Performance
                    </h3>
                </div>
                
                {minimizable && (
                    <button
                        onClick={() => setIsMinimized(!isMinimized)}
                        style={{
                            background: 'none',
                            border: 'none',
                            color: styles.color,
                            cursor: 'pointer',
                            fontSize: '16px',
                            padding: '4px',
                            borderRadius: '4px',
                            transition: 'opacity 0.2s ease'
                        }}
                        onMouseEnter={(e) => e.currentTarget.style.opacity = '0.7'}
                        onMouseLeave={(e) => e.currentTarget.style.opacity = '1'}
                    >
                        {isMinimized ? '📊' : '📉'}
                    </button>
                )}
            </div>

            {/* Metrics Grid */}
            {!isMinimized && (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '12px' }}>
                    {/* FPS */}
                    <div style={{ padding: '12px', background: 'rgba(255, 255, 255, 0.1)', borderRadius: '8px' }}>
                        <div style={{ fontSize: '12px', opacity: 0.8, marginBottom: '4px' }}>FPS</div>
                        <div
                            style={{
                                fontSize: '20px',
                                fontWeight: 'bold',
                                color: getStatusColor(metrics.fps, { good: 50, warning: 30 })
                            }}
                        >
                            {metrics.fps}
                        </div>
                    </div>

                    {/* Memory */}
                    <div style={{ padding: '12px', background: 'rgba(255, 255, 255, 0.1)', borderRadius: '8px' }}>
                        <div style={{ fontSize: '12px', opacity: 0.8, marginBottom: '4px' }}>Memory</div>
                        <div
                            style={{
                                fontSize: '16px',
                                fontWeight: 'bold',
                                color: getStatusColor(100 - (metrics.memoryUsage / metrics.totalMemory * 100), { good: 70, warning: 50 })
                            }}
                        >
                            {metrics.memoryUsage}MB
                        </div>
                        <div style={{ fontSize: '10px', opacity: 0.6 }}>
                            {metrics.totalMemory}MB total
                        </div>
                    </div>

                    {/* ResizeObserver Score */}
                    <div style={{ padding: '12px', background: 'rgba(255, 255, 255, 0.1)', borderRadius: '8px' }}>
                        <div style={{ fontSize: '12px', opacity: 0.8, marginBottom: '4px' }}>RO Score</div>
                        <div
                            style={{
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: getStatusColor(metrics.resizeObserverMetrics.performanceScore, { good: 80, warning: 60 })
                            }}
                        >
                            {metrics.resizeObserverMetrics.performanceScore}%
                        </div>
                        <div style={{ fontSize: '10px', opacity: 0.6 }}>
                            {metrics.resizeObserverMetrics.loopErrors} errors
                        </div>
                    </div>

                    {/* Asset Success Rate */}
                    <div style={{ padding: '12px', background: 'rgba(255, 255, 255, 0.1)', borderRadius: '8px' }}>
                        <div style={{ fontSize: '12px', opacity: 0.8, marginBottom: '4px' }}>Assets</div>
                        <div
                            style={{
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: getStatusColor(metrics.assetMetrics.successRate, { good: 95, warning: 80 })
                            }}
                        >
                            {Math.round(metrics.assetMetrics.successRate)}%
                        </div>
                        <div style={{ fontSize: '10px', opacity: 0.6 }}>
                            {metrics.assetMetrics.generatedAssets} generated
                        </div>
                    </div>
                </div>
            )}

            {/* Detailed Metrics */}
            {!isMinimized && showDetailedMetrics && (
                <div style={{ marginTop: '16px', padding: '12px', background: 'rgba(255, 255, 255, 0.05)', borderRadius: '8px' }}>
                    <h4 style={{ margin: '0 0 8px 0', fontSize: '14px', fontWeight: '600' }}>Detailed Metrics</h4>
                    <div style={{ fontSize: '12px', lineHeight: '1.4', opacity: 0.8 }}>
                        <div>DOM Nodes: {metrics.domNodes.toLocaleString()}</div>
                        <div>Load Time: {Math.round(metrics.loadTime)}ms</div>
                        <div>RO Callbacks: {metrics.resizeObserverMetrics.averageCallbackTime.toFixed(1)}ms avg</div>
                        <div>Asset Cache: {Math.round(metrics.assetMetrics.cacheHitRate * 100)}%</div>
                        <div>Connection: {metrics.networkMetrics.effectiveType} ({metrics.networkMetrics.downlink}Mbps)</div>
                    </div>
                </div>
            )}

            {/* Mini Chart for History */}
            {!isMinimized && history.length > 10 && (
                <div style={{ marginTop: '16px' }}>
                    <div style={{ fontSize: '12px', opacity: 0.8, marginBottom: '8px' }}>FPS History</div>
                    <div
                        style={{
                            height: '40px',
                            background: 'rgba(255, 255, 255, 0.05)',
                            borderRadius: '4px',
                            position: 'relative',
                            overflow: 'hidden'
                        }}
                    >
                        <svg width="100%" height="40" style={{ position: 'absolute', top: 0, left: 0 }}>
                            <polyline
                                points={history
                                    .slice(-30) // Last 30 data points
                                    .map((m, i) => `${(i / 29) * 100}%,${40 - (m.fps / 60) * 35}`)
                                    .join(' ')}
                                fill="none"
                                stroke={getStatusColor(metrics.fps, { good: 50, warning: 30 })}
                                strokeWidth="2"
                                opacity="0.8"
                            />
                        </svg>
                    </div>
                </div>
            )}

            {/* Quick Actions */}
            {!isMinimized && (
                <div style={{ display: 'flex', gap: '8px', marginTop: '16px' }}>
                    <button
                        onClick={() => {
                            if (window.gc) {
                                window.gc();
                                console.log('🧹 Manual garbage collection triggered');
                            } else {
                                console.warn('Garbage collection not available');
                            }
                        }}
                        style={{
                            flex: 1,
                            padding: '6px 12px',
                            background: 'rgba(16, 185, 129, 0.2)',
                            border: '1px solid rgba(16, 185, 129, 0.3)',
                            borderRadius: '6px',
                            color: styles.color,
                            fontSize: '11px',
                            cursor: 'pointer',
                            transition: 'all 0.2s ease'
                        }}
                        onMouseEnter={(e) => e.currentTarget.style.background = 'rgba(16, 185, 129, 0.3)'}
                        onMouseLeave={(e) => e.currentTarget.style.background = 'rgba(16, 185, 129, 0.2)'}
                    >
                        🧹 GC
                    </button>
                    
                    <button
                        onClick={() => {
                            const report = {
                                timestamp: new Date().toISOString(),
                                metrics: metrics,
                                userAgent: navigator.userAgent,
                                url: window.location.href
                            };
                            console.log('📊 Performance Report:', report);
                            navigator.clipboard?.writeText(JSON.stringify(report, null, 2))
                                .then(() => console.log('Report copied to clipboard'))
                                .catch(() => console.log('Could not copy to clipboard'));
                        }}
                        style={{
                            flex: 1,
                            padding: '6px 12px',
                            background: 'rgba(59, 130, 246, 0.2)',
                            border: '1px solid rgba(59, 130, 246, 0.3)',
                            borderRadius: '6px',
                            color: styles.color,
                            fontSize: '11px',
                            cursor: 'pointer',
                            transition: 'all 0.2s ease'
                        }}
                        onMouseEnter={(e) => e.currentTarget.style.background = 'rgba(59, 130, 246, 0.3)'}
                        onMouseLeave={(e) => e.currentTarget.style.background = 'rgba(59, 130, 246, 0.2)'}
                    >
                        📋 Copy
                    </button>
                </div>
            )}

            <style>
                {`
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.5; }
                    }
                `}
            </style>
        </div>
    );
};

export default PerformanceMonitor;
export type { PerformanceMetrics, PerformanceMonitorProps };