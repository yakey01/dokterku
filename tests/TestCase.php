<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\Traits\RoleSetupTrait;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, RoleSetupTrait;

    /**
     * Indicates if the test is using in-memory database
     */
    protected $seed = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create unique SQLite database file for each test process to avoid conflicts
        $this->setupUniqueTestDatabase();
        
        // Clear any existing permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Enable query logging for performance testing
        DB::enableQueryLog();
        
        // Set up test-specific configuration
        $this->configureTestEnvironment();
    }
    
    /**
     * Configure test environment settings
     */
    protected function configureTestEnvironment(): void
    {
        config(['app.env' => 'testing']);
        config(['cache.default' => 'array']);
        config(['session.driver' => 'array']);
        config(['queue.default' => 'sync']);
        config(['api.rate_limits.enabled' => false]);
        config(['logging.default' => 'stderr']);
    }
    
    /**
     * Setup unique SQLite database for each test process
     */
    protected function setupUniqueTestDatabase(): void
    {
        // Use consistent database file name that matches phpunit.xml configuration
        $databasePath = "database/testing.sqlite";
        
        // Ensure database directory exists
        if (!is_dir(dirname($databasePath))) {
            mkdir(dirname($databasePath), 0755, true);
        }
        
        // Create empty database file if it doesn't exist
        if (!file_exists($databasePath)) {
            touch($databasePath);
        }
        
        // Configure database connection to use the same file as phpunit.xml
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => $databasePath]);
        
        // Force a fresh database connection
        \DB::purge('sqlite');
        \DB::reconnect('sqlite');
        
        // Run migrations fresh for this database
        $this->artisan('migrate:fresh', [
            '--drop-views' => true,
            '--quiet' => true,
        ]);
        
        // Reset role tracking since database is fresh
        $this->resetRoleSetup();
        
        // Setup roles once after migrations
        $this->setupRoles();
    }

    /**
     * Create application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        
        return $app;
    }

    /**
     * Refresh the test database.
     */
    protected function refreshTestDatabase()
    {
        $this->artisan('migrate:fresh', [
            '--drop-views' => true,
            '--quiet' => true,
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }
    
    /**
     * Clean up test database files after tests
     */
    protected function tearDown(): void
    {
        // Clear query log
        DB::flushQueryLog();
        
        // Clean up test database files
        $this->cleanupTestDatabases();
        
        parent::tearDown();
    }
    
    /**
     * Assert that response time is within acceptable limits
     */
    protected function assertResponseTimeWithin(int $maxMilliseconds = 2000): void
    {
        $executionTime = (microtime(true) - LARAVEL_START) * 1000;
        
        $this->assertLessThan(
            $maxMilliseconds,
            $executionTime,
            "Response time {$executionTime}ms exceeded limit of {$maxMilliseconds}ms"
        );
    }
    
    /**
     * Assert that database query count is within limits
     */
    protected function assertQueryCountWithin(int $maxQueries = 20): void
    {
        $queryCount = count(DB::getQueryLog());
        
        $this->assertLessThan(
            $maxQueries,
            $queryCount,
            "Query count {$queryCount} exceeded limit of {$maxQueries}"
        );
    }
    
    /**
     * Assert that memory usage is within limits
     */
    protected function assertMemoryUsageWithin(int $maxMegabytes = 64): void
    {
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        
        $this->assertLessThan(
            $maxMegabytes,
            $memoryUsage,
            "Memory usage {$memoryUsage}MB exceeded limit of {$maxMegabytes}MB"
        );
    }
    
    /**
     * Assert JSON response has standard API structure
     */
    protected function assertStandardApiResponse($response): void
    {
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'timestamp'
            ]
        ]);
    }
    
    /**
     * Assert error response has standard structure
     */
    protected function assertErrorResponse($response, int $statusCode = 400): void
    {
        $response->assertStatus($statusCode)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'meta' => [
                        'timestamp'
                    ]
                ]);
        
        $response->assertJson(['success' => false]);
    }
    
    /**
     * Assert API endpoint performance meets standards
     */
    protected function assertApiPerformance($response): void
    {
        $response->assertSuccessful();
        $this->assertResponseTimeWithin(2000); // 2 seconds max
        $this->assertQueryCountWithin(20);     // Max 20 queries
        $this->assertMemoryUsageWithin(64);    // Max 64MB
    }
    
    /**
     * Clean up test database files
     */
    protected function cleanupTestDatabases(): void
    {
        $databaseDir = 'database';
        if (is_dir($databaseDir)) {
            $files = glob($databaseDir . '/testing*.sqlite');
            foreach ($files as $file) {
                // Only delete files older than 1 hour to avoid conflicts with running tests
                if (filemtime($file) < (time() - 3600)) {
                    unlink($file);
                }
            }
        }
    }
}
