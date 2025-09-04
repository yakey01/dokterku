# 🚀 Dokterku Deployment Setup Guide

## Overview
This guide helps you configure the comprehensive CI/CD pipeline for the Dokterku Healthcare Management System.

## GitHub Repository Secrets Configuration

### 1. Staging Environment Secrets

Navigate to your GitHub repository → Settings → Secrets and variables → Actions, then add:

#### Server Connection
```
STAGING_HOST=your-staging-server.com
STAGING_USERNAME=dokterku-deploy
STAGING_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
...your-private-key...
-----END OPENSSH PRIVATE KEY-----
STAGING_SSH_PORT=22
STAGING_PATH=/var/www/dokterku-staging
STAGING_URL=https://staging.dokterku.com
```

#### Database Configuration
```
STAGING_DB_HOST=localhost
STAGING_DB_PORT=3306
STAGING_DB_DATABASE=dokterku_staging
STAGING_DB_USERNAME=dokterku_staging_user
STAGING_DB_PASSWORD=secure_staging_password
```

### 2. Production Environment Secrets

#### Server Connection
```
PRODUCTION_HOST=your-production-server.com
PRODUCTION_USERNAME=dokterku-deploy
PRODUCTION_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
...your-private-key...
-----END OPENSSH PRIVATE KEY-----
PRODUCTION_SSH_PORT=22
PRODUCTION_PATH=/var/www/dokterku-production
PRODUCTION_URL=https://dokterku.com
```

#### Database Configuration
```
PRODUCTION_DB_HOST=localhost
PRODUCTION_DB_PORT=3306
PRODUCTION_DB_DATABASE=dokterku_production
PRODUCTION_DB_USERNAME=dokterku_prod_user
PRODUCTION_DB_PASSWORD=ultra_secure_production_password
```

## Environment Setup

### 1. GitHub Environment Protection Rules

#### Staging Environment
1. Go to Settings → Environments → New environment
2. Name: `staging`
3. Configure protection rules:
   - ✅ Required reviewers: 1 reviewer minimum
   - ✅ Wait timer: 0 minutes
   - ✅ Restrict to specific branches: `develop`

#### Production Environment
1. Go to Settings → Environments → New environment
2. Name: `production`
3. Configure protection rules:
   - ✅ Required reviewers: 2 reviewers minimum (for healthcare compliance)
   - ✅ Wait timer: 10 minutes (cooling period)
   - ✅ Restrict to specific branches: `main`

### 2. Server Preparation

#### Create Deployment User
```bash
# On your servers (staging & production)
sudo adduser dokterku-deploy
sudo usermod -aG docker dokterku-deploy
sudo usermod -aG www-data dokterku-deploy

# Setup SSH key authentication
sudo mkdir -p /home/dokterku-deploy/.ssh
sudo nano /home/dokterku-deploy/.ssh/authorized_keys
# Paste your public key here
sudo chmod 700 /home/dokterku-deploy/.ssh
sudo chmod 600 /home/dokterku-deploy/.ssh/authorized_keys
sudo chown -R dokterku-deploy:dokterku-deploy /home/dokterku-deploy/.ssh
```

#### Directory Structure
```bash
# Create deployment directories
sudo mkdir -p /var/www/dokterku-staging
sudo mkdir -p /var/www/dokterku-production
sudo mkdir -p /var/www/dokterku-staging/backups
sudo mkdir -p /var/www/dokterku-production/backups

# Set permissions
sudo chown -R dokterku-deploy:www-data /var/www/dokterku-*
sudo chmod -R 755 /var/www/dokterku-*
```

### 3. Docker & Docker Compose Setup

```bash
# Install Docker (if not already installed)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker dokterku-deploy

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

## Deployment Workflow Features

### 🔒 Security Analysis
- **Trivy Scanner**: Vulnerability detection in dependencies
- **TruffleHog**: Secret detection with verification
- **Custom Scans**: Hardcoded credential detection in PHP files

### 🧪 Testing Pipeline
- **Multi-Service Testing**: MySQL 8.0 + Redis 7
- **Code Quality**: PHPStan static analysis + PHPCS style checking
- **Coverage**: 60% minimum test coverage requirement
- **Panel Testing**: All 6 healthcare panels accessibility validation

### 🐳 Container Management
- **Multi-Platform**: AMD64 & ARM64 builds
- **Registry**: GitHub Container Registry (GHCR)
- **Caching**: Optimized build caching

### 🚀 Deployment Stages
1. **Develop Branch** → Staging Environment (auto-deploy)
2. **Main Branch** → Production Environment (with approvals)
3. **Manual Dispatch** → Choose environment + emergency options

### 🏥 Healthcare-Specific Features
- **Panel Validation**: Admin, Manajer, Bendahara, Petugas, Paramedis, Dokter
- **API Testing**: Authentication and health endpoints
- **Compliance**: Multi-reviewer production approvals

### 🔄 Emergency Capabilities
- **Rollback**: Automatic rollback on production failures
- **Backups**: Database + storage backup before deployments
- **Force Deploy**: Skip tests for emergency fixes
- **Health Checks**: Comprehensive post-deployment validation

## Deployment Commands

### Manual Deployment
```bash
# Trigger manual deployment via GitHub Actions
# Go to: Actions → Modern CI/CD Pipeline → Run workflow
# Select:
# - Environment: staging/production
# - Skip tests: false (unless emergency)
# - Force deploy: false (unless critical)
```

### Emergency Procedures

#### Emergency Deployment (Skip Tests)
```yaml
# In workflow dispatch:
environment: production
skip_tests: true
force_deploy: true
```

#### Manual Rollback
```bash
# SSH to server
ssh dokterku-deploy@your-server.com
cd /var/www/dokterku-production

# Find available backups
ls -la backups/

# The rollback job will automatically run if production deployment fails
# Or manually trigger rollback workflow if needed
```

## Health Check Endpoints

Your application should provide these endpoints:

```php
// routes/web.php or api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::store()->getStore() ? 'connected' : 'disconnected',
    ]);
});

Route::get('/api/health', function () {
    return response()->json(['status' => 'ok', 'api' => 'healthy']);
});
```

## Monitoring & Alerts

### Log Locations
- **Staging**: `/var/www/dokterku-staging/storage/logs/`
- **Production**: `/var/www/dokterku-production/storage/logs/`
- **Docker**: `docker-compose logs -f`

### Key Metrics to Monitor
- Response times for all 6 healthcare panels
- Database connection health
- Redis cache performance
- Storage space and backup integrity
- SSL certificate expiration

## Troubleshooting

### Common Issues

#### 1. SSH Connection Failed
```bash
# Test SSH connection
ssh -i ~/.ssh/your-key dokterku-deploy@your-server.com

# Check SSH key format (should be OpenSSH format)
ssh-keygen -p -m OpenSSH -f ~/.ssh/your-key
```

#### 2. Docker Permission Denied
```bash
# Add user to docker group
sudo usermod -aG docker dokterku-deploy
# Logout and login again
```

#### 3. Database Connection Failed
```bash
# Test database connection
docker-compose exec mysql mysql -u username -p database_name
```

#### 4. Health Check Failed
```bash
# Check application logs
docker-compose logs -f app

# Test health endpoint manually
curl -I https://your-domain.com/health
```

### Emergency Contacts
- **DevOps Lead**: [your-devops-email]
- **Database Admin**: [your-dba-email]
- **Security Team**: [your-security-email]

## Compliance Notes

For healthcare applications, ensure:
- ✅ All deployments are logged and auditable
- ✅ Production changes require multiple approvals
- ✅ Sensitive data is properly encrypted
- ✅ Backup procedures are tested regularly
- ✅ Access logs are maintained for compliance

---

**Next Steps**: Configure your GitHub secrets and test the deployment pipeline with a staging deployment first.