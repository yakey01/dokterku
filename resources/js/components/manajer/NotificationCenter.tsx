import React, { useState } from 'react';
import { useManagerNotifications, playNotificationSound } from '../../utils/managerWebSocket';

// ============================================
// NOTIFICATION CENTER FOR MANAGER DASHBOARD
// ============================================

interface NotificationItemProps {
  notification: {
    type: 'validation' | 'approval' | 'alert' | 'kpi_update';
    title: string;
    message: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    timestamp: string;
    data?: any;
  };
  index: number;
  onMarkAsRead: (index: number) => void;
}

const NotificationItem: React.FC<NotificationItemProps> = ({ 
  notification, 
  index, 
  onMarkAsRead 
}) => {
  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'validation':
        return '🔍';
      case 'approval':
        return '✅';
      case 'alert':
        return '🚨';
      case 'kpi_update':
        return '📊';
      default:
        return '📢';
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'urgent':
        return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700';
      case 'high':
        return 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-700';
      case 'medium':
        return 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700';
      default:
        return 'bg-neutral-50 dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700';
    }
  };

  const timeAgo = (timestamp: string) => {
    const now = new Date();
    const time = new Date(timestamp);
    const diffInMinutes = Math.floor((now.getTime() - time.getTime()) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Baru saja';
    if (diffInMinutes < 60) return `${diffInMinutes} menit lalu`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} jam lalu`;
    return time.toLocaleDateString('id-ID');
  };

  return (
    <div className={`p-4 border rounded-lg ${getPriorityColor(notification.priority)} hover:shadow-md transition-all duration-200`}>
      <div className="flex items-start justify-between">
        <div className="flex items-start space-x-3 flex-1">
          <span className="text-lg">{getNotificationIcon(notification.type)}</span>
          <div className="flex-1">
            <h4 className="font-medium text-neutral-900 dark:text-white text-sm">
              {notification.title}
            </h4>
            <p className="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
              {notification.message}
            </p>
            <p className="text-xs text-neutral-500 dark:text-neutral-500 mt-2">
              🕐 {timeAgo(notification.timestamp)}
            </p>
          </div>
        </div>
        
        <div className="flex items-center space-x-2">
          {notification.priority === 'urgent' && (
            <span className="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
          )}
          <button
            onClick={() => onMarkAsRead(index)}
            className="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 transition-colors"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
};

export const NotificationBell: React.FC = () => {
  const { 
    notifications, 
    isConnected, 
    unreadCount, 
    urgentCount, 
    markAsRead, 
    clearAllNotifications 
  } = useManagerNotifications();
  
  const [isOpen, setIsOpen] = useState(false);

  const handleNotificationClick = (index: number) => {
    const notification = notifications[index];
    if (notification.priority === 'urgent') {
      playNotificationSound('urgent');
    }
    markAsRead(index);
  };

  return (
    <div className="relative">
      {/* Bell Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-neutral-600 dark:text-neutral-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800"
      >
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-3.405-3.405A2.032 2.032 0 0118 9c0-3.314-2.686-6-6-6s-6 2.686-6 6c0 1.077.417 2.065 1.097 2.805L5 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        
        {/* Notification Badge */}
        {unreadCount > 0 && (
          <span className={`absolute -top-1 -right-1 min-w-[20px] h-5 ${
            urgentCount > 0 ? 'bg-red-500' : 'bg-blue-500'
          } text-white text-xs rounded-full flex items-center justify-center font-medium ${
            urgentCount > 0 ? 'animate-pulse' : ''
          }`}>
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
        
        {/* Connection Status */}
        <span className={`absolute -bottom-1 -right-1 w-3 h-3 ${
          isConnected ? 'bg-green-500' : 'bg-yellow-500'
        } border-2 border-white dark:border-neutral-900 rounded-full`}></span>
      </button>

      {/* Notification Dropdown */}
      {isOpen && (
        <>
          {/* Backdrop */}
          <div 
            className="fixed inset-0 z-40" 
            onClick={() => setIsOpen(false)}
          ></div>
          
          {/* Notification Panel */}
          <div className="absolute right-0 mt-2 w-96 bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-neutral-200 dark:border-neutral-700 z-50 max-h-96 overflow-hidden">
            {/* Header */}
            <div className="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                  🔔 Notifications
                </h3>
                <div className="flex items-center space-x-2">
                  <span className={`w-2 h-2 ${
                    isConnected ? 'bg-green-500' : 'bg-yellow-500'
                  } rounded-full`}></span>
                  <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {isConnected ? 'Live' : 'Polling'}
                  </span>
                </div>
              </div>
              
              {unreadCount > 0 && (
                <div className="flex items-center justify-between mt-2">
                  <span className="text-sm text-neutral-600 dark:text-neutral-400">
                    {unreadCount} unread {urgentCount > 0 && `(${urgentCount} urgent)`}
                  </span>
                  <button
                    onClick={clearAllNotifications}
                    className="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors"
                  >
                    Clear All
                  </button>
                </div>
              )}
            </div>

            {/* Notifications List */}
            <div className="max-h-80 overflow-y-auto custom-scrollbar">
              {notifications.length > 0 ? (
                <div className="p-4 space-y-3">
                  {notifications.map((notification, index) => (
                    <NotificationItem
                      key={`${notification.timestamp}-${index}`}
                      notification={notification}
                      index={index}
                      onMarkAsRead={handleNotificationClick}
                    />
                  ))}
                </div>
              ) : (
                <div className="p-8 text-center">
                  <svg className="w-12 h-12 text-neutral-400 dark:text-neutral-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                  </svg>
                  <p className="text-neutral-500 dark:text-neutral-400 text-sm">
                    📭 Tidak ada notifikasi
                  </p>
                  <p className="text-neutral-400 dark:text-neutral-600 text-xs mt-1">
                    Notifikasi baru akan muncul di sini
                  </p>
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="px-6 py-3 border-t border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                🔄 Real-time updates enabled • Last check: {new Date().toLocaleTimeString('id-ID')}
              </p>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

// Quick Notification Toast Component
export const NotificationToast: React.FC<{
  notification: {
    title: string;
    message: string;
    type: 'success' | 'warning' | 'error' | 'info';
  } | null;
  onClose: () => void;
}> = ({ notification, onClose }) => {
  const [isVisible, setIsVisible] = useState(!!notification);

  React.useEffect(() => {
    if (notification) {
      setIsVisible(true);
      const timer = setTimeout(() => {
        setIsVisible(false);
        setTimeout(onClose, 300); // Wait for animation
      }, 5000);
      
      return () => clearTimeout(timer);
    }
  }, [notification, onClose]);

  if (!notification) return null;

  const getToastStyles = (type: string) => {
    switch (type) {
      case 'success':
        return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200';
      case 'warning':
        return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200';
      case 'error':
        return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200';
      default:
        return 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200';
    }
  };

  return (
    <div className={`fixed top-4 right-4 z-50 transition-all duration-300 ${
      isVisible ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'
    }`}>
      <div className={`max-w-sm p-4 border rounded-lg shadow-lg backdrop-blur-lg ${getToastStyles(notification.type)}`}>
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <h4 className="font-medium text-sm">{notification.title}</h4>
            <p className="text-sm mt-1 opacity-90">{notification.message}</p>
          </div>
          <button
            onClick={() => {
              setIsVisible(false);
              setTimeout(onClose, 300);
            }}
            className="ml-3 opacity-70 hover:opacity-100 transition-opacity"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
};

export default NotificationToast;