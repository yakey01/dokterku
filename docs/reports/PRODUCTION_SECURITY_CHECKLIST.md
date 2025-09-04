# Production Security Checklist for Klinik Dokterku

## Environment Configuration

### Required Environment Variables for Production

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=strong-password

# Authentication & Security
JWT_SECRET=your-very-strong-jwt-secret
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
BCRYPT_ROUNDS=12

# API Security
API_DEVICE_BINDING_ENABLED=true
API_GPS_SPOOFING_DETECTION=true
API_LOGGING_ENABLED=true
API_LOG_REQUESTS=false  # Set to true for debugging
API_LOG_RESPONSES=false # Set to true for debugging
API_LOG_SLOW_QUERIES=true

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls

# Push Notifications (Optional)
FCM_ENABLED=true
FCM_SERVER_KEY=your-fcm-server-key
```

## Security Hardening Steps

### 1. Update API Configuration for Production

Update `config/api.php`:

```php
'allowed_origins' => [
    'https://your-domain.com',
    'https://app.your-domain.com',
    // Remove '*' for production
],

'rate_limits' => [
    'authentication' => [
        'requests' => 5,
        'per_minutes' => 15,
    ],
    'general_api' => [
        'requests' => 60, // Reduced for production
        'per_minutes' => 1,
    ],
    'attendance' => [
        'requests' => 5, // Reduced for production
        'per_minutes' => 1,
    ],
],
```

### 2. Database Security

1. **Create dedicated database user with minimal privileges:**
```sql
CREATE USER 'klinik_app'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON klinik_db.* TO 'klinik_app'@'%';
FLUSH PRIVILEGES;
```

2. **Enable SSL connections**
3. **Regular backups with encryption**
4. **Database firewall rules**

### 3. Web Server Configuration

#### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' api.your-domain.com;";
    
    # Hide server information
    server_tokens off;
    
    # File upload size
    client_max_body_size 20M;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=auth:10m rate=1r/s;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location /api/v2/auth/ {
        limit_req zone=auth burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }
    
    location ~ /storage/ {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

### 4. Application Security

#### Update `config/cors.php`:

```php
'allowed_origins' => [
    'https://your-domain.com',
    'https://app.your-domain.com',
],

'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],

'allowed_headers' => [
    'Accept',
    'Authorization',
    'Content-Type',
    'X-Requested-With',
    'X-CSRF-TOKEN',
    'X-Request-ID',
],

'supports_credentials' => true,
```

#### Security Middleware Stack

Update `bootstrap/app.php`:

```php
$middleware->append([
    \App\Http\Middleware\SecurityHeadersMiddleware::class,
    \App\Http\Middleware\Api\ApiSecurityAuditMiddleware::class,
]);

$middleware->group('api', [
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\Api\ApiResponseHeadersMiddleware::class,
]);
```

### 5. Monitoring & Logging

#### Log Configuration

Update `config/logging.php`:

```php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 30,
        'permission' => 0644,
    ],
    
    'api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api.log'),
        'level' => 'info',
        'days' => 14,
        'permission' => 0644,
    ],
    
    'attendance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/attendance.log'),
        'level' => 'info',
        'days' => 90,
        'permission' => 0644,
    ],
],
```

### 6. Mobile App Security

#### Frontend Security Measures

1. **Content Security Policy**: Already implemented in Nginx config
2. **API Token Management**: Tokens expire after 30 days for mobile
3. **GPS Validation**: Anti-spoofing detection enabled
4. **Device Binding**: Maximum 3 devices per user
5. **Rate Limiting**: Implemented per user and endpoint

### 7. Regular Security Tasks

#### Daily
- Monitor failed authentication attempts
- Check API rate limit violations
- Review GPS spoofing detections

#### Weekly
- Update dependencies (`composer update --no-dev`)
- Review security logs
- Check disk space and performance

#### Monthly
- Security audit of user permissions
- Database performance optimization
- SSL certificate renewal check
- Backup integrity verification

### 8. Emergency Response Plan

#### Security Incident Response

1. **Immediate Actions**:
   - Change all passwords and API keys
   - Revoke all user sessions
   - Enable maintenance mode
   - Contact hosting provider if needed

2. **Investigation**:
   - Review security logs
   - Identify affected users/data
   - Document the incident

3. **Recovery**:
   - Fix security vulnerabilities
   - Restore from clean backups if needed
   - Notify affected users
   - Update security measures

### 9. Performance Optimization

#### Production Caching

```bash
# Enable production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Enable OPcache in PHP
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
```

#### Database Optimization

```sql
-- Add indexes for performance
CREATE INDEX idx_users_role_active ON users(role_id, is_active);
CREATE INDEX idx_attendance_user_date ON non_paramedis_attendances(user_id, attendance_date);
CREATE INDEX idx_tokens_user ON personal_access_tokens(tokenable_id, tokenable_type);
```

### 10. SSL/TLS Configuration

#### SSL Security

1. **Use strong ciphers**
2. **Enable HSTS**
3. **Disable old TLS versions**
4. **Set up automatic renewal**

```nginx
# Strong SSL configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;

# HSTS
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
```

## Final Deployment Checklist

- [ ] All environment variables configured
- [ ] Database secured and backed up
- [ ] SSL certificate installed and configured
- [ ] Security headers implemented
- [ ] Rate limiting configured
- [ ] Logs monitoring set up
- [ ] Error tracking enabled (Sentry/Bugsnag)
- [ ] Performance monitoring enabled
- [ ] Backup strategy implemented
- [ ] Security incident response plan documented
- [ ] Team access and permissions configured
- [ ] Documentation updated
- [ ] Load testing completed
- [ ] Security penetration testing completed

## Maintenance Schedule

### Daily
- [ ] Check application logs for errors
- [ ] Monitor disk space and memory usage
- [ ] Verify backup completion

### Weekly
- [ ] Update dependencies
- [ ] Review security logs
- [ ] Database maintenance (optimize tables)

### Monthly
- [ ] Security audit
- [ ] Performance review
- [ ] SSL certificate check
- [ ] Disaster recovery test

This checklist ensures your Klinik Dokterku application is production-ready with enterprise-level security measures.