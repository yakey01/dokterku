# Panduan Pengguna - Petugas Klinik Dokterku

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Akses dan Login](#akses-dan-login)
3. [Dashboard Utama](#dashboard-utama)
4. [Manajemen Pasien](#manajemen-pasien)
5. [Pencatatan Tindakan](#pencatatan-tindakan)
6. [Laporan Keuangan](#laporan-keuangan)
7. [Operasi Bulk](#operasi-bulk)
8. [Notifikasi dan Validasi](#notifikasi-dan-validasi)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pendahuluan

Selamat datang di sistem manajemen klinik **Dokterku**! Panduan ini akan membantu Anda sebagai petugas klinik untuk menggunakan sistem dengan optimal.

### Fitur Utama untuk Petugas
- ✅ **Pendaftaran Pasien** - Registrasi pasien baru dan update data
- ✅ **Pencatatan Tindakan** - Input tindakan medis dengan tarif otomatis
- ✅ **Monitor Keuangan** - Tracking pendapatan dan pengeluaran
- ✅ **Operasi Bulk** - Import/export data dalam jumlah besar
- ✅ **Validasi Workflow** - Sistem persetujuan transaksi
- ✅ **Notifikasi Real-time** - Update status secara langsung

### Konsep Penting
- **Workflow Validasi**: Semua transaksi perlu persetujuan bendahara
- **Jaspel Otomatis**: Fee dibagi otomatis untuk dokter, paramedis, non-paramedis
- **Audit Trail**: Semua aktivitas tercatat dengan timestamp dan user

---

## Akses dan Login

### URL Akses Petugas
```
https://dokterku.com/petugas
```

### Kredensial Default
- **Email**: `petugas@dokterku.com`
- **Password**: `petugas123`

> ⚠️ **Keamanan**: Ganti password default setelah login pertama

### Proses Login
1. Buka URL panel petugas
2. Masukkan email dan password
3. Klik **"Masuk"**
4. Sistem akan mengarahkan ke dashboard petugas

### Panel Access Control
- **Role Petugas**: Akses terbatas pada input data dan monitoring
- **Tidak dapat**: Validasi transaksi, manajemen user, konfigurasi sistem
- **Multi-device**: Bisa login dari beberapa device secara bersamaan

---

## Dashboard Utama

### Layout Dashboard Petugas

#### 📊 **Widget Statistik Utama**
- **Total Pasien Hari Ini**: Jumlah pendaftaran pasien baru
- **Tindakan Pending**: Tindakan yang menunggu validasi
- **Pendapatan Hari Ini**: Total pendapatan (pending + approved)
- **Pengeluaran Hari Ini**: Total pengeluaran tercatat

#### 🔄 **Quick Actions Widget**
```
[📝 Daftar Pasien Baru]  [⚕️ Input Tindakan]
[💰 Catat Pendapatan]    [📊 Lihat Laporan]
```

#### 📈 **Grafik Performance**
- **Grafik Bulanan**: Tren pasien dan pendapatan
- **Chart.js Integration**: Visual interaktif dengan dark theme
- **Filter Periode**: Hari ini, minggu ini, bulan ini

### Shortcut Keyboard
- `Alt + P` → Tambah Pasien Baru
- `Alt + T` → Input Tindakan
- `Alt + R` → Buka Laporan
- `Alt + N` → Refresh Notifikasi

---

## Manajemen Pasien

### 📝 Pendaftaran Pasien Baru

#### **Form Pendaftaran Lengkap**
```
👤 Data Pribadi
├── Nama Lengkap *          (Text, required)
├── Tanggal Lahir *         (Date picker)
├── Jenis Kelamin *         (Radio: Laki-laki/Perempuan)
├── Alamat Lengkap *        (Textarea)
├── No. Telepon            (Phone format validation)
└── Email                  (Email validation)

🆔 Data Identitas
├── NIK                    (16 digit validation)
├── No. KTP                (Upload file, optional)
└── Foto Pasien            (Upload, max 2MB)

🏥 Data Medis
├── Alergi                 (Textarea, optional)
├── Riwayat Penyakit       (Textarea, optional)
└── Catatan Khusus         (Textarea, optional)
```

#### **Validasi Input Otomatis**
- **NIK**: Validasi 16 digit numerik
- **Telepon**: Format Indonesia (+62)
- **Email**: Validasi format email
- **File Upload**: JPG/PNG max 2MB
- **Duplikasi**: Cek otomatis nama + tanggal lahir

#### **Proses Penyimpanan**
1. Isi form dengan lengkap
2. Klik **"Simpan Pasien"**
3. Sistem generate ID pasien otomatis
4. Notifikasi sukses dengan ID pasien baru
5. Redirect ke detail pasien atau list pasien

### 🔍 Pencarian dan Filter Pasien

#### **Fitur Pencarian Lanjutan**
```
🔍 Search Box
├── Pencarian by: Nama, NIK, ID Pasien, Telepon
├── Auto-complete suggestions
└── Minimum 3 karakter

📅 Filter Tanggal
├── Tanggal Daftar (Date range)
├── Tanggal Lahir (Date range)
└── Last Visit (Date range)

🎛️ Filter Lanjutan
├── Jenis Kelamin (Dropdown)
├── Status Aktif (Active/Inactive)
├── Alergi Tertentu (Text search)
└── Sorting: Nama, Tanggal, ID
```

#### **Shortcut Pencarian**
- **Keyboard**: `Ctrl + F` focus ke search box
- **Filter Cepat**: Tombol "Hari Ini", "Minggu Ini", "Bulan Ini"
- **Export Results**: Tombol "📊 Export Hasil Pencarian"

### ✏️ Edit Data Pasien

#### **Update Data Pasien**
- **Edit Inline**: Klik field untuk edit langsung
- **Batch Edit**: Pilih multiple pasien untuk update massa
- **History Tracking**: Semua perubahan tercatat dengan timestamp
- **Photo Update**: Drag & drop untuk ganti foto pasien

#### **Validasi Update**
- **Konfirmasi Perubahan**: Modal konfirmasi untuk data sensitif
- **Auto-save**: Draft otomatis setiap 30 detik
- **Rollback**: Undo changes dalam 5 menit terakhir

---

## Pencatatan Tindakan

### ⚕️ Input Tindakan Medis

#### **Form Tindakan Komprehensif**
```
👤 Data Pasien
├── Pilih Pasien *         (Searchable select)
├── Auto-fill Data         (Nama, umur, last visit)
└── Quick Add Pasien Baru  (Modal popup)

⚕️ Detail Tindakan
├── Jenis Tindakan *       (Dropdown dengan tarif)
├── Dokter yang Menangani* (Select dari user dokter)
├── Tanggal Tindakan *     (Default: hari ini)
├── Diagnosis              (Textarea, optional)
└── Catatan Tindakan       (Textarea, optional)

💰 Perhitungan Otomatis
├── Tarif Dasar            (Auto-fill dari jenis tindakan)
├── Diskon (%)             (Numeric input, max 50%)
├── Tarif Final            (Calculated field)
└── Preview Jaspel         (Show distribution)
```

#### **Auto-calculation Jaspel Preview**
Saat memilih jenis tindakan, sistem menampilkan:
```
💰 Tarif: Rp 100,000
├── 🩺 Jasa Dokter:        Rp 60,000 (60%)
├── 👩‍⚕️ Jasa Paramedis:      Rp 20,000 (20%)
└── 👥 Jasa Non-Paramedis:  Rp 20,000 (20%)

📊 Setelah Diskon 10%: Rp 90,000
├── 🩺 Jasa Dokter:        Rp 54,000
├── 👩‍⚕️ Jasa Paramedis:      Rp 18,000
└── 👥 Jasa Non-Paramedis:  Rp 18,000
```

### 🔗 Workflow Tindakan ke Pendapatan

#### **Proses Otomatis**
1. **Input Tindakan** → Status: `pending`
2. **Auto-create Pendapatan** → Jumlah = tarif final
3. **Pending Validation** → Menunggu approval bendahara
4. **Setelah Approval** → Auto-generate 3 jaspel records

#### **Status Tracking**
- 🟡 **Pending**: Menunggu validasi bendahara
- ✅ **Approved**: Disetujui, jaspel sudah digenerate
- ❌ **Rejected**: Ditolak dengan komentar alasan
- 🔄 **Revision**: Perlu perbaikan data

### 📋 Manajemen Jenis Tindakan

#### **Quick Add Jenis Tindakan**
Jika jenis tindakan belum ada:
```
➕ Tambah Jenis Tindakan Baru
├── Nama Tindakan *        (Text)
├── Kode Tindakan          (Auto-generate/manual)
├── Tarif Dasar *          (Currency format)
├── Persentase Dokter *    (Default: 60%)
├── Persentase Paramedis * (Default: 20%)
└── Persentase Non-Paramedis * (Default: 20%)

Validasi: Total persentase = 100%
```

---

## Laporan Keuangan

### 📊 Dashboard Keuangan Petugas

#### **Ringkasan Hari Ini**
```
💰 Pendapatan
├── Total Tindakan: Rp 2,500,000
├── Pending Validasi: Rp 800,000
├── Sudah Disetujui: Rp 1,700,000
└── Ditolak: Rp 0

💸 Pengeluaran
├── Total Tercatat: Rp 450,000
├── Pending Validasi: Rp 150,000
├── Sudah Disetujui: Rp 300,000
└── Ditolak: Rp 0

📈 Net Income: Rp 2,050,000
```

#### **Grafik Visual (Chart.js)**
- **Tren Bulanan**: Line chart pendapatan vs pengeluaran
- **Breakdown Tindakan**: Pie chart jenis tindakan terpopuler
- **Performance Dokter**: Bar chart revenue per dokter
- **Dark Theme Compatible**: Otomatis sesuai theme sistem

### 📈 Laporan Periode

#### **Filter Laporan Lanjutan**
```
📅 Periode Waktu
├── Custom Range (Date picker start/end)
├── Quick Select: Hari ini, Kemarin, 7 hari, 30 hari
└── Bulan/Tahun picker

🎯 Filter Data
├── Dokter Tertentu (Multi-select)
├── Jenis Tindakan (Multi-select)
├── Status Validasi (All/Pending/Approved/Rejected)
└── Range Tarif (Min/Max amount)

📊 Format Output
├── Tampilan: Table/Card/Chart
├── Export: PDF/Excel/CSV
└── Email Report (Schedule atau instant)
```

#### **Detail Laporan**
- **Patient Summary**: Jumlah pasien baru vs returning
- **Revenue Breakdown**: Per dokter, per jenis tindakan
- **Validation Metrics**: Success rate, avg processing time
- **Performance Indicators**: Target vs actual, growth rate

### 💾 Export dan Backup

#### **Format Export Tersedia**
- **📄 PDF Report**: Formatted report dengan grafik
- **📊 Excel/CSV**: Raw data untuk analisis lanjutan
- **📧 Email Schedule**: Auto-send report harian/mingguan
- **☁️ Backup Data**: Full backup ke cloud storage

---

## Operasi Bulk

### 📥 Import Data Pasien

#### **CSV Import Template**
```csv
nama,tanggal_lahir,jenis_kelamin,alamat,telepon,email,nik
"John Doe","1990-05-15","L","Jl. Merdeka No. 1","081234567890","john@email.com","1234567890123456"
"Jane Smith","1985-12-20","P","Jl. Sudirman No. 2","081234567891","jane@email.com","1234567890123457"
```

#### **Proses Import dengan Validasi**
1. **Upload File CSV** (max 5MB, max 1000 records)
2. **Preview Data** dengan error highlighting
3. **Validasi Otomatis**:
   - Format tanggal, email, NIK
   - Duplikasi nama + tanggal lahir
   - Required fields validation
4. **Batch Processing** dengan progress bar
5. **Import Report** dengan sukses/error count

#### **Error Handling Import**
```
✅ Berhasil: 145 pasien
❌ Error: 5 pasien
├── Row 3: Format NIK tidak valid
├── Row 7: Email duplikasi
├── Row 12: Tanggal lahir tidak valid
├── Row 18: Nama wajib diisi
└── Row 25: Telepon format salah

📊 Download Error Report (CSV)
🔄 Perbaiki dan Import Ulang
```

### 📤 Export Data

#### **Bulk Export Options**
```
📊 Export Pasien
├── Semua Data (Full export)
├── Filter by Date Range
├── Filter by Status
└── Format: CSV/Excel/PDF

📋 Export Tindakan
├── Include/Exclude: Diagnosis, Catatan
├── Filter by Dokter
├── Filter by Status Validasi
└── Include Jaspel Breakdown

💰 Export Keuangan
├── Pendapatan/Pengeluaran/Kombinasi
├── Include Summary Statistics
├── Chart Images dalam PDF
└── Email Option untuk Automated Report
```

### 🔄 Batch Operations

#### **Bulk Update Pasien**
- **Multi-select**: Checkbox untuk pilih multiple pasien
- **Batch Actions**: Update status, kategori, dokter pic
- **Bulk Delete**: Soft delete dengan konfirmasi
- **Restore Options**: Undo bulk actions dalam 24 jam

#### **Bulk Validation**
- **Select All Pending**: Pilih semua transaksi pending
- **Batch Approve**: Mass approval dengan satu klik
- **Add Bulk Comments**: Komentar untuk multiple items
- **Export Validation Report**: Summary validation actions

---

## Notifikasi dan Validasi

### 🔔 Sistem Notifikasi Real-time

#### **Jenis Notifikasi untuk Petugas**
```
📋 Workflow Notifications
├── ✅ Tindakan Anda disetujui
├── ❌ Tindakan Anda ditolak
├── 🔄 Revisi diperlukan
└── ⏰ Pending validation reminder

📊 System Notifications  
├── 🎯 Target bulanan tercapai
├── 📈 Performance report tersedia
├── 🔄 System maintenance schedule
└── 🆕 Feature update announcement

⚠️ Alert Notifications
├── 🚨 Error import data
├── ⚠️ Validation deadline approaching
├── 🔒 Security login dari device baru
└── 💾 Backup completion status
```

#### **Notification Center**
- **Badge Counter**: Unread notification count
- **Priority Levels**: High (red), Medium (yellow), Low (blue)
- **Mark as Read**: Batch mark atau individual
- **Search Notifications**: Filter by type, date, status
- **Notification History**: 30 hari terakhir

### ✔️ Workflow Validasi

#### **Status Transaksi dan Artinya**
```
🟡 PENDING
├── Transaksi baru masuk sistem
├── Menunggu review bendahara
├── Dapat di-edit oleh petugas
└── Belum generate jaspel

✅ APPROVED  
├── Disetujui oleh bendahara
├── Otomatis generate jaspel
├── Tidak dapat di-edit
└── Masuk ke laporan final

❌ REJECTED
├── Ditolak dengan alasan
├── Dapat diperbaiki dan resubmit
├── Catatan penolakan tersimpan
└── Email notification ke petugas

🔄 REVISION
├── Perlu perbaikan minor
├── Bendahara beri catatan revisi
├── Edit dan resubmit untuk approval
└── Priority processing
```

#### **Monitoring Validasi**
- **Dashboard Status**: Real-time count per status
- **Aging Report**: Transaksi pending > 3 hari
- **SLA Tracking**: Target 24 jam approval time
- **Escalation**: Auto-reminder ke supervisor

### 📧 Email dan Telegram Integration

#### **Automated Notifications**
```
📧 Email Notifications
├── Daily summary report
├── Validation status changes
├── Weekly performance report
└── Monthly financial summary

📱 Telegram Bot (Optional)
├── Real-time validation updates
├── System alerts
├── Quick status check commands
└── Emergency notifications
```

---

## Tips dan Troubleshooting

### 💡 Tips Efisiensi

#### **Keyboard Shortcuts**
```
Global Shortcuts:
├── Ctrl + S     → Save current form
├── Ctrl + N     → New record (context-aware)
├── Ctrl + F     → Focus search box
├── Esc          → Close modal/Cancel action
└── F5           → Refresh data

Navigation:
├── Alt + 1      → Dashboard
├── Alt + 2      → Pasien
├── Alt + 3      → Tindakan
├── Alt + 4      → Laporan
└── Alt + 9      → Profile/Settings
```

#### **Data Entry Best Practices**
1. **Gunakan Auto-complete**: Typing hint untuk nama, alamat
2. **Copy Paste Support**: Format data dari Excel
3. **Tab Navigation**: Efficient form navigation
4. **Save Draft**: Auto-save setiap 30 detik
5. **Quick Actions**: Right-click context menu

### 🔧 Troubleshooting Common Issues

#### **❌ Problem: "Gagal menyimpan data pasien"**
**Solutions:**
```
✅ Check Internet Connection
├── Pastikan koneksi stabil
└── Refresh halaman dan coba lagi

✅ Validasi Input Data
├── Cek format NIK (16 digit)
├── Validasi format email
├── Ukuran foto < 2MB
└── Field required sudah diisi

✅ Clear Browser Cache
├── Ctrl + Shift + R (hard refresh)
├── Clear cookies untuk domain
└── Try incognito/private mode

✅ Contact Support
├── Screenshot error message
├── Note: Data apa yang diinput
└── Email: support@dokterku.com
```

#### **❌ Problem: "Data tidak muncul di laporan"**
**Solutions:**
```
✅ Check Status Transaksi
├── Pastikan status bukan 'draft'
├── Check tanggal transaksi
└── Validasi filter periode

✅ Refresh Cache
├── Klik tombol "🔄 Refresh Data"
├── Logout dan login kembali
└── Clear browser cache

✅ Verify Permissions
├── Check role access
├── Contact admin jika perlu
└── Test dengan user lain
```

#### **❌ Problem: "Upload file gagal"**
**Solutions:**
```
✅ File Requirements
├── Format: JPG, PNG, PDF only
├── Size: Maximum 2MB
├── Name: Avoid special characters
└── Resolution: Max 1920x1080

✅ Browser Issues
├── Try different browser
├── Disable browser extensions
├── Check JavaScript enabled
└── Update browser version

✅ Network Issues
├── Stable internet connection
├── Try mobile hotspot
├── Contact IT support
└── Use alternative device
```

### 📞 Kontak Support

#### **Support Channels**
```
🏥 Internal Support
├── IT Admin: ext. 101
├── Supervisor: ext. 102
└── Training: ext. 103

📧 Email Support
├── General: support@dokterku.com
├── Technical: tech@dokterku.com
└── Training: training@dokterku.com

📱 Emergency Contact
├── WhatsApp: +62-xxx-xxx-xxxx
├── Telegram: @dokterku_support
└── Phone: (021) xxx-xxxx

🕐 Support Hours
├── Weekdays: 08:00 - 17:00 WIB
├── Saturday: 08:00 - 12:00 WIB
└── Emergency: 24/7 for critical issues
```

### 📚 Training Resources

#### **Available Materials**
- **📹 Video Tutorial**: Step-by-step walkthrough
- **📖 User Manual PDF**: Offline reference guide
- **🎓 Interactive Tutorial**: In-app guided tour
- **❓ FAQ Section**: Common questions answered
- **📞 1-on-1 Training**: Schedule with trainer

---

## Lampiran

### 🔐 Security Guidelines

#### **Password Requirements**
- Minimum 8 karakter
- Kombinasi huruf besar, kecil, angka
- Ganti password setiap 90 hari
- Jangan sharing akun

#### **Data Protection**
- Logout setelah selesai kerja
- Lock screen jika tinggal sebentar
- Jangan screenshot data sensitif
- Report security incident immediately

### 📋 Standard Operating Procedures

#### **Daily Checklist**
```
🌅 Start of Day:
□ Login ke sistem
□ Check notifications
□ Review pending validations
□ Update calendar appointments

🕐 During Work:
□ Input data real-time
□ Verify pasien data accuracy  
□ Follow up pending approvals
□ Maintain patient privacy

🌅 End of Day:
□ Complete all pending entries
□ Export daily reports
□ Log out from all devices
□ Backup important files
```

---

**📞 Butuh Bantuan?**  
Hubungi support team atau supervisor untuk training lanjutan dan resolusi masalah.

*Generated: 2025-07-15*  
*Version: 2.0.0*  
*Status: Documentation Phase - User Manual Complete*