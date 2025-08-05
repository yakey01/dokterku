import * as React from 'react';
import { useState, useEffect } from 'react';
import { Calendar, Clock, DollarSign, User, Home, Crown, Shield, Star, Brain } from 'lucide-react';

interface MedicalRPGBottomNavProps {
  activeTab: string;
  setActiveTab: (tab: string) => void;
}

const MedicalRPGBottomNav: React.FC<MedicalRPGBottomNavProps> = ({ activeTab, setActiveTab }) => {
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState<'portrait' | 'landscape'>('portrait');

  useEffect(() => {
    // Detect iPad and orientation
    const checkDevice = () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      setIsIpad(width >= 768);
      setOrientation(width > height ? 'landscape' : 'portrait');
    };
    
    checkDevice();
    window.addEventListener('resize', checkDevice);
    window.addEventListener('orientationchange', checkDevice);
    
    return () => {
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
  }, []);

  // Map existing tab IDs to new navigation structure
  const navItems = [
    { 
      id: 'dashboard', 
      icon: Crown, 
      label: 'Home',
      theme: 'from-cyan-400 to-purple-400',
      hoverColor: 'cyan-purple'
    },
    { 
      id: 'jadwal', 
      icon: Calendar, 
      label: 'Missions',
      theme: 'from-blue-400 to-blue-600',
      hoverColor: 'blue'
    },
    { 
      id: 'presensi', 
      icon: Shield, 
      label: 'Guardian',
      theme: 'from-green-400 to-green-600',
      hoverColor: 'green'
    },
    { 
      id: 'jaspel', 
      icon: Star, 
      label: 'Rewards',
      theme: 'from-yellow-400 to-yellow-600',
      hoverColor: 'yellow'
    },
    { 
      id: 'profil', 
      icon: Brain, 
      label: 'Profile',
      theme: 'from-purple-400 to-purple-600',
      hoverColor: 'purple'
    }
  ];

  const getHoverClasses = (color: string) => {
    const hoverMap: { [key: string]: string } = {
      'cyan-purple': 'group-hover:text-cyan-400 group-hover:shadow-cyan-500/20',
      'blue': 'group-hover:text-blue-400 group-hover:shadow-blue-500/20',
      'green': 'group-hover:text-green-400 group-hover:shadow-green-500/20',
      'yellow': 'group-hover:text-yellow-400 group-hover:shadow-yellow-500/20',
      'purple': 'group-hover:text-purple-400 group-hover:shadow-purple-500/20'
    };
    return hoverMap[color] || '';
  };

  const getGradientBg = (color: string) => {
    const gradientMap: { [key: string]: string } = {
      'cyan-purple': 'from-cyan-500/0 to-purple-500/20',
      'blue': 'from-blue-500/0 to-blue-500/20',
      'green': 'from-green-500/0 to-green-500/20',
      'yellow': 'from-yellow-500/0 to-yellow-500/20',
      'purple': 'from-purple-500/0 to-purple-500/20'
    };
    return gradientMap[color] || '';
  };

  return (
    <div className={`
      absolute bottom-0 left-0 right-0 bg-gradient-to-t from-slate-800/90 via-purple-800/80 to-slate-700/90 
      backdrop-blur-3xl border-t border-purple-400/20 z-50
      ${isIpad ? 'px-8 md:px-12 lg:px-16 py-6 md:py-8' : 'px-6 py-4'}
    `}>
      <div className={`
        flex items-center mx-auto
        ${isIpad ? 'justify-center space-x-8 md:space-x-12 lg:space-x-16 max-w-4xl' : 'justify-between max-w-sm'}
      `}>
        
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeTab === item.id;
          
          return (
            <button
              key={item.id}
              onClick={() => setActiveTab(item.id)}
              className="relative group transition-all duration-500 ease-out"
            >
              {/* Active State */}
              {isActive ? (
                <>
                  <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
                  <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
                  <div className={`
                    relative bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl 
                    border border-cyan-300/30 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115
                    ${isIpad ? 'p-4 md:p-5' : 'p-3'}
                  `}>
                    <div className="flex flex-col items-center">
                      <Icon className={`text-white mb-1 ${isIpad ? 'w-6 h-6 md:w-8 md:h-8' : 'w-5 h-5'}`} />
                      {(isIpad || isActive) && (
                        <span className={`text-white font-medium ${isIpad ? 'text-xs md:text-sm' : 'text-xs'}`}>
                          {item.label}
                        </span>
                      )}
                    </div>
                  </div>
                </>
              ) : (
                /* Inactive State */
                <>
                  <div className={`absolute inset-0 bg-gradient-to-br ${getGradientBg(item.hoverColor)} rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500`}></div>
                  <div className={`
                    relative rounded-2xl transition-all duration-500 group-hover:bg-purple-600 bg-opacity-20 
                    group-hover:scale-110 group-hover:shadow-lg ${getHoverClasses(item.hoverColor)}
                    ${isIpad ? 'p-4 md:p-5' : 'p-3'}
                  `}>
                    <div className="flex flex-col items-center">
                      <Icon className={`
                        transition-colors duration-500 text-gray-400 mb-1
                        ${getHoverClasses(item.hoverColor).split(' ')[0]}
                        ${isIpad ? 'w-6 h-6 md:w-8 md:h-8' : 'w-5 h-5'}
                      `} />
                      {isIpad && (
                        <span className={`
                          transition-colors duration-500 text-gray-400 font-medium
                          ${getHoverClasses(item.hoverColor).split(' ')[0]}
                          ${isIpad ? 'text-xs md:text-sm' : 'text-xs'}
                        `}>
                          {item.label}
                        </span>
                      )}
                    </div>
                  </div>
                </>
              )}
            </button>
          );
        })}
      </div>
      
      {/* Gaming Home Indicator - Responsive */}
      <div className={`
        absolute left-1/2 transform -translate-x-1/2 
        bg-gradient-to-r from-transparent via-purple-400/60 to-transparent 
        rounded-full shadow-lg shadow-purple-400/30
        ${isIpad ? 'bottom-3 w-40 md:w-48 h-1.5' : 'bottom-2 w-32 h-1'}
      `}></div>
    </div>
  );
};

export default MedicalRPGBottomNav;