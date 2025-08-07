{{-- Enhanced GPS Auto-Detection Component for Geofencing Maps --}}

<div id="gps-enhanced-detector" class="gps-detector-container" x-data="{
    detecting: false,
    showResult: false,
    showManualInput: false,
    buttonText: '🌍 Get My Location',
    resultMessage: '',
    resultClass: '',
    permissionState: 'unknown',
    lastPosition: null,
    progressStage: '',
    
    // Enhanced GPS detection with progressive fallback
    async detectLocation() {
        if (this.detecting) return;
        
        this.detecting = true;
        this.showResult = false;
        this.buttonText = '🔄 Detecting GPS...';
        this.progressStage = 'Initializing...';
        
        // Check browser compatibility
        if (!navigator.geolocation) {
            this.showError('❌ GPS not supported by your browser.<br>Please enter coordinates manually.');
            this.resetButton();
            return;
        }
        
        // Check HTTPS requirement
        if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
            this.showError('⚠️ GPS requires HTTPS connection.<br>Please use secure connection or manual input.');
            this.resetButton();
            return;
        }
        
        // Progressive detection attempts
        const attempts = [
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 },
            { enableHighAccuracy: false, timeout: 20000, maximumAge: 60000 }
        ];
        
        for (let i = 0; i < attempts.length; i++) {
            this.progressStage = `Attempt ${i + 1}/${attempts.length}...`;
            
            try {
                const position = await this.getCurrentPosition(attempts[i]);
                const accuracy = Math.round(position.coords.accuracy);
                
                // Check if accuracy is acceptable for this attempt
                const accuracyThresholds = [50, 100, 500];
                const acceptable = accuracy <= accuracyThresholds[i];
                
                if (acceptable || i === attempts.length - 1) {
                    await this.handleSuccess(position, !acceptable);
                    return;
                }
                
            } catch (error) {
                console.warn(`GPS attempt ${i + 1} failed:`, error);
                
                if (i === attempts.length - 1) {
                    this.handleError(error);
                    return;
                }
            }
        }
    },
    
    getCurrentPosition(options) {
        return new Promise((resolve, reject) => {
            const timeoutId = setTimeout(() => {
                reject(new Error('Custom timeout'));
            }, options.timeout);
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    clearTimeout(timeoutId);
                    resolve(position);
                },
                (error) => {
                    clearTimeout(timeoutId);
                    reject(error);
                },
                options
            );
        });
    },
    
    async handleSuccess(position, lowAccuracy = false) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = Math.round(position.coords.accuracy);
        
        this.lastPosition = { lat, lng, accuracy };
        
        // Find and populate form fields
        const fields = this.findCoordinateFields();
        let fieldsPopulated = false;
        
        if (fields.latitude && fields.longitude) {
            fields.latitude.value = lat.toFixed(8);
            fields.longitude.value = lng.toFixed(8);
            
            // Trigger events for Filament reactivity
            this.triggerFieldEvents(fields.latitude);
            this.triggerFieldEvents(fields.longitude);
            
            // Update accuracy field if exists
            const accuracyField = this.findField(['gps_accuracy_required', 'accuracy']);
            if (accuracyField && accuracy < 100) {
                accuracyField.value = Math.ceil(accuracy).toString();
                this.triggerFieldEvents(accuracyField);
            }
            
            fieldsPopulated = true;
        }
        
        // Update map if available
        this.updateMap(lat, lng);
        
        // Show success message
        const accuracyIcon = accuracy < 50 ? '🟢' : accuracy < 100 ? '🟡' : '🔴';
        let message = `${accuracyIcon} GPS detected successfully!<br>`;
        message += `📍 Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>`;
        message += `🎯 Accuracy: ±${accuracy} meters`;
        
        if (lowAccuracy) {
            message += '<br>⚠️ Low accuracy - consider moving to open area';
        }
        
        if (!fieldsPopulated) {
            message += '<br>📋 Manual input required - fields not auto-populated';
            this.showManualInput = true;
        }
        
        this.showSuccess(message);
        this.buttonText = '✅ Success!';
        
        setTimeout(() => this.resetButton(), 4000);
    },
    
    handleError(error) {
        let message = '';
        let suggestion = '';
        
        if (error.code) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = '❌ GPS permission denied!';
                    suggestion = 'Click the location icon in your browser address bar to allow GPS access.';
                    this.showPermissionHelp();
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = '❌ GPS signal unavailable!';
                    suggestion = 'Move to an open area with clear sky view and ensure GPS is enabled.';
                    break;
                case error.TIMEOUT:
                    message = '⏰ GPS detection timed out!';
                    suggestion = 'Move to an outdoor location with better GPS signal.';
                    break;
                default:
                    message = '❌ GPS error occurred!';
                    suggestion = 'Try refreshing the page or use manual input.';
            }
        } else {
            message = '❌ GPS detection failed!';
            suggestion = 'Please try again or enter coordinates manually.';
        }
        
        this.showError(message + '<br><small>' + suggestion + '</small>');
        this.resetButton();
        this.showManualInput = true;
    },
    
    findCoordinateFields() {
        // Multiple strategies to find form fields
        const strategies = [
            () => ({
                latitude: document.querySelector('input[wire\\:model*=\"latitude\"]'),
                longitude: document.querySelector('input[wire\\:model*=\"longitude\"]')
            }),
            () => ({
                latitude: document.querySelector('input[name=\"latitude\"]'),
                longitude: document.querySelector('input[name=\"longitude\"]')
            }),
            () => ({
                latitude: document.querySelector('input[data-coordinate-field=\"latitude\"]'),
                longitude: document.querySelector('input[data-coordinate-field=\"longitude\"]')
            }),
            () => ({
                latitude: document.getElementById('latitude'),
                longitude: document.getElementById('longitude')
            })
        ];
        
        for (const strategy of strategies) {
            const fields = strategy();
            if (fields.latitude && fields.longitude) {
                return fields;
            }
        }
        
        return { latitude: null, longitude: null };
    },
    
    findField(names) {
        for (const name of names) {
            const field = document.querySelector(`input[name=\"${name}\"], input[id=\"${name}\"]`);
            if (field) return field;
        }
        return null;
    },
    
    triggerFieldEvents(field) {
        const events = ['input', 'change', 'blur', 'keyup'];
        events.forEach(eventType => {
            field.dispatchEvent(new Event(eventType, { bubbles: true }));
        });
        
        // Special handling for Livewire/Alpine
        if (field.hasAttribute('wire:model') || field.hasAttribute('x-model')) {
            field.dispatchEvent(new CustomEvent('input', { 
                detail: { value: field.value },
                bubbles: true 
            }));
        }
    },
    
    updateMap(lat, lng) {
        try {
            // Try to update Leaflet map if available
            if (window.L && window.CreativeLeafletMaps) {
                const maps = window.CreativeLeafletMaps;
                const mapKeys = Array.from(maps.keys());
                
                if (mapKeys.length > 0) {
                    const mapId = mapKeys[0];
                    const mapData = maps.get(mapId);
                    
                    if (mapData?.map) {
                        const latlng = L.latLng(lat, lng);
                        
                        // Update marker position
                        if (mapData.marker) {
                            mapData.marker.setLatLng(latlng);
                        }
                        
                        // Update map view
                        const currentZoom = mapData.map.getZoom();
                        mapData.map.setView(latlng, Math.max(currentZoom, 15), { animate: true });
                        
                        // Update geofence circle if exists
                        if (mapData.geofenceCircle) {
                            mapData.geofenceCircle.setLatLng(latlng);
                        }
                    }
                }
            }
        } catch (error) {
            console.warn('Map update failed:', error);
        }
    },
    
    showPermissionHelp() {
        // Create permission guidance popup
        const helpHtml = `
            <div class=\"permission-help\" style=\"margin-top: 15px; padding: 15px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px;\">
                <h4 style=\"margin: 0 0 10px 0; color: #92400e;\">🔒 Enable GPS Permission</h4>
                <div style=\"font-size: 14px; color: #78350f;\">
                    <div style=\"margin-bottom: 8px;\"><strong>Chrome/Edge:</strong> Click the 🌐 icon in address bar → Allow Location</div>
                    <div style=\"margin-bottom: 8px;\"><strong>Firefox:</strong> Click the 🛡️ shield icon → Permissions → Location → Allow</div>
                    <div style=\"margin-bottom: 8px;\"><strong>Safari:</strong> Menu → Preferences → Websites → Location → Allow</div>
                </div>
                <button onclick=\"window.location.reload()\" style=\"margin-top: 10px; background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px;\">
                    🔄 Refresh After Allowing
                </button>
            </div>
        `;
        
        const container = document.getElementById('permission-help-container');
        if (container) {
            container.innerHTML = helpHtml;
        }
    },
    
    fillManualCoordinates() {
        if (!this.lastPosition) return;
        
        const fields = this.findCoordinateFields();
        if (fields.latitude && fields.longitude) {
            fields.latitude.value = this.lastPosition.lat.toFixed(8);
            fields.longitude.value = this.lastPosition.lng.toFixed(8);
            
            this.triggerFieldEvents(fields.latitude);
            this.triggerFieldEvents(fields.longitude);
            
            this.showSuccess('✅ Coordinates filled manually!');
            this.showManualInput = false;
        }
    },
    
    copyCoordinates() {
        if (!this.lastPosition) return;
        
        const coords = `${this.lastPosition.lat.toFixed(8)},${this.lastPosition.lng.toFixed(8)}`;
        navigator.clipboard.writeText(coords).then(() => {
            this.showSuccess('📋 Coordinates copied to clipboard!');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = coords;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showSuccess('📋 Coordinates copied!');
        });
    },
    
    showSuccess(message) {
        this.resultMessage = message;
        this.resultClass = 'gps-success';
        this.showResult = true;
    },
    
    showError(message) {
        this.resultMessage = message;
        this.resultClass = 'gps-error';
        this.showResult = true;
    },
    
    resetButton() {
        this.detecting = false;
        this.buttonText = '🌍 Get My Location';
        this.progressStage = '';
    },
    
    getDeviceInfo() {
        const info = [];
        info.push(`Browser: ${navigator.userAgent.split(' ').slice(-1)[0] || 'Unknown'}`);
        info.push(`HTTPS: ${location.protocol === 'https:' ? '✅' : '❌ Required'}`);
        info.push(`GPS API: ${'geolocation' in navigator ? '✅' : '❌'}`);
        info.push(`Permissions: ${'permissions' in navigator ? '✅' : '⚠️ Limited'}`);
        return info.join('\\n');
    }
}">

    <style>
    .gps-detector-container {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #e0f2fe;
        border-radius: 12px;
        padding: 20px;
        margin: 15px 0;
    }
    
    .gps-main-button {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
    }
    
    .gps-main-button:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
    }
    
    .gps-main-button:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .gps-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        border-radius: 6px;
        padding: 12px;
        margin-top: 10px;
    }
    
    .gps-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        padding: 12px;
        margin-top: 10px;
    }
    
    .gps-progress {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        border-radius: 6px;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 14px;
        text-align: center;
    }
    
    .gps-manual-input {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }
    
    .gps-debug-links {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .gps-debug-link {
        display: inline-block;
        background: #6366f1;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        transition: background 0.2s;
    }
    
    .gps-debug-link:hover {
        background: #4f46e5;
    }
    
    .gps-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 10px;
        font-size: 12px;
        color: #6b7280;
    }
    </style>

    <!-- Debug and Testing Links -->
    <div class="gps-debug-links">
        <a href="/gps-diagnostic-tool.html" target="_blank" class="gps-debug-link">🔬 GPS Diagnostic</a>
        <a href="/gps-comprehensive-test.html" target="_blank" class="gps-debug-link">🧪 GPS Test Suite</a>
        <button @click="alert(getDeviceInfo())" class="gps-debug-link" style="border: none; cursor: pointer;">📱 Device Info</button>
    </div>

    <!-- Main GPS Detection Button -->
    <button 
        @click="detectLocation()" 
        :disabled="detecting"
        class="gps-main-button"
        x-text="buttonText">
    </button>

    <!-- Progress Indicator -->
    <div x-show="detecting && progressStage" class="gps-progress" x-text="progressStage"></div>

    <!-- Result Display -->
    <div x-show="showResult" 
         x-html="resultMessage" 
         :class="resultClass">
    </div>

    <!-- Permission Help Container -->
    <div id="permission-help-container"></div>

    <!-- Manual Input Section -->
    <div x-show="showManualInput || lastPosition" class="gps-manual-input">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <h4 style="margin: 0; color: #374151; font-size: 16px;">🔧 Manual Coordinates</h4>
            <button @click="showManualInput = !showManualInput" 
                    style="background: none; border: none; cursor: pointer; font-size: 14px; color: #6b7280;">
                <span x-text="showManualInput ? '▼' : '▶'"></span>
            </button>
        </div>
        
        <div x-show="showManualInput">
            <div x-show="lastPosition" style="margin-bottom: 15px;">
                <div style="background: #e0f2fe; padding: 12px; border-radius: 6px; margin-bottom: 10px;">
                    <div style="font-size: 14px; color: #075985; margin-bottom: 8px;">
                        <strong>📍 Detected Coordinates:</strong>
                    </div>
                    <div style="font-family: monospace; font-size: 12px; color: #0c4a6e;">
                        <div>Latitude: <span x-text="lastPosition?.lat.toFixed(8)"></span></div>
                        <div>Longitude: <span x-text="lastPosition?.lng.toFixed(8)"></span></div>
                        <div>Accuracy: ±<span x-text="lastPosition?.accuracy"></span> meters</div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 8px;">
                    <button @click="fillManualCoordinates()" 
                            style="flex: 1; background: #059669; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        📝 Fill Form Fields
                    </button>
                    <button @click="copyCoordinates()" 
                            style="flex: 1; background: #3b82f6; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        📋 Copy to Clipboard
                    </button>
                </div>
            </div>
            
            <!-- Manual Input Instructions -->
            <div style="background: #fffbeb; border: 1px solid #fed7aa; border-radius: 6px; padding: 12px;">
                <div style="font-size: 14px; color: #92400e; margin-bottom: 8px;">
                    <strong>💡 Manual Input Options:</strong>
                </div>
                <ul style="font-size: 12px; color: #78350f; margin: 0; padding-left: 20px;">
                    <li>Use the detected coordinates above</li>
                    <li>Get coordinates from Google Maps (right-click → "What's here?")</li>
                    <li>Use a GPS coordinate app on your phone</li>
                    <li>Copy from the debug tools above</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Technical Information -->
    <div class="gps-info-grid">
        <div>
            <strong>🌍 GPS Status:</strong> 
            <span x-text="'geolocation' in navigator ? 'Supported' : 'Not Available'"></span>
        </div>
        <div>
            <strong>🔒 Connection:</strong> 
            <span x-text="location.protocol === 'https:' ? 'Secure (HTTPS)' : 'Insecure (HTTP)'"></span>
        </div>
        <div>
            <strong>📱 Platform:</strong> 
            <span x-text="navigator.platform || 'Unknown'"></span>
        </div>
        <div>
            <strong>🌐 User Agent:</strong> 
            <span x-text="navigator.userAgent.split(' ').slice(-2).join(' ') || 'Unknown Browser'"></span>
        </div>
    </div>

    <!-- Usage Tips -->
    <div style="margin-top: 15px; font-size: 13px; color: #6b7280; line-height: 1.4;">
        <strong>💡 Tips for Best GPS Results:</strong><br>
        • Allow location access when prompted by your browser<br>
        • Move to an open area with clear sky view for better accuracy<br>
        • Ensure GPS is enabled on your device<br>
        • Use HTTPS connection for security compliance<br>
        • Try the diagnostic tool if experiencing issues
    </div>

    <!-- Auto-Detection on Page Load -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-detect if element has data attribute
        const detector = document.getElementById('gps-enhanced-detector');
        if (detector && detector.hasAttribute('data-auto-detect')) {
            setTimeout(() => {
                // Trigger auto-detection via Alpine
                detector._x_dataStack[0].detectLocation();
            }, 1500); // Delay for DOM stabilization
        }
    });
    </script>

</div>