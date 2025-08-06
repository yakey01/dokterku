<?php
// Include Laravel bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('http://localhost', 'GET')
);
$kernel->terminate($request, $response);

use App\Models\User;
use App\Models\Dokter;

// Test for all dokter users
$dokters = User::whereHas('roles', function($q) {
    $q->where('name', 'dokter');
})->get();

echo "<h2>All Dokter Users</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Dokter Name</th><th>Display Name</th></tr>";

foreach ($dokters as $user) {
    $dokterRecord = Dokter::where('user_id', $user->id)->first();
    $displayName = $dokterRecord ? $dokterRecord->nama_lengkap : $user->name;
    
    echo "<tr>";
    echo "<td>{$user->id}</td>";
    echo "<td>{$user->name}</td>";
    echo "<td>{$user->email}</td>";
    echo "<td>" . ($dokterRecord ? $dokterRecord->nama_lengkap : 'N/A') . "</td>";
    echo "<td><strong>{$displayName}</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Show current logged in user
if (auth()->check()) {
    $currentUser = auth()->user();
    echo "<h2>Current Logged In User</h2>";
    echo "<p>Name: {$currentUser->name}</p>";
    echo "<p>Email: {$currentUser->email}</p>";
    echo "<p>Roles: " . $currentUser->getRoleNames()->implode(', ') . "</p>";
} else {
    echo "<h2>No user logged in</h2>";
}