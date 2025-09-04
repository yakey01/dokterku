# 📁 Upload Instructions - Dokterku Healthcare System

## 🎯 Overview
This guide provides step-by-step instructions for uploading the Dokterku Healthcare System to your Hostinger hosting account.

## 📋 Pre-Upload Preparation

### Files to Upload ✅
```
dokterku/
├── app/                    # Laravel application code
├── bootstrap/              # Laravel bootstrap files
├── config/                 # Configuration files
├── database/               # Migrations and seeders
├── lang/                   # Language files
├── public/                 # Public web files (CSS, JS, images)
├── resources/              # Views, CSS, JS source files
├── routes/                 # Route definitions
├── storage/                # App storage (logs, cache, uploads)
├── .env.production         # Production environment file
├── .htaccess              # Apache configuration
├── artisan                # Laravel command line tool
├── composer.json          # PHP dependencies
├── package.json           # Node.js dependencies
├── production-optimize.sh # Production optimization script
├── DEPLOYMENT-GUIDE.md    # Deployment documentation
└── DEPLOYMENT-CHECKLIST.md # Deployment checklist
```

### Files to EXCLUDE ❌
```
# DO NOT upload these files:
node_modules/              # Node.js dependencies (will be installed on server)
vendor/                    # Composer dependencies (will be installed on server)
.git/                      # Git repository files
tests/                     # Test files
storage/logs/*.log         # Log files
.env                       # Local environment file
.env.example              # Example environment file
```

## 🚀 Upload Methods

### Method 1: File Manager (Recommended for Hostinger)

#### Step 1: Access File Manager
1. Login to your Hostinger control panel
2. Go to **Files > File Manager**
3. Navigate to `domains/dokterkuklinik.com/public_html`

#### Step 2: Upload Files
1. **Delete existing files** in public_html (if any)
2. **Upload all files** from your local dokterku folder
3. **Maintain directory structure** exactly as shown above

#### Step 3: Set Permissions
```bash
# In File Manager, set permissions:
# Directories: 755
# Files: 644
# Special: storage/ and bootstrap/cache/ should be 755
```

### Method 2: FTP Upload

#### Step 1: FTP Connection
```
Host: ftp.dokterkuklinik.com
Username: your_ftp_username
Password: your_ftp_password
Port: 21
```

#### Step 2: Upload Process
1. Connect to FTP
2. Navigate to `public_html/`
3. Upload all files maintaining directory structure
4. Set permissions as described above

### Method 3: SSH Upload (Advanced)

#### Step 1: SSH Connection
```bash
ssh username@dokterkuklinik.com
```

#### Step 2: Upload via SCP
```bash
# From your local machine:
scp -r /path/to/dokterku/* username@dokterkuklinik.com:~/domains/dokterkuklinik.com/public_html/
```

## 📂 Directory Structure After Upload

Your `public_html` folder should look like this:
```
public_html/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Filament/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   └── Services/
├── bootstrap/
│   ├── cache/
│   └── providers.php
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite (will be replaced with MySQL)
├── lang/
├── public/
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── build/
│   ├── index.php
│   └── .htaccess
├── resources/
├── routes/
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── .env.production
├── .htaccess
├── artisan
├── composer.json
├── package.json
└── production-optimize.sh
```

## 🔧 Post-Upload Configuration

### Step 1: Environment Setup
1. **Rename environment file**:
   ```bash
   mv .env.production .env
   ```

2. **Edit .env file** with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=u454362045_u45436245_kli
   DB_USERNAME=u454362045_u45436245_kli
   DB_PASSWORD=KlinikApp2025!
   ```

### Step 2: Install Dependencies
```bash
# In your hosting terminal/SSH:
cd domains/dokterkuklinik.com/public_html

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm ci --omit=dev

# Build production assets
npm run build
```

### Step 3: Database Setup
```bash
# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔐 File Permissions

### Required Permissions
```bash
# Set these permissions after upload:
chmod 755 storage/
chmod 755 bootstrap/cache/
chmod 644 .env
chmod 644 .htaccess
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Hostinger File Manager Permissions
If using Hostinger File Manager:
1. Right-click on `storage` folder
2. Select "Permissions"
3. Set to 755 (rwxr-xr-x)
4. Apply to all subfolders
5. Repeat for `bootstrap/cache`

## 🌐 Web Server Configuration

### Apache Configuration (.htaccess)
Make sure this `.htaccess` file is in your `public_html` root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Handle Laravel public folder
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
    
    # Handle missing files
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/index.php [L]
</IfModule>
```

### Document Root Configuration
If you can configure document root in Hostinger:
- Set document root to: `domains/dokterkuklinik.com/public_html/public`
- This is more secure as it hides Laravel files

## 🧪 Testing After Upload

### Step 1: Basic Tests
1. **Visit your domain**: https://dokterkuklinik.com
2. **Check for errors**: No 500 errors should appear
3. **Test database**: Connection should work

### Step 2: Panel Access Tests
1. **Admin Panel**: https://dokterkuklinik.com/admin
2. **Manajer Panel**: https://dokterkuklinik.com/manajer
3. **Bendahara Panel**: https://dokterkuklinik.com/bendahara
4. **Petugas Panel**: https://dokterkuklinik.com/petugas
5. **Paramedis Panel**: https://dokterkuklinik.com/paramedis

### Step 3: Login Test
1. Go to any panel
2. Try to login with test credentials
3. Verify role-based access works

## 🚨 Common Issues & Solutions

### Issue 1: 500 Internal Server Error
**Solution**:
```bash
# Check file permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Check .env file
cat .env

# Check logs
tail -f storage/logs/laravel.log
```

### Issue 2: Database Connection Error
**Solution**:
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check database credentials in .env
cat .env | grep DB_
```

### Issue 3: Assets Not Loading
**Solution**:
```bash
# Rebuild assets
npm run build

# Check public folder permissions
ls -la public/

# Create storage link
php artisan storage:link
```

### Issue 4: Blank Page
**Solution**:
```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Check logs
tail -f storage/logs/laravel.log
```

## 📞 Support

### Hostinger Support
- **Control Panel**: Check hosting control panel for logs
- **Support Ticket**: Create ticket if issues persist
- **Documentation**: Check Hostinger Laravel documentation

### System Logs
- **Laravel Logs**: `storage/logs/laravel.log`
- **Web Server Logs**: Available in hosting control panel
- **Database Logs**: Available in hosting control panel

## ✅ Success Criteria

Your upload is successful when:
- [ ] ✅ Website loads without errors
- [ ] ✅ All 5 panels are accessible
- [ ] ✅ Database connection works
- [ ] ✅ Login system works
- [ ] ✅ No 500 errors in logs
- [ ] ✅ Assets load correctly
- [ ] ✅ Mobile interface works

## 🎉 Final Steps

### Step 1: Security Check
1. **Verify .env file** is not publicly accessible
2. **Check file permissions** are correct
3. **Test unauthorized access** is blocked

### Step 2: Performance Check
1. **Test page load speed** (should be < 3 seconds)
2. **Check database queries** are optimized
3. **Verify caching** is working

### Step 3: Backup Setup
1. **Setup database backups**
2. **Setup file backups**
3. **Test backup restoration**

---

**🏥 Dokterku Healthcare Management System**  
*Upload Instructions for Hostinger*  
*Laravel 11 + Filament 3.x Production Ready*

**Upload Date**: ___________  
**Uploaded By**: ___________  
**Status**: ___________