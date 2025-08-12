<?php

namespace App\Filament\Petugas\Widgets;

use Filament\Widgets\Widget;

class PetugasStaticDashboardWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.petugas-static-dashboard-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    // No Livewire properties or methods at all
    // This is a completely static widget
}