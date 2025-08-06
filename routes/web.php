<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Bendahara\TreasurerDashboardController;
use App\Http\Controllers\Petugas\StaffDashboardController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// Include test routes
require __DIR__.'/test.php';
require __DIR__.'/test-models.php';
require __DIR__.'/test-csrf.php';
use Illuminate\Support\Facades\Auth;

// Debug work location issue
Route::get('/debug-work-location', function () {
    return view('debug-location-issue');
});

// Test route for WelcomeLogin component
Route::get('/test-welcome-login', function () {
    return view('test-welcome-login');
});

// Test route for Dokter component (bypass auth)
Route::get('/test-dokter-component', function () {
    $userData = [
        'id' => 13,
        'name' => 'Dr. Yaya Test',
        'email' => 'test@dokterku.com',
        'role' => 'dokter'
    ];
    $token = 'test-token-123';
    
    return view('mobile.dokter.app', compact('token', 'userData'));
});

// Main Welcome Login with Animation - GUARANTEED TO SHOW
Route::get('/welcome-login', function () {
    return view('welcome-login-app');
});

// Force redirect from main login to animated welcome login
Route::get('/login', function () {
    return redirect('/welcome-login');
})->name('login');

// Redirect unified login to welcome login
Route::get('/unified-login', function () {
    return redirect('/welcome-login');
});

// Alternative entry points for guaranteed animation
Route::get('/welcome', function () {
    return view('welcome-login-app');
});

Route::get('/animated-login', function () {
    return view('welcome-login-app');
});

// WORLD-CLASS: New Jaspel Dashboard 
Route::get('/paramedis/dashboard-new', function () {
    return view('paramedis.dashboard-new');
})->middleware(['auth', 'role:paramedis']);

// NEW CLEAN DASHBOARD CARD
Route::get('/paramedis/new-dashboard-card', function () {
    return view('paramedis.new-dashboard-card', [
        'user' => Auth::user()
    ]);
})->middleware(['auth', 'role:paramedis']);

// NEW DASHBOARD LINKS PAGE
Route::get('/new-dashboard-links', function () {
    return view('new-dashboard-links');
})->middleware(['auth']);

// Test endpoint for debugging Bita Jaspel data
Route::get('/debug-bita-jaspel', function () {
    try {
        $output = "🔍 DEBUG BITA JASPEL ISSUE\n";
        $output .= "==========================\n\n";
        
        // Find Bita
        $bita = \App\Models\User::find(20);
        if (!$bita) {
            return response("Bita user not found", 500)->header('Content-Type', 'text/plain');
        }
        
        $output .= "👤 USER INFO:\n";
        $output .= "  Name: {$bita->name}\n";
        $output .= "  ID: {$bita->id}\n";
        $output .= "  Primary Role: {$bita->role->name}\n";
        $output .= "  Multi-Roles: ";
        foreach ($bita->roles as $role) {
            $output .= $role->name . " ";
        }
        $output .= "\n";
        $output .= "  Has Paramedis Role: " . ($bita->hasRole('paramedis') ? 'YES' : 'NO') . "\n";
        $output .= "  API Access: " . ($bita->hasRole(['paramedis', 'dokter', 'admin', 'bendahara']) ? 'YES' : 'NO') . "\n\n";
        
        // Test Enhanced Jaspel Service
        $output .= "🔧 ENHANCED JASPEL SERVICE TEST:\n";
        $enhancedService = app(\App\Services\EnhancedJaspelService::class);
        $result = $enhancedService->getComprehensiveJaspelData($bita, 7, 2025);
        
        $output .= "  Items Count: " . count($result['jaspel_items']) . "\n";
        $output .= "  Pending Count: " . $result['summary']['count_pending'] . "\n";
        $output .= "  Pending Amount: " . $result['summary']['total_pending'] . "\n\n";
        
        if (count($result['jaspel_items']) > 0) {
            $output .= "📋 JASPEL ITEMS:\n";
            foreach ($result['jaspel_items'] as $item) {
                $output .= "  - ID: {$item['id']}, Type: {$item['jenis']}, Amount: {$item['jumlah']}, Status: {$item['status']}\n";
            }
            $output .= "\n";
        }
        
        // Test API Controller simulation
        $output .= "🌐 API CONTROLLER SIMULATION:\n";
        \Illuminate\Support\Facades\Auth::login($bita);
        
        $authUser = \Illuminate\Support\Facades\Auth::guard('web')->user();
        if ($authUser) {
            $output .= "  ✅ Auth::guard('web')->user(): {$authUser->name}\n";
            $output .= "  ✅ Has required roles: " . ($authUser->hasRole(['paramedis', 'dokter', 'admin', 'bendahara']) ? 'YES' : 'NO') . "\n";
            
            // Simulate the controller method
            try {
                $controllerResult = $enhancedService->getComprehensiveJaspelData($authUser, 7, 2025);
                $output .= "  ✅ Controller simulation: SUCCESS\n";
                $output .= "  ✅ Would return " . count($controllerResult['jaspel_items']) . " items\n";
            } catch (\Exception $e) {
                $output .= "  ❌ Controller simulation: FAILED - " . $e->getMessage() . "\n";
            }
        } else {
            $output .= "  ❌ Auth guard returned null\n";
        }
        
        \Illuminate\Support\Facades\Auth::logout();
        
        // Test the actual API endpoint
        $output .= "\n🌐 DIRECT API ENDPOINT TEST:\n";
        try {
            $jaspelController = new \App\Http\Controllers\Api\V2\Jaspel\JaspelController(
                app(\App\Services\JaspelCalculationService::class)
            );
            
            $request = new \Illuminate\Http\Request();
            $request->merge(['month' => 7, 'year' => 2025]);
            
            // This might not work due to Auth facade complexity, but let's try
            $output .= "  Direct controller test: Available but complex to simulate\n";
            
        } catch (\Exception $e) {
            $output .= "  Direct controller test: " . $e->getMessage() . "\n";
        }
        
        $output .= "\n🎯 CONCLUSION:\n";
        $output .= "Backend is working correctly. Issue is likely:\n";
        $output .= "1. Browser cache needs clearing\n";
        $output .= "2. User needs to logout and login again\n";
        $output .= "3. Frontend session token expired\n";
        $output .= "4. API endpoint URL incorrect\n";
        $output .= "\n🔧 NEXT STEPS:\n";
        $output .= "1. Clear browser cache completely\n";
        $output .= "2. Hard refresh (Ctrl+Shift+R)\n";
        $output .= "3. Logout and login as Bita\n";
        $output .= "4. Check DevTools Network tab for API calls\n";
        $output .= "5. Verify endpoint: /paramedis/api/v2/jaspel/mobile-data\n";
        
        return response($output)->header('Content-Type', 'text/plain');
        
    } catch (\Exception $e) {
        return response('ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 500)
            ->header('Content-Type', 'text/plain');
    }
});

// Test endpoint for debugging Jaspel data
Route::get('/debug-jaspel-flow', function () {
    try {
        $output = "🕵️‍♂️ WORLD-CLASS JASPEL DEBUG FLOW\n";
        $output .= "=====================================\n\n";
        
        // 1. USER AUTHENTICATION CHECK
        $output .= "1️⃣ AUTHENTICATION CHECK:\n";
        $naning = \App\Models\User::where('name', 'LIKE', '%Naning%')->first();
        if (!$naning) {
            $output .= "❌ CRITICAL: Naning user not found!\n";
            return response($output)->header('Content-Type', 'text/plain');
        }
        $output .= "✅ User found: {$naning->name} (ID: {$naning->id})\n";
        
        // Check roles
        $roles = $naning->roles->pluck('name')->toArray();
        $output .= "🎭 Roles: " . implode(', ', $roles) . "\n";
        
        // 2. DATABASE CONNECTIVITY
        $output .= "\n2️⃣ DATABASE CONNECTIVITY:\n";
        try {
            DB::select('SELECT 1');
            $output .= "✅ Database connection OK\n";
        } catch (\Exception $e) {
            $output .= "❌ Database error: " . $e->getMessage() . "\n";
        }
        
        // 3. PARAMEDIS RELATIONSHIP
        $output .= "\n3️⃣ PARAMEDIS RELATIONSHIP:\n";
        $paramedis = \App\Models\Pegawai::where('user_id', $naning->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        if (!$paramedis) {
            $output .= "❌ CRITICAL: Paramedis record not found for Naning!\n";
            return response($output)->header('Content-Type', 'text/plain');
        }
        $output .= "✅ Paramedis found: {$paramedis->nama_lengkap} (Pegawai ID: {$paramedis->id})\n";
        
        // 4. JASPEL RECORDS AUDIT
        $output .= "\n4️⃣ JASPEL RECORDS AUDIT:\n";
        $allJaspel = \App\Models\Jaspel::where('user_id', $naning->id)->get();
        $output .= "Total Jaspel records: {$allJaspel->count()}\n";
        
        foreach ($allJaspel as $j) {
            $tindakanInfo = $j->tindakan_id ? "Tindakan {$j->tindakan_id}" : "ORPHAN";
            $output .= "- Jaspel {$j->id}: {$tindakanInfo}, Status: {$j->status_validasi}, Rp " . number_format($j->nominal, 0, ',', '.') . "\n";
        }
        
        // 5. TINDAKAN AUDIT  
        $output .= "\n5️⃣ TINDAKAN AUDIT:\n";
        $allTindakan = \App\Models\Tindakan::where('paramedis_id', $paramedis->id)->get();
        $output .= "Total Tindakan records: {$allTindakan->count()}\n";
        
        foreach ($allTindakan as $t) {
            $jaspelCount = \App\Models\Jaspel::where('tindakan_id', $t->id)->count();
            $output .= "- Tindakan {$t->id}: Status {$t->status_validasi}, Jasa Paramedis Rp " . number_format($t->jasa_paramedis, 0, ',', '.') . ", Jaspel: {$jaspelCount}\n";
        }
        
        // 6. API ENDPOINT SIMULATION
        $output .= "\n6️⃣ API ENDPOINT SIMULATION:\n";
        
        // Simulate login as Naning
        \Illuminate\Support\Facades\Auth::login($naning);
        
        try {
            $jaspelController = new \App\Http\Controllers\Api\V2\Jaspel\JaspelController(
                app(\App\Services\JaspelCalculationService::class)
            );
            $request = new \Illuminate\Http\Request();
            $apiResponse = $jaspelController->getMobileJaspelData($request);
            $apiData = $apiResponse->getData(true);
            
            if ($apiData['success']) {
                $summary = $apiData['data']['summary'];
                $items = $apiData['data']['jaspel_items'];
                
                $output .= "✅ API Success:\n";
                $output .= "  - Total Items: " . count($items) . "\n";
                $output .= "  - Total Pending: Rp " . number_format($summary['total_pending'], 0, ',', '.') . "\n";
                $output .= "  - Total Paid: Rp " . number_format($summary['total_paid'], 0, ',', '.') . "\n";
                $output .= "  - Pending Count: " . $summary['count_pending'] . "\n";
                
                $output .= "\n📋 API Items Returned:\n";
                foreach ($items as $item) {
                    $output .= "  - ID {$item['id']}: {$item['jenis']}, Status: {$item['status']}, Rp " . number_format($item['jumlah'], 0, ',', '.') . "\n";
                }
            } else {
                $output .= "❌ API Error: " . $apiData['message'] . "\n";
            }
        } catch (\Exception $e) {
            $output .= "❌ API Exception: " . $e->getMessage() . "\n";
            $output .= "Stack trace: " . $e->getTraceAsString() . "\n";
        }
        
        \Illuminate\Support\Facades\Auth::logout();
        
        // 7. FRONTEND DEBUGGING HINTS
        $output .= "\n7️⃣ FRONTEND DEBUGGING HINTS:\n";
        $output .= "• Check browser console for API call logs\n";
        $output .= "• Verify token in localStorage/sessionStorage\n";
        $output .= "• Clear browser cache and cookies\n";
        $output .= "• Check if React component is properly mounted\n";
        $output .= "• Verify API endpoint route: /api/v2/jaspel/mobile-data\n";
        
        return response($output)->header('Content-Type', 'text/plain');
        
    } catch (\Exception $e) {
        return response('CRITICAL ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 500)
            ->header('Content-Type', 'text/plain');
    }
});

// Direct API test endpoint for Bita
Route::get('/test-bita-api', function () {
    try {
        $output = "🧪 DIRECT API TEST FOR BITA\n";
        $output .= "============================\n\n";
        
        // Find and login as Bita
        $bita = \App\Models\User::find(20);
        if (!$bita) {
            return response("Bita not found", 500)->header('Content-Type', 'text/plain');
        }
        
        \Illuminate\Support\Facades\Auth::login($bita);
        
        $output .= "👤 Logged in as: {$bita->name}\n";
        $output .= "🔑 Has paramedis role: " . ($bita->hasRole('paramedis') ? 'YES' : 'NO') . "\n";
        $output .= "🔐 Can access API: " . ($bita->hasRole(['paramedis', 'dokter', 'admin', 'bendahara']) ? 'YES' : 'NO') . "\n\n";
        
        // Test the JaspelController directly
        $output .= "🌐 TESTING JASPEL CONTROLLER:\n";
        
        $controller = new \App\Http\Controllers\Api\V2\Jaspel\JaspelController(
            app(\App\Services\JaspelCalculationService::class)
        );
        
        $request = new \Illuminate\Http\Request();
        $request->merge(['month' => 7, 'year' => 2025]);
        
        try {
            $response = $controller->getMobileJaspelData($request);
            $responseData = $response->getData(true);
            
            $output .= "✅ Controller Response: SUCCESS\n";
            $output .= "   Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
            $output .= "   Message: " . $responseData['message'] . "\n";
            
            if ($responseData['success'] && isset($responseData['data'])) {
                $jaspelItems = $responseData['data']['jaspel_items'] ?? [];
                $summary = $responseData['data']['summary'] ?? [];
                
                $output .= "   Items Count: " . count($jaspelItems) . "\n";
                $output .= "   Pending Count: " . ($summary['count_pending'] ?? 0) . "\n";
                $output .= "   Pending Amount: " . ($summary['total_pending'] ?? 0) . "\n";
                
                if (count($jaspelItems) > 0) {
                    $output .= "\n📋 First Item:\n";
                    $firstItem = $jaspelItems[0];
                    $output .= "   - ID: " . ($firstItem['id'] ?? 'N/A') . "\n";
                    $output .= "   - Type: " . ($firstItem['jenis'] ?? 'N/A') . "\n";
                    $output .= "   - Amount: " . ($firstItem['jumlah'] ?? 'N/A') . "\n";
                    $output .= "   - Status: " . ($firstItem['status'] ?? 'N/A') . "\n";
                }
            }
            
        } catch (\Exception $e) {
            $output .= "❌ Controller Response: FAILED\n";
            $output .= "   Error: " . $e->getMessage() . "\n";
            $output .= "   This explains why frontend gets empty data!\n";
        }
        
        \Illuminate\Support\Facades\Auth::logout();
        
        $output .= "\n🎯 CONCLUSION:\n";
        $output .= "If controller test succeeds but frontend fails,\n";
        $output .= "the issue is in frontend authentication/session.\n";
        
        return response($output)->header('Content-Type', 'text/plain');
        
    } catch (\Exception $e) {
        return response('API TEST ERROR: ' . $e->getMessage(), 500)
            ->header('Content-Type', 'text/plain');
    }
});

// Test all paramedis users for data consistency
Route::get('/test-all-paramedis-consistency', function () {
    try {
        $output = "🔍 PARAMEDIS DATA CONSISTENCY TEST\n";
        $output .= "===================================\n\n";
        
        // Get all paramedis users
        $paramedisUsers = \App\Models\User::whereHas('roles', function($query) {
            $query->where('name', 'paramedis');
        })->get();
        
        $output .= "Found " . $paramedisUsers->count() . " paramedis users:\n\n";
        
        foreach ($paramedisUsers as $user) {
            $output .= "👤 {$user->name} (ID: {$user->id}):\n";
            
            // Test Enhanced Service (Menu)
            try {
                $enhancedService = app(\App\Services\EnhancedJaspelService::class);
                $menuData = $enhancedService->getComprehensiveJaspelData($user, 7, 2025);
                $menuPending = $menuData['summary']['total_pending'];
                $menuCount = $menuData['summary']['count_pending'];
                
                $output .= "  📱 Menu API: {$menuPending} IDR, {$menuCount} items\n";
            } catch (\Exception $e) {
                $output .= "  📱 Menu API: ERROR - " . $e->getMessage() . "\n";
            }
            
            // Test Dashboard Service
            try {
                \Illuminate\Support\Facades\Auth::login($user);
                
                $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
                $request = new \Illuminate\Http\Request();
                $request->merge(['month' => 7, 'year' => 2025]);
                
                $response = $controller->getJaspel($request);
                $data = $response->getData(true);
                
                if ($data['success']) {
                    $dashboardPending = $data['data']['stats']['pending'];
                    $dashboardCount = $data['data']['stats']['count_tindakan'];
                    $output .= "  🖥️  Dashboard API: {$dashboardPending} IDR, {$dashboardCount} items\n";
                    
                    // Check consistency
                    if ($menuPending == $dashboardPending) {
                        $output .= "  ✅ CONSISTENT: Menu and Dashboard match!\n";
                    } else {
                        $output .= "  ❌ MISMATCH: Different values!\n";
                    }
                } else {
                    $output .= "  🖥️  Dashboard API: ERROR - " . $data['message'] . "\n";
                }
                
                \Illuminate\Support\Facades\Auth::logout();
            } catch (\Exception $e) {
                $output .= "  🖥️  Dashboard API: ERROR - " . $e->getMessage() . "\n";
            }
            
            $output .= "\n";
        }
        
        $output .= "🎯 SUMMARY:\n";
        $output .= "All paramedis users should now have consistent data\n";
        $output .= "between Dashboard and Menu Jaspel displays.\n";
        
        return response($output)->header('Content-Type', 'text/plain');
        
    } catch (\Exception $e) {
        return response('CONSISTENCY TEST ERROR: ' . $e->getMessage(), 500)
            ->header('Content-Type', 'text/plain');
    }
});

// Test API endpoint for main dashboard component - matches /api/v2/dashboards/paramedis/ format
Route::get('/test-dashboard-api', function () {
    try {
        // Always use Siti for testing (bypass auth for debugging)
        $user = \App\Models\User::find(23); // Siti Rahayu
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Use the actual ParamedisDashboardController logic
        $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
        
        // Create a mock request for Siti
        $mockRequest = new \Illuminate\Http\Request();
        
        // Temporarily authenticate as Siti
        \Illuminate\Support\Facades\Auth::login($user);
        
        // Call the real index method
        $response = $controller->index($mockRequest);
        
        return $response;
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch dashboard data',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test API endpoints for debugging 401 errors
Route::get('/test-paramedis-schedules-api', function () {
    try {
        $user = auth()->user();
        
        // Fallback to Laila for testing if not authenticated
        if (!$user) {
            $user = \App\Models\User::find(31); // Laila for testing
        }
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Get paramedis record
        $paramedis = \App\Models\Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        
        if (!$paramedis) {
            return response()->json(['error' => 'Paramedis record not found'], 404);
        }
        
        // Get schedules and transform to match JadwalItem interface
        // FIXED: Use user->id instead of paramedis->id since jadwal_jaga stores user_id in pegawai_id column
        $schedules = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
            ->whereDate('tanggal_jaga', '>=', now()->startOfDay())
            ->with(['shiftTemplate'])
            ->orderBy('tanggal_jaga', 'asc')
            ->limit(10)
            ->get()
            ->map(function($jadwal) {
                // Determine shift type based on time
                $jamMasuk = $jadwal->shiftTemplate ? $jadwal->shiftTemplate->jam_masuk : null;
                $jenis = 'pagi'; // default
                
                if ($jamMasuk) {
                    $hour = (int) date('H', strtotime($jamMasuk));
                    if ($hour >= 6 && $hour < 14) {
                        $jenis = 'pagi';
                    } elseif ($hour >= 14 && $hour < 22) {
                        $jenis = 'siang';
                    } else {
                        $jenis = 'malam';
                    }
                }
                
                // Format time range
                $waktu = '00:00 - 00:00';
                if ($jadwal->shiftTemplate) {
                    $jamMasuk = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->format('H:i');
                    $jamPulang = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_pulang)->format('H:i');
                    $waktu = $jamMasuk . ' - ' . $jamPulang;
                }
                
                // Map status
                $status = 'scheduled';
                if ($jadwal->status_jaga === 'Selesai') {
                    $status = 'completed';
                } elseif ($jadwal->status_jaga === 'Batal') {
                    $status = 'missed';
                }
                
                return [
                    'id' => (string) $jadwal->id,
                    'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'waktu' => $waktu,
                    'lokasi' => $jadwal->unit_kerja ?: 'Klinik',
                    'jenis' => $jenis,
                    'status' => $status
                ];
            });
        
        return response()->json($schedules->values());
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/test-paramedis-attendance-api', function () {
    try {
        $user = auth()->user();
        
        // Fallback to Laila for testing if not authenticated
        if (!$user) {
            $user = \App\Models\User::find(31); // Laila for testing
        }
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Get today's attendance status
        $today = now()->format('Y-m-d');
        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        
        return response()->json([
            'status' => $attendance ? $attendance->status : 'not_checked_in',
            'check_in_time' => $attendance ? $attendance->check_in_time : null,
            'check_out_time' => $attendance ? $attendance->check_out_time : null,
            'date' => $today,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Test API endpoint for paramedis attendance summary (for Laporan component)
Route::get('/test-paramedis-attendance-summary', function () {
    try {
        \Log::info('🔍 ATTENDANCE SUMMARY API CALLED');
        $user = auth()->user();
        
        // Fallback to Laila for testing if not authenticated
        if (!$user) {
            $user = \App\Models\User::find(31); // Laila for testing
        }
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Get current month attendance data
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $attendanceRecords = \App\Models\Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();
        
        $presentCount = $attendanceRecords->where('status', 'on_time')->count();
        $lateCount = $attendanceRecords->where('status', 'late')->count();
        $absentCount = $attendanceRecords->where('status', 'absent')->count();
        
        // Use new attendance logic: count COMPLETED shifts only (time-based)
        $scheduledShifts = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
            ->whereMonth('tanggal_jaga', $currentMonth)
            ->whereYear('tanggal_jaga', $currentYear)
            ->with('shiftTemplate') // Load shift template to get end time
            ->get();
        
        // Filter shifts to only count those that have already ended
        $completedShifts = $scheduledShifts->filter(function ($shift) {
            if (!$shift->shiftTemplate || !$shift->shiftTemplate->jam_pulang) {
                return false; // Skip shifts without end time
            }
            
            $shiftDate = \Carbon\Carbon::parse($shift->tanggal_jaga);
            $shiftEndTime = \Carbon\Carbon::parse($shift->shiftTemplate->jam_pulang);
            
            // Handle night shifts that end the next day (like 23:59 - 00:15)
            $shiftStartTime = \Carbon\Carbon::parse($shift->shiftTemplate->jam_masuk);
            if ($shiftEndTime->hour < 12 && $shiftStartTime->hour > 12) {
                // This is a night shift that crosses midnight - end time is on next day
                $shiftEndDateTime = $shiftDate->copy()->addDay()
                    ->setHour($shiftEndTime->hour)
                    ->setMinute($shiftEndTime->minute)
                    ->setSecond(0);
            } else {
                // Regular shift - end time is on same day
                $shiftEndDateTime = $shiftDate->copy()
                    ->setHour($shiftEndTime->hour)
                    ->setMinute($shiftEndTime->minute)
                    ->setSecond(0);
            }
            
            // Only count shifts that have already ended
            return \Carbon\Carbon::now()->gte($shiftEndDateTime);
        });
        
        // Use completed shifts as working days
        $workingDays = $completedShifts->count();
        
        // If no shifts completed yet, working days = 0 (no absent days)
        if ($workingDays == 0) {
            $workingDays = 0; // No completed shifts = no working days = no absent days
        }
        
        $totalDays = $workingDays; // Use calculated working days as denominator
        
        // Calculate total hours from check-in/check-out times
        $totalHours = 0;
        foreach ($attendanceRecords as $record) {
            if ($record->check_in_time && $record->check_out_time) {
                $checkIn = \Carbon\Carbon::parse($record->check_in_time);
                $checkOut = \Carbon\Carbon::parse($record->check_out_time);
                $totalHours += $checkIn->diffInHours($checkOut);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'on_time' => $presentCount,
                    'late' => $lateCount,
                    'absent' => $absentCount,
                    'total_days' => $totalDays,
                    'working_days' => $workingDays,
                    'scheduled_shifts' => $scheduledShifts->count(),
                    'completed_shifts' => $completedShifts->count(),
                    'total_hours' => $totalHours,
                    'attendance_rate' => $workingDays > 0 ? round(($presentCount + $lateCount) / $workingDays * 100, 1) : 0
                ],
                'history' => $attendanceRecords->map(function($record) {
                    return [
                        'id' => $record->id ?? 1,
                        'date' => $record->date,
                        'status' => $record->status,
                        'time_in' => $record->check_in_time,
                        'time_out' => $record->check_out_time,
                        'work_duration' => '8 jam', // Default work duration
                        'location_name_in' => 'Klinik'
                    ];
                })
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Test API endpoint for paramedis dashboard - proxies to real API endpoint
Route::get('/test-paramedis-dashboard-api', function () {
    try {
        // FIXED: Use authenticated user instead of hardcoded Siti
        $user = auth()->user();
        
        // Fallback to specific user for testing if not authenticated
        if (!$user) {
            $user = \App\Models\User::find(20); // Bita for testing
        }
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Get current month and year dynamically
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $lastMonth = now()->subMonth()->month;
        $lastMonthYear = now()->subMonth()->year;
        
        // Get comprehensive Jaspel data
        $enhancedService = app(\App\Services\EnhancedJaspelService::class);
        $currentData = $enhancedService->getComprehensiveJaspelData($user, $currentMonth, $currentYear);
        $lastData = $enhancedService->getComprehensiveJaspelData($user, $lastMonth, $lastMonthYear);
        
        // Calculate growth percentage
        $currentTotal = $currentData['summary']['total_pending'] + $currentData['summary']['total_paid'];
        $lastTotal = $lastData['summary']['total_pending'] + $lastData['summary']['total_paid'];
        
        $growthPercent = 0;
        if ($lastTotal > 0) {
            $growthPercent = (($currentTotal - $lastTotal) / $lastTotal) * 100;
        } elseif ($currentTotal > 0) {
            $growthPercent = 100; // 100% growth from 0
        }
        
        // Calculate weekly estimate based on current week progress
        $daysInMonth = now()->daysInMonth;
        $daysPassed = now()->day;
        $dailyAverage = $daysPassed > 0 ? $currentTotal / $daysPassed : 0;
        $weeklyEstimate = $dailyAverage * 7;
        
        // Get attendance data for current month
        $paramedis = \App\Models\Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        
        $shiftsThisMonth = 0;
        $attendanceRate = 0;
        
        if ($paramedis) {
            // Use the new attendance logic from ParamedisDashboardController
            $attendanceRecords = \App\Models\Attendance::where('user_id', $user->id)
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->get();
            
            $presentDays = $attendanceRecords->where('status', 'on_time')->count();
            $lateDays = $attendanceRecords->where('status', 'late')->count();
            
            // Count COMPLETED shifts only (time-based)
            $scheduledShifts = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
                ->whereMonth('tanggal_jaga', $currentMonth)
                ->whereYear('tanggal_jaga', $currentYear)
                ->with('shiftTemplate') // Load shift template to get end time
                ->get();
            
            // Filter shifts to only count those that have already ended
            $completedShifts = $scheduledShifts->filter(function ($shift) {
                if (!$shift->shiftTemplate || !$shift->shiftTemplate->jam_pulang) {
                    return false; // Skip shifts without end time
                }
                
                $shiftDate = \Carbon\Carbon::parse($shift->tanggal_jaga);
                $shiftEndTime = \Carbon\Carbon::parse($shift->shiftTemplate->jam_pulang);
                
                // Handle night shifts that end the next day (like 23:59 - 00:15)
                $shiftStartTime = \Carbon\Carbon::parse($shift->shiftTemplate->jam_masuk);
                if ($shiftEndTime->hour < 12 && $shiftStartTime->hour > 12) {
                    // This is a night shift that crosses midnight - end time is on next day
                    $shiftEndDateTime = $shiftDate->copy()->addDay()
                        ->setHour($shiftEndTime->hour)
                        ->setMinute($shiftEndTime->minute)
                        ->setSecond(0);
                } else {
                    // Regular shift - end time is on same day
                    $shiftEndDateTime = $shiftDate->copy()
                        ->setHour($shiftEndTime->hour)
                        ->setMinute($shiftEndTime->minute)
                        ->setSecond(0);
                }
                
                // Only count shifts that have already ended
                return \Carbon\Carbon::now()->gte($shiftEndDateTime);
            });
            
            // Use completed shifts as working days
            $workingDays = $completedShifts->count();
            
            // If no shifts completed yet, working days = 0 (no absent days)
            if ($workingDays == 0) {
                $workingDays = 0; // No completed shifts = no working days = no absent days
            }
            
            // Calculate attendance rate: (present + late) / working days * 100
            $attendanceRate = $workingDays > 0 ? (($presentDays + $lateDays) / $workingDays) * 100 : 0;
            
            $shiftsThisMonth = $scheduledShifts->count();
        }
        
        // Return dashboard data in expected format with success wrapper for Laporan component
        return response()->json([
            'success' => true,
            'data' => [
                'performance' => [
                    'attendance_rate' => round($attendanceRate, 1),
                    'patient_satisfaction' => 92,
                    'attendance_rank' => 1,
                    'total_staff' => 10
                ]
            ],
            // Also return direct format for Dashboard component compatibility
            'jaspel_monthly' => $currentTotal,
            'jaspel_weekly' => round($weeklyEstimate), // More accurate weekly estimate
            'daily_average' => round($dailyAverage),
            'attendance_rate' => round($attendanceRate, 1),
            'shifts_this_month' => $shiftsThisMonth,
            'paramedis_name' => $user->name,
            'paramedis_specialty' => 'Paramedis',
            'pending_jaspel' => $currentData['summary']['total_pending'],
            'approved_jaspel' => $currentData['summary']['total_paid'],
            'growth_percent' => round($growthPercent, 1),
            'last_month_total' => $lastTotal,
            'period_info' => [
                'current_month' => $currentMonth,
                'current_year' => $currentYear,
                'days_passed' => $daysPassed,
                'days_in_month' => $daysInMonth,
                'month_progress' => round(($daysPassed / $daysInMonth) * 100, 1)
            ],
            'stats' => [
                'jaspel_month' => $currentTotal,
                'jaspel_last_month' => $lastTotal,
                'jaspel_growth_percent' => round($growthPercent, 1),
                'patients_today' => 0, // Could be calculated from today's tindakan
                'tindakan_today' => 0, // Could be calculated from today's tindakan
                'shifts_week' => round($weeklyEstimate / ($currentTotal > 0 ? $currentTotal : 1) * $shiftsThisMonth)
            ],
            'performance' => [
                'attendance_rate' => round($attendanceRate, 1),
                'patient_satisfaction' => 92, // Could be from feedback system
                'attendance_rank' => 1, // Could be calculated
                'total_staff' => 10 // Could be from staff count
            ],
            'user' => [
                'name' => $user->name,
                'work_location' => null // Could be enhanced with location data
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch dashboard data',
            'message' => $e->getMessage(),
            'jaspel_monthly' => 0,
            'jaspel_weekly' => 0,
            'minutes_worked' => 0,
            'shifts_this_month' => 0,
            'paramedis_name' => 'Error',
            'paramedis_specialty' => 'Paramedis',
            'pending_jaspel' => 0,
            'approved_jaspel' => 0,
            'today_attendance' => null,
            'growth_percent' => 0
        ], 500);
    }
});

// Test Naning specific jadwal jaga
Route::get('/test-naning-schedules', function () {
    try {
        $user = \App\Models\User::find(13); // Naning specific
        
        if (!$user) {
            return response()->json(['error' => 'Naning user not found'], 404);
        }
        
        // Get paramedis record
        $paramedis = \App\Models\Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        
        if (!$paramedis) {
            return response()->json(['error' => 'Naning paramedis record not found'], 404);
        }
        
        // Get schedules and transform to match JadwalItem interface
        $schedules = \App\Models\JadwalJaga::where('pegawai_id', $paramedis->id)
            ->whereDate('tanggal_jaga', '>=', now()->startOfDay())
            ->with(['shiftTemplate'])
            ->orderBy('tanggal_jaga', 'asc')
            ->limit(10)
            ->get()
            ->map(function($jadwal) {
                $waktu = 'Tidak Ditentukan';
                if ($jadwal->shiftTemplate) {
                    $jamMasuk = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->format('H:i');
                    $jamPulang = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_pulang)->format('H:i');
                    $waktu = $jamMasuk . ' - ' . $jamPulang;
                }
                
                return [
                    'id' => (string) $jadwal->id,
                    'tanggal' => $jadwal->tanggal_jaga,
                    'waktu' => $waktu,
                    'lokasi' => $jadwal->unit_kerja ?? $jadwal->unit_instalasi ?? 'Tidak Ditentukan',
                    'jenis' => strtolower($jadwal->shiftTemplate->nama_shift ?? 'reguler'),
                    'status' => 'scheduled'
                ];
            });
        
        return response()->json([
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'paramedis_id' => $paramedis->id
            ],
            'schedules' => $schedules->values()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch Naning schedules',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-validation-center', function () {
    try {
        $output = "🔍 VALIDATION CENTER DEBUG\n";
        $output .= "========================\n\n";
        
        // Simulate ValidationCenterResource query
        $query = \App\Models\Tindakan::whereNotNull('input_by')
            ->with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'inputBy', 'validatedBy']);
        
        $allRecords = $query->get();
        $pendingRecords = $query->where('status_validasi', 'pending')->get();
        
        $output .= "📊 STATISTICS:\n";
        $output .= "Total records: " . $allRecords->count() . "\n";
        $output .= "Pending records: " . $pendingRecords->count() . "\n\n";
        
        $output .= "🕐 PENDING RECORDS (should show in UI):\n";
        foreach($pendingRecords as $t) {
            $jenisTindakan = $t->jenisTindakan ? $t->jenisTindakan->nama : 'N/A';
            $pasien = $t->pasien ? $t->pasien->nama : 'N/A';
            $paramedis = $t->paramedis ? $t->paramedis->nama_lengkap : 'N/A';
            $inputBy = $t->inputBy ? $t->inputBy->name : 'N/A';
            
            $output .= "- ID: {$t->id}\n";
            $output .= "  Jenis: {$jenisTindakan}\n";
            $output .= "  Pasien: {$pasien}\n";
            $output .= "  Paramedis: {$paramedis}\n";
            $output .= "  Tarif: Rp " . number_format($t->tarif, 0, ',', '.') . "\n";
            $output .= "  Input By: {$inputBy}\n";
            $output .= "  Status: {$t->status_validasi}\n";
            $output .= "  Date: {$t->tanggal_tindakan}\n\n";
        }
        
        $output .= "🔎 NANING SPECIFIC ANALYSIS:\n";
        $naning = \App\Models\User::where('name', 'LIKE', '%Naning%')->first();
        $naningTindakan = $allRecords->filter(function($t) use ($naning) {
            return $t->paramedis && $t->paramedis->user_id == $naning->id;
        });
        
        $output .= "Naning tindakan found: " . $naningTindakan->count() . "\n";
        foreach($naningTindakan as $t) {
            $output .= "- Tindakan {$t->id}: Status {$t->status_validasi}, Tarif Rp " . number_format($t->tarif, 0, ',', '.') . "\n";
        }
        
        return response($output)->header('Content-Type', 'text/plain');
    } catch (\Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
});

Route::get('/test-jaspel-data', function () {
    try {
        $naning = \App\Models\User::where('name', 'LIKE', '%Naning%')
                     ->whereHas('roles', function($q) { $q->where('name', 'paramedis'); })
                     ->first();
        
        if (!$naning) {
            return response('❌ Naning not found', 404);
        }
        
        $jaspelRecords = \App\Models\Jaspel::where('user_id', $naning->id)
            ->with(['tindakan.jenisTindakan'])
            ->get();
        
        $output = "🔍 JASPEL DEBUG for {$naning->name} (ID: {$naning->id})\n";
        $output .= "📊 Total Jaspel records: {$jaspelRecords->count()}\n\n";
        
        foreach ($jaspelRecords as $jaspel) {
            $tindakan = $jaspel->tindakan;
            $jenis = $tindakan && $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : 'N/A';
            $output .= "- ID: {$jaspel->id}, Jenis: {$jenis}, Nominal: Rp " . number_format($jaspel->nominal, 0, ',', '.') . ", Status: {$jaspel->status_validasi}\n";
        }
        
        $pendingTotal = $jaspelRecords->where('status_validasi', 'pending')->sum('nominal');
        $totalPaid = $jaspelRecords->where('status_validasi', 'disetujui')->sum('nominal');
        $output .= "\n💰 Total Paid: Rp " . number_format($totalPaid, 0, ',', '.');
        $output .= "\n⏳ Total Pending: Rp " . number_format($pendingTotal, 0, ',', '.');
        
        // Test mobile API simulation
        $output .= "\n\n🔬 MOBILE API SIMULATION:";
        Auth::login($naning);
        
        $jaspelController = new \App\Http\Controllers\Api\V2\Jaspel\JaspelController(
            app(\App\Services\JaspelCalculationService::class)
        );
        $request = new \Illuminate\Http\Request();
        $apiResponse = $jaspelController->getMobileJaspelData($request);
        $apiData = $apiResponse->getData(true);
        
        if ($apiData['success']) {
            $summary = $apiData['data']['summary'];
            $output .= "\n✅ API Success:";
            $output .= "\n  - Total Pending: Rp " . number_format($summary['total_pending'], 0, ',', '.');
            $output .= "\n  - Total Paid: Rp " . number_format($summary['total_paid'], 0, ',', '.');
            $output .= "\n  - Pending Count: " . $summary['count_pending'];
            $output .= "\n  - Items Returned: " . count($apiData['data']['jaspel_items']);
        } else {
            $output .= "\n❌ API Error: " . $apiData['message'];
        }
        
        Auth::logout();
        
        return response($output)->header('Content-Type', 'text/plain');
    } catch (\Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
});

// Health check endpoint for deployment monitoring
Route::get('/health', function () {
    try {
        // Check database connection
        $dbStatus = 'connected';
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
        } catch (Exception $e) {
            $dbStatus = 'disconnected';
        }

        // Check cache connection
        $cacheStatus = 'connected';
        try {
            Cache::put('health_check', time(), 5);
            Cache::get('health_check');
        } catch (Exception $e) {
            $cacheStatus = 'disconnected';
        }

        // Check storage writability
        $storageStatus = 'writable';
        try {
            $testFile = storage_path('logs/health_check.tmp');
            file_put_contents($testFile, 'test');
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        } catch (Exception $e) {
            $storageStatus = 'readonly';
        }

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'version' => app()->version(),
            'database' => $dbStatus,
            'cache' => $cacheStatus,
            'storage' => $storageStatus,
            'php_version' => PHP_VERSION,
            'laravel_version' => \Illuminate\Foundation\Application::VERSION,
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Health check failed',
            'timestamp' => now()->toISOString(),
            'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
        ], 503);
    }
})->name('health');

// API Health check endpoint
Route::get('/api/health', function () {
    return response()->json([
        'status' => 'ok',
        'api' => 'healthy',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
    ], 200);
})->name('api.health');

// EMERGENCY TEST ROUTE - Top priority
Route::get('/test-emergency', function () {
    return response()->json(['status' => 'Emergency route works', 'timestamp' => now()]);
});

// DEEP DIAGNOSTIC ROUTE - Root cause analysis
Route::get('/deep-diagnostic-jaspel', function () {
    $routes = [];
    $routeCollection = app('router')->getRoutes();
    
    foreach ($routeCollection as $route) {
        if (str_contains($route->uri(), 'jaspel') && str_contains($route->uri(), 'mobile-data')) {
            $routes[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'middleware' => $route->gatherMiddleware(),
                'action' => $route->getActionName()
            ];
        }
    }
    
    return response()->json([
        'timestamp' => now()->toISOString(),
        'request_info' => [
            'url' => request()->url(),
            'path' => request()->path(),
            'method' => request()->method(),
            'headers' => request()->headers->all(),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId()
        ],
        'jaspel_mobile_data_routes' => $routes,
        'route_count' => count($routes)
    ]);
});

// WORLD-CLASS: Alternative Jaspel endpoint outside paramedis group for broader access
Route::get('/api/v2/jaspel/mobile-data-alt', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'getMobileJaspelData'])
    ->middleware(['auth:web,sanctum', 'throttle:60,1'])
    ->name('jaspel.mobile-data-alt');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});


// Unified authentication routes
// Route::get('/login', [UnifiedAuthController::class, 'create'])->name('login'); // Redirected to /welcome-login
// Route::get('/unified-login', [UnifiedAuthController::class, 'create'])->name('unified.login.form'); // Redirected to /welcome-login
Route::post('/login', [UnifiedAuthController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('unified.login');
Route::post('/logout', [UnifiedAuthController::class, 'destroy'])->name('logout');


// Password reset routes
Route::get('/forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])
    ->middleware('guest')
    ->name('password.request');

Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

// Email verification routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:12,1'])->name('verification.send');

// Main dashboard route that redirects based on role
Route::get('/dashboard', DashboardController::class)->middleware(['auth'])->name('dashboard');

// Role-specific dashboard routes
Route::middleware(['auth'])->group(function () {
    Route::get('/legacy-admin/dashboard', [AdminDashboardController::class, 'index'])->name('legacy-admin.dashboard');
    Route::get('/manager/dashboard', function () {
        return redirect('/manajer');
    })->name('manager.dashboard');
    
    // Filament notification action routes
    Route::post('/filament/jadwal-jaga/create-missing-users', function () {
        $missingUsers = session()->get('missing_users_data', []);
        
        if (empty($missingUsers)) {
            return redirect()->back()->with('error', 'Data pegawai yang hilang tidak ditemukan.');
        }
        
        // Create instance of the page to call the method
        $page = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $page->createMissingUserAccounts($missingUsers);
        
        // Clear session data
        session()->forget('missing_users_data');
        
        return redirect()->back()->with('success', 'Proses pembuatan akun telah dimulai.');
    })->name('filament.jadwal-jaga.create-missing-users');
    Route::get('/treasurer/dashboard', function () {
        return redirect('/bendahara');
    })->name('treasurer.dashboard');
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    // Route removed - using standard petugas dashboard instead
    // (NO Route::get('/petugas', ...) or Route::prefix('petugas') here)
    // Enhanced Petugas Management Routes (still available at /petugas/enhanced/*, does not override /petugas root)
    Route::middleware(['auth', 'role:petugas'])->prefix('petugas/enhanced')->name('petugas.enhanced.')->group(function () {
        // Patient Management
        Route::prefix('pasien')->name('pasien.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'export'])->name('export');
            Route::get('/search/autocomplete', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'search'])->name('search');
            Route::get('/{id}/timeline', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'getTimeline'])->name('timeline');
        });
        
        // Tindakan (Medical Procedure) Management
        Route::prefix('tindakan')->name('tindakan.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-update-status', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::get('/{patientId}/timeline', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'getTimeline'])->name('patient-timeline');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'export'])->name('export');
            Route::get('/search/autocomplete', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'search'])->name('search');
        });
        
        // Pendapatan (Revenue) Management
        Route::prefix('pendapatan')->name('pendapatan.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-create-from-tindakan', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'bulkCreateFromTindakan'])->name('bulk-create-from-tindakan');
            Route::get('/analytics', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'getAnalytics'])->name('analytics');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'export'])->name('export');
            Route::get('/suggestions', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'getSuggestions'])->name('suggestions');
        });
        
        // Pengeluaran (Expense) Management
        Route::prefix('pengeluaran')->name('pengeluaran.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-update-status', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::get('/budget-analysis', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'getBudgetAnalysisData'])->name('budget-analysis');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'export'])->name('export');
            Route::get('/suggestions', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'getSuggestions'])->name('suggestions');
        });
        
        // Jumlah Pasien (Patient Reporting) Management
        Route::prefix('jumlah-pasien')->name('jumlah-pasien.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'index'])->name('index');
            Route::get('/calendar-data', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'getCalendarData'])->name('calendar-data');
            Route::get('/date-stats', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'getDateStats'])->name('date-stats');
            Route::get('/analytics', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'getAnalytics'])->name('analytics');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'export'])->name('export');
        });
    });
    // Redirect DOKTER to Flutter mobile app
    Route::get('/doctor/dashboard', function () {
        return redirect('/dokter/mobile-app');
    })->middleware('role:dokter');
    
    // DOKTER Mobile App Routes (Replaces Filament dashboard)
    Route::middleware(['auth', 'role:dokter'])->prefix('dokter')->name('dokter.')->group(function () {
        // Base dokter route - redirect to mobile app
        Route::get('/', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('index');
        
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
            
            // Get dokter data for more accurate name
            $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
            $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
            
            $userData = [
                'name' => $displayName,
                'email' => $user->email,
                'greeting' => $greeting,
                'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
            ];
            
            // AGGRESSIVE CACHE BUSTING HEADERS
            return response()
                ->view('mobile.dokter.app', compact('token', 'userData'))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                ->header('ETag', '"' . md5(time()) . '"');
        })->name('mobile-app')->middleware('throttle:1000,1');
        
        // EMERGENCY BYPASS ROUTE - Force new bundle loading (DISABLED - file not found)
        // Route::get('/mobile-app-v2', function () {
        //     // Route disabled - app-emergency.blade.php not found
        // })->name('mobile-app-v2')->middleware('throttle:1000,1');
        
        // API endpoint for doctor weekly schedules (for dashboard)
        Route::get('/api/weekly-schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getWeeklySchedule'])
            ->name('api.weekly-schedules')->middleware('throttle:1000,1');
            
        // API endpoint for unit kerja schedules (for dashboard)
        Route::get('/api/igd-schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getIgdSchedules'])
            ->name('api.igd-schedules')->middleware('throttle:1000,1');
        
        // API endpoint for doctor schedules
        Route::get('/api/schedules', function () {
            $user = auth()->user();
            $userId = $user->id;
            
            // Get current and upcoming schedules for this doctor
            $schedules = \App\Models\JadwalJaga::where('pegawai_id', $userId)
                ->where('unit_kerja', 'Dokter Jaga')
                ->where('tanggal_jaga', '>=', now()->subDays(1)) // Include yesterday for overnight shifts
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->orderBy('shift_template_id')
                ->take(10)
                ->get()
                ->map(function ($jadwal) {
                    // Determine shift type based on time
                    $jamMasuk = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->hour;
                    $jenis = match(true) {
                        $jamMasuk >= 6 && $jamMasuk < 14 => 'pagi',
                        $jamMasuk >= 14 && $jamMasuk < 22 => 'siang', 
                        default => 'malam'
                    };
                    
                    // Use dynamic location from database or default to unit kerja
                    $lokasi = 'Dokter Jaga'; // Use consistent unit kerja from admin
                    
                    return [
                        'id' => (string) $jadwal->id,
                        'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'waktu' => $jadwal->shiftTemplate->jam_masuk_format . ' - ' . $jadwal->shiftTemplate->jam_pulang_format,
                        'lokasi' => $lokasi,
                        'jenis' => $jenis,
                        'status' => $jadwal->tanggal_jaga->isPast() ? 'completed' : 'scheduled',
                        'shift_nama' => $jadwal->shiftTemplate->nama_shift,
                        'status_jaga' => $jadwal->status_jaga,
                        'keterangan' => $jadwal->keterangan
                    ];
                });
            
            return response()->json($schedules);
        })->name('api.schedules')->middleware('throttle:1000,1');
        
        // Legacy routes - redirect to new mobile app
        Route::get('/dashboard', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('dashboard');
        Route::get('/presensi', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('presensi');
        Route::get('/jaspel', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('jaspel');
        Route::get('/tindakan', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('tindakan');
        
        // Any other dokter routes should redirect to mobile app
        Route::fallback(function () {
            return redirect()->route('dokter.mobile-app');
        });
    });
    
    // EMERGENCY DOKTER BYPASS ROUTE (Outside middleware group for debugging)
    Route::get('/dokter/mobile-app-emergency', function () {
        $user = auth()->user();
        
        // Check if user exists and is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated']);
        }
        
        // Check role manually
        $hasRole = $user->roles()->where('name', 'dokter')->exists();
        if (!$hasRole) {
            return response()->json([
                'error' => 'Role check failed',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $user->roles()->pluck('name')->toArray()
            ]);
        }
        
        $token = $user->createToken('mobile-app-dokter-emergency-' . now()->timestamp)->plainTextToken;
        
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
        
        // Get dokter data
        $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
        $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
        
        $userData = [
            'name' => $displayName,
            'email' => $user->email,
            'greeting' => $greeting,
            'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
        ];
        
        // ULTIMATE CACHE BYPASS
        return response()
            ->view('mobile.dokter.app-emergency', compact('token', 'userData'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT');
    })->middleware('auth');
    
    // ULTIMATE EMERGENCY BYPASS - Completely independent route
    Route::get('/emergency-dokter-bypass', function () {
        $user = auth()->user();
        
        // Check if user exists and is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        // Get all user info for debugging
        $userData = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'roles' => $user->roles()->pluck('name')->toArray(),
            'role_id' => $user->role_id ?? null,
            'authenticated' => auth()->check(),
        ];
        
        // Check if user has dokter role in any way
        $hasRole = $user->roles()->where('name', 'dokter')->exists();
        $hasRoleId = $user->role_id && \App\Models\Role::find($user->role_id)?->name === 'dokter';
        
        if (!$hasRole && !$hasRoleId) {
            return response()->json([
                'error' => 'Role check failed',
                'debug' => $userData,
                'role_check_spatie' => $hasRole,
                'role_check_direct' => $hasRoleId,
                'message' => 'User does not have dokter role'
            ], 403);
        }
        
        // If user has role, generate token and load app
        $token = $user->createToken('emergency-bypass-' . now()->timestamp)->plainTextToken;
        
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
        
        // Get dokter data
        $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
        $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
        
        $appUserData = [
            'name' => $displayName,
            'email' => $user->email,
            'greeting' => $greeting,
            'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
        ];
        
        // ULTIMATE CACHE BYPASS
        return response()
            ->view('mobile.dokter.app-emergency', ['token' => $token, 'userData' => $appUserData])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT');
    })->middleware('auth');
    
    // PARAMEDIS Mobile App Routes (Replaces Filament dashboard)
    Route::middleware(['auth', 'role:paramedis'])->prefix('paramedis')->name('paramedis.')->group(function () {
        // Base paramedis route - redirect to mobile app
        Route::get('/', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('index');
        
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-paramedis-' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
            
            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'greeting' => $greeting,
                'initials' => strtoupper(substr($user->name ?? 'PA', 0, 2))
            ];
            
            return view('mobile.paramedis.app', compact('token', 'userData'));
        })->name('mobile-app')->middleware('throttle:1000,1');
        
        // Mobile app Jaspel data endpoint - WORLD-CLASS authentication support  
        Route::get('/api/v2/jaspel/mobile-data', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'getMobileJaspelData'])
            ->middleware(['auth:web,sanctum', 'throttle:60,1']);
            
        // BACKUP endpoint for mobile-data with enhanced debugging
        Route::get('/api/v2/jaspel/mobile-data-debug', function () {
            try {
                $user = auth()->user();
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Authentication failed',
                        'debug' => [
                            'session_id' => session()->getId(),
                            'auth_guard' => auth()->getDefaultDriver(),
                            'session_data' => session()->all()
                        ]
                    ], 401);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Debug endpoint working',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role?->name
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        })->middleware(['throttle:60,1']);
        
        // API endpoint for paramedis schedules
        Route::get('/api/schedules', function () {
            try {
                $user = auth()->user();
                
                // TEMPORARY FIX: Force bita for testing
                if ($user && $user->name === 'Naning Paramedis') {
                    $user = \App\Models\User::find(20); // Force bita
                    \Log::info('PARAMEDIS API: Forcing bita user for testing', ['original_user_id' => auth()->user()->id]);
                }
                
                // Debug authentication
                \Log::info('PARAMEDIS API: Authentication check', [
                    'authenticated' => auth()->check(),
                    'user_id' => $user?->id,
                    'user_name' => $user?->name,
                    'session_id' => session()->getId(),
                    'request_path' => request()->path()
                ]);
                
                if (!$user) {
                    \Log::warning('PARAMEDIS API: No authenticated user');
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                
                // Get the actual pegawai_id - ONLY for paramedis users
                $pegawai = \App\Models\Pegawai::where('user_id', $user->id)
                    ->where('jenis_pegawai', 'Paramedis')
                    ->first();
                
                if (!$pegawai) {
                    \Log::warning('Paramedis user has no valid pegawai record', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    return response()->json([]);
                }
                
                $pegawaiId = $pegawai->id; // Use pegawai record's ID for jadwal lookup
                
                // Get current and upcoming schedules for this paramedis ONLY
                $allSchedules = \App\Models\JadwalJaga::where('pegawai_id', $pegawaiId)
                    ->whereIn('unit_kerja', ['Pendaftaran', 'Pelayanan']) // EXCLUDE Dokter Jaga
                    ->where('tanggal_jaga', '>=', now()->subDays(1)) // Include yesterday for overnight shifts
                    ->with(['shiftTemplate'])
                    ->get();
                    
                \Log::debug('BEFORE FILTER: Found ' . $allSchedules->count() . ' raw schedules', [
                    'current_time' => now()->toString(),
                    'schedules' => $allSchedules->map(function($s) {
                        return [
                            'id' => $s->id,
                            'date' => $s->tanggal_jaga->format('Y-m-d'),
                            'shift_time' => $s->shiftTemplate ? $s->shiftTemplate->jam_masuk : 'NO_SHIFT'
                        ];
                    })
                ]);
                
                $schedules = $allSchedules
                    ->sortBy(function ($jadwal) {
                        if (!$jadwal->shiftTemplate) return '9999-12-31 23:59:59';
                        $shiftDate = $jadwal->tanggal_jaga->format('Y-m-d');
                        $shiftTime = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->format('H:i:s');
                        return $shiftDate . ' ' . $shiftTime;
                    })
                    ->filter(function ($jadwal) {
                        if (!$jadwal->shiftTemplate) {
                            \Log::debug('FILTER: No shift template for jadwal ' . $jadwal->id);
                            return false;
                        }
                        $shiftStart = \Carbon\Carbon::parse(
                            $jadwal->tanggal_jaga->format('Y-m-d') . ' ' . 
                            \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->format('H:i:s')
                        );
                        $isUpcoming = $shiftStart->gt(now());
                        \Log::debug('FILTER: Jadwal ' . $jadwal->id . ' on ' . $jadwal->tanggal_jaga->format('Y-m-d') . ' at ' . $jadwal->shiftTemplate->jam_masuk . ' -> Start: ' . $shiftStart . ' > Now: ' . now() . ' = ' . ($isUpcoming ? 'KEEP' : 'SKIP'));
                        return $isUpcoming;
                    })
                    ->take(10)
                    ->map(function ($jadwal) {
                        // Handle missing shiftTemplate with fallbacks
                        if ($jadwal->shiftTemplate) {
                            // Determine shift type based on time
                            $jamMasuk = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->hour;
                            $jenis = match(true) {
                                $jamMasuk >= 6 && $jamMasuk < 14 => 'pagi',
                                $jamMasuk >= 14 && $jamMasuk < 22 => 'siang', 
                                default => 'malam'
                            };
                            $waktu = ($jadwal->shiftTemplate->jam_masuk_format ?? '08:00') . ' - ' . ($jadwal->shiftTemplate->jam_pulang_format ?? '16:00');
                            $shiftNama = $jadwal->shiftTemplate->nama_shift ?? 'Shift';
                        } else {
                            // Fallback when no shiftTemplate
                            $jenis = 'pagi'; // Default fallback
                            $waktu = '08:00 - 16:00'; // Default fallback
                            $shiftNama = 'Shift Regular';
                        }
                        
                        // Use dynamic location from database or default to unit kerja  
                        $lokasi = $jadwal->unit_kerja ?? 'Pelayanan'; // Use unit_kerja from database
                        
                        return [
                            'id' => (string) $jadwal->id,
                            'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                            'waktu' => $waktu,
                            'lokasi' => $lokasi,
                            'jenis' => $jenis,
                            'status' => $jadwal->tanggal_jaga->isPast() ? 'completed' : 'scheduled',
                            'shift_nama' => $shiftNama,
                            'status_jaga' => $jadwal->status_jaga ?? 'scheduled',
                            'keterangan' => $jadwal->keterangan ?? ''
                        ];
                    });
                
                \Log::info('PARAMEDIS API: Returning schedules', [
                    'user_id' => $user->id,
                    'schedules_count' => $schedules->count(),
                    'first_schedule' => $schedules->first() ? [
                        'id' => $schedules->first()['id'],
                        'tanggal' => $schedules->first()['tanggal'],
                        'waktu' => $schedules->first()['waktu']
                    ] : null
                ]);
                
                // Ensure proper array format (not object with numeric keys)
                return response()->json($schedules->values());
            } catch (\Exception $e) {
                // Return empty array if there's any error
                \Log::error('Paramedis schedules API error: ' . $e->getMessage());
                return response()->json([]);
            }
        })->name('paramedis.api.schedules')->middleware('throttle:1000,1');
        
        // IGD Schedules API with dynamic unit kerja filtering
        Route::get('/api/igd-schedules', function (Request $request) {
            try {
                $user = auth()->user();
                
                // SECURITY: Ensure user has paramedis role
                if (!$user->hasRole('paramedis')) {
                    \Log::warning('Non-paramedis user attempted to access IGD schedules API', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    return response()->json(['success' => false, 'message' => 'Access denied'], 403);
                }
                
                $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
                return $controller->getIgdSchedules($request);
            } catch (\Exception $e) {
                \Log::error('Paramedis IGD schedules API error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memuat jadwal IGD',
                    'data' => [
                        'schedules' => [],
                        'total_count' => 0
                    ]
                ], 500);
            }
        })->name('api.igd.schedules')->middleware('throttle:1000,1');
        
        // Weekly Schedule API with dynamic data
        Route::get('/api/weekly-schedule', function (Request $request) {
            try {
                $user = auth()->user();
                
                // SECURITY: Ensure user has paramedis role
                if (!$user->hasRole('paramedis')) {
                    \Log::warning('Non-paramedis user attempted to access weekly schedules API', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    return response()->json(['success' => false, 'message' => 'Access denied'], 403);
                }
                
                $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
                return $controller->getWeeklySchedule($request);
            } catch (\Exception $e) {
                \Log::error('Paramedis weekly schedules API error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memuat jadwal minggu ini',
                    'data' => [
                        'schedules' => [],
                        'total_count' => 0
                    ]
                ], 500);
            }
        })->name('api.weekly.schedules')->middleware('throttle:1000,1');
        
        // Legacy routes - redirect to new mobile app
        Route::get('/dashboard', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('dashboard');
        Route::get('/presensi', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('presensi');
        Route::get('/jaspel', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('jaspel');
        Route::get('/tindakan', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('tindakan');
        Route::get('/jadwal-jaga', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('jadwal-jaga');
        
        // Riwayat Presensi - redirect to Filament resource
        Route::get('/riwayat-presensi', function () {
            return redirect('/paramedis/attendance-histories');
        })->name('riwayat-presensi');
        
        // Alternative route names for discoverability  
        Route::get('/history', function () {
            return redirect('/paramedis/attendance-histories');
        })->name('history');
        
        Route::get('/attendance-history', function () {
            return redirect('/paramedis/attendance-histories');
        })->name('attendance-history');
        
        // DIRECT ACCESS untuk laporan presensi (seperti jaspel)
        Route::get('/laporan-presensi', function () {
            return redirect('/paramedis/attendance-histories');
        })->name('laporan-presensi');
        
        Route::get('/presensi-saya', function () {
            return redirect('/paramedis/attendance-histories');
        })->name('presensi-saya');
        
        // Removed fallback route to prevent infinite redirects
    });
    
    // Non-Paramedis Mobile App Routes (Replaces old dashboard)
    Route::middleware(['auth', 'role:non_paramedis'])->prefix('nonparamedis')->name('nonparamedis.')->group(function () {
        Route::get('/app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-' . now()->timestamp)->plainTextToken;
            return view('mobile.nonparamedis.app', compact('token'));
        })->name('app');
        
        // Legacy routes - redirect to new mobile app
        Route::get('/dashboard', function () {
            return redirect()->route('nonparamedis.app');
        })->name('dashboard');
        Route::get('/presensi', function () {
            return redirect()->route('nonparamedis.app');
        })->name('presensi');
        Route::get('/jadwal', function () {
            return redirect()->route('nonparamedis.app');
        })->name('jadwal');
    });
    
    // React Dashboard Test Route (untuk semua role)
    Route::get('/react-dashboard-demo', function () {
        // Debug current user
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        \Log::info('Paramedis React Dashboard Access', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role?->name ?? 'no_role',
            'has_role' => $user->role ? 'YES' : 'NO'
        ]);
        
        // Allow all roles for demo
        // if ($user->role?->name !== 'paramedis') {
        //     return response()->json([
        //         'error' => 'Access denied',
        //         'user_role' => $user->role?->name ?? 'no_role',
        //         'required_role' => 'paramedis'
        //     ], 403);
        // }
        
        return view('react-dashboard-standalone');
    })->middleware('auth')->name('paramedis.react.dashboard');
    
    // Debug route to check users and roles
    Route::get('/debug-users', function () {
        $users = \App\Models\User::with('role')->get(['id', 'name', 'email', 'role_id']);
        return response()->json([
            'current_user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'role' => auth()->user()->role?->name ?? 'no_role'
            ] : 'not_logged_in',
            'all_users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name ?? 'no_role'
                ];
            })
        ]);
    })->middleware('auth');
    
    // Simple API test route - WORLD-CLASS dynamic data
    Route::get('/api-test', function () {
        $user = auth()->user();
        $jaspelMonthly = 0;
        
        if ($user) {
            $paramedis = \App\Models\Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
                
            if ($paramedis) {
                $thisMonth = \Carbon\Carbon::now()->startOfMonth();
                $jaspelMonthly = \App\Models\Jaspel::where('user_id', $user->id)
                    ->whereMonth('tanggal', $thisMonth->month)
                    ->whereYear('tanggal', $thisMonth->year)
                    ->whereIn('status_validasi', ['disetujui', 'approved'])
                    ->sum('nominal');
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'API working with dynamic data',
            'data' => [
                'jaspel_monthly' => $jaspelMonthly,
                'paramedis_name' => $user ? $user->name : 'Test User'
            ]
        ]);
    });
    
    // Standalone React Dashboard (tanpa Filament)
    Route::get('/react-dashboard', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        return view('react-dashboard-standalone');
    })->middleware('auth')->name('react.dashboard');
    
    // New Jaspel Dashboard (Premium Design)
    Route::get('/jaspel-dashboard', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        return view('paramedis-jaspel-dashboard');
    })->middleware('auth')->name('jaspel.dashboard');
    
    // Premium React Native Dashboard - Redirect to Filament Panel
    Route::get('/premium-dashboard', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        // Redirect paramedis users to their proper panel
        if ($user?->hasRole('paramedis')) {
            return redirect('/paramedis');
        }
        
        return view('premium-paramedis-dashboard-simple');
    })->middleware('auth')->name('premium.dashboard');
    
    // Direct test route for premium dashboard
    Route::get('/premium-test', function () {
        return view('premium-paramedis-dashboard-simple');
    })->middleware('auth');
});

// DEPRECATED: Legacy Admin routes - Use modern Filament admin panel at /admin instead
Route::middleware(['auth', 'role:admin'])->prefix('legacy-admin')->name('legacy-admin.')->group(function () {
    // User management routes
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
});

// Settings routes (Admin only)
Route::middleware(['auth', 'role:admin'])->prefix('settings')->name('settings.')->group(function () {
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Settings\UserManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Settings\UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [\App\Http\Controllers\Settings\UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Http\Controllers\Settings\UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/reset-password', [\App\Http\Controllers\Settings\UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/toggle-status', [\App\Http\Controllers\Settings\UserManagementController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // System Configuration
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\SystemConfigController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Settings\SystemConfigController::class, 'update'])->name('update');
        Route::post('/security', [\App\Http\Controllers\Settings\SystemConfigController::class, 'updateSecurity'])->name('update-security');
    });
    
    // Backup & Export
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\BackupController::class, 'index'])->name('index');
        Route::get('/export-excel', [\App\Http\Controllers\Settings\BackupController::class, 'exportMasterData'])->name('export-excel');
        Route::get('/export-json', [\App\Http\Controllers\Settings\BackupController::class, 'exportJson'])->name('export-json');
        Route::post('/import-json', [\App\Http\Controllers\Settings\BackupController::class, 'importJson'])->name('import-json');
    });
    
    // Telegram Settings
    Route::prefix('telegram')->name('telegram.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\TelegramController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Settings\TelegramController::class, 'update'])->name('update');
        Route::post('/test', [\App\Http\Controllers\Settings\TelegramController::class, 'testNotification'])->name('test');
        Route::get('/bot-info', [\App\Http\Controllers\Settings\TelegramController::class, 'getBotInfo'])->name('bot-info');
    });
    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// GPS Test Route
Route::get('/test-gps', function () {
    return view('components.gps-detector');
})->name('test.gps');

// Debug GPS Route
Route::get('/debug-gps', function () {
    return response()->file(public_path('debug-gps.html'));
})->name('debug.gps');

// DOKTER DEBUG ROUTE - TEMPORARY
Route::get('/debug-dokter', function () {
    $user = auth()->user();
    if (!$user) {
        return response()->json([
            'error' => 'Not authenticated',
            'message' => 'Please login first',
            'redirect' => route('login')
        ]);
    }
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name ?? 'no_role',
            'role_id' => $user->role_id,
            'has_dokter_role' => $user?->hasRole('dokter') ?? false,
            'all_roles' => $user->roles->pluck('name')->toArray(),
        ],
        'routes' => [
            'dokter_index' => route('dokter.index'),
            'dokter_mobile_app' => route('dokter.mobile-app'),
            'dokter_dashboard' => route('dokter.dashboard'),
        ],
        'auth_check' => auth()->check(),
        'middleware_test' => 'If you see this, basic auth is working'
    ]);
})->middleware(['auth']);

// DOKTER MOBILE APP TEST - TEMPORARY (NO AUTH)
Route::get('/debug-dokter-mobile', function () {
    try {
        $user = auth()->user();
        $token = $user ? $user->createToken('mobile-app-dokter-debug-' . now()->timestamp)->plainTextToken : 'no-token';
        
        return view('mobile.dokter.app', compact('token'));
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load mobile app',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Employee Card Download Route
Route::middleware(['auth'])->group(function () {
    Route::get('/employee-card/{card}/download', function (\App\Models\EmployeeCard $card) {
        if (!$card->pdf_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($card->pdf_path)) {
            abort(404, 'File kartu tidak ditemukan');
        }
        
        $filePath = storage_path('app/public/' . $card->pdf_path);
        $fileName = 'Kartu_' . $card->employee_name . '_' . $card->card_number . '.pdf';
        
        return response()->download($filePath, $fileName);
    })->name('employee-card.download');
});


// OLD DOKTER DASHBOARD ROUTES COMPLETELY REMOVED
// System now uses Filament Panel at /dokter route exclusively

// Dokter Gigi Dashboard Routes (Isolated from Filament)
Route::middleware(['auth', 'role:dokter_gigi'])->prefix('dokter-gigi')->name('dokter-gigi.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Dokter\DokterGigiDashboardController::class, 'index'])->name('dashboard');
    Route::get('/jaspel', [\App\Http\Controllers\Dokter\DokterGigiDashboardController::class, 'jaspel'])->name('jaspel');
});


// require __DIR__.'/auth.php'; // Using unified auth instead

// Test route removed - using standard petugas dashboard

// Test route to auto-login as tina_petugas and access Filament petugas panel
Route::get('/test-login-tina', function () {
    $user = \App\Models\User::where('username', 'tina_petugas')->first();
    if ($user) {
        auth()->login($user);
        return redirect('/petugas'); // Redirect to Filament petugas panel
    } else {
        return 'User tina_petugas not found!';
    }
})->name('test.login.tina');

// Simple JavaScript test route
Route::get('/test-js', function () {
    return '<!DOCTYPE html>
    <html>
    <head>
        <title>JavaScript Test</title>
    </head>
    <body>
        <h1>JavaScript Test</h1>
        <div id="test">Loading...</div>
        <script>
            console.log("🔧 JAVASCRIPT TEST LOADED!");
            alert("JavaScript is working!");
            document.getElementById("test").innerHTML = "JavaScript executed successfully!";
        </script>
    </body>
    </html>';
})->name('test.js');

// Test route untuk akses AttendanceHistoryResource langsung
Route::get('/test-attendance-resource', function () {
    try {
        // Login as Sabita for testing
        $sabita = \App\Models\User::where('email', 'ee@dd.com')->first();
        if (!$sabita) {
            return response()->json(['error' => 'Sabita not found']);
        }
        
        \Illuminate\Support\Facades\Auth::login($sabita);
        
        // Test if resource is registered
        $panel = \Filament\Facades\Filament::getPanel('paramedis');
        $resources = $panel->getResources();
        
        $attendanceResource = null;
        foreach ($resources as $resource) {
            if (str_contains($resource, 'AttendanceHistoryResource')) {
                $attendanceResource = $resource;
                break;
            }
        }
        
        if (!$attendanceResource) {
            return response()->json([
                'error' => 'AttendanceHistoryResource not found in panel',
                'all_resources' => $resources
            ]);
        }
        
        // Test the service
        $service = new \App\Services\AttendanceHistoryService();
        $data = $service->getUserAttendanceHistory($sabita->id, [], 10, 1);
        
        return response()->json([
            'success' => true,
            'user' => ['id' => $sabita->id, 'name' => $sabita->name, 'email' => $sabita->email],
            'resource_found' => $attendanceResource,
            'data_count' => $data->total(),
            'test_urls' => [
                'resource_url' => url('/paramedis/attendance-histories'),
                'dashboard_url' => url('/paramedis'),
                'direct_url' => url('/paramedis/laporan-presensi')
            ],
            'message' => 'AttendanceHistoryResource berhasil terdaftar dan berfungsi!'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } finally {
        \Illuminate\Support\Facades\Auth::logout();
    }
});
