<?php

namespace App\Enums;

enum TelegramNotificationType: string
{
    // Existing notifications
    case PENDAPATAN = 'pendapatan';
    case PENGELUARAN = 'pengeluaran';
    case PASIEN = 'pasien';
    case USER_BARU = 'user_baru';
    case REKAP_HARIAN = 'rekap_harian';
    case REKAP_MINGGUAN = 'rekap_mingguan';
    case VALIDASI_DISETUJUI = 'validasi_disetujui';
    case JASPEL_SELESAI = 'jaspel_selesai';
    case BACKUP_GAGAL = 'backup_gagal';
    
    // Enhanced notifications for cross-role communication
    case PRESENSI_DOKTER = 'presensi_dokter';
    case PRESENSI_PARAMEDIS = 'presensi_paramedis';
    case TINDAKAN_BARU = 'tindakan_baru';
    case VALIDASI_PENDING = 'validasi_pending';
    case JASPEL_DOKTER_READY = 'jaspel_dokter_ready';
    case LAPORAN_SHIFT = 'laporan_shift';
    case EMERGENCY_ALERT = 'emergency_alert';
    case SISTEM_MAINTENANCE = 'sistem_maintenance';
    case APPROVAL_REQUEST = 'approval_request';
    case JADWAL_JAGA_UPDATE = 'jadwal_jaga_update';
    case CUTI_REQUEST = 'cuti_request';
    case SHIFT_ASSIGNMENT = 'shift_assignment';

    public function label(): string
    {
        return match($this) {
            // Existing notifications
            self::PENDAPATAN => '💰 Notifikasi Pendapatan',
            self::PENGELUARAN => '📉 Notifikasi Pengeluaran',
            self::PASIEN => '👤 Notifikasi Pasien Baru',
            self::USER_BARU => '👋 Notifikasi User Baru',
            self::REKAP_HARIAN => '📊 Rekap Harian',
            self::REKAP_MINGGUAN => '📈 Rekap Mingguan',
            self::VALIDASI_DISETUJUI => '✅ Validasi Disetujui',
            self::JASPEL_SELESAI => '💼 Jaspel Selesai',
            self::BACKUP_GAGAL => '🚨 Backup Gagal',
            
            // Enhanced notifications
            self::PRESENSI_DOKTER => '🩺 Presensi Dokter',
            self::PRESENSI_PARAMEDIS => '👩‍⚕️ Presensi Paramedis',
            self::TINDAKAN_BARU => '🏥 Tindakan Medis Baru',
            self::VALIDASI_PENDING => '⏳ Menunggu Validasi',
            self::JASPEL_DOKTER_READY => '💵 Jaspel Dokter Siap',
            self::LAPORAN_SHIFT => '📋 Laporan Shift',
            self::EMERGENCY_ALERT => '🚨 Alert Emergency',
            self::SISTEM_MAINTENANCE => '🔧 Sistem Maintenance',
            self::APPROVAL_REQUEST => '📝 Permohonan Persetujuan',
            self::JADWAL_JAGA_UPDATE => '📅 Update Jadwal Jaga',
            self::CUTI_REQUEST => '🏖️ Permohonan Cuti',
            self::SHIFT_ASSIGNMENT => '⏰ Penugasan Shift',
        };
    }

    public function description(): string
    {
        return match($this) {
            // Existing notifications
            self::PENDAPATAN => 'Notifikasi saat ada pendapatan baru diinput',
            self::PENGELUARAN => 'Notifikasi saat ada pengeluaran baru diinput',
            self::PASIEN => 'Notifikasi saat ada pasien baru didaftarkan',
            self::USER_BARU => 'Notifikasi saat ada user baru ditambahkan ke sistem',
            self::REKAP_HARIAN => 'Laporan rekap transaksi harian otomatis',
            self::REKAP_MINGGUAN => 'Laporan rekap transaksi mingguan otomatis',
            self::VALIDASI_DISETUJUI => 'Notifikasi saat validasi bendahara disetujui',
            self::JASPEL_SELESAI => 'Notifikasi saat perhitungan jaspel selesai',
            self::BACKUP_GAGAL => 'Alert saat backup sistem gagal',
            
            // Enhanced notifications
            self::PRESENSI_DOKTER => 'Notifikasi presensi check-in/out dokter',
            self::PRESENSI_PARAMEDIS => 'Notifikasi presensi check-in/out paramedis',
            self::TINDAKAN_BARU => 'Notifikasi saat ada tindakan medis baru',
            self::VALIDASI_PENDING => 'Notifikasi saat ada transaksi menunggu validasi',
            self::JASPEL_DOKTER_READY => 'Notifikasi jaspel dokter sudah siap diambil',
            self::LAPORAN_SHIFT => 'Laporan pergantian shift dan handover',
            self::EMERGENCY_ALERT => 'Alert untuk situasi emergency atau urgent',
            self::SISTEM_MAINTENANCE => 'Notifikasi maintenance sistem dan downtime',
            self::APPROVAL_REQUEST => 'Permintaan persetujuan dari atasan/manager',
            self::JADWAL_JAGA_UPDATE => 'Update perubahan jadwal jaga/shift',
            self::CUTI_REQUEST => 'Notifikasi permohonan cuti staff',
            self::SHIFT_ASSIGNMENT => 'Penugasan shift/jadwal kerja baru',
        };
    }

    public static function getForRole(string $role): array
    {
        return match(strtolower($role)) {
            'petugas' => [
                self::PENDAPATAN,
                self::PENGELUARAN,
                self::PASIEN,
                self::REKAP_HARIAN,
            ],
            'bendahara' => [
                self::PENDAPATAN,        // Ditambah: notifikasi input pendapatan
                self::PENGELUARAN,       // Existing: notifikasi input pengeluaran
                self::PASIEN,            // Ditambah: notifikasi tindakan/pasien
                self::VALIDASI_DISETUJUI,
                self::REKAP_HARIAN,
                self::REKAP_MINGGUAN,
            ],
            'admin' => [
                self::USER_BARU,
                self::BACKUP_GAGAL,
                self::VALIDASI_DISETUJUI,
                self::REKAP_HARIAN,
                self::REKAP_MINGGUAN,
            ],
            'manajer' => [
                self::REKAP_HARIAN,
                self::REKAP_MINGGUAN,
                self::JASPEL_SELESAI,
                self::VALIDASI_DISETUJUI,
            ],
            'dokter' => [
                self::PASIEN,                // Notifikasi pasien baru
                self::JASPEL_SELESAI,        // Notifikasi jaspel selesai
                self::REKAP_HARIAN,          // Rekap harian untuk monitoring
                self::VALIDASI_DISETUJUI,    // When bendahara validates their transactions
                self::JASPEL_DOKTER_READY,   // When their jaspel is ready
                self::JADWAL_JAGA_UPDATE,    // Schedule changes
                self::SHIFT_ASSIGNMENT,      // New shift assignments
                self::CUTI_REQUEST,          // Leave request status
                self::APPROVAL_REQUEST,      // Manager approval needed
                self::EMERGENCY_ALERT,       // Emergency situations
                self::SISTEM_MAINTENANCE,    // System maintenance notifications
            ],
            'paramedis' => [
                self::PASIEN,                // Notifikasi pasien baru
                self::REKAP_HARIAN,          // Rekap harian operasional
                self::PRESENSI_DOKTER,       // Doctor attendance for coordination
                self::TINDAKAN_BARU,         // New medical procedures
                self::JADWAL_JAGA_UPDATE,    // Schedule changes
                self::SHIFT_ASSIGNMENT,      // New shift assignments
                self::LAPORAN_SHIFT,         // Shift handover reports
                self::EMERGENCY_ALERT,       // Emergency situations
                self::CUTI_REQUEST,          // Leave request updates
                self::SISTEM_MAINTENANCE,    // System maintenance
            ],
            'non_paramedis' => [
                self::REKAP_HARIAN,          // Rekap harian umum
                self::USER_BARU,             // Notifikasi user baru (untuk staff administratif)
                self::PASIEN,                // New patient registrations
                self::JADWAL_JAGA_UPDATE,    // Schedule changes affecting support
                self::SHIFT_ASSIGNMENT,      // New shift assignments
                self::EMERGENCY_ALERT,       // Emergency situations
                self::SISTEM_MAINTENANCE,    // System maintenance
            ],
            default => [],
        };
    }

    public static function getAllOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}