@php $uid = uniqid(); @endphp

{{-- Load GPS Detector Script --}}
<script src="/js/gps-detector.js"></script>

<style>
.gps-success {
    background: #d1fae5 !important;
    color: #065f46 !important;
    border: 1px solid #a7f3d0 !important;
}
.gps-error {
    background: #fee2e2 !important;
    color: #991b1b !important;
    border: 1px solid #fca5a5 !important;
}
</style>

<div style="text-align: center; margin: 15px 0;" x-data="gpsDetector()">
    
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
        class="gps-detect-btn" 
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
        📍 DETEKSI LOKASI SAAT INI
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
    
    <!-- GPS Help Button -->
    <div style="text-align: center; margin-top: 10px;">
        <button onclick="window.showGPSHelp && window.showGPSHelp('general')" 
                style="
                    background: #6366f1;
                    color: white;
                    padding: 8px 16px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    width: 100%;
                ">
            🆘 GPS Troubleshooting Help
        </button>
    </div>
</div>

<!-- Load GPS Help System -->
<script src="/gps-help-system.js"></script>