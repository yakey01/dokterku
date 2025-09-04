# 🚀 Dokterku Healthcare System - Deployment Workflow

Comprehensive deployment documentation for the Dokterku Healthcare Management System, a Laravel 11 application with Filament 3.x admin panels.

## 📋 Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Environment Configurations](#environment-configurations)
- [Docker Setup](#docker-setup)
- [Deployment Methods](#deployment-methods)
- [CI/CD Pipeline](#cicd-pipeline)
- [Production Optimization](#production-optimization)
- [Health Checks](#health-checks)
- [Troubleshooting](#troubleshooting)

## 🏥 Overview

The Dokterku Healthcare System deployment workflow provides:

- **Multi-environment support**: Development, Staging, Production
- **Docker containerization** for consistent deployments
- **Enhanced CI/CD pipeline** with security scanning and automated testing
- **Health monitoring** for all healthcare panels
- **Automated backups** and rollback capabilities
- **Production optimization** scripts for Laravel performance

### Healthcare Panels

- **Admin Panel**: System administration and overall management
- **Manajer Panel**: Management dashboard and reporting
- **Bendahara Panel**: Financial management and accounting
- **Petugas Panel**: General staff operations
- **Paramedis Panel**: Medical staff operations
- **Dokter Panel**: Doctor-specific functionality

## 🔧 Prerequisites

### System Requirements

- **Docker** 20.10+ and Docker Compose 2.0+
- **PHP** 8.3+ with required extensions
- **Node.js** 20+ with npm
- **Composer** 2.0+
- **MySQL** 8.0+ or **SQLite** for testing
- **Redis** (optional, for caching and sessions)

### Required PHP Extensions

```bash
php -m | grep -E "(mysql|pdo_mysql|redis|gd|curl|mbstring|xml|zip|bcmath|soap|intl|exif|iconv)"
```

## 🌍 Environment Configurations

### 1. Development Environment

```bash
cp .env.example .env
# Configure for local development
```

### 2. Staging Environment

```bash
cp .env.staging.example .env
# Update staging-specific values
```

### 3. Production Environment

```bash
cp .env.production.example .env
# Configure production settings with secure values
```

### 4. Testing Environment

```bash
cp .env.testing .env
# Optimized for automated testing with in-memory SQLite
```

## 🐳 Docker Setup

### Development Deployment

```bash
# Start development environment
docker-compose up -d

# View logs
docker-compose logs -f app

# Access application
http://localhost:8000
```

### Production Deployment

```bash
# Start production environment
docker-compose -f docker-compose.production.yml up -d

# Monitor services
docker-compose -f docker-compose.production.yml ps
```

### Docker Services

| Service | Purpose | Port |
|---------|---------|------|
| `app` | Laravel application | 8000 |
| `mysql` | Database server | 3306 |
| `redis` | Cache and sessions | 6379 |
| `nginx` | Reverse proxy | 80/443 |
| `queue` | Queue worker | - |
| `scheduler` | Task scheduler | - |

## 🚀 Deployment Methods

### 1. Automated Deployment Script

```bash
# Deploy to production
./deploy.sh production

# Deploy to staging
./deploy.sh staging

# Deploy to development
./deploy.sh
```

### 2. Manual Deployment Steps

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Set up environment
cp .env.production .env
php artisan key:generate

# 4. Run migrations
php artisan migrate --force

# 5. Optimize for production
./scripts/production-optimize.sh

# 6. Start services
docker-compose -f docker-compose.production.yml up -d
```

### 3. GitHub Actions Deployment

Push to `main` branch triggers automatic production deployment:

```yaml
# Manual deployment via GitHub Actions
gh workflow run "Enhanced Deployment Pipeline" \
  --field environment=production \
  --field force_deploy=false
```

## 🔄 CI/CD Pipeline

### Workflow Triggers

- **Push to `main`**: Production deployment
- **Push to `develop`**: Staging deployment
- **Pull Request**: Testing and validation
- **Manual trigger**: Custom environment deployment

### Pipeline Stages

1. **Security Scan**
   - Code vulnerability scanning
   - Secret detection
   - SARIF report generation

2. **Build & Test**
   - PHP and Node.js setup
   - Dependency installation
   - Database migrations
   - Frontend asset building
   - Comprehensive testing suite
   - Code coverage reporting

3. **Deployment**
   - Environment-specific deployment
   - Health checks for all panels
   - Automated rollback on failure

### Required GitHub Secrets

```bash
# Production Environment
PRODUCTION_HOST=your-production-server.com
PRODUCTION_USERNAME=deploy_user
PRODUCTION_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
PRODUCTION_SSH_PORT=22
PRODUCTION_PATH=/var/www/dokterku
PRODUCTION_URL=https://dokterkuklinik.com

# Staging Environment
STAGING_HOST=staging-server.com
STAGING_USERNAME=deploy_user
STAGING_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
STAGING_SSH_PORT=22
STAGING_PATH=/var/www/dokterku-staging
STAGING_URL=https://staging.dokterkuklinik.com

# Database Credentials
DB_USERNAME=dokterku_user
DB_PASSWORD=secure_database_password
DB_DATABASE=dokterku_production
```

## ⚡ Production Optimization

### Automated Optimization Script

```bash
./scripts/production-optimize.sh
```

### Manual Optimizations

```bash
# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Composer autoloader
composer dump-autoload --optimize --classmap-authoritative

# Publish Filament assets
php artisan vendor:publish --tag=filament-assets --force

# Set proper permissions
chmod -R 755 storage bootstrap/cache
```

### Performance Checklist

- ✅ Configuration caching enabled
- ✅ Route caching enabled
- ✅ View caching enabled
- ✅ Composer autoloader optimized
- ✅ Debug mode disabled
- ✅ HTTPS enforced
- ✅ Database indexes optimized
- ✅ Redis caching configured

## 🏥 Health Checks

### Application Health Endpoint

```bash
curl -s http://localhost:8000/health
```

### Panel Accessibility Checks

```bash
# Admin Panel
curl -s http://localhost:8000/admin

# Manajer Panel
curl -s http://localhost:8000/manajer

# Bendahara Panel
curl -s http://localhost:8000/bendahara

# Petugas Panel
curl -s http://localhost:8000/petugas

# Paramedis Panel
curl -s http://localhost:8000/paramedis

# Dokter Panel
curl -s http://localhost:8000/dokter
```

### Service Health Monitoring

```bash
# Database connectivity
php artisan migrate:status

# Cache functionality
php artisan tinker --execute="Cache::put('test', 'value'); echo Cache::get('test');"

# Queue system
php artisan queue:work --once

# Storage permissions
php artisan storage:link
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Database Connection Failed

```bash
# Check database credentials
grep DB_ .env

# Test database connection
php artisan migrate:status

# Reset database
php artisan migrate:fresh --force
```

#### 2. Permission Errors

```bash
# Fix storage permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 3. Asset Loading Issues

```bash
# Rebuild assets
npm run build

# Clear view cache
php artisan view:clear

# Republish Filament assets
php artisan vendor:publish --tag=filament-assets --force
```

#### 4. Docker Container Issues

```bash
# View container logs
docker-compose logs app

# Restart services
docker-compose restart

# Rebuild containers
docker-compose build --no-cache
```

### Debug Commands

```bash
# View application logs
tail -f storage/logs/laravel.log

# Check service status
docker-compose ps

# Monitor resource usage
docker stats

# Database queries log
php artisan telescope:publish
```

### Recovery Procedures

#### Rollback Deployment

```bash
# Using deployment script
./deploy.sh production --rollback

# Manual rollback
git checkout HEAD~1
./deploy.sh production
```

#### Database Recovery

```bash
# Restore from backup
mysql -u username -p database_name < backup.sql

# Or using Docker
docker-compose exec -T mysql mysql -u root -p database_name < backup.sql
```

## 📊 Monitoring & Maintenance

### Log Monitoring

```bash
# Application logs
tail -f storage/logs/laravel.log

# Docker logs
docker-compose logs -f

# System logs
journalctl -u docker -f
```

### Performance Monitoring

```bash
# Database performance
EXPLAIN SELECT * FROM users WHERE role_id = 1;

# Queue monitoring
php artisan queue:monitor redis:default

# Cache hit ratio
redis-cli info stats | grep keyspace
```

### Backup Strategy

```bash
# Database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Application backup
tar -czf app_backup_$(date +%Y%m%d).tar.gz \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.git \
  .
```

### Regular Maintenance

- Daily: Log rotation, cache optimization
- Weekly: Database optimization, backup verification
- Monthly: Security updates, dependency updates
- Quarterly: Performance audits, capacity planning

## 🔐 Security Considerations

### Production Security Checklist

- ✅ HTTPS enforced with valid SSL certificates
- ✅ Environment variables secured
- ✅ Database credentials rotated regularly
- ✅ SSH keys managed securely
- ✅ Regular security updates applied
- ✅ Firewall configured properly
- ✅ Backup encryption enabled
- ✅ Access logs monitored

### Security Headers

```nginx
# In Nginx configuration
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

## 📞 Support

### Documentation Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Docker Documentation](https://docs.docker.com)

### Healthcare System Specific

- Panel access issues: Check user roles and permissions
- GPS attendance problems: Verify location settings
- Financial module errors: Check validation rules
- Telegram notifications: Verify bot configuration

### Emergency Contacts

- **System Administrator**: admin@dokterkuklinik.com
- **DevOps Team**: devops@dokterkuklinik.com
- **Emergency Hotline**: +62-21-EMERGENCY

---

**🏥 Dokterku Healthcare System** - Comprehensive healthcare management with robust deployment infrastructure.

*Generated with deployment workflow automation - Last updated: $(date)*