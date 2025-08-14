<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;

class DetailPasienPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static ?string $navigationLabel = 'Detail Pasien';
    
    protected static ?string $title = 'Detail Pasien';
    
    protected static string $view = 'filament.petugas.pages.detail-pasien';
    
    protected static ?string $navigationGroup = 'Manajemen Pasien';
    
    protected static ?int $navigationSort = 3;
    
    protected static bool $shouldRegisterNavigation = false; // Hide from navigation for now
    
    public ?string $pasienId = null;
    
    public function mount(): void
    {
        // Get pasien ID from route parameter or query string
        $this->pasienId = request()->route('pasien') ?? request()->query('id');
    }
    
    protected function getViewData(): array
    {
        $pasien = null;
        
        if ($this->pasienId) {
            // Try to find pasien data
            $pasien = \App\Models\Pasien::find($this->pasienId);
        }
        
        return [
            'pasien' => $pasien,
            'pasienId' => $this->pasienId
        ];
    }
}