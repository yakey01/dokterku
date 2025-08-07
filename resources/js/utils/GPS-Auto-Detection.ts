/**
 * GPS Auto-Detection Utility for Geofencing Maps
 * 
 * A comprehensive, robust GPS auto-detection solution with:
 * - Progressive accuracy fallback (High → Medium → Low)
 * - Browser permission detection and guidance
 * - Cross-browser compatibility and fallbacks
 * - Comprehensive error handling with user-friendly messages
 * - Integration with Filament forms and WorkLocation model
 */

interface GPSPosition {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: number;
}

interface GPSOptions {
  enableHighAccuracy?: boolean;
  timeout?: number;
  maximumAge?: number;
  progressiveTimeout?: boolean;
  maxRetries?: number;
}

interface GPSError {
  code: number;
  message: string;
  userMessage: string;
  suggestion: string;
}

class GPSAutoDetector {
  private static instance: GPSAutoDetector;
  private permissionState: PermissionState | 'unknown' = 'unknown';
  private lastKnownPosition: GPSPosition | null = null;
  private isDetecting = false;
  
  // Progressive accuracy settings
  private accuracyLevels: GPSOptions[] = [
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }, // High accuracy
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }, // Medium with cache
    { enableHighAccuracy: false, timeout: 20000, maximumAge: 60000 }, // Low accuracy
  ];

  public static getInstance(): GPSAutoDetector {
    if (!GPSAutoDetector.instance) {
      GPSAutoDetector.instance = new GPSAutoDetector();
    }
    return GPSAutoDetector.instance;
  }

  private constructor() {
    this.init();
  }

  private async init(): Promise<void> {
    await this.checkPermissionState();
    this.setupEventListeners();
  }

  /**
   * Check and monitor GPS permission state
   */
  private async checkPermissionState(): Promise<void> {
    if ('permissions' in navigator && 'query' in navigator.permissions) {
      try {
        const result = await navigator.permissions.query({ name: 'geolocation' as PermissionName });
        this.permissionState = result.state;
        
        // Listen for permission changes
        result.addEventListener('change', () => {
          this.permissionState = result.state;
          this.handlePermissionChange(result.state);
        });
      } catch (error) {
        console.warn('GPS Auto-Detection: Permission API not fully supported', error);
      }
    }
  }

  /**
   * Handle permission state changes
   */
  private handlePermissionChange(state: PermissionState): void {
    const statusElement = document.getElementById('gps-permission-status');
    if (statusElement) {
      switch (state) {
        case 'granted':
          statusElement.innerHTML = '✅ GPS Permission Granted';
          statusElement.className = 'gps-status-success';
          break;
        case 'denied':
          statusElement.innerHTML = '❌ GPS Permission Denied';
          statusElement.className = 'gps-status-error';
          this.showPermissionGuidance();
          break;
        case 'prompt':
          statusElement.innerHTML = '⚠️ GPS Permission Required';
          statusElement.className = 'gps-status-warning';
          break;
      }
    }
  }

  /**
   * Setup DOM event listeners
   */
  private setupEventListeners(): void {
    // Auto-detect on page load if element exists
    document.addEventListener('DOMContentLoaded', () => {
      const mapContainer = document.querySelector('[id*="leaflet-map"]');
      const autoDetectEnabled = document.querySelector('[data-gps-auto-detect="true"]');
      
      if (mapContainer && autoDetectEnabled) {
        setTimeout(() => this.autoDetectLocation(), 1000); // Delay for DOM stabilization
      }
    });

    // Listen for form field changes to update map
    document.addEventListener('input', (event) => {
      const target = event.target as HTMLInputElement;
      if (target?.dataset?.coordinateField) {
        this.handleCoordinateFieldChange(target);
      }
    });
  }

  /**
   * Main GPS auto-detection method with progressive fallback
   */
  public async autoDetectLocation(options: Partial<GPSOptions> = {}): Promise<GPSPosition | null> {
    if (this.isDetecting) {
      console.warn('GPS detection already in progress');
      return null;
    }

    if (!this.isGeolocationSupported()) {
      this.showError('GPS_NOT_SUPPORTED', 'Your browser does not support GPS location detection. Please enter coordinates manually.');
      return null;
    }

    this.isDetecting = true;
    this.showDetectionProgress(true);

    try {
      // Try progressive accuracy levels
      for (let i = 0; i < this.accuracyLevels.length; i++) {
        const accuracyOptions = { ...this.accuracyLevels[i], ...options };
        
        try {
          this.updateProgressMessage(`Attempting GPS detection (${i + 1}/${this.accuracyLevels.length})...`);
          
          const position = await this.getCurrentPosition(accuracyOptions);
          
          // Validate position quality
          if (this.isPositionAcceptable(position, i)) {
            this.lastKnownPosition = position;
            this.populateFormFields(position);
            this.updateMapPosition(position);
            this.showSuccess(position);
            return position;
          } else if (i === this.accuracyLevels.length - 1) {
            // Use the position anyway if it's the last attempt
            this.lastKnownPosition = position;
            this.populateFormFields(position);
            this.updateMapPosition(position);
            this.showSuccess(position, true); // Show with warning
            return position;
          }
          
        } catch (error) {
          console.warn(`GPS attempt ${i + 1} failed:`, error);
          
          if (i === this.accuracyLevels.length - 1) {
            throw error; // Re-throw on last attempt
          }
        }
      }
      
    } catch (error) {
      this.handleDetectionError(error as GeolocationPositionError);
    } finally {
      this.isDetecting = false;
      this.showDetectionProgress(false);
    }

    return null;
  }

  /**
   * Get current position with enhanced promise wrapper
   */
  private getCurrentPosition(options: GPSOptions): Promise<GPSPosition> {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error('CUSTOM_TIMEOUT'));
      }, options.timeout || 10000);

      navigator.geolocation.getCurrentPosition(
        (position) => {
          clearTimeout(timeout);
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: position.timestamp
          });
        },
        (error) => {
          clearTimeout(timeout);
          reject(error);
        },
        options
      );
    });
  }

  /**
   * Check if position meets quality standards
   */
  private isPositionAcceptable(position: GPSPosition, attemptLevel: number): boolean {
    // Accuracy thresholds for each attempt level
    const accuracyThresholds = [50, 100, 500]; // meters
    return position.accuracy <= accuracyThresholds[attemptLevel];
  }

  /**
   * Populate form fields with detected coordinates
   */
  private populateFormFields(position: GPSPosition): void {
    const fields = this.findCoordinateFields();
    
    if (fields.latitude && fields.longitude) {
      // Set values with appropriate precision
      const latValue = position.latitude.toFixed(8);
      const lngValue = position.longitude.toFixed(8);
      
      fields.latitude.value = latValue;
      fields.longitude.value = lngValue;
      
      // Trigger all necessary events for Filament reactivity
      this.triggerFieldEvents(fields.latitude, latValue);
      this.triggerFieldEvents(fields.longitude, lngValue);
      
      // Update accuracy field if exists
      const accuracyField = this.findField(['gps_accuracy_required', 'accuracy']);
      if (accuracyField && position.accuracy < 100) {
        accuracyField.value = Math.ceil(position.accuracy).toString();
        this.triggerFieldEvents(accuracyField, accuracyField.value);
      }
    } else {
      console.warn('GPS Auto-Detection: Coordinate fields not found in form');
      this.showFieldNotFoundError(position);
    }
  }

  /**
   * Update map position if map exists
   */
  private updateMapPosition(position: GPSPosition): void {
    // Try to find and update Leaflet map
    if (window.L && (window as any).CreativeLeafletMaps) {
      const maps = (window as any).CreativeLeafletMaps;
      const mapKeys = Array.from(maps.keys());
      
      if (mapKeys.length > 0) {
        const mapId = mapKeys[0];
        const mapData = maps.get(mapId);
        
        if (mapData?.map) {
          const latlng = window.L.latLng(position.latitude, position.longitude);
          
          // Update marker position
          if (mapData.marker) {
            mapData.marker.setLatLng(latlng);
          }
          
          // Update map view
          mapData.map.setView(latlng, Math.max(mapData.map.getZoom(), 15));
          
          // Update geofence circle if exists
          if (mapData.geofenceCircle) {
            mapData.geofenceCircle.setLatLng(latlng);
          }
        }
      }
    }
  }

  /**
   * Enhanced field detection with multiple strategies
   */
  private findCoordinateFields(): { latitude: HTMLInputElement | null; longitude: HTMLInputElement | null } {
    const strategies = [
      // Strategy 1: Filament wire:model
      () => ({
        latitude: document.querySelector('input[wire\\:model*="latitude"]') as HTMLInputElement,
        longitude: document.querySelector('input[wire\\:model*="longitude"]') as HTMLInputElement
      }),
      // Strategy 2: Standard name attributes
      () => ({
        latitude: document.querySelector('input[name="latitude"]') as HTMLInputElement,
        longitude: document.querySelector('input[name="longitude"]') as HTMLInputElement
      }),
      // Strategy 3: Data attributes
      () => ({
        latitude: document.querySelector('input[data-coordinate-field="latitude"]') as HTMLInputElement,
        longitude: document.querySelector('input[data-coordinate-field="longitude"]') as HTMLInputElement
      }),
      // Strategy 4: ID-based
      () => ({
        latitude: document.getElementById('latitude') as HTMLInputElement,
        longitude: document.getElementById('longitude') as HTMLInputElement
      }),
    ];

    for (const strategy of strategies) {
      const fields = strategy();
      if (fields.latitude && fields.longitude) {
        return fields;
      }
    }

    return { latitude: null, longitude: null };
  }

  /**
   * Find field by multiple possible names
   */
  private findField(names: string[]): HTMLInputElement | null {
    for (const name of names) {
      const field = document.querySelector(`input[name="${name}"], input[id="${name}"]`) as HTMLInputElement;
      if (field) return field;
    }
    return null;
  }

  /**
   * Trigger all necessary events for field reactivity
   */
  private triggerFieldEvents(field: HTMLInputElement, value: string): void {
    const events = ['input', 'change', 'blur', 'keyup'];
    
    events.forEach(eventType => {
      const event = new Event(eventType, { bubbles: true });
      field.dispatchEvent(event);
    });

    // Special handling for Livewire/Alpine
    if (field.hasAttribute('wire:model') || field.hasAttribute('x-model')) {
      field.dispatchEvent(new CustomEvent('input', { 
        detail: { value },
        bubbles: true 
      }));
    }
  }

  /**
   * Handle coordinate field changes to update map
   */
  private handleCoordinateFieldChange(field: HTMLInputElement): void {
    const lat = parseFloat(this.findCoordinateFields().latitude?.value || '0');
    const lng = parseFloat(this.findCoordinateFields().longitude?.value || '0');
    
    if (lat && lng && lat !== 0 && lng !== 0) {
      setTimeout(() => this.updateMapPosition({ latitude: lat, longitude: lng, accuracy: 0, timestamp: Date.now() }), 100);
    }
  }

  /**
   * Show detection progress
   */
  private showDetectionProgress(show: boolean): void {
    const button = document.getElementById('get-location-btn') as HTMLButtonElement;
    const progressElement = document.getElementById('gps-progress');
    
    if (button) {
      button.disabled = show;
      if (show) {
        button.innerHTML = '🔄 Detecting GPS Location...';
        button.classList.add('detecting');
      } else {
        button.innerHTML = '🌍 Get My Location';
        button.classList.remove('detecting');
      }
    }

    if (progressElement) {
      progressElement.style.display = show ? 'block' : 'none';
    }
  }

  /**
   * Update progress message
   */
  private updateProgressMessage(message: string): void {
    const progressElement = document.getElementById('gps-progress-message');
    if (progressElement) {
      progressElement.textContent = message;
    }
  }

  /**
   * Show success message
   */
  private showSuccess(position: GPSPosition, withWarning = false): void {
    const accuracy = Math.round(position.accuracy);
    const accuracyColor = accuracy < 50 ? '🟢' : accuracy < 100 ? '🟡' : '🔴';
    
    let message = `${accuracyColor} GPS location detected successfully!\n`;
    message += `📍 Coordinates: ${position.latitude.toFixed(6)}, ${position.longitude.toFixed(6)}\n`;
    message += `🎯 Accuracy: ±${accuracy} meters`;
    
    if (withWarning && accuracy > 100) {
      message += `\n⚠️ Low accuracy detected. Consider moving to an open area for better GPS signal.`;
    }

    this.showNotification('success', 'GPS Detection Successful', message);
    
    // Update status display
    this.updateStatusDisplay('success', `Last GPS update: ${new Date().toLocaleTimeString()}`);
  }

  /**
   * Handle detection errors
   */
  private handleDetectionError(error: GeolocationPositionError | Error): void {
    let gpsError: GPSError;

    if ('code' in error) {
      switch (error.code) {
        case error.PERMISSION_DENIED:
          gpsError = {
            code: error.code,
            message: 'GPS permission denied',
            userMessage: 'GPS access was denied. Please allow location access and try again.',
            suggestion: 'Click the location icon in your browser address bar to enable GPS access.'
          };
          this.showPermissionGuidance();
          break;
        case error.POSITION_UNAVAILABLE:
          gpsError = {
            code: error.code,
            message: 'GPS position unavailable',
            userMessage: 'GPS signal unavailable. Your location cannot be determined.',
            suggestion: 'Move to an open area with clear sky view and ensure GPS is enabled on your device.'
          };
          break;
        case error.TIMEOUT:
          gpsError = {
            code: error.code,
            message: 'GPS timeout',
            userMessage: 'GPS detection timed out. Poor signal quality detected.',
            suggestion: 'Move to an outdoor location with better GPS signal and try again.'
          };
          break;
        default:
          gpsError = {
            code: 999,
            message: error.message,
            userMessage: 'Unknown GPS error occurred.',
            suggestion: 'Please try refreshing the page or entering coordinates manually.'
          };
      }
    } else {
      gpsError = {
        code: 998,
        message: error.message,
        userMessage: 'GPS detection failed due to a technical error.',
        suggestion: 'Please try again or enter coordinates manually.'
      };
    }

    this.showError('GPS_DETECTION_ERROR', gpsError.userMessage, gpsError.suggestion);
    this.updateStatusDisplay('error', 'GPS detection failed');
  }

  /**
   * Show permission guidance modal/popup
   */
  private showPermissionGuidance(): void {
    const guidance = this.createPermissionGuidanceElement();
    document.body.appendChild(guidance);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
      if (guidance.parentNode) {
        guidance.parentNode.removeChild(guidance);
      }
    }, 10000);
  }

  /**
   * Create permission guidance element
   */
  private createPermissionGuidanceElement(): HTMLElement {
    const guidance = document.createElement('div');
    guidance.id = 'gps-permission-guidance';
    guidance.innerHTML = `
      <div style="position: fixed; top: 20px; right: 20px; z-index: 10000; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 20px; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 12px;">
          <span style="font-size: 24px; margin-right: 8px;">📍</span>
          <h4 style="margin: 0; color: #991b1b;">GPS Permission Required</h4>
        </div>
        <p style="margin: 8px 0; color: #7f1d1d; font-size: 14px;">
          To automatically detect your location, please:
        </p>
        <ol style="margin: 8px 0; color: #7f1d1d; font-size: 14px; padding-left: 20px;">
          <li>Click the location icon in your browser's address bar</li>
          <li>Select "Allow" for location access</li>
          <li>Refresh the page if necessary</li>
        </ol>
        <div style="display: flex; gap: 8px; margin-top: 16px;">
          <button onclick="this.parentElement.parentElement.remove()" style="background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px;">
            Close
          </button>
          <button onclick="window.location.reload()" style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px;">
            Refresh Page
          </button>
        </div>
      </div>
    `;
    return guidance;
  }

  /**
   * Show error with field not found details
   */
  private showFieldNotFoundError(position: GPSPosition): void {
    const message = `
      GPS location detected but form fields not found.
      
      📍 Detected Coordinates:
      Latitude: ${position.latitude.toFixed(8)}
      Longitude: ${position.longitude.toFixed(8)}
      
      Please copy these coordinates manually to the form fields.
    `;
    
    this.showNotification('warning', 'Form Fields Not Found', message);
    
    // Show manual input option
    this.showManualInputModal(position);
  }

  /**
   * Show manual input modal
   */
  private showManualInputModal(position: GPSPosition): void {
    const modal = document.createElement('div');
    modal.innerHTML = `
      <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10001; display: flex; align-items: center; justify-content: center;" onclick="this.remove()">
        <div style="background: white; border-radius: 8px; padding: 24px; max-width: 500px; margin: 20px;" onclick="event.stopPropagation()">
          <h3 style="margin: 0 0 16px 0; color: #374151;">📍 GPS Coordinates Detected</h3>
          <p style="color: #6b7280; margin-bottom: 20px;">Copy these coordinates to your form:</p>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
            <div>
              <label style="display: block; font-weight: 500; margin-bottom: 4px;">Latitude:</label>
              <input type="text" value="${position.latitude.toFixed(8)}" readonly style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; background: #f9fafb;">
            </div>
            <div>
              <label style="display: block; font-weight: 500; margin-bottom: 4px;">Longitude:</label>
              <input type="text" value="${position.longitude.toFixed(8)}" readonly style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; background: #f9fafb;">
            </div>
          </div>
          
          <div style="text-align: center;">
            <button onclick="navigator.clipboard.writeText('${position.latitude.toFixed(8)},${position.longitude.toFixed(8)}'); alert('Coordinates copied to clipboard!')" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 8px;">
              📋 Copy Coordinates
            </button>
            <button onclick="this.parentElement.parentElement.parentElement.remove()" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
              Close
            </button>
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
  }

  /**
   * Generic notification system
   */
  private showNotification(type: 'success' | 'error' | 'warning', title: string, message: string): void {
    // Try Filament notification first
    if ((window as any).Filament?.notification) {
      (window as any).Filament.notification(type, title, message);
      return;
    }

    // Fallback to custom notification
    const colors = {
      success: { bg: '#d1fae5', border: '#a7f3d0', text: '#065f46' },
      error: { bg: '#fee2e2', border: '#fca5a5', text: '#991b1b' },
      warning: { bg: '#fef3c7', border: '#fcd34d', text: '#92400e' }
    };

    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
      background: ${colors[type].bg};
      border: 1px solid ${colors[type].border};
      color: ${colors[type].text};
      padding: 16px;
      border-radius: 8px;
      max-width: 400px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      font-size: 14px;
      line-height: 1.4;
    `;

    notification.innerHTML = `
      <div style="font-weight: bold; margin-bottom: 8px;">${title}</div>
      <div style="white-space: pre-line;">${message}</div>
      <button onclick="this.parentElement.remove()" style="position: absolute; top: 8px; right: 8px; background: none; border: none; font-size: 16px; cursor: pointer; color: ${colors[type].text};">×</button>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 8 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 8000);
  }

  /**
   * Show error with specific type
   */
  private showError(errorType: string, message: string, suggestion?: string): void {
    let fullMessage = message;
    if (suggestion) {
      fullMessage += `\n\n💡 Suggestion: ${suggestion}`;
    }
    
    this.showNotification('error', 'GPS Detection Error', fullMessage);
  }

  /**
   * Update status display element
   */
  private updateStatusDisplay(status: 'success' | 'error' | 'detecting', message: string): void {
    const statusElement = document.getElementById('gps-status');
    if (statusElement) {
      statusElement.className = `gps-status-${status}`;
      statusElement.textContent = message;
    }
  }

  /**
   * Check if geolocation is supported
   */
  private isGeolocationSupported(): boolean {
    return 'geolocation' in navigator && typeof navigator.geolocation.getCurrentPosition === 'function';
  }

  /**
   * Get current permission state
   */
  public getPermissionState(): PermissionState | 'unknown' {
    return this.permissionState;
  }

  /**
   * Get last known position
   */
  public getLastKnownPosition(): GPSPosition | null {
    return this.lastKnownPosition;
  }

  /**
   * Force retry detection
   */
  public async retryDetection(): Promise<GPSPosition | null> {
    this.isDetecting = false; // Reset flag
    return this.autoDetectLocation();
  }

  /**
   * Manual coordinate validation
   */
  public validateCoordinates(lat: number, lng: number): { valid: boolean; error?: string } {
    if (isNaN(lat) || isNaN(lng)) {
      return { valid: false, error: 'Coordinates must be valid numbers' };
    }

    if (lat < -90 || lat > 90) {
      return { valid: false, error: 'Latitude must be between -90 and 90 degrees' };
    }

    if (lng < -180 || lng > 180) {
      return { valid: false, error: 'Longitude must be between -180 and 180 degrees' };
    }

    return { valid: true };
  }

  /**
   * Get browser and device compatibility info
   */
  public getCompatibilityInfo(): { supported: boolean; info: string[] } {
    const info = [];
    const supported = this.isGeolocationSupported();

    info.push(`Browser: ${navigator.userAgent.split(' ').slice(-1)[0] || 'Unknown'}`);
    info.push(`HTTPS: ${location.protocol === 'https:' ? '✅ Yes' : '❌ No (Required for GPS)'}`);
    info.push(`Geolocation API: ${supported ? '✅ Supported' : '❌ Not supported'}`);
    info.push(`Permission API: ${'permissions' in navigator ? '✅ Available' : '⚠️ Limited'}`);

    return { supported, info };
  }
}

// Global instance and functions for external access
const gpsDetector = GPSAutoDetector.getInstance();

// Global function for button onclick
(window as any).autoDetectLocation = () => {
  gpsDetector.autoDetectLocation();
};

// Global function for retry
(window as any).retryGPSDetection = () => {
  gpsDetector.retryDetection();
};

export default GPSAutoDetector;
export { GPSAutoDetector };