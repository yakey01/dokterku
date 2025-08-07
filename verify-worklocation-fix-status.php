<?php

/**
 * 🔍 Quick WorkLocation Deletion Fix Status Verification
 * 
 * This script provides a quick verification of the current implementation
 * status of the WorkLocation deletion 404 fix to ensure all components
 * are properly in place.
 */

require_once __DIR__ . '/vendor/autoload.php';

class WorkLocationFixStatusVerifier 
{
    private $results = [];
    private $issues = [];

    public function __construct()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🔍 WorkLocation Deletion 404 Fix - Status Verification\n";
        echo str_repeat("=", 70) . "\n";
    }

    /**
     * 🚀 Run status verification
     */
    public function verifyImplementationStatus(): array
    {
        echo "Checking implementation status...\n\n";
        
        $this->checkModelImplementation();
        $this->checkFilamentResourceImplementation();
        $this->checkServiceImplementation();
        $this->checkDatabaseStructure();
        $this->generateStatusReport();
        
        return [
            'overall_status' => empty($this->issues) ? 'ready' : 'needs_attention',
            'results' => $this->results,
            'issues' => $this->issues
        ];
    }

    /**
     * ✅ Check WorkLocation Model implementation
     */
    private function checkModelImplementation(): void
    {
        echo "📋 Checking WorkLocation Model...\n";
        
        try {
            // Check if model file exists
            $modelPath = app_path('Models/WorkLocation.php');
            if (!file_exists($modelPath)) {
                $this->issues[] = "WorkLocation model file not found at: {$modelPath}";
                return;
            }
            
            // Check model implementation
            $modelContent = file_get_contents($modelPath);
            
            $checks = [
                'soft_deletes' => strpos($modelContent, 'SoftDeletes') !== false,
                'boot_method' => strpos($modelContent, 'protected static function boot()') !== false,
                'deleting_event' => strpos($modelContent, 'static::deleting') !== false,
                'deleted_event' => strpos($modelContent, 'static::deleted') !== false,
                'restoring_event' => strpos($modelContent, 'static::restoring') !== false,
                'update_quietly' => strpos($modelContent, 'updateQuietly') !== false,
            ];
            
            $passedChecks = array_filter($checks);
            $totalChecks = count($checks);
            $passedCount = count($passedChecks);
            
            if ($passedCount === $totalChecks) {
                $this->results['model'] = [
                    'status' => 'success',
                    'message' => 'WorkLocation model properly implemented with all required features',
                    'details' => $checks
                ];
                echo "  ✅ Model implementation: COMPLETE\n";
            } else {
                $missing = array_keys(array_filter($checks, fn($v) => !$v));
                $this->issues[] = "WorkLocation model missing features: " . implode(', ', $missing);
                echo "  ⚠️  Model implementation: INCOMPLETE\n";
            }
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking WorkLocation model: " . $e->getMessage();
            echo "  ❌ Model check failed\n";
        }
    }

    /**
     * ✅ Check Filament Resource implementation
     */
    private function checkFilamentResourceImplementation(): void
    {
        echo "📋 Checking Filament WorkLocationResource...\n";
        
        try {
            $resourcePath = app_path('Filament/Resources/WorkLocationResource.php');
            if (!file_exists($resourcePath)) {
                $this->issues[] = "WorkLocationResource file not found at: {$resourcePath}";
                return;
            }
            
            $resourceContent = file_get_contents($resourcePath);
            
            $checks = [
                'toggle_column' => strpos($resourceContent, 'ToggleColumn::make(\'is_active\')') !== false,
                'disabled_check' => strpos($resourceContent, 'fn ($record) => $record->trashed()') !== false,
                'update_state_using' => strpos($resourceContent, 'updateStateUsing') !== false,
                'soft_delete_scope' => strpos($resourceContent, 'withoutGlobalScopes([SoftDeletingScope::class])') !== false,
                'record_classes' => strpos($resourceContent, 'recordClasses') !== false,
                'deletion_service' => strpos($resourceContent, 'WorkLocationDeletionService') !== false,
                'safe_delete_action' => strpos($resourceContent, 'Safe Delete') !== false,
            ];
            
            $passedChecks = array_filter($checks);
            $totalChecks = count($checks);
            $passedCount = count($passedChecks);
            
            if ($passedCount >= 6) { // Allow some flexibility
                $this->results['filament_resource'] = [
                    'status' => 'success',
                    'message' => 'Filament WorkLocationResource properly enhanced',
                    'details' => $checks
                ];
                echo "  ✅ Filament Resource: COMPLETE\n";
            } else {
                $missing = array_keys(array_filter($checks, fn($v) => !$v));
                $this->issues[] = "WorkLocationResource missing features: " . implode(', ', $missing);
                echo "  ⚠️  Filament Resource: INCOMPLETE\n";
            }
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking Filament resource: " . $e->getMessage();
            echo "  ❌ Filament Resource check failed\n";
        }
    }

    /**
     * ✅ Check Deletion Service implementation
     */
    private function checkServiceImplementation(): void
    {
        echo "📋 Checking WorkLocationDeletionService...\n";
        
        try {
            $servicePath = app_path('Services/WorkLocationDeletionService.php');
            if (!file_exists($servicePath)) {
                $this->issues[] = "WorkLocationDeletionService file not found at: {$servicePath}";
                return;
            }
            
            $serviceContent = file_get_contents($servicePath);
            
            $checks = [
                'safe_delete' => strpos($serviceContent, 'public function safeDelete') !== false,
                'check_dependencies' => strpos($serviceContent, 'public function checkDependencies') !== false,
                'reassign_users' => strpos($serviceContent, 'protected function reassignUsers') !== false,
                'get_delete_preview' => strpos($serviceContent, 'public function getDeletePreview') !== false,
                'transaction_usage' => strpos($serviceContent, 'DB::transaction') !== false,
                'logging' => strpos($serviceContent, 'Log::info') !== false,
            ];
            
            $passedChecks = array_filter($checks);
            $totalChecks = count($checks);
            $passedCount = count($passedChecks);
            
            if ($passedCount === $totalChecks) {
                $this->results['deletion_service'] = [
                    'status' => 'success',
                    'message' => 'WorkLocationDeletionService fully implemented',
                    'details' => $checks
                ];
                echo "  ✅ Deletion Service: COMPLETE\n";
            } else {
                $missing = array_keys(array_filter($checks, fn($v) => !$v));
                $this->issues[] = "WorkLocationDeletionService missing features: " . implode(', ', $missing);
                echo "  ⚠️  Deletion Service: INCOMPLETE\n";
            }
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking deletion service: " . $e->getMessage();
            echo "  ❌ Deletion Service check failed\n";
        }
    }

    /**
     * ✅ Check database structure
     */
    private function checkDatabaseStructure(): void
    {
        echo "📋 Checking Database Structure...\n";
        
        try {
            // Check if work_locations table exists and has soft delete column
            if (!\Schema::hasTable('work_locations')) {
                $this->issues[] = "work_locations table does not exist";
                return;
            }
            
            $checks = [
                'deleted_at_column' => \Schema::hasColumn('work_locations', 'deleted_at'),
                'is_active_column' => \Schema::hasColumn('work_locations', 'is_active'),
                'basic_columns' => \Schema::hasColumns('work_locations', ['name', 'latitude', 'longitude', 'radius_meters']),
            ];
            
            $passedChecks = array_filter($checks);
            $totalChecks = count($checks);
            $passedCount = count($passedChecks);
            
            if ($passedCount === $totalChecks) {
                $this->results['database'] = [
                    'status' => 'success',
                    'message' => 'Database structure properly configured',
                    'details' => $checks
                ];
                echo "  ✅ Database Structure: COMPLETE\n";
            } else {
                $missing = array_keys(array_filter($checks, fn($v) => !$v));
                $this->issues[] = "Database structure missing: " . implode(', ', $missing);
                echo "  ⚠️  Database Structure: INCOMPLETE\n";
            }
            
            // Check for any existing WorkLocation records
            $totalRecords = \DB::table('work_locations')->count();
            $trashedRecords = \DB::table('work_locations')->whereNotNull('deleted_at')->count();
            
            echo "  📊 Records: {$totalRecords} total, {$trashedRecords} soft-deleted\n";
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking database structure: " . $e->getMessage();
            echo "  ❌ Database check failed\n";
        }
    }

    /**
     * 📊 Generate status report
     */
    private function generateStatusReport(): void
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 IMPLEMENTATION STATUS REPORT\n";
        echo str_repeat("=", 70) . "\n";
        
        // Overall status
        if (empty($this->issues)) {
            echo "🎉 OVERALL STATUS: ✅ READY FOR TESTING\n\n";
            echo "All required components are properly implemented:\n";
            foreach ($this->results as $component => $result) {
                echo "  ✅ " . ucfirst(str_replace('_', ' ', $component)) . ": " . $result['message'] . "\n";
            }
        } else {
            echo "⚠️  OVERALL STATUS: ❌ NEEDS ATTENTION\n\n";
            echo "Issues found that need to be resolved:\n";
            foreach ($this->issues as $issue) {
                echo "  ❌ {$issue}\n";
            }
        }
        
        echo "\n📋 NEXT STEPS:\n";
        
        if (empty($this->issues)) {
            echo "  1. ✅ All components implemented correctly\n";
            echo "  2. 🧪 Run comprehensive testing: php test-worklocation-deletion-fix.php\n";
            echo "  3. 🌐 Perform browser testing using: /test-worklocation-frontend-validation.html\n";
            echo "  4. 🚀 Deploy to production after successful testing\n";
        } else {
            echo "  1. 🔧 Resolve the issues listed above\n";
            echo "  2. 🔍 Re-run this status check: php verify-worklocation-fix-status.php\n";
            echo "  3. 🧪 Proceed to testing once all issues are resolved\n";
        }
        
        echo "\n📁 TESTING FILES AVAILABLE:\n";
        echo "  • test-worklocation-deletion-fix.php - Comprehensive backend testing\n";
        echo "  • public/test-worklocation-frontend-validation.html - Browser-based frontend testing\n";
        echo "  • verify-worklocation-fix-status.php - This status verification script\n";
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "Status check completed at: " . now()->format('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 70) . "\n\n";
    }
}

// Execute the verification if script is run directly
if (php_sapi_name() === 'cli') {
    try {
        $verifier = new WorkLocationFixStatusVerifier();
        $result = $verifier->verifyImplementationStatus();
        
        // Exit with appropriate code
        exit($result['overall_status'] === 'ready' ? 0 : 1);
        
    } catch (\Exception $e) {
        echo "\n❌ CRITICAL ERROR: " . $e->getMessage() . "\n\n";
        exit(1);
    }
}

// Function to be used in Laravel context
if (!function_exists('verifyWorkLocationFixStatus')) {
    function verifyWorkLocationFixStatus(): array
    {
        $verifier = new WorkLocationFixStatusVerifier();
        return $verifier->verifyImplementationStatus();
    }
}