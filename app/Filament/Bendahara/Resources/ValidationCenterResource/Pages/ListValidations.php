<?php

namespace App\Filament\Bendahara\Resources\ValidationCenterResource\Pages;

use App\Filament\Bendahara\Resources\ValidationCenterResource;
use App\Models\Tindakan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class ListValidations extends ListRecords
{
    protected static string $resource = ValidationCenterResource::class;
    
    // Use default Filament view for proper horizontal scroll functionality
    // Custom styling applied via bendahara theme.css

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Validasi Baru')
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

            'pending' => Tab::make('⏳ Menunggu Validasi')
                ->icon('heroicon-o-clock')
                ->badge($this->getTabBadge('pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),

            'approved' => Tab::make('✅ Sudah Disetujui')
                ->icon('heroicon-o-check-circle')
                ->badge($this->getTabBadge('approved'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui')),

            'rejected' => Tab::make('❌ Ditolak')
                ->icon('heroicon-o-x-circle')
                ->badge($this->getTabBadge('rejected'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'ditolak')),

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
            'pending' => static::getResource()::getEloquentQuery()->where('status_validasi', 'pending')->count(),
            'approved' => static::getResource()::getEloquentQuery()->where('status_validasi', 'disetujui')->count(),
            'rejected' => static::getResource()::getEloquentQuery()->where('status_validasi', 'ditolak')->count(),
            'dokter' => static::getResource()::getEloquentQuery()->whereNotNull('dokter_id')->count(),
            'paramedis' => static::getResource()::getEloquentQuery()->whereNotNull('paramedis_id')->count(),
            default => 0,
        };
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
            case 'pending':
                return $query->where('status_validasi', 'pending');
                
            case 'approved':
                return $query->where('status_validasi', 'disetujui');
                
            case 'rejected':
                return $query->where('status_validasi', 'ditolak');
                
            case 'dokter':
                return $query->whereNotNull('dokter_id');
                
            case 'paramedis':
                return $query->whereNotNull('paramedis_id');
                
            case 'all':
            default:
                return $query;
        }
    }

    public function getTitle(): string
    {
        return '🔍 Validation Center';
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

    private function performQuickAction(string $actionType): void
    {
        try {
            $affected = 0;
            
            switch ($actionType) {
                case 'approve_low_value':
                    $records = Tindakan::where('status_validasi', 'pending')
                        ->where('tarif', '<', 100000)
                        ->get();
                    
                    foreach ($records as $record) {
                        $record->update([
                            'status_validasi' => 'approved',
                            'status' => 'selesai',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                            'komentar_validasi' => 'Auto-approved: Low value routine procedure'
                        ]);
                        $affected++;
                    }
                    break;
                    
                case 'approve_routine':
                    $routineProcedures = ['Konsultasi Dokter Umum', 'Pemeriksaan Tekanan Darah'];
                    $records = Tindakan::where('status_validasi', 'pending')
                        ->whereHas('jenisTindakan', function ($query) use ($routineProcedures) {
                            $query->whereIn('nama', $routineProcedures);
                        })
                        ->where('tarif', '<', 200000)
                        ->get();
                    
                    foreach ($records as $record) {
                        $record->update([
                            'status_validasi' => 'approved',
                            'status' => 'selesai',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                            'komentar_validasi' => 'Auto-approved: Routine procedure'
                        ]);
                        $affected++;
                    }
                    break;
                    
                case 'flag_high_value':
                    $records = Tindakan::where('status_validasi', 'pending')
                        ->where('tarif', '>', 1000000)
                        ->get();
                    
                    foreach ($records as $record) {
                        $currentComment = $record->komentar_validasi ?? '';
                        $flagNote = '🚩 FLAGGED: High value procedure requires manual review';
                        
                        $record->update([
                            'komentar_validasi' => $currentComment ? "{$currentComment}\n{$flagNote}" : $flagNote,
                        ]);
                        $affected++;
                    }
                    break;
            }

            Notification::make()
                ->title('⚡ Quick Action Complete')
                ->body("Action completed successfully. {$affected} records affected.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Quick Action Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function exportCurrentView(string $format): void
    {
        // Export functionality placeholder
        Notification::make()
            ->title('📤 Export Started')
            ->body("Exporting current view to {$format} format. You will be notified when ready.")
            ->info()
            ->send();
    }
}