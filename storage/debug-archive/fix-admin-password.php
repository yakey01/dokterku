<?php

/**
 * Fix Admin Password Script
 * 
 * This script will properly reset the admin password with correct bcrypt hashing
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== FIXING ADMIN PASSWORD ===\n\n";

// Find admin user
$adminUser = User::where('email', 'admin@dokterkuklinik.com')->first();

if (!$adminUser) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "Found admin user:\n";
echo "- ID: {$adminUser->id}\n";
echo "- Email: {$adminUser->email}\n";
echo "- Current password hash: " . substr($adminUser->password, 0, 30) . "...\n\n";

// Test current password
echo "Testing current password 'admin123'...\n";
if (Hash::check('admin123', $adminUser->password)) {
    echo "✅ Password 'admin123' works with current hash\n";
} else {
    echo "❌ Password 'admin123' does NOT work with current hash\n";
    echo "📝 Regenerating password hash...\n\n";
    
    // Generate new hash
    $newPassword = 'admin123';
    $newHash = Hash::make($newPassword);
    
    echo "New password hash: " . substr($newHash, 0, 30) . "...\n";
    
    // Test new hash before updating
    if (Hash::check($newPassword, $newHash)) {
        echo "✅ New hash verification successful\n";
        
        // Update in database
        $updated = DB::table('users')
            ->where('id', $adminUser->id)
            ->update([
                'password' => $newHash,
                'updated_at' => now()
            ]);
        
        if ($updated) {
            echo "✅ Password updated in database\n\n";
            
            // Verify update worked
            $updatedUser = User::find($adminUser->id);
            if (Hash::check($newPassword, $updatedUser->password)) {
                echo "✅ VERIFICATION: Password 'admin123' now works!\n";
                echo "✅ Admin user can now login with: admin@dokterkuklinik.com / admin123\n";
            } else {
                echo "❌ VERIFICATION FAILED: Something went wrong\n";
            }
        } else {
            echo "❌ Failed to update password in database\n";
        }
    } else {
        echo "❌ New hash verification failed\n";
    }
}

echo "\n=== ADMIN PASSWORD FIX COMPLETED ===\n";