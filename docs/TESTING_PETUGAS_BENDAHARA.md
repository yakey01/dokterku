# Testing Guide: Petugas → Bendahara Data Flow

## 🎯 **MASALAH YANG SUDAH DISELESAIKAN**

1. ✅ **Data dummy dihapus** - All dummy financial data cleaned up
2. ✅ **User Tina teridentifikasi** - Multiple Tina users found with different roles
3. ✅ **Query bendahara verified** - Filtering logic working correctly

## 👤 **AVAILABLE TINA USERS**

| Email | Name | Role | Panel Access |
|-------|------|------|--------------|
| `tina@petugas.com` | Tina Petugas | Staff (Petugas) | `/petugas` |
| `tina@bendahara.com` | Tina Bendahara | Treasurer | `/bendahara` |
| `tina@manajer.com` | Tina Manajer | Manager | `/manajer` |
| `tina.slamet@klinik.com` | Tina Slamet | Staff | `/petugas` |

## 🧪 **CARA TESTING YANG BENAR**

### **Step 1: Login sebagai Petugas**
```
URL: http://127.0.0.1:8000/login
Email: tina@petugas.com
Password: password (sesuai seeder)
```

### **Step 2: Input Data di Panel Petugas**
1. Masuk ke `/petugas`
2. Input **Pendapatan Harian** dengan data:
   - Nama: "Test Pendapatan Tina"
   - Nominal: 100000
   - Kategori: tindakan_medis
   - Keterangan: "Testing data flow"

3. Input **Pengeluaran Harian** dengan data:
   - Nama: "Test Pengeluaran Tina"
   - Nominal: 50000
   - Kategori: operasional
   - Keterangan: "Testing data flow"

### **Step 3: Verifikasi di Panel Bendahara**
```
URL: http://127.0.0.1:8000/bendahara
Email: tina@bendahara.com
Password: password
```

1. Masuk ke **Validasi Pendapatan**
2. Cek apakah data dari Tina Petugas muncul
3. Kolom "Input Oleh" harus menampilkan "Tina Petugas"

## 🔍 **TROUBLESHOOTING**

### **Jika data tidak muncul:**

1. **Cek relasi input_by:**
```sql
SELECT p.*, u.name as input_by_name 
FROM pendapatan p 
LEFT JOIN users u ON p.input_by = u.id 
WHERE u.email = 'tina@petugas.com';
```

2. **Cek filtering di bendahara:**
- Pastikan data memiliki `input_by` tidak null
- Pastikan `status_validasi` default adalah 'pending'

3. **Clear cache jika perlu:**
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

## 📋 **DEBUGGING COMMANDS**

```bash
# Check users
php artisan tinker --execute="App\Models\User::where('email', 'tina@petugas.com')->first()"

# Check pendapatan by Tina
php artisan tinker --execute="App\Models\Pendapatan::with('inputBy')->whereHas('inputBy', function($q) { $q->where('email', 'tina@petugas.com'); })->get()"

# Check pengeluaran by Tina  
php artisan tinker --execute="App\Models\Pengeluaran::with('inputBy')->whereHas('inputBy', function($q) { $q->where('email', 'tina@petugas.com'); })->get()"
```

## ⚠️ **CATATAN PENTING**

1. **User Tina Manajer** (`tina@manajer.com`) ≠ **Tina Petugas** (`tina@petugas.com`)
2. Data hanya muncul di bendahara jika diinput oleh user dengan role **petugas**
3. Sistem sudah bersih dari data dummy - testing harus dilakukan dengan data real
4. Panel bendahara menggunakan filtering `whereNotNull('input_by')` - data harus memiliki user yang valid

## 🎯 **EXPECTED RESULT**

Setelah testing:
- ✅ Data input di `/petugas` oleh Tina Petugas
- ✅ Data muncul di `/bendahara` untuk validasi  
- ✅ Kolom "Input Oleh" menampilkan "Tina Petugas"
- ✅ Status awal "pending" untuk validasi bendahara