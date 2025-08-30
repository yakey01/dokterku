import React, { useEffect, useCallback, useRef, useState } from 'react';
import { cacheManager } from '../../../utils/CacheManager';

// FIXED: Add debounce utility to prevent rapid cache updates
const debounce = (func: Function, delay: number) => {
  let timeoutId: NodeJS.Timeout;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(null, args), delay);
  };
};

/**
 * Real-time Manajer Dashboard hook with WebSocket integration
 * Provides live updates for financial, attendance, and approval data
 */
export const useRealtimeManajerDashboard = () => {
  const [connectionStatus, setConnectionStatus] = useState({
    connected: false,
    health: 'connecting',
    lastUpdate: null as Date | null
  });

  const [notifications, setNotifications] = useState(0);
  const isInitialized = useRef(false);
  const lastUpdateTimes = useRef({
    financial: 0,
    attendance: 0,
    jaspel: 0,
    approvals: 0
  });

  // FIXED: Add debounced cache update functions to prevent render storms
  const debouncedCacheUpdate = useRef({
    financial: debounce((data: any) => {
      // FIXED: Targeted cache updates instead of mass invalidation
      if (data.finance_overview) {
        cacheManager.update('finance-overview', data.finance_overview, 'websocket');
      }
      if (data.today_stats) {
        cacheManager.update('manajer_today_stats', data.today_stats, 'websocket');
      }
      if (data.recent_transactions) {
        cacheManager.update('recent-transactions', data.recent_transactions, 'websocket');
      }
    }, 300),
    
    jaspel: debounce((data: any) => {
      if (data.jaspel_summary) {
        cacheManager.update('jaspel-summary', data.jaspel_summary, 'websocket');
      }
      if (data.doctor_ranking) {
        cacheManager.update('doctor-ranking', data.doctor_ranking, 'websocket');
      }
      // Only invalidate today stats if significant revenue impact
      if (data.revenue_impact && Math.abs(data.revenue_impact) > 100000) {
        cacheManager.invalidate('manajer_today_stats');
      }
    }, 300),
    
    attendance: debounce((data: any) => {
      if (data.attendance_today) {
        cacheManager.update('attendance-today', data.attendance_today, 'websocket');
      }
      if (data.attendance_trends) {
        cacheManager.update('attendance-trends', data.attendance_trends, 'websocket');
      }
      // Only update today stats if attendance rate changed significantly
      if (data.rate_change && Math.abs(data.rate_change) > 5) {
        cacheManager.invalidate('manajer_today_stats');
      }
    }, 300)
  });

  // WebSocket connection and event handling
  const wsRef = useRef<WebSocket | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const maxReconnectAttempts = 5;
  const reconnectAttempts = useRef(0);

  /**
   * Show notification to user
   */
  const showNotification = useCallback((notification: {
    title: string;
    message: string;
    type: 'success' | 'warning' | 'error' | 'info';
  }) => {
    // Request permission first if needed
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
          new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
            tag: `manajer-${Date.now()}`
          });
        }
      });
    } else if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title, {
        body: notification.message,
        icon: '/favicon.ico',
        tag: `manajer-${Date.now()}`
      });
    }
    
    // Console notification always works
    console.log(`🔔 ${notification.type.toUpperCase()}: ${notification.title} - ${notification.message}`);
  }, []);

  /**
   * FIXED: Handle JASPEL real-time updates with debouncing
   */
  const handleJaspelUpdate = useCallback((data: any) => {
    console.log('💰 JASPEL Update received:', data);
    
    try {
      // FIXED: Use debounced cache updates
      debouncedCacheUpdate.current.jaspel(data);
      
      // Show notification for high-value JASPEL
      if (data.jaspel?.nominal > 500000) {
        showNotification({
          title: '💰 JASPEL Update',
          message: `${data.jaspel.user_name}: Rp ${data.jaspel.nominal.toLocaleString('id-ID')}`,
          type: 'success'
        });
      }
      
    } catch (error) {
      console.error('❌ Error processing JASPEL update:', error);
    }
  }, [showNotification]);

  /**
   * FIXED: Handle financial real-time updates with debouncing
   */
  const handleFinancialUpdate = useCallback((data: any) => {
    console.log('💳 Financial Update received:', data);
    
    try {
      // FIXED: Use debounced cache updates instead of immediate invalidation
      debouncedCacheUpdate.current.financial(data);
      
      // Show notification for high-value transactions
      if (data.amount > 5000000) {
        showNotification({
          title: '🔔 High-Value Transaction',
          message: `${data.type}: Rp ${data.amount.toLocaleString('id-ID')} requires approval`,
          type: 'warning'
        });
        
        // Increment notification counter
        setNotifications(prev => prev + 1);
      }
      
    } catch (error) {
      console.error('❌ Error processing financial update:', error);
    }
  }, [showNotification]);

  /**
   * FIXED: Handle attendance real-time updates with debouncing
   */
  const handleAttendanceUpdate = useCallback((data: any) => {
    console.log('👥 Attendance Update received:', data);
    
    try {
      // FIXED: Use debounced cache updates
      debouncedCacheUpdate.current.attendance(data);
      
    } catch (error) {
      console.error('❌ Error processing attendance update:', error);
    }
  }, []);

  /**
   * Handle approval queue real-time updates
   */
  const handleApprovalUpdate = useCallback((data: any) => {
    console.log('⚡ Approval Update received:', data);
    
    try {
      // Update approval cache
      cacheManager.update('pending-approvals', data.pending_approvals, 'websocket');
      
      // Show urgent approval notifications
      if (data.approval?.priority >= 4) {
        showNotification({
          title: '🚨 Urgent Approval Required',
          message: `${data.approval.title} - Priority: ${data.approval.priority_label}`,
          type: 'error'
        });
        
        setNotifications(prev => prev + 1);
      }
      
    } catch (error) {
      console.error('❌ Error processing approval update:', error);
    }
  }, [showNotification]);

  /**
   * Handle incoming WebSocket messages
   */
  const handleWebSocketMessage = useCallback((data: any) => {
    const now = Date.now();
    
    // Prevent duplicate processing
    if (data.event && lastUpdateTimes.current[data.event] && 
        now - lastUpdateTimes.current[data.event] < 1000) {
      return;
    }

    console.log('📡 Manager WebSocket update:', data);

    try {
      switch (data.event) {
        case 'jaspel.updated':
          handleJaspelUpdate(data.data);
          lastUpdateTimes.current.jaspel = now;
          break;
          
        case 'financial.updated':
          handleFinancialUpdate(data.data);
          lastUpdateTimes.current.financial = now;
          break;
          
        case 'attendance.updated':
          handleAttendanceUpdate(data.data);
          lastUpdateTimes.current.attendance = now;
          break;
          
        case 'approval.created':
        case 'approval.updated':
          handleApprovalUpdate(data.data);
          lastUpdateTimes.current.approvals = now;
          break;
          
        default:
          console.log('📨 Unhandled WebSocket event:', data.event);
      }
      
      setConnectionStatus(prev => ({ ...prev, lastUpdate: new Date() }));
      
    } catch (error) {
      console.error('❌ Error handling WebSocket message:', error);
    }
  }, [handleJaspelUpdate, handleFinancialUpdate, handleAttendanceUpdate, handleApprovalUpdate]);

  /**
   * Subscribe to manager-specific broadcasting channels
   */
  const subscribeToChannels = useCallback(() => {
    const subscriptions = [
      'financial.updates',
      'management.oversight', 
      'approval.queue',
      'jaspel.updates',
      'attendance.summary'
    ];

    subscriptions.forEach(channel => {
      const subscribeMessage = {
        event: 'pusher:subscribe',
        data: { channel }
      };
      
      if (wsRef.current?.readyState === WebSocket.OPEN) {
        wsRef.current.send(JSON.stringify(subscribeMessage));
        console.log(`📡 Subscribed to channel: ${channel}`);
      }
    });
  }, []);

  /**
   * Schedule WebSocket reconnection
   */
  const scheduleReconnect = useCallback(() => {
    if (reconnectAttempts.current >= maxReconnectAttempts) {
      console.log('❌ Max reconnection attempts reached');
      setConnectionStatus(prev => ({ ...prev, health: 'failed' }));
      return;
    }
    
    const delay = Math.pow(2, reconnectAttempts.current) * 1000; // Exponential backoff
    reconnectAttempts.current++;
    
    console.log(`🔄 Reconnecting in ${delay}ms (attempt ${reconnectAttempts.current})`);
    
    reconnectTimeoutRef.current = setTimeout(() => {
      setConnectionStatus(prev => ({ ...prev, health: 'reconnecting' }));
      initializeWebSocket();
    }, delay);
  }, []);

  /**
   * Initialize WebSocket connection for manager dashboard
   */
  const initializeWebSocket = useCallback(() => {
    try {
      // Disable WebSocket in development mode since we don't have Pusher server running
      if (!process.env.MIX_PUSHER_APP_KEY || process.env.MIX_PUSHER_APP_KEY === '') {
        console.log('🔒 WebSocket disabled - No Pusher app key configured');
        setConnectionStatus({
          connected: false,
          health: 'disabled',
          lastUpdate: null
        });
        return;
      }
      
      // Use Laravel Echo configuration
      const wsUrl = `ws://127.0.0.1:6001/app/${process.env.MIX_PUSHER_APP_KEY}?protocol=7&client=js&version=4.3.1`;
      
      wsRef.current = new WebSocket(wsUrl);
      
      wsRef.current.onopen = () => {
        console.log('🔗 Manajer WebSocket connected');
        setConnectionStatus({
          connected: true,
          health: 'connected',
          lastUpdate: new Date()
        });
        reconnectAttempts.current = 0;
        
        // Subscribe to manager-specific channels
        subscribeToChannels();
      };
      
      wsRef.current.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          handleWebSocketMessage(data);
        } catch (error) {
          console.error('❌ WebSocket message parsing error:', error);
        }
      };
      
      wsRef.current.onclose = () => {
        console.log('🔌 Manajer WebSocket disconnected');
        setConnectionStatus(prev => ({ ...prev, connected: false, health: 'disconnected' }));
        scheduleReconnect();
      };
      
      wsRef.current.onerror = (error) => {
        console.error('❌ Manajer WebSocket error:', error);
        setConnectionStatus(prev => ({ ...prev, health: 'error' }));
      };
      
    } catch (error) {
      console.error('❌ Failed to initialize WebSocket:', error);
      setConnectionStatus(prev => ({ ...prev, health: 'error' }));
    }
  }, [subscribeToChannels, handleWebSocketMessage, scheduleReconnect]);

  /**
   * Manual reconnection
   */
  const reconnect = useCallback(() => {
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
    }
    
    if (wsRef.current) {
      wsRef.current.close();
    }
    
    reconnectAttempts.current = 0;
    initializeWebSocket();
  }, [initializeWebSocket]);

  /**
   * Get connection health indicator
   */
  const getConnectionHealth = useCallback(() => {
    return {
      ...connectionStatus,
      isHealthy: connectionStatus.connected && connectionStatus.health === 'connected',
      canReconnect: reconnectAttempts.current < maxReconnectAttempts,
      reconnectAttempts: reconnectAttempts.current
    };
  }, [connectionStatus]);

  /**
   * Initialize real-time connection
   */
  useEffect(() => {
    if (!isInitialized.current) {
      isInitialized.current = true;
      
      // Initialize WebSocket connection (notification permission will be requested on first notification)
      initializeWebSocket();
    }
    
    // Cleanup on unmount
    return () => {
      if (wsRef.current) {
        wsRef.current.close();
      }
      if (reconnectTimeoutRef.current) {
        clearTimeout(reconnectTimeoutRef.current);
      }
    };
  }, [initializeWebSocket]);

  return {
    // Connection status
    connectionStatus: getConnectionHealth(),
    
    // Notification count
    notifications,
    setNotifications,
    
    // Manual actions
    reconnect,
    
    // Utility functions
    showNotification,
    
    // WebSocket status indicators
    isConnected: connectionStatus.connected,
    health: connectionStatus.health,
    lastUpdate: connectionStatus.lastUpdate
  };
};