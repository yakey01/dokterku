import '../../react-preamble';
import React from 'react';
import { useState, useEffect } from 'react';
import { Calendar, Clock, Crown, Shield, Star, Brain, MapPin, Sword, Target, Award, Flame, ChevronRight, Trophy, Zap, Heart, Activity, TrendingUp, Users, CheckCircle, ArrowUp, Sparkles, Medal, ChevronLeft, Eye, Settings, BarChart3 } from 'lucide-react';

interface DashboardProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

const HolisticMedicalDashboard = ({ userData, onNavigate }: DashboardProps) => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState<'portrait' | 'landscape'>('portrait');

  useEffect(() => {
    const checkDevice = () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      setIsIpad(width >= 768);
      setOrientation(width > height ? 'landscape' : 'portrait');
    };

    checkDevice();
    window.addEventListener('resize', checkDevice);
    window.addEventListener('orientationchange', checkDevice);

    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);

    return () => {
      clearInterval(timer);
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
  }, []);

  const formatTime = (date: Date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  };

  const greeting = () => {
    const hour = currentTime.getHours();
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 17) return 'Selamat Siang';
    return 'Selamat Malam';
  };

  const doctorLevel = 15;
  const currentXP = 2850;
  const nextLevelXP = 3200;
  const xpProgress = (currentXP / nextLevelXP) * 100;

  const achievements = [
    { id: 1, name: 'Emergency Master', icon: Sword, color: 'from-red-400 to-red-600', progress: 85 },
    { id: 2, name: 'Life Saver', icon: Heart, color: 'from-pink-400 to-pink-600', progress: 92 },
    { id: 3, name: 'Night Guardian', icon: Shield, color: 'from-blue-400 to-blue-600', progress: 78 },
    { id: 4, name: 'Elite Healer', icon: Star, color: 'from-yellow-400 to-yellow-600', progress: 95 }
  ];

  const leaderboard = [
    { rank: 1, name: 'Dr. Sarah Chen', xp: 4200, level: 18, avatar: '👩‍⚕️' },
    { rank: 2, name: userData?.name || 'Dr. Ahmad', xp: currentXP, level: doctorLevel, avatar: '👨‍⚕️' },
    { rank: 3, name: 'Dr. Maria Santos', xp: 2750, level: 14, avatar: '👩‍⚕️' },
    { rank: 4, name: 'Dr. John Smith', xp: 2680, level: 14, avatar: '👨‍⚕️' },
    { rank: 5, name: 'Dr. Lisa Wong', xp: 2450, level: 13, avatar: '👩‍⚕️' }
  ];

  const visibleLeaderboard = isIpad ? leaderboard : leaderboard.slice(0, 2);

  const wellnessMetrics = [
    { icon: Heart, label: 'Health', value: 92, color: 'text-red-400', bg: 'bg-red-500/20' },
    { icon: Zap, label: 'Energy', value: 78, color: 'text-yellow-400', bg: 'bg-yellow-500/20' },
    { icon: Brain, label: 'Focus', value: 88, color: 'text-purple-400', bg: 'bg-purple-500/20' },
    { icon: Activity, label: 'Stamina', value: 85, color: 'text-green-400', bg: 'bg-green-500/20' }
  ];

  const quickActions = [
    { icon: Calendar, label: 'Missions', color: 'from-blue-500 to-cyan-500', tab: 'jadwal' },
    { icon: Shield, label: 'Guardian', color: 'from-green-500 to-emerald-500', tab: 'presensi' },
    { icon: Star, label: 'Rewards', color: 'from-purple-500 to-pink-500', tab: 'jaspel' },
    { icon: BarChart3, label: 'Reports', color: 'from-orange-500 to-red-500', tab: 'laporan' }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-hidden">
        
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
          {isIpad && <div className="absolute top-40 right-20 w-24 h-24 bg-cyan-500/5 rounded-full blur-2xl animate-pulse"></div>}
          {isIpad && <div className="absolute bottom-40 right-8 w-28 h-28 bg-yellow-500/5 rounded-full blur-3xl animate-bounce"></div>}
        </div>

        <div className="flex justify-between items-center px-4 sm:px-6 md:px-8 pt-3 pb-2 text-white text-sm font-semibold relative z-10">
          <span>{formatTime(currentTime)}</span>
          <div className="flex items-center space-x-1">
            <div className="flex space-x-1">
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-white rounded-full"></div>
              <div className="w-1 h-3 bg-gray-500 rounded-full"></div>
            </div>
            <div className="w-6 h-3 border border-white rounded-sm relative">
              <div className="w-4 h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
            </div>
          </div>
        </div>

        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-32">
          
          <div className="mb-6">
            <div className="relative">
              <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
              <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
              <div className={`relative ${isIpad ? 'p-6 md:p-8 lg:p-10' : 'p-4 sm:p-6'}`}>
                
                <div className="flex items-center justify-between mb-6">
                  <div className="flex items-center space-x-4">
                    <div className={`${isIpad ? 'w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32' : 'w-20 h-20'} bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-3xl flex items-center justify-center relative overflow-hidden`}>
                      <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                      <Crown className={`${isIpad ? 'w-12 h-12 md:w-14 md:h-14 lg:w-16 lg:h-16' : 'w-10 h-10'} text-white relative z-10`} />
                    </div>
                    <div>
                      <h1 className={`${isIpad ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl sm:text-2xl'} font-bold text-white mb-2`}>
                        {greeting()}, {userData?.name || 'Dr. Elite'}
                      </h1>
                      <div className="flex items-center space-x-3">
                        <span className={`${isIpad ? 'text-lg md:text-xl' : 'text-base'} text-purple-200`}>Level {doctorLevel}</span>
                        <div className="flex items-center space-x-1">
                          <Flame className="w-4 h-4 text-orange-400" />
                          <span className="text-orange-300 font-semibold">{currentXP} XP</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <button 
                    onClick={() => onNavigate?.('profil')}
                    className="p-3 bg-white/10 rounded-2xl hover:bg-white/20 transition-colors"
                  >
                    <Settings className="w-5 h-5 text-white" />
                  </button>
                </div>

                <div className="mb-4">
                  <div className="flex justify-between items-center mb-2">
                    <span className="text-sm text-purple-200">Progress to Level {doctorLevel + 1}</span>
                    <span className="text-sm text-white font-semibold">{currentXP}/{nextLevelXP} XP</span>
                  </div>
                  <div className="w-full bg-slate-700/50 rounded-full h-3 relative overflow-hidden">
                    <div 
                      className="h-full bg-gradient-to-r from-cyan-400 to-purple-500 rounded-full transition-all duration-1000 ease-out relative"
                      style={{ width: `${xpProgress}%` }}
                    >
                      <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className={`grid gap-6 md:gap-8 lg:gap-10 ${
            isIpad && orientation === 'landscape' 
              ? 'lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3' 
              : 'grid-cols-1'
          }`}>

            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/20">
              <h2 className={`${isIpad ? 'text-xl md:text-2xl' : 'text-lg'} font-bold mb-6 bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent`}>
                Wellness Analytics
              </h2>
              
              <div className={`grid gap-4 ${isIpad ? 'grid-cols-4' : 'grid-cols-2'}`}>
                {wellnessMetrics.map((metric, index) => (
                  <div key={index} className="relative group">
                    <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-4 border border-white/10 group-hover:border-white/20 transition-all duration-300">
                      <div className={`${metric.bg} rounded-2xl p-3 mb-3 ${isIpad ? 'w-16 h-16' : 'w-12 h-12'} flex items-center justify-center mx-auto`}>
                        <metric.icon className={`${isIpad ? 'w-8 h-8' : 'w-6 h-6'} ${metric.color}`} />
                      </div>
                      <div className="text-center">
                        <div className={`${isIpad ? 'text-2xl' : 'text-xl'} font-bold text-white mb-1`}>{metric.value}%</div>
                        <div className={`${isIpad ? 'text-sm' : 'text-xs'} text-gray-400`}>{metric.label}</div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="space-y-6">
              
              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/20">
                <div className="flex items-center justify-between mb-4">
                  <h3 className={`${isIpad ? 'text-lg md:text-xl' : 'text-base'} font-bold text-white`}>Recent Achievements</h3>
                  <button 
                    onClick={() => onNavigate?.('profil')}
                    className="text-purple-400 hover:text-white text-sm flex items-center"
                  >
                    View All <ChevronRight className="w-4 h-4 ml-1" />
                  </button>
                </div>
                
                <div className="space-y-3">
                  {achievements.slice(0, isIpad ? 4 : 2).map((achievement) => (
                    <div key={achievement.id} className="flex items-center space-x-3 p-3 bg-slate-800/30 rounded-2xl">
                      <div className={`w-10 h-10 bg-gradient-to-br ${achievement.color} rounded-xl flex items-center justify-center`}>
                        <achievement.icon className="w-5 h-5 text-white" />
                      </div>
                      <div className="flex-1">
                        <div className="text-white font-medium text-sm">{achievement.name}</div>
                        <div className="w-full bg-slate-700/50 rounded-full h-2 mt-1">
                          <div 
                            className={`h-full bg-gradient-to-r ${achievement.color} rounded-full`}
                            style={{ width: `${achievement.progress}%` }}
                          ></div>
                        </div>
                      </div>
                      <span className="text-xs text-gray-400">{achievement.progress}%</span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/20">
                <div className="flex items-center justify-between mb-4">
                  <h3 className={`${isIpad ? 'text-lg md:text-xl' : 'text-base'} font-bold text-white`}>Medical Leaderboard</h3>
                  <Trophy className="w-5 h-5 text-yellow-400" />
                </div>
                
                <div className="space-y-3">
                  {visibleLeaderboard.map((doctor) => (
                    <div key={doctor.rank} className={`flex items-center space-x-3 p-3 rounded-2xl ${
                      doctor.name === (userData?.name || 'Dr. Ahmad') 
                        ? 'bg-gradient-to-r from-purple-600/20 to-pink-600/20 border border-purple-400/30' 
                        : 'bg-slate-800/30'
                    }`}>
                      <div className={`w-8 h-8 rounded-xl flex items-center justify-center ${
                        doctor.rank === 1 ? 'bg-yellow-500/20 text-yellow-400' :
                        doctor.rank === 2 ? 'bg-gray-400/20 text-gray-300' :
                        doctor.rank === 3 ? 'bg-orange-500/20 text-orange-400' :
                        'bg-slate-600/20 text-gray-400'
                      }`}>
                        <span className="text-sm font-bold">#{doctor.rank}</span>
                      </div>
                      <span className="text-lg">{doctor.avatar}</span>
                      <div className="flex-1">
                        <div className="text-white font-medium text-sm">{doctor.name}</div>
                        <div className="text-xs text-gray-400">Level {doctor.level} • {doctor.xp} XP</div>
                      </div>
                      {doctor.rank <= 3 && (
                        <Medal className={`w-4 h-4 ${
                          doctor.rank === 1 ? 'text-yellow-400' :
                          doctor.rank === 2 ? 'text-gray-300' :
                          'text-orange-400'
                        }`} />
                      )}
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="space-y-6">
              
              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/20">
                <h3 className={`${isIpad ? 'text-lg md:text-xl' : 'text-base'} font-bold text-white mb-4`}>Quick Actions</h3>
                
                <div className={`grid gap-4 ${isIpad ? 'grid-cols-4' : 'grid-cols-2'}`}>
                  {quickActions.map((action, index) => (
                    <button
                      key={index}
                      onClick={() => onNavigate?.(action.tab)}
                      className={`relative p-4 bg-gradient-to-br ${action.color} rounded-2xl hover:scale-105 transition-all duration-300 group overflow-hidden`}
                    >
                      <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                      <div className="relative text-center">
                        <action.icon className={`${isIpad ? 'w-8 h-8' : 'w-6 h-6'} text-white mx-auto mb-2`} />
                        <span className={`${isIpad ? 'text-sm' : 'text-xs'} text-white font-medium`}>{action.label}</span>
                      </div>
                    </button>
                  ))}
                </div>
              </div>

              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/20">
                <div className="flex items-center justify-between mb-4">
                  <h3 className={`${isIpad ? 'text-lg md:text-xl' : 'text-base'} font-bold text-white`}>Today's Mission</h3>
                  <button 
                    onClick={() => onNavigate?.('jadwal')}
                    className="text-purple-400 hover:text-white text-sm flex items-center"
                  >
                    View All <ChevronRight className="w-4 h-4 ml-1" />
                  </button>
                </div>
                
                <div className="bg-gradient-to-br from-red-600/20 to-orange-600/20 rounded-2xl p-4 border border-red-400/30">
                  <div className="flex items-center space-x-3 mb-3">
                    <div className="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-2xl flex items-center justify-center">
                      <Sword className="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <h4 className="text-white font-bold">Emergency Division A</h4>
                      <p className="text-red-200 text-sm">Elite Difficulty • +450 XP</p>
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4 mb-4">
                    <div className="flex items-center space-x-2">
                      <Clock className="w-4 h-4 text-blue-400" />
                      <span className="text-white text-sm">07:00 - 14:00</span>
                    </div>
                    <div className="flex items-center space-x-2">
                      <MapPin className="w-4 h-4 text-green-400" />
                      <span className="text-white text-sm">Emergency Wing</span>
                    </div>
                  </div>
                  
                  <button 
                    onClick={() => onNavigate?.('presensi')}
                    className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium py-3 rounded-xl transition-all duration-300"
                  >
                    Start Mission
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-purple-400/60 to-transparent rounded-full shadow-lg shadow-purple-400/30"></div>
      </div>
    </div>
  );
};

export default HolisticMedicalDashboard;
export { HolisticMedicalDashboard };
