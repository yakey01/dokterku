# 🚨 Emergency Admin Recovery Guide

## 🔥 EMERGENCY: Cannot Access Admin Panel

### Quick Recovery Steps

1. **SSH into server**:
   ```bash
   ssh username@dokterkuklinik.com
   cd domains/dokterkuklinik.com/public_html/dokterku
   ```

2. **Check current admin status**:
   ```bash
   php artisan admin:replace --verify
   ```

3. **If verification fails, try rollback**:
   ```bash
   php artisan admin:replace --rollback --force
   ```

4. **If rollback fails, restore from backup**:
   ```bash
   # Find latest backup
   ls -la ~/backups/admin_replacement/
   
   # Restore database
   mysql -h localhost -u [username] -p [database] < ~/backups/admin_replacement/[latest]/full_database_backup.sql
   ```

5. **Create emergency admin**:
   ```bash
   php artisan user:create-admin
   ```

## 🆘 Database Corruption

### Manual Admin Creation

```sql
-- Connect to MySQL
mysql -h localhost -u [username] -p [database]

-- Create admin role if missing
INSERT INTO roles (name, display_name, description, guard_name, is_active, created_at, updated_at) 
VALUES ('admin', 'Administrator', 'System Administrator', 'web', 1, NOW(), NOW());

-- Get role ID
SELECT id FROM roles WHERE name = 'admin';

-- Create admin user (replace [role_id] with actual ID)
INSERT INTO users (name, email, username, password, role_id, is_active, email_verified_at, created_at, updated_at)
VALUES ('Emergency Admin', 'admin@dokterku.com', 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', [role_id], 1, NOW(), NOW(), NOW());
```

## 🔧 GitHub Actions Failed

### Manual Deployment Recovery

1. **Check deployment logs** in GitHub Actions
2. **SSH into server** and check application status
3. **Clear caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```
4. **Test database connection**:
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

## 📞 Emergency Contacts

- **Hostinger Support**: [Access via cPanel]
- **Database Access**: cPanel → MySQL Databases
- **File Manager**: cPanel → File Manager
- **Backup Location**: `~/backups/admin_replacement/`

## ⚡ Quick Commands Reference

```bash
# Verify admin setup
php artisan admin:replace --verify

# Rollback admin users
php artisan admin:replace --rollback --force

# Create new admin
php artisan user:create-admin

# Check database connection
php artisan tinker
DB::connection()->getPdo();

# View logs
tail -f storage/logs/laravel.log

# Check migration status
php artisan migrate:status
```

## 🔒 Emergency Admin Credentials

**Default credentials** (change immediately after access):
- Email: `admin@dokterku.com`
- Username: `admin`
- Password: `admin123` or `dokterku_admin_2024`

## 📋 Recovery Checklist

- [ ] SSH access working
- [ ] Database connection established
- [ ] Admin user exists in database
- [ ] Admin panel accessible
- [ ] Application logs checked
- [ ] Backup restoration (if needed)
- [ ] New password set
- [ ] System functionality verified

---

**⚠️ Always change default passwords immediately after emergency recovery!**