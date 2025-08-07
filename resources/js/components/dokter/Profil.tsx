import React, { useState, useEffect } from 'react';
import { 
  User, 
  Edit, 
  Camera, 
  Phone, 
  Mail, 
  MapPin, 
  Calendar, 
  Award, 
  Star, 
  Crown, 
  Shield, 
  Target, 
  Activity, 
  Heart, 
  Brain, 
  Zap, 
  TrendingUp,
  Users,
  Clock,
  CheckCircle,
  Settings,
  Lock,
  Bell,
  Stethoscope,
  BookOpen,
  GraduationCap,
  Building,
  FileText,
  Eye,
  EyeOff,
  Save,
  X
} from 'lucide-react';

const ProfileComponent = () => {
  const [activeTab, setActiveTab] = useState('profile');
  const [isEditing, setIsEditing] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [isIpad, setIsIpad] = useState(false);
  const [orientation, setOrientation] = useState('portrait');
  const [profileData, setProfileData] = useState({
    name: 'Dr. Naning Paramedis',
    email: 'naning.paramedis@hospital.com',
    phone: '+62 812-3456-7890',
    address: 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
    birthDate: '15 Maret 1990',
    gender: 'Perempuan',
    specialization: 'Dokter Umum',
    licenseNumber: 'STR.12345678901',
    hospital: 'RS. Kediri Medical Center',
    experience: '5 tahun',
    bio: 'Dokter umum yang berpengalaman dalam pelayanan medis komprehensif dengan fokus pada pencegahan dan pengobatan berbagai kondisi kesehatan.'
  });

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

  const achievements = [
    {
      id: 1,
      title: "Perfect Attendance",
      description: "100% hadir selama 3 bulan berturut-turut",
      icon: Award,
      color: "from-yellow-500 to-orange-500",
      date: "Juli 2025",
      rarity: "Gold"
    },
    {
      id: 2,
      title: "Patient Care Excellence",
      description: "Rating 5.0 dari 100+ pasien",
      icon: Heart,
      color: "from-pink-500 to-red-500",
      date: "Juni 2025",
      rarity: "Platinum"
    },
    {
      id: 3,
      title: "Emergency Hero",
      description: "Menangani 50+ kasus darurat",
      icon: Shield,
      color: "from-blue-500 to-cyan-500",
      date: "Mei 2025",
      rarity: "Diamond"
    },
    {
      id: 4,
      title: "Knowledge Master",
      description: "Menyelesaikan 20 pelatihan medis",
      icon: Brain,
      color: "from-purple-500 to-indigo-500",
      date: "April 2025",
      rarity: "Gold"
    },
    {
      id: 5,
      title: "Team Player",
      description: "Kolaborasi terbaik dengan tim medis",
      icon: Users,
      color: "from-green-500 to-emerald-500",
      date: "Maret 2025",
      rarity: "Silver"
    },
    {
      id: 6,
      title: "Innovation Pioneer",
      description: "Mengimplementasikan 3 prosedur baru",
      icon: Star,
      color: "from-cyan-500 to-blue-500",
      date: "Februari 2025",
      rarity: "Platinum"
    }
  ];

  const certifications = [
    {
      id: 1,
      name: "Basic Life Support (BLS)",
      issuer: "American Heart Association",
      issueDate: "Januari 2023",
      expiryDate: "Januari 2025",
      status: "Valid"
    },
    {
      id: 2,
      name: "Advanced Cardiac Life Support (ACLS)",
      issuer: "American Heart Association",
      issueDate: "Maret 2023",
      expiryDate: "Maret 2025",
      status: "Valid"
    },
    {
      id: 3,
      name: "Pediatric Advanced Life Support (PALS)",
      issuer: "American Heart Association",
      issueDate: "Juni 2023",
      expiryDate: "Juni 2025",
      status: "Valid"
    },
    {
      id: 4,
      name: "Emergency Medicine Certification",
      issuer: "Indonesian Medical Association",
      issueDate: "September 2022",
      expiryDate: "September 2025",
      status: "Valid"
    }
  ];

  const stats = [
    { label: "Jam Kerja Total", value: "2,847", icon: Clock, color: "text-blue-400" },
    { label: "Pasien Ditangani", value: "1,234", icon: Users, color: "text-green-400" },
    { label: "Rating Kepuasan", value: "4.9/5", icon: Star, color: "text-yellow-400" },
    { label: "Sertifikasi", value: "12", icon: Award, color: "text-purple-400" },
    { label: "Pelatihan", value: "28", icon: BookOpen, color: "text-cyan-400" },
    { label: "Pengalaman", value: "5 Tahun", icon: TrendingUp, color: "text-orange-400" }
  ];

  const getRarityColor = (rarity) => {
    switch (rarity) {
      case 'Silver': return 'text-gray-300 bg-gray-400/20 border-gray-400/50';
      case 'Gold': return 'text-yellow-400 bg-yellow-400/20 border-yellow-400/50';
      case 'Platinum': return 'text-cyan-400 bg-cyan-400/20 border-cyan-400/50';
      case 'Diamond': return 'text-purple-400 bg-purple-400/20 border-purple-400/50';
      default: return 'text-gray-400 bg-gray-400/20 border-gray-400/50';
    }
  };

  const handleSave = () => {
    setIsEditing(false);
    // Here you would typically save to backend
  };

  const handleCancel = () => {
    setIsEditing(false);
    // Here you would reset form data
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-hidden">
        
        {/* Dynamic Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 bg-purple-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '10%', width: '30vw', maxWidth: '400px', height: '30vw', maxHeight: '400px' }}></div>
          <div className="absolute top-60 bg-cyan-500 bg-opacity-5 rounded-full blur-2xl animate-bounce" style={{ right: '5%', width: '25vw', maxWidth: '350px', height: '25vw', maxHeight: '350px' }}></div>
          <div className="absolute bottom-80 bg-pink-500 bg-opacity-5 rounded-full blur-3xl animate-pulse" style={{ left: '15%', width: '28vw', maxWidth: '380px', height: '28vw', maxHeight: '380px' }}></div>
        </div>

        {/* Header - JadwalJaga Container Pattern */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pt-8 pb-6">
          <div className="text-center mb-8">
            <h1 className={`font-bold bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mb-2
              ${isIpad ? 'text-3xl md:text-4xl lg:text-5xl' : 'text-2xl sm:text-3xl'}
            `}>
              Doctor Profile
            </h1>
            <p className={`text-purple-200 ${isIpad ? 'text-lg md:text-xl' : 'text-base'}`}>
              Professional Information & Achievements
            </p>
          </div>

          {/* Tab Navigation */}
          <div className="flex justify-center mb-8">
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-2 border border-white/20">
              <div className="flex space-x-2 md:space-x-4">
                {[
                  { id: 'profile', label: 'Profile', icon: User },
                  { id: 'achievements', label: 'Achievements', icon: Award },
                  { id: 'certifications', label: 'Certifications', icon: GraduationCap },
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
                      <span className="text-sm font-medium hidden sm:block">{tab.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        </div>

        {/* Content - JadwalJaga Container Pattern */}
        <div className="relative z-10 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-16 2xl:px-20 pb-32">
          
          {/* Profile Tab */}
          {activeTab === 'profile' && (
            <div className="space-y-8">
              {/* Profile Header Card */}
              <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 md:p-8 border border-white/20">
                <div className="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
                  {/* Avatar */}
                  <div className="relative">
                    <div className={`
                      ${isIpad ? 'w-32 h-32 md:w-40 md:h-40' : 'w-24 h-24 sm:w-32 sm:h-32'}
                      bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-3xl 
                      flex items-center justify-center relative overflow-hidden
                    `}>
                      <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                      <User className={`text-white relative z-10 ${isIpad ? 'w-16 h-16 md:w-20 md:h-20' : 'w-12 h-12 sm:w-16 sm:h-16'}`} />
                    </div>
                    <button className="absolute -bottom-2 -right-2 w-10 h-10 bg-purple-600 hover:bg-purple-700 rounded-full flex items-center justify-center border-2 border-white transition-colors">
                      <Camera className="w-5 h-5 text-white" />
                    </button>
                    {/* Level Badge */}
                    <div className="absolute -top-3 -left-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-black font-bold px-3 py-1 rounded-full border-2 border-white shadow-lg">
                      Lv.7
                    </div>
                  </div>

                  {/* Profile Info */}
                  <div className="flex-1 text-center md:text-left">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                      <div>
                        <h2 className={`font-bold text-white mb-2 ${isIpad ? 'text-2xl md:text-3xl' : 'text-xl sm:text-2xl'}`}>
                          {profileData.name}
                        </h2>
                        <p className={`text-purple-300 mb-2 ${isIpad ? 'text-lg' : 'text-base'}`}>
                          {profileData.specialization}
                        </p>
                        <p className={`text-gray-400 ${isIpad ? 'text-base' : 'text-sm'}`}>
                          {profileData.hospital}
                        </p>
                      </div>
                      <button
                        onClick={() => setIsEditing(true)}
                        className="mt-4 md:mt-0 flex items-center space-x-2 bg-purple-600/30 hover:bg-purple-600/50 px-4 py-2 rounded-xl transition-colors border border-purple-400/30"
                      >
                        <Edit className="w-4 h-4 text-purple-300" />
                        <span className="text-purple-300 text-sm font-medium">Edit Profile</span>
                      </button>
                    </div>

                    {/* Quick Stats */}
                    <div className="grid grid-cols-3 gap-4">
                      <div className="text-center">
                        <div className={`font-bold text-cyan-400 ${isIpad ? 'text-xl md:text-2xl' : 'text-lg'}`}>Level 7</div>
                        <div className={`text-gray-400 ${isIpad ? 'text-sm' : 'text-xs'}`}>Doctor Rank</div>
                      </div>
                      <div className="text-center">
                        <div className={`font-bold text-green-400 ${isIpad ? 'text-xl md:text-2xl' : 'text-lg'}`}>2,847</div>
                        <div className={`text-gray-400 ${isIpad ? 'text-sm' : 'text-xs'}`}>XP Points</div>
                      </div>
                      <div className="text-center">
                        <div className={`font-bold text-yellow-400 ${isIpad ? 'text-xl md:text-2xl' : 'text-lg'}`}>96.5%</div>
                        <div className={`text-gray-400 ${isIpad ? 'text-sm' : 'text-xs'}`}>Performance</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Profile Details - JadwalJaga Responsive Pattern Applied */}
              <div className={`
                grid gap-6 md:gap-8
                ${isIpad && orientation === 'landscape' 
                  ? 'lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-2' 
                  : 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2'
                }
              `}>
                {/* Personal Information */}
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-white/30 transition-all duration-300">
                  <h3 className={`font-bold text-white mb-6 flex items-center ${isIpad ? 'text-xl md:text-2xl' : 'text-lg sm:text-xl'}`}>
                    <User className={`mr-2 text-cyan-400 ${isIpad ? 'w-6 h-6' : 'w-5 h-5'}`} />
                    Personal Information
                  </h3>
                  
                  <div className="space-y-4">
                    {[
                      { icon: Mail, label: 'Email', value: profileData.email },
                      { icon: Phone, label: 'Phone', value: profileData.phone },
                      { icon: MapPin, label: 'Address', value: profileData.address },
                      { icon: Calendar, label: 'Birth Date', value: profileData.birthDate },
                      { icon: User, label: 'Gender', value: profileData.gender }
                    ].map((item, index) => {
                      const Icon = item.icon;
                      return (
                        <div key={index} className="flex items-start space-x-3">
                          <Icon className={`text-gray-400 mt-0.5 flex-shrink-0 ${isIpad ? 'w-5 h-5' : 'w-4 h-4 sm:w-5 sm:h-5'}`} />
                          <div>
                            <div className={`text-gray-400 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>{item.label}</div>
                            <div className={`text-white font-medium ${isIpad ? 'text-base' : 'text-sm sm:text-base'}`}>{item.value}</div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Professional Information */}
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-white/30 transition-all duration-300">
                  <h3 className={`font-bold text-white mb-6 flex items-center ${isIpad ? 'text-xl md:text-2xl' : 'text-lg sm:text-xl'}`}>
                    <Stethoscope className={`mr-2 text-purple-400 ${isIpad ? 'w-6 h-6' : 'w-5 h-5'}`} />
                    Professional Information
                  </h3>
                  
                  <div className="space-y-4">
                    {[
                      { icon: Award, label: 'License Number', value: profileData.licenseNumber },
                      { icon: Building, label: 'Hospital', value: profileData.hospital },
                      { icon: Clock, label: 'Experience', value: profileData.experience },
                      { icon: Target, label: 'Specialization', value: profileData.specialization }
                    ].map((item, index) => {
                      const Icon = item.icon;
                      return (
                        <div key={index} className="flex items-start space-x-3">
                          <Icon className={`text-gray-400 mt-0.5 flex-shrink-0 ${isIpad ? 'w-5 h-5' : 'w-4 h-4 sm:w-5 sm:h-5'}`} />
                          <div>
                            <div className={`text-gray-400 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>{item.label}</div>
                            <div className={`text-white font-medium ${isIpad ? 'text-base' : 'text-sm sm:text-base'}`}>{item.value}</div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Bio */}
              <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-white/30 transition-all duration-300">
                <h3 className={`font-bold text-white mb-4 flex items-center ${isIpad ? 'text-xl md:text-2xl' : 'text-lg sm:text-xl'}`}>
                  <FileText className={`mr-2 text-green-400 ${isIpad ? 'w-6 h-6' : 'w-5 h-5'}`} />
                  Bio
                </h3>
                <p className={`text-gray-300 leading-relaxed ${isIpad ? 'text-base md:text-lg' : 'text-sm sm:text-base'}`}>{profileData.bio}</p>
              </div>

              {/* Statistics Grid - JadwalJaga Pattern Applied */}
              <div className={`
                grid gap-4 md:gap-6
                ${isIpad && orientation === 'landscape' 
                  ? 'grid-cols-3 lg:grid-cols-6 xl:grid-cols-6 2xl:grid-cols-6' 
                  : 'grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-6 xl:grid-cols-6'
                }
              `}>
                {stats.map((stat, index) => {
                  const Icon = stat.icon;
                  return (
                    <div key={index} className="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20 hover:border-white/30 transition-all duration-300 text-center">
                      <Icon className={`mx-auto mb-2 ${stat.color} ${isIpad ? 'w-8 h-8' : 'w-6 h-6 sm:w-8 sm:h-8'}`} />
                      <div className={`font-bold text-white ${isIpad ? 'text-lg md:text-xl' : 'text-base sm:text-lg'}`}>{stat.value}</div>
                      <div className={`text-gray-400 ${isIpad ? 'text-xs' : 'text-xs sm:text-xs'}`}>{stat.label}</div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* Achievements Tab */}
          {activeTab === 'achievements' && (
            <div className="space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-6">
                Doctor Achievements
              </h2>
              
              <div className={`
                grid gap-6 md:gap-8
                ${isIpad && orientation === 'landscape' 
                  ? 'lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3' 
                  : 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3'
                }
              `}>
                {achievements.map((achievement) => {
                  const Icon = achievement.icon;
                  return (
                    <div key={achievement.id} className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-purple-400/50 transition-all duration-300 group">
                      <div className="flex items-start space-x-4 mb-4">
                        <div className={`w-12 h-12 bg-gradient-to-br ${achievement.color} rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300`}>
                          <Icon className="w-6 h-6 text-white" />
                        </div>
                        <div className="flex-1">
                          <div className="flex items-center justify-between mb-2">
                            <h3 className="text-lg font-bold text-white">{achievement.title}</h3>
                            <div className={`px-2 py-1 rounded-full text-xs font-medium border ${getRarityColor(achievement.rarity)}`}>
                              {achievement.rarity}
                            </div>
                          </div>
                          <p className="text-gray-300 text-sm mb-2">{achievement.description}</p>
                          <p className="text-gray-400 text-xs">{achievement.date}</p>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* Certifications Tab */}
          {activeTab === 'certifications' && (
            <div className="space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-6">
                Medical Certifications
              </h2>
              
              <div className="space-y-4">
                {certifications.map((cert) => (
                  <div key={cert.id} className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-green-400/50 transition-all duration-300">
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center space-x-4">
                        <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                          <GraduationCap className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="text-lg font-bold text-white">{cert.name}</h3>
                          <p className="text-green-300">{cert.issuer}</p>
                        </div>
                      </div>
                      <div className="flex items-center space-x-2 bg-green-500/20 px-3 py-1.5 rounded-full border border-green-400/50">
                        <CheckCircle className="w-4 h-4 text-green-400" />
                        <span className="text-green-400 text-sm font-medium">{cert.status}</span>
                      </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <div className="text-gray-400 text-sm">Issue Date</div>
                        <div className="text-white font-medium">{cert.issueDate}</div>
                      </div>
                      <div>
                        <div className="text-gray-400 text-sm">Expiry Date</div>
                        <div className="text-white font-medium">{cert.expiryDate}</div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Settings Tab */}
          {activeTab === 'settings' && (
            <div className="space-y-6">
              <h2 className="text-2xl font-bold text-white text-center mb-6">
                Account Settings
              </h2>
              
              <div className={`
                grid gap-6 md:gap-8
                ${isIpad && orientation === 'landscape' 
                  ? 'lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-2' 
                  : 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2'
                }
              `}>
                {/* Security Settings */}
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-white/30 transition-all duration-300">
                  <h3 className={`font-bold text-white mb-6 flex items-center ${isIpad ? 'text-xl md:text-2xl' : 'text-lg sm:text-xl'}`}>
                    <Lock className={`mr-2 text-red-400 ${isIpad ? 'w-6 h-6' : 'w-5 h-5'}`} />
                    Security Settings
                  </h3>
                  
                  <div className="space-y-4">
                    <button className="w-full flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/10 hover:border-purple-400/50 transition-colors">
                      <div className="flex items-center space-x-3">
                        <Lock className="w-5 h-5 text-gray-400" />
                        <span className="text-white">Change Password</span>
                      </div>
                      <Settings className="w-4 h-4 text-gray-400" />
                    </button>
                    
                    <button className="w-full flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/10 hover:border-purple-400/50 transition-colors">
                      <div className="flex items-center space-x-3">
                        <Shield className="w-5 h-5 text-gray-400" />
                        <span className="text-white">Two-Factor Authentication</span>
                      </div>
                      <div className="text-green-400 text-sm">Enabled</div>
                    </button>
                  </div>
                </div>

                {/* Notification Settings */}
                <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 hover:border-white/30 transition-all duration-300">
                  <h3 className={`font-bold text-white mb-6 flex items-center ${isIpad ? 'text-xl md:text-2xl' : 'text-lg sm:text-xl'}`}>
                    <Bell className={`mr-2 text-yellow-400 ${isIpad ? 'w-6 h-6' : 'w-5 h-5'}`} />
                    Notifications
                  </h3>
                  
                  <div className="space-y-4">
                    {[
                      { label: 'Schedule Reminders', enabled: true },
                      { label: 'Patient Updates', enabled: true },
                      { label: 'System Alerts', enabled: false },
                      { label: 'Marketing Emails', enabled: false }
                    ].map((setting, index) => (
                      <div key={index} className="flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/10">
                        <span className="text-white">{setting.label}</span>
                        <div className={`w-12 h-6 rounded-full p-1 transition-colors ${setting.enabled ? 'bg-green-500' : 'bg-gray-600'}`}>
                          <div className={`w-4 h-4 rounded-full bg-white transition-transform ${setting.enabled ? 'translate-x-6' : 'translate-x-0'}`}></div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Edit Profile Modal */}
        {isEditing && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div className="bg-white/10 backdrop-blur-2xl rounded-3xl border border-white/20 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-2xl font-bold text-white">Edit Profile</h2>
                  <button
                    onClick={handleCancel}
                    className="p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors"
                  >
                    <X className="w-5 h-5 text-white" />
                  </button>
                </div>

                <div className="space-y-6">
                  {/* Form fields - JadwalJaga Responsive Pattern */}
                  <div className={`
                    grid gap-4 md:gap-6
                    ${isIpad && orientation === 'landscape' 
                      ? 'lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-2' 
                      : 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2'
                    }
                  `}>
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Full Name</label>
                      <input
                        type="text"
                        value={profileData.name}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                        placeholder="Enter full name"
                      />
                    </div>
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Email</label>
                      <input
                        type="email"
                        value={profileData.email}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                        placeholder="Enter email"
                      />
                    </div>
                  </div>

                  <div className="flex flex-col space-y-3 md:flex-row md:space-y-0 md:space-x-4">
                    <button
                      onClick={handleSave}
                      className="flex-1 flex items-center justify-center space-x-2 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-xl transition-colors"
                    >
                      <Save className="w-4 h-4" />
                      <span>Save Changes</span>
                    </button>
                    <button
                      onClick={handleCancel}
                      className="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 rounded-xl transition-colors"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProfileComponent;