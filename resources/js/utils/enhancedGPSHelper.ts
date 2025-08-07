/**
 * Enhanced GPS Helper for Better Location Detection
 * Provides comprehensive error handling, progressive strategies, and user guidance
 */

export interface GPSCapabilities {
  supported: boolean;
  permissionStatus: 'granted' | 'denied' | 'prompt' | 'unknown';
  batteryLevel: number | null;
  connectionType: string;
  isSecureContext: boolean;
  userAgent: string;
}

export interface GPSErrorDetails {
  code: number;
  message: string;
  technicalMessage: string;
  userFriendlyMessage: string;
  troubleshootingSteps: string[];
  suggestedActions: string[];
  canRetry: boolean;
  estimatedFixTime: string;
}

export interface GPSDetectionStrategy {
  name: string;
  description: string;
  timeout: number;
  accuracy: boolean;
  maxAge: number;
  priority: number;
}

export interface GPSLocationResult {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: number;
  source: 'high-accuracy' | 'normal' | 'cached' | 'network';
  confidence: number;
  strategy: string;
  detectionTime: number;
}

class EnhancedGPSHelper {
  private capabilities: GPSCapabilities | null = null;
  private detectionStrategies: GPSDetectionStrategy[] = [
    {
      name: 'high_accuracy_fast',
      description: 'GPS akurasi tinggi dengan timeout cepat',
      timeout: 5000,
      accuracy: true,
      maxAge: 10000,
      priority: 1
    },
    {
      name: 'high_accuracy_medium',
      description: 'GPS akurasi tinggi dengan timeout sedang',
      timeout: 10000,
      accuracy: true,
      maxAge: 30000,
      priority: 2
    },
    {
      name: 'normal_accuracy',
      description: 'GPS akurasi normal dengan timeout fleksibel',
      timeout: 15000,
      accuracy: false,
      maxAge: 60000,
      priority: 3
    },
    {
      name: 'cached_location',
      description: 'Lokasi tersimpan (jika tersedia)',
      timeout: 20000,
      accuracy: false,
      maxAge: 300000,
      priority: 4
    }
  ];

  /**
   * Check GPS and device capabilities
   */
  async checkCapabilities(): Promise<GPSCapabilities> {
    if (this.capabilities) {
      return this.capabilities;
    }

    const capabilities: GPSCapabilities = {
      supported: 'geolocation' in navigator,
      permissionStatus: 'unknown',
      batteryLevel: null,
      connectionType: (navigator as any).connection?.effectiveType || 'unknown',
      isSecureContext: window.isSecureContext,
      userAgent: navigator.userAgent
    };

    // Check permission status
    if ('permissions' in navigator) {
      try {
        const permission = await navigator.permissions.query({ name: 'geolocation' });
        capabilities.permissionStatus = permission.state;
      } catch (error) {
        console.warn('Permission API not available:', error);
      }
    }

    // Check battery level
    if ('getBattery' in navigator) {
      try {
        const battery = await (navigator as any).getBattery();
        capabilities.batteryLevel = battery.level;
      } catch (error) {
        console.warn('Battery API not available:', error);
      }
    }

    this.capabilities = capabilities;
    return capabilities;
  }

  /**
   * Get detailed error information with Indonesian messages and troubleshooting
   */
  getDetailedError(error: GeolocationPositionError): GPSErrorDetails {
    const baseDetails: Partial<GPSErrorDetails> = {
      code: error.code,
      technicalMessage: error.message,
    };

    switch (error.code) {
      case error.PERMISSION_DENIED:
        return {
          ...baseDetails,
          message: 'Akses lokasi ditolak',
          userFriendlyMessage: 'Browser memblokir akses ke lokasi Anda',
          troubleshootingSteps: [
            'Klik ikon kunci 🔒 atau lokasi 📍 di address bar',
            'Pilih "Selalu izinkan lokasi untuk situs ini"',
            'Refresh halaman (F5 atau Ctrl+R)',
            'Jika masih gagal, buka Settings browser → Privacy → Location'
          ],
          suggestedActions: [
            'Aktifkan izin lokasi di browser',
            'Periksa pengaturan privacy browser',
            'Coba browser berbeda jika masih gagal'
          ],
          canRetry: true,
          estimatedFixTime: '1-2 menit',
          code: error.code,
          technicalMessage: error.message
        } as GPSErrorDetails;

      case error.POSITION_UNAVAILABLE:
        return {
          ...baseDetails,
          message: 'Lokasi tidak tersedia',
          userFriendlyMessage: 'GPS tidak dapat menentukan posisi Anda saat ini',
          troubleshootingSteps: [
            'Pastikan GPS aktif di pengaturan perangkat',
            'Pindah ke area terbuka (hindari gedung tinggi)',
            'Tunggu beberapa menit untuk sinyal GPS stabil',
            'Restart aplikasi GPS jika perlu'
          ],
          suggestedActions: [
            'Aktifkan GPS di perangkat',
            'Keluar ke area terbuka',
            'Periksa mode pesawat tidak aktif',
            'Restart perangkat jika perlu'
          ],
          canRetry: true,
          estimatedFixTime: '2-5 menit',
          code: error.code,
          technicalMessage: error.message
        } as GPSErrorDetails;

      case error.TIMEOUT:
        return {
          ...baseDetails,
          message: 'GPS timeout',
          userFriendlyMessage: 'Pencarian lokasi memakan waktu terlalu lama',
          troubleshootingSteps: [
            'Tunggu beberapa detik dan coba lagi',
            'Pindah ke lokasi dengan sinyal GPS lebih baik',
            'Periksa koneksi internet stabil',
            'Tutup aplikasi lain yang menggunakan GPS'
          ],
          suggestedActions: [
            'Coba lagi dengan sabar',
            'Pindah ke area terbuka',
            'Periksa koneksi internet',
            'Restart aplikasi'
          ],
          canRetry: true,
          estimatedFixTime: '30 detik - 2 menit',
          code: error.code,
          technicalMessage: error.message
        } as GPSErrorDetails;

      default:
        return {
          ...baseDetails,
          message: 'Kesalahan GPS tidak dikenal',
          userFriendlyMessage: 'Terjadi masalah teknis dengan GPS',
          troubleshootingSteps: [
            'Refresh halaman dan coba lagi',
            'Periksa koneksi internet stabil',
            'Tutup dan buka kembali browser',
            'Coba lagi dalam beberapa menit'
          ],
          suggestedActions: [
            'Refresh halaman',
            'Restart browser',
            'Periksa koneksi internet',
            'Hubungi support jika masalah berlanjut'
          ],
          canRetry: true,
          estimatedFixTime: '1-3 menit',
          code: error.code,
          technicalMessage: error.message
        } as GPSErrorDetails;
    }
  }

  /**
   * Progressive GPS detection with multiple strategies
   */
  async detectLocationProgressive(
    onProgress?: (status: string, progress: number, strategy?: string) => void,
    onStrategyChange?: (strategy: GPSDetectionStrategy) => void
  ): Promise<GPSLocationResult> {
    const startTime = Date.now();
    
    // Check capabilities first
    const capabilities = await this.checkCapabilities();
    
    if (!capabilities.supported) {
      throw new Error('GPS tidak didukung oleh browser ini');
    }

    if (capabilities.permissionStatus === 'denied') {
      throw new Error('Akses lokasi ditolak. Aktifkan izin lokasi di browser.');
    }

    // Adjust strategies based on capabilities
    const strategies = this.getOptimizedStrategies(capabilities);
    
    let lastError: GeolocationPositionError | null = null;
    
    for (let i = 0; i < strategies.length; i++) {
      const strategy = strategies[i];
      onStrategyChange?.(strategy);
      
      try {
        onProgress?.(
          `🛰️ ${strategy.description}...`,
          (i / strategies.length) * 80,
          strategy.name
        );

        const location = await this.tryLocationStrategy(strategy, onProgress);
        const detectionTime = Date.now() - startTime;

        onProgress?.('✅ GPS berhasil dideteksi!', 100, strategy.name);
        
        return {
          ...location,
          strategy: strategy.name,
          detectionTime
        };
        
      } catch (error) {
        lastError = error as GeolocationPositionError;
        console.warn(`Strategy ${strategy.name} failed:`, error);
        
        if (i < strategies.length - 1) {
          onProgress?.(
            `⚠️ Strategi ${i + 1} gagal, mencoba alternatif...`,
            ((i + 1) / strategies.length) * 80,
            strategy.name
          );
          // Brief pause between strategies
          await new Promise(resolve => setTimeout(resolve, 500));
        }
      }
    }

    // All strategies failed
    if (lastError) {
      throw lastError;
    } else {
      throw new Error('Semua strategi GPS gagal');
    }
  }

  /**
   * Try a specific location strategy
   */
  private tryLocationStrategy(
    strategy: GPSDetectionStrategy,
    onProgress?: (status: string, progress: number, strategy?: string) => void
  ): Promise<GPSLocationResult> {
    return new Promise((resolve, reject) => {
      let resolved = false;
      
      const options: PositionOptions = {
        enableHighAccuracy: strategy.accuracy,
        timeout: strategy.timeout,
        maximumAge: strategy.maxAge
      };

      const timeoutId = setTimeout(() => {
        if (!resolved) {
          resolved = true;
          reject(new Error(`Strategy ${strategy.name} timeout after ${strategy.timeout}ms`));
        }
      }, strategy.timeout + 1000);

      navigator.geolocation.getCurrentPosition(
        (position) => {
          if (resolved) return;
          resolved = true;
          clearTimeout(timeoutId);

          const { latitude, longitude, accuracy, timestamp } = position.coords;
          
          resolve({
            latitude,
            longitude,
            accuracy,
            timestamp: timestamp || Date.now(),
            source: strategy.accuracy ? 'high-accuracy' : 'normal',
            confidence: this.calculateConfidence(accuracy, strategy),
            strategy: strategy.name,
            detectionTime: 0 // Will be calculated by caller
          });
        },
        (error) => {
          if (resolved) return;
          resolved = true;
          clearTimeout(timeoutId);
          reject(error);
        },
        options
      );
    });
  }

  /**
   * Get optimized strategies based on device capabilities
   */
  private getOptimizedStrategies(capabilities: GPSCapabilities): GPSDetectionStrategy[] {
    let strategies = [...this.detectionStrategies];

    // If low battery, prefer less accurate but faster methods
    if (capabilities.batteryLevel && capabilities.batteryLevel < 0.2) {
      strategies = strategies.filter(s => !s.accuracy || s.timeout < 10000);
    }

    // If slow connection, increase timeouts
    if (capabilities.connectionType === 'slow-2g' || capabilities.connectionType === '2g') {
      strategies.forEach(s => s.timeout *= 1.5);
    }

    // Sort by priority
    return strategies.sort((a, b) => a.priority - b.priority);
  }

  /**
   * Calculate confidence based on accuracy and strategy
   */
  private calculateConfidence(accuracy: number, strategy: GPSDetectionStrategy): number {
    let confidence = 1.0;

    // Base confidence on accuracy
    if (accuracy <= 10) confidence *= 1.0;
    else if (accuracy <= 50) confidence *= 0.8;
    else if (accuracy <= 100) confidence *= 0.6;
    else confidence *= 0.4;

    // Adjust for strategy
    if (strategy.accuracy) confidence *= 1.0;
    else confidence *= 0.8;

    if (strategy.maxAge > 60000) confidence *= 0.9; // Slightly reduce for old cache

    return Math.max(0.1, Math.min(1.0, confidence));
  }

  /**
   * Get user-friendly status messages
   */
  getStatusMessage(status: string, strategy?: string): string {
    const messages: Record<string, string> = {
      'checking': '🔍 Memeriksa kemampuan GPS...',
      'high_accuracy_fast': '🎯 Mencari lokasi akurat (cepat)...',
      'high_accuracy_medium': '📍 Mencari lokasi akurat...',
      'normal_accuracy': '🗺️ Mencari lokasi normal...',
      'cached_location': '💾 Menggunakan lokasi tersimpan...',
      'network': '🌐 Menggunakan lokasi jaringan...',
      'success': '✅ Lokasi berhasil ditemukan!',
      'error': '❌ Gagal menemukan lokasi'
    };

    return messages[strategy || status] || messages[status] || status;
  }

  /**
   * Get device-specific optimization tips
   */
  getOptimizationTips(capabilities: GPSCapabilities): string[] {
    const tips: string[] = [];

    if (capabilities.batteryLevel && capabilities.batteryLevel < 0.2) {
      tips.push('🔋 Baterai rendah: menggunakan mode hemat untuk GPS');
    }

    if (capabilities.connectionType === 'slow-2g' || capabilities.connectionType === '2g') {
      tips.push('🐌 Koneksi lambat: membutuhkan waktu lebih lama');
    }

    if (!capabilities.isSecureContext) {
      tips.push('🔒 Situs tidak aman: beberapa fitur GPS mungkin terbatas');
    }

    if (capabilities.permissionStatus === 'prompt') {
      tips.push('📋 Izinkan akses lokasi ketika diminta');
    }

    return tips;
  }
}

// Export singleton instance
export const enhancedGPS = new EnhancedGPSHelper();
export default enhancedGPS;