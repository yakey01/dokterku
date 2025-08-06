<?php

namespace App\Providers;

use App\Services\Medical\Procedures\MedicalProcedureSeederService;
use App\Services\Medical\Procedures\ProcedureValidationService;
use App\Services\Medical\Procedures\Data\MedicalProcedureDataProvider;
use App\Services\Medical\Procedures\Calculators\FeeCalculatorService;
use App\Services\Medical\Procedures\Generators\ProcedureCodeGenerator;
use Illuminate\Support\ServiceProvider;

class MedicalProcedureServiceProvider extends ServiceProvider
{
    /**
     * Register medical procedure services
     */
    public function register(): void
    {
        // Register core services
        $this->app->singleton(MedicalProcedureDataProvider::class);
        $this->app->singleton(FeeCalculatorService::class);
        $this->app->singleton(ProcedureCodeGenerator::class);
        
        // Register validation service with dependencies
        $this->app->singleton(ProcedureValidationService::class, function ($app) {
            return new ProcedureValidationService(
                $app->make(ProcedureCodeGenerator::class)
            );
        });
        
        // Register main seeder service with all dependencies
        $this->app->singleton(MedicalProcedureSeederService::class, function ($app) {
            return new MedicalProcedureSeederService(
                $app->make(MedicalProcedureDataProvider::class),
                $app->make(FeeCalculatorService::class),
                $app->make(ProcedureCodeGenerator::class)
            );
        });
    }

    /**
     * Bootstrap medical procedure services
     */
    public function boot(): void
    {
        // Any bootstrapping logic if needed
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            MedicalProcedureDataProvider::class,
            FeeCalculatorService::class,
            ProcedureCodeGenerator::class,
            ProcedureValidationService::class,
            MedicalProcedureSeederService::class,
        ];
    }
}