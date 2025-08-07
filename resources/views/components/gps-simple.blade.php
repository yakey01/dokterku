{{-- Minimal GPS Detection for Filament Forms --}}

<div style="text-align: center; margin: 15px 0; padding: 20px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
    
    <!-- Simple GPS Button -->
    <button 
        id="gps-detect-btn"
        onclick="detectGPSLocation()"
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
            margin-bottom: 15px;
        ">
        📍 DETEKSI LOKASI SAAT INI
    </button>
    
    <!-- Result Area -->
    <div id="gps-result" style="display: none; margin: 10px 0; padding: 10px; border-radius: 6px;"></div>
    
    <!-- Manual Input -->
    <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px; text-align: left;">
        <h4 style="margin: 0 0 10px 0; color: #374151;">🔧 Input Manual</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
            <input type="text" id="manual-lat" placeholder="Latitude" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            <input type="text" id="manual-lon" placeholder="Longitude" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
        </div>
        <button onclick="fillManualCoords()" style="width: 100%; background: #6366f1; color: white; padding: 10px; border: none; border-radius: 6px; cursor: pointer;">
            📝 ISI KOORDINAT MANUAL
        </button>
    </div>
    
    <!-- Debug Links -->
    <div style="margin-top: 15px;">
        <a href="/debug-gps" target="_blank" style="margin-right: 10px; color: #3b82f6; text-decoration: underline;">🔬 Debug Tool</a>
        <a href="/test-gps" target="_blank" style="color: #3b82f6; text-decoration: underline;">🧪 GPS Test</a>
    </div>
</div>

<script>
function detectGPSLocation() {
    const button = document.getElementById('gps-detect-btn');
    const result = document.getElementById('gps-result');
    
    button.disabled = true;
    button.innerHTML = '🔍 Mendeteksi lokasi...';
    button.style.background = '#6b7280';
    result.style.display = 'none';
    
    if (!navigator.geolocation) {
        showResult('❌ Browser tidak mendukung GPS!', 'error');
        resetButton();
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude.toFixed(8);
            const lon = position.coords.longitude.toFixed(8);
            const acc = Math.round(position.coords.accuracy);
            
            console.log('📍 GPS Success:', lat, lon, acc + 'm');
            
            // Find form inputs with multiple strategies
            let latInput = findLatitudeInput();
            let lonInput = findLongitudeInput();
            
            if (latInput && lonInput) {
                // Fill the inputs
                latInput.value = lat;
                lonInput.value = lon;
                
                // Trigger events for Filament/Livewire
                triggerInputEvents(latInput);
                triggerInputEvents(lonInput);
                
                button.innerHTML = '✅ BERHASIL!';
                button.style.background = '#10b981';
                showResult(`🎉 Koordinat berhasil diisi!<br>📌 Lat: ${lat}<br>📌 Lon: ${lon}<br>🎯 Akurasi: ±${acc}m`, 'success');
            } else {
                console.error('❌ Form inputs not found');
                showResult(`❌ Field form tidak ditemukan!<br>📍 Koordinat: ${lat}, ${lon}<br>📝 Gunakan input manual di bawah`, 'error');
                
                // Auto-fill manual inputs
                document.getElementById('manual-lat').value = lat;
                document.getElementById('manual-lon').value = lon;
            }
            
            setTimeout(resetButton, 3000);
        },
        function(error) {
            console.error('❌ GPS Error:', error);
            let message = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = '❌ Akses lokasi ditolak!<br>🔧 Izinkan akses lokasi di browser';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = '❌ Lokasi tidak tersedia!<br>📡 Aktifkan GPS/Location services';
                    break;
                case error.TIMEOUT:
                    message = '⏰ Timeout GPS!<br>🚶 Pindah ke tempat terbuka';
                    break;
                default:
                    message = `❌ GPS Error: ${error.message}`;
                    break;
            }
            showResult(message, 'error');
            resetButton();
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

function findLatitudeInput() {
    return document.querySelector('input[wire\:model*="latitude"]') ||
           document.querySelector('input[name="latitude"]') ||
           document.querySelector('#latitude') ||
           Array.from(document.querySelectorAll('input')).find(input => {
               const label = input.closest('.fi-fo-field-wrp')?.querySelector('label')?.textContent?.toLowerCase();
               return label?.includes('latitude') || label?.includes('lintang');
           });
}

function findLongitudeInput() {
    return document.querySelector('input[wire\:model*="longitude"]') ||
           document.querySelector('input[name="longitude"]') ||
           document.querySelector('#longitude') ||
           Array.from(document.querySelectorAll('input')).find(input => {
               const label = input.closest('.fi-fo-field-wrp')?.querySelector('label')?.textContent?.toLowerCase();
               return label?.includes('longitude') || label?.includes('bujur');
           });
}

function triggerInputEvents(input) {
    ['input', 'change', 'keyup', 'blur', 'focus'].forEach(eventType => {
        input.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
    });
    input.focus();
    setTimeout(() => input.blur(), 50);
}

function fillManualCoords() {
    const lat = document.getElementById('manual-lat').value;
    const lon = document.getElementById('manual-lon').value;
    
    if (!lat || !lon) {
        showResult('❌ Mohon isi kedua koordinat!', 'error');
        return;
    }
    
    let latInput = findLatitudeInput();
    let lonInput = findLongitudeInput();
    
    if (latInput && lonInput) {
        latInput.value = lat;
        lonInput.value = lon;
        triggerInputEvents(latInput);
        triggerInputEvents(lonInput);
        showResult(`✅ Koordinat manual berhasil diisi!<br>📌 Lat: ${lat}<br>📌 Lon: ${lon}`, 'success');
    } else {
        showResult('❌ Field form tidak ditemukan! Input manual ke field Latitude/Longitude.', 'error');
    }
}

function showResult(message, type) {
    const result = document.getElementById('gps-result');
    result.innerHTML = message;
    result.style.display = 'block';
    
    if (type === 'success') {
        result.style.background = '#d1fae5';
        result.style.color = '#065f46';
        result.style.border = '1px solid #a7f3d0';
    } else {
        result.style.background = '#fee2e2';
        result.style.color = '#991b1b';
        result.style.border = '1px solid #fca5a5';
    }
}

function resetButton() {
    const button = document.getElementById('gps-detect-btn');
    button.disabled = false;
    button.innerHTML = '📍 DETEKSI LOKASI SAAT INI';
    button.style.background = '#10b981';
}
</script>