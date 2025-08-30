<?php
/**
 * INDONESIAN GREETING DEPLOYMENT VALIDATION
 * 
 * This script verifies that the Indonesian greeting fixes for dr. Yaya's dashboard
 * have been successfully deployed and are working correctly.
 */

echo "🩺 INDONESIAN GREETING DEPLOYMENT VALIDATION\n";
echo "=====================================\n\n";

// 1. File existence validation
echo "1. FILE EXISTENCE VALIDATION\n";
echo "-----------------------------\n";

$originalFile = __DIR__ . '/resources/js/components/dokter/HolisticMedicalDashboard.tsx';
$fixedFile = __DIR__ . '/resources/js/components/dokter/HolisticMedicalDashboardFixed.tsx';
$optimizedFile = __DIR__ . '/resources/js/components/dokter/OptimizedOriginalDashboard.tsx';

if (file_exists($fixedFile)) {
    echo "✅ Fixed Component exists: HolisticMedicalDashboardFixed.tsx\n";
} else {
    echo "❌ Fixed Component missing: HolisticMedicalDashboardFixed.tsx\n";
}

if (file_exists($optimizedFile)) {
    echo "✅ Active Component exists: OptimizedOriginalDashboard.tsx\n";
} else {
    echo "❌ Active Component missing: OptimizedOriginalDashboard.tsx\n";
}

// 2. Indonesian greeting validation in active component
echo "\n2. INDONESIAN GREETING VALIDATION\n";
echo "----------------------------------\n";

if (file_exists($optimizedFile)) {
    $optimizedContent = file_get_contents($optimizedFile);
    
    $indonesianGreetings = [
        'Selamat Pagi',
        'Selamat Siang', 
        'Selamat Malam'
    ];
    
    $foundGreetings = [];
    foreach ($indonesianGreetings as $greeting) {
        if (strpos($optimizedContent, $greeting) !== false) {
            $foundGreetings[] = $greeting;
            echo "✅ Found Indonesian greeting: $greeting\n";
        } else {
            echo "❌ Missing Indonesian greeting: $greeting\n";
        }
    }
    
    // Check for English greetings (should be removed)
    $englishGreetings = [
        'Good Morning, Doctor!',
        'Good Afternoon, Doctor!',
        'Good Evening, Doctor!'
    ];
    
    $foundEnglish = [];
    foreach ($englishGreetings as $greeting) {
        if (strpos($optimizedContent, $greeting) !== false) {
            $foundEnglish[] = $greeting;
            echo "⚠️  Still contains English greeting: $greeting\n";
        }
    }
    
    if (count($foundGreetings) === 3 && count($foundEnglish) === 0) {
        echo "🎉 PERFECT: All Indonesian greetings present, no English greetings found!\n";
    } elseif (count($foundGreetings) === 3) {
        echo "✅ GOOD: All Indonesian greetings present (but English greetings still exist)\n";
    } else {
        echo "❌ ISSUE: Missing Indonesian greetings\n";
    }
} else {
    echo "❌ Cannot validate - OptimizedOriginalDashboard.tsx not found\n";
}

// 3. Build assets validation
echo "\n3. BUILD ASSETS VALIDATION\n";
echo "--------------------------\n";

$buildDir = __DIR__ . '/public/build/assets/js';
$manifestFile = __DIR__ . '/public/build/manifest.json';

if (file_exists($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true);
    
    if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
        $assetInfo = $manifest['resources/js/dokter-mobile-app.tsx'];
        $assetFile = __DIR__ . '/public/build/' . $assetInfo['file'];
        
        echo "✅ Manifest entry exists for dokter-mobile-app.tsx\n";
        echo "📄 Asset file: " . $assetInfo['file'] . "\n";
        
        if (file_exists($assetFile)) {
            echo "✅ Built asset file exists\n";
            
            // Check if Indonesian greetings are in built file
            $builtContent = file_get_contents($assetFile);
            $indonesianInBuilt = 0;
            
            foreach ($indonesianGreetings as $greeting) {
                if (strpos($builtContent, $greeting) !== false) {
                    $indonesianInBuilt++;
                }
            }
            
            echo "📊 Indonesian greetings in built file: $indonesianInBuilt/3\n";
            
            if ($indonesianInBuilt === 3) {
                echo "🎉 EXCELLENT: All Indonesian greetings are in the built JavaScript!\n";
            } else {
                echo "⚠️  WARNING: Not all Indonesian greetings found in built file\n";
            }
            
            // Check file size and modification time
            $fileSize = filesize($assetFile);
            $fileTime = filemtime($assetFile);
            
            echo "📊 Asset file size: " . number_format($fileSize) . " bytes\n";
            echo "⏰ Last modified: " . date('Y-m-d H:i:s', $fileTime) . "\n";
            
            // Check if modified recently (within last hour)
            if (time() - $fileTime < 3600) {
                echo "✅ Asset was built recently (within last hour)\n";
            } else {
                echo "⚠️  Asset is older than 1 hour - may need rebuild\n";
            }
            
        } else {
            echo "❌ Built asset file missing: " . $assetFile . "\n";
        }
    } else {
        echo "❌ No manifest entry for dokter-mobile-app.tsx\n";
    }
} else {
    echo "❌ Build manifest not found\n";
}

// 4. Component usage validation
echo "\n4. COMPONENT USAGE VALIDATION\n";
echo "-----------------------------\n";

$mainAppFile = __DIR__ . '/resources/js/dokter-mobile-app.tsx';
if (file_exists($mainAppFile)) {
    $appContent = file_get_contents($mainAppFile);
    
    // Check which component is being used by default
    if (strpos($appContent, 'OptimizedOriginalDashboard') !== false) {
        echo "✅ OptimizedOriginalDashboard is imported\n";
        
        // Check if it's the default component (used without parameters)
        if (strpos($appContent, 'useOptimized') !== false && 
            strpos($appContent, '(!useOriginal && !window.location.search.includes(\'legacy=true\'))') !== false) {
            echo "✅ OptimizedOriginalDashboard is used by default\n";
            echo "🎯 This means Indonesian greetings will show by default!\n";
        } else {
            echo "⚠️  OptimizedOriginalDashboard usage logic may have changed\n";
        }
    } else {
        echo "❌ OptimizedOriginalDashboard not found in main app\n";
    }
    
    if (strpos($appContent, 'HolisticMedicalDashboard') !== false) {
        echo "✅ HolisticMedicalDashboard is still available as fallback\n";
    }
} else {
    echo "❌ Main app file not found\n";
}

// 5. API integration check
echo "\n5. API INTEGRATION CHECK\n";
echo "------------------------\n";

if (file_exists($optimizedFile)) {
    $optimizedContent = file_get_contents($optimizedFile);
    
    // Check for real API data usage
    if (strpos($optimizedContent, 'userData?.name') !== false) {
        echo "✅ Component uses real user data (userData?.name)\n";
    }
    
    if (strpos($optimizedContent, 'getDashboard') !== false) {
        echo "✅ Component calls real dashboard API\n";
    }
    
    if (strpos($optimizedContent, 'patients_month') !== false || 
        strpos($optimizedContent, 'patient_count') !== false) {
        echo "✅ Component displays real patient count data\n";
    }
    
    // Check for personalized greeting function
    if (strpos($optimizedContent, 'getPersonalizedGreeting') !== false) {
        echo "✅ Uses personalized greeting function\n";
    }
    
    if (strpos($optimizedContent, 'firstName') !== false) {
        echo "✅ Extracts first name for personalization\n";
    }
}

// Final summary
echo "\n🏆 DEPLOYMENT SUMMARY\n";
echo "====================\n";

$issues = [];
$successes = [];

// Check critical components
if (!file_exists($optimizedFile)) {
    $issues[] = "OptimizedOriginalDashboard.tsx missing";
} else {
    $optimizedContent = file_get_contents($optimizedFile);
    
    if (strpos($optimizedContent, 'Selamat Pagi') !== false &&
        strpos($optimizedContent, 'Selamat Siang') !== false &&
        strpos($optimizedContent, 'Selamat Malam') !== false) {
        $successes[] = "Indonesian greetings in source code";
    } else {
        $issues[] = "Indonesian greetings missing from source";
    }
}

// Check built assets
if (file_exists($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true);
    if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
        $assetInfo = $manifest['resources/js/dokter-mobile-app.tsx'];
        $assetFile = __DIR__ . '/public/build/' . $assetInfo['file'];
        
        if (file_exists($assetFile)) {
            $builtContent = file_get_contents($assetFile);
            if (strpos($builtContent, 'Selamat Pagi') !== false) {
                $successes[] = "Indonesian greetings in built assets";
            } else {
                $issues[] = "Indonesian greetings missing from built assets";
            }
        } else {
            $issues[] = "Built asset file missing";
        }
    } else {
        $issues[] = "Asset manifest entry missing";
    }
} else {
    $issues[] = "Build manifest missing";
}

echo "✅ SUCCESSES (" . count($successes) . "):\n";
foreach ($successes as $success) {
    echo "   • $success\n";
}

if (!empty($issues)) {
    echo "\n❌ ISSUES (" . count($issues) . "):\n";
    foreach ($issues as $issue) {
        echo "   • $issue\n";
    }
    echo "\n🔧 ACTION REQUIRED: Fix the issues above\n";
} else {
    echo "\n🎉 PERFECT DEPLOYMENT! No issues found.\n";
    echo "\n✨ Dr. Yaya's dashboard should now show:\n";
    echo "   • Personalized Indonesian greetings (Selamat Pagi, dr. Yaya!)\n";
    echo "   • Real patient count data from API\n";
    echo "   • Proper time-based greeting changes\n";
}

echo "\n🌐 TEST DEPLOYMENT:\n";
echo "   Visit: https://dokterku.herd/mobile/dokter\n";
echo "   Expected: Indonesian greeting for current time\n";
echo "   Expected: Real patient data displayed\n";

?>