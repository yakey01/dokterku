<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Filament\Notifications\Notification;
use App\Models\DataExport;
use App\Services\DataExportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DownloadCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    
    protected static string $view = 'filament.pages.download-center';
    
    protected static ?string $navigationLabel = 'Download Center';
    
    protected static ?string $title = 'Download Center';
    
    protected static ?string $navigationGroup = '📊 Laporan & Analitik';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = 'download-center';
    
    // Page properties
    public $recentDownloads = [];
    public $downloadStats = [];
    public $quickExports = [];
    
    public function mount(): void
    {
        $this->loadDownloadData();
    }
    
    public function loadDownloadData(): void
    {
        $this->recentDownloads = DataExport::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $this->downloadStats = [
            'total_downloads' => DataExport::where('user_id', Auth::id())->count(),
            'this_month' => DataExport::where('user_id', Auth::id())
                ->whereMonth('created_at', now()->month)
                ->count(),
            'total_size' => $this->formatBytes($this->getTotalDownloadSize()),
            'last_download' => optional(DataExport::where('user_id', Auth::id())
                ->latest()
                ->first())->created_at?->diffForHumans() ?? 'Belum ada',
        ];
        
        $this->quickExports = $this->getQuickExportOptions();
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('quick_patient_export')
                ->label('📋 Export Pasien')
                ->icon('heroicon-o-users')
                ->color('info')  
                ->requiresConfirmation()
                ->modalHeading('Export Data Pasien')
                ->modalDescription('Export data pasien dengan filter tanggal.')
                ->form([
                    Section::make('Filter Export')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('date_from')
                                        ->label('Dari Tanggal')
                                        ->default(now()->startOfMonth())
                                        ->native(false),
                                    DatePicker::make('date_to')
                                        ->label('Sampai Tanggal')
                                        ->default(now())
                                        ->native(false),
                                ]),
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'pdf' => 'PDF Report (.pdf)',
                                ])
                                ->default('xlsx')
                                ->required()
                                ->native(false),
                        ])
                ])
                ->action(function (array $data) {
                    return $this->exportPatientData($data);
                }),
                
            Action::make('quick_treatment_export')
                ->label('💉 Export Tindakan')
                ->icon('heroicon-o-hand-raised')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Export Data Tindakan')
                ->modalDescription('Export data tindakan medis dengan filter tanggal.')
                ->form([
                    Section::make('Filter Export')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('date_from')
                                        ->label('Dari Tanggal')
                                        ->default(now()->startOfMonth())
                                        ->native(false),
                                    DatePicker::make('date_to')
                                        ->label('Sampai Tanggal')
                                        ->default(now())
                                        ->native(false),
                                ]),
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)', 
                                    'csv' => 'CSV (.csv)',
                                    'pdf' => 'PDF Report (.pdf)',
                                ])
                                ->default('xlsx')
                                ->required()
                                ->native(false),
                        ])
                ])  
                ->action(function (array $data) {
                    return $this->exportTreatmentData($data);
                }),
                
            Action::make('quick_financial_export')
                ->label('💰 Export Keuangan')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Export Data Keuangan')
                ->modalDescription('Export laporan keuangan harian.')
                ->form([
                    Section::make('Filter Export')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('date_from')
                                        ->label('Dari Tanggal')
                                        ->default(now()->startOfMonth())
                                        ->native(false),
                                    DatePicker::make('date_to')
                                        ->label('Sampai Tanggal')
                                        ->default(now())
                                        ->native(false),
                                ]),
                            Select::make('type')
                                ->label('Jenis Laporan')
                                ->options([
                                    'income' => 'Pendapatan Saja',
                                    'expenses' => 'Pengeluaran Saja', 
                                    'both' => 'Pendapatan & Pengeluaran',
                                ])
                                ->default('both')
                                ->required()
                                ->native(false),
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'pdf' => 'PDF Report (.pdf)',
                                ])
                                ->default('xlsx')
                                ->required()
                                ->native(false),
                        ])
                ])
                ->action(function (array $data) {
                    return $this->exportFinancialData($data);
                }),
                
            Action::make('custom_export')
                ->label('🔧 Export Kustom')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Export Data Kustom')
                ->modalDescription('Buat export kustom dengan opsi lanjutan.')
                ->modalWidth('2xl')
                ->form([
                    Section::make('Pilih Data')
                        ->schema([
                            Select::make('model')
                                ->label('Jenis Data')
                                ->options([
                                    'App\\Models\\Pasien' => '👥 Data Pasien',
                                    'App\\Models\\Tindakan' => '💉 Data Tindakan',
                                    'App\\Models\\PendapatanHarian' => '💰 Pendapatan Harian',
                                    'App\\Models\\PengeluaranHarian' => '💸 Pengeluaran Harian',
                                    'App\\Models\\JumlahPasienHarian' => '📊 Jumlah Pasien Harian',
                                ])
                                ->required()
                                ->native(false),
                        ]),
                    Section::make('Filter & Format')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('date_from')
                                        ->label('Dari Tanggal')
                                        ->native(false),
                                    DatePicker::make('date_to')
                                        ->label('Sampai Tanggal')
                                        ->native(false),
                                ]),
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'json' => 'JSON (.json)',
                                    'pdf' => 'PDF Report (.pdf)',
                                ])
                                ->default('xlsx')
                                ->required()
                                ->native(false),
                        ]),
                    Section::make('Opsi Lanjutan')
                        ->schema([
                            Toggle::make('include_relations')
                                ->label('Sertakan Data Terkait')
                                ->default(true),
                            Toggle::make('compress_file')
                                ->label('Kompres File (ZIP)')
                                ->default(false),
                            Toggle::make('email_notification')
                                ->label('Kirim Notifikasi Email')
                                ->default(false),
                        ])
                        ->collapsible()
                        ->collapsed(true),
                ])
                ->action(function (array $data) {
                    return $this->executeCustomExport($data);
                }),
        ];
    }
    
    public function downloadFile(int $exportId): \Symfony\Component\HttpFoundation\BinaryFileResponse|null
    {
        $export = DataExport::where('id', $exportId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$export || !$export->file_path) {
            Notification::make()
                ->title('❌ File Tidak Ditemukan')
                ->body('File export tidak ditemukan atau sudah kedaluwarsa.')
                ->danger()
                ->send();
                
            return null;
        }
        
        $filePath = storage_path('app/' . $export->file_path);
        
        if (!file_exists($filePath)) {
            Notification::make()
                ->title('❌ File Tidak Ada')
                ->body('File fisik tidak ditemukan di storage.')
                ->danger()
                ->send();
                
            return null;
        }
        
        return response()->download($filePath, $export->file_name);
    }
    
    protected function exportPatientData(array $data)
    {
        $exportService = app(DataExportService::class);
        
        try {
            $result = $exportService->exportData(\App\Models\Pasien::class, [
                'format' => $data['format'],
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'include_relations' => true,
                'user_id' => Auth::id(),
                'export_type' => 'quick_patients',
            ]);
            
            Notification::make()
                ->title('✅ Export Berhasil')
                ->body('Data pasien berhasil diexport.')
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
    }
    
    protected function exportTreatmentData(array $data)
    {
        $exportService = app(DataExportService::class);
        
        try {
            $result = $exportService->exportData(\App\Models\Tindakan::class, [
                'format' => $data['format'],
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'include_relations' => true,
                'user_id' => Auth::id(),
                'export_type' => 'quick_treatments',
            ]);
            
            Notification::make()
                ->title('✅ Export Berhasil')
                ->body('Data tindakan berhasil diexport.')
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
    }
    
    protected function exportFinancialData(array $data)
    {
        $exportService = app(DataExportService::class);
        
        try {
            $models = [];
            
            if ($data['type'] === 'income' || $data['type'] === 'both') {
                $models[] = \App\Models\PendapatanHarian::class;
            }
            
            if ($data['type'] === 'expenses' || $data['type'] === 'both') {
                $models[] = \App\Models\PengeluaranHarian::class;
            }
            
            $results = [];
            foreach ($models as $model) {
                $result = $exportService->exportData($model, [
                    'format' => $data['format'],
                    'date_from' => $data['date_from'],
                    'date_to' => $data['date_to'],
                    'include_relations' => true,
                    'user_id' => Auth::id(),
                    'export_type' => 'quick_financial',
                ]);
                $results[] = $result;
            }
            
            Notification::make()
                ->title('✅ Export Berhasil')
                ->body('Data keuangan berhasil diexport.')
                ->success()
                ->send();
                
            // Return first result for now - in future, could create a ZIP
            return response()->download(
                storage_path('app/' . $results[0]['file_path']),
                $results[0]['file_name']
            );
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function executeCustomExport(array $data)
    {
        $exportService = app(DataExportService::class);
        
        try {
            $result = $exportService->exportData($data['model'], [
                'format' => $data['format'],
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'include_relations' => $data['include_relations'] ?? true,
                'compress_file' => $data['compress_file'] ?? false,
                'user_id' => Auth::id(),
                'export_type' => 'custom',
            ]);
            
            if ($data['email_notification'] ?? false) {
                // TODO: Send email notification
            }
            
            Notification::make()
                ->title('✅ Export Berhasil')
                ->body('Export kustom berhasil dibuat.')
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
    }
    
    protected function getTotalDownloadSize(): int
    {
        return DataExport::where('user_id', Auth::id())
            ->whereNotNull('file_size')
            ->sum('file_size');
    }
    
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    protected function getQuickExportOptions(): array
    {
        return [
            [
                'title' => 'Data Pasien Hari Ini',
                'description' => 'Export semua pasien yang terdaftar hari ini',
                'icon' => '👥',
                'action' => 'exportTodayPatients',
            ],
            [
                'title' => 'Laporan Keuangan Bulan Ini', 
                'description' => 'Export laporan pendapatan dan pengeluaran bulan ini',
                'icon' => '💰',
                'action' => 'exportMonthlyFinancial',
            ],
            [
                'title' => 'Tindakan Minggu Ini',
                'description' => 'Export semua tindakan medis minggu ini',
                'icon' => '💉',
                'action' => 'exportWeeklyTreatments',
            ],
        ];
    }
}