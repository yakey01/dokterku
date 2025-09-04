# Hosting Troubleshooting Guide

## 🔍 Cara Mengatasi Masalah Pail di Hosting

### Masalah yang Sering Terjadi
1. **PailServiceProvider not found** - Service provider tidak terdaftar
2. **Class not found** - Package tidak terinstall dengan benar
3. **Permission denied** - Masalah permission file
4. **Cache issues** - Cache Laravel yang bermasalah

### 📋 Langkah-langkah Troubleshooting

#### 1. Akses Terminal Hosting
```bash
# Masuk ke terminal hosting Anda
ssh username@your-hosting-server.com
# atau gunakan terminal yang disediakan hosting
```

#### 2. Navigasi ke Project Laravel
```bash
# Ganti dengan path yang benar sesuai hosting Anda
cd /path/to/your/laravel/project
# Contoh: cd /home/username/public_html
# atau: cd /var/www/html/your-project
```

#### 3. Jalankan Script Troubleshooting
```bash
# Upload file hosting-troubleshoot.sh ke hosting Anda
chmod +x hosting-troubleshoot.sh
./hosting-troubleshoot.sh
```

#### 4. Atau Jalankan Perintah Manual

**Check Environment:**
```bash
php --version
composer --version
php artisan --version
```

**Check Pail Installation:**
```bash
composer show laravel/pail
```

**Install Pail jika belum ada:**
```bash
composer require --dev laravel/pail
```

**Clear All Caches:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**Regenerate Autoload:**
```bash
composer dump-autoload
```

**Publish Pail Config:**
```bash
php artisan vendor:publish --tag=pail-config --force
```

**Test Pail:**
```bash
php artisan pail --help
```

### 🔧 Solusi Khusus

#### Jika Pail Tidak Terinstall
```bash
# Install Pail
composer require --dev laravel/pail

# Clear caches
php artisan optimize:clear

# Regenerate autoload
composer dump-autoload
```

#### Jika Ada Error Permission
```bash
# Set permission yang benar
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Jika Ada Error Service Provider
```bash
# Check service providers
cat bootstrap/providers.php

# Pail seharusnya auto-discover, tidak perlu manual register
# Jika ada error, coba:
composer update
php artisan config:clear
```

#### Jika Masih Error
```bash
# Check Laravel logs
tail -n 50 storage/logs/laravel.log

# Check .env settings
grep -E 'APP_ENV|APP_DEBUG' .env

# Pastikan production settings
APP_ENV=production
APP_DEBUG=false
```

### 📞 Troubleshooting Lanjutan

#### Check PHP Extensions
```bash
php -m | grep -E 'mbstring|xml|curl|json|openssl'
```

#### Check Composer Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

#### Check Laravel Requirements
```bash
php artisan about
```

### 🚨 Common Hosting Issues

1. **Shared Hosting Limitations**
   - Beberapa hosting tidak mendukung `composer require --dev`
   - Solusi: Install Pail di local, upload vendor folder

2. **PHP Version Compatibility**
   - Pastikan PHP 8.2+ untuk Laravel 11
   - Check: `php --version`

3. **Memory Limits**
   - Jika ada memory limit error
   - Solusi: `php -d memory_limit=512M artisan command`

4. **File Permissions**
   - Storage dan bootstrap/cache harus writable
   - Solusi: `chmod -R 755 storage bootstrap/cache`

### 📱 Testing di Production

Setelah fix, test dengan:

```bash
# Test basic Laravel
php artisan --version

# Test Pail
php artisan pail --help

# Test website
curl -I https://your-domain.com
```

### 🔄 Backup dan Restore

**Sebelum melakukan perubahan:**
```bash
# Backup current state
cp -r vendor vendor.backup
cp .env .env.backup
```

**Jika ada masalah:**
```bash
# Restore backup
rm -rf vendor
mv vendor.backup vendor
cp .env.backup .env
```

### 📞 Support

Jika masih ada masalah:
1. Check Laravel logs: `tail -n 100 storage/logs/laravel.log`
2. Check hosting error logs
3. Contact hosting provider untuk PHP/Composer support
4. Pastikan hosting mendukung Laravel 11 requirements

---

**Note:** Script-script di atas sudah dibuat dan siap digunakan. Upload ke hosting Anda dan jalankan sesuai kebutuhan. 