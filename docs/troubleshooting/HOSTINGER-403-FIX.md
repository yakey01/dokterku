# 🚨 403 FORBIDDEN ERROR - HOSTINGER TROUBLESHOOTING

## Problem
GitHub Actions deployment sukses tapi website masih 403 Forbidden.

## Kemungkinan Penyebab

### 1. **Document Root Salah di Control Panel**
- Hostinger control panel mungkin set document root ke folder yang berbeda
- Periksa di **Hostinger Control Panel → Website → Manage**
- Document root harus pointing ke `/domains/dokterkuklinik.com/public_html`

### 2. **Struktur Direktori Hostinger Berbeda**
- Mungkin path yang benar adalah:
  - `~/public_html/` (user home)
  - `~/htdocs/`
  - `~/www/`
  - `/home/u454362045/domains/dokterkuklinik.com/public_html/`

## 🔧 LANGKAH TROUBLESHOOTING

### STEP 1: SSH ke Server dan Jalankan Debug
```bash
# SSH ke Hostinger
ssh u454362045@dokterkuklinik.com

# Jalankan debug script
bash debug-403.sh
```

### STEP 2: Cek Document Root di Control Panel
1. Login ke **Hostinger Control Panel**
2. Buka **Website → Manage**
3. Cek **Document Root** settings
4. Pastikan pointing ke folder yang benar

### STEP 3: Cek Struktur Direktori
```bash
# Cek direktori home
ls -la ~

# Cek apakah ada public_html di home
ls -la ~/public_html/

# Cek domains directory
ls -la ~/domains/

# Find semua index.php
find ~ -name "index.php" -type f
```

### STEP 4: Manual Fix (Jika Perlu)
```bash
# Jika file deploy ke path yang salah, pindahkan
# Misal jika harus ke ~/public_html/
cd ~
cp -r /domains/dokterkuklinik.com/public_html/* public_html/

# Atau buat symlink
ln -s /domains/dokterkuklinik.com/public_html public_html

# Set permissions
chmod -R 755 public_html/
chmod -R 775 public_html/storage/
```

## 🎯 KEMUNGKINAN SOLUTIONS

### Solution 1: Update Deployment Target
Jika structure berbeda, update `.github/workflows/deploy.yml`:
```yaml
TARGET: "/home/u454362045/public_html"  # Ganti path ini
```

### Solution 2: Hostinger Control Panel Settings
- **Document Root**: `/domains/dokterkuklinik.com/public_html`
- **PHP Version**: 8.3
- **SSL**: Enabled

### Solution 3: Manual File Copy
```bash
# Login SSH dan copy manual
ssh u454362045@dokterkuklinik.com
cp -r /domains/dokterkuklinik.com/public_html/* ~/public_html/
```

## 📞 CONTACT HOSTINGER SUPPORT

Jika masih bermasalah, hubungi Hostinger Support dengan info:
- **Domain**: dokterkuklinik.com
- **Issue**: 403 Forbidden after successful deployment
- **Account**: u454362045
- **Request**: Verify document root path for domain

## 🚀 NEXT STEPS

1. **Jalankan debug script** untuk identify exact issue
2. **Update deployment path** jika diperlukan
3. **Test manual copy** jika auto deployment path salah
4. **Contact support** jika structure tidak standard

---

💡 **TIP**: Setiap hosting provider punya struktur directory yang berbeda. Hostinger biasanya pakai `~/public_html/` untuk main domain.