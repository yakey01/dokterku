<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database setup
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection established\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

echo "🧹 REMOVING DUMMY LEADERBOARD DATA\n";
echo "==================================\n\n";

$dummyDoctorNames = ['Dr. Dokter Umum', 'Dr. Spesialis Penyakit Dalam'];
$removedUsers = [];

foreach ($dummyDoctorNames as $doctorName) {
    echo "🔍 Searching for: $doctorName\n";
    
    // Find the user
    $stmt = $pdo->prepare("SELECT id, name, email, role_id FROM users WHERE name = ?");
    $stmt->execute([$doctorName]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "  ✅ Found user ID: {$user['id']}\n";
        $removedUsers[] = $user;
        
        // Check if user has any related data
        echo "  🔍 Checking related data...\n";
        
        // Check tindakan records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tindakan WHERE dokter_id IN (SELECT id FROM dokters WHERE user_id = ?)");
        $stmt->execute([$user['id']]);
        $tindakanCount = $stmt->fetchColumn();
        echo "    - Tindakan records: $tindakanCount\n";
        
        // Check dokter records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dokters WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $dokterCount = $stmt->fetchColumn();
        echo "    - Dokter records: $dokterCount\n";
        
        // Check jaspel records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jaspel WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $jaspelCount = $stmt->fetchColumn();
        echo "    - Jaspel records: $jaspelCount\n";
        
        // Check attendance records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $attendanceCount = $stmt->fetchColumn();
        echo "    - Attendance records: $attendanceCount\n";
        
        // Check role assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM model_has_roles WHERE model_id = ? AND model_type = 'App\\Models\\User'");
        $stmt->execute([$user['id']]);
        $roleCount = $stmt->fetchColumn();
        echo "    - Role assignments: $roleCount\n";
        
        echo "  🗑️  REMOVING USER: $doctorName (ID: {$user['id']})\n";
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Remove role assignments
            if ($roleCount > 0) {
                $stmt = $pdo->prepare("DELETE FROM model_has_roles WHERE model_id = ? AND model_type = 'App\\Models\\User'");
                $stmt->execute([$user['id']]);
                echo "    ✅ Removed role assignments\n";
            }
            
            // Remove attendance records
            if ($attendanceCount > 0) {
                $stmt = $pdo->prepare("DELETE FROM attendances WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                echo "    ✅ Removed attendance records\n";
            }
            
            // Remove jaspel records
            if ($jaspelCount > 0) {
                $stmt = $pdo->prepare("DELETE FROM jaspel WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                echo "    ✅ Removed jaspel records\n";
            }
            
            // Remove tindakan records (if any)
            if ($tindakanCount > 0) {
                $stmt = $pdo->prepare("DELETE FROM tindakan WHERE dokter_id IN (SELECT id FROM dokters WHERE user_id = ?)");
                $stmt->execute([$user['id']]);
                echo "    ✅ Removed tindakan records\n";
            }
            
            // Remove dokter records
            if ($dokterCount > 0) {
                $stmt = $pdo->prepare("DELETE FROM dokters WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                echo "    ✅ Removed dokter records\n";
            }
            
            // Finally, remove the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            echo "    ✅ Removed user record\n";
            
            // Commit transaction
            $pdo->commit();
            echo "  ✅ Successfully removed $doctorName\n\n";
            
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollback();
            echo "  ❌ Error removing $doctorName: " . $e->getMessage() . "\n\n";
        }
        
    } else {
        echo "  ❌ User not found\n\n";
    }
}

// Clear any cached data
echo "🧹 CLEARING CACHE...\n";
echo "-------------------\n";

try {
    // Clear Laravel cache
    $stmt = $pdo->query("DELETE FROM cache WHERE key LIKE '%leaderboard%' OR key LIKE '%doctor%' OR key LIKE '%dokter%'");
    $cleared = $stmt->rowCount();
    echo "✅ Cleared $cleared cache entries\n";
} catch (Exception $e) {
    echo "⚠️  Cache clear warning: " . $e->getMessage() . "\n";
}

echo "\n📊 SUMMARY:\n";
echo "===========\n";
echo "Removed " . count($removedUsers) . " dummy doctor users:\n";
foreach ($removedUsers as $user) {
    echo "  - {$user['name']} (ID: {$user['id']})\n";
}

echo "\n✅ CLEANUP COMPLETE!\n";
echo "===================\n";
echo "🔄 Next steps:\n";
echo "1. Test the leaderboard API\n";
echo "2. Check frontend for any remaining hardcoded data\n";
echo "3. Clear browser cache if needed\n";

?>