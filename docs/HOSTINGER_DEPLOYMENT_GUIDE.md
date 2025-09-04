# 🚀 Panduan Deployment ke Hostinger

## 📋 Prerequisites

Sebelum deployment, pastikan Anda memiliki:
- ✅ Akun Hostinger dengan akses SSH
- ✅ Domain `dokterkuklinik.com` sudah terkonfigurasi
- ✅ Repository GitHub dengan workflow yang sudah disetup

## 🔐 Konfigurasi GitHub Secrets

Buka repository GitHub Anda dan pergi ke **Settings > Secrets and variables > Actions**, lalu tambahkan secrets berikut:

### 1. SSH Connection Secrets
```
HOST = [IP Address Hostinger Anda]
USERNAME = [Username SSH Hostinger]
SSH_PRIVATE_KEY = [Private Key SSH Anda]
SSH_PORT = 22
```

### 2. Cara Mendapatkan SSH Credentials di Hostinger

#### Langkah 1: Aktifkan SSH di Hostinger
1. Login ke Hostinger Control Panel
2. Buka **Advanced > SSH Access**
3. Aktifkan SSH Access
4. Catat IP Address dan Username

#### Langkah 2: Generate SSH Key
```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "your-email@example.com"

# Copy public key ke Hostinger
cat ~/.ssh/id_rsa.pub
```

#### Langkah 3: Setup SSH Key di Hostinger
1. Buka **Advanced > SSH Access**
2. Klik **Manage SSH Keys**
3. Tambahkan public key yang sudah di-generate
4. Copy private key untuk GitHub Secrets

## 🏗️ Struktur Direktori di Hostinger

Pastikan struktur direktori di Hostinger seperti ini:
```
domains/
└── dokterkuklinik.com/
    └── public_html/
        └── dokterku/          # Root aplikasi Laravel
            ├── app/
            ├── public/
            ├── storage/
            ├── bootstrap/
            └── ...
```

## 🔄 Workflow Deployment

### Workflow yang Tersedia:

1. **`deploy-to-hostinger.yml`** - Workflow khusus untuk Hostinger
   - ✅ Build assets di GitHub Actions
   - ✅ Deploy via SSH ke Hostinger
   - ✅ Optimized untuk production

2. **`build.yml`** - Workflow build dan deploy
   - ✅ Build dan deploy dalam satu job
   - ✅ Cache optimization

### Trigger Deployment:

#### Otomatis (Push ke main branch):
```bash
git push origin main
```

#### Manual (via GitHub UI):
1. Buka repository di GitHub
2. Klik tab **Actions**
3. Pilih workflow **Deploy to Hostinger**
4. Klik **Run workflow**

## 📁 File Konfigurasi Penting

### 1. `.env` di Hostinger
Pastikan file `.env` di server Hostinger sudah dikonfigurasi dengan benar:

```env
APP_NAME="Dokterku Klinik"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dokterkuklinik.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 2. Permissions
Setelah deployment, pastikan permissions sudah benar:
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 🐛 Troubleshooting

### Error: Permission Denied
```bash
# Di server Hostinger
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Error: Composer Dependencies
```bash
# Di server Hostinger
composer install --no-dev --ignore-platform-reqs --optimize-autoloader
```

### Error: Database Migration
```bash
# Di server Hostinger
php artisan migrate --force
```

### Error: Cache Issues
```bash
# Di server Hostinger
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## 📊 Monitoring Deployment

### 1. GitHub Actions Logs
- Buka repository > Actions
- Klik pada workflow run terbaru
- Periksa logs untuk setiap step

### 2. Server Logs
```bash
# SSH ke Hostinger
tail -f /var/log/apache2/error.log
tail -f domains/dokterkuklinik.com/public_html/dokterku/storage/logs/laravel.log
```

## 🔒 Security Best Practices

1. **Environment Variables**: Jangan commit file `.env` ke repository
2. **SSH Keys**: Gunakan SSH key yang kuat dan aman
3. **Database**: Gunakan database user dengan privileges minimal
4. **HTTPS**: Aktifkan SSL certificate di Hostinger
5. **Backup**: Backup database dan files secara regular

## 📞 Support

Jika mengalami masalah:
1. Periksa GitHub Actions logs
2. Periksa server logs di Hostinger
3. Pastikan semua secrets sudah dikonfigurasi dengan benar
4. Verifikasi struktur direktori di server

---

**🎉 Selamat! Aplikasi Anda sekarang siap di-deploy ke Hostinger!** 