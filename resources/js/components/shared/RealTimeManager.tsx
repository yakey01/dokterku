import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { Wifi, WifiOff, Bell, CheckCircle, AlertCircle, Info } from 'lucide-react';

// 🚀 Shared Real-Time Manager for all panels

interface RealTimeContextType {
  connected: boolean;
  notifications: Notification[];
  lastUpdate: string;
  addNotification: (notification: Omit<Notification, 'id' | 'timestamp'>) => void;
  clearNotifications: () => void;
  setupConnection: (userId: string, userRole: string) => void;
}

interface Notification {
  id: number;
  title: string;
  message: string;
  type: 'success' | 'error' | 'info' | 'warning';
  timestamp: string;
}

const RealTimeContext = createContext<RealTimeContextType | undefined>(undefined);

interface RealTimeProviderProps {
  children: ReactNode;
  autoSetup?: boolean;
}

export const RealTimeProvider: React.FC<RealTimeProviderProps> = ({ 
  children, 
  autoSetup = true 
}) => {
  const [connected, setConnected] = useState(false);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [lastUpdate, setLastUpdate] = useState<string>('Never');

  const addNotification = (notification: Omit<Notification, 'id' | 'timestamp'>) => {
    const newNotification: Notification = {
      ...notification,
      id: Date.now(),
      timestamp: new Date().toLocaleTimeString(),
    };
    
    setNotifications(prev => [newNotification, ...prev.slice(0, 4)]); // Keep last 5
    setLastUpdate(newNotification.timestamp);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
      setNotifications(prev => prev.filter(n => n.id !== newNotification.id));
    }, 10000);
  };

  const clearNotifications = () => {
    setNotifications([]);
  };

  const setupConnection = (userId: string, userRole: string) => {
    try {
      if (typeof window !== 'undefined' && window.Echo) {
        console.log(`🔌 RealTimeManager: Setting up connection for ${userRole} user ${userId}...`);
        
        // Role-specific private channel
        window.Echo.private(`${userRole}.${userId}`)
          .listen('tindakan.validated', (event: any) => {
            console.log(`🎯 ${userRole} received validation update:`, event);
            addNotification(event.notification);
            
            // Dispatch custom event for components to handle
            window.dispatchEvent(new CustomEvent(`${userRole}-tindakan-validated`, { 
              detail: event 
            }));
          })
          .listen('jaspel.updated', (event: any) => {
            console.log(`💰 ${userRole} received JASPEL update:`, event);
            addNotification(event.notification);
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent(`${userRole}-jaspel-updated`, { 
              detail: event 
            }));
          });
        
        // Public channels based on role
        if (['dokter', 'paramedis', 'petugas'].includes(userRole)) {
          window.Echo.channel('medical.procedures')
            .listen('tindakan.input.created', (event: any) => {
              console.log(`📝 ${userRole} received new tindakan input:`, event);
              
              // Only notify if relevant to this user
              if (event.tindakan.dokter_user_id == userId || 
                  event.tindakan.paramedis_user_id == userId ||
                  event.tindakan.input_by == userId) {
                addNotification(event.notification);
              }
            });
        }
        
        if (['bendahara', 'manajer', 'admin'].includes(userRole)) {
          window.Echo.channel('financial.updates')
            .listen('jaspel.updated', (event: any) => {
              console.log(`💰 ${userRole} received financial update:`, event);
              addNotification(event.notification);
            });
        }
        
        // Connection status listeners
        window.Echo.connector.pusher.connection.bind('connected', () => {
          console.log(`✅ ${userRole} WebSocket connected`);
          setConnected(true);
        });
        
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
          console.log(`❌ ${userRole} WebSocket disconnected`);
          setConnected(false);
        });
        
      } else {
        console.log(`⚠️ Echo not available for ${userRole}, using polling fallback...`);
        setConnected(false);
      }
    } catch (error) {
      console.error(`❌ Failed to setup ${userRole} WebSocket:`, error);
      setConnected(false);
    }
  };

  // Auto-setup connection if enabled
  useEffect(() => {
    if (autoSetup) {
      const userDataMeta = document.querySelector('meta[name="user-data"]');
      const userIdMeta = document.querySelector('meta[name="user-id"]');
      const userRoleMeta = document.querySelector('meta[name="user-role"]');
      
      let userId = userIdMeta?.getAttribute('content');
      let userRole = userRoleMeta?.getAttribute('content');
      
      // Try to get from user data if not found in separate meta tags
      if (!userId || !userRole) {
        try {
          const userData = JSON.parse(userDataMeta?.getAttribute('content') || '{}');
          userId = userId || userData.id?.toString();
          userRole = userRole || userData.role || userData.roles?.[0]?.name;
        } catch (e) {
          console.error('Error parsing user data for real-time setup:', e);
        }
      }
      
      if (userId && userRole) {
        setupConnection(userId, userRole);
      } else {
        console.warn('⚠️ User ID or role not found for real-time setup');
      }
    }
  }, [autoSetup]);

  const value: RealTimeContextType = {
    connected,
    notifications,
    lastUpdate,
    addNotification,
    clearNotifications,
    setupConnection,
  };

  return (
    <RealTimeContext.Provider value={value}>
      {children}
    </RealTimeContext.Provider>
  );
};

export const useRealTime = (): RealTimeContextType => {
  const context = useContext(RealTimeContext);
  if (context === undefined) {
    throw new Error('useRealTime must be used within a RealTimeProvider');
  }
  return context;
};

// 🚀 Real-Time Status Indicator Component
interface RealTimeStatusProps {
  className?: string;
  showLabel?: boolean;
}

export const RealTimeStatus: React.FC<RealTimeStatusProps> = ({ 
  className = '', 
  showLabel = true 
}) => {
  const { connected, lastUpdate } = useRealTime();
  
  return (
    <div className={`flex items-center gap-1 text-xs ${className}`}>
      {connected ? (
        <>
          <Wifi className="w-3 h-3 text-green-400" />
          {showLabel && <span className="text-green-400">Live</span>}
        </>
      ) : (
        <>
          <WifiOff className="w-3 h-3 text-yellow-400 opacity-50" />
          {showLabel && <span className="text-yellow-400">Polling</span>}
        </>
      )}
      {lastUpdate !== 'Never' && (
        <span className="text-gray-400 ml-1" title={`Last update: ${lastUpdate}`}>
          •
        </span>
      )}
    </div>
  );
};

// 🔔 Real-Time Notifications Component
interface RealTimeNotificationsProps {
  className?: string;
  maxNotifications?: number;
}

export const RealTimeNotifications: React.FC<RealTimeNotificationsProps> = ({ 
  className = '',
  maxNotifications = 3 
}) => {
  const { notifications, clearNotifications } = useRealTime();
  
  if (notifications.length === 0) {
    return null;
  }
  
  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'success': return <CheckCircle className="w-4 h-4 text-green-400" />;
      case 'error': return <AlertCircle className="w-4 h-4 text-red-400" />;
      case 'warning': return <AlertCircle className="w-4 h-4 text-yellow-400" />;
      default: return <Info className="w-4 h-4 text-blue-400" />;
    }
  };
  
  const getNotificationStyle = (type: string) => {
    switch (type) {
      case 'success': return 'bg-green-500/10 border-green-500/30 text-green-300';
      case 'error': return 'bg-red-500/10 border-red-500/30 text-red-300';
      case 'warning': return 'bg-yellow-500/10 border-yellow-500/30 text-yellow-300';
      default: return 'bg-blue-500/10 border-blue-500/30 text-blue-300';
    }
  };

  return (
    <div className={`space-y-3 ${className}`}>
      <div className="flex items-center justify-between">
        <h3 className="text-white font-bold text-lg flex items-center gap-2">
          <Bell className="w-5 h-5 text-blue-400" />
          Real-time Updates
        </h3>
        {notifications.length > 0 && (
          <button 
            onClick={clearNotifications}
            className="text-xs text-gray-400 hover:text-gray-300"
          >
            Clear all
          </button>
        )}
      </div>
      
      {notifications.slice(0, maxNotifications).map((notification) => (
        <div
          key={notification.id}
          className={`p-4 rounded-xl border backdrop-blur-sm transition-all duration-500 ${getNotificationStyle(notification.type)}`}
        >
          <div className="flex items-start justify-between">
            <div className="flex items-start gap-3">
              {getNotificationIcon(notification.type)}
              <div className="flex-1">
                <div className="font-semibold mb-1">{notification.title}</div>
                <div className="text-sm opacity-90">{notification.message}</div>
              </div>
            </div>
            <div className="text-xs opacity-70 ml-4">
              {notification.timestamp}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};