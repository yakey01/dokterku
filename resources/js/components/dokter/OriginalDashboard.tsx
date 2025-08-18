import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Home, 
  Calendar, 
  Trophy, 
  Clock, 
  User, 
  LogOut,
  Menu,
  X,
  Bell,
  Wifi,
  WifiOff
} from 'lucide-react';

// Import original dokter components
import JaspelComponent from './Jaspel';
import PresensiComponent from './Presensi';
import ProfilComponent from './Profil';

/**
 * Original Dokter Dashboard
 * Restores the classic tab-based structure with original components
 */
const OriginalDokterDashboard: React.FC = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [userData, setUserData] = useState<any>(null);
  const [realtimeConnected, setRealtimeConnected] = useState(false);
  const [notifications, setNotifications] = useState<any[]>([]);

  // Get user data from meta tags
  useEffect(() => {
    const userDataMeta = document.querySelector('meta[name="user-data"]');
    if (userDataMeta) {
      try {
        const data = JSON.parse(userDataMeta.getAttribute('content') || '{}');
        setUserData(data);
        console.log('👤 User data loaded:', data.name);
      } catch (e) {
        console.error('Error parsing user data:', e);
      }
    }
  }, []);

  // Setup real-time connection
  useEffect(() => {
    if (userData?.id) {
      setupRealTimeConnection(userData.id);
    }
  }, [userData]);

  const setupRealTimeConnection = (userId: number) => {
    try {
      if (typeof window !== 'undefined' && window.Echo) {
        console.log('🔌 Setting up real-time connection for dokter...');
        
        window.Echo.private(`dokter.${userId}`)
          .listen('tindakan.validated', (event: any) => {
            console.log('🎯 Real-time validation received:', event);
            addNotification(event.notification);
            
            // Refresh active tab data
            window.dispatchEvent(new CustomEvent('dokter-data-refresh'));
          });
        
        window.Echo.connector.pusher.connection.bind('connected', () => {
          console.log('✅ Dokter WebSocket connected');
          setRealtimeConnected(true);
        });
        
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
          console.log('❌ Dokter WebSocket disconnected');
          setRealtimeConnected(false);
        });
        
      } else {
        console.log('⚠️ Echo not available, using polling fallback...');
        setRealtimeConnected(false);
      }
    } catch (error) {
      console.error('❌ Failed to setup real-time connection:', error);
      setRealtimeConnected(false);
    }
  };

  const addNotification = (notification: any) => {
    const newNotification = {
      id: Date.now(),
      ...notification,
      timestamp: new Date().toLocaleTimeString(),
    };
    
    setNotifications(prev => [newNotification, ...prev.slice(0, 2)]);
    
    // Auto-remove after 8 seconds
    setTimeout(() => {
      setNotifications(prev => prev.filter(n => n.id !== newNotification.id));
    }, 8000);
  };

  const handleLogout = async () => {
    try {
      await fetch('/logout', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        credentials: 'include'
      });
      
      window.location.href = '/';
    } catch (error) {
      console.error('Logout error:', error);
      window.location.href = '/';
    }
  };

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: Home },
    { id: 'jaspel', label: 'JASPEL', icon: Trophy },
    { id: 'presensi', label: 'Presensi', icon: Clock },
    { id: 'profil', label: 'Profil', icon: User },
  ];

  const renderTabContent = () => {
    switch (activeTab) {
      case 'jaspel':
        return <JaspelComponent />;
      case 'presensi':
        return <PresensiComponent />;
      case 'profil':
        return <ProfilComponent />;
      default:
        return (
          <div className="p-6">
            <div className="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-6 text-white mb-6">
              <h2 className="text-2xl font-bold mb-2">
                Selamat Datang, {userData?.name || 'Dokter'}!
              </h2>
              <p className="opacity-90">
                Dashboard JASPEL & Presensi - Sistem Terintegrasi
              </p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div 
                onClick={() => setActiveTab('jaspel')}
                className="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all cursor-pointer border border-gray-200 hover:border-blue-300"
              >
                <Trophy className="w-12 h-12 text-yellow-500 mb-4" />
                <h3 className="text-lg font-semibold text-gray-800 mb-2">JASPEL</h3>
                <p className="text-gray-600">Lihat pencapaian dan reward JASPEL Anda</p>
              </div>
              
              <div 
                onClick={() => setActiveTab('presensi')}
                className="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all cursor-pointer border border-gray-200 hover:border-green-300"
              >
                <Clock className="w-12 h-12 text-green-500 mb-4" />
                <h3 className="text-lg font-semibold text-gray-800 mb-2">Presensi</h3>
                <p className="text-gray-600">Kelola jadwal dan presensi kerja</p>
              </div>
              
              <div 
                onClick={() => setActiveTab('profil')}
                className="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all cursor-pointer border border-gray-200 hover:border-purple-300"
              >
                <User className="w-12 h-12 text-purple-500 mb-4" />
                <h3 className="text-lg font-semibold text-gray-800 mb-2">Profil</h3>
                <p className="text-gray-600">Kelola informasi profil Anda</p>
              </div>
            </div>
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation */}
      <nav className="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
        <div className="px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Left side */}
            <div className="flex items-center">
              <button
                onClick={() => setSidebarOpen(!sidebarOpen)}
                className="p-2 rounded-md text-gray-400 hover:text-gray-500 lg:hidden"
              >
                {sidebarOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
              </button>
              
              <div className="ml-4 lg:ml-0">
                <h1 className="text-xl font-semibold text-gray-900">
                  DOKTERKU Dashboard
                </h1>
              </div>
            </div>

            {/* Right side */}
            <div className="flex items-center space-x-4">
              {/* Real-time status */}
              <div className={`flex items-center gap-1 text-xs px-2 py-1 rounded-full ${
                realtimeConnected 
                  ? 'text-green-600 bg-green-50' 
                  : 'text-yellow-600 bg-yellow-50'
              }`}>
                {realtimeConnected ? (
                  <Wifi className="w-3 h-3" />
                ) : (
                  <WifiOff className="w-3 h-3" />
                )}
                <span>{realtimeConnected ? 'Live' : 'Offline'}</span>
              </div>

              {/* Notifications */}
              {notifications.length > 0 && (
                <div className="relative">
                  <Bell className="w-5 h-5 text-gray-400" />
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                    {notifications.length}
                  </span>
                </div>
              )}

              {/* User info */}
              <div className="flex items-center">
                <span className="text-sm text-gray-700 mr-3">
                  {userData?.name || 'Dokter'}
                </span>
                <button
                  onClick={handleLogout}
                  className="p-2 text-gray-400 hover:text-gray-500"
                  title="Logout"
                >
                  <LogOut className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </nav>

      {/* Sidebar for mobile */}
      <AnimatePresence>
        {sidebarOpen && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-gray-600 bg-opacity-75 z-30 lg:hidden"
              onClick={() => setSidebarOpen(false)}
            />
            <motion.div
              initial={{ x: -280 }}
              animate={{ x: 0 }}
              exit={{ x: -280 }}
              className="fixed inset-y-0 left-0 w-64 bg-white shadow-xl z-40 lg:hidden"
            >
              <MobileSidebar 
                tabs={tabs} 
                activeTab={activeTab} 
                setActiveTab={setActiveTab}
                setSidebarOpen={setSidebarOpen}
              />
            </motion.div>
          </>
        )}
      </AnimatePresence>

      <div className="flex">
        {/* Desktop Sidebar */}
        <div className="hidden lg:flex lg:flex-shrink-0">
          <div className="flex flex-col w-64 bg-white border-r border-gray-200">
            <DesktopSidebar 
              tabs={tabs} 
              activeTab={activeTab} 
              setActiveTab={setActiveTab}
            />
          </div>
        </div>

        {/* Main Content */}
        <div className="flex-1 overflow-hidden">
          {/* Real-time notifications */}
          {notifications.length > 0 && (
            <div className="p-4 space-y-2">
              {notifications.map((notification) => (
                <motion.div
                  key={notification.id}
                  initial={{ opacity: 0, y: -20 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -20 }}
                  className={`p-3 rounded-lg border ${
                    notification.type === 'success' 
                      ? 'bg-green-50 border-green-200 text-green-800' 
                      : notification.type === 'error'
                      ? 'bg-red-50 border-red-200 text-red-800'
                      : 'bg-blue-50 border-blue-200 text-blue-800'
                  }`}
                >
                  <div className="flex justify-between items-start">
                    <div>
                      <div className="font-medium">{notification.title}</div>
                      <div className="text-sm">{notification.message}</div>
                    </div>
                    <div className="text-xs opacity-75">
                      {notification.timestamp}
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          )}

          {/* Tab Content */}
          <div className="min-h-screen">
            <AnimatePresence mode="wait">
              <motion.div
                key={activeTab}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                transition={{ duration: 0.2 }}
              >
                {renderTabContent()}
              </motion.div>
            </AnimatePresence>
          </div>
        </div>
      </div>
    </div>
  );
};

// Desktop Sidebar Component
const DesktopSidebar: React.FC<{
  tabs: any[];
  activeTab: string;
  setActiveTab: (tab: string) => void;
}> = ({ tabs, activeTab, setActiveTab }) => {
  return (
    <div className="flex flex-col h-full">
      <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
        <div className="flex items-center flex-shrink-0 px-4">
          <h2 className="text-lg font-medium text-gray-900">Menu</h2>
        </div>
        <nav className="mt-5 flex-1 px-2 space-y-1">
          {tabs.map(({ id, label, icon: Icon }) => (
            <button
              key={id}
              onClick={() => setActiveTab(id)}
              className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md w-full text-left transition-colors ${
                activeTab === id
                  ? 'bg-gray-100 text-gray-900'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              }`}
            >
              <Icon className={`mr-3 h-5 w-5 ${
                activeTab === id ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'
              }`} />
              {label}
            </button>
          ))}
        </nav>
      </div>
    </div>
  );
};

// Mobile Sidebar Component
const MobileSidebar: React.FC<{
  tabs: any[];
  activeTab: string;
  setActiveTab: (tab: string) => void;
  setSidebarOpen: (open: boolean) => void;
}> = ({ tabs, activeTab, setActiveTab, setSidebarOpen }) => {
  const handleTabClick = (tabId: string) => {
    setActiveTab(tabId);
    setSidebarOpen(false);
  };

  return (
    <div className="flex flex-col h-full">
      <div className="flex items-center h-16 px-4 border-b border-gray-200">
        <h2 className="text-lg font-medium text-gray-900">Menu</h2>
      </div>
      <nav className="mt-5 flex-1 px-2 space-y-1">
        {tabs.map(({ id, label, icon: Icon }) => (
          <button
            key={id}
            onClick={() => handleTabClick(id)}
            className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md w-full text-left transition-colors ${
              activeTab === id
                ? 'bg-gray-100 text-gray-900'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            }`}
          >
            <Icon className={`mr-3 h-5 w-5 ${
              activeTab === id ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'
            }`} />
            {label}
          </button>
        ))}
      </nav>
    </div>
  );
};

export default OriginalDokterDashboard;