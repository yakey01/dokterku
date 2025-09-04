# Error Troubleshooting Deep Dive

## Overview
Dokumen ini menjelaskan error-error yang muncul dan solusi yang telah diimplementasikan untuk memperbaikinya.

## Errors Encountered

### 1. ✅ SyntaxError: The string did not match the expected pattern

**Problem**: Error ini muncul saat melakukan fetch request ke API endpoints.

**Root Causes**:
- Token authentication yang tidak valid atau kosong
- URL endpoint yang tidak valid
- Headers yang tidak sesuai format
- CSRF token yang tidak ditemukan

**Solutions Applied**:

#### A. Improved Token Handling
```typescript
// Before
const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// After
let token = localStorage.getItem('auth_token');
if (!token) {
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  token = csrfMeta?.getAttribute('content') || '';
}

// Validate token before making request
if (!token) {
  console.warn('No authentication token found');
  return;
}
```

#### B. Better Fetch Configuration
```typescript
// Before
const response = await fetch('/api/v2/dashboards/dokter/', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-CSRF-TOKEN': token || '',
    'Content-Type': 'application/json'
  }
});

// After
const response = await fetch('/api/v2/dashboards/dokter/', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
    'X-CSRF-TOKEN': token
  },
  credentials: 'same-origin'
});
```

#### C. Enhanced Error Handling
```typescript
try {
  // API call
  if (response.ok) {
    const data = await response.json();
    // Process data
  } else {
    console.error('Failed to load data:', response.status, response.statusText);
  }
} catch (error) {
  console.error('Error loading data:', error);
  // Set default data if API fails
  setUserData({
    name: 'User',
    email: 'user@example.com',
    role: 'dokter'
  });
}
```

### 2. ✅ GeolocationPositionError

**Problem**: GPS error saat mencoba mendapatkan lokasi user.

**Root Causes**:
- Permission denied untuk akses lokasi
- GPS tidak tersedia
- Timeout saat mendapatkan lokasi
- Browser tidak mendukung geolocation

**Solutions Applied**:

#### A. Enhanced GPS Error Handling
```typescript
const errorCallback = (error: GeolocationPositionError) => {
  console.error('GPS Error:', error);
  let errorMessage = 'GPS error occurred';
  
  switch (error.code) {
    case error.PERMISSION_DENIED:
      errorMessage = 'GPS permission denied. Please enable location access.';
      break;
    case error.POSITION_UNAVAILABLE:
      errorMessage = 'GPS position unavailable. Please check your location settings.';
      break;
    case error.TIMEOUT:
      errorMessage = 'GPS timeout. Please try again.';
      break;
    default:
      errorMessage = `GPS error: ${error.message}`;
  }
  
  console.error(errorMessage);
  setGpsStatus('error');
};
```

#### B. Improved GPS Options
```typescript
const options: PositionOptions = {
  enableHighAccuracy: true,
  timeout: 15000, // Increased timeout
  maximumAge: 60000 // Increased maximum age
};
```

#### C. Better GPS Promise Handling
```typescript
const position = await new Promise<GeolocationPosition>((resolve, reject) => {
  if (!navigator.geolocation) {
    reject(new Error('Geolocation is not supported'));
    return;
  }

  navigator.geolocation.getCurrentPosition(
    resolve,
    (error) => {
      let errorMessage = 'GPS error occurred';
      switch (error.code) {
        case error.PERMISSION_DENIED:
          errorMessage = 'GPS permission denied. Please enable location access.';
          break;
        case error.POSITION_UNAVAILABLE:
          errorMessage = 'GPS position unavailable. Please check your location settings.';
          break;
        case error.TIMEOUT:
          errorMessage = 'GPS timeout. Please try again.';
          break;
        default:
          errorMessage = `GPS error: ${error.message}`;
      }
      reject(new Error(errorMessage));
    },
    {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 60000
    }
  );
});
```

## Implementation Details

### 1. User Data Loading
```typescript
useEffect(() => {
  const loadUserData = async () => {
    try {
      // Get token with better error handling
      let token = localStorage.getItem('auth_token');
      if (!token) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        token = csrfMeta?.getAttribute('content') || '';
      }

      // Validate token before making request
      if (!token) {
        console.warn('No authentication token found');
        return;
      }

      const response = await fetch('/api/v2/dashboards/dokter/', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token
        },
        credentials: 'same-origin'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data?.user) {
          setUserData(data.data.user);
        } else {
          console.warn('User data not found in response:', data);
        }
      } else {
        console.error('Failed to load user data:', response.status, response.statusText);
      }
    } catch (error) {
      console.error('Error loading user data:', error);
      // Set default user data if API fails
      setUserData({
        name: 'User',
        email: 'user@example.com',
        role: 'dokter'
      });
    }
  };

  loadUserData();
}, []);
```

### 2. Schedule and Work Location Loading
```typescript
useEffect(() => {
  const loadScheduleAndWorkLocation = async () => {
    try {
      // Get token with better error handling
      let token = localStorage.getItem('auth_token');
      if (!token) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        token = csrfMeta?.getAttribute('content') || '';
      }

      // Validate token before making request
      if (!token) {
        console.warn('No authentication token found for schedule/work location');
        return;
      }

      const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        'X-CSRF-TOKEN': token
      };

      // Fetch schedule data
      const scheduleResponse = await fetch('/api/v2/dashboards/dokter/jadwal-jaga', {
        method: 'GET',
        headers,
        credentials: 'same-origin'
      });

      if (scheduleResponse.ok) {
        // Process schedule data
      } else {
        console.error('Failed to load schedule data:', scheduleResponse.status, scheduleResponse.statusText);
      }

      // Fetch work location data
      const workLocationResponse = await fetch('/api/v2/dashboards/dokter/work-location/status', {
        method: 'GET',
        headers,
        credentials: 'same-origin'
      });

      if (workLocationResponse.ok) {
        // Process work location data
      } else {
        console.error('Failed to load work location data:', workLocationResponse.status, workLocationResponse.statusText);
      }

    } catch (error) {
      console.error('Error loading schedule and work location:', error);
      // Set default schedule data if API fails
      setScheduleData(prev => ({
        ...prev,
        todaySchedule: [],
        currentShift: null,
        workLocation: null,
        validationMessage: 'Gagal memuat data jadwal dan lokasi kerja'
      }));
    }
  };

  loadScheduleAndWorkLocation();
}, []);
```

### 3. Check-in/Check-out Error Handling
```typescript
const handleCheckIn = async () => {
  try {
    // Validate schedule and work location first
    if (!scheduleData.canCheckIn) {
      alert(`❌ Tidak dapat melakukan check-in: ${scheduleData.validationMessage}`);
      return;
    }

    // Get current GPS location with better error handling
    const position = await new Promise<GeolocationPosition>((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation is not supported'));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        resolve,
        (error) => {
          let errorMessage = 'GPS error occurred';
          switch (error.code) {
            case error.PERMISSION_DENIED:
              errorMessage = 'GPS permission denied. Please enable location access.';
              break;
            case error.POSITION_UNAVAILABLE:
              errorMessage = 'GPS position unavailable. Please check your location settings.';
              break;
            case error.TIMEOUT:
              errorMessage = 'GPS timeout. Please try again.';
              break;
            default:
              errorMessage = `GPS error: ${error.message}`;
          }
          reject(new Error(errorMessage));
        },
        {
          enableHighAccuracy: true,
          timeout: 15000,
          maximumAge: 60000
        }
      );
    });

    // Process position and make API call
    const { latitude, longitude, accuracy } = position.coords;
    
    // Get authentication token
    let token = localStorage.getItem('auth_token');
    if (!token) {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      token = csrfMeta?.getAttribute('content') || '';
    }

    if (!token) {
      alert('❌ Tidak dapat melakukan check-in: Token autentikasi tidak ditemukan');
      return;
    }

    // Make API call with proper error handling
    const response = await fetch('/api/v2/dashboards/dokter/checkin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        latitude: latitude,
        longitude: longitude,
        accuracy: accuracy,
        location: hospitalLocation.name,
        schedule_id: scheduleData.currentShift?.id,
        work_location_id: scheduleData.workLocation?.id
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      // Handle success
      setIsCheckedIn(true);
      alert('✅ Check-in berhasil!');
    } else {
      alert(`❌ Check-in gagal: ${result.message || 'Unknown error'}`);
    }
  } catch (error) {
    console.error('Check-in error:', error);
    if (error instanceof Error) {
      alert(`❌ Check-in gagal: ${error.message}`);
    } else {
      alert('❌ Check-in gagal: Terjadi kesalahan yang tidak diketahui');
    }
  }
};
```

## Testing Steps

### 1. Test Authentication
```bash
# Check if user is authenticated
curl -H "Authorization: Bearer {token}" \
     -H "X-CSRF-TOKEN: {csrf_token}" \
     http://localhost:8000/api/v2/dashboards/dokter/
```

### 2. Test GPS Functionality
- Open browser developer tools
- Go to Console tab
- Check for GPS-related logs
- Verify GPS permissions are granted

### 3. Test API Endpoints
```bash
# Test schedule endpoint
curl -H "Authorization: Bearer {token}" \
     -H "X-CSRF-TOKEN: {csrf_token}" \
     http://localhost:8000/api/v2/dashboards/dokter/jadwal-jaga

# Test work location endpoint
curl -H "Authorization: Bearer {token}" \
     -H "X-CSRF-TOKEN: {csrf_token}" \
     http://localhost:8000/api/v2/dashboards/dokter/work-location/status
```

## Monitoring

### 1. Console Logs
Monitor these console logs for debugging:
- `Error loading user data:`
- `Error loading schedule and work location:`
- `GPS Error:`
- `Check-in error:`

### 2. Network Tab
- Monitor API calls in browser developer tools
- Check response status codes
- Verify request headers

### 3. Error Messages
- Check for specific error messages in alerts
- Monitor validation messages
- Track GPS status changes

## Expected Results After Fix

1. **No More SyntaxError**: API calls should work without pattern matching errors
2. **Better GPS Handling**: GPS errors should be handled gracefully with specific error messages
3. **Improved User Experience**: Users should see helpful error messages instead of generic errors
4. **Fallback Data**: Application should continue working even if some APIs fail
5. **Better Debugging**: Console logs should provide clear information about what went wrong

## Next Steps

1. **Test in Production**: Deploy changes and monitor for errors
2. **User Testing**: Test with different user scenarios
3. **Performance Monitoring**: Monitor API response times
4. **Error Tracking**: Implement proper error tracking system
5. **Documentation Update**: Update user guides with troubleshooting information
