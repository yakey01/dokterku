<?php

namespace App\Filament\Bendahara\Components;

use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BlackThemeCard extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $value = null,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $trend = null,
        public ?string $color = 'emerald'
    ) {}

    public function render(): View
    {
        return view('filament.bendahara.components.black-theme-card');
    }
}