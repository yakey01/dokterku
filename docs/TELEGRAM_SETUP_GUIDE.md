# 🤖 Telegram Bot Setup Guide - Dokterku

## 1. Buat Bot Telegram

### A. Hubungi BotFather
1. Buka Telegram, cari: `@BotFather`
2. Kirim perintah: `/newbot`
3. Ikuti instruksi:
   ```
   BotFather: Alright, a new bot. How are we going to call it? Please choose a name for your bot.
   You: Dokterku Bot
   
   BotFather: Good. Now let's choose a username for your bot. It must end in `bot`. Like this, for example: TetrisBot or tetris_bot.
   You: dokterku_clinic_bot
   ```

### B. Dapatkan Token
Setelah sukses, BotFather akan berikan token seperti:
```
Use this token to access the HTTP API:
1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789
```

### C. Update .env File
```bash
TELEGRAM_BOT_TOKEN=1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789
TELEGRAM_ADMIN_CHAT_ID=your_admin_chat_id
```

## 2. Setup Chat ID untuk Setiap Role

### A. Chat ID Personal (untuk Admin)
1. Cari bot: `@userinfobot`
2. Kirim `/start`
3. Bot akan berikan Chat ID anda
4. Copy Chat ID tersebut ke .env

### B. Chat ID Grup (untuk Role lain)
1. **Buat grup Telegram** dengan nama:
   - "Dokterku - Admin"
   - "Dokterku - Manajer" 
   - "Dokterku - Bendahara"
   - "Dokterku - Petugas"
   - "Dokterku - Dokter"
   - "Dokterku - Paramedis"

2. **Tambahkan bot ke grup**:
   - Invite `@dokterku_clinic_bot` ke setiap grup
   - Jadikan bot sebagai admin grup

3. **Dapatkan Chat ID grup**:
   - Forward pesan dari grup ke `@userinfobot`
   - Bot akan berikan Chat ID grup (biasanya dimulai dengan `-100`)

## 3. Test Bot di Laravel

### A. Update .env dengan Token Valid
```bash
TELEGRAM_BOT_TOKEN=1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789
TELEGRAM_ADMIN_CHAT_ID=123456789
```

### B. Clear Config Cache
```bash
php artisan config:clear
php artisan cache:clear
```

### C. Test di Settings Page
1. Buka: `/settings/telegram`
2. Masukkan Admin Chat ID
3. Klik "Test Kirim Notifikasi"
4. Periksa Telegram untuk pesan test

## 4. Konfigurasi Role-based Notifications

### Input Chat ID per Role:
- **Admin**: Chat ID personal admin
- **Manajer**: Chat ID grup manajer  
- **Bendahara**: Chat ID grup bendahara
- **Petugas**: Chat ID grup petugas
- **Dokter**: Chat ID grup dokter
- **Paramedis**: Chat ID grup paramedis

### Pilih Jenis Notifikasi per Role:
- **Petugas**: ✅ Pendapatan Baru, ✅ Pasien Baru, ✅ Rekap Harian
- **Bendahara**: ✅ Validasi Approved, ✅ Rekap Harian, ✅ Rekap Mingguan
- **Admin**: ✅ User Baru, ✅ Error Sistem, ✅ Backup Status
- **Manajer**: ✅ Rekap Harian, ✅ Rekap Mingguan
- **Dokter**: ✅ Pasien Baru, ✅ Rekap Harian
- **Paramedis**: ✅ Pasien Baru, ✅ Rekap Harian

## 5. Test Complete Setup

### A. Test Individual Role
1. Masukkan Chat ID untuk satu role
2. Pilih jenis notifikasi
3. Save konfigurasi
4. Trigger event (contoh: buat pasien baru)
5. Periksa notifikasi di grup yang sesuai

### B. Test All Roles
1. Konfigurasi semua role dengan Chat ID
2. Trigger berbagai event
3. Verify notifikasi sampai ke grup yang benar

## 6. Production Deployment

### A. Environment Variables
```bash
# Production .env
TELEGRAM_BOT_TOKEN=your_production_bot_token
TELEGRAM_ADMIN_CHAT_ID=your_production_admin_chat_id
```

### B. Queue Configuration
```bash
# Ensure queue is running for notifications
php artisan queue:work --tries=3
```

### C. Monitoring
- Monitor log files untuk error Telegram API
- Setup fallback notification jika Telegram down
- Regular test notifikasi untuk ensure connectivity

---

## 🔧 Troubleshooting

### Error: "Not Found"
- ✅ Periksa token bot valid
- ✅ Bot sudah di-start minimal sekali
- ✅ Token ada di .env dan config di-clear

### Error: "Chat not found"  
- ✅ Chat ID benar (personal atau grup)
- ✅ Bot sudah ada di grup (untuk grup Chat ID)
- ✅ Bot punya permission send message

### Error: "Forbidden"
- ✅ Bot di-block user (untuk personal chat)
- ✅ Bot di-kick dari grup (untuk grup chat)
- ✅ Bot belum jadi admin grup

### Notifikasi tidak sampai
- ✅ Role configuration aktif
- ✅ Event listener berjalan
- ✅ Queue worker aktif
- ✅ Network connection ke Telegram API

---

**📞 Support**: Jika ada masalah, periksa Laravel log di `storage/logs/laravel.log`