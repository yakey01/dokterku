<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find Dr. Yaya Rindang
$user = \App\Models\User::where('name', 'LIKE', '%Rindang%')->first();

if ($user) {
    echo "Found user: {$user->name} (ID: {$user->id})\n";
    
    // Check if doctor record exists
    $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
    
    if (!$dokter) {
        echo "Creating doctor record for Dr. Rindang...\n";
        
        $dokter = \App\Models\Dokter::create([
            'user_id' => $user->id,
            'nama_lengkap' => $user->name,
            'nik' => '1234567890123456',
            'jabatan' => 'dokter_umum',
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "✅ Doctor record created with ID: {$dokter->id}\n";
    } else {
        echo "Doctor record already exists with ID: {$dokter->id}\n";
        
        // Ensure it's active
        if (!$dokter->aktif) {
            $dokter->update(['aktif' => true]);
            echo "✅ Doctor record activated\n";
        }
    }
    
    echo "Doctor details:\n";
    echo "  - Name: {$dokter->nama_lengkap}\n";
    echo "  - Jabatan: {$dokter->jabatan}\n";
    echo "  - Active: " . ($dokter->aktif ? 'Yes' : 'No') . "\n";
    
} else {
    echo "❌ Could not find Dr. Rindang user\n";
}