import React, { useState, useEffect } from 'react';
import { 
  HelpCircle, 
  CheckCircle, 
  AlertTriangle, 
  Smartphone, 
  Globe, 
  Settings, 
  RefreshCw,
  MapPin,
  Wifi,
  Battery,
  Monitor,
  X
} from 'lucide-react';

interface GPSTroubleshootingGuideProps {
  onClose: () => void;
  currentError?: string | null;
  deviceInfo?: {
    userAgent: string;
    isSecureContext: boolean;
    connectionType: string;
    batteryLevel?: number;
  };
}

interface TroubleshootingStep {
  id: string;
  title: string;
  description: string;
  icon: React.ReactNode;
  difficulty: 'easy' | 'medium' | 'hard';
  estimatedTime: string;
  steps: string[];
  tips: string[];
  commonIssues: string[];
}

const GPSTroubleshootingGuide: React.FC<GPSTroubleshootingGuideProps> = ({
  onClose,
  currentError,
  deviceInfo
}) => {
  const [activeStep, setActiveStep] = useState<string>('permission');
  const [completedSteps, setCompletedSteps] = useState<Set<string>>(new Set());
  const [systemInfo, setSystemInfo] = useState<any>({});

  useEffect(() => {
    // Gather system information
    const info = {
      platform: navigator.platform,
      userAgent: navigator.userAgent,
      language: navigator.language,
      online: navigator.onLine,
      cookieEnabled: navigator.cookieEnabled,
      geolocationSupported: 'geolocation' in navigator,
      isSecureContext: window.isSecureContext,
      connectionType: (navigator as any).connection?.effectiveType || 'unknown',
    };
    setSystemInfo(info);

    // Auto-select step based on error
    if (currentError) {
      if (currentError.includes('ditolak') || currentError.includes('denied')) {
        setActiveStep('permission');
      } else if (currentError.includes('timeout') || currentError.includes('tidak tersedia')) {
        setActiveStep('signal');
      } else if (currentError.includes('akurasi') || currentError.includes('accuracy')) {
        setActiveStep('accuracy');
      }
    }
  }, [currentError]);

  const troubleshootingSteps: TroubleshootingStep[] = [
    {
      id: 'permission',
      title: 'Izin Akses Lokasi',
      description: 'Memastikan browser memiliki izin untuk mengakses lokasi',
      icon: <Settings className="w-5 h-5" />,
      difficulty: 'easy',
      estimatedTime: '1-2 menit',
      steps: [
        'Klik ikon kunci 🔒 atau lokasi 📍 di address bar browser',
        'Pilih "Always allow location" atau "Selalu izinkan lokasi"',
        'Refresh halaman dengan F5 atau Ctrl+R',
        'Jika masih gagal, buka Settings browser → Privacy & Security → Site Settings → Location',
        'Pastikan situs ini diizinkan mengakses lokasi'
      ],
      tips: [
        'Chrome: chrome://settings/content/location',
        'Firefox: about:preferences#privacy → Permissions → Location',
        'Safari: Safari → Preferences → Websites → Location',
        'Hindari mode incognito/private untuk akses lokasi yang stabil'
      ],
      commonIssues: [
        'Izin ditolak secara permanen - perlu reset manual di settings',
        'Mode incognito/private blocking - gunakan normal browsing',
        'Corporate firewall blocking - hubungi IT admin'
      ]
    },
    {
      id: 'signal',
      title: 'Sinyal GPS & Koneksi',
      description: 'Memperbaiki masalah sinyal GPS dan koneksi internet',
      icon: <Globe className="w-5 h-5" />,
      difficulty: 'medium',
      estimatedTime: '3-5 menit',
      steps: [
        'Keluar ke area terbuka (hindari gedung tinggi, basement, atau dalam ruangan)',
        'Pastikan GPS aktif di pengaturan perangkat',
        'Periksa koneksi internet stabil (WiFi atau data seluler)',
        'Tutup aplikasi lain yang menggunakan GPS',
        'Restart GPS: Settings → Location → Turn OFF → Wait 10s → Turn ON',
        'Tunggu 2-3 menit untuk GPS lock pertama kali'
      ],
      tips: [
        'GPS bekerja lebih baik di area terbuka',
        'Hindari penggunaan saat cuaca buruk (hujan lebat, badai)',
        'Posisi tegak lebih baik dari posisi tidur/miring',
        'Android: Clear GPS data di Settings → Apps → GPS/Location'
      ],
      commonIssues: [
        'GPS cold start membutuhkan waktu 2-5 menit',
        'Indoor positioning kurang akurat',
        'Interference dari perangkat elektronik lain'
      ]
    },
    {
      id: 'accuracy',
      title: 'Akurasi & Performance',
      description: 'Meningkatkan akurasi dan kecepatan deteksi GPS',
      icon: <MapPin className="w-5 h-5" />,
      difficulty: 'medium',
      estimatedTime: '2-3 menit',
      steps: [
        'Aktifkan "High accuracy mode" di pengaturan lokasi perangkat',
        'Pastikan Google Location Services aktif (Android)',
        'Update Google Play Services ke versi terbaru',
        'Kalibrasi kompas: buka Google Maps → kalibrasi dengan gerakan angka 8',
        'Clear cache aplikasi browser',
        'Disable VPN atau proxy service'
      ],
      tips: [
        'High accuracy mode menggunakan GPS + WiFi + Mobile data',
        'Kalibrasi kompas secara berkala untuk akurasi yang lebih baik',
        'Hindari penggunaan VPN karena dapat mengubah lokasi',
        'Restart perangkat jika GPS masih tidak akurat'
      ],
      commonIssues: [
        'VPN/Proxy menyebabkan lokasi salah',
        'Cached location data yang usang',
        'Interference dari metal objects atau magnet'
      ]
    },
    {
      id: 'device',
      title: 'Pengaturan Perangkat',
      description: 'Optimasi pengaturan perangkat untuk GPS',
      icon: <Smartphone className="w-5 h-5" />,
      difficulty: 'medium',
      estimatedTime: '3-4 menit',
      steps: [
        'Periksa mode hemat baterai tidak membatasi GPS',
        'Pastikan tanggal dan waktu perangkat akurat',
        'Update sistem operasi ke versi terbaru',
        'Restart perangkat untuk refresh GPS subsystem',
        'Periksa storage space - GPS membutuhkan ruang untuk cache',
        'Disable "Mock location" atau developer options'
      ],
      tips: [
        'Battery saver mode dapat membatasi GPS accuracy',
        'Wrong time/date dapat mengganggu GPS calculations',
        'Developer options → Mock location apps → None',
        'Android: Settings → Location → Improve accuracy → WiFi scanning ON'
      ],
      commonIssues: [
        'Power saving mode limiting GPS performance',
        'Incorrect system time affecting GPS calculations',
        'Mock location apps interfering with real GPS'
      ]
    },
    {
      id: 'browser',
      title: 'Pengaturan Browser',
      description: 'Optimasi browser untuk geolocation API',
      icon: <Monitor className="w-5 h-5" />,
      difficulty: 'easy',
      estimatedTime: '2-3 menit',
      steps: [
        'Update browser ke versi terbaru',
        'Clear browser cache dan cookies',
        'Disable browser extensions yang mungkin interfere',
        'Periksa browser tidak dalam mode privacy/incognito',
        'Reset location permissions: Settings → Site Settings → Location → Reset',
        'Coba browser berbeda sebagai test (Chrome, Firefox, Safari)'
      ],
      tips: [
        'Chrome memiliki geolocation support terbaik',
        'Firefox: about:config → geo.enabled → true',
        'Safari: Develop → Disable Cross-Origin Restrictions',
        'Edge: Settings → Site permissions → Location'
      ],
      commonIssues: [
        'Browser extensions blocking geolocation',
        'Outdated browser dengan geolocation bugs',
        'Privacy settings too restrictive'
      ]
    },
    {
      id: 'network',
      title: 'Koneksi & Firewall',
      description: 'Mengatasi masalah network dan corporate firewall',
      icon: <Wifi className="w-5 h-5" />,
      difficulty: 'hard',
      estimatedTime: '5-10 menit',
      steps: [
        'Periksa koneksi internet stabil dengan speed test',
        'Coba ganti dari WiFi ke mobile data atau sebaliknya',
        'Disable VPN, proxy, atau corporate firewall sementara',
        'Flush DNS: ipconfig /flushdns (Windows) atau sudo dscacheutil -flushcache (Mac)',
        'Restart router/modem jika menggunakan WiFi',
        'Hubungi IT admin jika di corporate network'
      ],
      tips: [
        'Corporate firewall sering blok geolocation APIs',
        'Public WiFi kadang blok location services',
        'Mobile hotspot bisa jadi alternative test',
        'DNS issues dapat affect location accuracy'
      ],
      commonIssues: [
        'Corporate firewall blocking Google Location APIs',
        'ISP blocking certain geolocation services',
        'Network congestion affecting API response times'
      ]
    }
  ];

  const markStepCompleted = (stepId: string) => {
    setCompletedSteps(prev => new Set([...prev, stepId]));
  };

  const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
      case 'easy': return 'text-green-400 bg-green-500/20';
      case 'medium': return 'text-yellow-400 bg-yellow-500/20';
      case 'hard': return 'text-red-400 bg-red-500/20';
      default: return 'text-gray-400 bg-gray-500/20';
    }
  };

  const activeStepData = troubleshootingSteps.find(step => step.id === activeStep);
  const completionRate = (completedSteps.size / troubleshootingSteps.length) * 100;

  return (
    <div className="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-gray-900 border border-gray-700 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-700">
          <div className="flex items-center space-x-3">
            <HelpCircle className="w-6 h-6 text-blue-400" />
            <div>
              <h2 className="text-xl font-bold text-white">GPS Troubleshooting Guide</h2>
              <p className="text-sm text-gray-400">Panduan lengkap mengatasi masalah GPS</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="flex">
          {/* Sidebar */}
          <div className="w-80 border-r border-gray-700 p-4">
            {/* Progress */}
            <div className="mb-6">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium text-gray-300">Progress</span>
                <span className="text-sm text-blue-400">{Math.round(completionRate)}%</span>
              </div>
              <div className="w-full bg-gray-700 rounded-full h-2">
                <div 
                  className="bg-blue-500 h-2 rounded-full transition-all duration-300"
                  style={{ width: `${completionRate}%` }}
                />
              </div>
            </div>

            {/* Current Error */}
            {currentError && (
              <div className="mb-6 p-3 bg-red-500/10 border border-red-400/30 rounded-lg">
                <div className="text-sm font-medium text-red-300 mb-1">Current Issue:</div>
                <div className="text-xs text-red-200">{currentError}</div>
              </div>
            )}

            {/* System Info */}
            <div className="mb-6 p-3 bg-gray-800 rounded-lg">
              <div className="text-sm font-medium text-gray-300 mb-2">System Info</div>
              <div className="space-y-1 text-xs text-gray-400">
                <div>GPS: {systemInfo.geolocationSupported ? '✅ Supported' : '❌ Not Supported'}</div>
                <div>Secure: {systemInfo.isSecureContext ? '✅ HTTPS' : '⚠️ HTTP'}</div>
                <div>Online: {systemInfo.online ? '✅ Connected' : '❌ Offline'}</div>
                <div>Connection: {systemInfo.connectionType}</div>
                {deviceInfo?.batteryLevel && (
                  <div>Battery: {Math.round(deviceInfo.batteryLevel * 100)}%</div>
                )}
              </div>
            </div>

            {/* Steps Navigation */}
            <div className="space-y-2">
              {troubleshootingSteps.map((step) => (
                <button
                  key={step.id}
                  onClick={() => setActiveStep(step.id)}
                  className={`w-full text-left p-3 rounded-lg transition-colors ${
                    activeStep === step.id 
                      ? 'bg-blue-500/20 border border-blue-400/30' 
                      : 'bg-gray-800 hover:bg-gray-700'
                  }`}
                >
                  <div className="flex items-center justify-between mb-1">
                    <div className="flex items-center space-x-2">
                      {completedSteps.has(step.id) ? (
                        <CheckCircle className="w-4 h-4 text-green-400" />
                      ) : (
                        step.icon
                      )}
                      <span className="text-sm font-medium text-white">{step.title}</span>
                    </div>
                    <span className={`px-2 py-1 rounded text-xs ${getDifficultyColor(step.difficulty)}`}>
                      {step.difficulty}
                    </span>
                  </div>
                  <div className="text-xs text-gray-400">{step.description}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Main Content */}
          <div className="flex-1 p-6 overflow-y-auto max-h-[80vh]">
            {activeStepData && (
              <div>
                {/* Step Header */}
                <div className="flex items-center justify-between mb-6">
                  <div className="flex items-center space-x-3">
                    {completedSteps.has(activeStepData.id) ? (
                      <CheckCircle className="w-8 h-8 text-green-400" />
                    ) : (
                      <div className="p-2 bg-blue-500/20 rounded-lg">
                        {activeStepData.icon}
                      </div>
                    )}
                    <div>
                      <h3 className="text-2xl font-bold text-white">{activeStepData.title}</h3>
                      <p className="text-gray-400">{activeStepData.description}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className={`px-3 py-1 rounded-full text-sm font-medium ${getDifficultyColor(activeStepData.difficulty)}`}>
                      {activeStepData.difficulty.toUpperCase()}
                    </div>
                    <div className="text-xs text-gray-400 mt-1">⏱️ {activeStepData.estimatedTime}</div>
                  </div>
                </div>

                {/* Step Instructions */}
                <div className="space-y-6">
                  {/* Steps */}
                  <div>
                    <h4 className="text-lg font-semibold text-white mb-3">📋 Langkah-langkah:</h4>
                    <div className="space-y-3">
                      {activeStepData.steps.map((step, index) => (
                        <div key={index} className="flex items-start space-x-3 p-3 bg-gray-800 rounded-lg">
                          <div className="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                            {index + 1}
                          </div>
                          <div className="text-sm text-gray-300">{step}</div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Tips */}
                  <div>
                    <h4 className="text-lg font-semibold text-white mb-3">💡 Tips & Tricks:</h4>
                    <div className="space-y-2">
                      {activeStepData.tips.map((tip, index) => (
                        <div key={index} className="flex items-start space-x-2 p-3 bg-blue-500/10 border border-blue-400/20 rounded-lg">
                          <span className="text-blue-400 flex-shrink-0">•</span>
                          <span className="text-sm text-blue-200">{tip}</span>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Common Issues */}
                  <div>
                    <h4 className="text-lg font-semibold text-white mb-3">⚠️ Common Issues:</h4>
                    <div className="space-y-2">
                      {activeStepData.commonIssues.map((issue, index) => (
                        <div key={index} className="flex items-start space-x-2 p-3 bg-yellow-500/10 border border-yellow-400/20 rounded-lg">
                          <AlertTriangle className="w-4 h-4 text-yellow-400 flex-shrink-0 mt-0.5" />
                          <span className="text-sm text-yellow-200">{issue}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                {/* Action Button */}
                <div className="mt-8 flex items-center justify-between">
                  <button
                    onClick={() => markStepCompleted(activeStepData.id)}
                    disabled={completedSteps.has(activeStepData.id)}
                    className={`px-6 py-3 rounded-lg font-medium transition-colors ${
                      completedSteps.has(activeStepData.id)
                        ? 'bg-green-500/20 text-green-400 border border-green-400/30'
                        : 'bg-blue-500/20 text-blue-300 border border-blue-400/30 hover:bg-blue-500/30'
                    }`}
                  >
                    {completedSteps.has(activeStepData.id) ? (
                      <span className="flex items-center space-x-2">
                        <CheckCircle className="w-4 h-4" />
                        <span>Completed</span>
                      </span>
                    ) : (
                      <span>Mark as Completed</span>
                    )}
                  </button>

                  <button
                    onClick={() => window.location.reload()}
                    className="px-6 py-3 bg-purple-500/20 border border-purple-400/30 rounded-lg text-purple-300 hover:bg-purple-500/30 transition-colors font-medium flex items-center space-x-2"
                  >
                    <RefreshCw className="w-4 h-4" />
                    <span>Test GPS Now</span>
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default GPSTroubleshootingGuide;