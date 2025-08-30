{{-- WORLD-CLASS LIVEWIRE-SAFE MAP COMPONENT --}}
<div class="livewire-map-container" wire:ignore>
    {{-- Single root element ensures Livewire compatibility --}}
    
    {{-- Map Container --}}
    <div class="mb-4">
        <div 
            id="livewire-map-{{ $statePath }}-{{ uniqid() }}" 
            class="w-full border border-gray-300 rounded-lg overflow-hidden"
            style="height: 400px;"
        ></div>
    </div>

    {{-- GPS Button --}}
    <div class="mb-4">
        <button 
            type="button"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            wire:click="$refresh"
            onclick="if(window.initLivewireMap) window.initLivewireMap('{{ $statePath }}', {{ $latitude }}, {{ $longitude }})"
        >
            <span class="mr-2">🌍</span>
            Get My Location
        </button>
        <span class="ml-3 text-sm text-gray-600">Click to detect your location</span>
    </div>

    {{-- Coordinate Display --}}
    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                <span class="text-sm text-gray-900 font-mono">{{ number_format($latitude, 6) }}</span>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                <span class="text-sm text-gray-900 font-mono">{{ number_format($longitude, 6) }}</span>
            </div>
        </div>
    </div>

    {{-- Embedded JavaScript for map functionality --}}
    @once
    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script>
    window.initLivewireMap = function(statePath, lat, lng) {
        const mapId = 'livewire-map-' + statePath + '-' + Math.random().toString(36).substr(2, 9);
        const mapEl = document.querySelector('[id^="livewire-map-' + statePath + '"]');
        
        if (!mapEl || !window.L) return;
        
        // Create map
        const map = L.map(mapEl, {
            center: [lat, lng],
            zoom: 15
        });
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add marker
        const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        
        // Handle marker drag
        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            @this.call('updateCoordinates', pos.lat, pos.lng);
        });
        
        // Handle map click
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            @this.call('updateCoordinates', e.latlng.lat, e.latlng.lng);
        });
    };
    </script>
    @endpush
    @endonce
</div>