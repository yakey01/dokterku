import React, { useState, useEffect } from 'react';
import { 
  User, 
  Award, 
  Calendar, 
  Shield, 
  Target, 
  Trophy, 
  Star, 
  Activity,
  FileText,
  Settings,
  Edit,
  Save,
  X,
  Camera,
  Mail,
  Phone,
  MapPin,
  Clock,
  CheckCircle,
  TrendingUp,
  Briefcase,
  Heart,
  Zap
} from 'lucide-react';

const ProfileGamingTheme = () => {
  const [activeTab, setActiveTab] = useState('profile');
  const [isEditMode, setIsEditMode] = useState(false);
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState('portrait');

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
    
    return () => {
      window.removeEventListener('resize', checkDevice);
      window.removeEventListener('orientationchange', checkDevice);
    };
  }, []);

  // Profile Data
  const profileData = {
    name: "Dr. Naning Paramedis",
    title: "Senior Medical Officer",
    specialization: "Emergency Medicine",
    level: 42,
    experience: 12800,
    nextLevelExp: 15000,
    email: "naning@dokterku.com",
    phone: "+62 812-3456-7890",
    location: "Jakarta, Indonesia",
    joinDate: "Januari 2020",
    avatar: "/api/placeholder/120/120"
  };

  // Achievements Data
  const achievements = [
    {
      id: 1,
      name: "Lifesaver Elite",
      description: "Saved 100+ critical patients",
      icon: Heart,
      color: "from-red-500 to-pink-500",
      unlocked: true,
      progress: 100,
      rarity: "Legendary",
      points: 500
    },
    {
      id: 2,
      name: "Night Warrior",
      description: "Completed 50 night shifts",
      icon: Shield,
      color: "from-purple-500 to-indigo-500",
      unlocked: true,
      progress: 100,
      rarity: "Epic",
      points: 300
    },
    {
      id: 3,
      name: "Speed Demon",
      description: "Average response time < 5 minutes",
      icon: Zap,
      color: "from-yellow-500 to-orange-500",
      unlocked: true,
      progress: 95,
      rarity: "Rare",
      points: 200
    },
    {
      id: 4,
      name: "Perfect Attendance",
      description: "No absence for 6 months",
      icon: Calendar,
      color: "from-green-500 to-emerald-500",
      unlocked: true,
      progress: 100,
      rarity: "Rare",
      points: 150
    },
    {
      id: 5,
      name: "Mentor Master",
      description: "Trained 20+ junior staff",
      icon: Award,
      color: "from-blue-500 to-cyan-500",
      unlocked: true,
      progress: 80,
      rarity: "Epic",
      points: 250
    },
    {
      id: 6,
      name: "Research Pioneer",
      description: "Published 5 medical papers",
      icon: FileText,
      color: "from-indigo-500 to-purple-500",
      unlocked: false,
      progress: 60,
      rarity: "Legendary",
      points: 600
    }
  ];

  // Certifications
  const certifications = [
    {
      id: 1,
      name: "Advanced Cardiac Life Support (ACLS)",
      issuer: "American Heart Association",
      date: "Januari 2024",
      validUntil: "Januari 2026",
      status: "Active"
    },
    {
      id: 2,
      name: "Basic Life Support (BLS)",
      issuer: "Red Cross Indonesia",
      date: "Maret 2024",
      validUntil: "Maret 2026",
      status: "Active"
    },
    {
      id: 3,
      name: "Emergency Trauma Care",
      issuer: "WHO Emergency Care",
      date: "Juni 2023",
      validUntil: "Juni 2025",
      status: "Active"
    },
    {
      id: 4,
      name: "Pediatric Advanced Life Support",
      issuer: "Indonesian Pediatric Society",
      date: "September 2023",
      validUntil: "September 2025",
      status: "Active"
    }
  ];

  // Stats
  const stats = {
    totalPatients: 2847,
    successRate: 96.5,
    avgResponseTime: "4.2 min",
    totalProcedures: 1523,
    teamworkScore: 98,
    patientSatisfaction: 4.9
  };

  const getRarityColor = (rarity: string) => {
    switch (rarity) {
      case 'Legendary': return 'text-yellow-400 bg-yellow-400/20 border-yellow-400/50';
      case 'Epic': return 'text-purple-400 bg-purple-400/20 border-purple-400/50';
      case 'Rare': return 'text-blue-400 bg-blue-400/20 border-blue-400/50';
      default: return 'text-gray-400 bg-gray-400/20 border-gray-400/50';
    }
  };

  const getStatusColor = (status: string) => {
    return status === 'Active' 
      ? 'text-green-400 bg-green-400/20 border-green-400/50' 
      : 'text-red-400 bg-red-400/20 border-red-400/50';
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-hidden">
        
        {/* Dynamic Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 bg-blue-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '10%', width: '30vw', maxWidth: '400px', height: '30vw', maxHeight: '400px' }}></div>
          <div className="absolute top-60 bg-purple-500 bg-opacity-5 rounded-full blur-2xl animate-bounce" style={{ right: '5%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }}></div>
          <div className="absolute bottom-40 bg-cyan-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '15%', width: '28vw', maxWidth: '380px', height: '28vw', maxHeight: '380px' }}></div>
        </div>

        {/* Header */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pt-8 pb-6">
          <div className="text-center mb-8">
            <h1 className={`font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mb-2
              ${isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl sm:text-3xl'}
            `}>
              Profile & Achievements
            </h1>
            <p className={`text-purple-200 ${isIpad ? 'text-lg md:text-xl' : 'text-base'}`}>
              Level {profileData.level} Medical Hero
            </p>
          </div>

          {/* Tab Navigation */}
          <div className="flex justify-center mb-8">
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-2 border border-white/20">
              <div className="flex space-x-2">
                {[
                  { id: 'profile', label: 'Profile', icon: User },
                  { id: 'achievements', label: 'Achievements', icon: Trophy },
                  { id: 'certifications', label: 'Certifications', icon: Award },
                  { id: 'settings', label: 'Settings', icon: Settings }
                ].map((tab) => {
                  const Icon = tab.icon;
                  return (
                    <button
                      key={tab.id}
                      onClick={() => setActiveTab(tab.id)}
                      className={`
                        flex items-center space-x-2 px-4 py-2 rounded-xl transition-all duration-300
                        ${activeTab === tab.id 
                          ? 'bg-purple-600 text-white shadow-lg' 
                          : 'text-gray-300 hover:text-white hover:bg-white/10'
                        }
                      `}
                    >
                      <Icon className="w-4 h-4" />
                      <span className="text-sm font-medium">{tab.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-32 lg:pb-16">
          
          {/* Profile Tab */}
          {activeTab === 'profile' && (
            <div className="max-w-4xl mx-auto space-y-8">
              {/* Profile Card */}
              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 md:p-8 border border-white/20">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-2xl font-bold text-white">Profile Information</h2>
                  <button
                    onClick={() => setIsEditMode(!isEditMode)}
                    className={`px-4 py-2 rounded-xl flex items-center space-x-2 transition-all duration-300 ${
                      isEditMode 
                        ? 'bg-green-600 hover:bg-green-700 text-white' 
                        : 'bg-purple-600 hover:bg-purple-700 text-white'
                    }`}
                  >
                    {isEditMode ? (
                      <>
                        <Save className="w-4 h-4" />
                        <span>Save</span>
                      </>
                    ) : (
                      <>
                        <Edit className="w-4 h-4" />
                        <span>Edit</span>
                      </>
                    )}
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                  {/* Avatar Section */}
                  <div className="flex flex-col items-center">
                    <div className="relative mb-4">
                      <div className="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 p-1">
                        <div className="w-full h-full rounded-full bg-slate-900 flex items-center justify-center">
                          <User className="w-16 h-16 text-purple-400" />
                        </div>
                      </div>
                      {isEditMode && (
                        <button className="absolute bottom-0 right-0 w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center hover:bg-purple-700 transition-colors">
                          <Camera className="w-5 h-5 text-white" />
                        </button>
                      )}
                    </div>
                    <div className="text-center">
                      <h3 className="text-xl font-bold text-white mb-1">{profileData.name}</h3>
                      <p className="text-purple-300">{profileData.title}</p>
                      <p className="text-gray-400 text-sm">{profileData.specialization}</p>
                    </div>

                    {/* Level Progress */}
                    <div className="w-full mt-6">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-sm text-gray-400">Level {profileData.level}</span>
                        <span className="text-sm text-gray-400">Level {profileData.level + 1}</span>
                      </div>
                      <div className="w-full h-3 bg-gray-700 rounded-full overflow-hidden">
                        <div 
                          className="h-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500"
                          style={{ width: `${(profileData.experience / profileData.nextLevelExp) * 100}%` }}
                        />
                      </div>
                      <p className="text-center text-sm text-purple-300 mt-2">
                        {profileData.experience} / {profileData.nextLevelExp} XP
                      </p>
                    </div>
                  </div>

                  {/* Contact Information */}
                  <div className="md:col-span-2 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <label className="text-gray-400 text-sm flex items-center space-x-2">
                          <Mail className="w-4 h-4" />
                          <span>Email</span>
                        </label>
                        {isEditMode ? (
                          <input
                            type="email"
                            value={profileData.email}
                            className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white focus:border-purple-500 focus:outline-none"
                          />
                        ) : (
                          <p className="text-white">{profileData.email}</p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <label className="text-gray-400 text-sm flex items-center space-x-2">
                          <Phone className="w-4 h-4" />
                          <span>Phone</span>
                        </label>
                        {isEditMode ? (
                          <input
                            type="tel"
                            value={profileData.phone}
                            className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white focus:border-purple-500 focus:outline-none"
                          />
                        ) : (
                          <p className="text-white">{profileData.phone}</p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <label className="text-gray-400 text-sm flex items-center space-x-2">
                          <MapPin className="w-4 h-4" />
                          <span>Location</span>
                        </label>
                        {isEditMode ? (
                          <input
                            type="text"
                            value={profileData.location}
                            className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white focus:border-purple-500 focus:outline-none"
                          />
                        ) : (
                          <p className="text-white">{profileData.location}</p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <label className="text-gray-400 text-sm flex items-center space-x-2">
                          <Calendar className="w-4 h-4" />
                          <span>Join Date</span>
                        </label>
                        <p className="text-white">{profileData.joinDate}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Stats Grid */}
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <User className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">{stats.totalPatients}</div>
                  <div className="text-blue-300 text-sm">Total Patients</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Target className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">{stats.successRate}%</div>
                  <div className="text-green-300 text-sm">Success Rate</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Clock className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">{stats.avgResponseTime}</div>
                  <div className="text-yellow-300 text-sm">Avg Response</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Activity className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">{stats.totalProcedures}</div>
                  <div className="text-purple-300 text-sm">Procedures</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-indigo-500 to-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Shield className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">{stats.teamworkScore}</div>
                  <div className="text-indigo-300 text-sm">Teamwork</div>
                </div>

                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 text-center">
                  <div className="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Star className="w-6 h-6 text-white" />
                  </div>
                  <div className="text-xl font-bold text-white">{stats.patientSatisfaction}</div>
                  <div className="text-red-300 text-sm">Satisfaction</div>
                </div>
              </div>
            </div>
          )}

          {/* Achievements Tab */}
          {activeTab === 'achievements' && (
            <div className="max-w-4xl mx-auto">
              <h2 className="text-2xl font-bold text-white text-center mb-8">
                Your Achievements
              </h2>
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {achievements.map((achievement) => {
                  const Icon = achievement.icon;
                  return (
                    <div 
                      key={achievement.id} 
                      className={`bg-white/10 backdrop-blur-xl rounded-2xl p-6 border ${
                        achievement.unlocked ? 'border-white/20' : 'border-gray-600/20 opacity-50'
                      } hover:border-purple-400/50 transition-all duration-300`}
                    >
                      <div className="flex items-center justify-between mb-4">
                        <div className={`w-16 h-16 bg-gradient-to-br ${achievement.color} rounded-xl flex items-center justify-center ${
                          !achievement.unlocked && 'grayscale'
                        }`}>
                          <Icon className="w-8 h-8 text-white" />
                        </div>
                        <div className={`px-3 py-1 rounded-full text-xs font-medium border ${getRarityColor(achievement.rarity)}`}>
                          {achievement.rarity}
                        </div>
                      </div>
                      
                      <h3 className="text-lg font-bold text-white mb-2">{achievement.name}</h3>
                      <p className="text-gray-400 text-sm mb-4">{achievement.description}</p>
                      
                      <div className="space-y-2">
                        <div className="flex items-center justify-between text-sm">
                          <span className="text-gray-400">Progress</span>
                          <span className="text-purple-300">{achievement.progress}%</span>
                        </div>
                        <div className="w-full h-2 bg-gray-700 rounded-full overflow-hidden">
                          <div 
                            className={`h-full bg-gradient-to-r ${achievement.color} transition-all duration-500`}
                            style={{ width: `${achievement.progress}%` }}
                          />
                        </div>
                      </div>
                      
                      <div className="mt-4 flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                          <Trophy className="w-4 h-4 text-yellow-400" />
                          <span className="text-yellow-400 font-medium">{achievement.points} pts</span>
                        </div>
                        {achievement.unlocked && (
                          <div className="flex items-center space-x-1">
                            <CheckCircle className="w-4 h-4 text-green-400" />
                            <span className="text-green-400 text-sm">Unlocked</span>
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* Certifications Tab */}
          {activeTab === 'certifications' && (
            <div className="max-w-4xl mx-auto space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-8">
                Professional Certifications
              </h2>
              
              <div className="space-y-4">
                {certifications.map((cert) => (
                  <div key={cert.id} className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-purple-400/50 transition-all duration-300">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-4">
                        <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center">
                          <Award className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="text-lg font-bold text-white">{cert.name}</h3>
                          <p className="text-purple-300">{cert.issuer}</p>
                        </div>
                      </div>
                      <div className={`px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(cert.status)}`}>
                        {cert.status}
                      </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-4 mt-4">
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <Calendar className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Issued Date</span>
                        </div>
                        <p className="text-white">{cert.date}</p>
                      </div>
                      
                      <div>
                        <div className="flex items-center space-x-2 mb-1">
                          <Clock className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-400 text-sm">Valid Until</span>
                        </div>
                        <p className="text-white">{cert.validUntil}</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              
              <div className="text-center mt-8">
                <button className="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl flex items-center space-x-2 mx-auto transition-colors">
                  <FileText className="w-5 h-5" />
                  <span>Add New Certification</span>
                </button>
              </div>
            </div>
          )}

          {/* Settings Tab */}
          {activeTab === 'settings' && (
            <div className="max-w-2xl mx-auto space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-8">
                Settings
              </h2>
              
              <div className="space-y-4">
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                  <h3 className="text-lg font-bold text-white mb-4">Notification Preferences</h3>
                  <div className="space-y-3">
                    <label className="flex items-center justify-between cursor-pointer">
                      <span className="text-gray-300">Email Notifications</span>
                      <input type="checkbox" className="toggle" defaultChecked />
                    </label>
                    <label className="flex items-center justify-between cursor-pointer">
                      <span className="text-gray-300">SMS Notifications</span>
                      <input type="checkbox" className="toggle" />
                    </label>
                    <label className="flex items-center justify-between cursor-pointer">
                      <span className="text-gray-300">Push Notifications</span>
                      <input type="checkbox" className="toggle" defaultChecked />
                    </label>
                  </div>
                </div>
                
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                  <h3 className="text-lg font-bold text-white mb-4">Privacy Settings</h3>
                  <div className="space-y-3">
                    <label className="flex items-center justify-between cursor-pointer">
                      <span className="text-gray-300">Show Profile to Team</span>
                      <input type="checkbox" className="toggle" defaultChecked />
                    </label>
                    <label className="flex items-center justify-between cursor-pointer">
                      <span className="text-gray-300">Share Achievement Progress</span>
                      <input type="checkbox" className="toggle" defaultChecked />
                    </label>
                    <label className="flex items-center justify-between cursor-pointer">
                      <span className="text-gray-300">Display Stats Publicly</span>
                      <input type="checkbox" className="toggle" />
                    </label>
                  </div>
                </div>
                
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                  <h3 className="text-lg font-bold text-white mb-4">Account Settings</h3>
                  <div className="space-y-3">
                    <button className="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-colors">
                      Change Password
                    </button>
                    <button className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors">
                      Two-Factor Authentication
                    </button>
                    <button className="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">
                      Deactivate Account
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ProfileGamingTheme;