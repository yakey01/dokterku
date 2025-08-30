/**
 * WebSocket Manager for Real-Time Dashboard Updates
 * Provides real-time data synchronization for attendance, JASPEL, and leaderboard updates
 */

import React, { useEffect } from 'react';

interface WebSocketMessage {
  type: 'attendance_update' | 'jaspel_update' | 'leaderboard_update' | 'user_notification' | 'attendance.updated';
  data: any;
  timestamp: number;
  userId?: string;
  action?: 'checkin' | 'checkout' | 'update';
}

interface WebSocketSubscription {
  id: string;
  callback: (data: any) => void;
  filter?: (message: WebSocketMessage) => boolean;
}

class WebSocketManager {
  private ws: WebSocket | null = null;
  private subscriptions: Map<string, WebSocketSubscription> = new Map();
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 1000; // Start with 1 second
  private isReconnecting = false;
  private heartbeatInterval: NodeJS.Timeout | null = null;
  private lastHeartbeat = 0;

  constructor(private url: string = '/ws/general') {
    this.connect();
  }

  /**
   * Establish WebSocket connection
   */
  private connect(): void {
    try {
      // Determine WebSocket URL
      const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
      const host = window.location.host;
      const wsUrl = `${protocol}//${host}${this.url}`;
      
      console.log('🔌 WebSocket: Connecting to', wsUrl);
      
      this.ws = new WebSocket(wsUrl);
      this.setupEventHandlers();
      
    } catch (error) {
      console.error('❌ WebSocket: Failed to connect:', error);
      this.scheduleReconnect();
    }
  }

  /**
   * Setup WebSocket event handlers
   */
  private setupEventHandlers(): void {
    if (!this.ws) return;

    this.ws.onopen = () => {
      console.log('✅ WebSocket: Connected successfully');
      this.reconnectAttempts = 0;
      this.isReconnecting = false;
      this.startHeartbeat();
      
      // Send authentication if available
      this.authenticate();
    };

    this.ws.onmessage = (event) => {
      try {
        const message: WebSocketMessage = JSON.parse(event.data);
        this.handleMessage(message);
      } catch (error) {
        console.error('❌ WebSocket: Failed to parse message:', error);
      }
    };

    this.ws.onclose = (event) => {
      console.warn('⚠️ WebSocket: Connection closed', { code: event.code, reason: event.reason });
      this.stopHeartbeat();
      
      if (!this.isReconnecting) {
        this.scheduleReconnect();
      }
    };

    this.ws.onerror = (error) => {
      console.error('❌ WebSocket: Error occurred:', error);
    };
  }

  /**
   * Send authentication token to WebSocket server
   */
  private authenticate(): void {
    const token = this.getAuthToken();
    if (token && this.ws?.readyState === WebSocket.OPEN) {
      this.send({
        type: 'auth',
        token: token
      });
    }
  }

  /**
   * Get authentication token from various sources
   */
  private getAuthToken(): string | null {
    // Try to get token from meta tag
    const metaToken = document.querySelector('meta[name="auth-token"]')?.getAttribute('content');
    if (metaToken) return metaToken;

    // Try to get from localStorage
    const localToken = localStorage.getItem('auth_token');
    if (localToken) return localToken;

    // Try to get from cookie
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
      const [name, value] = cookie.trim().split('=');
      if (name === 'auth_token' || name === 'laravel_session') {
        return value;
      }
    }

    return null;
  }

  /**
   * Handle incoming WebSocket messages
   */
  private handleMessage(message: WebSocketMessage): void {
    console.log('📩 WebSocket: Received message:', message.type, message.data);
    
    // Update last heartbeat for connection health
    this.lastHeartbeat = Date.now();

    // Distribute message to subscribers
    for (const subscription of this.subscriptions.values()) {
      if (!subscription.filter || subscription.filter(message)) {
        try {
          subscription.callback(message.data);
        } catch (error) {
          console.error('❌ WebSocket: Subscription callback error:', error);
        }
      }
    }
  }

  /**
   * Subscribe to WebSocket messages
   */
  subscribe(
    id: string, 
    callback: (data: any) => void, 
    filter?: (message: WebSocketMessage) => boolean
  ): () => void {
    this.subscriptions.set(id, { id, callback, filter });
    
    console.log(`📝 WebSocket: Subscribed to ${id}, total subscriptions: ${this.subscriptions.size}`);
    
    // Return unsubscribe function
    return () => this.unsubscribe(id);
  }

  /**
   * Unsubscribe from WebSocket messages
   */
  unsubscribe(id: string): void {
    this.subscriptions.delete(id);
    console.log(`📝 WebSocket: Unsubscribed from ${id}, remaining: ${this.subscriptions.size}`);
  }

  /**
   * Send message to WebSocket server
   */
  send(data: any): boolean {
    if (this.ws?.readyState === WebSocket.OPEN) {
      try {
        this.ws.send(JSON.stringify(data));
        return true;
      } catch (error) {
        console.error('❌ WebSocket: Failed to send message:', error);
        return false;
      }
    }
    
    console.warn('⚠️ WebSocket: Cannot send message, connection not open');
    return false;
  }

  /**
   * Start heartbeat to maintain connection
   */
  private startHeartbeat(): void {
    this.heartbeatInterval = setInterval(() => {
      if (this.ws?.readyState === WebSocket.OPEN) {
        this.send({ type: 'ping', timestamp: Date.now() });
      }
      
      // Check if connection is stale
      const now = Date.now();
      if (now - this.lastHeartbeat > 30000) { // 30 seconds
        console.warn('⚠️ WebSocket: Connection appears stale, reconnecting...');
        this.reconnect();
      }
    }, 10000); // Send ping every 10 seconds
  }

  /**
   * Stop heartbeat
   */
  private stopHeartbeat(): void {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }

  /**
   * Schedule reconnection attempt
   */
  private scheduleReconnect(): void {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('❌ WebSocket: Max reconnection attempts reached');
      return;
    }

    this.isReconnecting = true;
    this.reconnectAttempts++;
    
    const delay = Math.min(this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1), 30000);
    
    console.log(`🔄 WebSocket: Scheduling reconnection attempt ${this.reconnectAttempts} in ${delay}ms`);
    
    setTimeout(() => {
      if (this.isReconnecting) {
        this.connect();
      }
    }, delay);
  }

  /**
   * Force reconnection
   */
  reconnect(): void {
    if (this.ws) {
      this.ws.close();
    }
    this.isReconnecting = false;
    this.connect();
  }

  /**
   * Get connection status
   */
  getStatus(): { connected: boolean; reconnecting: boolean; attempts: number } {
    return {
      connected: this.ws?.readyState === WebSocket.OPEN,
      reconnecting: this.isReconnecting,
      attempts: this.reconnectAttempts
    };
  }

  /**
   * Clean up WebSocket connection
   */
  disconnect(): void {
    console.log('🔌 WebSocket: Disconnecting...');
    
    this.stopHeartbeat();
    this.subscriptions.clear();
    
    if (this.ws) {
      this.ws.close(1000, 'Client disconnect');
      this.ws = null;
    }
  }
}

// Export singleton instance
export const webSocketManager = new WebSocketManager();

// React hook for WebSocket subscriptions
export const useWebSocket = (
  subscriptionId: string,
  callback: (data: any) => void,
  filter?: (message: WebSocketMessage) => boolean,
  dependencies: React.DependencyList = []
) => {
  useEffect(() => {
    const unsubscribe = webSocketManager.subscribe(subscriptionId, callback, filter);
    return unsubscribe;
  }, dependencies);

  return webSocketManager.getStatus();
};

// Specific hooks for dashboard data
export const useAttendanceUpdates = (callback: (data: any) => void) => {
  return useWebSocket(
    'attendance-updates',
    callback,
    (message) => message.type === 'attendance_update' || message.type === 'attendance.updated',
    [callback]
  );
};

export const useJaspelUpdates = (callback: (data: any) => void) => {
  return useWebSocket(
    'jaspel-updates',
    callback,
    (message) => message.type === 'jaspel_update',
    [callback]
  );
};

export const useLeaderboardUpdates = (callback: (data: any) => void) => {
  return useWebSocket(
    'leaderboard-updates',
    callback,
    (message) => message.type === 'leaderboard_update',
    [callback]
  );
};

// NEW: Specific hook for attendance history real-time updates
export const useAttendanceHistoryUpdates = (callback: (data: any) => void) => {
  return useWebSocket(
    'attendance-history-updates',
    callback,
    (message) => {
      // Listen for attendance.updated events specifically for history tab
      return message.type === 'attendance.updated' && 
             (message.action === 'checkin' || message.action === 'checkout');
    },
    [callback]
  );
};

export default webSocketManager;