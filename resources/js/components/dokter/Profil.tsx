import React, { useState, useEffect } from 'react';
import { 
  User, 
  Crown, 
  Award, 
  Star, 
  Shield, 
  Heart, 
  Brain, 
  Zap, 
  Target, 
  Calendar, 
  Clock, 
  MapPin, 
  Phone, 
  Mail, 
  Edit3, 
  Settings, 
  LogOut,
  Trophy,
  Flame,
  TrendingUp,
  Activity,
  Badge,
  Medal,
  Gem,
  Sparkles,
  Coffee,
  Moon,
  Sun,
  Camera,
  Upload,
  Save,
  X,
  Check,
  Eye,
  EyeOff,
  Lock,
  Unlock
} from 'lucide-react';

interface ProfileData {
  id: string;
  name: string;
  title: string;
  specialization: string;
  level: number;
  experience: number;
  nextLevelXP: number;
  avatar: string;
  email: string;
  phone: string;
  workLocation: string;
  joinDate: string;
  totalPatients: number;
  totalProcedures: number;
  attendanceRate: number;
  performanceScore: number;
  achievements: Achievement[];
  stats: Stats;
}

interface Achievement {
  id: string;
  title: string;
  description: string;
  icon: React.ComponentType<any>;
  rarity: 'common' | 'rare' | 'epic' | 'legendary';
  unlocked: boolean;
  unlockedDate?: string;
}

interface Stats {
  totalXP: number;
  monthlyXP: number;
  streak: number;
  rank: number;
  totalHours: number;
  efficiency: number;
}

interface ProfilProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

export function Profil({ userData, onNavigate }: ProfilProps) {
  const [isEditMode, setIsEditMode] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');
  const [showStats, setShowStats] = useState(true);

  const mockProfileData: ProfileData = {
    id: '1',
    name: 'Dr. Naning Paramedis',
    title: 'Senior Medical Officer',
    specialization: 'General Practitioner',
    level: 7,
    experience: 2847,
    nextLevelXP: 3000,
    avatar: '/api/placeholder/120/120',
    email: 'naning@dokterku.com',
    phone: '+62 812-3456-7890',
    workLocation: 'Klinik Dokterku Utama',
    joinDate: '2023-01-15',
    totalPatients: 1247,
    totalProcedures: 892,
    attendanceRate: 96.7,
    performanceScore: 94.2,
    achievements: [
      {
        id: '1',
        title: 'Perfect Attendance',
        description: 'No missed days for 30 consecutive days',
        icon: Calendar,
        rarity: 'epic',
        unlocked: true,
        unlockedDate: '2024-07-15'
      },
      {
        id: '2',
        title: 'Patient Champion',
        description: 'Treated 1000+ patients',
        icon: Heart,
        rarity: 'rare',
        unlocked: true,
        unlockedDate: '2024-06-10'
      },
      {
        id: '3',
        title: 'Excellence Master',
        description: 'Maintain 95%+ performance for 6 months',
        icon: Crown,
        rarity: 'legendary',
        unlocked: false
      },
      {
        id: '4',
        title: 'Speed Healer',
        description: 'Complete 100 procedures in optimal time',
        icon: Zap,
        rarity: 'rare',
        unlocked: true,
        unlockedDate: '2024-05-20'
      }
    ],
    stats: {
      totalXP: 15847,
      monthlyXP: 2847,
      streak: 28,
      rank: 2,
      totalHours: 1560,
      efficiency: 97.8
    }
  };

  const getRarityColor = (rarity: string) => {
    switch (rarity) {
      case 'common':
        return 'from-gray-500/30 to-slate-500/30 border-gray-400/50 text-gray-300';
      case 'rare':
        return 'from-blue-500/30 to-cyan-500/30 border-blue-400/50 text-blue-300';
      case 'epic':
        return 'from-purple-500/30 to-pink-500/30 border-purple-400/50 text-purple-300';
      case 'legendary':
        return 'from-yellow-500/30 to-orange-500/30 border-yellow-400/50 text-yellow-300';
      default:
        return 'from-gray-500/30 to-slate-500/30 border-gray-400/50 text-gray-300';
    }
  };

  const calculateExperiencePercent = () => {
    return (mockProfileData.experience / mockProfileData.nextLevelXP) * 100;
  };

  const formatJoinDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const renderOverviewTab = () => (
    <div className="space-y-6">
      {/* Character Stats */}
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <h3 className="text-lg font-bold text-white mb-4 flex items-center">
          <Activity className="w-5 h-5 mr-2 text-purple-400" />
          Character Stats
        </h3>
        
        <div className="grid grid-cols-2 gap-4">
          <div className="text-center">
            <div className="flex items-center justify-center mb-2">
              <Trophy className="w-5 h-5 text-yellow-400 mr-2" />
              <span className="text-2xl font-bold text-white">{mockProfileData.stats.rank}</span>
            </div>
            <span className="text-yellow-300 text-sm">Server Rank</span>
          </div>
          
          <div className="text-center">
            <div className="flex items-center justify-center mb-2">
              <TrendingUp className="w-5 h-5 text-green-400 mr-2" />
              <span className="text-2xl font-bold text-white">{mockProfileData.performanceScore}%</span>
            </div>
            <span className="text-green-300 text-sm">Performance</span>
          </div>
          
          <div className="text-center">
            <div className="flex items-center justify-center mb-2">
              <Flame className="w-5 h-5 text-orange-400 mr-2" />
              <span className="text-2xl font-bold text-white">{mockProfileData.stats.streak}</span>
            </div>
            <span className="text-orange-300 text-sm">Day Streak</span>
          </div>
          
          <div className="text-center">
            <div className="flex items-center justify-center mb-2">
              <Zap className="w-5 h-5 text-cyan-400 mr-2" />
              <span className="text-2xl font-bold text-white">{mockProfileData.stats.efficiency}%</span>
            </div>
            <span className="text-cyan-300 text-sm">Efficiency</span>
          </div>
        </div>
      </div>

      {/* Professional Info */}
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <h3 className="text-lg font-bold text-white mb-4 flex items-center">
          <User className="w-5 h-5 mr-2 text-blue-400" />
          Professional Info
        </h3>
        
        <div className="space-y-4">
          <div className="flex items-center justify-between py-2">
            <span className="text-gray-300">Specialization</span>
            <span className="text-white font-medium">{mockProfileData.specialization}</span>
          </div>
          
          <div className="flex items-center justify-between py-2">
            <span className="text-gray-300">Work Location</span>
            <span className="text-white font-medium">{mockProfileData.workLocation}</span>
          </div>
          
          <div className="flex items-center justify-between py-2">
            <span className="text-gray-300">Join Date</span>
            <span className="text-white font-medium">{formatJoinDate(mockProfileData.joinDate)}</span>
          </div>
          
          <div className="flex items-center justify-between py-2">
            <span className="text-gray-300">Total Patients</span>
            <span className="text-green-400 font-bold">{mockProfileData.totalPatients.toLocaleString()}</span>
          </div>
          
          <div className="flex items-center justify-between py-2">
            <span className="text-gray-300">Total Procedures</span>
            <span className="text-blue-400 font-bold">{mockProfileData.totalProcedures.toLocaleString()}</span>
          </div>
        </div>
      </div>

      {/* Contact Info */}
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <h3 className="text-lg font-bold text-white mb-4 flex items-center">
          <Mail className="w-5 h-5 mr-2 text-green-400" />
          Contact Info
        </h3>
        
        <div className="space-y-4">
          <div className="flex items-center space-x-3">
            <Mail className="w-5 h-5 text-blue-400" />
            <span className="text-white">{mockProfileData.email}</span>
          </div>
          
          <div className="flex items-center space-x-3">
            <Phone className="w-5 h-5 text-green-400" />
            <span className="text-white">{mockProfileData.phone}</span>
          </div>
          
          <div className="flex items-center space-x-3">
            <MapPin className="w-5 h-5 text-purple-400" />
            <span className="text-white">{mockProfileData.workLocation}</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderAchievementsTab = () => (
    <div className="space-y-4">
      <div className="text-center mb-6">
        <h3 className="text-xl font-bold bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent mb-2">
          Achievement Gallery
        </h3>
        <p className="text-gray-300 text-sm">
          {mockProfileData.achievements.filter(a => a.unlocked).length} / {mockProfileData.achievements.length} Unlocked
        </p>
      </div>

      {mockProfileData.achievements.map((achievement) => (
        <div 
          key={achievement.id}
          className={`bg-gradient-to-r ${getRarityColor(achievement.rarity)} rounded-2xl p-4 border backdrop-blur-sm relative ${
            !achievement.unlocked ? 'opacity-60' : ''
          }`}
        >
          <div className="flex items-center space-x-4">
            <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${
              achievement.unlocked 
                ? 'bg-gradient-to-br from-yellow-500 to-orange-500' 
                : 'bg-gradient-to-br from-gray-500 to-slate-600'
            }`}>
              <achievement.icon className="w-6 h-6 text-white" />
            </div>
            
            <div className="flex-1">
              <div className="flex items-center space-x-2 mb-1">
                <h4 className="text-white font-bold text-sm">{achievement.title}</h4>
                <div className={`px-2 py-1 rounded-full text-xs font-bold ${
                  achievement.rarity === 'legendary' ? 'bg-yellow-500/20 text-yellow-300' :
                  achievement.rarity === 'epic' ? 'bg-purple-500/20 text-purple-300' :
                  achievement.rarity === 'rare' ? 'bg-blue-500/20 text-blue-300' :
                  'bg-gray-500/20 text-gray-300'
                }`}>
                  {achievement.rarity.toUpperCase()}
                </div>
              </div>
              <p className="text-gray-300 text-xs mb-2">{achievement.description}</p>
              {achievement.unlocked && achievement.unlockedDate && (
                <p className="text-green-400 text-xs">
                  Unlocked: {new Date(achievement.unlockedDate).toLocaleDateString('id-ID')}
                </p>
              )}
            </div>
            
            {!achievement.unlocked && (
              <Lock className="w-5 h-5 text-gray-400" />
            )}
          </div>
        </div>
      ))}
    </div>
  );

  const renderStatsTab = () => (
    <div className="space-y-6">
      {/* XP Progress */}
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <h3 className="text-lg font-bold text-white mb-4 flex items-center">
          <Star className="w-5 h-5 mr-2 text-yellow-400" />
          Experience Points
        </h3>
        
        <div className="text-center mb-4">
          <div className="text-3xl font-bold text-white mb-2">
            {mockProfileData.stats.totalXP.toLocaleString()} XP
          </div>
          <div className="text-gray-300 text-sm">Total Experience</div>
        </div>
        
        <div className="grid grid-cols-2 gap-4">
          <div className="text-center">
            <div className="text-xl font-bold text-purple-400 mb-1">
              {mockProfileData.stats.monthlyXP.toLocaleString()}
            </div>
            <div className="text-purple-300 text-sm">This Month</div>
          </div>
          
          <div className="text-center">
            <div className="text-xl font-bold text-blue-400 mb-1">
              {mockProfileData.stats.totalHours.toLocaleString()}h
            </div>
            <div className="text-blue-300 text-sm">Total Hours</div>
          </div>
        </div>
      </div>

      {/* Performance Metrics */}
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        <h3 className="text-lg font-bold text-white mb-4 flex items-center">
          <Target className="w-5 h-5 mr-2 text-green-400" />
          Performance Metrics
        </h3>
        
        <div className="space-y-4">
          <div>
            <div className="flex justify-between text-sm mb-2">
              <span className="text-gray-300">Attendance Rate</span>
              <span className="text-green-400 font-bold">{mockProfileData.attendanceRate}%</span>
            </div>
            <div className="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
              <div 
                className="bg-gradient-to-r from-green-500 to-emerald-500 h-2 rounded-full transition-all duration-1000"
                style={{ width: `${mockProfileData.attendanceRate}%` }}
              ></div>
            </div>
          </div>
          
          <div>
            <div className="flex justify-between text-sm mb-2">
              <span className="text-gray-300">Performance Score</span>
              <span className="text-blue-400 font-bold">{mockProfileData.performanceScore}%</span>
            </div>
            <div className="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
              <div 
                className="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full transition-all duration-1000"
                style={{ width: `${mockProfileData.performanceScore}%` }}
              ></div>
            </div>
          </div>
          
          <div>
            <div className="flex justify-between text-sm mb-2">
              <span className="text-gray-300">Efficiency Rating</span>
              <span className="text-purple-400 font-bold">{mockProfileData.stats.efficiency}%</span>
            </div>
            <div className="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
              <div 
                className="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full transition-all duration-1000"
                style={{ width: `${mockProfileData.stats.efficiency}%` }}
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <div className="w-full">
      <div className="max-w-sm mx-auto relative overflow-hidden">
        
        {/* Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-purple-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-pink-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-blue-500/5 rounded-full blur-3xl animate-pulse"></div>
        </div>

        {/* Character Card */}
        <div className="px-6 pt-8 pb-6 relative z-10">
          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-br from-purple-600/30 via-pink-600/30 to-blue-600/30 rounded-3xl backdrop-blur-2xl"></div>
            <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
            <div className="relative p-8">
              
              {/* Avatar and Level */}
              <div className="text-center mb-6">
                <div className="relative inline-block mb-4">
                  <div className="w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-lg border-4 border-white/20">
                    <User className="w-12 h-12 text-white" />
                  </div>
                  <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-sm font-bold px-3 py-1 rounded-full border-2 border-white shadow-lg">
                    Lv.{mockProfileData.level}
                  </div>
                </div>
                
                <h1 className="text-2xl font-bold text-white mb-1">{mockProfileData.name}</h1>
                <p className="text-purple-200 text-sm mb-2">{mockProfileData.title}</p>
                
                {/* XP Progress */}
                <div className="mb-4">
                  <div className="flex justify-between text-xs text-gray-300 mb-1">
                    <span>Level {mockProfileData.level}</span>
                    <span>{mockProfileData.experience}/{mockProfileData.nextLevelXP} XP</span>
                  </div>
                  <div className="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
                    <div 
                      className="bg-gradient-to-r from-cyan-400 via-purple-500 to-pink-500 h-2 rounded-full transition-all duration-1000 shadow-lg"
                      style={{ width: `${calculateExperiencePercent()}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="px-6 mb-6 relative z-10">
          <div className="flex space-x-2 bg-white/5 backdrop-blur-2xl rounded-2xl p-2 border border-white/10">
            <button
              onClick={() => setActiveTab('overview')}
              className={`flex-1 py-3 px-4 rounded-xl text-sm font-medium transition-all duration-300 ${
                activeTab === 'overview'
                  ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg'
                  : 'text-gray-300 hover:text-white hover:bg-white/10'
              }`}
            >
              Overview
            </button>
            <button
              onClick={() => setActiveTab('achievements')}
              className={`flex-1 py-3 px-4 rounded-xl text-sm font-medium transition-all duration-300 ${
                activeTab === 'achievements'
                  ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg'
                  : 'text-gray-300 hover:text-white hover:bg-white/10'
              }`}
            >
              Achievements
            </button>
            <button
              onClick={() => setActiveTab('stats')}
              className={`flex-1 py-3 px-4 rounded-xl text-sm font-medium transition-all duration-300 ${
                activeTab === 'stats'
                  ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg'
                  : 'text-gray-300 hover:text-white hover:bg-white/10'
              }`}
            >
              Stats
            </button>
          </div>
        </div>

        {/* Tab Content */}
        <div className="px-6 relative z-10">
          {activeTab === 'overview' && renderOverviewTab()}
          {activeTab === 'achievements' && renderAchievementsTab()}
          {activeTab === 'stats' && renderStatsTab()}
        </div>
      </div>
    </div>
  );
}