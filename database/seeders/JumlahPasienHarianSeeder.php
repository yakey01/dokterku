<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Models\User;
use Carbon\Carbon;

class JumlahPasienHarianSeeder extends Seeder
{
    public function run()
    {
        // Find Dr. Yaya Mulyana
        $yayaDokter = Dokter::where('nama_lengkap', 'LIKE', '%Yaya%Mulyana%')
            ->orWhere('nama_lengkap', 'LIKE', '%yaya%mulyana%')
            ->first();
        
        if (!$yayaDokter) {
            // Try to find by user
            $yayaUser = User::where('name', 'LIKE', '%Yaya%Mulyana%')
                ->orWhere('name', 'LIKE', '%yaya%mulyana%')
                ->first();
            
            if ($yayaUser) {
                $yayaDokter = Dokter::where('user_id', $yayaUser->id)->first();
            }
        }
        
        if (!$yayaDokter) {
            // Create Dr. Yaya Mulyana if not exists
            $yayaUser = User::where('email', 'yaya@dokterku.com')->first();
            if (!$yayaUser) {
                $yayaUser = User::create([
                    'name' => 'Dr. Yaya Mulyana',
                    'email' => 'yaya@dokterku.com',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ]);
            }
            
            $yayaDokter = Dokter::create([
                'user_id' => $yayaUser->id,
                'nama_lengkap' => 'Dr. Yaya Mulyana',
                'nik' => '3273012345678901',
                'tanggal_lahir' => '1985-03-15',
                'jenis_kelamin' => 'L',
                'jabatan' => 'dokter_umum',
                'nomor_sip' => 'SIP.2024.001',
                'email' => 'yaya@dokterku.com',
                'aktif' => true,
                'username' => 'yaya',
                'password' => bcrypt('password'),
                'status_akun' => 'Aktif',
            ]);
        }
        
        // Create REAL JumlahPasienHarian data for Dr. Yaya Mulyana - matching Bendahara dashboard
        // Only 2 approved entries as shown in Bendahara validation
        $dataToInsert = [];
        
        // First approved entry: 08/08/2025 - 100 total (80 umum, 20 BPJS)
        $dataToInsert[] = [
            'tanggal' => Carbon::create(2025, 8, 8),
            'poli' => 'umum',
            'jumlah_pasien_umum' => 80,
            'jumlah_pasien_bpjs' => 20,
            'dokter_id' => $yayaDokter->id,
            'input_by' => 1, // Admin user
            'status_validasi' => 'approved',
            'validasi_by' => User::where('name', 'LIKE', '%fitri%tri%')->first()->id ?? 1,
            'validasi_at' => Carbon::create(2025, 8, 8, 14, 30),
            'catatan_validasi' => 'Data valid dan disetujui',
            'catatan' => 'Hari sibuk dengan total 100 pasien',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Second approved entry: 12/08/2025 - 60 total (40 umum, 20 BPJS)
        $dataToInsert[] = [
            'tanggal' => Carbon::create(2025, 8, 12),
            'poli' => 'umum',
            'jumlah_pasien_umum' => 40,
            'jumlah_pasien_bpjs' => 20,
            'dokter_id' => $yayaDokter->id,
            'input_by' => 1,
            'status_validasi' => 'approved',
            'validasi_by' => User::where('name', 'LIKE', '%fitri%tri%')->first()->id ?? 1,
            'validasi_at' => Carbon::create(2025, 8, 12, 15, 00),
            'catatan_validasi' => 'Validasi berhasil',
            'catatan' => 'Pasien normal untuk hari kerja',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Insert the data
        foreach ($dataToInsert as $data) {
            JumlahPasienHarian::updateOrCreate(
                [
                    'tanggal' => $data['tanggal'],
                    'dokter_id' => $data['dokter_id'],
                    'poli' => $data['poli'],
                ],
                $data
            );
        }
        
        $this->command->info('JumlahPasienHarian data seeded successfully for Dr. Yaya Mulyana');
        $this->command->info('Created 2 approved entries matching Bendahara dashboard:');
        $this->command->info('  - 08/08/2025: 100 pasien (80 umum, 20 BPJS)');
        $this->command->info('  - 12/08/2025: 60 pasien (40 umum, 20 BPJS)');
    }
}