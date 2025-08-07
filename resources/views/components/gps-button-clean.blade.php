{{-- GPS Button Component - Clean Version Without Script Exposure --}}

<div id="gps-detector-container" x-data="{
    detecting: false,
    showResult: false,
    buttonText: '📍 DETEKSI LOKASI SAAT INI',
    resultMessage: '',
    resultClass: '',
    manualLat: '',
    manualLon: '',
    
    detectLocation() {
        this.detecting = true;
        this.buttonText = '🔍 Mendeteksi lokasi...';
        this.showResult = false;
        
        if (!navigator.geolocation) {
            this.showError('❌ Browser tidak mendukung GPS!');
            this.resetButton();
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude.toFixed(8);
                const lon = position.coords.longitude.toFixed(8);
                const acc = Math.round(position.coords.accuracy);
                
                // Find form inputs
                let latInput = document.querySelector('input[wire\\:model*=\"latitude\"]') ||
                              document.querySelector('input[name=\"latitude\"]');
                let lonInput = document.querySelector('input[wire\\:model*=\"longitude\"]') ||
                               document.querySelector('input[name=\"longitude\"]');
                
                if (latInput && lonInput) {
                    latInput.value = lat;
                    lonInput.value = lon;
                    
                    // Trigger events
                    ['input', 'change', 'blur'].forEach(eventType => {
                        latInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                        lonInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                    });
                    
                    this.buttonText = '✅ BERHASIL!';
                    this.showSuccess('🎉 Koordinat berhasil diisi!<br>📌 Lat: ' + lat + '<br>📌 Lon: ' + lon + '<br>🎯 Akurasi: ±' + acc + 'm');
                } else {
                    this.showError('❌ Field tidak ditemukan!<br>Lat: ' + lat + '<br>Lon: ' + lon);
                    this.manualLat = lat;
                    this.manualLon = lon;
                }
                
                setTimeout(() => this.resetButton(), 3000);
            },
            (error) => {
                let message = '';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = '❌ Akses ditolak! Izinkan lokasi di browser.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = '❌ Lokasi tidak tersedia! Aktifkan GPS.';
                        break;
                    case error.TIMEOUT:
                        message = '⏰ Timeout! Pindah ke tempat terbuka.';
                        break;
                    default:
                        message = '❌ GPS Error: ' + error.message;
                        break;
                }
                this.showError(message);
                this.resetButton();
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    },
    
    fillManualCoordinates() {
        if (!this.manualLat || !this.manualLon) {
            this.showError('❌ Mohon isi kedua koordinat!');
            return;
        }
        
        let latInput = document.querySelector('input[wire\\:model*=\"latitude\"]') ||
                      document.querySelector('input[name=\"latitude\"]');
        let lonInput = document.querySelector('input[wire\\:model*=\"longitude\"]') ||
                       document.querySelector('input[name=\"longitude\"]');
        
        if (latInput && lonInput) {
            latInput.value = this.manualLat;
            lonInput.value = this.manualLon;
            
            ['input', 'change', 'blur'].forEach(eventType => {
                latInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                lonInput.dispatchEvent(new Event(eventType, { bubbles: true }));
            });
            
            this.showSuccess('✅ Koordinat manual berhasil diisi!');
        } else {
            this.showError('❌ Field form tidak ditemukan!');
        }
    },
    
    showSuccess(message) {
        this.resultMessage = message;
        this.resultClass = 'success-result';
        this.showResult = true;
    },
    
    showError(message) {
        this.resultMessage = message;
        this.resultClass = 'error-result';
        this.showResult = true;
    },
    
    resetButton() {
        this.detecting = false;
        this.buttonText = '📍 DETEKSI LOKASI SAAT INI';
    }
}" style="text-align: center; margin: 15px 0;">

    <style>
    .success-result {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .error-result {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    </style>
    
    <!-- Debug Links -->
    <div style="margin-bottom: 15px;">
        <a href="/debug-gps" target="_blank" style="
            display: inline-block;
            background: #f59e0b;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 10px;
        ">🔬 Debug Tool</a>
        <a href="/test-gps" target="_blank" style="
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        ">🧪 GPS Test</a>
    </div>
    
    <!-- Main GPS Button -->
    <button 
        @click="detectLocation()" 
        :disabled="detecting"
        style="
            background: #10b981;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        " 
        onmouseover="this.style.background='#059669'" 
        onmouseout="this.style.background='#10b981'"
        x-text="buttonText">
    </button>
    
    <!-- Result Display -->
    <div x-show="showResult" 
         x-html="resultMessage" 
         style="margin-top: 10px; padding: 10px; border-radius: 6px;"
         :class="resultClass">
    </div>
    
    <!-- Manual Input Section -->
    <div style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; text-align: left;">
        <h4 style="margin: 0 0 10px 0; color: #374151;">🔧 Manual Input (Jika GPS Gagal)</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
                <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 5px;">Latitude:</label>
                <input type="text" x-model="manualLat" placeholder="-6.2088" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 5px;">Longitude:</label>
                <input type="text" x-model="manualLon" placeholder="106.8238" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;">
            </div>
        </div>
        <button @click="fillManualCoordinates()" 
                :disabled="!manualLat || !manualLon"
                style="
                    width: 100%;
                    margin-top: 10px;
                    background: #6366f1;
                    color: white;
                    padding: 10px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                ">
            📝 ISI KOORDINAT MANUAL
        </button>
    </div>
    
    <small style="color: #6b7280; display: block; margin-top: 8px;">
        💡 Tips: Gunakan debug tool untuk analisis atau copy koordinat dari Google Maps
    </small>
</div>