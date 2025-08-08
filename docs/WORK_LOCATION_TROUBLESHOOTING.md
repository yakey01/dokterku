# Work Location Troubleshooting Guide

## Problem
Dr. Yaya sudah mendapat penugasan work location, tetapi di Presensi.tsx masih menampilkan "❌ Work location belum ditugaskan".

## Analysis

### 1. Frontend Logic Issues
- **Hardcoded Validation**: Sebelumnya `hasWorkLocation` di-hardcode menjadi `true`
- **API Response Format**: Frontend mengharapkan format data yang berbeda dari API
- **Data Structure Mismatch**: API mengembalikan `data.work_location` tetapi frontend menggunakan `data` langsung

### 2. API Response Structure
API `/api/v2/dashboards/dokter/work-location/status` mengembalikan:
```json
{
  "success": true,
  "message": "Work location status retrieved",
  "data": {
    "work_location": {
      "id": 1,
      "name": "RS. Kediri Medical Center",
      "address": "Jl. Ahmad Yani No. 123",
      "coordinates": {
        "latitude": -7.8167,
        "longitude": 112.0167
      },
      "radius_meters": 100,
      "is_active": true
    },
    "user_id": 123,
    "timestamp": "2025-01-20T10:30:00.000Z"
  }
}
```

### 3. Frontend Expected Structure
Frontend mengharapkan:
```typescript
scheduleData.workLocation = {
  id: number,
  name: string,
  address: string,
  // ... other properties
}
```

## Fixes Applied

### 1. Fixed API Response Parsing
```typescript
// Before
workLocation: workLocationData.data || null

// After  
workLocation: workLocationData.data?.work_location || null
```

### 2. Fixed Validation Logic
```typescript
// Before
const hasWorkLocation = true; // Hardcoded

// After
const hasWorkLocation = scheduleData.workLocation && scheduleData.workLocation.id;
```

### 3. Added Debugging
```typescript
console.log('Work Location API Response:', workLocationData);
console.log('Work Location Data:', scheduleData.workLocation);
console.log('Has Work Location:', hasWorkLocation);
```

## Testing Steps

### 1. Check API Response
```bash
curl -H "Authorization: Bearer {token}" \
     -H "X-CSRF-TOKEN: {csrf_token}" \
     http://localhost:8000/api/v2/dashboards/dokter/work-location/status
```

### 2. Check Browser Console
- Open browser developer tools
- Go to Console tab
- Look for "Work Location API Response" logs
- Verify data structure matches expected format

### 3. Check Database
```sql
-- Check if user has work location assigned
SELECT u.id, u.name, u.work_location_id, wl.name as work_location_name
FROM users u
LEFT JOIN work_locations wl ON u.work_location_id = wl.id
WHERE u.name LIKE '%Yaya%';

-- Check work locations table
SELECT * FROM work_locations WHERE is_active = 1;
```

### 4. Check User Model Relationship
```php
// In User model
public function workLocation()
{
    return $this->belongsTo(WorkLocation::class, 'work_location_id');
}
```

## Common Issues

### 1. User Not Assigned to Work Location
```sql
-- Check if user has work_location_id
SELECT id, name, work_location_id FROM users WHERE name LIKE '%Yaya%';
```

### 2. Work Location Inactive
```sql
-- Check if work location is active
SELECT * FROM work_locations WHERE id = {work_location_id};
```

### 3. API Authentication Issues
- Check if token is valid
- Check if CSRF token is present
- Check if user is authenticated

### 4. Frontend State Issues
- Check if `scheduleData.workLocation` is properly set
- Check if `validateCurrentStatus` is called after data loads
- Check if `useEffect` dependencies are correct

## Debugging Commands

### 1. Test API Endpoint
```bash
# Test work location status
curl -X GET "http://localhost:8000/api/v2/dashboards/dokter/work-location/status" \
  -H "Authorization: Bearer {your_token}" \
  -H "X-CSRF-TOKEN: {your_csrf_token}" \
  -H "Content-Type: application/json"
```

### 2. Check User Data
```bash
# Test user data endpoint
curl -X GET "http://localhost:8000/api/v2/dashboards/dokter/" \
  -H "Authorization: Bearer {your_token}" \
  -H "X-CSRF-TOKEN: {your_csrf_token}" \
  -H "Content-Type: application/json"
```

### 3. Database Queries
```sql
-- Check user work location assignment
SELECT 
    u.id,
    u.name,
    u.work_location_id,
    wl.name as work_location_name,
    wl.is_active
FROM users u
LEFT JOIN work_locations wl ON u.work_location_id = wl.id
WHERE u.name LIKE '%Yaya%';

-- Check all active work locations
SELECT * FROM work_locations WHERE is_active = 1;
```

## Expected Behavior After Fix

1. **API Response**: Should return work location data in correct format
2. **Frontend State**: `scheduleData.workLocation` should contain work location object
3. **Validation**: `hasWorkLocation` should be `true` if work location exists
4. **UI Display**: Should show work location name and address instead of error message
5. **Check-in/out**: Should be enabled if all other conditions are met

## Next Steps

1. **Test with Dr. Yaya**: Login as Dr. Yaya and verify work location appears
2. **Test with Other Users**: Login with users who have/don't have work location
3. **Monitor Console**: Check for any error messages or unexpected data
4. **Verify Database**: Ensure work location assignments are correct
5. **Test Edge Cases**: Test with inactive work locations, missing data, etc.

## Monitoring

- Monitor browser console for debugging logs
- Monitor API responses for correct data structure
- Monitor database for work location assignments
- Monitor UI for correct display of work location status
