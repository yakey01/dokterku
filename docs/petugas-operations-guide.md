# Operations Guide - Petugas Dashboard System

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Deployment](#deployment)
4. [Configuration](#configuration)
5. [Monitoring](#monitoring)
6. [Backup and Recovery](#backup-and-recovery)
7. [Security](#security)
8. [Performance Optimization](#performance-optimization)
9. [Troubleshooting](#troubleshooting)
10. [Maintenance](#maintenance)
11. [Scaling](#scaling)
12. [Incident Response](#incident-response)

---

## System Overview

### Purpose
The Petugas Dashboard is a clinic management system designed specifically for medical staff (petugas) to manage patient data, treatments, financial transactions, and daily reports with built-in approval workflows.

### Key Components
- **Laravel 11** - Backend framework
- **Filament PHP v3.3** - Admin panel framework
- **Livewire** - Real-time UI components
- **MySQL 8.0** - Primary database
- **Redis** - Caching and session storage
- **Telegram Bot** - Notification system
- **Queue System** - Background job processing

### System Requirements
- **PHP**: 8.2+
- **MySQL**: 8.0+
- **Redis**: 6.0+
- **Node.js**: 18+
- **Memory**: 2GB minimum, 4GB recommended
- **Storage**: 50GB minimum, 100GB recommended
- **CPU**: 2 cores minimum, 4 cores recommended

---

## Architecture

### Application Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Load Balancer │    │   Web Servers   │    │   Database      │
│   (Nginx)       │────│   (PHP-FPM)     │────│   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                       ┌─────────────────┐    ┌─────────────────┐
                       │   Cache         │    │   Queue         │
                       │   (Redis)       │    │   (Redis)       │
                       └─────────────────┘    └─────────────────┘
                                │
                       ┌─────────────────┐
                       │   File Storage  │
                       │   (Local/S3)    │
                       └─────────────────┘
```

### Directory Structure
```
/var/www/dokterku/
├── app/
│   ├── Filament/Petugas/
│   │   ├── Resources/
│   │   └── Widgets/
│   ├── Services/
│   ├── Models/
│   └── Policies/
├── config/
├── database/
├── storage/
│   ├── logs/
│   ├── cache/
│   └── exports/
├── tests/
└── docs/
```

### Database Schema
```sql
-- Core Tables
- users (authentication and user management)
- pasien (patient data)
- tindakan (medical treatments)
- pendapatan_harian (daily income)
- pengeluaran_harian (daily expenses)
- jumlah_pasien_harian (daily patient reports)

-- Supporting Tables
- jenis_tindakan (treatment types)
- pendapatan (income categories)
- pengeluaran (expense categories)
- shifts (work shifts)
- audit_logs (audit trail)

-- Notification Tables
- notifications (system notifications)
- user_sessions (session management)
```

---

## Deployment

### Environment Setup

#### Production Environment
```bash
# Clone repository
git clone https://github.com/dokterku/clinic-system.git
cd clinic-system

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Environment configuration
cp .env.production .env
php artisan key:generate
```

#### Environment Variables
```env
# Application
APP_NAME="Dokterku Petugas Dashboard"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dokterku_production
DB_USERNAME=dokterku_user
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@dokterku.com
MAIL_PASSWORD=mail_password

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id

# File Storage
FILESYSTEM_DRIVER=local
# For S3: FILESYSTEM_DRIVER=s3
# AWS_ACCESS_KEY_ID=your_access_key
# AWS_SECRET_ACCESS_KEY=your_secret_key
# AWS_DEFAULT_REGION=ap-southeast-1
# AWS_BUCKET=dokterku-storage
```

### Docker Deployment
```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/html/storage
      - ./logs:/var/www/html/storage/logs
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: dokterku_production
      MYSQL_USER: dokterku_user
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:6.0-alpine
    command: redis-server --requirepass redis_password

volumes:
  mysql_data:
```

### Database Migration
```bash
# Run migrations
php artisan migrate --force

# Seed essential data
php artisan db:seed --class=ProductionSeeder

# Create application cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name dokterku.com www.dokterku.com;
    root /var/www/dokterku/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";

    # Gzip compression
    gzip on;
    gzip_types text/css application/javascript text/javascript application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_param PHP_VALUE "upload_max_filesize=100M \n post_max_size=100M";
        fastcgi_read_timeout 300;
    }

    # Block access to sensitive files
    location ~ /\.ht {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }
}
```

### PHP-FPM Configuration
```ini
; /etc/php/8.2/fpm/pool.d/www.conf
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000

; Performance
request_slowlog_timeout = 10s
slowlog = /var/log/php8.2-fpm-slow.log

; Memory
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
```

### Supervisor Configuration
```ini
; /etc/supervisor/conf.d/dokterku-workers.conf
[program:dokterku-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dokterku/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/dokterku/storage/logs/worker.log
stopwaitsecs=3600

[program:dokterku-schedule]
process_name=%(program_name)s
command=php /var/www/dokterku/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/dokterku/storage/logs/scheduler.log
```

---

## Monitoring

### Application Monitoring

#### Laravel Telescope (Development)
```bash
# Install Telescope for debugging
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

#### Log Monitoring
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Monitor worker logs
tail -f storage/logs/worker.log

# Monitor nginx access logs
tail -f /var/log/nginx/access.log

# Monitor nginx error logs
tail -f /var/log/nginx/error.log
```

#### Health Check Endpoints
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
            'cache' => Cache::get('health-check') !== null ? 'connected' : 'disconnected',
            'queue' => 'operational', // Add queue health check
        ]
    ]);
});
```

### System Monitoring

#### System Resources
```bash
# CPU usage
top -p $(pgrep -d, php-fpm)

# Memory usage
free -h

# Disk usage
df -h

# MySQL performance
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW STATUS LIKE 'Threads%';"

# Redis performance
redis-cli info stats
redis-cli info memory
```

#### Performance Metrics
```bash
# Application response time
curl -o /dev/null -s -w "%{time_total}\n" https://dokterku.com/petugas

# Database query performance
mysql -e "SHOW GLOBAL STATUS LIKE 'Slow_queries';"

# Cache hit ratio
redis-cli info stats | grep keyspace_hits
```

### Alerting Setup

#### Script untuk Monitoring
```bash
#!/bin/bash
# monitoring.sh

# Check application health
health_status=$(curl -s https://dokterku.com/health | jq -r '.status')
if [ "$health_status" != "healthy" ]; then
    echo "Application health check failed" | mail -s "ALERT: Dokterku Health Check Failed" ops@dokterku.com
fi

# Check disk space
disk_usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $disk_usage -gt 80 ]; then
    echo "Disk usage is at ${disk_usage}%" | mail -s "ALERT: High Disk Usage" ops@dokterku.com
fi

# Check MySQL connection
mysql_check=$(mysql -e "SELECT 1;" 2>/dev/null && echo "OK" || echo "FAIL")
if [ "$mysql_check" = "FAIL" ]; then
    echo "MySQL connection failed" | mail -s "ALERT: MySQL Connection Failed" ops@dokterku.com
fi
```

---

## Backup and Recovery

### Database Backup

#### Automated Backup Script
```bash
#!/bin/bash
# backup.sh

# Configuration
DB_NAME="dokterku_production"
DB_USER="dokterku_user"
DB_PASS="secure_password"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    $DB_NAME > $BACKUP_DIR/dokterku_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/dokterku_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

# Log backup completion
echo "$(date): Database backup completed - dokterku_$DATE.sql.gz" >> /var/log/backup.log
```

#### Crontab Setup
```bash
# crontab -e
# Daily backup at 2 AM
0 2 * * * /var/scripts/backup.sh

# Weekly backup retention check
0 3 * * 0 /var/scripts/cleanup-old-backups.sh
```

### File Storage Backup
```bash
#!/bin/bash
# backup-files.sh

# Backup storage directory
rsync -av --delete /var/www/dokterku/storage/ /var/backups/storage/

# Backup uploads if using local storage
rsync -av --delete /var/www/dokterku/public/uploads/ /var/backups/uploads/

# Sync to remote backup server (optional)
rsync -av /var/backups/ backup-server:/backups/dokterku/
```

### Recovery Procedures

#### Database Recovery
```bash
# Stop application
sudo systemctl stop nginx
sudo systemctl stop php8.2-fpm

# Restore database
mysql -u root -p
DROP DATABASE dokterku_production;
CREATE DATABASE dokterku_production;
exit

gunzip < /var/backups/mysql/dokterku_20240715_020000.sql.gz | mysql -u root -p dokterku_production

# Restart services
sudo systemctl start php8.2-fpm
sudo systemctl start nginx
```

#### Application Recovery
```bash
# Restore from git repository
cd /var/www/
git clone https://github.com/dokterku/clinic-system.git dokterku-restore
cd dokterku-restore

# Install dependencies
composer install --no-dev --optimize-autoloader

# Restore configuration
cp /var/backups/config/.env .env

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Switch to restored version
cd /var/www/
sudo systemctl stop nginx
mv dokterku dokterku-broken
mv dokterku-restore dokterku
sudo systemctl start nginx
```

---

## Security

### Access Control

#### Firewall Configuration
```bash
# UFW configuration
sudo ufw enable
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw deny 3306/tcp  # MySQL (block external access)
sudo ufw deny 6379/tcp  # Redis (block external access)
```

#### SSH Hardening
```bash
# /etc/ssh/sshd_config
Port 2222                    # Change default port
PermitRootLogin no          # Disable root login
PasswordAuthentication no   # Use key-based auth only
PubkeyAuthentication yes
MaxAuthTries 3
AllowUsers dokterku-admin
```

### Application Security

#### SSL Certificate (Let's Encrypt)
```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d dokterku.com -d www.dokterku.com

# Auto-renewal
sudo crontab -e
0 12 * * * /usr/bin/certbot renew --quiet
```

#### Security Headers
```nginx
# In nginx server block
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
```

#### Input Validation
All user inputs are validated through:
- Laravel Form Requests
- Filament form validation
- Database constraints
- XSS protection middleware

#### Authentication Security
- Password hashing using bcrypt
- Session security with secure cookies
- CSRF protection on all forms
- Rate limiting on authentication endpoints

---

## Performance Optimization

### Database Optimization

#### Indexing Strategy
```sql
-- Patient table indexes
CREATE INDEX idx_pasien_input_by ON pasien(input_by);
CREATE INDEX idx_pasien_created_at ON pasien(created_at);
CREATE INDEX idx_pasien_no_rekam_medis ON pasien(no_rekam_medis);

-- Treatment table indexes
CREATE INDEX idx_tindakan_input_by ON tindakan(input_by);
CREATE INDEX idx_tindakan_pasien_id ON tindakan(pasien_id);
CREATE INDEX idx_tindakan_tanggal ON tindakan(tanggal_tindakan);
CREATE INDEX idx_tindakan_status ON tindakan(status_validasi);

-- Composite indexes for common queries
CREATE INDEX idx_tindakan_user_date ON tindakan(input_by, tanggal_tindakan);
CREATE INDEX idx_pendapatan_user_date ON pendapatan_harian(user_id, tanggal_input);
```

#### Query Optimization
```php
// Efficient data loading with relationships
$patients = Pasien::with(['tindakan' => function($query) {
    $query->select('id', 'pasien_id', 'tarif', 'created_at');
}])
->where('input_by', auth()->id())
->latest()
->paginate(25);

// Use database aggregations instead of PHP calculations
$stats = DB::table('tindakan')
    ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(tarif) as total')
    )
    ->where('input_by', auth()->id())
    ->groupBy(DB::raw('DATE(created_at)'))
    ->get();
```

### Caching Strategy

#### Laravel Cache Configuration
```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

// Cache usage in services
class PetugasStatsService
{
    public function getDashboardStats($userId)
    {
        return Cache::remember("stats.{$userId}", 300, function () use ($userId) {
            return $this->calculateStats($userId);
        });
    }
}
```

#### OPcache Configuration
```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### Application Performance

#### Queue Optimization
```php
// Use queued jobs for heavy operations
class ExportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Heavy export operation
    }
}

// Dispatch job
ExportDataJob::dispatch($userId, $filters);
```

#### Asset Optimization
```bash
# Optimize assets
npm run production

# Enable asset versioning
php artisan view:cache
php artisan config:cache
php artisan route:cache
```

---

## Troubleshooting

### Common Issues

#### High CPU Usage
```bash
# Identify processes consuming CPU
top -p $(pgrep -d, php-fpm)

# Check slow queries
mysql -e "SHOW PROCESSLIST;" | grep -v Sleep

# Check queue workers
supervisorctl status dokterku-worker:*
```

**Solutions:**
- Optimize database queries
- Add missing indexes
- Increase worker processes
- Enable query caching

#### Memory Issues
```bash
# Check memory usage
free -h
php -m | grep opcache

# Check PHP-FPM pool status
curl http://localhost/fpm-status
```

**Solutions:**
- Increase PHP memory limit
- Optimize OPcache settings
- Review memory leaks in code
- Add more server memory

#### Database Performance
```bash
# Check slow query log
mysql -e "SHOW VARIABLES LIKE 'slow_query_log';"
tail -f /var/log/mysql/mysql-slow.log

# Check connection count
mysql -e "SHOW STATUS LIKE 'Threads_connected';"
```

**Solutions:**
- Optimize slow queries
- Add database indexes
- Increase connection pool
- Consider database caching

### Error Resolution

#### Common Error Messages

**"Class not found" Errors:**
```bash
# Clear and rebuild autoloader
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

**"Queue not processing" Issues:**
```bash
# Restart queue workers
supervisorctl restart dokterku-worker:*

# Check failed jobs
php artisan queue:failed
php artisan queue:retry all
```

**"Session expired" Problems:**
```bash
# Check session configuration
php artisan config:clear
redis-cli flushall

# Verify session driver
grep SESSION_DRIVER .env
```

### Log Analysis

#### Error Log Monitoring
```bash
# Monitor application errors
tail -f storage/logs/laravel.log | grep ERROR

# Monitor SQL errors
tail -f /var/log/mysql/error.log

# Monitor system errors
journalctl -f -u nginx
journalctl -f -u php8.2-fpm
```

---

## Maintenance

### Regular Maintenance Tasks

#### Daily Tasks
```bash
#!/bin/bash
# daily-maintenance.sh

# Clear expired cache
php artisan cache:prune-stale-tags

# Clean up temporary files
find /tmp -name "php*" -mtime +1 -delete

# Rotate logs
logrotate /etc/logrotate.d/dokterku

# Check disk space
df -h | awk '$5 > 80 {print "WARNING: " $0}'
```

#### Weekly Tasks
```bash
#!/bin/bash
# weekly-maintenance.sh

# Optimize database tables
mysql dokterku_production -e "OPTIMIZE TABLE pasien, tindakan, pendapatan_harian, pengeluaran_harian;"

# Clean up old exports
find /var/www/dokterku/storage/exports -name "*.csv" -mtime +7 -delete
find /var/www/dokterku/storage/exports -name "*.xlsx" -mtime +7 -delete

# Vacuum Redis cache
redis-cli --scan --pattern "expired:*" | xargs redis-cli del

# Update system packages (test environment first)
# sudo apt update && sudo apt upgrade -y
```

#### Monthly Tasks
```bash
#!/bin/bash
# monthly-maintenance.sh

# Analyze database performance
mysql dokterku_production -e "ANALYZE TABLE pasien, tindakan, pendapatan_harian, pengeluaran_harian;"

# Review slow query log
mysql -e "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"

# Check SSL certificate expiry
certbot certificates

# Security audit
# Run security scanner
# Review user access logs
# Update dependencies
```

### Update Procedures

#### Application Updates
```bash
# Backup before update
/var/scripts/backup.sh

# Pull latest code
git fetch origin
git checkout v1.2.0  # or latest stable version

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

#### Dependency Updates
```bash
# Update Composer dependencies
composer update --no-dev

# Update NPM dependencies
npm update --production

# Security audit
composer audit
npm audit
```

---

## Scaling

### Horizontal Scaling

#### Load Balancer Configuration
```nginx
# /etc/nginx/conf.d/load-balancer.conf
upstream dokterku_backend {
    least_conn;
    server 10.0.1.10:80 weight=3 max_fails=3 fail_timeout=30s;
    server 10.0.1.11:80 weight=3 max_fails=3 fail_timeout=30s;
    server 10.0.1.12:80 weight=2 max_fails=3 fail_timeout=30s;
}

server {
    listen 80;
    server_name dokterku.com;
    
    location / {
        proxy_pass http://dokterku_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

#### Session Storage
```php
// Use Redis for shared sessions
'SESSION_DRIVER' => 'redis',
'SESSION_CONNECTION' => 'session',

// Redis cluster configuration
'redis' => [
    'session' => [
        'host' => env('REDIS_SESSION_HOST', '127.0.0.1'),
        'password' => env('REDIS_SESSION_PASSWORD', null),
        'port' => env('REDIS_SESSION_PORT', 6379),
        'database' => 1,
    ],
],
```

### Vertical Scaling

#### MySQL Optimization
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
# Memory settings
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M

# Connection settings
max_connections = 200
max_connect_errors = 1000000

# Query cache
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M

# Performance
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
```

#### Redis Optimization
```bash
# /etc/redis/redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### Microservices Migration

#### Service Separation Strategy
1. **Authentication Service**: User management and authentication
2. **Patient Service**: Patient data management
3. **Treatment Service**: Medical treatment management
4. **Financial Service**: Financial transaction management
5. **Notification Service**: Real-time notifications
6. **Export Service**: Data export and reporting

---

## Incident Response

### Incident Classification

#### Severity Levels
- **P1 (Critical)**: System down, data loss, security breach
- **P2 (High)**: Major functionality unavailable
- **P3 (Medium)**: Minor functionality issues
- **P4 (Low)**: Cosmetic issues, feature requests

### Response Procedures

#### P1 Incident Response
1. **Detection** (0-5 minutes)
   - Automated monitoring alerts
   - User reports
   - Health check failures

2. **Assessment** (5-15 minutes)
   - Confirm incident severity
   - Identify affected systems
   - Estimate user impact

3. **Response** (15-30 minutes)
   - Activate incident team
   - Implement immediate fixes
   - Communicate to stakeholders

4. **Resolution** (30 minutes - 4 hours)
   - Apply permanent fix
   - Verify system stability
   - Document lessons learned

#### Incident Team Contacts
```yaml
Incident Commander: ops-lead@dokterku.com
Technical Lead: tech-lead@dokterku.com
Database Expert: dba@dokterku.com
Security Expert: security@dokterku.com
Communications: comms@dokterku.com
```

### Communication Templates

#### User Notification
```
Subject: [RESOLVED] Dokterku System Maintenance Complete

Dear Users,

The scheduled maintenance on the Dokterku Petugas Dashboard has been completed successfully. All services are now fully operational.

Maintenance Window: 2024-07-15 02:00 - 04:00 WIB
Services Affected: All dashboard functions
Status: Resolved

If you experience any issues, please contact support at support@dokterku.com.

Thank you for your patience.

Dokterku Operations Team
```

### Post-Incident Review

#### Review Template
```markdown
# Incident Post-Mortem: [Incident Title]

## Summary
Brief description of the incident.

## Timeline
- **Detection**: 2024-07-15 14:30 WIB
- **Assessment**: 2024-07-15 14:35 WIB
- **Response**: 2024-07-15 14:45 WIB
- **Resolution**: 2024-07-15 15:30 WIB

## Root Cause
Detailed explanation of what caused the incident.

## Impact
- Users affected: 150 active users
- Downtime: 1 hour
- Data loss: None

## Action Items
1. [ ] Implement additional monitoring
2. [ ] Update deployment procedures
3. [ ] Improve error handling

## Lessons Learned
Key takeaways and improvements for future incidents.
```

---

## Conclusion

This operations guide provides comprehensive procedures for managing the Petugas Dashboard system. Regular review and updates of these procedures ensure system reliability and performance.

### Key Takeaways
- **Proactive Monitoring**: Implement comprehensive monitoring and alerting
- **Regular Backups**: Maintain automated backup procedures
- **Performance Optimization**: Continuously optimize for better performance
- **Security First**: Maintain strong security practices
- **Documentation**: Keep documentation current and accessible

### Support Contacts
- **Operations Team**: ops@dokterku.com
- **Development Team**: dev@dokterku.com
- **24/7 Emergency**: +62-xxx-xxx-xxxx

---

*This operations guide is maintained by the Dokterku DevOps team and updated regularly to reflect current best practices.*

**Version**: 1.0  
**Last Updated**: 2024-07-15  
**Next Review**: 2024-10-15