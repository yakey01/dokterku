# Panduan Deployment dan Pemeliharaan Sistem Dokterku

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Persiapan Environment](#persiapan-environment)
3. [Deployment Production](#deployment-production)
4. [Database Setup](#database-setup)
5. [Konfigurasi Server](#konfigurasi-server)
6. [Security Setup](#security-setup)
7. [Monitoring dan Logging](#monitoring-dan-logging)
8. [Backup dan Recovery](#backup-dan-recovery)
9. [Performance Tuning](#performance-tuning)
10. [Maintenance Tasks](#maintenance-tasks)
11. [Troubleshooting](#troubleshooting)
12. [Update dan Upgrade](#update-dan-upgrade)

---

## Pendahuluan

### Sistem Arsitektur Dokterku

**Teknologi Stack:**
- **Backend**: Laravel 11 + PHP 8.2+
- **Frontend**: FilamentPHP v3.3 + Livewire + Alpine.js
- **Database**: MySQL/PostgreSQL (production), SQLite (development)
- **Cache**: Redis (production), File cache (development)
- **Queue**: Redis/Database
- **Web Server**: Nginx + PHP-FPM
- **Asset Building**: Vite + Tailwind CSS 4.0

**Multi-Panel Architecture:**
- Admin Panel (`/admin`)
- Manajer Panel (`/manajer`)
- Bendahara Panel (`/bendahara`)
- Petugas Panel (`/petugas`)
- Paramedis Panel (`/paramedis`)
- Dokter Panel (`/dokter`)

---

## Persiapan Environment

### Sistem Requirements

#### **Server Requirements (Production)**
```yaml
Operating System:
  - Ubuntu 20.04 LTS atau 22.04 LTS (recommended)
  - CentOS 8+ / RHEL 8+
  - Debian 11+

Hardware Minimum:
  - CPU: 2 cores (4 cores recommended)
  - RAM: 4GB (8GB recommended)
  - Storage: 50GB SSD (100GB+ recommended)
  - Network: 100Mbps

Hardware Recommended (High Load):
  - CPU: 4+ cores
  - RAM: 16GB+
  - Storage: 200GB+ NVMe SSD
  - Network: 1Gbps
```

#### **Software Dependencies**
```bash
# PHP Requirements
PHP: 8.2 atau 8.3
Extensions:
  - bcmath, ctype, curl, dom, fileinfo
  - filter, hash, mbstring, openssl
  - pcre, pdo, pdo_mysql, session
  - tokenizer, xml, zip, gd, imagick

# Database
MySQL: 8.0+ atau PostgreSQL: 13+
Redis: 6.0+ (untuk cache dan queue)

# Web Server
Nginx: 1.18+
PHP-FPM: 8.2+

# Node.js (untuk asset building)
Node.js: 18+ LTS
NPM: 9+
```

### Pre-deployment Checklist

```bash
# 1. Verify PHP version dan extensions
php -v
php -m | grep -E 'bcmath|ctype|curl|dom|fileinfo|filter|hash|mbstring|openssl|pcre|pdo|session|tokenizer|xml|zip|gd'

# 2. Check database connectivity
mysql --version
redis-cli ping

# 3. Verify Node.js dan NPM
node --version
npm --version

# 4. Check server resources
free -h
df -h
lscpu
```

---

## Deployment Production

### 1. Clone Repository dan Setup

#### **Clone dan Setup Project**
```bash
# 1. Clone repository
cd /var/www
sudo git clone https://github.com/your-org/dokterku.git
cd dokterku

# 2. Set ownership dan permissions
sudo chown -R www-data:www-data /var/www/dokterku
sudo chmod -R 755 /var/www/dokterku
sudo chmod -R 775 /var/www/dokterku/storage
sudo chmod -R 775 /var/www/dokterku/bootstrap/cache

# 3. Install Composer dependencies
composer install --no-dev --optimize-autoloader

# 4. Install NPM dependencies dan build assets
npm install
npm run build
```

#### **Environment Configuration**
```bash
# 1. Copy environment file
cp .env.example .env

# 2. Generate application key
php artisan key:generate

# 3. Configure .env untuk production
nano .env
```

#### **Production .env Configuration**
```env
# Application
APP_NAME="Dokterku Klinik Management"
APP_ENV=production
APP_KEY=base64:generated-key-here
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dokterku_production
DB_USERNAME=dokterku_user
DB_PASSWORD=secure_database_password

# Cache dan Session (Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=local

# API Keys (if needed)
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
TELEGRAM_BOT_TOKEN=your_telegram_bot_token

# Security
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

### 2. Database Migration dan Seeding

#### **Database Setup**
```bash
# 1. Run migrations
php artisan migrate --force

# 2. Seed production data
php artisan db:seed --class=ProductionSeeder

# 3. Create storage link
php artisan storage:link

# 4. Clear dan optimize caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

#### **Create Production Seeder**
```php
<?php
// database/seeders/ProductionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $this->call([
            RoleSeeder::class,
            WorkLocationSeeder::class,
            SystemConfigSeeder::class,
        ]);
        
        // Create admin user only
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        
        \App\Models\User::firstOrCreate([
            'email' => 'admin@yourdomain.com'
        ], [
            'name' => 'System Administrator',
            'password' => bcrypt('change_this_password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
        
        echo "Production seeding completed. Please change admin password!\n";
    }
}
```

### 3. Web Server Configuration (Nginx)

#### **Nginx Virtual Host**
```nginx
# /etc/nginx/sites-available/dokterku
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/dokterku/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # File upload limit
    client_max_body_size 100M;
    
    # Laravel specific configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Asset caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # API rate limiting
    location ^~ /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}

# Rate limiting configuration
http {
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
}
```

#### **Enable Site dan Restart Services**
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/dokterku /etc/nginx/sites-enabled/

# Test nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### 4. SSL Certificate Setup (Let's Encrypt)

```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Verify auto-renewal
sudo certbot renew --dry-run

# Set up auto-renewal cron job
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

---

## Database Setup

### MySQL Production Configuration

#### **Create Database dan User**
```sql
-- Connect sebagai root
mysql -u root -p

-- Create database
CREATE DATABASE dokterku_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user dengan restricted permissions
CREATE USER 'dokterku_user'@'localhost' IDENTIFIED BY 'secure_database_password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP ON dokterku_production.* TO 'dokterku_user'@'localhost';
FLUSH PRIVILEGES;

-- Test connection
mysql -u dokterku_user -p dokterku_production
```

#### **MySQL Performance Tuning**
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
# Memory settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 64M
query_cache_size = 64M
query_cache_limit = 4M

# Connection settings
max_connections = 200
connect_timeout = 60
wait_timeout = 120

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Binary logging
log-bin = mysql-bin
binlog_format = ROW
expire_logs_days = 7

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Database Backup Setup

#### **Automated Backup Script**
```bash
#!/bin/bash
# /usr/local/bin/backup-dokterku.sh

# Configuration
DB_NAME="dokterku_production"
DB_USER="dokterku_user"
DB_PASS="secure_database_password"
BACKUP_DIR="/var/backups/dokterku"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Create files backup
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/dokterku/storage/app

# Remove old backups
find $BACKUP_DIR -name "*.gz" -mtime +$RETENTION_DAYS -delete

# Log backup completion
echo "$(date): Backup completed - db_backup_$DATE.sql.gz" >> /var/log/dokterku-backup.log
```

#### **Setup Backup Cron Job**
```bash
# Make backup script executable
sudo chmod +x /usr/local/bin/backup-dokterku.sh

# Add to crontab
sudo crontab -e
# Add this line for daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-dokterku.sh

# Weekly backup with extended retention
0 3 * * 0 /usr/local/bin/backup-dokterku.sh weekly
```

---

## Konfigurasi Server

### PHP-FPM Optimization

#### **PHP-FPM Pool Configuration**
```ini
# /etc/php/8.2/fpm/pool.d/dokterku.conf
[dokterku]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm-dokterku.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 1000

; PHP configuration
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_time] = 300

; Error logging
php_admin_value[error_log] = /var/log/php/dokterku-error.log
php_admin_flag[log_errors] = on
```

### Redis Configuration

#### **Redis for Cache dan Queue**
```bash
# /etc/redis/redis.conf

# Memory settings
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence (for queue jobs)
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password
bind 127.0.0.1

# Performance
tcp-keepalive 300
timeout 0
```

### Queue Worker Setup

#### **Systemd Service untuk Queue Worker**
```ini
# /etc/systemd/system/dokterku-worker.service
[Unit]
Description=Dokterku Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/dokterku
ExecStart=/usr/bin/php /var/www/dokterku/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=3

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=dokterku-worker

[Install]
WantedBy=multi-user.target
```

#### **Enable dan Start Queue Worker**
```bash
# Enable dan start service
sudo systemctl daemon-reload
sudo systemctl enable dokterku-worker
sudo systemctl start dokterku-worker

# Check status
sudo systemctl status dokterku-worker

# View logs
sudo journalctl -u dokterku-worker -f
```

### Task Scheduler Setup

#### **Laravel Scheduler Cron**
```bash
# Add to www-data crontab
sudo crontab -u www-data -e

# Add this line
* * * * * cd /var/www/dokterku && php artisan schedule:run >> /dev/null 2>&1
```

---

## Security Setup

### Firewall Configuration

#### **UFW (Ubuntu Firewall) Setup**
```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow ssh

# Allow HTTP dan HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Allow MySQL only from localhost
sudo ufw allow from 127.0.0.1 to any port 3306

# Check status
sudo ufw status verbose
```

### Application Security

#### **File Permissions Security**
```bash
# Set secure permissions
sudo find /var/www/dokterku -type f -exec chmod 644 {} \;
sudo find /var/www/dokterku -type d -exec chmod 755 {} \;

# Special permissions for storage dan cache
sudo chmod -R 775 /var/www/dokterku/storage
sudo chmod -R 775 /var/www/dokterku/bootstrap/cache

# Protect sensitive files
sudo chmod 600 /var/www/dokterku/.env
sudo chmod 600 /var/www/dokterku/config/database.php
```

#### **Environment Security Checklist**
```bash
# 1. Verify APP_DEBUG=false
grep APP_DEBUG /var/www/dokterku/.env

# 2. Check strong APP_KEY
grep APP_KEY /var/www/dokterku/.env

# 3. Verify database credentials are secure
grep DB_PASSWORD /var/www/dokterku/.env

# 4. Check session security
grep SESSION_SECURE_COOKIE /var/www/dokterku/.env

# 5. Remove default credentials
# Login ke system dan ganti password admin default
```

### SSL dan HTTPS Security

#### **Force HTTPS in Laravel**
```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

#### **Security Headers Configuration**
```php
// app/Http/Middleware/SecurityHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        
        return $response;
    }
}
```

---

## Monitoring dan Logging

### Application Logging

#### **Laravel Logging Configuration**
```php
// config/logging.php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    
    'dokterku' => [
        'driver' => 'daily',
        'path' => storage_path('logs/dokterku.log'),
        'level' => 'info',
        'days' => 30,
    ],
    
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
],
```

#### **Custom Logging dalam Application**
```php
// Log important events
use Illuminate\Support\Facades\Log;

// User actions
Log::channel('dokterku')->info('User login', [
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent()
]);

// Security events
Log::channel('security')->warning('Failed login attempt', [
    'email' => $request->email,
    'ip' => request()->ip()
]);

// Performance monitoring
Log::channel('dokterku')->info('Slow query detected', [
    'query_time' => $executionTime,
    'query' => $queryString
]);
```

### System Monitoring

#### **Log Rotation Setup**
```bash
# /etc/logrotate.d/dokterku
/var/www/dokterku/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}

/var/log/nginx/dokterku*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    postrotate
        systemctl reload nginx
    endscript
}
```

#### **Health Check Script**
```bash
#!/bin/bash
# /usr/local/bin/dokterku-health-check.sh

# Configuration
APP_URL="https://yourdomain.com"
LOG_FILE="/var/log/dokterku-health.log"
ALERT_EMAIL="admin@yourdomain.com"

# Check application response
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $APP_URL)

if [ $HTTP_STATUS -eq 200 ]; then
    echo "$(date): Application OK - HTTP $HTTP_STATUS" >> $LOG_FILE
else
    echo "$(date): Application DOWN - HTTP $HTTP_STATUS" >> $LOG_FILE
    # Send alert email
    echo "Dokterku application is down. HTTP Status: $HTTP_STATUS" | mail -s "ALERT: Dokterku Down" $ALERT_EMAIL
fi

# Check database connection
php /var/www/dokterku/artisan tinker --execute="DB::connection()->getPdo();"
if [ $? -eq 0 ]; then
    echo "$(date): Database OK" >> $LOG_FILE
else
    echo "$(date): Database connection failed" >> $LOG_FILE
    echo "Database connection failed" | mail -s "ALERT: Database Down" $ALERT_EMAIL
fi

# Check queue worker
if systemctl is-active --quiet dokterku-worker; then
    echo "$(date): Queue worker OK" >> $LOG_FILE
else
    echo "$(date): Queue worker down" >> $LOG_FILE
    systemctl restart dokterku-worker
    echo "Queue worker restarted" | mail -s "ALERT: Queue Worker Restarted" $ALERT_EMAIL
fi
```

#### **Setup Health Check Cron**
```bash
# Add to crontab for 5-minute checks
sudo crontab -e
*/5 * * * * /usr/local/bin/dokterku-health-check.sh
```

---

## Performance Tuning

### Application Performance

#### **Optimize Composer Autoloader**
```bash
# Production optimization
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Generate optimized bootstrap file
php artisan optimize

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

#### **Laravel Caching Strategy**
```php
// config/cache.php - Production caching
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => env('CACHE_PREFIX', 'dokterku_cache'),
    ],
],

// Use cache for expensive operations
class PasienService
{
    public function getMonthlyStats()
    {
        return Cache::remember('monthly_stats', 3600, function () {
            return Pasien::selectRaw('COUNT(*) as total, MONTH(created_at) as month')
                ->groupBy('month')
                ->get();
        });
    }
}
```

#### **Database Query Optimization**
```php
// Use eager loading untuk N+1 problems
$pasien = Pasien::with(['tindakan', 'tindakan.jenisTindakan'])->get();

// Use chunking untuk large datasets
Pasien::chunk(1000, function ($patients) {
    foreach ($patients as $patient) {
        // Process patient
    }
});

// Database indexing strategy
Schema::table('tindakan', function (Blueprint $table) {
    $table->index(['tanggal_tindakan', 'status']);
    $table->index(['pasien_id', 'created_at']);
    $table->index(['dokter_id', 'status']);
});
```

### Server Performance

#### **Nginx Performance Tuning**
```nginx
# /etc/nginx/nginx.conf
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    # Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # Caching
    open_file_cache max=10000 inactive=20s;
    open_file_cache_valid 30s;
    open_file_cache_min_uses 2;
    open_file_cache_errors on;
    
    # Buffer sizes
    client_body_buffer_size 128k;
    client_max_body_size 100m;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;
    output_buffers 1 32k;
    postpone_output 1460;
}
```

#### **MySQL Performance Monitoring**
```sql
-- Slow query analysis
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- Index usage analysis
SELECT 
    table_name,
    index_name,
    cardinality,
    sub_part,
    packed,
    nullable,
    index_type
FROM information_schema.statistics 
WHERE table_schema = 'dokterku_production'
ORDER BY table_name, seq_in_index;

-- Performance schema monitoring
SELECT * FROM performance_schema.events_statements_summary_by_digest 
ORDER BY avg_timer_wait DESC LIMIT 10;
```

---

## Maintenance Tasks

### Daily Maintenance Tasks

#### **Daily Maintenance Script**
```bash
#!/bin/bash
# /usr/local/bin/dokterku-daily-maintenance.sh

LOG_FILE="/var/log/dokterku-maintenance.log"
echo "$(date): Starting daily maintenance" >> $LOG_FILE

# 1. Clear expired sessions
php /var/www/dokterku/artisan session:cleanup

# 2. Clear old log files
find /var/www/dokterku/storage/logs -name "*.log" -mtime +30 -delete

# 3. Optimize database tables
mysql -u dokterku_user -p'secure_database_password' dokterku_production -e "OPTIMIZE TABLE pasien, tindakan, pendapatan, pengeluaran, jaspel;"

# 4. Clear expired cache entries
php /var/www/dokterku/artisan cache:prune-stale-tags

# 5. Update statistics
php /var/www/dokterku/artisan dokterku:update-statistics

# 6. Check disk space
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "WARNING: Disk usage is ${DISK_USAGE}%" | mail -s "ALERT: High Disk Usage" admin@yourdomain.com
fi

echo "$(date): Daily maintenance completed" >> $LOG_FILE
```

### Weekly Maintenance Tasks

#### **Weekly Maintenance Script**
```bash
#!/bin/bash
# /usr/local/bin/dokterku-weekly-maintenance.sh

LOG_FILE="/var/log/dokterku-maintenance.log"
echo "$(date): Starting weekly maintenance" >> $LOG_FILE

# 1. Full database backup
/usr/local/bin/backup-dokterku.sh

# 2. Analyze database tables
mysql -u dokterku_user -p'secure_database_password' dokterku_production -e "ANALYZE TABLE pasien, tindakan, pendapatan, pengeluaran, jaspel;"

# 3. Check for failed jobs
FAILED_JOBS=$(php /var/www/dokterku/artisan queue:failed | wc -l)
if [ $FAILED_JOBS -gt 0 ]; then
    echo "WARNING: $FAILED_JOBS failed jobs found" | mail -s "ALERT: Failed Jobs" admin@yourdomain.com
fi

# 4. Update Composer dependencies (security patches only)
cd /var/www/dokterku
composer update --no-dev --with-dependencies --prefer-stable

# 5. Regenerate optimized files
php artisan optimize:clear
php artisan optimize

# 6. Check SSL certificate expiration
SSL_DAYS=$(echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2 | xargs -I {} date -d {} +%s)
NOW=$(date +%s)
DAYS_LEFT=$(( (SSL_DAYS - NOW) / 86400 ))

if [ $DAYS_LEFT -lt 30 ]; then
    echo "WARNING: SSL certificate expires in $DAYS_LEFT days" | mail -s "ALERT: SSL Certificate Expiring" admin@yourdomain.com
fi

echo "$(date): Weekly maintenance completed" >> $LOG_FILE
```

### Monthly Maintenance Tasks

#### **Monthly Maintenance Script**
```bash
#!/bin/bash
# /usr/local/bin/dokterku-monthly-maintenance.sh

LOG_FILE="/var/log/dokterku-maintenance.log"
echo "$(date): Starting monthly maintenance" >> $LOG_FILE

# 1. Archive old data
php /var/www/dokterku/artisan dokterku:archive-old-data

# 2. Update system packages
sudo apt update && sudo apt upgrade -y

# 3. Restart services untuk memory cleanup
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart mysql
sudo systemctl restart redis-server

# 4. Performance analysis report
php /var/www/dokterku/artisan dokterku:performance-report --email=admin@yourdomain.com

# 5. Security audit
php /var/www/dokterku/artisan dokterku:security-audit

echo "$(date): Monthly maintenance completed" >> $LOG_FILE
```

### Setup Maintenance Cron Jobs

```bash
# Add to root crontab
sudo crontab -e

# Daily maintenance at 3 AM
0 3 * * * /usr/local/bin/dokterku-daily-maintenance.sh

# Weekly maintenance on Sunday at 4 AM
0 4 * * 0 /usr/local/bin/dokterku-weekly-maintenance.sh

# Monthly maintenance on 1st day of month at 5 AM
0 5 1 * * /usr/local/bin/dokterku-monthly-maintenance.sh
```

---

## Troubleshooting

### Common Issues dan Solutions

#### **1. Application Error 500**
```bash
# Check PHP error logs
sudo tail -f /var/log/php/dokterku-error.log

# Check Laravel logs
sudo tail -f /var/www/dokterku/storage/logs/laravel.log

# Check nginx error logs
sudo tail -f /var/log/nginx/error.log

# Clear all caches
cd /var/www/dokterku
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

#### **2. Database Connection Issues**
```bash
# Test database connection
mysql -u dokterku_user -p dokterku_production

# Check MySQL status
sudo systemctl status mysql

# Check MySQL error log
sudo tail -f /var/log/mysql/error.log

# Test from Laravel
php artisan tinker
> DB::connection()->getPdo();
```

#### **3. Queue Jobs Not Processing**
```bash
# Check queue worker status
sudo systemctl status dokterku-worker

# Check failed jobs
php artisan queue:failed

# Restart queue worker
sudo systemctl restart dokterku-worker

# Manual queue processing
php artisan queue:work --once
```

#### **4. High Memory Usage**
```bash
# Check memory usage
free -h
ps aux --sort=-%mem | head -10

# Check PHP memory limits
php -i | grep memory_limit

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

#### **5. SSL Certificate Issues**
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate manually
sudo certbot renew

# Test certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

### Performance Debugging

#### **Application Performance Issues**
```bash
# Enable Laravel Debugbar (development only)
composer require barryvdh/laravel-debugbar --dev

# Check slow queries
php artisan telescope:install  # If using Laravel Telescope

# Profile with Blackfire atau Xdebug
# Install Blackfire client
sudo apt install blackfire-agent
```

#### **Database Performance Issues**
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Check current processes
SHOW PROCESSLIST;

-- Check table locks
SHOW OPEN TABLES WHERE In_use > 0;

-- Analyze table performance
EXPLAIN SELECT * FROM pasien WHERE created_at > '2025-01-01';
```

### Security Incidents

#### **Security Incident Response**
```bash
# 1. Check for suspicious login attempts
grep "Failed password" /var/log/auth.log | tail -20

# 2. Check Laravel authentication logs
grep "authentication" /var/www/dokterku/storage/logs/laravel.log

# 3. Check for file modifications
find /var/www/dokterku -name "*.php" -mtime -1 -ls

# 4. Check active sessions
php artisan tinker
> DB::table('sessions')->count();

# 5. Force logout all users if needed
php artisan session:flush
```

#### **Malware Detection dan Cleanup**
```bash
# Install ClamAV antivirus
sudo apt install clamav clamav-daemon

# Update virus definitions
sudo freshclam

# Scan application directory
sudo clamscan -r /var/www/dokterku

# Check for suspicious patterns in code
grep -r "eval\|base64_decode\|shell_exec" /var/www/dokterku --include="*.php"
```

---

## Update dan Upgrade

### Application Updates

#### **Safe Update Process**
```bash
# 1. Create backup before update
/usr/local/bin/backup-dokterku.sh

# 2. Put application in maintenance mode
php artisan down --message="System update in progress"

# 3. Pull latest changes
git fetch
git checkout main
git pull origin main

# 4. Update dependencies
composer install --no-dev --optimize-autoloader

# 5. Run migrations
php artisan migrate --force

# 6. Update assets
npm install
npm run build

# 7. Clear dan rebuild caches
php artisan optimize:clear
php artisan optimize

# 8. Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart dokterku-worker

# 9. Test application
curl -I https://yourdomain.com

# 10. Bring application back online
php artisan up
```

#### **Automated Update Script**
```bash
#!/bin/bash
# /usr/local/bin/dokterku-update.sh

set -e  # Exit on any error

BACKUP_DIR="/var/backups/dokterku"
APP_DIR="/var/www/dokterku"
DATE=$(date +%Y%m%d_%H%M%S)

echo "Starting Dokterku update process..."

# 1. Create backup
echo "Creating backup..."
/usr/local/bin/backup-dokterku.sh

# 2. Maintenance mode
echo "Enabling maintenance mode..."
cd $APP_DIR
php artisan down --message="System update in progress"

# 3. Update application
echo "Updating application..."
git pull origin main

# 4. Update dependencies
echo "Updating dependencies..."
composer install --no-dev --optimize-autoloader

# 5. Run migrations
echo "Running database migrations..."
php artisan migrate --force

# 6. Build assets
echo "Building assets..."
npm install
npm run build

# 7. Optimize application
echo "Optimizing application..."
php artisan optimize:clear
php artisan optimize

# 8. Restart services
echo "Restarting services..."
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart dokterku-worker

# 9. Health check
echo "Performing health check..."
sleep 5
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://yourdomain.com)

if [ $HTTP_STATUS -eq 503 ]; then
    echo "Health check passed (maintenance mode)."
else
    echo "WARNING: Unexpected status code: $HTTP_STATUS"
fi

# 10. Disable maintenance mode
echo "Disabling maintenance mode..."
php artisan up

# 11. Final health check
sleep 5
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://yourdomain.com)

if [ $HTTP_STATUS -eq 200 ]; then
    echo "Update completed successfully!"
else
    echo "ERROR: Application not responding correctly. Status: $HTTP_STATUS"
    echo "Rolling back..."
    php artisan down
    # Restore from backup if needed
    exit 1
fi

echo "Dokterku update completed at $(date)"
```

### System Updates

#### **PHP Version Upgrade**
```bash
# Example: Upgrade from PHP 8.2 to 8.3

# 1. Install new PHP version
sudo apt update
sudo apt install php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip

# 2. Configure PHP-FPM pool for new version
sudo cp /etc/php/8.2/fpm/pool.d/dokterku.conf /etc/php/8.3/fpm/pool.d/dokterku.conf

# 3. Update Nginx configuration
sudo nano /etc/nginx/sites-available/dokterku
# Change: fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
# To: fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;

# 4. Test configuration
sudo nginx -t
php8.3 -v

# 5. Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo systemctl stop php8.2-fpm

# 6. Update Composer dan test application
cd /var/www/dokterku
php8.3 /usr/local/bin/composer install --no-dev
php8.3 artisan --version

# 7. Remove old PHP version (after testing)
sudo apt remove php8.2*
```

#### **Laravel Framework Upgrade**
```bash
# Example: Upgrade Laravel 11.x to newer version

# 1. Check current version
php artisan --version

# 2. Update composer.json
nano composer.json
# Update Laravel framework version constraint

# 3. Update dependencies
composer update

# 4. Check for breaking changes
# Review Laravel upgrade guide
# Test critical functionality

# 5. Update configuration files if needed
php artisan vendor:publish --tag=laravel-assets --force

# 6. Run tests
php artisan test

# 7. Deploy update using update script
/usr/local/bin/dokterku-update.sh
```

---

## Recovery Procedures

### Disaster Recovery

#### **Complete System Recovery**
```bash
#!/bin/bash
# /usr/local/bin/dokterku-recovery.sh

BACKUP_DATE="20250715_020000"  # Backup date to restore
BACKUP_DIR="/var/backups/dokterku"
APP_DIR="/var/www/dokterku"

echo "Starting disaster recovery process..."

# 1. Stop all services
sudo systemctl stop nginx
sudo systemctl stop php8.2-fpm
sudo systemctl stop dokterku-worker

# 2. Restore database
echo "Restoring database..."
mysql -u root -p -e "DROP DATABASE IF EXISTS dokterku_production;"
mysql -u root -p -e "CREATE DATABASE dokterku_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
gunzip -c $BACKUP_DIR/db_backup_$BACKUP_DATE.sql.gz | mysql -u root -p dokterku_production

# 3. Restore files
echo "Restoring files..."
sudo rm -rf $APP_DIR/storage/app/*
sudo tar -xzf $BACKUP_DIR/files_backup_$BACKUP_DATE.tar.gz -C /

# 4. Set permissions
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/storage

# 5. Clear caches
cd $APP_DIR
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 6. Start services
sudo systemctl start mysql
sudo systemctl start redis-server
sudo systemctl start php8.2-fpm
sudo systemctl start nginx
sudo systemctl start dokterku-worker

# 7. Verify recovery
sleep 10
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://yourdomain.com)

if [ $HTTP_STATUS -eq 200 ]; then
    echo "Recovery completed successfully!"
else
    echo "Recovery verification failed. Status: $HTTP_STATUS"
fi
```

### Point-in-Time Recovery

#### **Database Point-in-Time Recovery**
```bash
# If MySQL binary logging is enabled

# 1. Stop application
php artisan down

# 2. Restore from full backup
gunzip -c /var/backups/dokterku/db_backup_20250715_020000.sql.gz | mysql -u root -p dokterku_production

# 3. Apply binary logs from backup time to desired point
mysql -u root -p dokterku_production -e "SHOW BINARY LOGS;"

# 4. Apply specific binary log range
mysqlbinlog --start-datetime="2025-07-15 02:00:00" --stop-datetime="2025-07-15 14:30:00" /var/log/mysql/mysql-bin.000001 | mysql -u root -p dokterku_production

# 5. Verify data integrity
php artisan tinker
> \App\Models\Pasien::count();

# 6. Bring application back online
php artisan up
```

---

## Checklist Deployment

### Pre-Production Checklist

- [ ] **Server Requirements**
  - [ ] PHP 8.2+ dengan semua extensions
  - [ ] MySQL 8.0+ atau PostgreSQL 13+
  - [ ] Redis 6.0+
  - [ ] Nginx 1.18+
  - [ ] Node.js 18+ LTS

- [ ] **Security Configuration**
  - [ ] SSL certificate installed dan configured
  - [ ] Firewall rules configured
  - [ ] File permissions set correctly
  - [ ] Strong passwords untuk database dan Redis
  - [ ] APP_DEBUG=false
  - [ ] Secure session configuration

- [ ] **Application Setup**
  - [ ] Environment file configured
  - [ ] Database migrated dan seeded
  - [ ] Queue worker running
  - [ ] Scheduler configured
  - [ ] Storage linked
  - [ ] Assets built dan optimized

- [ ] **Monitoring Setup**
  - [ ] Log rotation configured
  - [ ] Health check scripts installed
  - [ ] Backup scripts configured
  - [ ] Alert email addresses configured

### Post-Deployment Checklist

- [ ] **Functional Testing**
  - [ ] Admin login works
  - [ ] All panels accessible
  - [ ] Database operations work
  - [ ] File uploads work
  - [ ] Email sending works
  - [ ] Queue jobs processing

- [ ] **Performance Testing**
  - [ ] Page load times < 3 seconds
  - [ ] Database queries optimized
  - [ ] Caching working correctly
  - [ ] Memory usage within limits

- [ ] **Security Testing**
  - [ ] SSL certificate valid
  - [ ] Security headers present
  - [ ] No debug information exposed
  - [ ] File permissions secure
  - [ ] Default passwords changed

- [ ] **Monitoring Verification**
  - [ ] Logs being written
  - [ ] Health checks running
  - [ ] Backup jobs scheduled
  - [ ] Email alerts configured

---

**📞 Need Help?**

Untuk bantuan deployment atau troubleshooting:
- **Technical Support**: tech-support@dokterku.com
- **Emergency Contact**: +62-xxx-xxx-xxxx
- **Documentation**: https://docs.dokterku.com

*Generated: 2025-07-15*  
*Version: 2.0.0*  
*Status: Documentation Phase - Operations Guide Complete*