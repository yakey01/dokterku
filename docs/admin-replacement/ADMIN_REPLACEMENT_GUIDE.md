# 🔐 Admin User Replacement Guide

## Overview

This guide covers the complete process for safely replacing admin users in the Hostinger production environment with localhost admin credentials via GitHub Actions deployment.

## 🎯 What This Process Does

1. **Backup**: Creates comprehensive database backups before any changes
2. **Remove**: Safely soft-deletes existing admin users (preserves data for rollback)
3. **Replace**: Creates new admin user with localhost specifications
4. **Verify**: Confirms new admin can access the system
5. **Rollback**: Automatic recovery if anything goes wrong

## 🚀 How to Execute Admin Replacement

### Method 1: GitHub Actions (Recommended)

1. Go to **Actions** tab in GitHub repository
2. Select **"Replace Admin Users (Production Only)"** workflow
3. Click **"Run workflow"**
4. Fill in the required parameters:
   - **Admin Email**: Default is `admin@dokterku.com`
   - **Admin Name**: Default is `Administrator`
   - **Confirmation**: Type `REPLACE_ADMIN_USERS` exactly
   - **Enable rollback**: Keep checked for safety
5. Click **"Run workflow"**

### Method 2: SSH Command Line

```bash
# SSH into Hostinger
ssh username@dokterkuklinik.com

# Navigate to application directory
cd domains/dokterkuklinik.com/public_html/dokterku

# Run admin replacement command
php artisan admin:replace --email=admin@dokterku.com --name="Administrator"

# Verify the replacement
php artisan admin:replace --verify
```

## 🔧 Available Commands

### Admin Replacement Command

```bash
# Basic replacement
php artisan admin:replace

# With custom parameters
php artisan admin:replace --email=custom@email.com --name="Custom Admin" --password=newpassword

# Force replacement without confirmation
php artisan admin:replace --force

# Verify current admin setup
php artisan admin:replace --verify

# Rollback to previous admin users
php artisan admin:replace --rollback
```

### Database Seeder

```bash
# Run production admin seeder directly
php artisan db:seed --class=ProductionAdminReplacementSeeder --force
```

## 🛡️ Safety Mechanisms

### 1. Backup System
- **Full database backup** created before any changes
- **Admin-specific table backup** for quick recovery
- **Application state backup** (environment files, etc.)
- Backups stored in `~/backups/admin_replacement/` with timestamps

### 2. Soft Delete Protection
- Existing admin users are **soft deleted**, not permanently removed
- Original data preserved in `admin_users_backup` table
- Complete rollback possible at any time

### 3. Transaction Safety
- All database operations wrapped in transactions
- Automatic rollback on any failure
- Atomic operations ensure data consistency

### 4. Verification Steps
- Database connection testing
- Admin panel access verification
- Role and permission validation
- Health checks after deployment

### 5. Emergency Rollback
- Automatic rollback on deployment failure
- Manual rollback command available
- Backup restoration process documented

## 📊 Environment Variables

Add these to your `.env` file for customization:

```env
# Production Admin Credentials
PRODUCTION_ADMIN_EMAIL=admin@dokterku.com
PRODUCTION_ADMIN_NAME="Administrator"
PRODUCTION_ADMIN_USERNAME=admin
PRODUCTION_ADMIN_PASSWORD=your_secure_password_here

# Deployment Control
DEPLOY_WITH_ADMIN_REPLACEMENT=false
```

## 🔍 Verification Process

After replacement, verify the following:

### 1. Admin Panel Access
- Visit: `https://dokterkuklinik.com/admin`
- Login with new credentials
- Check dashboard loads correctly

### 2. Database Verification
```sql
-- Check admin user exists
SELECT id, name, email, role_id FROM users WHERE email = 'admin@dokterku.com';

-- Check role assignment
SELECT u.name, u.email, r.name as role_name 
FROM users u 
JOIN roles r ON u.role_id = r.id 
WHERE r.name = 'admin';
```

### 3. Command Line Verification
```bash
php artisan admin:replace --verify
```

## 🚨 Troubleshooting

### Problem: Admin Replacement Failed

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Verify database connection
php artisan admin:replace --verify

# Attempt rollback
php artisan admin:replace --rollback --force
```

### Problem: New Admin Cannot Access Panel

**Solution:**
```bash
# Check user exists
php artisan tinker
User::where('email', 'admin@dokterku.com')->first();

# Check role assignment
$admin = User::where('email', 'admin@dokterku.com')->first();
$admin->role;
$admin->canAccessPanel(filament('admin')->getPanel());
```

### Problem: Migration Failed

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Restore from backup
mysql -h localhost -u username -p database_name < ~/backups/admin_replacement/latest/full_database_backup.sql

# Re-run migration
php artisan migrate --force
```

## 📋 Rollback Process

### Automatic Rollback
- Triggered automatically on deployment failure
- Restores from database backup
- Recreates original admin users

### Manual Rollback
```bash
# Using artisan command
php artisan admin:replace --rollback --force

# Using migration rollback
php artisan migrate:rollback --step=1 --force

# Manual database restore
mysql -h localhost -u username -p database_name < backup_file.sql
```

## 📈 Monitoring and Logs

### Application Logs
```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Check admin replacement logs
grep "AdminReplacement" storage/logs/laravel.log
```

### Database Logs
```sql
-- Check admin replacement activity
SELECT * FROM admin_replacement_logs ORDER BY created_at DESC LIMIT 10;

-- Check backup records
SELECT * FROM admin_users_backup ORDER BY backed_up_at DESC;
```

### GitHub Actions Logs
- View workflow execution in GitHub Actions tab
- Download logs for detailed troubleshooting
- Check individual job outputs

## 🔒 Security Considerations

1. **Change Default Password**: Always change the default password after first login
2. **Enable 2FA**: Set up two-factor authentication if available
3. **Review Permissions**: Ensure admin has appropriate permissions only
4. **Monitor Access**: Check admin login logs regularly
5. **Backup Security**: Secure backup files with appropriate permissions

## 📞 Support

If you encounter issues:

1. **Check Logs**: Review application and deployment logs
2. **Run Verification**: Use `php artisan admin:replace --verify`
3. **Emergency Rollback**: Use rollback commands if needed
4. **Manual Recovery**: Follow manual recovery steps in troubleshooting

## 🚀 Post-Replacement Checklist

- [ ] Admin panel accessible at `/admin`
- [ ] New admin credentials work
- [ ] All admin functions operational
- [ ] Password changed from default
- [ ] Backup verification completed
- [ ] System health check passed
- [ ] Documentation updated with new credentials

---

*This admin replacement system was designed with security and reliability as top priorities. Always test in staging environment first when possible.*