{{-- WORLD-CLASS CREATIVE SOLUTION: IFRAME ISOLATION FOR LIVEWIRE COMPATIBILITY --}}
@php
    $statePath = $getStatePath();
    $defaultLat = old($statePath . '.latitude', -6.2088) ?: -6.2088;
    $defaultLng = old($statePath . '.longitude', 106.8456) ?: 106.8456;
    $uniqueId = 'isolated-map-' . str_replace(['.', '[', ']'], '-', $statePath) . '-' . uniqid();
@endphp

<div class="isolated-map-wrapper">
    {{-- Single root element ensures Livewire compatibility --}}
    
    {{-- Coordinate Inputs (hidden) for form binding --}}
    <input type="hidden" name="latitude" value="{{ $defaultLat }}" id="{{ $uniqueId }}-lat-input">
    <input type="hidden" name="longitude" value="{{ $defaultLng }}" id="{{ $uniqueId }}-lng-input">
    
    {{-- Map iframe for complete isolation --}}
    <div class="map-iframe-container mb-4">
        <iframe 
            id="{{ $uniqueId }}-iframe"
            src="data:text/html,{{ urlencode('
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { margin: 0; padding: 0; }
        #map { height: 400px; width: 100%; }
        .controls { padding: 10px; background: #f5f5f5; }
        .coords { font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div id="map"></div>
    <div class="controls">
        <button onclick="getCurrentLocation()" style="background: #3b82f6; color: white; padding: 8px 16px; border: none; border-radius: 4px;">🌍 Get My Location</button>
        <div class="coords" id="coords">Lat: ' . $defaultLat . ', Lng: ' . $defaultLng . '</div>
    </div>
    
    <script>
        const map = L.map("map").setView([' . $defaultLat . ', ' . $defaultLng . '], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
        
        const marker = L.marker([' . $defaultLat . ', ' . $defaultLng . '], {draggable: true}).addTo(map);
        
        function updateCoords(lat, lng) {
            document.getElementById("coords").textContent = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
            window.parent.postMessage({type: "mapUpdate", lat: lat, lng: lng, mapId: "' . $uniqueId . '"}, "*");
        }
        
        marker.on("dragend", function(e) {
            const pos = e.target.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });
        
        map.on("click", function(e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });
        
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                    updateCoords(lat, lng);
                });
            }
        }
    </script>
</body>
</html>
            ') }}"
            style="width: 100%; height: 500px; border: 1px solid #d1d5db; border-radius: 8px;"
            frameborder="0"
        ></iframe>
    </div>
    
    {{-- Coordinate Display --}}
    <div class="coordinate-display p-3 bg-gray-50 rounded-lg">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                <span id="{{ $uniqueId }}-lat-display" class="text-sm text-gray-900 font-mono">{{ number_format($defaultLat, 6) }}</span>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                <span id="{{ $uniqueId }}-lng-display" class="text-sm text-gray-900 font-mono">{{ number_format($defaultLng, 6) }}</span>
            </div>
        </div>
    </div>
    
    {{-- PostMessage listener for iframe communication --}}
    <script>
    (function() {
        window.addEventListener('message', function(event) {
            if (event.data.type === 'mapUpdate' && event.data.mapId === '{{ $uniqueId }}') {
                const lat = event.data.lat;
                const lng = event.data.lng;
                
                // Update hidden inputs
                const latInput = document.getElementById('{{ $uniqueId }}-lat-input');
                const lngInput = document.getElementById('{{ $uniqueId }}-lng-input');
                const latDisplay = document.getElementById('{{ $uniqueId }}-lat-display');
                const lngDisplay = document.getElementById('{{ $uniqueId }}-lng-display');
                
                if (latInput) latInput.value = lat.toFixed(6);
                if (lngInput) lngInput.value = lng.toFixed(6);
                if (latDisplay) latDisplay.textContent = lat.toFixed(6);
                if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
                
                // Update main form fields
                const mainLatField = document.querySelector('input[name="latitude"]');
                const mainLngField = document.querySelector('input[name="longitude"]');
                
                if (mainLatField) {
                    mainLatField.value = lat.toFixed(6);
                    mainLatField.dispatchEvent(new Event('input', {bubbles: true}));
                }
                if (mainLngField) {
                    mainLngField.value = lng.toFixed(6);
                    mainLngField.dispatchEvent(new Event('input', {bubbles: true}));
                }
            }
        });
    })();
    </script>
</div>