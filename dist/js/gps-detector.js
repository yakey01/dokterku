// GPS Detector Alpine.js Component
// Only define if not already defined to prevent duplicates
if (typeof window.gpsDetector === 'undefined') {
    window.gpsDetector = function() {
        return {
            detecting: false,
            showResult: false,
            buttonText: '📍 DETEKSI LOKASI SAAT INI',
            resultMessage: '',
            resultClass: '',
            manualLat: '',
            manualLon: '',
            
            init() {
                console.log('🚀 GPS Detector initialized with Alpine.js');
            },
            
            detectLocation() {
                console.log('🔍 GPS Detection started...');
                
                this.detecting = true;
                this.buttonText = '🔍 Mendeteksi lokasi...';
                this.showResult = false;
                
                if (!navigator.geolocation) {
                    this.showError('❌ Browser tidak mendukung GPS!');
                    this.resetButton();
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => this.onLocationSuccess(position),
                    (error) => this.onLocationError(error),
                    {
                        enableHighAccuracy: true,
                        timeout: 20000,
                        maximumAge: 0
                    }
                );
            },
            
            onLocationSuccess(position) {
                const lat = position.coords.latitude.toFixed(8);
                const lon = position.coords.longitude.toFixed(8);
                const acc = Math.round(position.coords.accuracy);
                
                console.log(`📍 GPS Success: ${lat}, ${lon}, ±${acc}m`);
                
                // Find form inputs using comprehensive strategy
                const inputs = this.findFormInputs();
                
                if (inputs.latitude && inputs.longitude) {
                    // Fill the form inputs
                    this.fillInputs(inputs.latitude, inputs.longitude, lat, lon);
                    
                    this.buttonText = '✅ KOORDINAT TERISI!';
                    this.showSuccess(`🎉 Koordinat berhasil diisi!<br>
                                   📌 Latitude: ${lat}<br>
                                   📌 Longitude: ${lon}<br>
                                   🎯 Akurasi: ±${acc} meter<br>
                                   ✅ Form siap untuk disimpan`);
                } else {
                    console.error('❌ Form inputs not found');
                    this.showError(`❌ Field form tidak ditemukan!<br><br>
                                  📍 Koordinat GPS:<br>
                                  <strong>Lat: ${lat}</strong><br>
                                  <strong>Lon: ${lon}</strong><br>
                                  🎯 Akurasi: ±${acc}m<br><br>
                                  📝 Gunakan input manual di bawah`);
                    
                    // Auto-fill manual inputs
                    this.manualLat = lat;
                    this.manualLon = lon;
                }
                
                setTimeout(() => this.resetButton(), 4000);
            },
            
            onLocationError(error) {
                console.error('❌ GPS Error:', error);
                
                let message = '';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = '❌ Akses lokasi ditolak!<br>🔧 Klik ikon 🔒 di address bar → Allow<br>🔄 Refresh dan coba lagi';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = '❌ Lokasi tidak tersedia!<br>📡 Aktifkan GPS/Location services<br>🌐 Periksa koneksi internet';
                        break;
                    case error.TIMEOUT:
                        message = '⏰ Timeout GPS!<br>🚶 Pindah ke tempat terbuka<br>🔄 Coba lagi';
                        break;
                    default:
                        message = `❌ GPS Error!<br>📝 ${error.message}<br>🔧 Gunakan input manual`;
                        break;
                }
                
                this.showError(message);
                this.resetButton();
            },
            
            findFormInputs() {
                console.log('🔍 Searching for form inputs...');
                
                let latInput = null;
                let lonInput = null;
                
                // Strategy 1: Filament wire:model
                document.querySelectorAll('input[wire\\:model]').forEach(input => {
                    const model = input.getAttribute('wire:model');
                    if (model?.includes('latitude')) latInput = input;
                    if (model?.includes('longitude')) lonInput = input;
                });
                
                // Strategy 2: Form names
                if (!latInput) latInput = document.querySelector('input[name="latitude"]');
                if (!lonInput) lonInput = document.querySelector('input[name="longitude"]');
                
                // Strategy 3: IDs
                if (!latInput) latInput = document.querySelector('#latitude, #latitude-input');
                if (!lonInput) lonInput = document.querySelector('#longitude, #longitude-input');
                
                // Strategy 4: Data attributes
                if (!latInput) latInput = document.querySelector('input[data-field="latitude"]');
                if (!lonInput) lonInput = document.querySelector('input[data-field="longitude"]');
                
                // Strategy 5: Label text search
                if (!latInput || !lonInput) {
                    document.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                        const wrapper = input.closest('.fi-fo-field-wrp, .form-group, .field-wrapper');
                        const label = wrapper?.querySelector('label')?.textContent?.toLowerCase();
                        
                        if (label?.includes('latitude') || label?.includes('lintang')) latInput = input;
                        if (label?.includes('longitude') || label?.includes('bujur')) lonInput = input;
                    });
                }
                
                console.log('🎯 Input search result:', {
                    latitude: !!latInput,
                    longitude: !!lonInput,
                    totalInputs: document.querySelectorAll('input').length
                });
                
                return { latitude: latInput, longitude: lonInput };
            },
            
            fillInputs(latInput, lonInput, lat, lon) {
                console.log('📝 Filling form inputs...');
                
                // Set values
                latInput.value = lat;
                lonInput.value = lon;
                
                // Trigger comprehensive events
                [latInput, lonInput].forEach(input => {
                    ['input', 'change', 'keyup', 'blur'].forEach(eventType => {
                        input.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
                    });
                    
                    // Alpine.js and Livewire events
                    if (window.Alpine) {
                        input.dispatchEvent(new CustomEvent('alpine:update', { 
                            detail: { value: input.value }, bubbles: true 
                        }));
                    }
                    
                    if (window.Livewire) {
                        input.dispatchEvent(new CustomEvent('livewire:update', { 
                            detail: { value: input.value }, bubbles: true 
                        }));
                    }
                    
                    // Focus cycle
                    input.focus();
                    setTimeout(() => input.blur(), 50);
                });
                
                // Force Livewire component update
                if (window.Livewire) {
                    setTimeout(() => {
                        const wireId = latInput.closest('[wire\\:id]')?.getAttribute('wire:id');
                        if (wireId) {
                            const component = Livewire.find(wireId);
                            if (component) {
                                console.log('🔄 Forcing Livewire component update...');
                                component.set('data.latitude', lat);
                                component.set('data.longitude', lon);
                            }
                        }
                    }, 100);
                }
            },
            
            fillManualCoordinates() {
                console.log('📝 Manual coordinate fill triggered');
                
                if (!this.manualLat || !this.manualLon) {
                    this.showError('❌ Mohon isi kedua koordinat!');
                    return;
                }
                
                const inputs = this.findFormInputs();
                
                if (inputs.latitude && inputs.longitude) {
                    this.fillInputs(inputs.latitude, inputs.longitude, this.manualLat, this.manualLon);
                    this.showSuccess(`✅ Koordinat manual berhasil diisi!<br>
                                   📌 Latitude: ${this.manualLat}<br>
                                   📌 Longitude: ${this.manualLon}`);
                } else {
                    this.showError('❌ Field form tidak ditemukan! Silakan input manual langsung ke field Latitude/Longitude di form.');
                }
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
                this.buttonText = '📍 DETEKSI LOKASI SAAT INI';
            }
        };
    };
}