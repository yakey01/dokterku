<?php

namespace App\Filament\Petugas\Resources\PasienResource\Pages;

use App\Filament\Petugas\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use App\Services\ExportImportService;

class ListPasiens extends ListRecords
{
    protected static string $resource = PasienResource::class;

    protected static ?string $title = '🏥 Manajemen Data Pasien';
    
    public function getSubheading(): ?string
    {
        return 'Kelola data pasien dengan sistem yang mudah dan efisien. Gunakan tab untuk navigasi berdasarkan status verifikasi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Input Pasien Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->size('lg')
                ->extraAttributes(['class' => 'elegant-dark-action-btn'])
                ->visible(fn (): bool => true),
                
            Actions\Action::make('export_all')
                ->label('📊 Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->size('lg')
                ->extraAttributes(['class' => 'elegant-dark-action-btn'])
                ->modalHeading('📊 Export Data Pasien')
                ->modalDescription('Pilih format export dan opsi yang diinginkan.')
                ->modalSubmitActionLabel('Export')
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format File')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                            'json' => 'JSON (.json)',
                        ])
                        ->default('xlsx')
                        ->required(),
                        
                    \Filament\Forms\Components\Toggle::make('include_relations')
                        ->label('Sertakan Data Terkait')
                        ->helperText('Sertakan data tindakan dan relasi lainnya')
                        ->default(false),
                        
                    \Filament\Forms\Components\Select::make('date_range')
                        ->label('Rentang Waktu')
                        ->options([
                            'all' => 'Semua Data',
                            'today' => 'Hari Ini',
                            'this_week' => 'Minggu Ini',
                            'this_month' => 'Bulan Ini',
                            'this_year' => 'Tahun Ini',
                        ])
                        ->default('all')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $exportService = new ExportImportService();
                        
                        $options = [
                            'format' => $data['format'],
                            'include_relations' => $data['include_relations'],
                            'date_range' => $data['date_range'],
                        ];
                        
                        $result = $exportService->exportData(
                            \App\Models\Pasien::class,
                            $options
                        );
                        
                        Notification::make()
                            ->title('✅ Export Berhasil')
                            ->body("Data pasien berhasil diekspor ke {$data['format']}. Total: {$result['total_records']} record.")
                            ->success()
                            ->send();
                            
                        return response()->download(
                            storage_path('app/' . $result['file_path']),
                            $result['file_name']
                        );
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Export Gagal')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('refresh_data')
                ->label('🔄 Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->size('lg')
                ->extraAttributes(['class' => 'elegant-dark-action-btn'])
                ->action(function () {
                    $this->resetTable();
                    \Filament\Notifications\Notification::make()
                        ->title('Data Direfresh')
                        ->body('Data pasien telah diperbarui.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Input Pasien Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->size('lg'),
        ];
    }

    public function getTabs(): array
    {
        $totalPasien = $this->getModel()::count();
        
        return [
            'semua' => Tab::make('📊 Semua Pasien')
                ->icon('heroicon-o-users')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->badge(fn () => $totalPasien)
                ->badgeColor('info'),
            
            'pending' => Tab::make('⏳ Menunggu Verifikasi')
                ->icon('heroicon-o-clock')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'verified' => Tab::make('✅ Terverifikasi')
                ->icon('heroicon-o-check-circle')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'verified'))
                ->badge(fn () => $this->getModel()::where('status', 'verified')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('❌ Ditolak')
                ->icon('heroicon-o-x-circle')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => $this->getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger'),
                
            'recent' => Tab::make('🆕 Terbaru (7 Hari)')
                ->icon('heroicon-o-calendar-days')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                ->badge(fn () => $this->getModel()::where('created_at', '>=', now()->subDays(7))->count())
                ->badgeColor('info'),
                
            'male' => Tab::make('👨 Laki-laki')
                ->icon('heroicon-o-user')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_kelamin', 'L'))
                ->badge(fn () => $this->getModel()::where('jenis_kelamin', 'L')->count())
                ->badgeColor('info'),
                
            'female' => Tab::make('👩 Perempuan')
                ->icon('heroicon-o-user')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_kelamin', 'P'))
                ->badge(fn () => $this->getModel()::where('jenis_kelamin', 'P')->count())
                ->badgeColor('success'),
                
            'today' => Tab::make('📅 Hari Ini')
                ->icon('heroicon-o-calendar')
                ->extraAttributes(['class' => 'elegant-dark-tab'])
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => $this->getModel()::whereDate('created_at', today())->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-user-plus';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum ada data pasien';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Mulai dengan menambahkan data pasien baru menggunakan tombol "Input Pasien Baru" di atas.';
    }

}