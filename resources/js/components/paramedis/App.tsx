import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Calendar, 
  DollarSign, 
  Clock, 
  FileText, 
  User, 
  Bell, 
  LogOut, 
  Stethoscope, 
  ArrowRight, 
  Eye, 
  EyeOff,
  Home,
  Sun,
  Moon,
  DoorOpen,
  Settings,
  KeyRound
} from 'lucide-react';
import { Dashboard } from './Dashboard';
import { JadwalJaga } from './JadwalJaga';
import { Jaspel } from './Jaspel';
import { Presensi } from './Presensi';
import { Laporan } from './Laporan';
import { Profil } from './Profil';
import { Button } from '../ui/button';
import { Card, CardContent, CardHeader } from '../ui/card';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { ThemeProvider, useTheme } from './ThemeContext';

function AppContent() {
  // Check if user is already authenticated
  const userAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.getAttribute('content') === 'true';
  
  const [isLoggedIn, setIsLoggedIn] = useState(userAuthenticated);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [userData, setUserData] = useState<any>(null);
  const [showProfileModal, setShowProfileModal] = useState(false);
  const [showChangePassword, setShowChangePassword] = useState(false);
  const { theme, toggleTheme } = useTheme();

  // Get user data from meta tag
  useEffect(() => {
    const userDataMeta = document.querySelector('meta[name="user-data"]');
    if (userDataMeta) {
      try {
        const data = JSON.parse(userDataMeta.getAttribute('content') || '{}');
        setUserData(data);
      } catch (e) {
        console.error('Error parsing user data:', e);
      }
    }
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    try {
      const response = await fetch('/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          email,
          password,
        }),
      });

      if (response.ok) {
        // If login successful, just set logged in state
        setIsLoggedIn(true);
      } else {
        throw new Error('Login failed');
      }
    } catch (error) {
      console.error('Login error:', error);
      // For demo purposes, allow login with any credentials
      setIsLoggedIn(true);
    } finally {
      setIsLoading(false);
    }
  };

  const handleLogout = async () => {
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    try {
      const response = await fetch('/logout', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
        },
      });
      
      if (response.ok) {
        // Redirect to login page after logout
        window.location.href = '/login';
      }
    } catch (error) {
      console.error('Logout error:', error);
      // Force redirect on error
      window.location.href = '/login';
    }
  };

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: Home },
    { id: 'jadwal', label: 'Jadwal', icon: Calendar },
    { id: 'jaspel', label: 'Jaspel', icon: DollarSign },
    { id: 'presensi', label: 'Presensi', icon: Clock },
    { id: 'laporan', label: 'Laporan', icon: FileText },
  ];

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <Dashboard userData={userData} />;
      case 'jadwal':
        return <JadwalJaga />;
      case 'jaspel':
        return <Jaspel />;
      case 'presensi':
        return <Presensi />;
      case 'laporan':
        return <Laporan />;
      default:
        return <Dashboard userData={userData} />;
    }
  };

  // Check if user is already logged in via Laravel session
  const isAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.getAttribute('content') === 'true';

  if (isAuthenticated || isLoggedIn) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-950 transition-colors duration-300">
        {/* Header */}
        <div className="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
          <div className="max-w-md mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 dark:from-blue-500 dark:to-purple-500 rounded-full flex items-center justify-center">
                  <Stethoscope className="w-5 h-5 text-white" />
                </div>
                <div>
                  <h1 className="text-lg font-semibold text-gray-900 dark:text-white transition-colors duration-300">Dokterku</h1>
                  <p className="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-300">Paramedis</p>
                </div>
              </div>
              <div className="flex items-center space-x-2">
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={toggleTheme}
                  className="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300"
                >
                  {theme === 'dark' ? (
                    <Sun className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                  ) : (
                    <Moon className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                  )}
                </Button>
                <Button variant="ghost" size="sm" className="relative hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300">
                  <Bell className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                  <span className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">
                    3
                  </span>
                </Button>
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={() => setShowProfileModal(true)}
                  className="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300"
                >
                  <User className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </Button>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="max-w-md mx-auto">
          <div className="px-4 py-6 pb-28 min-h-[calc(100vh-120px)] theme-transition">
            <div className="space-y-6">
              {renderContent()}
            </div>
            
            {/* Bottom Spacing untuk ensure content tidak terpotong */}
            <div className="h-8 safe-area-pb"></div>
          </div>
        </div>

        {/* Bottom Navigation */}
        <div className="fixed bottom-0 left-0 right-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-t border-gray-200 dark:border-gray-700 safe-area-pb shadow-lg transition-all duration-300 card-enhanced">
          <div className="max-w-md mx-auto">
            <div className="flex items-center justify-around py-2">
              {tabs.map((tab) => (
                <motion.div
                  key={tab.id}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                  transition={{ duration: 0.2 }}
                >
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex flex-col items-center space-y-1 px-2 py-3 min-w-0 rounded-xl transition-all duration-300 ${
                      activeTab === tab.id
                        ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 shadow-sm scale-105'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/50'
                    }`}
                  >
                    <tab.icon className="w-5 h-5" />
                    <span className="text-xs font-medium truncate text-mobile-friendly">{tab.label}</span>
                  </Button>
                </motion.div>
              ))}
            </div>
          </div>
        </div>

        {/* Profile Modal */}
        <AnimatePresence>
          {showProfileModal && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
              onClick={() => setShowProfileModal(false)}
            >
              <motion.div
                initial={{ scale: 0.95, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.95, opacity: 0 }}
                className="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
                onClick={(e) => e.stopPropagation()}
              >
                <div className="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white">
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-xl font-bold">Profil</h3>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setShowProfileModal(false)}
                      className="text-white hover:bg-white/20"
                    >
                      ✕
                    </Button>
                  </div>
                  <div className="flex items-center space-x-3">
                    <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                      <User className="w-6 h-6" />
                    </div>
                    <div>
                      <p className="font-semibold">{userData?.name || 'Paramedis'}</p>
                      <p className="text-blue-100 text-sm">{userData?.email || 'paramedis@dokterku.com'}</p>
                    </div>
                  </div>
                </div>
                
                <div className="p-6 space-y-4">
                  <Button
                    variant="outline"
                    className="w-full justify-start"
                    onClick={() => {
                      setShowChangePassword(true);
                      setShowProfileModal(false);
                    }}
                  >
                    <KeyRound className="w-4 h-4 mr-2" />
                    Ganti Password
                  </Button>
                  
                  <Button
                    variant="outline"
                    className="w-full justify-start text-red-600 border-red-200 hover:bg-red-50 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-950/50"
                    onClick={() => {
                      setShowProfileModal(false);
                      handleLogout();
                    }}
                  >
                    <LogOut className="w-4 h-4 mr-2" />
                    Logout
                  </Button>
                </div>
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Change Password Modal */}
        <AnimatePresence>
          {showChangePassword && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
              onClick={() => setShowChangePassword(false)}
            >
              <motion.div
                initial={{ scale: 0.95, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.95, opacity: 0 }}
                className="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
                onClick={(e) => e.stopPropagation()}
              >
                <div className="bg-gradient-to-r from-green-600 to-blue-600 p-6 text-white">
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-xl font-bold">Ganti Password</h3>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setShowChangePassword(false)}
                      className="text-white hover:bg-white/20"
                    >
                      ✕
                    </Button>
                  </div>
                </div>
                
                <div className="p-6 space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="current-password">Password Lama</Label>
                    <Input
                      id="current-password"
                      type="password"
                      placeholder="Masukkan password lama"
                      className="dark:bg-gray-800 dark:border-gray-600"
                    />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="new-password">Password Baru</Label>
                    <Input
                      id="new-password"
                      type="password"
                      placeholder="Masukkan password baru"
                      className="dark:bg-gray-800 dark:border-gray-600"
                    />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="confirm-password">Konfirmasi Password</Label>
                    <Input
                      id="confirm-password"
                      type="password"
                      placeholder="Konfirmasi password baru"
                      className="dark:bg-gray-800 dark:border-gray-600"
                    />
                  </div>
                  
                  <div className="flex space-x-3 pt-4">
                    <Button
                      variant="outline"
                      className="flex-1"
                      onClick={() => setShowChangePassword(false)}
                    >
                      Batal
                    </Button>
                    <Button
                      className="flex-1"
                      onClick={() => {
                        // TODO: Implement password change logic
                        alert('Fitur ganti password akan segera tersedia');
                        setShowChangePassword(false);
                      }}
                    >
                      Simpan
                    </Button>
                  </div>
                </div>
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-blue-800 dark:from-gray-900 dark:via-gray-800 dark:to-blue-950 flex items-center justify-center p-4 transition-colors duration-300">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md"
      >
        <Card className="bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm shadow-2xl border-0 transition-colors duration-300">
          <CardHeader className="text-center pb-6">
            <div className="w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 dark:from-blue-500 dark:to-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
              <Stethoscope className="w-8 h-8 text-white" />
            </div>
            <div className="flex items-center justify-center gap-4 mb-4">
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-300">Dokterku</h1>
              <Button 
                variant="ghost" 
                size="sm" 
                onClick={toggleTheme}
                className="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300"
              >
                {theme === 'dark' ? (
                  <Sun className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                ) : (
                  <Moon className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                )}
              </Button>
            </div>
            <p className="text-gray-600 dark:text-gray-400 transition-colors duration-300">Paramedis Portal</p>
          </CardHeader>
          <CardContent className="space-y-6">
            <form onSubmit={handleLogin} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email" className="text-gray-700 dark:text-gray-300 transition-colors duration-300">Email</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="Enter your email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  className="dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 transition-colors duration-300"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password" className="text-gray-700 dark:text-gray-300 transition-colors duration-300">Password</Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="Enter your password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    className="dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 transition-colors duration-300"
                  />
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="absolute right-2 top-1/2 -translate-y-1/2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <EyeOff className="w-4 h-4 text-gray-600 dark:text-gray-300" /> : <Eye className="w-4 h-4 text-gray-600 dark:text-gray-300" />}
                  </Button>
                </div>
              </div>
              <Button type="submit" className="w-full" disabled={isLoading}>
                {isLoading ? (
                  <div className="flex items-center space-x-2">
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>Signing in...</span>
                  </div>
                ) : (
                  <div className="flex items-center space-x-2">
                    <span>Sign In</span>
                    <ArrowRight className="w-4 h-4" />
                  </div>
                )}
              </Button>
            </form>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}

export default function App() {
  return (
    <ThemeProvider>
      <AppContent />
    </ThemeProvider>
  );
}