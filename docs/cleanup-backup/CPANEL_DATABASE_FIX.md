# 🔧 Manual Database Fix via cPanel - Hostinger

Jika script otomatis gagal, ikuti langkah manual ini untuk memperbaiki database di cPanel Hostinger.

## 📋 Database Credentials Target
```
Database Name: u454362045_u45436245_kli
Username: u454362045_u45436245_kli  
Password: LaTahzan@01
Host: localhost (atau 127.0.0.1)
```

## 🚀 Step-by-Step Manual Fix

### 1. Login ke cPanel Hostinger
- Login ke [hpanel.hostinger.com](https://hpanel.hostinger.com)
- Pilih website: dokterkuklinik.com
- Klik "Manage" -> "Advanced" -> "cPanel"

### 2. Buka MySQL Databases
- Di cPanel, cari section "DATABASES"
- Klik "MySQL Databases"

### 3. Verifikasi Database Exists
- Di bagian "Current Databases", pastikan database `u454362045_u45436245_kli` ada
- Jika tidak ada, buat database baru:
  ```
  Database Name: u45436245_kli
  (akan menjadi: u454362045_u45436245_kli)
  ```

### 4. Verifikasi MySQL User Exists
- Di bagian "Current Users", pastikan user `u454362045_u45436245_kli` ada
- Jika tidak ada, buat user baru:
  ```
  Username: u45436245_kli
  Password: LaTahzan@01
  ```

### 5. Reset Password User (PENTING!)
- Di "Current Users", klik "Change Password" untuk user `u454362045_u45436245_kli`
- Set password baru: `LaTahzan@01`
- Klik "Change Password"

### 6. Grant Privileges ke User
- Di bagian "Add User To Database"
- User: `u454362045_u45436245_kli`
- Database: `u454362045_u45436245_kli`
- Klik "Add"
- Pilih "ALL PRIVILEGES"
- Klik "Make Changes"

### 7. Test Database Connection
- Buka "phpMyAdmin" dari cPanel
- Login dengan:
  ```
  Server: localhost
  Username: u454362045_u45436245_kli
  Password: LaTahzan@01
  ```
- Pastikan bisa login dan melihat database

## 🔧 Alternative: File Manager Fix

### 1. Buka File Manager di cPanel
- Navigate ke: `domains/dokterkuklinik.com/public_html/dokterku`

### 2. Edit .env File
- Klik kanan `.env` -> "Edit"
- Pastikan setting database:
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u454362045_u45436245_kli
DB_USERNAME=u454362045_u45436245_kli
DB_PASSWORD=LaTahzan@01
```

### 3. Test via Terminal (jika tersedia)
- Buka "Terminal" di cPanel
- Navigate ke Laravel directory:
```bash
cd domains/dokterkuklinik.com/public_html/dokterku
```

- Test MySQL connection:
```bash
mysql -h localhost -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli -e "SELECT 1;"
```

- Test Laravel connection:
```bash
php artisan tinker
DB::connection()->getPdo();
exit
```

- Run migrations:
```bash
php artisan migrate --force
```

## 🆘 Troubleshooting Common Issues

### Issue 1: "Access denied for user"
**Solution:**
1. Reset password di cPanel MySQL Databases
2. Pastikan user memiliki ALL PRIVILEGES
3. Coba ganti DB_HOST dari `localhost` ke `127.0.0.1`

### Issue 2: "Unknown database"
**Solution:**
1. Buat database baru di cPanel
2. Pastikan nama database persis: `u454362045_u45436245_kli`

### Issue 3: "Can't connect to MySQL server"
**Solution:**
1. Cek status MySQL di cPanel
2. Contact Hostinger support
3. Restart MySQL service (jika ada akses)

### Issue 4: "SQLSTATE[HY000] [2002] Connection refused"
**Solution:**
1. Ganti DB_HOST ke variasi lain:
   - `localhost`
   - `127.0.0.1` 
   - `mysql.hostinger.com`
   - `mysql`

## 📞 Contact Hostinger Support

Jika semua langkah manual gagal:

1. **Live Chat Hostinger:**
   - Login ke hpanel.hostinger.com
   - Klik icon chat di kanan bawah
   - Jelaskan masalah database connection

2. **Information untuk Support:**
   ```
   Domain: dokterkuklinik.com
   Database: u454362045_u45436245_kli
   User: u454362045_u45436245_kli
   Issue: Laravel aplikasi tidak bisa connect ke MySQL database
   Error: Access denied atau Connection refused
   ```

3. **Request ke Support:**
   - Reset database password
   - Verify database user privileges
   - Check MySQL service status
   - Provide correct database host

## ✅ Verification Steps

Setelah fix manual, verifikasi dengan:

1. **phpMyAdmin Test:** Login berhasil ke database
2. **Laravel Test:** `php artisan tinker` -> `DB::connection()->getPdo()`
3. **Migration Test:** `php artisan migrate:status`
4. **Website Test:** Buka https://dokterkuklinik.com

---

🤖 **Generated automatically
🔧 **Manual database troubleshooting guide for Hostinger**