# Manual Pengguna Dashboard Petugas - Dokterku

## Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Login dan Akses](#login-dan-akses)
3. [Dashboard Utama](#dashboard-utama)
4. [Manajemen Pasien](#manajemen-pasien)
5. [Manajemen Tindakan](#manajemen-tindakan)
6. [Manajemen Keuangan](#manajemen-keuangan)
7. [Laporan Harian](#laporan-harian)
8. [Notifikasi dan Approval](#notifikasi-dan-approval)
9. [Export dan Import Data](#export-dan-import-data)
10. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan

Dashboard Petugas adalah sistem manajemen khusus untuk staff petugas di klinik Dokterku. Sistem ini memungkinkan petugas untuk:

- ✅ Mengelola data pasien secara mandiri
- ✅ Mencatat tindakan medis dan perawatan
- ✅ Mengelola pendapatan dan pengeluaran harian
- ✅ Membuat laporan pasien harian
- ✅ Menggunakan sistem approval untuk validasi data
- ✅ Export/import data dalam berbagai format

### Fitur Utama
- **Data Scoping**: Setiap petugas hanya dapat melihat dan mengelola data yang mereka input sendiri
- **Workflow Approval**: Sistem persetujuan bertingkat untuk tindakan dan transaksi
- **Real-time Notifications**: Notifikasi langsung untuk approval dan reminder
- **Bulk Operations**: Operasi massal untuk efisiensi kerja
- **Advanced Search**: Pencarian canggih dengan filter

---

## Login dan Akses

### 1. Mengakses Sistem
1. Buka browser dan akses URL: `https://your-domain.com/petugas`
2. Masukkan email dan password yang telah diberikan
3. Klik tombol **"Login"**

### 2. Setelah Login
Setelah berhasil login, Anda akan diarahkan ke dashboard utama petugas.

### 3. Logout
- Klik nama pengguna di pojok kanan atas
- Pilih **"Logout"** dari dropdown menu

---

## Dashboard Utama

### Widget Dashboard

#### 1. **Stats Widget**
Menampilkan statistik harian, mingguan, dan bulanan:
- **Pasien**: Jumlah pasien yang didaftarkan
- **Tindakan**: Jumlah dan total nilai tindakan
- **Pendapatan**: Total pendapatan yang dicatat
- **Pengeluaran**: Total pengeluaran yang dicatat

#### 2. **Quick Actions Widget**
Akses cepat untuk operasi umum:
- 👤 **Tambah Pasien**: Daftar pasien baru
- 🏥 **Input Tindakan**: Catat tindakan medis
- 💰 **Input Pendapatan**: Catat pendapatan harian
- 💸 **Input Pengeluaran**: Catat pengeluaran harian
- 📊 **Laporan Harian**: Buat laporan pasien harian
- 👥 **Lihat Semua Pasien**: Akses daftar lengkap pasien

#### 3. **Notification Widget**
Menampilkan notifikasi penting:
- Approval pending untuk tindakan/transaksi
- Reminder tugas harian
- Status persetujuan

### Greeting dan Tips
- **Personalized Greeting**: Sapaan personal berdasarkan waktu
- **Workflow Tips**: Tips kerja berdasarkan role dan aktivitas

---

## Manajemen Pasien

### 1. Tambah Pasien Baru

#### Langkah-langkah:
1. Klik **"Tambah Pasien"** di Quick Actions atau menu Pasien
2. Isi form dengan data berikut:

**Data Wajib:**
- **No. Rekam Medis**: Otomatis ter-generate (RM-YYYY-XXX)
- **Nama Lengkap**: Nama pasien
- **Tanggal Lahir**: Format DD/MM/YYYY
- **Jenis Kelamin**: Pilih L (Laki-laki) atau P (Perempuan)

**Data Opsional:**
- **Alamat**: Alamat lengkap pasien
- **No. Telepon**: Nomor telepon yang bisa dihubungi
- **Email**: Alamat email pasien
- **Pekerjaan**: Profesi pasien
- **Status Pernikahan**: Single/Menikah/Janda/Duda
- **Kontak Darurat**: Nama dan telepon kontak darurat

3. Klik **"Simpan"** untuk menyimpan data

#### Tips:
- ✅ Pastikan No. Rekam Medis unik
- ✅ Verifikasi data sebelum menyimpan
- ✅ Gunakan format tanggal yang benar

### 2. Edit Data Pasien

#### Langkah-langkah:
1. Masuk ke menu **"Pasien"**
2. Cari pasien menggunakan fitur search
3. Klik tombol **"Edit"** pada baris pasien
4. Update data yang diperlukan
5. Klik **"Simpan"** untuk menyimpan perubahan

#### Catatan Penting:
- ⚠️ Anda hanya dapat mengedit pasien yang Anda input sendiri
- ⚠️ No. Rekam Medis tidak dapat diubah setelah disimpan

### 3. Pencarian dan Filter

#### Fitur Pencarian:
- **Search Bar**: Cari berdasarkan nama atau no. rekam medis
- **Filter Jenis Kelamin**: Filter berdasarkan L/P
- **Filter Tanggal**: Filter berdasarkan tanggal registrasi
- **Sort**: Urutkan berdasarkan nama, tanggal, dll.

#### Bulk Operations:
- **Select Multiple**: Pilih beberapa pasien sekaligus
- **Bulk Export**: Export data pasien terpilih
- **Bulk Update**: Update data massal (jika tersedia)

---

## Manajemen Tindakan

### 1. Input Tindakan Baru

#### Langkah-langkah:
1. Klik **"Input Tindakan"** di Quick Actions
2. Isi form tindakan:

**Data Wajib:**
- **Jenis Tindakan**: Pilih dari dropdown yang tersedia
- **Pasien**: Pilih pasien dari daftar Anda
- **Tanggal & Waktu**: Kapan tindakan dilakukan
- **Shift**: Pagi/Siang/Sore/Malam
- **Dokter**: Dokter yang menangani (jika ada)

**Data Keuangan:**
- **Tarif**: Otomatis terisi dari jenis tindakan
- **Jasa Dokter**: Pembagian fee dokter
- **Jasa Paramedis**: Pembagian fee paramedis
- **Jasa Non-Paramedis**: Pembagian fee non-paramedis

3. Klik **"Simpan"** untuk menyimpan

#### Auto-Approval System:
- **≤ 100.000**: Otomatis disetujui
- **> 100.000**: Perlu approval supervisor
- **> 1.000.000**: Perlu approval manager

### 2. Status Tindakan

#### Status yang Tersedia:
- 🟡 **Pending**: Belum disubmit untuk validasi
- 🔵 **Submitted**: Sudah disubmit, menunggu approval
- 🟢 **Approved**: Sudah disetujui
- 🔴 **Rejected**: Ditolak dengan alasan
- 🟠 **Revision**: Perlu revisi

#### Workflow Approval:
1. **Submit**: Petugas submit tindakan
2. **Review**: Supervisor/Manager review
3. **Decision**: Approve/Reject/Request Revision
4. **Notification**: Notifikasi hasil ke petugas

### 3. Tracking dan Monitoring

#### Dashboard Tindakan:
- View semua tindakan yang Anda input
- Filter berdasarkan status
- Monitor progress approval
- Lihat alasan jika ada rejection

---

## Manajemen Keuangan

### 1. Pendapatan Harian

#### Input Pendapatan:
1. Klik **"Input Pendapatan"** di Quick Actions
2. Isi form:
   - **Tanggal**: Tanggal pendapatan
   - **Shift**: Shift kerja
   - **Jenis Pendapatan**: Pilih dari master data
   - **Nominal**: Jumlah pendapatan
   - **Deskripsi**: Keterangan detail

#### Auto-Approval Thresholds:
- **≤ 500.000**: Auto-approved
- **> 500.000**: Perlu approval

### 2. Pengeluaran Harian

#### Input Pengeluaran:
1. Klik **"Input Pengeluaran"** di Quick Actions
2. Isi form:
   - **Tanggal**: Tanggal pengeluaran
   - **Shift**: Shift kerja
   - **Jenis Pengeluaran**: Pilih dari master data
   - **Nominal**: Jumlah pengeluaran
   - **Deskripsi**: Keterangan detail

#### Auto-Approval Thresholds:
- **≤ 300.000**: Auto-approved
- **> 300.000**: Perlu approval

### 3. Monitoring Keuangan

#### Dashboard Features:
- **Summary Harian**: Total pendapatan vs pengeluaran
- **Trend Mingguan**: Grafik trend 7 hari terakhir
- **Comparison**: Perbandingan dengan periode sebelumnya
- **Status Approval**: Monitoring approval pendapatan/pengeluaran

---

## Laporan Harian

### 1. Membuat Laporan Pasien Harian

#### Langkah-langkah:
1. Klik **"Laporan Harian"** di Quick Actions
2. Isi form laporan:
   - **Tanggal**: Tanggal laporan
   - **Shift**: Shift kerja
   - **Jumlah Pasien**: Total pasien yang dilayani
   - **Keterangan**: Detail aktivitas hari ini

3. Klik **"Simpan"** untuk menyimpan laporan

#### Tips Membuat Laporan:
- ✅ Buat laporan setiap akhir shift
- ✅ Pastikan jumlah pasien akurat
- ✅ Berikan keterangan yang detail
- ✅ Submit tepat waktu

### 2. Review Laporan

#### Fitur Review:
- **View History**: Lihat semua laporan yang pernah dibuat
- **Edit**: Edit laporan yang belum final
- **Status**: Monitor status approval laporan
- **Print/Export**: Cetak atau export laporan

---

## Notifikasi dan Approval

### 1. Sistem Notifikasi

#### Jenis Notifikasi:
- 🔔 **Validation Pending**: Ada item yang perlu approval
- ✅ **Validation Approved**: Item Anda telah disetujui
- ❌ **Validation Rejected**: Item Anda ditolak
- 🔄 **Revision Required**: Perlu revisi
- 📋 **Task Reminder**: Reminder tugas harian

#### Cara Membaca Notifikasi:
1. Lihat widget **Notification** di dashboard
2. Klik notifikasi untuk melihat detail
3. Ambil tindakan yang diperlukan
4. Mark as read setelah selesai

### 2. Workflow Approval

#### Untuk Petugas:
1. **Submit**: Submit item untuk approval
2. **Wait**: Tunggu proses review
3. **Respond**: Respon jika ada revision request
4. **Monitor**: Pantau status di dashboard

#### Role-based Approval:
- **Supervisor**: Approve tindakan ≤ 1.000.000
- **Manager**: Approve tindakan > 1.000.000
- **Auto-system**: Approve item dengan nilai kecil

---

## Export dan Import Data

### 1. Export Data

#### Format yang Tersedia:
- **CSV**: Format spreadsheet standar
- **Excel (XLSX)**: Format Microsoft Excel
- **PDF**: Format laporan cetak
- **JSON**: Format data terstruktur

#### Cara Export:
1. Masuk ke resource yang ingin di-export (Pasien/Tindakan/dll)
2. Klik tombol **"Export"**
3. Pilih format yang diinginkan
4. Pilih filter/range data (opsional)
5. Klik **"Download"**

#### Export Options:
- **All Data**: Export semua data Anda
- **Filtered Data**: Export data dengan filter tertentu
- **Date Range**: Export data dalam rentang tanggal
- **Selected Records**: Export record yang dipilih

### 2. Import Data

#### Format yang Didukung:
- **CSV**: Comma-separated values
- **Excel (XLSX)**: Microsoft Excel format

#### Cara Import:
1. Siapkan file dengan format yang benar
2. Klik **"Import"** di resource terkait
3. Upload file
4. Map kolom jika diperlukan
5. Review preview data
6. Klik **"Import"** untuk proses

#### Template Import:
- Download template dari sistem
- Isi data sesuai format
- Jangan ubah header kolom
- Validate data sebelum import

### 3. Bulk Operations

#### Operasi yang Tersedia:
- **Bulk Create**: Tambah banyak record sekaligus
- **Bulk Update**: Update banyak record sekaligus
- **Bulk Delete**: Hapus banyak record sekaligus (jika ada permission)

#### Performance Tips:
- **Batch Size**: Proses data dalam batch kecil
- **Validation**: Aktifkan validasi untuk data quality
- **Backup**: Backup data sebelum bulk operations

---

## Tips dan Troubleshooting

### 1. Best Practices

#### Input Data:
- ✅ **Double-check**: Selalu cek data sebelum submit
- ✅ **Consistent Format**: Gunakan format yang konsisten
- ✅ **Regular Backup**: Backup data secara rutin
- ✅ **Timely Submission**: Submit approval tepat waktu

#### Performance:
- ✅ **Use Filters**: Gunakan filter untuk data besar
- ✅ **Pagination**: Manfaatkan pagination
- ✅ **Cache**: Cache akan meningkatkan performa
- ✅ **Close Tabs**: Tutup tab yang tidak digunakan

### 2. Troubleshooting Umum

#### Masalah Login:
- **Lupa Password**: Hubungi admin untuk reset
- **Session Expired**: Login ulang
- **Access Denied**: Cek role dan permission

#### Masalah Data:
- **Data Tidak Muncul**: Cek filter dan scope
- **Tidak Bisa Edit**: Cek ownership dan permission
- **Validation Error**: Periksa format data

#### Masalah Performance:
- **Loading Lambat**: Clear cache browser
- **Export Gagal**: Cek ukuran data dan koneksi
- **Memory Error**: Kurangi range data

### 3. Keyboard Shortcuts

#### Navigation:
- **Ctrl + /**: Open search
- **Ctrl + N**: New record (di list page)
- **Ctrl + S**: Save form
- **Esc**: Close modal/dialog

#### Table Operations:
- **Ctrl + A**: Select all visible records
- **Delete**: Delete selected (jika ada permission)
- **Enter**: Open/Edit record

### 4. Dukungan Teknis

#### Untuk Bantuan:
- **Help Desk**: Hubungi IT support internal
- **Documentation**: Baca manual ini lengkap
- **Training**: Ikuti training yang disediakan
- **Feedback**: Berikan feedback untuk improvement

#### Kontak Support:
- **Email**: support@dokterku.com
- **Phone**: 021-XXXXXXX
- **Internal Chat**: Slack #dokterku-support

---

## Kesimpulan

Dashboard Petugas Dokterku dirancang untuk memberikan pengalaman kerja yang efisien dan user-friendly. Dengan fitur-fitur canggih seperti:

- **Data Scoping** yang aman
- **Workflow Approval** yang terstruktur  
- **Real-time Notifications**
- **Bulk Operations** untuk efisiensi
- **Export/Import** yang fleksibel

Sistem ini akan membantu petugas dalam menjalankan tugas harian dengan lebih efektif.

**Ingat**: Selalu ikuti prosedur yang ditetapkan dan jangan ragu untuk meminta bantuan jika mengalami kesulitan.

---

*Manual ini akan terus diperbarui seiring dengan pengembangan sistem. Versi terbaru selalu tersedia di portal internal.*

**Versi**: 1.0  
**Tanggal Update**: 2024-07-15  
**Tim Pengembang**: Dokterku Development Team