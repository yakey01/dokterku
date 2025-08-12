<?php

namespace Database\Seeders;

use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MasterPengeluaranSeeder extends Seeder
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

        $this->command->info('Creating Master Pengeluaran data...');
        
        // ✅ 1. Konsumsi / Minuman / Makanan
        $konsumsiData = [
            [
                'kode_pengeluaran' => 'EXP-KON-001',
                'nama_pengeluaran' => 'Konsumsi dokter',
                'kategori' => 'konsumsi',
                'keterangan' => 'Biaya konsumsi untuk dokter yang bertugas'
            ],
            [
                'kode_pengeluaran' => 'EXP-KON-002',
                'nama_pengeluaran' => 'Konsumsi',
                'kategori' => 'konsumsi',
                'keterangan' => 'Biaya konsumsi umum untuk staf klinik'
            ],
            [
                'kode_pengeluaran' => 'EXP-KON-003',
                'nama_pengeluaran' => 'Beli bebek goreng belakang',
                'kategori' => 'konsumsi',
                'keterangan' => 'Pembelian makanan bebek goreng untuk konsumsi'
            ],
            [
                'kode_pengeluaran' => 'EXP-KON-004',
                'nama_pengeluaran' => 'Beli aqua',
                'kategori' => 'konsumsi',
                'keterangan' => 'Pembelian air mineral untuk kebutuhan klinik'
            ],
            [
                'kode_pengeluaran' => 'EXP-KON-005',
                'nama_pengeluaran' => 'Coca cola',
                'kategori' => 'konsumsi',
                'keterangan' => 'Pembelian minuman untuk konsumsi klinik'
            ],
        ];
        
        $this->createPengeluaranData('🥤 Konsumsi / Minuman / Makanan', $konsumsiData, $defaultUser->id);
        
        // ✅ 2. Belanja Alat & Bahan Habis Pakai
        $belanjaData = [
            [
                'kode_pengeluaran' => 'EXP-ALT-001',
                'nama_pengeluaran' => 'Kresek kinik',
                'kategori' => 'alat_bahan',
                'keterangan' => 'Pembelian kantong plastik untuk keperluan klinik'
            ],
            [
                'kode_pengeluaran' => 'EXP-ALT-002',
                'nama_pengeluaran' => 'Belanja plastik',
                'kategori' => 'alat_bahan',
                'keterangan' => 'Pembelian bahan plastik untuk kebutuhan operasional'
            ],
            [
                'kode_pengeluaran' => 'EXP-ALT-003',
                'nama_pengeluaran' => 'Belanja tisu, A4, bayclean, spidol',
                'kategori' => 'alat_bahan',
                'keterangan' => 'Pembelian alat tulis dan kebersihan kantor'
            ],
            [
                'kode_pengeluaran' => 'EXP-ALT-004',
                'nama_pengeluaran' => 'Beli tinta',
                'kategori' => 'alat_bahan',
                'keterangan' => 'Pembelian tinta untuk printer kantor'
            ],
            [
                'kode_pengeluaran' => 'EXP-ALT-005',
                'nama_pengeluaran' => 'Belanja gigi',
                'kategori' => 'alat_bahan',
                'keterangan' => 'Pembelian alat dan bahan untuk perawatan gigi'
            ],
            [
                'kode_pengeluaran' => 'EXP-ALT-006',
                'nama_pengeluaran' => 'FC Lembar KB',
                'kategori' => 'alat_bahan',
                'keterangan' => 'Fotocopy lembar Keluarga Berencana'
            ],
        ];
        
        $this->createPengeluaranData('🛠️ Belanja Alat & Bahan Habis Pakai', $belanjaData, $defaultUser->id);
        
        // ✅ 3. Akomodasi & Transportasi
        $akomodasiData = [
            [
                'kode_pengeluaran' => 'EXP-AKO-001',
                'nama_pengeluaran' => 'Akomodasi Home Visite',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Biaya akomodasi untuk kunjungan rumah pasien'
            ],
            [
                'kode_pengeluaran' => 'EXP-AKO-002',
                'nama_pengeluaran' => 'Transport suji',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Biaya transportasi ke daerah Suji'
            ],
            [
                'kode_pengeluaran' => 'EXP-AKO-003',
                'nama_pengeluaran' => 'Akomodasi rapat',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Biaya akomodasi untuk menghadiri rapat'
            ],
            [
                'kode_pengeluaran' => 'EXP-AKO-004',
                'nama_pengeluaran' => 'Akomodasi pelatihan',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Biaya akomodasi untuk mengikuti pelatihan medis'
            ],
            [
                'kode_pengeluaran' => 'EXP-AKO-005',
                'nama_pengeluaran' => 'Akomodasi BPJS',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Biaya akomodasi untuk urusan BPJS'
            ],
            [
                'kode_pengeluaran' => 'EXP-AKO-006',
                'nama_pengeluaran' => 'Akomodasi Home Visite tgl 15',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Biaya akomodasi kunjungan rumah pada tanggal 15'
            ],
            [
                'kode_pengeluaran' => 'EXP-AKO-007',
                'nama_pengeluaran' => 'Bensin sabita',
                'kategori' => 'akomodasi_transport',
                'keterangan' => 'Pembelian bahan bakar untuk kendaraan sabita'
            ],
        ];
        
        $this->createPengeluaranData('🚗 Akomodasi & Transportasi', $akomodasiData, $defaultUser->id);
        
        // ✅ 4. Obat & Alkes
        $obatData = [
            [
                'kode_pengeluaran' => 'EXP-OBT-001',
                'nama_pengeluaran' => 'Order obat pelita',
                'kategori' => 'obat_alkes',
                'keterangan' => 'Pemesanan obat-obatan dari apotek Pelita'
            ],
        ];
        
        $this->createPengeluaranData('💊 Obat & Alkes', $obatData, $defaultUser->id);

        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();
        
        $totalItems = count($konsumsiData) + count($belanjaData) + count($akomodasiData) + count($obatData);
        $this->command->info("✅ Successfully seeded {$totalItems} master pengeluaran records:");
        $this->command->info("   🥤 Konsumsi: " . count($konsumsiData) . " items");
        $this->command->info("   🛠️ Alat & Bahan: " . count($belanjaData) . " items");
        $this->command->info("   🚗 Akomodasi & Transport: " . count($akomodasiData) . " items");
        $this->command->info("   💊 Obat & Alkes: " . count($obatData) . " items");
    }
    
    /**
     * Create pengeluaran records for a specific category
     */
    private function createPengeluaranData(string $categoryName, array $items, int $userId): void
    {
        $this->command->info("Creating {$categoryName} items...");
        
        foreach ($items as $item) {
            $pengeluaranData = [
                'kode_pengeluaran' => $item['kode_pengeluaran'],
                'nama_pengeluaran' => $item['nama_pengeluaran'],
                'kategori' => $item['kategori'],
                'keterangan' => $item['keterangan'],
                'tanggal' => now()->format('Y-m-d'),
                'nominal' => 0, // Will be set when actual transactions occur
                'input_by' => $userId,
                'status_validasi' => 'disetujui', // Master data is pre-approved
                'validasi_by' => $userId,
                'validasi_at' => now(),
            ];
            
            Pengeluaran::firstOrCreate(
                ['kode_pengeluaran' => $item['kode_pengeluaran']],
                $pengeluaranData
            );

            $this->command->info("  ✓ {$item['kode_pengeluaran']} - {$item['nama_pengeluaran']}");
        }
    }
}