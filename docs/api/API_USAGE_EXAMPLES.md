# Dokterku API v2 - NonParamedis Usage Examples

## Table of Contents
1. [Authentication Setup](#authentication-setup)
2. [cURL Examples](#curl-examples)
3. [JavaScript Examples](#javascript-examples)
4. [PHP Examples](#php-examples)
5. [Python Examples](#python-examples)
6. [Mobile App Integration](#mobile-app-integration)
7. [Error Handling](#error-handling-patterns)
8. [Rate Limiting Guidelines](#rate-limiting-guidelines)

## Authentication Setup

### 1. Login and Token Management

First, authenticate to get your Bearer token:

```bash
curl -X POST https://dokterku.com/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "login": "sari.lestari@dokterku.com",
    "password": "password123",
    "device_name": "Mobile App v1.0",
    "device_fingerprint": "unique-device-id-123"
  }'
```

Response:
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Sari Lestari",
      "email": "sari.lestari@dokterku.com",
      "role": "non_paramedis"
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "expires_in": 43200
  }
}
```

### 2. Using the Token

Include the token in all subsequent requests:
```
Authorization: Bearer 1|abcdef123456...
```

## cURL Examples

### Dashboard Overview
```bash
curl -X GET https://dokterku.com/api/v2/dashboards/nonparamedis/ \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Check In Attendance
```bash
curl -X POST https://dokterku.com/api/v2/dashboards/nonparamedis/attendance/checkin \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 10.5,
    "work_location_id": 1
  }'
```

### Check Out Attendance
```bash
curl -X POST https://dokterku.com/api/v2/dashboards/nonparamedis/attendance/checkout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 8.0
  }'
```

### Get Schedule
```bash
curl -X GET "https://dokterku.com/api/v2/dashboards/nonparamedis/schedule?month=7&year=2025" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Get Reports
```bash
curl -X GET "https://dokterku.com/api/v2/dashboards/nonparamedis/reports?period=month&date=2025-07-15" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

## JavaScript Examples

### 1. API Client Setup

```javascript
class DokterkuAPI {
  constructor(baseUrl = 'https://dokterku.com/api/v2') {
    this.baseUrl = baseUrl;
    this.token = localStorage.getItem('auth_token');
  }

  setToken(token) {
    this.token = token;
    localStorage.setItem('auth_token', token);
  }

  async request(method, endpoint, data = null) {
    const config = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(this.token && { 'Authorization': `Bearer ${this.token}` })
      }
    };

    if (data) {
      config.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, config);
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'API request failed');
      }

      return await response.json();
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  // Authentication methods
  async login(credentials) {
    const response = await this.request('POST', '/auth/login', credentials);
    if (response.data.token) {
      this.setToken(response.data.token);
    }
    return response;
  }

  async logout() {
    const response = await this.request('POST', '/auth/logout');
    localStorage.removeItem('auth_token');
    this.token = null;
    return response;
  }

  // NonParamedis methods
  async getDashboard() {
    return this.request('GET', '/dashboards/nonparamedis/');
  }

  async getAttendanceStatus() {
    return this.request('GET', '/dashboards/nonparamedis/attendance/status');
  }

  async checkIn(location) {
    return this.request('POST', '/dashboards/nonparamedis/attendance/checkin', location);
  }

  async checkOut(location) {
    return this.request('POST', '/dashboards/nonparamedis/attendance/checkout', location);
  }

  async getSchedule(month = null, year = null) {
    const params = new URLSearchParams();
    if (month) params.append('month', month);
    if (year) params.append('year', year);
    
    const query = params.toString() ? `?${params.toString()}` : '';
    return this.request('GET', `/dashboards/nonparamedis/schedule${query}`);
  }

  async getReports(period = 'month', date = null) {
    const params = new URLSearchParams();
    params.append('period', period);
    if (date) params.append('date', date);
    
    return this.request('GET', `/dashboards/nonparamedis/reports?${params.toString()}`);
  }
}
```

### 2. Usage Examples

```javascript
// Initialize API client
const api = new DokterkuAPI();

// Login
async function login() {
  try {
    const response = await api.login({
      login: 'sari.lestari@dokterku.com',
      password: 'password123',
      device_name: 'Web Browser',
      device_fingerprint: generateDeviceFingerprint()
    });
    
    console.log('Login successful:', response.data.user);
    return response.data;
  } catch (error) {
    console.error('Login failed:', error.message);
    throw error;
  }
}

// Get current location and check in
async function checkInWithLocation() {
  try {
    // Get GPS location
    const position = await getCurrentPosition();
    
    const response = await api.checkIn({
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      work_location_id: 1
    });
    
    console.log('Check-in successful:', response.data);
    return response.data;
  } catch (error) {
    console.error('Check-in failed:', error.message);
    throw error;
  }
}

// Helper function to get GPS location
function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation not supported'));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      resolve,
      reject,
      { 
        enableHighAccuracy: true, 
        timeout: 10000, 
        maximumAge: 60000 
      }
    );
  });
}

// Load dashboard data
async function loadDashboard() {
  try {
    const response = await api.getDashboard();
    
    // Update UI with dashboard data
    updateDashboardUI(response.data);
    
    return response.data;
  } catch (error) {
    console.error('Dashboard load failed:', error.message);
    throw error;
  }
}

function updateDashboardUI(data) {
  // Update user info
  document.getElementById('user-name').textContent = data.user.name;
  document.getElementById('user-role').textContent = data.user.role;
  
  // Update stats
  document.getElementById('hours-today').textContent = `${data.stats.hours_today}h ${data.stats.minutes_today}m`;
  document.getElementById('work-days').textContent = data.stats.work_days_this_month;
  document.getElementById('attendance-rate').textContent = `${data.stats.attendance_rate}%`;
  
  // Update quick actions
  const actionsContainer = document.getElementById('quick-actions');
  actionsContainer.innerHTML = '';
  
  data.quick_actions.forEach(action => {
    const actionElement = createActionElement(action);
    actionsContainer.appendChild(actionElement);
  });
}

function createActionElement(action) {
  const element = document.createElement('div');
  element.className = `action-item ${action.enabled ? 'enabled' : 'disabled'}`;
  element.innerHTML = `
    <div class="action-icon">${action.icon}</div>
    <div class="action-content">
      <div class="action-title">${action.title}</div>
      <div class="action-subtitle">${action.subtitle}</div>
    </div>
  `;
  
  if (action.enabled) {
    element.addEventListener('click', () => handleActionClick(action));
  }
  
  return element;
}

async function handleActionClick(action) {
  switch (action.action) {
    case 'attendance':
      await handleAttendanceAction();
      break;
    case 'schedule':
      await loadSchedule();
      break;
    case 'reports':
      await loadReports();
      break;
  }
}
```

## PHP Examples

### 1. PHP API Client Class

```php
<?php

class DokterkuAPI 
{
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl = 'https://dokterku.com/api/v2')
    {
        $this->baseUrl = $baseUrl;
    }
    
    public function setToken($token)
    {
        $this->token = $token;
    }
    
    private function request($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('CURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception($decodedResponse['message'] ?? 'API request failed');
        }
        
        return $decodedResponse;
    }
    
    public function login($credentials)
    {
        $response = $this->request('POST', '/auth/login', $credentials);
        
        if (isset($response['data']['token'])) {
            $this->setToken($response['data']['token']);
        }
        
        return $response;
    }
    
    public function logout()
    {
        $response = $this->request('POST', '/auth/logout');
        $this->token = null;
        return $response;
    }
    
    public function getDashboard()
    {
        return $this->request('GET', '/dashboards/nonparamedis/');
    }
    
    public function checkIn($location)
    {
        return $this->request('POST', '/dashboards/nonparamedis/attendance/checkin', $location);
    }
    
    public function checkOut($location)
    {
        return $this->request('POST', '/dashboards/nonparamedis/attendance/checkout', $location);
    }
    
    public function getSchedule($month = null, $year = null)
    {
        $params = [];
        if ($month) $params['month'] = $month;
        if ($year) $params['year'] = $year;
        
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', '/dashboards/nonparamedis/schedule' . $query);
    }
    
    public function getReports($period = 'month', $date = null)
    {
        $params = ['period' => $period];
        if ($date) $params['date'] = $date;
        
        return $this->request('GET', '/dashboards/nonparamedis/reports?' . http_build_query($params));
    }
}
```

### 2. Usage Examples

```php
<?php

try {
    $api = new DokterkuAPI();
    
    // Login
    $loginData = [
        'login' => 'sari.lestari@dokterku.com',
        'password' => 'password123',
        'device_name' => 'PHP Application',
        'device_fingerprint' => uniqid('php_', true)
    ];
    
    $loginResponse = $api->login($loginData);
    echo "Login successful: " . $loginResponse['data']['user']['name'] . PHP_EOL;
    
    // Get dashboard
    $dashboard = $api->getDashboard();
    echo "Work days this month: " . $dashboard['data']['stats']['work_days_this_month'] . PHP_EOL;
    echo "Attendance rate: " . $dashboard['data']['stats']['attendance_rate'] . "%" . PHP_EOL;
    
    // Check in (example coordinates)
    $checkInData = [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'accuracy' => 10.5,
        'work_location_id' => 1
    ];
    
    $checkInResponse = $api->checkIn($checkInData);
    echo "Check-in time: " . $checkInResponse['data']['check_in_time'] . PHP_EOL;
    
    // Get current month schedule
    $schedule = $api->getSchedule(date('n'), date('Y'));
    echo "Total work hours this month: " . $schedule['data']['summary']['total_work_hours'] . PHP_EOL;
    
    // Get monthly reports
    $reports = $api->getReports('month', date('Y-m-d'));
    echo "Attendance rate: " . $reports['data']['summary']['attendance_rate'] . "%" . PHP_EOL;
    echo "Punctuality score: " . $reports['data']['performance_indicators']['punctuality_score'] . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

## Python Examples

### 1. Python API Client

```python
import requests
import json
from typing import Optional, Dict, Any

class DokterkuAPI:
    def __init__(self, base_url: str = "https://dokterku.com/api/v2"):
        self.base_url = base_url
        self.token = None
        self.session = requests.Session()
        
    def set_token(self, token: str):
        self.token = token
        self.session.headers.update({'Authorization': f'Bearer {token}'})
        
    def _request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Dict[str, Any]:
        url = f"{self.base_url}{endpoint}"
        
        headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        
        try:
            response = self.session.request(
                method=method,
                url=url,
                json=data,
                headers=headers,
                timeout=30
            )
            
            response.raise_for_status()
            return response.json()
            
        except requests.exceptions.RequestException as e:
            if response.status_code >= 400:
                error_data = response.json()
                raise Exception(error_data.get('message', 'API request failed'))
            raise Exception(f"Request failed: {str(e)}")
    
    def login(self, credentials: Dict[str, str]) -> Dict[str, Any]:
        response = self._request('POST', '/auth/login', credentials)
        
        if 'token' in response['data']:
            self.set_token(response['data']['token'])
            
        return response
    
    def logout(self) -> Dict[str, Any]:
        response = self._request('POST', '/auth/logout')
        self.token = None
        self.session.headers.pop('Authorization', None)
        return response
    
    def get_dashboard(self) -> Dict[str, Any]:
        return self._request('GET', '/dashboards/nonparamedis/')
    
    def check_in(self, location: Dict[str, float]) -> Dict[str, Any]:
        return self._request('POST', '/dashboards/nonparamedis/attendance/checkin', location)
    
    def check_out(self, location: Dict[str, float]) -> Dict[str, Any]:
        return self._request('POST', '/dashboards/nonparamedis/attendance/checkout', location)
    
    def get_schedule(self, month: Optional[int] = None, year: Optional[int] = None) -> Dict[str, Any]:
        params = {}
        if month:
            params['month'] = month
        if year:
            params['year'] = year
            
        query = '?' + '&'.join([f"{k}={v}" for k, v in params.items()]) if params else ''
        return self._request('GET', f'/dashboards/nonparamedis/schedule{query}')
    
    def get_reports(self, period: str = 'month', date: Optional[str] = None) -> Dict[str, Any]:
        params = {'period': period}
        if date:
            params['date'] = date
            
        query = '?' + '&'.join([f"{k}={v}" for k, v in params.items()])
        return self._request('GET', f'/dashboards/nonparamedis/reports{query}')
```

### 2. Usage Examples

```python
import datetime

def main():
    api = DokterkuAPI()
    
    try:
        # Login
        login_data = {
            'login': 'sari.lestari@dokterku.com',
            'password': 'password123',
            'device_name': 'Python Application',
            'device_fingerprint': 'python-app-12345'
        }
        
        login_response = api.login(login_data)
        print(f"Login successful: {login_response['data']['user']['name']}")
        
        # Get dashboard
        dashboard = api.get_dashboard()
        stats = dashboard['data']['stats']
        print(f"Work days this month: {stats['work_days_this_month']}")
        print(f"Attendance rate: {stats['attendance_rate']}%")
        print(f"Current status: {dashboard['data']['current_status']}")
        
        # Check attendance status
        status = api._request('GET', '/dashboards/nonparamedis/attendance/status')
        print(f"Can check in: {status['data']['can_check_in']}")
        print(f"Can check out: {status['data']['can_check_out']}")
        
        # Example check-in (if allowed)
        if status['data']['can_check_in']:
            check_in_data = {
                'latitude': -6.2088,
                'longitude': 106.8456,
                'accuracy': 10.5,
                'work_location_id': 1
            }
            
            check_in_response = api.check_in(check_in_data)
            print(f"Check-in successful at: {check_in_response['data']['check_in_time']}")
        
        # Get schedule for current month
        now = datetime.datetime.now()
        schedule = api.get_schedule(now.month, now.year)
        print(f"Total work hours this month: {schedule['data']['summary']['total_work_hours']}")
        
        # Get monthly reports
        reports = api.get_reports('month', now.strftime('%Y-%m-%d'))
        summary = reports['data']['summary']
        performance = reports['data']['performance_indicators']
        
        print(f"Attendance rate: {summary['attendance_rate']}%")
        print(f"Punctuality score: {performance['punctuality_score']}")
        print(f"Consistency score: {performance['consistency_score']}")
        print(f"Location compliance: {performance['location_compliance']}%")
        
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    main()
```

## Mobile App Integration

### React Native Example

```javascript
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';

class DokterkuMobileAPI {
  constructor() {
    this.baseUrl = 'https://dokterku.com/api/v2';
    this.token = null;
  }

  async init() {
    this.token = await AsyncStorage.getItem('auth_token');
  }

  async login(credentials) {
    const response = await this.request('POST', '/auth/login', {
      ...credentials,
      device_name: 'Mobile App',
      device_fingerprint: await this.getDeviceFingerprint()
    });

    if (response.data.token) {
      await AsyncStorage.setItem('auth_token', response.data.token);
      this.token = response.data.token;
    }

    return response;
  }

  async getDeviceFingerprint() {
    // Generate unique device identifier
    let fingerprint = await AsyncStorage.getItem('device_fingerprint');
    if (!fingerprint) {
      fingerprint = `mobile_${Date.now()}_${Math.random().toString(36)}`;
      await AsyncStorage.setItem('device_fingerprint', fingerprint);
    }
    return fingerprint;
  }

  async getCurrentLocation() {
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status !== 'granted') {
      throw new Error('Location permission denied');
    }

    const location = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.High,
      timeout: 10000,
      maximumAge: 60000
    });

    return {
      latitude: location.coords.latitude,
      longitude: location.coords.longitude,
      accuracy: location.coords.accuracy
    };
  }

  async checkInWithCurrentLocation() {
    try {
      const location = await this.getCurrentLocation();
      return await this.request('POST', '/dashboards/nonparamedis/attendance/checkin', location);
    } catch (error) {
      throw new Error(`Check-in failed: ${error.message}`);
    }
  }

  async checkOutWithCurrentLocation() {
    try {
      const location = await this.getCurrentLocation();
      return await this.request('POST', '/dashboards/nonparamedis/attendance/checkout', location);
    } catch (error) {
      throw new Error(`Check-out failed: ${error.message}`);
    }
  }

  async request(method, endpoint, data = null) {
    const config = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(this.token && { 'Authorization': `Bearer ${this.token}` })
      }
    };

    if (data) {
      config.body = JSON.stringify(data);
    }

    const response = await fetch(`${this.baseUrl}${endpoint}`, config);
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'API request failed');
    }

    return await response.json();
  }
}
```

## Error Handling Patterns

### Comprehensive Error Handler

```javascript
class APIErrorHandler {
  static handle(error, context = '') {
    console.error(`API Error in ${context}:`, error);

    // Parse error response
    let message = 'An unexpected error occurred';
    let details = null;

    if (error.response) {
      // HTTP error response
      const data = error.response.data;
      message = data.message || message;
      details = data.errors || null;

      switch (error.response.status) {
        case 401:
          this.handleUnauthorized();
          break;
        case 403:
          this.handleForbidden();
          break;
        case 422:
          this.handleValidationError(details);
          break;
        case 429:
          this.handleRateLimit();
          break;
        case 500:
          this.handleServerError();
          break;
      }
    }

    return {
      message,
      details,
      code: error.response?.status || 0,
      context
    };
  }

  static handleUnauthorized() {
    // Clear stored token
    localStorage.removeItem('auth_token');
    
    // Redirect to login
    window.location.href = '/login';
  }

  static handleForbidden() {
    alert('You do not have permission to perform this action.');
  }

  static handleValidationError(errors) {
    if (errors) {
      const messages = Object.values(errors).flat();
      alert('Validation Error:\n' + messages.join('\n'));
    }
  }

  static handleRateLimit() {
    alert('Too many requests. Please wait a moment before trying again.');
  }

  static handleServerError() {
    alert('Server error occurred. Please try again later.');
  }
}
```

## Rate Limiting Guidelines

### Best Practices

1. **Implement Exponential Backoff**
```javascript
async function requestWithBackoff(apiFunction, maxRetries = 3) {
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      return await apiFunction();
    } catch (error) {
      if (error.response?.status === 429 && attempt < maxRetries - 1) {
        const delay = Math.pow(2, attempt) * 1000; // 1s, 2s, 4s
        await new Promise(resolve => setTimeout(resolve, delay));
        continue;
      }
      throw error;
    }
  }
}

// Usage
const dashboard = await requestWithBackoff(() => api.getDashboard());
```

2. **Rate Limit Tracking**
```javascript
class RateLimitTracker {
  constructor() {
    this.limits = {
      attendance: { max: 5, window: 60000, requests: [] },
      general: { max: 60, window: 60000, requests: [] }
    };
  }

  canMakeRequest(type = 'general') {
    const limit = this.limits[type];
    const now = Date.now();
    
    // Remove old requests outside the window
    limit.requests = limit.requests.filter(time => now - time < limit.window);
    
    return limit.requests.length < limit.max;
  }

  recordRequest(type = 'general') {
    this.limits[type].requests.push(Date.now());
  }

  getResetTime(type = 'general') {
    const limit = this.limits[type];
    if (limit.requests.length === 0) return 0;
    
    const oldestRequest = Math.min(...limit.requests);
    return oldestRequest + limit.window;
  }
}
```

## Security Considerations

### Token Management
```javascript
class SecureTokenManager {
  static setToken(token) {
    // Store token securely
    if (typeof window !== 'undefined') {
      // Browser environment
      sessionStorage.setItem('auth_token', token);
    } else {
      // Node.js environment
      process.env.AUTH_TOKEN = token;
    }
  }

  static getToken() {
    if (typeof window !== 'undefined') {
      return sessionStorage.getItem('auth_token');
    } else {
      return process.env.AUTH_TOKEN;
    }
  }

  static clearToken() {
    if (typeof window !== 'undefined') {
      sessionStorage.removeItem('auth_token');
      localStorage.removeItem('auth_token');
    } else {
      delete process.env.AUTH_TOKEN;
    }
  }

  static isTokenExpired(token) {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp * 1000 < Date.now();
    } catch {
      return true;
    }
  }
}
```

---

*Last updated: 2025-07-15 | API Version: 2.0 | Dokterku Medical Clinic Management System*