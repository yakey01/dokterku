# 🗑️ Hostinger Cleanup Guide - Persiapan GitHub Deployment

## 📋 Overview
Panduan untuk menghapus file lama di Hostinger dan mempersiapkan deployment via GitHub Actions.

## 🚨 BACKUP DULU SEBELUM HAPUS!

### Step 1: Backup Database
```sql
-- Login ke phpMyAdmin atau MySQL
-- Export database u454362045_u45436245_kli
-- Download backup file (.sql)
```

### Step 2: Backup File Penting (Opsional)
```bash
# Backup file .env jika ada data penting
# Backup folder storage/app jika ada file upload
# Backup folder public/images jika ada gambar
```

## 🗑️ File yang HARUS DIHAPUS

### A. Hapus SEMUA File Laravel Lama
```bash
# Di public_html/, hapus semua ini:
app/                  # Folder aplikasi Laravel
bootstrap/            # Folder bootstrap Laravel
config/              # Folder konfigurasi
database/            # Folder database dan migrations
lang/                # Folder bahasa
public/              # Folder public Laravel
resources/           # Folder resources (views, css, js)
routes/              # Folder routes
storage/             # Folder storage Laravel
vendor/              # Folder vendor composer
.env                 # File environment
.env.example         # File environment example
artisan              # File artisan Laravel
composer.json        # File composer
composer.lock        # File composer lock
package.json         # File package.json
package-lock.json    # File package lock
*.php                # Semua file PHP lainnya
*.md                 # File markdown
*.sh                 # File shell script
```

### B. Hapus File Development
```bash
# Hapus juga file-file ini jika ada:
node_modules/        # Folder node modules
.git/                # Folder git (jika ada)
tests/               # Folder tests
.gitignore           # File gitignore
*.log                # File log
```

### C. Hapus File Web Server
```bash
# Hapus file web server lama:
.htaccess            # File htaccess lama
index.html           # File index html (jika ada)
index.php            # File index php standalone
```

## ✅ File yang BOLEH DIPERTAHANKAN

### File yang Aman Dibiarkan
```bash
# File-file ini boleh tidak dihapus:
.well-known/         # Folder SSL certificate
cgi-bin/             # Folder CGI (jika ada)
error_log            # Log error server
robots.txt           # File robots (jika ada)
sitemap.xml          # File sitemap (jika ada)
```

## 🛠️ Cara Hapus File di Hostinger

### Method 1: File Manager (Recommended)
1. **Login ke Hostinger Control Panel**
2. **Go to Files > File Manager**
3. **Navigate ke**: `domains/dokterkuklinik.com/public_html`
4. **Select All** (Ctrl+A)
5. **Delete** semua file kecuali yang disebutkan di "File yang Boleh Dipertahankan"

### Method 2: SSH Command (Advanced)
```bash
# SSH ke server
ssh username@dokterkuklinik.com

# Masuk ke directory
cd domains/dokterkuklinik.com/public_html

# Hapus semua file Laravel (HATI-HATI!)
rm -rf app/ bootstrap/ config/ database/ lang/ public/ resources/ routes/ storage/ vendor/
rm -f .env .env.example artisan composer.json composer.lock package.json package-lock.json
rm -f *.php *.md *.sh
rm -f .htaccess

# Periksa apa yang tersisa
ls -la
```

## 🔧 Persiapan GitHub Deployment

### Step 1: Update GitHub Actions Workflow
```yaml
# File: .github/workflows/deploy.yml
name: Deploy to Hostinger
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
      
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install npm dependencies
        run: npm ci --omit=dev
      
      - name: Build assets
        run: npm run build
      
      - name: Deploy to server
        uses: easingthemes/ssh-deploy@v2.1.5
        env:
          SSH_PRIVATE_KEY: ${{ secrets.SSH_PRIVATE_KEY }}
          REMOTE_HOST: ${{ secrets.REMOTE_HOST }}
          REMOTE_USER: ${{ secrets.REMOTE_USER }}
          SOURCE: "."
          TARGET: "/domains/dokterkuklinik.com/public_html"
          EXCLUDE: "/node_modules/, /.git/, /tests/, /.env, /storage/logs/"
      
      - name: Run production optimization
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.REMOTE_HOST }}
          username: ${{ secrets.REMOTE_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd domains/dokterkuklinik.com/public_html
            cp .env.production .env
            php artisan key:generate --force
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan storage:link
            chmod -R 755 storage bootstrap/cache
```

### Step 2: Setup GitHub Secrets
Di GitHub repository settings > Secrets and variables > Actions, tambahkan:

```
SSH_PRIVATE_KEY: [Your SSH Private Key]
REMOTE_HOST: dokterkuklinik.com
REMOTE_USER: [Your SSH username]
```

### Step 3: Setup SSH Key
```bash
# Generate SSH key pair (di local machine)
ssh-keygen -t rsa -b 4096 -C "deploy@dokterkuklinik.com"

# Copy public key ke server
ssh-copy-id -i ~/.ssh/id_rsa.pub username@dokterkuklinik.com

# Copy private key ke GitHub Secrets
cat ~/.ssh/id_rsa  # Copy content ini ke GitHub Secrets
```

## 🎯 Langkah-langkah Deployment

### 1. Cleanup Hostinger (Hari ini)
- [ ] Backup database
- [ ] Backup file penting
- [ ] Hapus semua file lama
- [ ] Verify public_html kosong

### 2. Setup GitHub (Hari ini)
- [ ] Update workflow file
- [ ] Setup GitHub secrets
- [ ] Setup SSH keys
- [ ] Test SSH connection

### 3. First Deploy (Hari ini)
```bash
# Push ke GitHub
git add .
git commit -m "Setup GitHub Actions deployment"
git push origin main

# GitHub Actions akan otomatis deploy
```

### 4. Verify Deployment
- [ ] Check website: https://dokterkuklinik.com
- [ ] Test all panels
- [ ] Check database connection
- [ ] Verify no errors

## 🔧 Troubleshooting

### Issue 1: Permission Denied
```bash
# SSH ke server, fix permissions
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

### Issue 2: Database Connection
```bash
# Check .env file
cat .env | grep DB_

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Issue 3: Assets Not Loading
```bash
# Rebuild assets
npm run build

# Create storage link
php artisan storage:link
```

## 🎉 Keuntungan GitHub Deployment

### ✅ Otomatis
- **Push ke GitHub** = otomatis deploy
- **No manual upload** diperlukan
- **Consistent deployment** setiap kali

### ✅ Rollback Easy
- **Git history** untuk rollback
- **Previous versions** tersimpan
- **Easy debugging** dengan commit history

### ✅ Team Collaboration
- **Multiple developers** bisa deploy
- **Code review** sebelum deploy
- **Automated testing** bisa ditambahkan

## 📋 Checklist Akhir

### Before Cleanup
- [ ] Database backup completed
- [ ] Important files backed up
- [ ] SSH access verified
- [ ] GitHub repository ready

### After Cleanup
- [ ] public_html is empty (except .well-known)
- [ ] GitHub Actions workflow updated
- [ ] SSH keys configured
- [ ] First deployment successful

### Verify Success
- [ ] Website accessible
- [ ] All panels working
- [ ] Database connection OK
- [ ] No 500 errors

---

## 🚀 READY FOR GITHUB DEPLOYMENT!

**Next Steps:**
1. **Backup** database dan file penting
2. **Hapus** semua file lama dari public_html
3. **Setup** GitHub Actions workflow
4. **Deploy** via GitHub push

**Setelah ini, setiap `git push` akan otomatis deploy ke Hostinger!** 🎉