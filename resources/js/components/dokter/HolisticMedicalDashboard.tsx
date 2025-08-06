import React, { useState, useEffect } from 'react';
import { Calendar, Clock, DollarSign, User, Home, TrendingUp, Award, Target, Brain, Heart, Zap, Shield, Star, Crown, Flame, Coffee, Moon, Sun } from 'lucide-react';

const HolisticMedicalDashboard = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [doctorLevel, setDoctorLevel] = useState(7);
  const [experiencePoints, setExperiencePoints] = useState(2847);
  const [nextLevelXP, setNextLevelXP] = useState(3000);
  const [dailyStreak, setDailyStreak] = useState(15);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const formatTime = (date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  };

  const getTimeGreeting = () => {
    const hour = currentTime.getHours();
    if (hour < 12) return { greeting: "Good Morning, Doctor!", icon: Sun, color: "from-amber-400 to-orange-500" };
    if (hour < 17) return { greeting: "Good Afternoon, Doctor!", icon: Sun, color: "from-blue-400 to-cyan-500" };
    return { greeting: "Good Evening, Doctor!", icon: Moon, color: "from-purple-400 to-indigo-500" };
  };

  const { greeting, icon: TimeIcon, color } = getTimeGreeting();

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full max-w-md md:max-w-4xl lg:max-w-6xl xl:max-w-7xl mx-auto min-h-screen relative overflow-hidden">
        
        {/* Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
        </div>



        {/* Main Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
          {/* Main Content */}
          <div className="lg:col-span-8">
            
        {/* Doctor Level Card */}
        <div className="px-4 md:px-6 lg:px-8 pt-6 md:pt-8 lg:pt-12 pb-6 relative z-10">
          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
            <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
            <div className="relative p-8">
              
              {/* Level Badge & Avatar */}
              <div className="flex items-center justify-between mb-6">
                <div className="flex items-center space-x-4">
                  <div className="relative">
                    <div className="w-20 h-20 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-2xl flex items-center justify-center relative overflow-hidden">
                      <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                      <Crown className="w-10 h-10 text-white relative z-10" />
                    </div>
                    <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-lg">
                      Lv.{doctorLevel}
                    </div>
                  </div>
                  <div>
                    <h1 className={`text-xl md:text-2xl lg:text-3xl font-bold bg-gradient-to-r ${color} bg-clip-text text-transparent mb-1`}>
                      {greeting}
                    </h1>
                    <p className="text-purple-200 text-base md:text-lg lg:text-xl">Dr. Naning Paramedis</p>
                  </div>
                </div>
              </div>

              {/* XP Progress */}
              <div className="mb-6">
                <div className="flex justify-between text-sm md:text-base mb-2">
                  <span className="text-cyan-300">Klinik Dokterku</span>
                  <span className="text-white font-semibold">Sahabat Menuju Sehat</span>
                </div>
                <div className="w-full bg-gray-700/50 rounded-full h-3 overflow-hidden">
                  <div 
                    className="bg-gradient-to-r from-cyan-400 via-purple-500 to-pink-500 h-3 rounded-full transition-all duration-1000 shadow-lg"
                    style={{ width: `${(experiencePoints / nextLevelXP) * 100}%` }}
                  ></div>
                </div>
              </div>

              {/* Daily Stats */}
              <div className="grid grid-cols-3 gap-4">
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Flame className="w-5 h-5 text-orange-400 mr-2" />
                    <span className="text-2xl font-bold text-white">{dailyStreak}</span>
                  </div>
                  <span className="text-orange-300 text-xs md:text-sm">Day Streak</span>
                </div>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Star className="w-5 h-5 text-yellow-400 mr-2" />
                    <span className="text-2xl font-bold text-white">96.2%</span>
                  </div>
                  <span className="text-yellow-300 text-xs md:text-sm">Performance</span>
                </div>
                <div className="text-center">
                  <div className="flex items-center justify-center mb-2">
                    <Award className="w-5 h-5 text-purple-400 mr-2" />
                    <span className="text-2xl font-bold text-white">12</span>
                  </div>
                  <span className="text-purple-300 text-xs md:text-sm">Achievements</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Doctor Analytics */}
        <div className="px-4 md:px-6 lg:px-8 mb-6 md:mb-8 lg:mb-12 relative z-10">
          <h3 className="text-lg md:text-xl lg:text-2xl font-bold mb-4 md:mb-6 text-center bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
            Doctor Analytics
          </h3>
          
          <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-4 md:p-6 lg:p-8 border border-white/10">


            {/* Achievement Timeline */}
            <div className="space-y-4">
              <h4 className="font-semibold text-white mb-4">Recent Achievements</h4>
              
              <div className="flex items-center space-x-4 p-3 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl border border-green-400/30">
                <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
                <div className="flex-1">
                  <div className="font-medium text-white">Jaspel Bulan Ini</div>
                  <div className="text-green-300 text-sm">Rp 8,750,000</div>
                </div>
                <div className="text-2xl">💰</div>
              </div>

              <div className="flex items-center space-x-4 p-3 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-2xl border border-blue-400/30">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                  <Calendar className="w-5 h-5 text-white" />
                </div>
                <div className="flex-1">
                  <div className="font-medium text-white">Tingkat Kehadiran</div>
                  <div className="text-blue-300 text-sm">96.7% - 29/30 hari</div>
                </div>
                <div className="text-2xl">📅</div>
              </div>


            </div>
          </div>
        </div>

        {/* Leaderboard Preview */}
        <div className="px-4 md:px-6 lg:px-8 pb-32 relative z-10">
          <h3 className="text-lg md:text-xl lg:text-2xl font-bold mb-4 md:mb-6 text-center bg-gradient-to-r from-pink-400 to-purple-400 bg-clip-text text-transparent">
            Elite Doctor Leaderboard
          </h3>
          
          <div className="space-y-3 md:space-y-4 lg:space-y-6">
            <div className="flex items-center space-x-3 md:space-x-4 lg:space-x-6 bg-gradient-to-r from-yellow-500/30 to-amber-500/30 rounded-2xl p-3 md:p-4 lg:p-6 border-2 border-yellow-400/50">
              <div className="w-12 h-12 bg-gradient-to-br from-yellow-500 to-amber-500 rounded-xl flex items-center justify-center font-bold text-white text-lg">
                👑
              </div>
              <div className="flex-1">
                <div className="font-bold text-white">Dr. Maya Sari</div>
                <div className="text-yellow-300">Level 9 • 98.7% Score</div>
              </div>
              <div className="text-right">
                <div className="text-xl md:text-2xl lg:text-3xl font-bold text-yellow-400">4,750 XP</div>
              </div>
            </div>

            <div className="flex items-center space-x-3 md:space-x-4 lg:space-x-6 bg-gradient-to-r from-gray-400/30 to-slate-500/30 rounded-2xl p-3 md:p-4 lg:p-6 border-2 border-gray-400/50">
              <div className="w-12 h-12 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl flex items-center justify-center font-bold text-white text-lg">
                🥈
              </div>
              <div className="flex-1">
                <div className="font-bold text-white">Dr. Naning Paramedis</div>
                <div className="text-green-300">Level 7 • 96.2% Score</div>
              </div>
              <div className="text-right">
                <div className="text-xl md:text-2xl lg:text-3xl font-bold text-green-400">{experiencePoints} XP</div>
                <div className="text-xs text-green-300">You</div>
              </div>
            </div>
          </div>
          </div>
        </div>
        
        {/* Sidebar for larger screens */}
        <div className="hidden lg:block lg:col-span-4">
          <div className="sticky top-8">
            <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10 mb-6">
              <h4 className="text-lg font-bold text-white mb-4">Quick Stats</h4>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-300">Current Level</span>
                  <span className="text-purple-400 font-bold">Lv.{doctorLevel}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-300">Daily Streak</span>
                  <span className="text-orange-400 font-bold">{dailyStreak} days</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-300">XP Progress</span>
                  <span className="text-cyan-400 font-bold">{Math.round((experiencePoints / nextLevelXP) * 100)}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        </div>

        {/* Medical RPG Bottom Navigation */}
        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-slate-800/90 via-purple-800/80 to-slate-700/90 backdrop-blur-3xl px-4 md:px-6 lg:px-8 py-3 md:py-4 border-t border-purple-400/20 relative z-10 rounded-t-3xl">
          <div className="flex justify-between items-center">
            
            {/* Home - Active State */}
            <button className="relative group transition-all duration-500 ease-out">
              <div className="absolute -top-3 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/30 to-purple-500/30 rounded-2xl blur-lg scale-150 opacity-60"></div>
              <div className="relative bg-gradient-to-r from-cyan-500/40 to-purple-500/40 backdrop-blur-xl border border-cyan-300/30 p-3 rounded-2xl shadow-2xl shadow-purple-500/30 scale-115">
                <div className="flex flex-col items-center">
                  <Crown className="w-5 h-5 text-white mb-1" />
                  <span className="text-xs text-white font-medium">Home</span>
                </div>
              </div>
            </button>
            
            {/* Calendar - Inactive */}
            <button className="relative group transition-all duration-500 ease-out">
              <div className="absolute inset-0 bg-gradient-to-br from-blue-500/0 to-blue-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div className="relative p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-blue-500/20">
                <div className="flex flex-col items-center">
                  <Calendar className="w-5 h-5 transition-colors duration-500 text-gray-400 group-hover:text-blue-400 mb-1" />
                  <span className="text-xs transition-colors duration-500 text-gray-400 group-hover:text-blue-400 font-medium">Missions</span>
                </div>
              </div>
            </button>
            
            {/* Shield - Inactive */}
            <button className="relative group transition-all duration-500 ease-out">
              <div className="absolute inset-0 bg-gradient-to-br from-green-500/0 to-green-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div className="relative p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-green-500/20">
                <div className="flex flex-col items-center">
                  <Shield className="w-5 h-5 transition-colors duration-500 text-gray-400 group-hover:text-green-400 mb-1" />
                  <span className="text-xs transition-colors duration-500 text-gray-400 group-hover:text-green-400 font-medium">Guardian</span>
                </div>
              </div>
            </button>
            
            {/* Star - Inactive */}
            <button className="relative group transition-all duration-500 ease-out">
              <div className="absolute inset-0 bg-gradient-to-br from-yellow-500/0 to-yellow-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div className="relative p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-yellow-500/20">
                <div className="flex flex-col items-center">
                  <Star className="w-5 h-5 transition-colors duration-500 text-gray-400 group-hover:text-yellow-400 mb-1" />
                  <span className="text-xs transition-colors duration-500 text-gray-400 group-hover:text-yellow-400 font-medium">Rewards</span>
                </div>
              </div>
            </button>
            
            {/* Brain - Inactive */}
            <button className="relative group transition-all duration-500 ease-out">
              <div className="absolute inset-0 bg-gradient-to-br from-purple-500/0 to-purple-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div className="relative p-3 rounded-2xl transition-all duration-500 group-hover:bg-purple-600/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-purple-500/20">
                <div className="flex flex-col items-center">
                  <Brain className="w-5 h-5 transition-colors duration-500 text-gray-400 group-hover:text-purple-400 mb-1" />
                  <span className="text-xs transition-colors duration-500 text-gray-400 group-hover:text-purple-400 font-medium">Profile</span>
                </div>
              </div>
            </button>
            
          </div>
        </div>

        {/* Gaming Home Indicator */}
        <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-purple-400/60 to-transparent rounded-full shadow-lg shadow-purple-400/30"></div>
      </div>
    </div>
  );
};

export default HolisticMedicalDashboard;