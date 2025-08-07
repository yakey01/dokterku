import React, { useState, useEffect, useRef } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPin, Navigation, Wifi, WifiOff, AlertTriangle } from 'lucide-react';

// Fix Leaflet default icon issue
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

interface LocationData {
  lat: number;
  lng: number;
  name: string;
  address: string;
  radius?: number;
}

interface DynamicMapProps {
  hospitalLocation: LocationData;
  userLocation?: { lat: number; lng: number; accuracy?: number } | null;
  onLocationUpdate?: (location: { lat: number; lng: number; accuracy?: number }) => void;
  showUserLocation?: boolean;
  className?: string;
}

// Custom hospital icon
const createHospitalIcon = () => {
  return L.divIcon({
    html: `
      <div class="hospital-marker">
        <div class="hospital-icon">🏥</div>
        <div class="hospital-pulse"></div>
      </div>
    `,
    className: 'custom-hospital-marker',
    iconSize: [40, 40],
    iconAnchor: [20, 40],
  });
};

// Custom user location icon
const createUserLocationIcon = (accuracy?: number) => {
  const accuracyClass = accuracy && accuracy < 10 ? 'high-accuracy' : accuracy && accuracy < 50 ? 'medium-accuracy' : 'low-accuracy';
  return L.divIcon({
    html: `
      <div class="user-marker ${accuracyClass}">
        <div class="user-icon">📍</div>
        <div class="accuracy-ring"></div>
      </div>
    `,
    className: 'custom-user-marker',
    iconSize: [30, 30],
    iconAnchor: [15, 15],
  });
};

// Map controller component
const MapController: React.FC<{
  hospitalLocation: LocationData;
  userLocation?: { lat: number; lng: number; accuracy?: number } | null;
  onLocationUpdate?: (location: { lat: number; lng: number; accuracy?: number }) => void;
}> = ({ hospitalLocation, userLocation, onLocationUpdate }) => {
  const map = useMap();
  const [isTracking, setIsTracking] = useState(false);

  // Auto-center map when user location changes
  useEffect(() => {
    if (userLocation) {
      map.setView([userLocation.lat, userLocation.lng], 16);
    } else {
      map.setView([hospitalLocation.lat, hospitalLocation.lng], 15);
    }
  }, [userLocation, hospitalLocation, map]);

  // GPS tracking function
  const startLocationTracking = () => {
    if (!navigator.geolocation) {
      alert('Geolocation tidak didukung di browser ini');
      return;
    }

    setIsTracking(true);
    
    // Update GPS status to loading
    if (onLocationUpdate) {
      // Trigger a loading state update
      const loadingLocation = { lat: 0, lng: 0, accuracy: 0 };
      onLocationUpdate(loadingLocation);
    }
    
    const watchId = navigator.geolocation.watchPosition(
      (position) => {
        const { latitude, longitude, accuracy } = position.coords;
        const newLocation = { lat: latitude, lng: longitude, accuracy };
        
        if (onLocationUpdate) {
          onLocationUpdate(newLocation);
        }
        
        // Center map on user location
        map.setView([latitude, longitude], 16);
      },
      (error) => {
        console.error('GPS Error:', error);
        setIsTracking(false);
        
        let errorMessage = 'Gagal mendapatkan lokasi';
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = 'Izin lokasi ditolak. Silakan aktifkan GPS di pengaturan browser.';
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage = 'Informasi lokasi tidak tersedia.';
            break;
          case error.TIMEOUT:
            errorMessage = 'Timeout saat mendapatkan lokasi.';
            break;
        }
        
        alert(errorMessage);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 30000
      }
    );

    // Cleanup function
    return () => {
      navigator.geolocation.clearWatch(watchId);
      setIsTracking(false);
    };
  };

  // Calculate distance between two points
  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  };

  // Format distance
  const formatDistance = (meters: number): string => {
    if (meters < 1000) {
      return `${Math.round(meters)}m`;
    } else {
      return `${(meters / 1000).toFixed(1)}km`;
    }
  };

  return (
    <div className="map-controls absolute top-4 right-4 z-[1000] space-y-2">
      {/* GPS Tracking Button */}
      <button
        onClick={startLocationTracking}
        disabled={isTracking}
        className={`p-3 rounded-full shadow-lg transition-all duration-300 ${
          isTracking
            ? 'bg-green-500 text-white animate-pulse'
            : 'bg-white/90 backdrop-blur-sm text-gray-700 hover:bg-white hover:shadow-xl'
        }`}
        title={isTracking ? 'Sedang melacak lokasi...' : 'Aktifkan GPS Tracking'}
      >
        <Navigation className={`w-5 h-5 ${isTracking ? 'animate-spin' : ''}`} />
      </button>

      {/* Location Status */}
      {userLocation && (
        <div className="bg-white/90 backdrop-blur-sm rounded-lg p-3 shadow-lg">
          <div className="flex items-center space-x-2 mb-2">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span className="text-sm font-medium text-gray-700">Lokasi Anda</span>
          </div>
          <div className="text-xs text-gray-600 space-y-1">
            <div>Lat: {userLocation.lat.toFixed(6)}</div>
            <div>Lng: {userLocation.lng.toFixed(6)}</div>
            {userLocation.accuracy && (
              <div>Akurasi: ±{Math.round(userLocation.accuracy)}m</div>
            )}
            <div className="border-t pt-1 mt-1">
              <div className="font-medium text-green-600">
                Jarak: {formatDistance(calculateDistance(
                  userLocation.lat, userLocation.lng,
                  hospitalLocation.lat, hospitalLocation.lng
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const DynamicMap: React.FC<DynamicMapProps> = ({
  hospitalLocation,
  userLocation,
  onLocationUpdate,
  showUserLocation = true,
  className = "h-64 w-full"
}) => {
  const [mapReady, setMapReady] = useState(false);
  const [gpsStatus, setGpsStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');

  // Check GPS availability and update status based on user location
  useEffect(() => {
    if (navigator.geolocation) {
      if (userLocation) {
        setGpsStatus('success');
      } else {
        setGpsStatus('idle');
      }
    } else {
      setGpsStatus('error');
    }
  }, [userLocation]);

  // Calculate distance for display
  const getDistance = () => {
    if (!userLocation) return null;
    
    const R = 6371e3; // Earth's radius in meters
    const φ1 = userLocation.lat * Math.PI / 180;
    const φ2 = hospitalLocation.lat * Math.PI / 180;
    const Δφ = (hospitalLocation.lat - userLocation.lat) * Math.PI / 180;
    const Δλ = (hospitalLocation.lng - userLocation.lng) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    const distance = R * c;
    return distance < 1000 ? `${Math.round(distance)}m` : `${(distance / 1000).toFixed(1)}km`;
  };

  return (
    <div className={`relative ${className}`}>
      {/* Map Container */}
      <MapContainer
        center={[hospitalLocation.lat, hospitalLocation.lng]}
        zoom={15}
        className="h-full w-full rounded-lg"
        whenReady={() => setMapReady(true)}
      >
        {/* OpenStreetMap Tile Layer */}
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        {/* Hospital Marker */}
        <Marker
          position={[hospitalLocation.lat, hospitalLocation.lng]}
          icon={createHospitalIcon()}
        >
          <Popup>
            <div className="hospital-popup">
              <h3 className="font-bold text-lg text-blue-600">{hospitalLocation.name}</h3>
              <p className="text-gray-600 text-sm">{hospitalLocation.address}</p>
              {hospitalLocation.radius && (
                <p className="text-xs text-gray-500 mt-1">
                  Radius: {hospitalLocation.radius}m
                </p>
              )}
            </div>
          </Popup>
        </Marker>

        {/* User Location Marker */}
        {showUserLocation && userLocation && (
          <Marker
            position={[userLocation.lat, userLocation.lng]}
            icon={createUserLocationIcon(userLocation.accuracy)}
          >
            <Popup>
              <div className="user-popup">
                <h3 className="font-bold text-lg text-green-600">Lokasi Anda</h3>
                <p className="text-gray-600 text-sm">
                  Lat: {userLocation.lat.toFixed(6)}
                </p>
                <p className="text-gray-600 text-sm">
                  Lng: {userLocation.lng.toFixed(6)}
                </p>
                {userLocation.accuracy && (
                  <p className="text-xs text-gray-500">
                    Akurasi: ±{Math.round(userLocation.accuracy)}m
                  </p>
                )}
              </div>
            </Popup>
          </Marker>
        )}

        {/* Map Controller */}
        <MapController
          hospitalLocation={hospitalLocation}
          userLocation={userLocation}
          onLocationUpdate={onLocationUpdate}
        />
      </MapContainer>

      {/* GPS Status Overlay */}
      {!mapReady && (
        <div className="absolute inset-0 bg-gray-100 rounded-lg flex items-center justify-center">
          <div className="text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
            <p className="text-gray-600">Memuat peta...</p>
          </div>
        </div>
      )}

      {/* GPS Status Indicator */}
      <div className="absolute top-2 left-2 z-[1000]">
        <div className={`flex items-center space-x-2 px-3 py-2 rounded-full text-sm font-medium ${
          gpsStatus === 'success' ? 'bg-green-100 text-green-800' :
          gpsStatus === 'error' ? 'bg-red-100 text-red-800' :
          gpsStatus === 'loading' ? 'bg-yellow-100 text-yellow-800' :
          'bg-gray-100 text-gray-800'
        }`}>
          {gpsStatus === 'success' ? (
            <Wifi className="w-4 h-4" />
          ) : gpsStatus === 'error' ? (
            <WifiOff className="w-4 h-4" />
          ) : gpsStatus === 'loading' ? (
            <div className="w-4 h-4 border-2 border-yellow-500 border-t-transparent rounded-full animate-spin"></div>
          ) : (
            <AlertTriangle className="w-4 h-4" />
          )}
          <span>
            {gpsStatus === 'success' ? 'GPS Aktif' :
             gpsStatus === 'error' ? 'GPS Error' :
             gpsStatus === 'loading' ? 'Mendeteksi GPS...' :
             'GPS Tidak Tersedia'}
          </span>
        </div>
      </div>

      {/* Distance Display */}
      {userLocation && (
        <div className="absolute bottom-2 left-2 z-[1000]">
          <div className="bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 shadow-lg">
            <div className="flex items-center space-x-2">
              <MapPin className="w-4 h-4 text-blue-500" />
              <span className="text-sm font-medium text-gray-700">
                Jarak: {getDistance()}
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DynamicMap;
