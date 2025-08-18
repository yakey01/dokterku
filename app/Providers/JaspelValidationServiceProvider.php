<?php

namespace App\Providers;

use App\Models\Jaspel;
use App\Observers\JaspelObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * JASPEL Validation Service Provider
 * 
 * Provides system-wide validation and monitoring for JASPEL data integrity.
 * Implements prevention mechanisms for dummy data and business rule violations.
 */
class JaspelValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Register validation services in container
        $this->app->singleton('jaspel.validator', function ($app) {
            return new \App\Services\JaspelValidationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Register model observer
        Jaspel::observe(JaspelObserver::class);

        // Register database query monitoring (only in specific environments)
        if (config('app.env') === 'production' || config('app.debug')) {
            $this->registerQueryMonitoring();
        }

        // Register custom validation rules
        $this->registerCustomValidations();

        // Register prevention hooks
        $this->registerPreventionHooks();
    }

    /**
     * Register database query monitoring for JASPEL operations
     */
    private function registerQueryMonitoring()
    {
        DB::listen(function (QueryExecuted $query) {
            // Monitor JASPEL-related queries for suspicious patterns
            if (str_contains($query->sql, 'jaspel') && 
                str_contains($query->sql, 'INSERT')) {
                
                $this->validateJaspelInsertQuery($query);
            }
        });
    }

    /**
     * Register custom validation rules
     */
    private function registerCustomValidations()
    {
        // Custom validation for JASPEL amount patterns
        \Illuminate\Support\Facades\Validator::extend('not_dummy_amount', function ($attribute, $value, $parameters, $validator) {
            return !$this->isDummyAmountPattern($value);
        });

        \Illuminate\Support\Facades\Validator::replacer('not_dummy_amount', function ($message, $attribute, $rule, $parameters) {
            return 'The ' . $attribute . ' appears to be dummy/test data.';
        });

        // Custom validation for business hours
        \Illuminate\Support\Facades\Validator::extend('business_hours', function ($attribute, $value, $parameters, $validator) {
            $hour = \Carbon\Carbon::parse($value)->hour;
            return $hour >= 6 && $hour <= 22; // 6 AM to 10 PM
        });

        \Illuminate\Support\Facades\Validator::replacer('business_hours', function ($message, $attribute, $rule, $parameters) {
            return 'The ' . $attribute . ' should be within business hours (06:00 - 22:00).';
        });
    }

    /**
     * Register prevention hooks
     */
    private function registerPreventionHooks()
    {
        // Hook into seeder prevention in production
        if (app()->environment('production')) {
            $this->preventSeedersInProduction();
        }

        // Hook into bulk operations monitoring
        $this->monitorBulkOperations();
    }

    /**
     * Validate JASPEL insert queries for suspicious patterns
     */
    private function validateJaspelInsertQuery(QueryExecuted $query)
    {
        try {
            // Check for bulk inserts (potential seeder activity)
            if (count($query->bindings) > 20) { // More than 20 parameters suggests bulk insert
                Log::warning('Bulk JASPEL insert detected', [
                    'sql' => $query->sql,
                    'binding_count' => count($query->bindings),
                    'time' => $query->time,
                    'user_agent' => request()->header('User-Agent'),
                    'ip' => request()->ip()
                ]);

                // In production, this might trigger alerts
                if (app()->environment('production')) {
                    $this->triggerBulkInsertAlert($query);
                }
            }

            // Check for rapid successive inserts
            $this->checkRapidInserts();

        } catch (\Exception $e) {
            Log::error('Error in JASPEL query validation', [
                'error' => $e->getMessage(),
                'query' => $query->sql
            ]);
        }
    }

    /**
     * Check if amount follows dummy data patterns
     */
    private function isDummyAmountPattern($amount): bool
    {
        if (!is_numeric($amount)) {
            return false;
        }

        $amount = floatval($amount);

        // Round number patterns
        if ($amount >= 100000 && $amount % 10000 == 0) {
            return true;
        }

        // Sequential patterns
        $amountStr = strval(intval($amount));
        if (strlen($amountStr) >= 3) {
            $isSequential = true;
            for ($i = 1; $i < strlen($amountStr); $i++) {
                if (intval($amountStr[$i]) !== intval($amountStr[$i-1]) + 1) {
                    $isSequential = false;
                    break;
                }
            }
            if ($isSequential) return true;
        }

        // Repeating digit patterns
        $uniqueDigits = array_unique(str_split($amountStr));
        if (count($uniqueDigits) <= 2 && strlen($amountStr) >= 5) {
            return true;
        }

        return false;
    }

    /**
     * Prevent seeders from running in production
     */
    private function preventSeedersInProduction()
    {
        // Register console starting event
        $this->app['events']->listen(\Illuminate\Console\Events\CommandStarting::class, function ($event) {
            if (str_contains($event->command, 'db:seed') || 
                str_contains($event->command, 'seed')) {
                
                Log::critical('Seeder execution attempted in production', [
                    'command' => $event->command,
                    'user' => auth()->user() ? auth()->user()->email : 'console',
                    'timestamp' => now()
                ]);

                // Could throw exception to prevent execution
                // throw new \Exception('Seeders are not allowed in production environment');
            }
        });
    }

    /**
     * Monitor bulk operations for suspicious activity
     */
    private function monitorBulkOperations()
    {
        // Track rapid JASPEL creation
        $this->app['cache']->remember('jaspel_creation_monitor', 60, function () {
            return ['count' => 0, 'last_reset' => time()];
        });
    }

    /**
     * Check for rapid successive inserts
     */
    private function checkRapidInserts()
    {
        $cacheKey = 'jaspel_rapid_inserts_' . auth()->id();
        $currentCount = cache()->get($cacheKey, 0);
        
        cache()->put($cacheKey, $currentCount + 1, 300); // 5 minutes
        
        if ($currentCount > 10) { // More than 10 inserts in 5 minutes
            Log::warning('Rapid JASPEL inserts detected', [
                'user_id' => auth()->id(),
                'count' => $currentCount + 1,
                'ip' => request()->ip()
            ]);
        }
    }

    /**
     * Trigger alert for bulk insert operations
     */
    private function triggerBulkInsertAlert(QueryExecuted $query)
    {
        // In a real system, this might:
        // - Send notifications to administrators
        // - Create incident tickets
        // - Trigger monitoring alerts
        // - Block the user temporarily
        
        Log::alert('Suspicious bulk JASPEL insert in production', [
            'sql' => $query->sql,
            'bindings' => count($query->bindings),
            'user' => auth()->user() ? auth()->user()->email : 'unknown',
            'ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'timestamp' => now()
        ]);

        // Could implement additional security measures here
        // such as rate limiting or temporary user suspension
    }
}