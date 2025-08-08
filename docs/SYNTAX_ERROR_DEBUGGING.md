# Syntax Error Pattern Debugging Guide

## Problem Analysis

Error yang persisten:
```
[Error] Error loading user data: – SyntaxError: The string did not match the expected pattern.
[Error] Error loading schedule and work location: – SyntaxError: The string did not match the expected pattern.
```

## Root Cause Investigation

### 1. **URL Pattern Matching Issue**
Error "The string did not match the expected pattern" kemungkinan besar disebabkan oleh:
- **Invalid URL construction** dengan `new URL()`
- **Regex pattern matching** dalam token sanitization
- **Browser URL validation** yang ketat

### 2. **Token Sanitization Problem**
Regex pattern `/[^a-zA-Z0-9\-_\.]/g` mungkin menyebabkan masalah dengan:
- **Special characters** dalam token
- **Unicode characters** yang tidak terduga
- **Empty string** setelah sanitization

### 3. **Fetch API Configuration**
Masalah dengan:
- **Headers format** yang tidak sesuai
- **Credentials configuration** yang salah
- **CORS policy** violations

## Debugging Implementation

### 1. **Enhanced Console Logging**
```typescript
console.log('🔍 Starting user data load...');
console.log('🔍 Token from localStorage:', token ? 'Found' : 'Not found');
console.log('🔍 Token from meta tag:', token ? 'Found' : 'Not found');
console.log('🔍 Making API request to /api/v2/dashboards/dokter/');
console.log('🔍 Response status:', response.status);
console.log('🔍 Response ok:', response.ok);
console.log('🔍 Response data:', data);
console.log('🔍 Setting user data:', data.data.user);
```

### 2. **Error Details Logging**
```typescript
console.error('Error details:', {
  name: error.name,
  message: error.message,
  stack: error.stack
});
```

### 3. **Fallback Data Strategy**
```typescript
// Different fallback data for different scenarios
setUserData({
  name: 'Guest User',      // No token
  email: 'guest@example.com',
  role: 'guest'
});

setUserData({
  name: 'API User',        // API response issue
  email: 'api@example.com',
  role: 'api_user'
});

setUserData({
  name: 'Error User',      // HTTP error
  email: 'error@example.com',
  role: 'error_user'
});

setUserData({
  name: 'Fallback User',   // Exception caught
  email: 'fallback@example.com',
  role: 'fallback'
});
```

## Testing Steps

### 1. **Browser Console Monitoring**
1. Open browser developer tools (F12)
2. Go to Console tab
3. Look for 🔍 debugging logs
4. Monitor error details

### 2. **Network Tab Analysis**
1. Go to Network tab
2. Filter by "Fetch/XHR"
3. Check API requests to `/api/v2/dashboards/dokter/`
4. Verify request headers and response

### 3. **Token Validation**
1. Check localStorage for `auth_token`
2. Check meta tag for CSRF token
3. Verify token format and content

### 4. **API Endpoint Testing**
```bash
# Test with curl
curl -H "Authorization: Bearer {token}" \
     -H "X-CSRF-TOKEN: {csrf_token}" \
     -H "Accept: application/json" \
     http://localhost:8000/api/v2/dashboards/dokter/
```

## Expected Debug Output

### **Successful Flow**
```
🔍 Starting user data load...
🔍 Token from localStorage: Found
🔍 Making API request to /api/v2/dashboards/dokter/
🔍 Response status: 200
🔍 Response ok: true
🔍 Response data: {success: true, data: {user: {...}}}
🔍 Setting user data: {name: "Dr. Yaya", email: "yaya@example.com", role: "dokter"}
```

### **Token Not Found**
```
🔍 Starting user data load...
🔍 Token from localStorage: Not found
🔍 Token from meta tag: Found
🔍 Making API request to /api/v2/dashboards/dokter/
🔍 Response status: 200
🔍 Response ok: true
🔍 Setting user data: {name: "Dr. Yaya", email: "yaya@example.com", role: "dokter"}
```

### **No Token Available**
```
🔍 Starting user data load...
🔍 Token from localStorage: Not found
🔍 Token from meta tag: Not found
No authentication token found
// Sets Guest User data
```

### **API Error**
```
🔍 Starting user data load...
🔍 Token from localStorage: Found
🔍 Making API request to /api/v2/dashboards/dokter/
🔍 Response status: 401
🔍 Response ok: false
Failed to load user data: 401 Unauthorized
// Sets Error User data
```

### **Exception Caught**
```
🔍 Starting user data load...
🔍 Token from localStorage: Found
🔍 Making API request to /api/v2/dashboards/dokter/
Error loading user data: SyntaxError: The string did not match the expected pattern.
Error details: {
  name: "SyntaxError",
  message: "The string did not match the expected pattern.",
  stack: "..."
}
// Sets Fallback User data
```

## Troubleshooting Guide

### 1. **If Token Not Found**
- Check if user is logged in
- Verify localStorage is accessible
- Check if CSRF meta tag exists in HTML

### 2. **If API Returns 401/403**
- Verify token is valid
- Check if token has expired
- Ensure proper authentication headers

### 3. **If SyntaxError Persists**
- Check browser console for detailed error
- Verify API endpoint is correct
- Test API with curl or Postman
- Check for CORS issues

### 4. **If GPS Errors Continue**
- Check browser permissions for location
- Verify HTTPS is used (required for GPS)
- Test on different browser/device

## Next Steps

1. **Monitor Console Logs**: Check browser console for 🔍 debugging output
2. **Identify Error Pattern**: Look for specific error details in console
3. **Test API Endpoints**: Verify API endpoints work with curl/Postman
4. **Check Authentication**: Ensure proper token handling
5. **Verify CORS**: Check for cross-origin issues

## Expected Results

With enhanced debugging, we should be able to:
- **Identify exact error location** in the code
- **Understand token handling issues**
- **See API response details**
- **Track error propagation**
- **Provide better user feedback**

The debugging logs will help pinpoint whether the issue is:
- Token retrieval
- API request construction
- Response parsing
- Data processing
- State management
