<?php

namespace Database\Seeders;

use App\Models\Pendapatan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MasterPendapatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints
        Schema::disableForeignKeyConstraints();

        // Get first user as default input_by
        $defaultUser = User::first();
        
        if (!$defaultUser) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Creating master pendapatan data...');

        // 1. PENDAPATAN DARI LAYANAN MEDIS
        $layananMedis = [
            // Layanan Umum
            [
                'kode_pendapatan' => 'LM-001',
                'nama_pendapatan' => 'Konsultasi Dokter Umum',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari konsultasi dokter umum'
            ],
            [
                'kode_pendapatan' => 'LM-002',
                'nama_pendapatan' => 'Pemeriksaan Fisik Umum',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan fisik umum'
            ],
            [
                'kode_pendapatan' => 'LM-003',
                'nama_pendapatan' => 'Tindakan Medis Ringan',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari tindakan medis ringan'
            ],
            [
                'kode_pendapatan' => 'LM-004',
                'nama_pendapatan' => 'Penanganan Luka',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari penanganan dan perawatan luka'
            ],
            [
                'kode_pendapatan' => 'LM-005',
                'nama_pendapatan' => 'Injeksi/Suntik',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari tindakan injeksi atau suntik'
            ],

            // Layanan Gigi
            [
                'kode_pendapatan' => 'LM-101',
                'nama_pendapatan' => 'Konsultasi Dokter Gigi',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari konsultasi dokter gigi'
            ],
            [
                'kode_pendapatan' => 'LM-102',
                'nama_pendapatan' => 'Pemeriksaan Gigi dan Mulut',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan gigi dan mulut'
            ],
            [
                'kode_pendapatan' => 'LM-103',
                'nama_pendapatan' => 'Scaling Gigi',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari tindakan scaling dan pembersihan gigi'
            ],
            [
                'kode_pendapatan' => 'LM-104',
                'nama_pendapatan' => 'Penambalan Gigi',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari tindakan penambalan gigi'
            ],
            [
                'kode_pendapatan' => 'LM-105',
                'nama_pendapatan' => 'Pencabutan Gigi',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'layanan_medis',
                'keterangan' => 'Pendapatan dari tindakan pencabutan gigi'
            ],
        ];

        // 2. PENDAPATAN DARI PENUNJANG MEDIS (LABORATORIUM)
        $laboratorium = [
            [
                'kode_pendapatan' => 'LAB-001',
                'nama_pendapatan' => 'Tes Darah Lengkap',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan darah lengkap (CBC)'
            ],
            [
                'kode_pendapatan' => 'LAB-002',
                'nama_pendapatan' => 'Tes Urin Lengkap',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan urin lengkap (urinalisis)'
            ],
            [
                'kode_pendapatan' => 'LAB-003',
                'nama_pendapatan' => 'Tes Gula Darah Puasa',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kadar gula darah puasa'
            ],
            [
                'kode_pendapatan' => 'LAB-004',
                'nama_pendapatan' => 'Tes Gula Darah Sewaktu',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kadar gula darah sewaktu'
            ],
            [
                'kode_pendapatan' => 'LAB-005',
                'nama_pendapatan' => 'Tes Gula Darah 2 Jam PP',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari tes gula darah 2 jam post prandial'
            ],
            [
                'kode_pendapatan' => 'LAB-006',
                'nama_pendapatan' => 'Tes HbA1c',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan hemoglobin terglikolasi'
            ],
            [
                'kode_pendapatan' => 'LAB-007',
                'nama_pendapatan' => 'Tes Kolesterol Total',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kadar kolesterol total'
            ],
            [
                'kode_pendapatan' => 'LAB-008',
                'nama_pendapatan' => 'Tes Kolesterol HDL',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kolesterol HDL (baik)'
            ],
            [
                'kode_pendapatan' => 'LAB-009',
                'nama_pendapatan' => 'Tes Kolesterol LDL',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kolesterol LDL (jahat)'
            ],
            [
                'kode_pendapatan' => 'LAB-010',
                'nama_pendapatan' => 'Tes Trigliserida',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kadar trigliserida'
            ],
            [
                'kode_pendapatan' => 'LAB-011',
                'nama_pendapatan' => 'Tes Asam Urat',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kadar asam urat'
            ],
            [
                'kode_pendapatan' => 'LAB-012',
                'nama_pendapatan' => 'Tes Kreatinin',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan fungsi ginjal (kreatinin)'
            ],
            [
                'kode_pendapatan' => 'LAB-013',
                'nama_pendapatan' => 'Tes Ureum',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan kadar ureum'
            ],
            [
                'kode_pendapatan' => 'LAB-014',
                'nama_pendapatan' => 'Tes SGOT/ALT',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan fungsi hati SGOT/ALT'
            ],
            [
                'kode_pendapatan' => 'LAB-015',
                'nama_pendapatan' => 'Tes SGPT/AST',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari pemeriksaan fungsi hati SGPT/AST'
            ],
            [
                'kode_pendapatan' => 'LAB-016',
                'nama_pendapatan' => 'Paket Medical Check Up Basic',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari paket pemeriksaan kesehatan dasar'
            ],
            [
                'kode_pendapatan' => 'LAB-017',
                'nama_pendapatan' => 'Paket Medical Check Up Premium',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penunjang_medis',
                'keterangan' => 'Pendapatan dari paket pemeriksaan kesehatan lengkap'
            ],
        ];

        // 3. PENDAPATAN DARI PENJUALAN OBAT & ALAT KESEHATAN
        $obatAlkes = [
            // Obat-obatan
            [
                'kode_pendapatan' => 'OBT-001',
                'nama_pendapatan' => 'Penjualan Obat Generik',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan obat-obatan generik'
            ],
            [
                'kode_pendapatan' => 'OBT-002',
                'nama_pendapatan' => 'Penjualan Obat Paten',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan obat-obatan bermerek/paten'
            ],
            [
                'kode_pendapatan' => 'OBT-003',
                'nama_pendapatan' => 'Penjualan Antibiotik',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan obat antibiotik'
            ],
            [
                'kode_pendapatan' => 'OBT-004',
                'nama_pendapatan' => 'Penjualan Vitamin & Suplemen',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan vitamin dan suplemen'
            ],
            [
                'kode_pendapatan' => 'OBT-005',
                'nama_pendapatan' => 'Penjualan Obat Khusus',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan obat-obatan khusus/spesialis'
            ],

            // Alat Kesehatan
            [
                'kode_pendapatan' => 'ALK-001',
                'nama_pendapatan' => 'Penjualan Alat Medis Habis Pakai',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan alat medis sekali pakai (spuit, sarung tangan, dll)'
            ],
            [
                'kode_pendapatan' => 'ALK-002',
                'nama_pendapatan' => 'Penjualan Perban & Kasa',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan perban dan kasa steril'
            ],
            [
                'kode_pendapatan' => 'ALK-003',
                'nama_pendapatan' => 'Penjualan Plester & Antiseptik',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan plester dan antiseptik'
            ],
            [
                'kode_pendapatan' => 'ALK-004',
                'nama_pendapatan' => 'Penjualan Alat Ukur Kesehatan',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan termometer, tensimeter, dll'
            ],
            [
                'kode_pendapatan' => 'ALK-005',
                'nama_pendapatan' => 'Penjualan Masker & APD',
                'sumber_pendapatan' => 'Umum',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan masker dan alat pelindung diri'
            ],

            // Alat Kesehatan Khusus Gigi
            [
                'kode_pendapatan' => 'ALG-001',
                'nama_pendapatan' => 'Penjualan Sikat Gigi Medis',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan sikat gigi khusus medis'
            ],
            [
                'kode_pendapatan' => 'ALG-002',
                'nama_pendapatan' => 'Penjualan Pasta Gigi Medis',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan pasta gigi dengan fluoride tinggi'
            ],
            [
                'kode_pendapatan' => 'ALG-003',
                'nama_pendapatan' => 'Penjualan Obat Kumur Medis',
                'sumber_pendapatan' => 'Gigi',
                'kategori' => 'penjualan_obat_alkes',
                'keterangan' => 'Pendapatan dari penjualan obat kumur antiseptik'
            ],
        ];

        // Combine all data
        $allPendapatan = array_merge($layananMedis, $laboratorium, $obatAlkes);

        // Insert data with default values
        foreach ($allPendapatan as $pendapatan) {
            $pendapatan['tanggal'] = now()->format('Y-m-d');
            $pendapatan['nominal'] = 0; // Will be set when actual transactions occur
            $pendapatan['input_by'] = $defaultUser->id;
            $pendapatan['status_validasi'] = 'disetujui'; // Master data is pre-approved
            $pendapatan['validasi_by'] = $defaultUser->id;
            $pendapatan['validasi_at'] = now();
            $pendapatan['is_aktif'] = true;
            
            Pendapatan::firstOrCreate(
                ['kode_pendapatan' => $pendapatan['kode_pendapatan']],
                $pendapatan
            );
        }

        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();

        $totalCount = count($allPendapatan);
        $this->command->info("✅ Successfully seeded {$totalCount} master pendapatan records:");
        $this->command->info("   📋 Layanan Medis: " . count($layananMedis) . " items");
        $this->command->info("   🔬 Laboratorium: " . count($laboratorium) . " items");
        $this->command->info("   💊 Obat & Alkes: " . count($obatAlkes) . " items");
    }
}