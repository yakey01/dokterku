import React, { useState, useEffect } from 'react';
import { Calendar, Clock, User, Activity } from 'lucide-react';

/**
 * Simple fallback dashboard for debugging blank page issues
 */
const SimpleDashboard: React.FC = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  
  useEffect(() => {
    console.log('✅ SimpleDashboard mounted successfully');
    
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    
    return () => clearInterval(timer);
  }, []);
  
  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white">
      <div className="container mx-auto px-4 py-8">
        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold mb-2">🏥 DOKTERKU</h1>
          <p className="text-purple-200">Simple Dashboard - Working!</p>
          <p className="text-sm text-gray-300">
            {currentTime.toLocaleString('id-ID')}
          </p>
        </div>
        
        {/* Status Cards */}
        <div className="grid grid-cols-2 gap-4 mb-8">
          <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                <Activity className="w-6 h-6 text-green-400" />
              </div>
              <div>
                <div className="text-2xl font-bold text-green-400">✅</div>
                <div className="text-sm text-gray-300">React Working</div>
              </div>
            </div>
          </div>
          
          <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                <User className="w-6 h-6 text-blue-400" />
              </div>
              <div>
                <div className="text-2xl font-bold text-blue-400">13</div>
                <div className="text-sm text-gray-300">User ID</div>
              </div>
            </div>
          </div>
        </div>
        
        {/* Navigation */}
        <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
          <h3 className="text-lg font-semibold mb-4">🧪 Test Components</h3>
          <div className="grid grid-cols-1 gap-3">
            <button 
              onClick={() => {
                localStorage.setItem('dashboard-mode', 'original');
                window.location.reload();
              }}
              className="w-full bg-green-500/20 hover:bg-green-500/30 p-4 rounded-xl text-left transition-colors"
            >
              <div className="font-medium">🔄 Try Original Dashboard</div>
              <div className="text-sm text-gray-300">Switch to OriginalDokterDashboard</div>
            </button>
            
            <button 
              onClick={() => {
                localStorage.setItem('dashboard-mode', 'optimized');
                window.location.reload();
              }}
              className="w-full bg-blue-500/20 hover:bg-blue-500/30 p-4 rounded-xl text-left transition-colors"
            >
              <div className="font-medium">🚀 Try Optimized Dashboard</div>
              <div className="text-sm text-gray-300">Switch to OptimizedOriginalDashboard</div>
            </button>
            
            <a 
              href="/dokter/mobile-app-simple"
              className="block w-full bg-purple-500/20 hover:bg-purple-500/30 p-4 rounded-xl text-left transition-colors"
            >
              <div className="font-medium">📱 Simple App Version</div>
              <div className="text-sm text-gray-300">Try alternative mobile app</div>
            </a>
          </div>
        </div>
        
        {/* Debug Info */}
        <div className="mt-8 bg-black/20 rounded-xl p-4">
          <h4 className="font-medium mb-2">🔍 Debug Info</h4>
          <div className="text-xs text-gray-300 space-y-1">
            <div>Time: {currentTime.toISOString()}</div>
            <div>User Agent: {navigator.userAgent.substring(0, 50)}...</div>
            <div>Screen: {window.innerWidth}x{window.innerHeight}</div>
            <div>URL: {window.location.href}</div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SimpleDashboard;