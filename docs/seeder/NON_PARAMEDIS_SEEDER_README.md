# NonParamedis Attendance Seeder Documentation

## Overview

The `NonParamedisAttendanceSeeder` creates realistic test data for the non-medical administrative staff attendance system in the Dokterku Medical Clinic Management platform. This seeder generates comprehensive attendance records with realistic work patterns, GPS data, and approval workflows.

## Table of Contents
1. [Seeder Features](#seeder-features)
2. [User Profiles Created](#user-profiles-created)
3. [Data Patterns Generated](#data-patterns-generated)
4. [Usage Instructions](#usage-instructions)
5. [Generated Data Structure](#generated-data-structure)
6. [Customization Options](#customization-options)
7. [Troubleshooting](#troubleshooting)

## Seeder Features

### Core Functionality
- Creates 3 distinct non-paramedis users with unique work characteristics
- Generates 7 days of historical attendance data per user
- Implements realistic work patterns and behaviors
- Creates proper GPS coordinates within work location geofence
- Generates device information and metadata
- Implements approval workflow with realistic approval rates

### Realistic Data Patterns
- **Variable Work Times**: Different check-in/check-out patterns per user
- **GPS Coordinates**: Realistic coordinates within clinic boundaries
- **Work Duration**: Proper calculation of work hours and minutes
- **Approval Status**: 80% approved, 20% pending approval distribution
- **Device Information**: Realistic mobile device metadata
- **GPS Metadata**: Comprehensive location validation data

## User Profiles Created

### 1. Sari Lestari (Punctual Worker)
```php
User Details:
- Name: "Sari Lestari"
- Email: "sari.lestari@dokterku.com"
- Username: "sari.lestari"
- NIP: "NP001"
- Phone: "081234567890"
- Role: non_paramedis
- Join Date: 6 months ago

Work Pattern:
- Always punctual (check-in: 7:55-8:05 AM)
- Consistent work hours (8-9 hours daily)
- Regular check-out times (5:00-5:15 PM)
- Works weekdays only
- High approval rate
- No late arrivals
```

### 2. Budi Santoso (Occasionally Late Worker)
```php
User Details:
- Name: "Budi Santoso"
- Email: "budi.santoso@dokterku.com"
- Username: "budi.santoso"
- NIP: "NP002"
- Phone: "081234567891"
- Role: non_paramedis
- Join Date: 4 months ago

Work Pattern:
- Occasionally late (30% chance of 8:10-8:30 AM arrival)
- Compensates with overtime when late
- Variable work duration (8-10 hours)
- Works weekdays only
- Includes reason notes for late arrivals
- Good approval rate despite tardiness
```

### 3. Dewi Kusuma (Variable Schedule Worker)
```php
User Details:
- Name: "Dewi Kusuma"
- Email: "dewi.kusuma@dokterku.com"
- Username: "dewi.kusuma"
- NIP: "NP003"
- Phone: "081234567892"
- Role: non_paramedis
- Join Date: 8 months ago

Work Pattern:
- Variable work schedule (early bird to late starter)
- Sometimes works weekends (20% chance)
- Flexible work hours (7-10 hours daily)
- Multiple schedule patterns
- Weekend work includes special notes
- Mixed approval status due to irregular hours
```

## Data Patterns Generated

### GPS Coordinate Generation
```php
// Generates realistic coordinates within work location geofence
private function generateLocationWithinGeofence(WorkLocation $workLocation): array
{
    $radiusInDegrees = $workLocation->radius_meters / 111320; // Conversion to degrees
    $randomRadius = $radiusInDegrees * sqrt(rand(0, 100) / 100); // Random point within circle
    $randomAngle = rand(0, 360) * pi() / 180;

    return [
        'lat' => $workLocation->latitude + ($randomRadius * cos($randomAngle)),
        'lng' => $workLocation->longitude + ($randomRadius * sin($randomAngle))
    ];
}
```

### Work Pattern Examples

#### Punctual Pattern (Sari)
```php
Work Schedule:
- Check-in: 7:55 AM - 8:05 AM
- Check-out: 5:00 PM - 5:15 PM
- Work Duration: 8h 55m - 9h 20m
- Weekends: Off
- Notes: None (consistent performance)
```

#### Late Pattern (Budi)
```php
Normal Days (70%):
- Check-in: 7:55 AM - 7:59 AM
- Check-out: 5:00 PM - 5:10 PM
- Work Duration: 8h 1m - 9h 15m

Late Days (30%):
- Check-in: 8:10 AM - 8:30 AM
- Check-out: 5:20 PM - 6:05 PM (compensation)
- Work Duration: 8h 35m - 9h 55m
- Notes: "Terlambat karena kemacetan"
```

#### Variable Pattern (Dewi)
```php
Pattern Options:
1. Early Bird: 7:45 AM - 4:30 PM
2. Normal: 8:00 AM - 5:00 PM
3. Late Start: 8:15 AM - 5:30 PM
4. Flexible: 9:00 AM - 6:30 PM

Weekend Work (20% chance):
- Notes: "Kerja weekend untuk project khusus"
- Extended hours for project completion
```

### Device Information Generated
```json
{
  "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15",
  "ip_address": "192.168.1.100-203.255.254.254",
  "platform": "mobile_web"
}
```

### GPS Metadata Structure
```json
{
  "accuracy": 5-20,
  "provider": "gps",
  "timestamp": "2025-07-15T08:15:30.000000Z",
  "speed": 0,
  "bearing": 0-360,
  "validation_result": {
    "is_valid": true,
    "distance": 10-80,
    "max_distance": 100
  }
}
```

## Usage Instructions

### 1. Prerequisites
```bash
# Ensure required seeders are run first
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=WorkLocationSeeder
```

### 2. Run the Seeder
```bash
# Run NonParamedis seeder only
php artisan db:seed --class=NonParamedisAttendanceSeeder

# Or run as part of full database seeding
php artisan db:seed
```

### 3. Verify Seeded Data
```sql
-- Check created users
SELECT id, name, email, nip FROM users WHERE id IN (
    SELECT user_id FROM non_paramedis_attendances
);

-- Check attendance records
SELECT 
    u.name,
    npa.attendance_date,
    npa.check_in_time,
    npa.check_out_time,
    npa.total_work_minutes,
    npa.status,
    npa.approval_status
FROM non_paramedis_attendances npa
JOIN users u ON npa.user_id = u.id
ORDER BY npa.attendance_date DESC, u.name;

-- Check work patterns
SELECT 
    u.name,
    COUNT(*) as total_days,
    AVG(npa.total_work_minutes) as avg_work_minutes,
    COUNT(CASE WHEN npa.approval_status = 'approved' THEN 1 END) as approved_days
FROM non_paramedis_attendances npa
JOIN users u ON npa.user_id = u.id
GROUP BY u.id, u.name;
```

### 4. Test API with Seeded Data
```bash
# Login as seeded user
curl -X POST "http://localhost:8000/api/v2/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "sari.lestari@dokterku.com",
    "password": "password",
    "device_name": "Test Device"
  }'

# Use returned token to test dashboard
curl -X GET "http://localhost:8000/api/v2/dashboards/nonparamedis/" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Generated Data Structure

### Sample Attendance Record
```sql
INSERT INTO non_paramedis_attendances VALUES (
    1,                              -- id
    1,                              -- user_id (Sari Lestari)
    1,                              -- work_location_id
    '2025-07-08 08:05:00',         -- check_in_time
    -6.20876543,                    -- check_in_latitude
    106.84567890,                   -- check_in_longitude
    15.20,                          -- check_in_accuracy
    NULL,                           -- check_in_address
    47.50,                          -- check_in_distance
    1,                              -- check_in_valid_location
    '2025-07-08 17:10:00',         -- check_out_time
    -6.20881234,                    -- check_out_latitude
    106.84562345,                   -- check_out_longitude
    12.80,                          -- check_out_accuracy
    NULL,                           -- check_out_address
    39.20,                          -- check_out_distance
    1,                              -- check_out_valid_location
    545,                            -- total_work_minutes (9h 5m)
    '2025-07-08',                   -- attendance_date
    'checked_out',                  -- status
    NULL,                           -- notes
    '{"user_agent":"Mozilla/5.0...","ip_address":"192.168.1.150","platform":"mobile_web"}',
    NULL,                           -- browser_info
    '192.168.1.150',               -- ip_address
    '{"accuracy":15.2,"provider":"gps","timestamp":"2025-07-08T08:05:00.000000Z","speed":0,"bearing":127}',
    0,                              -- suspected_spoofing
    'approved',                     -- approval_status
    1,                              -- approved_by (admin user)
    '2025-07-08 19:30:00',         -- approved_at
    'Presensi sesuai jadwal kerja', -- approval_notes
    '2025-07-08 08:05:00',         -- created_at
    '2025-07-08 19:30:00'          -- updated_at
);
```

### Data Distribution

#### By User
- **Sari Lestari**: 7 consistent records, all approved
- **Budi Santoso**: 7 records with 2 late entries, 6 approved
- **Dewi Kusuma**: 8 records (includes weekend work), 5 approved

#### By Status
- **checked_out**: 22 records (complete work days)
- **approval_status**: 18 approved, 4 pending

#### By Work Duration
- **Minimum**: 480 minutes (8 hours)
- **Maximum**: 630 minutes (10.5 hours)
- **Average**: 525 minutes (8.75 hours)

## Customization Options

### 1. Modify User Count
```php
// In createNonParamedisUsers() method
// Change the number of users created
private function createNonParamedisUsers(int $roleId): array
{
    $users = [];

    // Add more users here
    $users[] = User::create([...]);  // User 4
    $users[] = User::create([...]);  // User 5
    
    return $users;
}
```

### 2. Adjust Time Periods
```php
// In createUserAttendancePattern() method
// Change the number of days generated
for ($i = 14; $i >= 1; $i--) {  // Changed from 7 to 14 days
    $date = Carbon::now()->subDays($i);
    // ...
}
```

### 3. Modify Work Patterns
```php
// Add new work pattern
private function getFlexiblePattern(Carbon $date): array
{
    $patterns = [
        ['in' => [6, 30], 'out' => [15, 30]], // Early shift
        ['in' => [10, 0], 'out' => [19, 0]],  // Late shift
        ['in' => [8, 0], 'out' => [17, 0]],   // Normal shift
    ];
    
    // Implementation here...
}
```

### 4. Customize Approval Rates
```php
// In createDailyAttendance() method
// Adjust approval probability
'approval_status' => rand(1, 10) <= 9 ? 'approved' : 'pending', // 90% approved
'approved_by' => rand(1, 10) <= 9 ? 1 : null,
```

### 5. Add Custom Notes
```php
// Add more note variations
private function getRandomApprovalNote(): string
{
    $notes = [
        'Presensi sesuai jadwal kerja',
        'Lokasi valid, waktu kerja memadai',
        'Disetujui setelah verifikasi',
        'Kinerja baik, presensi tepat waktu',
        'Approved - Normal working hours',
        'Good attendance record',           // Added
        'Compensated for late arrival',     // Added
        'Overtime approved for project'     // Added
    ];

    return $notes[array_rand($notes)];
}
```

## Troubleshooting

### Common Issues

#### 1. Role Not Found Error
```
Error: Non-paramedis role not found. Please run RoleSeeder first.
```

**Solution**:
```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=NonParamedisAttendanceSeeder
```

#### 2. Work Location Not Found Error
```
Error: No active work locations found. Please run WorkLocationSeeder first.
```

**Solution**:
```bash
php artisan db:seed --class=WorkLocationSeeder
php artisan db:seed --class=NonParamedisAttendanceSeeder
```

#### 3. Duplicate Entry Error
```
Error: Duplicate entry for key 'users_email_unique'
```

**Solution**:
```bash
# Clear existing data
php artisan migrate:refresh
php artisan db:seed
```

#### 4. GPS Coordinates Outside Valid Range
**Check**: Ensure work locations have valid latitude/longitude values
```sql
SELECT id, name, latitude, longitude FROM work_locations WHERE is_active = 1;
```

#### 5. JSON Field Errors (MySQL < 5.7)
**Solution**: Upgrade to MySQL 8.0+ or modify seeder to use text fields

### Verification Queries

#### Check User Creation
```sql
SELECT u.*, r.name as role_name 
FROM users u 
JOIN roles r ON u.role_id = r.id 
WHERE r.name = 'non_paramedis';
```

#### Verify Attendance Data
```sql
SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT user_id) as unique_users,
    MIN(attendance_date) as earliest_date,
    MAX(attendance_date) as latest_date,
    AVG(total_work_minutes) as avg_work_minutes
FROM non_paramedis_attendances;
```

#### Check Data Quality
```sql
-- Verify GPS coordinates are within valid ranges
SELECT COUNT(*) as invalid_coords
FROM non_paramedis_attendances 
WHERE check_in_latitude NOT BETWEEN -90 AND 90 
   OR check_in_longitude NOT BETWEEN -180 AND 180;

-- Check for incomplete records
SELECT COUNT(*) as incomplete_records
FROM non_paramedis_attendances 
WHERE check_in_time IS NOT NULL 
  AND check_out_time IS NULL;
```

### Performance Considerations

#### Large Dataset Generation
For generating larger datasets:

```php
// Batch insert for better performance
DB::transaction(function () {
    $batchSize = 100;
    $attendances = [];
    
    foreach ($users as $user) {
        for ($i = 30; $i >= 1; $i--) {
            $attendances[] = [/* attendance data */];
            
            if (count($attendances) >= $batchSize) {
                NonParamedisAttendance::insert($attendances);
                $attendances = [];
            }
        }
    }
    
    if (!empty($attendances)) {
        NonParamedisAttendance::insert($attendances);
    }
});
```

---

## Summary

The NonParamedisAttendanceSeeder provides a comprehensive foundation for testing and development of the non-medical staff attendance system. It creates realistic data patterns that mirror actual workplace scenarios, making it ideal for:

- **API Testing**: Complete test data for all endpoints
- **UI Development**: Realistic data for dashboard and reporting interfaces
- **Performance Testing**: Sufficient data volume for optimization testing
- **User Training**: Realistic scenarios for training purposes
- **Demo Purposes**: Professional-looking data for presentations

The seeder is designed to be easily customizable and extensible, allowing developers to modify patterns, add new user types, or adjust data volumes based on specific requirements.

---

*Seeder Documentation v1.0 | Generated on July 15, 2025 | Dokterku Medical Clinic Management System*