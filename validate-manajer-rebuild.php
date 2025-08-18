<?php

/**
 * Comprehensive Manajer Dashboard Validation Script
 * Tests all backend components of the rebuilt Manajer system
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Carbon\Carbon;

class ManajerDashboardValidator
{
    private $results = [];
    private $errors = [];
    private $baseUrl;
    
    public function __construct()
    {
        // Initialize Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        $this->baseUrl = config('app.url');
        
        echo "🔍 MANAJER DASHBOARD VALIDATION STARTED\n";
        echo "=====================================\n\n";
    }
    
    /**
     * Run all validation tests
     */
    public function runAllTests()
    {
        $this->testDatabaseConnectivity();
        $this->testModelIntegrity();
        $this->testApiEndpoints();
        $this->testAuthentication();
        $this->testPermissions();
        $this->testFilamentPages();
        $this->testDataIntegrity();
        $this->testWebSocketChannels();
        
        $this->generateReport();
    }
    
    /**
     * Test database connectivity for all models
     */
    private function testDatabaseConnectivity()
    {
        echo "📊 Testing Database Connectivity...\n";
        
        $models = [
            'User' => \App\Models\User::class,
            'Dokter' => \App\Models\Dokter::class,
            'Pegawai' => \App\Models\Pegawai::class,
            'PendapatanHarian' => \App\Models\PendapatanHarian::class,
            'PengeluaranHarian' => \App\Models\PengeluaranHarian::class,
            'JumlahPasienHarian' => \App\Models\JumlahPasienHarian::class,
            'Tindakan' => \App\Models\Tindakan::class,
            'Jaspel' => \App\Models\Jaspel::class,
            'DokterPresensi' => \App\Models\DokterPresensi::class,
            'Attendance' => \App\Models\Attendance::class,
        ];
        
        foreach ($models as $name => $class) {
            try {
                $count = $class::count();
                $this->results["db_$name"] = "✅ $name: $count records";
                echo "  ✅ $name: $count records\n";
            } catch (Exception $e) {
                $this->errors["db_$name"] = "❌ $name: " . $e->getMessage();
                echo "  ❌ $name: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    /**
     * Test model integrity and relationships
     */
    private function testModelIntegrity()
    {
        echo "🔗 Testing Model Integrity...\n";
        
        try {
            // Test User-Dokter relationship
            $doctorUser = User::whereHas('roles', function($q) {
                $q->where('name', 'dokter');
            })->first();
            
            if ($doctorUser) {
                $dokter = $doctorUser->dokter;
                $this->results['user_dokter_relation'] = $dokter ? "✅ User-Dokter relationship working" : "⚠️ No Dokter record for user";
                echo "  " . $this->results['user_dokter_relation'] . "\n";
            }
            
            // Test manager user exists
            $managerUser = User::whereHas('roles', function($q) {
                $q->where('name', 'manajer');
            })->first();
            
            if ($managerUser) {
                $this->results['manager_user'] = "✅ Manager user found: " . $managerUser->email;
                echo "  ✅ Manager user found: " . $managerUser->email . "\n";
            } else {
                $this->errors['manager_user'] = "❌ No manager user found";
                echo "  ❌ No manager user found\n";
            }
            
            // Test data relationships
            $this->testDataRelationships();
            
        } catch (Exception $e) {
            $this->errors['model_integrity'] = "❌ Model integrity error: " . $e->getMessage();
            echo "  ❌ Model integrity error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    /**
     * Test data relationships
     */
    private function testDataRelationships()
    {
        try {
            // Test Tindakan-Dokter relationship
            $tindakan = \App\Models\Tindakan::with('dokter')->first();
            if ($tindakan && $tindakan->dokter) {
                $this->results['tindakan_dokter'] = "✅ Tindakan-Dokter relationship working";
                echo "  ✅ Tindakan-Dokter relationship working\n";
            }
            
            // Test Jaspel calculations
            $jaspel = \App\Models\Jaspel::first();
            if ($jaspel) {
                $this->results['jaspel_data'] = "✅ Jaspel data available";
                echo "  ✅ Jaspel data available\n";
            }
            
        } catch (Exception $e) {
            $this->errors['data_relationships'] = "❌ Data relationship error: " . $e->getMessage();
            echo "  ❌ Data relationship error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test API endpoints
     */
    private function testApiEndpoints()
    {
        echo "🌐 Testing API Endpoints...\n";
        
        // Get manager user for testing
        $managerUser = User::whereHas('roles', function($q) {
            $q->where('name', 'manajer');
        })->first();
        
        if (!$managerUser) {
            $this->errors['api_no_manager'] = "❌ Cannot test API: No manager user found";
            echo "  ❌ Cannot test API: No manager user found\n\n";
            return;
        }
        
        $endpoints = [
            '/api/v2/manajer/dashboard' => 'Dashboard Data',
            '/api/v2/manajer/finance' => 'Finance Data',
            '/api/v2/manajer/attendance' => 'Attendance Data',
            '/api/v2/manajer/jaspel' => 'Jaspel Data',
            '/api/v2/manajer/profile' => 'Profile Data',
        ];
        
        foreach ($endpoints as $endpoint => $description) {
            try {
                // Simulate API call by calling controller directly
                $this->testControllerMethod($endpoint, $description, $managerUser);
            } catch (Exception $e) {
                $this->errors["api_$endpoint"] = "❌ $description: " . $e->getMessage();
                echo "  ❌ $description: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    /**
     * Test controller methods directly
     */
    private function testControllerMethod($endpoint, $description, $user)
    {
        try {
            $controller = new \App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController();
            
            // Mock authentication
            auth()->login($user);
            
            $request = new \Illuminate\Http\Request();
            
            switch ($endpoint) {
                case '/api/v2/manajer/dashboard':
                    $response = $controller->getDashboardData($request);
                    break;
                case '/api/v2/manajer/finance':
                    $response = $controller->getFinanceData($request);
                    break;
                case '/api/v2/manajer/attendance':
                    $response = $controller->getAttendanceData($request);
                    break;
                case '/api/v2/manajer/jaspel':
                    $response = $controller->getJaspelData($request);
                    break;
                case '/api/v2/manajer/profile':
                    $response = $controller->getProfileData($request);
                    break;
                default:
                    throw new Exception("Unknown endpoint");
            }
            
            if ($response && $response->getStatusCode() === 200) {
                $this->results["api_$endpoint"] = "✅ $description: Working";
                echo "  ✅ $description: Working\n";
            } else {
                $this->errors["api_$endpoint"] = "❌ $description: Invalid response";
                echo "  ❌ $description: Invalid response\n";
            }
            
        } catch (Exception $e) {
            $this->errors["api_$endpoint"] = "❌ $description: " . $e->getMessage();
            echo "  ❌ $description: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test authentication
     */
    private function testAuthentication()
    {
        echo "🔐 Testing Authentication...\n";
        
        try {
            // Test manager user authentication
            $managerUser = User::whereHas('roles', function($q) {
                $q->where('name', 'manajer');
            })->first();
            
            if ($managerUser) {
                // Test role verification
                if ($managerUser->hasRole('manajer')) {
                    $this->results['auth_manager_role'] = "✅ Manager role verification working";
                    echo "  ✅ Manager role verification working\n";
                } else {
                    $this->errors['auth_manager_role'] = "❌ Manager role verification failed";
                    echo "  ❌ Manager role verification failed\n";
                }
                
                // Test permissions
                $permissions = $managerUser->getAllPermissions();
                $this->results['auth_permissions'] = "✅ Manager has " . $permissions->count() . " permissions";
                echo "  ✅ Manager has " . $permissions->count() . " permissions\n";
            }
            
        } catch (Exception $e) {
            $this->errors['authentication'] = "❌ Authentication error: " . $e->getMessage();
            echo "  ❌ Authentication error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    /**
     * Test permissions
     */
    private function testPermissions()
    {
        echo "🛡️ Testing Permissions...\n";
        
        try {
            $managerUser = User::whereHas('roles', function($q) {
                $q->where('name', 'manajer');
            })->first();
            
            if ($managerUser) {
                // Test key permissions
                $keyPermissions = [
                    'view_dashboard',
                    'view_financial_data',
                    'view_staff_performance',
                    'approve_high_value_transactions'
                ];
                
                foreach ($keyPermissions as $permission) {
                    if ($managerUser->can($permission)) {
                        $this->results["perm_$permission"] = "✅ Permission '$permission': Granted";
                        echo "  ✅ Permission '$permission': Granted\n";
                    } else {
                        $this->results["perm_$permission"] = "⚠️ Permission '$permission': Not granted";
                        echo "  ⚠️ Permission '$permission': Not granted\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->errors['permissions'] = "❌ Permission error: " . $e->getMessage();
            echo "  ❌ Permission error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    /**
     * Test Filament pages
     */
    private function testFilamentPages()
    {
        echo "📱 Testing Filament Pages...\n";
        
        try {
            // Check if Filament resources exist
            $resources = [
                'StrategicPlanningResource' => \App\Filament\Manajer\Resources\StrategicPlanningResource::class,
                'EmployeePerformanceResource' => \App\Filament\Manajer\Resources\EmployeePerformanceResource::class,
                'FinancialOversightResource' => \App\Filament\Manajer\Resources\FinancialOversightResource::class,
                'OperationalAnalyticsResource' => \App\Filament\Manajer\Resources\OperationalAnalyticsResource::class,
            ];
            
            foreach ($resources as $name => $class) {
                if (class_exists($class)) {
                    $this->results["filament_$name"] = "✅ $name: Available";
                    echo "  ✅ $name: Available\n";
                } else {
                    $this->errors["filament_$name"] = "❌ $name: Missing";
                    echo "  ❌ $name: Missing\n";
                }
            }
            
            // Test dashboard page
            if (class_exists(\App\Filament\Manajer\Pages\Dashboard::class)) {
                $this->results['filament_dashboard'] = "✅ Manajer Dashboard Page: Available";
                echo "  ✅ Manajer Dashboard Page: Available\n";
            } else {
                $this->errors['filament_dashboard'] = "❌ Manajer Dashboard Page: Missing";
                echo "  ❌ Manajer Dashboard Page: Missing\n";
            }
            
        } catch (Exception $e) {
            $this->errors['filament_pages'] = "❌ Filament pages error: " . $e->getMessage();
            echo "  ❌ Filament pages error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    /**
     * Test data integrity
     */
    private function testDataIntegrity()
    {
        echo "🔍 Testing Data Integrity...\n";
        
        try {
            $currentMonth = Carbon::now();
            
            // Test financial data consistency
            $pendapatan = \App\Models\PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->where('validation_status', 'approved')
                ->sum('nominal');
                
            $pengeluaran = \App\Models\PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->where('validation_status', 'approved')
                ->sum('nominal');
                
            $this->results['data_financial'] = "✅ Financial data: Revenue=$pendapatan, Expenses=$pengeluaran";
            echo "  ✅ Financial data: Revenue=$pendapatan, Expenses=$pengeluaran\n";
            
            // Test patient data
            $patients = \App\Models\JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
                
            $this->results['data_patients'] = "✅ Patient data: $patients total patients this month";
            echo "  ✅ Patient data: $patients total patients this month\n";
            
            // Test staff data
            $doctors = \App\Models\Dokter::count();
            $staff = \App\Models\Pegawai::count();
            
            $this->results['data_staff'] = "✅ Staff data: $doctors doctors, $staff staff members";
            echo "  ✅ Staff data: $doctors doctors, $staff staff members\n";
            
        } catch (Exception $e) {
            $this->errors['data_integrity'] = "❌ Data integrity error: " . $e->getMessage();
            echo "  ❌ Data integrity error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    /**
     * Test WebSocket channels
     */
    private function testWebSocketChannels()
    {
        echo "📡 Testing WebSocket Channels...\n";
        
        try {
            // Check if broadcast channels are defined
            $channels = [
                'manajer.kpi-updates',
                'manajer.critical-alerts',
                'manajer.performance-updates',
                'manajer.strategic-updates',
                'manajer.approval-alerts'
            ];
            
            $channelFile = file_get_contents(__DIR__ . '/routes/channels.php');
            
            foreach ($channels as $channel) {
                if (strpos($channelFile, $channel) !== false) {
                    $this->results["ws_$channel"] = "✅ WebSocket channel '$channel': Defined";
                    echo "  ✅ WebSocket channel '$channel': Defined\n";
                } else {
                    $this->errors["ws_$channel"] = "❌ WebSocket channel '$channel': Missing";
                    echo "  ❌ WebSocket channel '$channel': Missing\n";
                }
            }
            
        } catch (Exception $e) {
            $this->errors['websocket'] = "❌ WebSocket error: " . $e->getMessage();
            echo "  ❌ WebSocket error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    /**
     * Generate final report
     */
    private function generateReport()
    {
        echo "📋 VALIDATION REPORT\n";
        echo "===================\n\n";
        
        $totalTests = count($this->results) + count($this->errors);
        $passedTests = count($this->results);
        $failedTests = count($this->errors);
        
        echo "📊 SUMMARY:\n";
        echo "  Total Tests: $totalTests\n";
        echo "  Passed: $passedTests ✅\n";
        echo "  Failed: $failedTests ❌\n";
        echo "  Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        if (!empty($this->errors)) {
            echo "❌ FAILURES:\n";
            foreach ($this->errors as $test => $error) {
                echo "  $error\n";
            }
            echo "\n";
        }
        
        echo "✅ SUCCESSES:\n";
        foreach ($this->results as $test => $result) {
            echo "  $result\n";
        }
        echo "\n";
        
        // Recommendations
        echo "💡 RECOMMENDATIONS:\n";
        if ($failedTests > 0) {
            echo "  • Fix failed tests before deployment\n";
            echo "  • Verify database migrations are up to date\n";
            echo "  • Ensure all required permissions are seeded\n";
            echo "  • Check Filament panel configuration\n";
        } else {
            echo "  • All tests passed! System is ready for deployment\n";
            echo "  • Consider running frontend validation next\n";
        }
        
        echo "\n🏁 Validation completed at " . now()->format('Y-m-d H:i:s') . "\n";
    }
}

// Run validation if script is executed directly
if (isset($argv) && basename($argv[0]) === 'validate-manajer-rebuild.php') {
    $validator = new ManajerDashboardValidator();
    $validator->runAllTests();
}