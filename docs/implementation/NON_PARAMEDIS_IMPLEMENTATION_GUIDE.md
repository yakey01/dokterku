# NonParamedis Implementation Guide

## Overview

This guide provides comprehensive instructions for implementing, deploying, and maintaining the NonParamedis (Non-Medical Administrative Staff) attendance management system within the Dokterku Medical Clinic Management platform.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Configuration](#configuration)
4. [Database Setup](#database-setup)
5. [API Testing](#api-testing)
6. [Deployment](#deployment)
7. [Monitoring and Maintenance](#monitoring-and-maintenance)
8. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements
- **PHP**: 8.1 or higher
- **Laravel**: 10.x
- **MySQL**: 8.0 or higher
- **Composer**: 2.x
- **Node.js**: 18.x or higher (for asset compilation)
- **Redis**: 6.0 or higher (for caching and sessions)

### Required PHP Extensions
```bash
php -m | grep -E "(gd|curl|mbstring|xml|bcmath|json|openssl|pdo|pdo_mysql|tokenizer|zip)"
```

### Laravel Packages
- `laravel/sanctum`: API authentication
- `spatie/laravel-permission`: Role-based permissions
- `darkaonline/l5-swagger`: API documentation
- `spatie/laravel-activitylog`: Audit logging

## Installation Steps

### 1. Clone and Setup Project

```bash
# Clone repository
git clone https://github.com/your-org/dokterku.git
cd dokterku

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Environment Configuration

Edit `.env` file with your configuration:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dokterku
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,dokterku.local
SESSION_DOMAIN=.dokterku.local

# API Configuration
API_RATE_LIMIT_ATTENDANCE=5
API_RATE_LIMIT_GENERAL=60

# GPS Configuration
GPS_DEFAULT_ACCURACY_THRESHOLD=50
GPS_SPOOFING_DETECTION_ENABLED=true

# Swagger Documentation
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_OPEN_API_SPEC_VERSION=3.0.0
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE dokterku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed database with base data
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=WorkLocationSeeder
php artisan db:seed --class=UserSeeder

# Seed NonParamedis test data
php artisan db:seed --class=NonParamedisAttendanceSeeder
```

### 4. API Documentation Setup

```bash
# Generate Swagger documentation
php artisan l5-swagger:generate

# Publish Swagger assets
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

### 5. Asset Compilation

```bash
# Development build
npm run dev

# Production build
npm run build
```

## Configuration

### 1. Role Configuration

Ensure the `non_paramedis` role exists:

```php
// database/seeders/RoleSeeder.php
Role::create([
    'name' => 'non_paramedis',
    'display_name' => 'Non-Paramedis',
    'description' => 'Non-medical administrative staff',
    'is_active' => true
]);
```

### 2. Middleware Configuration

Register middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... existing middleware
    'enhanced.role' => \App\Http\Middleware\EnhancedRoleMiddleware::class,
    'api.rate.limit' => \App\Http\Middleware\Api\ApiRateLimitMiddleware::class,
    'api.headers' => \App\Http\Middleware\Api\ApiResponseHeadersMiddleware::class,
];
```

### 3. GPS Service Configuration

Configure GPS validation in `config/api.php`:

```php
<?php
return [
    'gps' => [
        'default_accuracy_threshold' => env('GPS_DEFAULT_ACCURACY_THRESHOLD', 50),
        'max_distance_meters' => env('GPS_MAX_DISTANCE_METERS', 100),
        'spoofing_detection' => env('GPS_SPOOFING_DETECTION_ENABLED', true),
        'validation_timeout' => env('GPS_VALIDATION_TIMEOUT', 10),
    ],
    
    'rate_limits' => [
        'attendance' => env('API_RATE_LIMIT_ATTENDANCE', 5),
        'general' => env('API_RATE_LIMIT_GENERAL', 60),
    ],
    
    'response' => [
        'version' => '2.0',
        'include_request_id' => true,
        'include_timestamp' => true,
    ],
];
```

### 4. Work Location Setup

Create work locations using seeder or admin panel:

```php
// database/seeders/WorkLocationSeeder.php
WorkLocation::create([
    'name' => 'Klinik Dokterku Pusat',
    'address' => 'Jl. Kesehatan No. 123, Jakarta',
    'latitude' => -6.2088,
    'longitude' => 106.8456,
    'radius_meters' => 100,
    'location_type' => 'main_office',
    'is_active' => true,
]);
```

## Database Setup

### 1. Run Migrations

```bash
# Run all migrations
php artisan migrate

# Check migration status
php artisan migrate:status

# Rollback if needed (development only)
php artisan migrate:rollback
```

### 2. Verify Table Structure

```sql
-- Check table creation
DESCRIBE non_paramedis_attendances;

-- Verify indexes
SHOW INDEX FROM non_paramedis_attendances;

-- Check foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'dokterku'
  AND TABLE_NAME = 'non_paramedis_attendances';
```

### 3. Seed Test Data

```bash
# Seed specific classes
php artisan db:seed --class=NonParamedisAttendanceSeeder

# Verify seeded data
mysql -u username -p dokterku -e "
SELECT 
    u.name, 
    npa.attendance_date, 
    npa.status, 
    npa.approval_status,
    npa.total_work_minutes
FROM non_paramedis_attendances npa
JOIN users u ON npa.user_id = u.id
ORDER BY npa.attendance_date DESC
LIMIT 10;
"
```

## API Testing

### 1. Basic Connectivity Test

```bash
# Test system health
curl -X GET "http://localhost:8000/api/v2/system/health" \
  -H "Accept: application/json"

# Expected response:
# {
#   "success": true,
#   "message": "API is healthy",
#   "data": {
#     "status": "ok",
#     "version": "2.0",
#     "database": "connected"
#   }
# }
```

### 2. Authentication Test

```bash
# Login test
curl -X POST "http://localhost:8000/api/v2/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "login": "sari.lestari@dokterku.com",
    "password": "password",
    "device_name": "Test Device"
  }'

# Save the token from response for subsequent tests
export API_TOKEN="1|your_token_here"
```

### 3. NonParamedis API Tests

```bash
# Test authentication endpoint
curl -X GET "http://localhost:8000/api/v2/dashboards/nonparamedis/test" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"

# Test dashboard
curl -X GET "http://localhost:8000/api/v2/dashboards/nonparamedis/" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"

# Test attendance status
curl -X GET "http://localhost:8000/api/v2/dashboards/nonparamedis/attendance/status" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"

# Test check-in (with valid coordinates)
curl -X POST "http://localhost:8000/api/v2/dashboards/nonparamedis/attendance/checkin" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 10.5,
    "work_location_id": 1
  }'
```

### 4. Automated Testing Script

Create `tests/api_test.sh`:

```bash
#!/bin/bash

# API Testing Script for NonParamedis Module
BASE_URL="http://localhost:8000/api/v2"
EMAIL="sari.lestari@dokterku.com"
PASSWORD="password"

echo "=== Dokterku NonParamedis API Test Suite ==="

# Test 1: Health Check
echo "1. Testing system health..."
HEALTH_RESPONSE=$(curl -s -X GET "$BASE_URL/system/health" -H "Accept: application/json")
echo "Health Check: $HEALTH_RESPONSE"

# Test 2: Login
echo "2. Testing login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"login\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"device_name\":\"Test Script\"}")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token')
echo "Login Token: $TOKEN"

if [ "$TOKEN" = "null" ]; then
    echo "❌ Login failed!"
    exit 1
fi

# Test 3: Dashboard
echo "3. Testing dashboard..."
DASHBOARD_RESPONSE=$(curl -s -X GET "$BASE_URL/dashboards/nonparamedis/" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
echo "Dashboard Response: $DASHBOARD_RESPONSE"

# Test 4: Attendance Status
echo "4. Testing attendance status..."
STATUS_RESPONSE=$(curl -s -X GET "$BASE_URL/dashboards/nonparamedis/attendance/status" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
echo "Status Response: $STATUS_RESPONSE"

echo "=== Test Suite Complete ==="
```

Make it executable and run:

```bash
chmod +x tests/api_test.sh
./tests/api_test.sh
```

## Deployment

### 1. Production Environment Setup

```bash
# Optimize for production
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate API documentation
php artisan l5-swagger:generate

# Build production assets
npm run build
```

### 2. Web Server Configuration

#### Apache Configuration (.htaccess)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name dokterku.com www.dokterku.com;
    root /var/www/dokterku/public;
    
    index index.php index.html;
    
    # API routes
    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
        
        # CORS headers
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS, PUT, DELETE';
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization';
        
        # Handle preflight OPTIONS requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### 3. SSL Configuration

```bash
# Install SSL certificate (Let's Encrypt example)
sudo certbot --nginx -d dokterku.com -d www.dokterku.com

# Update .env for HTTPS
APP_URL=https://dokterku.com
SANCTUM_STATEFUL_DOMAINS=dokterku.com,www.dokterku.com
```

### 4. Supervisor Configuration (Queue Workers)

Create `/etc/supervisor/conf.d/dokterku-worker.conf`:

```ini
[program:dokterku-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dokterku/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/dokterku/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start dokterku-worker:*
```

## Monitoring and Maintenance

### 1. Log Monitoring

#### Laravel Logs
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Monitor specific NonParamedis activities
grep "NonParamedis" storage/logs/laravel.log

# Monitor API errors
grep "API Error" storage/logs/laravel.log
```

#### Create Log Monitoring Script
```bash
#!/bin/bash
# scripts/monitor_logs.sh

LOG_FILE="/var/www/dokterku/storage/logs/laravel.log"
ALERT_EMAIL="admin@dokterku.com"

# Check for critical errors in last 10 minutes
RECENT_ERRORS=$(grep "ERROR" $LOG_FILE | grep "$(date '+%Y-%m-%d %H:%M' -d '10 minutes ago')")

if [ ! -z "$RECENT_ERRORS" ]; then
    echo "Critical errors detected in NonParamedis API:" | mail -s "API Alert" $ALERT_EMAIL
    echo "$RECENT_ERRORS" | mail -s "Error Details" $ALERT_EMAIL
fi
```

### 2. Performance Monitoring

#### Database Query Monitoring
```sql
-- Monitor slow queries
SELECT 
    ROUND(SUM(timer_wait)/1000000000000,6) as total_latency,
    ROUND(AVG(timer_wait)/1000000000000,6) as avg_latency,
    COUNT_STAR as total_queries,
    digest_text
FROM performance_schema.events_statements_summary_by_digest 
WHERE digest_text LIKE '%non_paramedis_attendances%'
ORDER BY total_latency DESC
LIMIT 10;
```

#### API Response Time Monitoring
```bash
# Create performance test script
#!/bin/bash
# scripts/performance_test.sh

API_BASE="https://dokterku.com/api/v2"
TOKEN="your_production_token"

echo "Testing API performance..."

# Test dashboard endpoint
time curl -s -o /dev/null -w "%{http_code} %{time_total}\n" \
  "$API_BASE/dashboards/nonparamedis/" \
  -H "Authorization: Bearer $TOKEN"

# Test attendance status
time curl -s -o /dev/null -w "%{http_code} %{time_total}\n" \
  "$API_BASE/dashboards/nonparamedis/attendance/status" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Database Maintenance

#### Daily Maintenance Script
```bash
#!/bin/bash
# scripts/daily_maintenance.sh

# Optimize tables
mysql -u username -p dokterku -e "OPTIMIZE TABLE non_paramedis_attendances;"

# Clean up old sessions
php artisan session:gc

# Clear expired tokens
mysql -u username -p dokterku -e "DELETE FROM personal_access_tokens WHERE expires_at < NOW();"

# Update attendance statistics cache
php artisan cache:forget attendance_stats_*
```

#### Weekly Reports
```bash
#!/bin/bash
# scripts/weekly_report.sh

# Generate weekly attendance summary
mysql -u username -p dokterku -e "
SELECT 
    WEEK(attendance_date) as week_number,
    COUNT(*) as total_attendances,
    COUNT(CASE WHEN status = 'checked_out' THEN 1 END) as completed_attendances,
    ROUND(AVG(total_work_minutes), 2) as avg_work_minutes,
    COUNT(CASE WHEN approval_status = 'pending' THEN 1 END) as pending_approvals
FROM non_paramedis_attendances 
WHERE attendance_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY WEEK(attendance_date)
ORDER BY week_number DESC;
"
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Authentication Issues

**Problem**: "Unauthenticated" errors
```bash
# Check token validity
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $token = $user->createToken('test-token');
>>> echo $token->plainTextToken;
```

**Solution**: Verify token in database and ensure middleware is working
```sql
SELECT * FROM personal_access_tokens WHERE tokenable_id = 1 ORDER BY created_at DESC LIMIT 5;
```

#### 2. GPS Validation Issues

**Problem**: Check-in fails with location errors

**Debug GPS Service**:
```php
// In tinker
$service = app(\App\Services\GpsValidationService::class);
$result = $service->validateLocation(-6.2088, 106.8456, 10.5);
dd($result);
```

**Solution**: Check work location coordinates and radius settings
```sql
SELECT id, name, latitude, longitude, radius_meters FROM work_locations WHERE is_active = 1;
```

#### 3. Database Performance Issues

**Problem**: Slow API responses

**Check Query Performance**:
```sql
-- Enable query logging
SET GLOBAL general_log = 'ON';
SET GLOBAL general_log_file = '/tmp/mysql.log';

-- Monitor queries for 1 minute, then check log
tail -f /tmp/mysql.log | grep non_paramedis_attendances
```

**Solution**: Add missing indexes or optimize queries
```sql
-- Check for missing indexes
EXPLAIN SELECT * FROM non_paramedis_attendances WHERE user_id = 1 AND attendance_date = '2025-07-15';
```

#### 4. Rate Limiting Issues

**Problem**: "Rate limit exceeded" errors

**Check Rate Limit Configuration**:
```php
// Check middleware configuration
php artisan route:list | grep nonparamedis
```

**Solution**: Adjust rate limits in configuration or implement proper retry logic

#### 5. JSON Field Issues

**Problem**: Errors with JSON fields (device_info, gps_metadata)

**Debug JSON Data**:
```sql
-- Check JSON validity
SELECT id, JSON_VALID(device_info) as valid_device_info FROM non_paramedis_attendances WHERE device_info IS NOT NULL LIMIT 10;
```

**Solution**: Ensure proper JSON encoding in application
```php
// Correct JSON storage
$attendance->device_info = [
    'user_agent' => $request->header('User-Agent'),
    'ip_address' => $request->ip(),
    'platform' => 'mobile_web'
];
```

### Emergency Procedures

#### 1. API Downtime Recovery

```bash
# Check application status
php artisan down --message="Maintenance in progress"

# Fix issues and bring back online
php artisan up

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 2. Database Recovery

```bash
# Backup before recovery
mysqldump -u username -p dokterku > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from backup if needed
mysql -u username -p dokterku < backup_file.sql
```

#### 3. Reset User Authentication

```sql
-- Clear all user tokens (force re-login)
DELETE FROM personal_access_tokens;

-- Reset specific user tokens
DELETE FROM personal_access_tokens WHERE tokenable_id = 1;
```

### Support Contacts

- **Technical Issues**: tech-support@dokterku.com
- **API Documentation**: https://dokterku.com/api/v2/documentation
- **Emergency Contact**: +62-xxx-xxx-xxxx

---

*Implementation Guide v1.0 | Generated on 2025-07-15 | Dokterku Medical Clinic Management System*