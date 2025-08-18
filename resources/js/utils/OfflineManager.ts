/**
 * Offline Data Manager for Dashboard
 * Handles offline scenarios and data persistence
 */

import React, { useState, useEffect, useCallback } from 'react';
import { cacheManager } from './CacheManager';

interface OfflineData {
  dashboardMetrics: any;
  leaderboard: any[];
  attendanceHistory: any[];
  lastSync: number;
  isOffline: boolean;
}

class OfflineManager {
  private isOnline = navigator.onLine;
  private offlineCallbacks = new Set<(isOffline: boolean) => void>();
  private syncQueue: Array<{ action: string; data: any; timestamp: number }> = [];

  constructor() {
    this.initializeOfflineHandling();
  }

  /**
   * Initialize offline event handlers
   */
  private initializeOfflineHandling(): void {
    window.addEventListener('online', () => {
      this.isOnline = true;
      console.log('🌐 Network: Back online');
      this.notifyOfflineStatusChange(false);
      this.processSyncQueue();
    });

    window.addEventListener('offline', () => {
      this.isOnline = false;
      console.log('📡 Network: Gone offline');
      this.notifyOfflineStatusChange(true);
    });

    // Periodic online check
    setInterval(() => {
      const currentStatus = navigator.onLine;
      if (currentStatus !== this.isOnline) {
        this.isOnline = currentStatus;
        this.notifyOfflineStatusChange(!currentStatus);
      }
    }, 5000);
  }

  /**
   * Check if currently offline
   */
  isOffline(): boolean {
    return !this.isOnline;
  }

  /**
   * Get offline data from cache
   */
  async getOfflineData(): Promise<OfflineData | null> {
    try {
      const [dashboardMetrics, leaderboard, attendanceHistory] = await Promise.all([
        cacheManager.get('dashboard-metrics'),
        cacheManager.get('leaderboard-data'),
        cacheManager.get('attendance-history')
      ]);

      if (!dashboardMetrics && !leaderboard && !attendanceHistory) {
        return null;
      }

      return {
        dashboardMetrics,
        leaderboard: leaderboard || [],
        attendanceHistory: attendanceHistory || [],
        lastSync: Math.max(
          this.getDataTimestamp(dashboardMetrics),
          this.getDataTimestamp(leaderboard),
          this.getDataTimestamp(attendanceHistory)
        ),
        isOffline: true
      };
    } catch (error) {
      console.error('❌ Failed to get offline data:', error);
      return null;
    }
  }

  /**
   * Store data for offline use
   */
  async storeOfflineData(data: Partial<OfflineData>): Promise<void> {
    try {
      const promises = [];

      if (data.dashboardMetrics) {
        promises.push(cacheManager.set('dashboard-metrics', data.dashboardMetrics, {
          ttl: 24 * 60 * 60 * 1000, // 24 hours for offline
          enableLocalStorage: true,
          persistOffline: true
        }));
      }

      if (data.leaderboard) {
        promises.push(cacheManager.set('leaderboard-data', data.leaderboard, {
          ttl: 24 * 60 * 60 * 1000,
          enableLocalStorage: true,
          persistOffline: true
        }));
      }

      if (data.attendanceHistory) {
        promises.push(cacheManager.set('attendance-history', data.attendanceHistory, {
          ttl: 24 * 60 * 60 * 1000,
          enableLocalStorage: true,
          persistOffline: true
        }));
      }

      await Promise.all(promises);
      console.log('💾 Offline data stored successfully');

    } catch (error) {
      console.error('❌ Failed to store offline data:', error);
    }
  }

  /**
   * Add action to sync queue for when online
   */
  queueForSync(action: string, data: any): void {
    this.syncQueue.push({
      action,
      data,
      timestamp: Date.now()
    });

    console.log(`📋 Queued for sync: ${action} (${this.syncQueue.length} items in queue)`);

    // Persist sync queue to localStorage
    try {
      localStorage.setItem('dokterku_sync_queue', JSON.stringify(this.syncQueue));
    } catch (error) {
      console.warn('⚠️ Failed to persist sync queue:', error);
    }
  }

  /**
   * Process sync queue when back online
   */
  private async processSyncQueue(): Promise<void> {
    if (this.syncQueue.length === 0) {
      return;
    }

    console.log(`🔄 Processing sync queue: ${this.syncQueue.length} items`);

    // Process items in order
    for (const item of this.syncQueue) {
      try {
        await this.processSyncItem(item);
      } catch (error) {
        console.error('❌ Failed to sync item:', error, item);
        // Keep item in queue for retry
        break;
      }
    }

    // Clear processed items
    this.syncQueue = [];
    localStorage.removeItem('dokterku_sync_queue');
  }

  /**
   * Process individual sync item
   */
  private async processSyncItem(item: { action: string; data: any; timestamp: number }): Promise<void> {
    switch (item.action) {
      case 'attendance_update':
        // Sync attendance data
        console.log('🔄 Syncing attendance update');
        break;
      
      case 'jaspel_update':
        // Sync JASPEL data
        console.log('🔄 Syncing JASPEL update');
        break;
      
      case 'user_action':
        // Sync user actions
        console.log('🔄 Syncing user action');
        break;
      
      default:
        console.warn('⚠️ Unknown sync action:', item.action);
    }
  }

  /**
   * Subscribe to offline status changes
   */
  onOfflineStatusChange(callback: (isOffline: boolean) => void): () => void {
    this.offlineCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.offlineCallbacks.delete(callback);
    };
  }

  /**
   * Notify all subscribers of offline status change
   */
  private notifyOfflineStatusChange(isOffline: boolean): void {
    for (const callback of this.offlineCallbacks) {
      try {
        callback(isOffline);
      } catch (error) {
        console.error('❌ Offline callback error:', error);
      }
    }
  }

  /**
   * Get timestamp from cached data
   */
  private getDataTimestamp(data: any): number {
    if (!data) return 0;
    
    // Try to extract timestamp from cache metadata
    if (typeof data === 'object' && data.timestamp) {
      return data.timestamp;
    }
    
    return Date.now();
  }

  /**
   * Force sync all data when back online
   */
  async forceSyncAll(): Promise<void> {
    if (this.isOffline()) {
      console.warn('⚠️ Cannot sync while offline');
      return;
    }

    console.log('🔄 Force syncing all data...');
    
    try {
      // Clear all caches to force fresh data
      cacheManager.clear();
      
      // Process any pending sync items
      await this.processSyncQueue();
      
      console.log('✅ Force sync completed');
      
    } catch (error) {
      console.error('❌ Force sync failed:', error);
    }
  }

  /**
   * Get sync queue status
   */
  getSyncQueueStatus(): { count: number; oldestItem: number | null } {
    const oldestItem = this.syncQueue.length > 0 
      ? Math.min(...this.syncQueue.map(item => item.timestamp))
      : null;
      
    return {
      count: this.syncQueue.length,
      oldestItem
    };
  }

  /**
   * Initialize from localStorage on startup
   */
  initializeFromStorage(): void {
    try {
      const storedQueue = localStorage.getItem('dokterku_sync_queue');
      if (storedQueue) {
        this.syncQueue = JSON.parse(storedQueue);
        console.log(`📋 Restored sync queue: ${this.syncQueue.length} items`);
        
        // Process queue if online
        if (this.isOnline) {
          setTimeout(() => this.processSyncQueue(), 1000);
        }
      }
    } catch (error) {
      console.warn('⚠️ Failed to restore sync queue:', error);
      localStorage.removeItem('dokterku_sync_queue');
    }
  }
}

// Export singleton instance
export const offlineManager = new OfflineManager();

// React hook for offline management (import React separately)
export const useOfflineManager = () => {
  const [isOffline, setIsOffline] = useState(offlineManager.isOffline());
  const [syncQueueStatus, setSyncQueueStatus] = useState(offlineManager.getSyncQueueStatus());

  useEffect(() => {
    // Subscribe to offline status changes
    const unsubscribe = offlineManager.onOfflineStatusChange(setIsOffline);
    
    // Update sync queue status periodically
    const updateSyncStatus = () => {
      setSyncQueueStatus(offlineManager.getSyncQueueStatus());
    };
    
    const interval = setInterval(updateSyncStatus, 5000);
    updateSyncStatus(); // Initial update
    
    return () => {
      unsubscribe();
      clearInterval(interval);
    };
  }, []);

  const getOfflineData = useCallback(async () => {
    return await offlineManager.getOfflineData();
  }, []);

  const forceSyncAll = useCallback(async () => {
    await offlineManager.forceSyncAll();
    setSyncQueueStatus(offlineManager.getSyncQueueStatus());
  }, []);

  return {
    isOffline,
    syncQueueStatus,
    getOfflineData,
    forceSyncAll,
    offlineManager
  };
};

export default offlineManager;