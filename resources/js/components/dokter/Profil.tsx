import React, { useState, useEffect } from 'react';
import { RobustJsonParser } from '../../utils/robustJsonParser';
import { ApiClient } from '../../utils/apiClient';
import { 
  User, 
  Edit, 
  Camera, 
  Phone, 
  Mail, 
  MapPin, 
  Calendar, 
  Settings,
  Lock,
  Bell,
  Stethoscope,
  Building,
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
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [show2FAModal, setShow2FAModal] = useState(false);
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  });
  const [twoFactorData, setTwoFactorData] = useState({
    qr_code: '',
    secret: '',
    backup_codes: [],
    verification_code: ''
  });
  const [securityLoading, setSecurityLoading] = useState(false);
  const [profileData, setProfileData] = useState({
    name: 'Loading...',
    email: 'loading@example.com',
    phone: '',
    address: '',
    birthDate: '',
    gender: 'Tidak ditentukan',
    nik: '',
    nomor_sip: '',
    jabatan: 'Dokter',
    spesialisasi: '',
    tanggal_bergabung: '',
    status_akun: '',
  });

  const [editData, setEditData] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
    birthDate: '',
    gender: '',
    nik: '',
    nomor_sip: '',
    jabatan: '',
    spesialisasi: ''
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

  // Load user data
  useEffect(() => {
    const loadUserData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v2/dashboards/dokter/', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });

        if (response.ok) {
          const data = await response.json();
          if (data.success && data.data?.user) {
            const user = data.data.user;
            setProfileData(prev => ({
              ...prev,
              name: user.name || prev.name,
              email: user.email || prev.email,
              phone: user.phone || prev.phone,
              address: user.address || prev.address,
              birthDate: user.date_of_birth || prev.birthDate,
              gender: user.gender || prev.gender,
                            nik: user.nik || prev.nik,
              nomor_sip: user.nomor_sip || prev.nomor_sip,
              jabatan: user.jabatan || prev.jabatan,
              spesialisasi: user.spesialisasi || prev.spesialisasi,
              tanggal_bergabung: user.tanggal_bergabung || prev.tanggal_bergabung,
              status_akun: user.status_akun || prev.status_akun
            }));
            
            // Set edit data for form
            setEditData({
              name: user.name || '',
              email: user.email || '',
              phone: user.phone || '',
              address: user.address || '',
              birthDate: user.date_of_birth || '',
              gender: user.gender === 'Laki-laki' ? 'male' : user.gender === 'Perempuan' ? 'female' : '',
                            nik: user.nik || '',
              nomor_sip: user.nomor_sip || '',
              jabatan: user.jabatan || '',
              spesialisasi: user.spesialisasi || ''
            });
          }
        }
      } catch (error) {
        console.error('Error loading user data:', error);
      }
    };

    loadUserData();
  }, []);





  const handleSave = async () => {
    try {
      const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      const response = await fetch('/api/v2/dashboards/dokter/update-profile', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token || '',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(editData)
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          // Update profile data with new values
          setProfileData(prev => ({
            ...prev,
            name: editData.name || prev.name,
            email: editData.email || prev.email,
            phone: editData.phone || prev.phone,
            address: editData.address || prev.address,
            birthDate: editData.birthDate || prev.birthDate,
            gender: editData.gender || prev.gender,
                        nik: editData.nik || prev.nik,
            nomor_sip: editData.nomor_sip || prev.nomor_sip,
            jabatan: editData.jabatan || prev.jabatan,
            spesialisasi: editData.spesialisasi || prev.spesialisasi
          }));
          setIsEditing(false);
        }
      }
    } catch (error) {
      console.error('Error saving profile:', error);
    }
  };

  const handleCancel = () => {
    setIsEditing(false);
    // Reset edit data to current profile data
    setEditData({
      name: profileData.name,
      email: profileData.email,
      phone: profileData.phone,
      address: profileData.address,
      birthDate: profileData.birthDate,
      gender: profileData.gender,
      nik: profileData.nik,
      nomor_sip: profileData.nomor_sip,
      jabatan: profileData.jabatan,
      spesialisasi: profileData.spesialisasi
    });
  };

  const handlePasswordChange = async () => {
    try {
      setSecurityLoading(true);
      const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      const response = await fetch('/api/v2/auth/change-password', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token || '',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(passwordData)
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        alert('Password berhasil diubah!');
        setShowPasswordModal(false);
        setPasswordData({
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        });
      } else {
        alert(data.message || 'Gagal mengubah password');
      }
    } catch (error) {
      console.error('Error changing password:', error);
      alert('Terjadi kesalahan saat mengubah password');
    } finally {
      setSecurityLoading(false);
    }
  };

  const handleSetup2FA = async () => {
    try {
      setSecurityLoading(true);
      
      console.log('🚀 Starting 2FA setup...');
      
      const result = await ApiClient.post('/api/v2/auth/setup-2fa');

      if (!result.success) {
        console.error('2FA Setup API Error:', result.error);
        alert(result.error || 'Gagal setup 2FA');
        return;
      }

      const data = result.data;
      if (data && data.success) {
        console.log('✅ 2FA Setup successful');
        setTwoFactorData({
          qr_code: data.data.qr_code,
          secret: data.data.secret,
          backup_codes: data.data.backup_codes || [],
          verification_code: ''
        });
        setShow2FAModal(true);
      } else {
        alert(data?.message || 'Gagal setup 2FA');
      }
    } catch (error) {
      console.error('Error setting up 2FA:', error);
      alert('Terjadi kesalahan saat setup 2FA');
    } finally {
      setSecurityLoading(false);
    }
  };

  const handleVerify2FA = async () => {
    try {
      setSecurityLoading(true);
      const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      const response = await fetch('/api/v2/auth/verify-2fa', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token || '',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          code: twoFactorData.verification_code
        })
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        alert('2FA berhasil diaktifkan!');
        setShow2FAModal(false);
        // Refresh page to update 2FA status
        window.location.reload();
      } else {
        alert(data.message || 'Kode verifikasi salah');
      }
    } catch (error) {
      console.error('Error verifying 2FA:', error);
      alert('Terjadi kesalahan saat verifikasi 2FA');
    } finally {
      setSecurityLoading(false);
    }
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
                          {profileData.jabatan === 'dokter_umum' ? 'Dokter Umum' : 
                           profileData.jabatan === 'dokter_gigi' ? 'Dokter Gigi' : 
                           profileData.jabatan === 'dokter_spesialis' ? 'Dokter Spesialis' : 
                           profileData.jabatan}
                        </p>
                        <p className={`text-gray-400 ${isIpad ? 'text-base' : 'text-sm'}`}>
                          {profileData.spesialisasi || 'Tidak ditentukan'}
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
                      { icon: User, label: 'NIK', value: profileData.nik || 'Tidak ditentukan' },
                      { icon: Building, label: 'Nomor SIP', value: profileData.nomor_sip || 'Tidak ditentukan' },
                      { icon: Calendar, label: 'Tanggal Bergabung', value: profileData.tanggal_bergabung || 'Tidak ditentukan' },
                      { icon: Stethoscope, label: 'Jabatan', value: profileData.jabatan === 'dokter_umum' ? 'Dokter Umum' : 
                                                           profileData.jabatan === 'dokter_gigi' ? 'Dokter Gigi' : 
                                                           profileData.jabatan === 'dokter_spesialis' ? 'Dokter Spesialis' : 
                                                           profileData.jabatan }
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
                    <button 
                      onClick={() => setShowPasswordModal(true)}
                      disabled={securityLoading}
                      className="w-full flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/10 hover:border-purple-400/50 transition-colors disabled:opacity-50"
                    >
                      <div className="flex items-center space-x-3">
                        <Lock className="w-5 h-5 text-gray-400" />
                        <span className="text-white">Change Password</span>
                      </div>
                      <Settings className="w-4 h-4 text-gray-400" />
                    </button>
                    
                    <button 
                      onClick={handleSetup2FA}
                      disabled={securityLoading}
                      className="w-full flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/10 hover:border-purple-400/50 transition-colors disabled:opacity-50"
                    >
                      <div className="flex items-center space-x-3">
                        <Lock className="w-5 h-5 text-gray-400" />
                        <span className="text-white">Two-Factor Authentication</span>
                      </div>
                      <div className="text-orange-400 text-sm">Setup</div>
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

        {/* Password Change Modal */}
        {showPasswordModal && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div className="bg-white/10 backdrop-blur-2xl rounded-3xl border border-white/20 w-full max-w-md">
              <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-bold text-white">Change Password</h2>
                  <button
                    onClick={() => setShowPasswordModal(false)}
                    className="p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors"
                  >
                    <X className="w-5 h-5 text-white" />
                  </button>
                </div>

                <div className="space-y-4">
                  <div>
                    <label className="block text-gray-300 mb-2 text-sm">Current Password</label>
                    <input
                      type="password"
                      value={passwordData.current_password}
                      onChange={(e) => setPasswordData(prev => ({ ...prev, current_password: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors"
                      placeholder="Enter current password"
                    />
                  </div>
                  <div>
                    <label className="block text-gray-300 mb-2 text-sm">New Password</label>
                    <input
                      type="password"
                      value={passwordData.new_password}
                      onChange={(e) => setPasswordData(prev => ({ ...prev, new_password: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors"
                      placeholder="Enter new password (min 8 characters)"
                    />
                  </div>
                  <div>
                    <label className="block text-gray-300 mb-2 text-sm">Confirm New Password</label>
                    <input
                      type="password"
                      value={passwordData.new_password_confirmation}
                      onChange={(e) => setPasswordData(prev => ({ ...prev, new_password_confirmation: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors"
                      placeholder="Confirm new password"
                    />
                  </div>
                </div>

                <div className="flex space-x-4 mt-6">
                  <button
                    onClick={handlePasswordChange}
                    disabled={securityLoading || !passwordData.current_password || !passwordData.new_password || passwordData.new_password !== passwordData.new_password_confirmation}
                    className="flex-1 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-600 text-white py-3 rounded-xl transition-colors"
                  >
                    {securityLoading ? 'Changing...' : 'Change Password'}
                  </button>
                  <button
                    onClick={() => setShowPasswordModal(false)}
                    className="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 rounded-xl transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* 2FA Setup Modal */}
        {show2FAModal && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-40 flex items-start justify-center pt-4 pb-28 px-4">
            <div className="bg-white/10 backdrop-blur-2xl rounded-3xl border border-white/20 w-full max-w-2xl max-h-[75vh] overflow-y-auto scroll-smooth shadow-2xl" style={{scrollBehavior: 'smooth', scrollPaddingTop: '2rem'}}>
              <div className="p-6 relative">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-bold text-white">Setup Two-Factor Authentication</h2>
                  <button
                    onClick={() => setShow2FAModal(false)}
                    className="p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors"
                  >
                    <X className="w-5 h-5 text-white" />
                  </button>
                </div>

                <div className="space-y-8">
                  {/* QR Code */}
                  <div className="text-center transform transition-all duration-500 ease-out">
                    <h3 className="text-lg font-semibold text-white mb-3">Step 1: Scan QR Code</h3>
                    <p className="text-gray-300 mb-6">Scan this QR code with your authenticator app:</p>
                    <div className="bg-white p-6 rounded-2xl mx-auto w-fit shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 group">
                      <div 
                        className="transition-transform duration-300 group-hover:rotate-2"
                        dangerouslySetInnerHTML={{ __html: twoFactorData.qr_code }} 
                      />
                    </div>
                    <p className="text-gray-400 text-xs mt-3 opacity-60">Click to view larger</p>
                  </div>

                  {/* Manual Secret */}
                  <div>
                    <h3 className="text-lg font-semibold text-white mb-3">Step 2: Manual Entry (Alternative)</h3>
                    <label className="block text-gray-300 mb-3 text-sm">If you can't scan the QR code, enter this secret manually:</label>
                    <div className="bg-white/15 border border-white/30 rounded-xl px-6 py-4 text-white font-mono text-base break-all tracking-wider">
                      {twoFactorData.secret}
                    </div>
                  </div>

                  {/* Verification Code */}
                  <div>
                    <h3 className="text-lg font-semibold text-white mb-3">Step 3: Verify Setup</h3>
                    <label className="block text-gray-300 mb-4 text-sm">Enter the 6-digit code from your authenticator app:</label>
                    <input
                      type="text"
                      value={twoFactorData.verification_code}
                      onChange={(e) => setTwoFactorData(prev => ({ ...prev, verification_code: e.target.value }))}
                      className="w-full bg-white/15 border-2 border-white/30 rounded-xl px-6 py-4 text-white placeholder-gray-400 hover:border-white/40 focus:border-purple-400/70 focus:ring-2 focus:ring-purple-400/20 transition-all text-center font-mono text-xl tracking-widest"
                      placeholder="123456"
                      maxLength={6}
                    />
                  </div>

                  {/* Backup Codes */}
                  <div>
                    <h3 className="text-lg font-semibold text-white mb-3">📋 Backup Codes</h3>
                    <p className="text-gray-300 mb-4 text-sm">Save these codes safely! Use them if you lose access to your authenticator app:</p>
                    <div className="bg-white/15 border border-white/30 rounded-xl px-6 py-4">
                      <div className="grid grid-cols-2 gap-3">
                        {twoFactorData.backup_codes.map((code, index) => (
                          <div key={index} className="bg-white/10 rounded-lg px-4 py-3 text-center">
                            <span className="text-white font-mono text-sm tracking-wider">{code}</span>
                          </div>
                        ))}
                      </div>
                      <div className="mt-4 p-3 bg-yellow-500/20 border border-yellow-500/30 rounded-lg">
                        <p className="text-yellow-200 text-xs text-center">
                          ⚠️ Each code can only be used once. Store them securely!
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-white/20">
                  <button
                    onClick={handleVerify2FA}
                    disabled={securityLoading || twoFactorData.verification_code.length !== 6}
                    className="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-600 disabled:to-gray-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl disabled:cursor-not-allowed"
                  >
                    <span className="flex items-center justify-center gap-2">
                      {securityLoading ? (
                        <>
                          <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                          Verifying...
                        </>
                      ) : (
                        <>
                          🔐 Verify & Enable 2FA
                        </>
                      )}
                    </span>
                  </button>
                  <button
                    onClick={() => setShow2FAModal(false)}
                    className="flex-1 bg-white/10 hover:bg-white/20 border border-white/30 hover:border-white/40 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200"
                  >
                    Cancel
                  </button>
                </div>
                
                {/* Extra padding for bottom navigation */}
                <div className="h-8"></div>
              </div>
            </div>
          </div>
        )}

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
                        value={editData.name}
                        onChange={(e) => setEditData(prev => ({ ...prev, name: e.target.value }))}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                        placeholder="Enter full name"
                      />
                    </div>
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Email</label>
                      <input
                        type="email"
                        value={editData.email}
                        onChange={(e) => setEditData(prev => ({ ...prev, email: e.target.value }))}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                        placeholder="Enter email"
                      />
                    </div>
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Phone</label>
                      <input
                        type="tel"
                        value={editData.phone}
                        onChange={(e) => setEditData(prev => ({ ...prev, phone: e.target.value }))}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                        placeholder="Enter phone number"
                      />
                    </div>
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Gender</label>
                      <select
                        value={editData.gender}
                        onChange={(e) => setEditData(prev => ({ ...prev, gender: e.target.value }))}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                      >
                        <option value="">Select Gender</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                      </select>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Address</label>
                      <textarea
                        value={editData.address}
                        onChange={(e) => setEditData(prev => ({ ...prev, address: e.target.value }))}
                        rows={3}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
                        placeholder="Enter address"
                      />
                    </div>
                    <div>
                      <label className={`block text-gray-300 mb-2 ${isIpad ? 'text-sm' : 'text-xs sm:text-sm'}`}>Birth Date</label>
                      <input
                        type="date"
                        value={editData.birthDate}
                        onChange={(e) => setEditData(prev => ({ ...prev, birthDate: e.target.value }))}
                        className={`w-full bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 hover:border-white/30 focus:border-purple-400/50 transition-colors ${isIpad ? 'px-4 py-3' : 'px-3 py-2 sm:px-4 sm:py-3'}`}
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