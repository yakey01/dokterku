// ============================================
// MANAGER WEBSOCKET REAL-TIME UPDATES
// ============================================

interface ManagerNotification {
  type: 'validation' | 'approval' | 'alert' | 'kpi_update';
  title: string;
  message: string;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  data?: any;
  timestamp: string;
}

interface WebSocketManager {
  connect: () => void;
  disconnect: () => void;
  subscribe: (callback: (notification: ManagerNotification) => void) => void;
  isConnected: boolean;
}

class ManagerWebSocketService implements WebSocketManager {
  private connection: any = null;
  private callbacks: Array<(notification: ManagerNotification) => void> = [];
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 1000;

  get isConnected(): boolean {
    return this.connection && this.connection.readyState === 1;
  }

  connect(): void {
    try {
      // Check for Laravel Echo without Alpine/Livewire conflicts
      if (typeof window !== 'undefined') {
        // Try to use fetch-based polling instead of Echo to avoid conflicts
        console.log('🔄 Using HTTP polling for real-time updates (avoiding Alpine/Livewire conflicts)');
        this.fallbackToPolling();
      }
    } catch (error) {
      console.error('❌ Connection failed:', error);
      this.fallbackToPolling();
    }
  }

  private subscribeToChannels(Echo: any): void {
    // Manager KPI updates
    Echo.channel('manajer.kpi-updates')
      .listen('KPIUpdated', (e: any) => {
        this.notifyCallbacks({
          type: 'kpi_update',
          title: '📊 KPI Updated',
          message: e.message || 'Dashboard metrics have been updated',
          priority: 'medium',
          data: e.data,
          timestamp: new Date().toISOString(),
        });
      });

    // Critical alerts
    Echo.channel('manajer.critical-alerts')
      .listen('CriticalAlert', (e: any) => {
        this.notifyCallbacks({
          type: 'alert',
          title: '🚨 Critical Alert',
          message: e.message,
          priority: 'urgent',
          data: e.data,
          timestamp: new Date().toISOString(),
        });
      });

    // Approval notifications
    Echo.channel('manajer.approval-alerts')
      .listen('ApprovalRequired', (e: any) => {
        this.notifyCallbacks({
          type: 'approval',
          title: '✅ Approval Required',
          message: e.message,
          priority: e.priority || 'medium',
          data: e.data,
          timestamp: new Date().toISOString(),
        });
      });

    // Validation updates
    Echo.channel('validation.updates')
      .listen('ValidationStatusChanged', (e: any) => {
        this.notifyCallbacks({
          type: 'validation',
          title: '🔍 Validation Update',
          message: e.message,
          priority: 'low',
          data: e.data,
          timestamp: new Date().toISOString(),
        });
      });

    // Executive dashboard updates
    Echo.channel('executive.dashboard')
      .listen('DashboardDataUpdated', (e: any) => {
        this.notifyCallbacks({
          type: 'kpi_update',
          title: '📈 Dashboard Updated',
          message: 'New data available for executive dashboard',
          priority: 'medium',
          data: e.data,
          timestamp: new Date().toISOString(),
        });
      });
  }

  private fallbackToPolling(): void {
    // Fallback to HTTP polling if WebSocket is not available
    const pollInterval = setInterval(async () => {
      try {
        const response = await fetch('/api/v2/manajer/notifications');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
          data.data.forEach((notification: ManagerNotification) => {
            this.notifyCallbacks(notification);
          });
        }
      } catch (error) {
        console.error('Polling error:', error);
      }
    }, 30000); // Poll every 30 seconds

    // Store interval ID for cleanup
    this.connection = { 
      readyState: 1, 
      close: () => clearInterval(pollInterval) 
    };
  }

  private notifyCallbacks(notification: ManagerNotification): void {
    this.callbacks.forEach(callback => {
      try {
        callback(notification);
      } catch (error) {
        console.error('Callback error:', error);
      }
    });
  }

  private handleReconnect(): void {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
      
      console.log(`🔄 Attempting reconnect ${this.reconnectAttempts}/${this.maxReconnectAttempts} in ${delay}ms`);
      
      setTimeout(() => {
        this.connect();
      }, delay);
    } else {
      console.error('❌ Max reconnection attempts reached, falling back to polling');
      this.fallbackToPolling();
    }
  }

  subscribe(callback: (notification: ManagerNotification) => void): void {
    this.callbacks.push(callback);
  }

  disconnect(): void {
    if (this.connection) {
      this.connection.close();
      this.connection = null;
    }
    this.callbacks = [];
    console.log('🔌 Manager WebSocket disconnected');
  }
}

// Singleton instance
export const managerWebSocket = new ManagerWebSocketService();

// React Hook for WebSocket integration
export const useManagerNotifications = () => {
  const [notifications, setNotifications] = useState<ManagerNotification[]>([]);
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
    // Subscribe to notifications
    managerWebSocket.subscribe((notification) => {
      setNotifications(prev => [notification, ...prev.slice(0, 49)]); // Keep last 50
      
      // Show browser notification for urgent alerts
      if (notification.priority === 'urgent' && 'Notification' in window) {
        if (Notification.permission === 'granted') {
          new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
            tag: 'manager-alert',
          });
        }
      }
    });

    // Connect to WebSocket
    managerWebSocket.connect();
    setIsConnected(managerWebSocket.isConnected);

    // Check connection status periodically
    const statusInterval = setInterval(() => {
      setIsConnected(managerWebSocket.isConnected);
    }, 5000);

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }

    return () => {
      clearInterval(statusInterval);
      managerWebSocket.disconnect();
    };
  }, []);

  const markAsRead = (index: number) => {
    setNotifications(prev => prev.filter((_, i) => i !== index));
  };

  const clearAllNotifications = () => {
    setNotifications([]);
  };

  const getUnreadCount = () => {
    return notifications.length;
  };

  const getUrgentCount = () => {
    return notifications.filter(n => n.priority === 'urgent').length;
  };

  return {
    notifications,
    isConnected,
    unreadCount: getUnreadCount(),
    urgentCount: getUrgentCount(),
    markAsRead,
    clearAllNotifications,
  };
};

// Notification Sound Utility
export const playNotificationSound = (priority: string) => {
  if (typeof window === 'undefined') return;
  
  try {
    const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
    
    // Different tones for different priorities
    const frequency = priority === 'urgent' ? 800 : priority === 'high' ? 600 : 400;
    const duration = priority === 'urgent' ? 0.3 : 0.15;
    
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
    oscillator.type = 'sine';
    
    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + duration);
  } catch (error) {
    console.log('Audio not available:', error);
  }
};