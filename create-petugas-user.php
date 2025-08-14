<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Get petugas role ID
$petugasRoleId = 5; // Usually petugas is role_id 5

$user = User::where('email', 'petugas@dokterku.com')->first();
if (!$user) {
    $user = User::create([
        'name' => 'Petugas Test',
        'email' => 'petugas@dokterku.com',
        'password' => Hash::make('password123'),
        'role_id' => $petugasRoleId,
        'is_active' => true
    ]);
    echo "User created: petugas@dokterku.com / password123\n";
} else {
    $user->password = Hash::make('password123');
    $user->role_id = $petugasRoleId;
    $user->is_active = true;
    $user->save();
    echo "User updated: petugas@dokterku.com / password123\n";
}