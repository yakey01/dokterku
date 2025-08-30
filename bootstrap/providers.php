<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\CustomAuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\BendaharaPanelProvider::class,
    App\Providers\Filament\DokterPanelProvider::class,
    App\Providers\Filament\ManajerPanelProvider::class,
    App\Providers\Filament\ParamedisPanelProvider::class,
    App\Providers\Filament\PetugasPanelProvider::class,
    App\Providers\Filament\VerifikatorPanelProvider::class,
    
    // Module Service Providers
    App\Modules\User\Providers\UserServiceProvider::class,
    
    // Medical Procedure Services
    App\Providers\MedicalProcedureServiceProvider::class,
    
    // Bendahara Component Services
    App\Providers\BendaharaComponentServiceProvider::class,
];
