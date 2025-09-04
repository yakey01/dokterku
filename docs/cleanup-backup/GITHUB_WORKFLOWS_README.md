# GitHub Workflows Documentation

## 📋 File YAML yang Tersisa

Setelah pembersihan, hanya ada 4 file YAML yang aktif:

### 1. `simple-deploy.yml` ⭐ **RECOMMENDED**
- **Fungsi:** Deployment sederhana dan bersih
- **Trigger:** Push ke branch `main` atau manual
- **Fitur:**
  - Pull latest changes
  - Run Pail fix script
  - Run troubleshooting script
  - Clear Laravel caches
  - Regenerate autoload
  - Test Laravel

### 2. `test-deploy.yml` 🧪
- **Fungsi:** Testing koneksi SSH
- **Trigger:** Manual only
- **Fitur:**
  - Test SSH connection
  - Check PHP version
  - Check Composer version

### 3. `deploy.yml` 📦
- **Fungsi:** Deployment lengkap (legacy)
- **Trigger:** Manual
- **Status:** Backup file

### 4. `deploy-to-hostinger.yml` 🏠
- **Fungsi:** Deployment khusus Hostinger
- **Trigger:** Manual
- **Status:** Backup file

## 🗑️ File yang Dihapus

File-file berikut telah dihapus karena bermasalah atau tidak diperlukan:

- ❌ `fix-blank-page.yml` - Terlalu kompleks, banyak error
- ❌ `ultimate-403-fix.yml` - Bermasalah dengan permissions
- ❌ `emergency-403-fix.yml` - Duplikat dan bermasalah
- ❌ `step-by-step-debug.yml` - Terlalu verbose
- ❌ `fix-env-file.yml` - Tidak diperlukan
- ❌ `deploy-old.yml` - Versi lama
- ❌ `build.yml` - Bermasalah dengan Pail

## 🚀 Cara Menggunakan

### Untuk Deployment Normal:
1. Push ke branch `main`
2. Workflow `simple-deploy.yml` akan otomatis berjalan
3. Atau trigger manual dari GitHub Actions

### Untuk Testing:
1. Buka GitHub Actions
2. Pilih "Test Deploy"
3. Klik "Run workflow"

### Untuk Debugging:
1. Upload script `hosting-troubleshoot.sh` ke hosting
2. Jalankan manual di hosting
3. Share output untuk analisis

## 🔧 Troubleshooting

### Jika Workflow Gagal:
1. **Check Secrets:** Pastikan `HOST`, `REMOTE_USER`, `SSH_PRIVATE_KEY` sudah diset
2. **Check SSH Key:** Pastikan SSH key valid dan terdaftar di hosting
3. **Check Path:** Pastikan path `domains/dokterkuklinik.com/public_html` benar
4. **Check Permissions:** Pastikan user memiliki akses ke direktori

### Jika Script Tidak Ditemukan:
1. Upload script yang diperlukan ke hosting
2. Pastikan file executable: `chmod +x script-name.sh`
3. Test manual di hosting terlebih dahulu

## 📝 Best Practices

1. **Gunakan `simple-deploy.yml`** untuk deployment rutin
2. **Test dengan `test-deploy.yml`** sebelum deployment besar
3. **Backup sebelum deployment** penting
4. **Monitor logs** di GitHub Actions
5. **Keep workflows simple** - hindari kompleksitas berlebihan

## 🔄 Maintenance

- **Regular cleanup:** Hapus workflow yang tidak digunakan
- **Update secrets:** Perbarui SSH keys secara berkala
- **Monitor performance:** Cek waktu eksekusi workflow
- **Document changes:** Update dokumentasi saat ada perubahan

---

**Note:** Semua workflow sekarang lebih sederhana dan fokus pada fungsi utama tanpa kompleksitas yang tidak perlu. 