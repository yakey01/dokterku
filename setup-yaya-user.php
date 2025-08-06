<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Hash;

echo "🔧 SETTING UP YAYA TEST USER\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Find or create user
$user = User::where('email', 'yaya@dokter.local')->first();
if (!$user) {
    $user = User::create([
        'name' => 'dr Yaya Mulyana, M.Kes',
        'email' => 'yaya@dokter.local', 
        'password' => Hash::make('password'),
        'email_verified_at' => now()
    ]);
    echo "✅ Created new user: {$user->email}\n";
} else {
    echo "ℹ️  User already exists: {$user->email}\n";
}

// Find or create pegawai record
$pegawai = Pegawai::where('user_id', $user->id)->first();
if (!$pegawai) {
    // Check if there's an existing pegawai for Yaya (try multiple approaches)
    $existingPegawai = Pegawai::where('nama_lengkap', 'LIKE', '%Yaya%')->first();
    
    if (!$existingPegawai) {
        // Try searching by similar names
        $existingPegawai = Pegawai::where('nama_lengkap', 'LIKE', '%dr%')
            ->where('nama_lengkap', 'LIKE', '%M.Kes%')
            ->first();
    }
    
    if (!$existingPegawai) {
        // Find any doctor pegawai without user_id
        $existingPegawai = Pegawai::where('jenis_pegawai', 'Dokter')
            ->whereNull('user_id')
            ->first();
    }
    
    if ($existingPegawai) {
        $existingPegawai->update([
            'user_id' => $user->id,
            'nama_lengkap' => 'dr Yaya Mulyana, M.Kes' // Update name to match
        ]);
        $pegawai = $existingPegawai;
        echo "✅ Linked existing pegawai (ID: {$existingPegawai->id}) to user\n";
    } else {
        // Create with unique NIK
        $uniqueNik = '3333' . str_pad($user->id, 12, '0', STR_PAD_LEFT);
        $pegawai = Pegawai::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'dr Yaya Mulyana, M.Kes',
            'nik' => $uniqueNik,
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1980-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_telepon' => '081234567890',
            'jenis_pegawai' => 'Dokter',
            'unit_kerja' => 'Dokter Jaga',
            'jabatan' => 'Dokter',
            'status' => 'Aktif'
        ]);
        echo "✅ Created new pegawai record with NIK: {$uniqueNik}\n";
    }
} else {
    echo "ℹ️  Pegawai already linked\n";
}

echo "\n🎯 SETUP COMPLETE:\n";
echo "Email: {$user->email}\n";
echo "Password: password\n";
echo "User ID: {$user->id}\n";
echo "Pegawai ID: {$pegawai->id}\n";
echo "Pegawai Name: {$pegawai->nama_lengkap}\n";

echo "\n📱 TEST AUTHENTICATION:\n";
echo "1. Login at: http://127.0.0.1:8000/login\n";
echo "2. Or API login: http://127.0.0.1:8000/api/v2/auth/login\n";
echo "3. Test endpoint: http://127.0.0.1:8000/dokter/web-api/jadwal-jaga\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "✨ READY FOR TESTING!\n";