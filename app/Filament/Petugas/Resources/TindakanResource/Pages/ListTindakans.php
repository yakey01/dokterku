<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTindakans extends ListRecords
{
    protected static string $resource = TindakanResource::class;

    protected static string $view = 'filament.petugas.pages.world-class-tindakan-list';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Input Tindakan Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->size('lg')
                ->button()
                ->extraAttributes([
                    'class' => 'world-class-create-btn',
                    'style' => 'color: #000000 !important; -webkit-text-fill-color: #000000 !important; text-shadow: none !important;',
                    'data-force-black-text' => 'true',
                ]),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('🗂️ Semua Data')
                ->icon('heroicon-o-queue-list')
                ->badge($this->getTabBadge('all')),

            'sudah_validasi' => Tab::make('✅ Sudah Validasi')
                ->icon('heroicon-o-check-circle')
                ->badge($this->getTabBadge('sudah_validasi'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui')),

            'belum_validasi' => Tab::make('⏳ Belum Validasi')
                ->icon('heroicon-o-clock')
                ->badge($this->getTabBadge('belum_validasi'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),

            'dokter' => Tab::make('👨‍⚕️ Dokter')
                ->icon('heroicon-o-user-circle')
                ->badge($this->getTabBadge('dokter'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('dokter_id')),

            'paramedis' => Tab::make('👩‍⚕️ Paramedis')
                ->icon('heroicon-o-heart')
                ->badge($this->getTabBadge('paramedis'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('paramedis_id')),
        ];
    }

    private function getTabBadge(string $tab): int
    {
        return match($tab) {
            'all' => static::getResource()::getEloquentQuery()->count(),
            'sudah_validasi' => static::getResource()::getEloquentQuery()->where('status_validasi', 'disetujui')->count(),
            'belum_validasi' => static::getResource()::getEloquentQuery()->where('status_validasi', 'pending')->count(),
            'dokter' => static::getResource()::getEloquentQuery()->whereNotNull('dokter_id')->count(),
            'paramedis' => static::getResource()::getEloquentQuery()->whereNotNull('paramedis_id')->count(),
            default => 0,
        };
    }
    
    public function getTitle(): string
    {
        return '🩺 Data Tindakan Medis';
    }
    
    public function getSubheading(): ?string
    {
        return null;
    }
    
    public function getExtraBodyAttributes(): array
    {
        return [
            'data-custom-styling' => 'black-text-enforcement',
        ];
    }

    public function filterTable(string $tabKey): void
    {
        // Update URL parameter to persist the selected tab
        request()->merge(['activeTab' => $tabKey]);
        
        // Simply refresh the component - the filtering will be handled by getTableQuery()
        $this->dispatch('$refresh');
    }

    public function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Check for active tab filter from URL
        $activeTab = request()->get('activeTab', 'all');
        
        switch ($activeTab) {
            case 'sudah_validasi':
                return $query->where('status_validasi', 'disetujui');
                
            case 'belum_validasi':
                return $query->where('status_validasi', 'pending');
                
            case 'dokter':
                return $query->whereNotNull('dokter_id');
                
            case 'paramedis':
                return $query->whereNotNull('paramedis_id');
                
            case 'all':
            default:
                return $query;
        }
    }
}