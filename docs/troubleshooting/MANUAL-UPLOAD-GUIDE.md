# 🚀 MANUAL UPLOAD GUIDE - HOSTINGER

## File yang Sudah Disiapkan
✅ **File**: `/tmp/dokterku-clean.zip` (Ready untuk upload)
✅ **Size**: ~80MB (sudah optimized)
✅ **Content**: Laravel application dengan dependencies

## 📋 LANGKAH-LANGKAH UPLOAD

### STEP 1: Upload File ke Hostinger
1. **Buka Hostinger File Manager**
   - Login ke Hostinger control panel
   - Klik **File Manager**
   - Masuk ke `public_html` directory

2. **Upload File**
   - Klik **Upload** button
   - Pilih file `/tmp/dokterku-clean.zip`
   - Tunggu upload selesai

3. **Extract File**
   - Right-click pada `dokterku-clean.zip`
   - Pilih **Extract**
   - Extract ke folder `public_html`
   - Pindahkan semua file dari folder `dokterku-clean` ke root `public_html`

### STEP 2: Install Dependencies via Terminal
```bash
# SSH ke server
ssh u454362045@dokterkuklinik.com

# Masuk ke directory
cd ~/public_html

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies dan build assets
npm install
npm run build

# Generate Laravel application key
php artisan key:generate --force

# Set file permissions
chmod -R 755 storage bootstrap/cache
chmod 644 .env

# Run database migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Test deployment
php artisan about
```

### STEP 3: Verify Website
1. **Check website**: https://dokterkuklinik.com
2. **Check admin**: https://dokterkuklinik.com/admin
3. **Check other panels**:
   - https://dokterkuklinik.com/manajer
   - https://dokterkuklinik.com/bendahara
   - https://dokterkuklinik.com/petugas
   - https://dokterkuklinik.com/paramedis

## 🔧 TROUBLESHOOTING

### Jika Website Masih 403:
```bash
# Set proper permissions
find ~/public_html -type d -exec chmod 755 {} \;
find ~/public_html -type f -exec chmod 644 {} \;
chmod -R 775 ~/public_html/storage ~/public_html/bootstrap/cache
```

### Jika Ada Error Database:
```bash
# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Re-run migrations
php artisan migrate:fresh --seed
```

### Jika CSS/JS Tidak Load:
```bash
# Rebuild assets
npm run build

# Re-link storage
php artisan storage:link

# Clear cache
php artisan cache:clear
php artisan view:clear
```

## 🎯 FINAL CHECKLIST

- [ ] File uploaded dan extracted
- [ ] Dependencies installed (composer + npm)
- [ ] Database migrated
- [ ] Storage linked
- [ ] Cache optimized
- [ ] Website accessible
- [ ] All 5 panels working
- [ ] Login system working

## 📞 SUPPORT

Jika masih ada masalah, check:
1. **Error logs**: `storage/logs/laravel.log`
2. **Web server logs**: Check via Hostinger control panel
3. **Database connection**: Verify credentials di `.env`

---

**Ready to go!** 🚀