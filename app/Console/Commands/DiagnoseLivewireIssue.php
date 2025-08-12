<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class DiagnoseLivewireIssue extends Command
{
    protected $signature = 'diagnose:livewire';
    protected $description = 'Diagnose Livewire update issues in Petugas panel';

    public function handle()
    {
        $this->info('=== Livewire Configuration Check ===');
        $this->line('Livewire Update Endpoint: ' . config('livewire.update_uri', '/livewire/update'));
        $this->line('Asset URL: ' . config('livewire.asset_url', 'Not set'));
        $this->line('App URL: ' . config('app.url'));
        
        $this->info("\n=== Checking Livewire Routes ===");
        $routes = Route::getRoutes();
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'livewire')) {
                $this->line('  ' . $route->methods()[0] . ' ' . $route->uri());
                if ($route->middleware()) {
                    $this->line('    Middleware: ' . implode(', ', $route->middleware()));
                }
            }
        }
        
        $this->info("\n=== Petugas Widgets Analysis ===");
        $widgetPath = app_path('Filament/Petugas/Widgets');
        if (is_dir($widgetPath)) {
            $files = glob($widgetPath . '/*.php');
            foreach ($files as $file) {
                $className = 'App\\Filament\\Petugas\\Widgets\\' . basename($file, '.php');
                if (class_exists($className)) {
                    $widget = basename($file, '.php');
                    $reflection = new ReflectionClass($className);
                    
                    // Check inheritance
                    $isLivewire = $reflection->isSubclassOf(\Livewire\Component::class);
                    $isFilamentWidget = $reflection->isSubclassOf(\Filament\Widgets\Widget::class);
                    
                    if ($widget === 'PetugasSimpleDashboardWidget') {
                        $this->info("  ✅ $widget:");
                        $this->line("     - Extends Filament Widget: " . ($isFilamentWidget ? 'Yes' : 'No'));
                        $this->line("     - Is Livewire Component: " . ($isLivewire ? 'Yes' : 'No'));
                        
                        // Check for problematic methods
                        if ($reflection->hasMethod('mount')) {
                            $method = $reflection->getMethod('mount');
                            $params = $method->getParameters();
                            if (count($params) > 0) {
                                $this->warn("     ⚠️  mount() has parameters - may cause issues");
                            }
                        }
                        
                        // Check for form integration
                        $traits = $reflection->getTraitNames();
                        foreach ($traits as $trait) {
                            if (str_contains($trait, 'InteractsWithForms')) {
                                $this->warn("     ⚠️  Uses InteractsWithForms trait");
                            }
                        }
                    }
                }
            }
        }
        
        $this->info("\n=== Session Configuration ===");
        $this->line('Driver: ' . config('session.driver'));
        $this->line('Lifetime: ' . config('session.lifetime') . ' minutes');
        $this->line('Cookie: ' . config('session.cookie'));
        $this->line('Same Site: ' . config('session.same_site'));
        
        $this->info("\n=== Potential Issues ===");
        
        // Check middleware
        $middlewareGroups = config('app.middleware_groups', []);
        if (isset($middlewareGroups['web'])) {
            foreach ($middlewareGroups['web'] as $middleware) {
                if (str_contains($middleware, 'ForceLocalSession')) {
                    $this->warn('⚠️  ForceLocalSession middleware in web group - may interfere with Livewire');
                }
                if (str_contains($middleware, 'ClearStaleSession')) {
                    $this->warn('⚠️  ClearStaleSession middleware in web group - may clear Livewire state');
                }
            }
        }
        
        // Check if simple widget is being used
        $dashboardPath = app_path('Filament/Petugas/Pages/Dashboard.php');
        if (file_exists($dashboardPath)) {
            $content = file_get_contents($dashboardPath);
            if (str_contains($content, 'PetugasSimpleDashboardWidget')) {
                $this->info('✅ Dashboard is using PetugasSimpleDashboardWidget (minimal Livewire)');
            } else {
                $this->warn('⚠️  Dashboard is not using the simple widget');
            }
        }
        
        $this->info("\n=== Recommendations ===");
        $this->line('1. Clear all caches: php artisan optimize:clear');
        $this->line('2. Check browser console for JavaScript errors');
        $this->line('3. Verify CSRF token is present in meta tags');
        $this->line('4. Check network tab for actual 500 error response body');
        $this->line('5. The simple widget should minimize Livewire update issues');
        
        return Command::SUCCESS;
    }
}