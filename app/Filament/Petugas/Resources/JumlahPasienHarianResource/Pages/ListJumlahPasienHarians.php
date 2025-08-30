<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJumlahPasienHarians extends ListRecords
{
    protected static string $resource = JumlahPasienHarianResource::class;
    protected static string $view = 'filament.petugas.pages.jumlah-pasien-full-layout';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Tambah Jumlah Pasien')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->size('lg')
                ->button()
                ->extraAttributes([
                    'class' => 'world-class-create-btn',
                    'style' => 'color: #000000 !important; -webkit-text-fill-color: #000000 !important; text-shadow: none !important;',
                    'data-force-black-text' => 'true',
                ]),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Temporarily disabled until widget is properly registered
            // \App\Filament\Petugas\Widgets\JumlahPasienHistoryStatsWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Data Jumlah Pasien Harian';
    }
    
    public function getSubheading(): ?string
    {
        // Removed detailed subheading - info now in horizontal metrics cards
        return null;
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            // Empty array, but this method allows us to add custom footer content
        ];
    }
    
    // FIXED: Removed getFooter() method that was causing 500 error
    // The method had incorrect return type (?string instead of ?View)
    // Custom styling can be implemented using CSS resources or custom themes
    
    /**
     * Add custom attributes to the body element
     * This is a safe alternative to the problematic getFooter method
     */
    public function getExtraBodyAttributes(): array
    {
        return [
            'data-custom-styling' => 'black-text-enforcement',
        ];
    }
}