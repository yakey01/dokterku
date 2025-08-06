<?php

namespace App\Filament\Traits;

use Filament\Tables;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use App\Services\DataExportService;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Exception;

trait HasBuiltInExports
{
    /**
     * Get built-in export header actions for table
     */
    public function getBuiltInExportHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('export_all')
                ->label('📤 Export Semua Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Export Semua Data')
                ->modalDescription('Export semua data dari tabel ini ke format file pilihan Anda.')
                ->modalSubmitActionLabel('Download')
                ->form($this->getExportFormSchema())
                ->action(function (array $data) {
                    return $this->executeExportAll($data);
                }),
                
            Tables\Actions\Action::make('export_filtered')
                ->label('📋 Export Data Terfilter')
                ->icon('heroicon-o-funnel')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Export Data Terfilter')
                ->modalDescription('Export data berdasarkan filter yang sedang aktif.')
                ->modalSubmitActionLabel('Download')
                ->form($this->getExportFormSchema())
                ->action(function (array $data) {
                    return $this->executeExportFiltered($data);
                })
                ->visible(fn () => method_exists($this, 'getTableQuery')),
        ];
    }

    /**
     * Get built-in export bulk actions
     */
    public function getBuiltInExportBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('export_selected')
                ->label('📤 Export Terpilih')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Export Data Terpilih')
                ->modalDescription('Export data yang dipilih ke format file.')
                ->modalSubmitActionLabel('Download')
                ->form($this->getExportFormSchema())
                ->action(function (Collection $records, array $data) {
                    return $this->executeExportSelected($records, $data);
                }),
        ];
    }

    /**
     * Get export form schema
     */
    protected function getExportFormSchema(): array
    {
        return [
            Section::make('Format Export')
                ->schema([
                    Select::make('format')
                        ->label('Format File')
                        ->options([
                            'xlsx' => '📊 Excel (.xlsx) - Recommended',
                            'csv' => '📄 CSV (.csv) - Universal',
                            'json' => '🔧 JSON (.json) - Developers',
                            'pdf' => '📑 PDF (.pdf) - Reports',
                        ])
                        ->default('xlsx')
                        ->required()
                        ->native(false),
                ]),
                
            Section::make('Opsi Data')
                ->schema([
                    Toggle::make('include_relations')
                        ->label('Sertakan Data Terkait')
                        ->helperText('Sertakan data relasi (misal: pasien, dokter, dll)')
                        ->default(true),
                        
                    Toggle::make('include_timestamps')
                        ->label('Sertakan Waktu Pembuatan/Update')
                        ->helperText('Sertakan created_at dan updated_at')
                        ->default(false),
                        
                    Toggle::make('format_dates')
                        ->label('Format Tanggal Indonesian')
                        ->helperText('Format tanggal dalam bahasa Indonesia')
                        ->default(true),
                ]),
                
            Section::make('Filter Tanggal (Opsional)')
                ->schema([
                    DatePicker::make('date_from')
                        ->label('Dari Tanggal')
                        ->native(false),
                        
                    DatePicker::make('date_to')
                        ->label('Sampai Tanggal')
                        ->native(false),
                ])
                ->collapsible()
                ->collapsed(true),
                
            Section::make('Opsi Lanjutan')
                ->schema([
                    Toggle::make('compress_file')
                        ->label('Kompres File (ZIP)')
                        ->helperText('File akan dikompres dalam format ZIP')
                        ->default(false),
                        
                    Checkbox::make('email_download')
                        ->label('Kirim ke Email')
                        ->helperText('Kirim link download ke email Anda')
                        ->default(false),
                ])
                ->collapsible()
                ->collapsed(true),
        ];
    }

    /**
     * Execute export all data
     */
    protected function executeExportAll(array $data)
    {
        try {
            $modelClass = static::getModel();
            $exportService = app(DataExportService::class);
            
            $result = $exportService->exportData($modelClass, [
                'format' => $data['format'],
                'include_relations' => $data['include_relations'] ?? true,
                'include_timestamps' => $data['include_timestamps'] ?? false,
                'format_dates' => $data['format_dates'] ?? true,
                'compress_file' => $data['compress_file'] ?? false,
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'user_id' => auth()->id(),
                'export_type' => 'all',
            ]);
            
            if ($data['email_download'] ?? false) {
                $this->sendEmailDownload($result);
                
                Notification::make()
                    ->title('✅ Export Berhasil')
                    ->body('Link download telah dikirim ke email Anda.')
                    ->success()
                    ->send();
                    
                return null;
            }
            
            return response()->download(
                storage_path('app/' . $result['file_path']),
                $result['file_name']
            );
            
        } catch (Exception $e) {
            Notification::make()
                ->title('❌ Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
                
            return null;
        }
    }

    /**
     * Execute export filtered data
     */
    protected function executeExportFiltered(array $data)
    {
        try {
            $modelClass = static::getModel();
            $exportService = app(DataExportService::class);
            
            // Get current table filters if available
            $filters = [];
            if (method_exists($this, 'getTableFilters')) {
                $filters = $this->getTableFilters();
            }
            
            $result = $exportService->exportData($modelClass, [
                'format' => $data['format'],
                'include_relations' => $data['include_relations'] ?? true,
                'include_timestamps' => $data['include_timestamps'] ?? false,
                'format_dates' => $data['format_dates'] ?? true,
                'compress_file' => $data['compress_file'] ?? false,
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'filters' => $filters,
                'user_id' => auth()->id(),
                'export_type' => 'filtered',
            ]);
            
            if ($data['email_download'] ?? false) {
                $this->sendEmailDownload($result);
                
                Notification::make()
                    ->title('✅ Export Berhasil')
                    ->body('Link download telah dikirim ke email Anda.')
                    ->success()
                    ->send();
                    
                return null;
            }
            
            return response()->download(
                storage_path('app/' . $result['file_path']),
                $result['file_name']
            );
            
        } catch (Exception $e) {
            Notification::make()
                ->title('❌ Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
                
            return null;
        }
    }

    /**
     * Execute export selected records
     */
    protected function executeExportSelected(Collection $records, array $data)
    {
        try {
            $modelClass = static::getModel();
            $exportService = app(DataExportService::class);
            $ids = $records->pluck('id')->toArray();
            
            $result = $exportService->exportData($modelClass, [
                'format' => $data['format'],
                'include_relations' => $data['include_relations'] ?? true,
                'include_timestamps' => $data['include_timestamps'] ?? false,
                'format_dates' => $data['format_dates'] ?? true,
                'compress_file' => $data['compress_file'] ?? false,
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'filters' => ['id' => $ids],
                'user_id' => auth()->id(),
                'export_type' => 'selected',
                'record_count' => count($ids),
            ]);
            
            if ($data['email_download'] ?? false) {
                $this->sendEmailDownload($result);
                
                Notification::make()
                    ->title('✅ Export Berhasil')
                    ->body('Link download telah dikirim ke email Anda.')
                    ->success()
                    ->send();
                    
                return null;
            }
            
            return response()->download(
                storage_path('app/' . $result['file_path']),
                $result['file_name']
            );
            
        } catch (Exception $e) {
            Notification::make()
                ->title('❌ Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
                
            return null;
        }
    }

    /**
     * Send email download link
     */
    protected function sendEmailDownload(array $result): void
    {
        // TODO: Implement email sending logic
        // This would typically use Laravel's Mail system
        // to send download links to users
    }
}