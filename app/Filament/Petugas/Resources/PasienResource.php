<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PasienResource\Pages;
use App\Filament\Petugas\Resources\TindakanResource;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\BulkOperationService;
use App\Services\ExportImportService;
use App\Services\AdvancedSearchService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Exception;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Enums\FontWeight;

class PasienResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationGroup = 'Manajemen Pasien';
    
    protected static ?string $navigationLabel = 'Input Pasien';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Pasien';
    
    protected static ?string $pluralModelLabel = 'Input Pasien';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    public static function canDeleteAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Identitas Pasien')
                    ->description('Masukkan data lengkap pasien. Field dengan tanda (*) wajib diisi.')
                    ->extraAttributes(['class' => 'glass-card animate-slide-up'])
                    ->schema([
                        Forms\Components\TextInput::make('no_rekam_medis')
                            ->label('No. Rekam Medis')
                            ->maxLength(20)
                            ->placeholder('Otomatis di-generate jika kosong')
                            ->helperText('Nomor rekam medis akan dibuat otomatis jika tidak diisi')
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-identification')
                            ->validationMessages([
                                'unique' => 'Nomor rekam medis sudah digunakan.',
                                'max' => 'Nomor rekam medis maksimal 20 karakter.',
                            ]),
                        
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap *')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap pasien')
                            ->prefixIcon('heroicon-o-user')
                            ->autocomplete('name')
                            ->validationMessages([
                                'required' => 'Nama lengkap wajib diisi.',
                                'max' => 'Nama lengkap maksimal 255 karakter.',
                            ]),
                        
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir *')
                            ->required()
                            ->maxDate(now())
                            ->placeholder('Pilih tanggal lahir')
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->displayFormat('d/m/Y')
                            ->helperText('Format: dd/mm/yyyy')
                            ->validationMessages([
                                'required' => 'Tanggal lahir wajib diisi.',
                                'date' => 'Format tanggal tidak valid.',
                                'before_or_equal' => 'Tanggal lahir tidak boleh lebih dari hari ini.',
                            ]),
                        
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin *')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->placeholder('Pilih jenis kelamin')
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Jenis kelamin wajib dipilih.',
                                'in' => 'Pilihan jenis kelamin tidak valid.',
                            ]),
                        
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->persistCollapsed(false),
                
                
                Forms\Components\Section::make('Informasi Personal')
                    ->description('Informasi tambahan tentang pasien.')
                    ->extraAttributes(['class' => 'glass-card animate-slide-up'])
                    ->schema([
                        Forms\Components\TextInput::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->maxLength(255)
                            ->placeholder('Contoh: Pegawai Swasta, Wiraswasta, Mahasiswa')
                            ->prefixIcon('heroicon-o-briefcase'),
                        
                        Forms\Components\Select::make('status_pernikahan')
                            ->label('Status Pernikahan')
                            ->options([
                                'belum_menikah' => 'Belum Menikah',
                                'menikah' => 'Menikah',
                                'janda' => 'Janda',
                                'duda' => 'Duda',
                            ])
                            ->placeholder('Pilih status pernikahan')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Sistem Manajemen Data Pasien')
            ->headerActions([
                // Header Widget for Quick Stats
                Tables\Actions\Action::make('patient_stats')
                    ->label('📊 Statistik Pasien')
                    ->color('info')
                    ->disabled()
                    ->extraAttributes(['class' => 'elegant-dark-header-widget'])
                    ->modalContent(view('filament.widgets.patient-stats-summary')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('no_rekam_medis')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-identification')
                    ->copyable()
                    ->copyMessage('Nomor rekam medis disalin!')
                    ->weight('bold')
                    ->extraAttributes(['class' => 'elegant-dark-text-primary']),
                
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->limit(30)
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->weight('medium')
                    ->extraAttributes(['class' => 'elegant-dark-text-primary']),
                
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tgl. Lahir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->description(fn (Pasien $record): string => $record->umur ? $record->umur . ' tahun' : '')
                    ->extraAttributes(['class' => 'elegant-dark-text-secondary']),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => '👨 Laki-laki',
                        'P' => '👩 Perempuan',
                        'Laki-laki' => '👨 Laki-laki',
                        'Perempuan' => '👩 Perempuan',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'success',
                        'Laki-laki' => 'info',
                        'Perempuan' => 'success',
                        default => 'gray',
                    })
                    ->extraAttributes(['class' => 'elegant-dark-badge']),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Verifikasi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Menunggu Verifikasi',
                        'verified' => '✅ Terverifikasi',
                        'rejected' => '❌ Ditolak',
                        default => '⏳ Menunggu Verifikasi',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'verified' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->extraAttributes(['class' => 'elegant-dark-badge']),
                
                
                Tables\Columns\TextColumn::make('pekerjaan')
                    ->label('Pekerjaan')
                    ->limit(20)
                    ->searchable()
                    ->icon('heroicon-o-briefcase')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('status_pernikahan')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state = null): string => match ($state) {
                        'belum_menikah' => 'Belum Menikah',
                        'menikah' => 'Menikah',
                        'janda' => 'Janda',
                        'duda' => 'Duda',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (string $state = null): string => match ($state) {
                        'belum_menikah' => 'gray',
                        'menikah' => 'success',
                        'janda' => 'warning',
                        'duda' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->extraAttributes(['class' => 'elegant-dark-text-muted'])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Verifikasi')
                    ->options([
                        'pending' => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ]),
                
                Tables\Filters\Filter::make('tanggal_lahir')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Lahir Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Lahir Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_lahir', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_lahir', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('status_pernikahan')
                    ->label('Status Pernikahan')
                    ->options([
                        'belum_menikah' => 'Belum Menikah',
                        'menikah' => 'Menikah',
                        'janda' => 'Janda',
                        'duda' => 'Duda',
                    ]),
            ])
            ->headerActions([
                // Primary Create Action - ALWAYS VISIBLE
                CreateAction::make()
                    ->label('Input Pasien Baru')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->size('lg')
                    ->visible(fn (): bool => true),
                    
                // Advanced Search Action
                Tables\Actions\Action::make('advanced_search')
                    ->label('Pencarian Lanjutan')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalHeading('Pencarian Lanjutan Pasien')
                    ->modalDescription('Gunakan filter lanjutan untuk mencari pasien dengan kriteria spesifik.')
                    ->modalSubmitActionLabel('Cari')
                    ->form([
                        Repeater::make('filters')
                            ->label('Filter Pencarian')
                            ->schema([
                                Select::make('field')
                                    ->label('Field')
                                    ->options([
                                        'nama' => 'Nama',
                                        'no_rekam_medis' => 'No. Rekam Medis',
                                        'jenis_kelamin' => 'Jenis Kelamin',
                                        'tanggal_lahir' => 'Tanggal Lahir',
                                        'status_pernikahan' => 'Status Pernikahan',
                                        'pekerjaan' => 'Pekerjaan',
                                    ])
                                    ->required(),
                                Select::make('operator')
                                    ->label('Operator')
                                    ->options([
                                        'equals' => 'Sama dengan',
                                        'contains' => 'Mengandung',
                                        'starts_with' => 'Dimulai dengan',
                                        'ends_with' => 'Diakhiri dengan',
                                        'not_equals' => 'Tidak sama dengan',
                                        'greater_than' => 'Lebih besar dari',
                                        'less_than' => 'Lebih kecil dari',
                                        'date_equals' => 'Tanggal sama dengan',
                                        'date_before' => 'Tanggal sebelum',
                                        'date_after' => 'Tanggal setelah',
                                        'is_null' => 'Kosong',
                                        'is_not_null' => 'Tidak kosong',
                                    ])
                                    ->required(),
                                TextInput::make('value')
                                    ->label('Nilai')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Filter')
                            ->collapsed(),
                        TextInput::make('search_term')
                            ->label('Kata Kunci Pencarian')
                            ->placeholder('Cari di semua field...')
                            ->helperText('Pencarian akan dilakukan di semua field yang dapat dicari'),
                        TextInput::make('save_search_name')
                            ->label('Simpan Pencarian (Opsional)')
                            ->placeholder('Nama pencarian untuk disimpan')
                            ->helperText('Masukkan nama untuk menyimpan pencarian ini'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $searchService = new AdvancedSearchService();
                            
                            $searchParams = [
                                'filters' => $data['filters'] ?? [],
                                'search' => $data['search_term'] ?? '',
                                'per_page' => 25,
                                'page' => 1
                            ];
                            
                            // Save search if name provided
                            if (!empty($data['save_search_name'])) {
                                $searchService->saveSearch(
                                    Pasien::class,
                                    $searchParams,
                                    $data['save_search_name']
                                );
                            }
                            
                            // Apply search (this would typically modify the table query)
                            $results = $searchService->search(Pasien::class, $searchParams);
                            
                            Notification::make()
                                ->title('Pencarian Berhasil')
                                ->body("Ditemukan {$results['pagination']['total']} pasien")
                                ->success()
                                ->send();
                                
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Pencarian Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                // Saved Searches Action
                Tables\Actions\Action::make('saved_searches')
                    ->label('Pencarian Tersimpan')
                    ->icon('heroicon-o-bookmark')
                    ->color('success')
                    ->modalHeading('Pencarian Tersimpan')
                    ->modalDescription('Akses pencarian yang telah disimpan sebelumnya.')
                    ->modalSubmitActionLabel('Gunakan')
                    ->form([
                        Select::make('saved_search_id')
                            ->label('Pilih Pencarian')
                            ->options(function () {
                                try {
                                    $searchService = new AdvancedSearchService();
                                    $savedSearches = $searchService->getSavedSearches(Pasien::class);
                                    
                                    return collect($savedSearches['data'])->pluck('name', 'id');
                                } catch (Exception $e) {
                                    return [];
                                }
                            })
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $searchService = new AdvancedSearchService();
                            $savedSearches = $searchService->getSavedSearches(Pasien::class);
                            
                            $selectedSearch = collect($savedSearches['data'])
                                ->firstWhere('id', $data['saved_search_id']);
                            
                            if ($selectedSearch) {
                                $results = $searchService->search(Pasien::class, $selectedSearch['search_params']);
                                
                                Notification::make()
                                    ->title('Pencarian Berhasil')
                                    ->body("Menggunakan pencarian '{$selectedSearch['name']}' - Ditemukan {$results['pagination']['total']} pasien")
                                    ->success()
                                    ->send();
                            }
                            
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Pencarian Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                // Import Data Action
                Tables\Actions\Action::make('import_data')
                    ->label('Import Data')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalHeading('Import Data Pasien')
                    ->modalDescription('Upload file Excel, CSV, atau JSON untuk mengimpor data pasien.')
                    ->modalSubmitActionLabel('Import')
                    ->form([
                        FileUpload::make('import_file')
                            ->label('File Import')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/json'])
                            ->required()
                            ->helperText('Format yang didukung: Excel (.xlsx), CSV (.csv), JSON (.json)'),
                        Select::make('import_options')
                            ->label('Opsi Import')
                            ->options([
                                'skip_duplicates' => 'Lewati data duplikat',
                                'update_existing' => 'Update data yang sudah ada',
                                'validate_only' => 'Validasi saja (tidak import)',
                            ])
                            ->multiple()
                            ->helperText('Pilih opsi import yang diinginkan'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $importService = new ExportImportService();
                            
                            if (!empty($data['import_file'])) {
                                $filePath = $data['import_file'];
                                $options = [
                                    'skip_duplicates' => in_array('skip_duplicates', $data['import_options'] ?? []),
                                    'validate' => !in_array('validate_only', $data['import_options'] ?? []),
                                ];
                                
                                if (in_array('validate_only', $data['import_options'] ?? [])) {
                                    $result = $importService->validateImportData(Pasien::class, $filePath);
                                    
                                    Notification::make()
                                        ->title('Validasi Selesai')
                                        ->body("Valid: {$result['valid']}, Invalid: {$result['invalid']}, Total: {$result['total']}")
                                        ->success()
                                        ->send();
                                } else {
                                    $result = $importService->importData(Pasien::class, $filePath, $options);
                                    
                                    Notification::make()
                                        ->title('Import Berhasil')
                                        ->body("Berhasil import {$result['imported']} pasien, Error: {$result['errors']}")
                                        ->success()
                                        ->send();
                                }
                            }
                            
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Import Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('create_tindakan')
                        ->label('🏥 Buat Tindakan')
                        ->icon('heroicon-o-plus-circle')
                        ->color('primary')
                        ->extraAttributes(['class' => 'elegant-dark-action-btn view'])
                        ->url(fn (Pasien $record): string => TindakanResource::getUrl('create', [], panel: 'petugas') . '?pasien_id=' . $record->id)
                        ->tooltip('Buat tindakan medis untuk pasien ini'),
                    
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat Detail')
                        ->extraAttributes(['class' => 'elegant-dark-action-btn view'])
                        ->tooltip('Lihat detail lengkap pasien'),
                        
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit Data')
                        ->extraAttributes(['class' => 'elegant-dark-action-btn edit'])
                        ->tooltip('Edit informasi pasien'),
                        
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->extraAttributes(['class' => 'elegant-dark-action-btn delete'])
                        ->tooltip('Hapus data pasien'),
                        
                    Tables\Actions\Action::make('copy_rekam_medis')
                        ->label('📋 Salin No. RM')
                        ->icon('heroicon-o-clipboard')
                        ->color('info')
                        ->extraAttributes(['class' => 'elegant-dark-action-btn'])
                        ->action(function (Pasien $record) {
                            \Filament\Notifications\Notification::make()
                                ->title('Nomor Rekam Medis Disalin')
                                ->body("No. RM: {$record->no_rekam_medis}")
                                ->success()
                                ->send();
                        })
                        ->tooltip('Salin nomor rekam medis'),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->extraAttributes(['class' => 'elegant-dark-action-group'])
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::check() && Auth::user()->can('delete_any_pasien')),
                    
                    // Export selected patients
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Terpilih')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Export Data Pasien')
                        ->modalDescription('Export data pasien yang dipilih ke format file.')
                        ->modalSubmitActionLabel('Export')
                        ->form([
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'json' => 'JSON (.json)',
                                ])
                                ->default('xlsx')
                                ->required(),
                            Toggle::make('include_relations')
                                ->label('Sertakan Data Terkait')
                                ->helperText('Sertakan data tindakan dan relasi lainnya')
                                ->default(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $exportService = new ExportImportService();
                                $ids = $records->pluck('id')->toArray();
                                
                                // Create temporary filtered export
                                $result = $exportService->exportData(
                                    Pasien::class,
                                    [
                                        'format' => $data['format'],
                                        'include_relations' => $data['include_relations'],
                                        'filters' => ['id' => $ids]
                                    ]
                                );
                                
                                // Trigger download
                                return response()->download(
                                    storage_path('app/' . $result['file_path']),
                                    $result['file_name']
                                );
                                
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Export Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Bulk update status
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Update Status Pasien')
                        ->modalDescription('Update status untuk pasien yang dipilih.')
                        ->modalSubmitActionLabel('Update')
                        ->form([
                            Select::make('status_pernikahan')
                                ->label('Status Pernikahan')
                                ->options([
                                    'belum_menikah' => 'Belum Menikah',
                                    'menikah' => 'Menikah',
                                    'janda' => 'Janda',
                                    'duda' => 'Duda',
                                ])
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $updateData = array_filter($data);
                                if (empty($updateData)) {
                                    Notification::make()
                                        ->title('Tidak Ada Data')
                                        ->body('Pilih minimal satu field untuk diupdate.')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                                
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) use ($updateData) {
                                    return array_merge(['id' => $record->id], $updateData);
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    Pasien::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('Update Berhasil')
                                    ->body("Berhasil update {$result['updated']} pasien.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Update Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Bulk assign to user
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Assign ke User')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Assign Pasien ke User')
                        ->modalDescription('Assign pasien yang dipilih ke user tertentu.')
                        ->modalSubmitActionLabel('Assign')
                        ->form([
                            Select::make('user_id')
                                ->label('User')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'petugas');
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) use ($data) {
                                    return [
                                        'id' => $record->id,
                                        'input_by' => $data['user_id']
                                    ];
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    Pasien::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('Assign Berhasil')
                                    ->body("Berhasil assign {$result['updated']} pasien.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Assign Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->deferLoading()
            ->extremePaginationLinks()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateHeading('🏥 Belum ada data pasien')
            ->emptyStateDescription('Mulai dengan menambahkan data pasien baru menggunakan tombol "Input Pasien Baru" di atas.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('📋 Data Identitas Pasien')
                    ->description('Informasi dasar pasien yang terdaftar di sistem')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('no_rekam_medis')
                                    ->label('No. Rekam Medis')
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->copyMessage('Nomor rekam medis disalin!')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                                    
                                Components\TextEntry::make('nama')
                                    ->label('Nama Lengkap')
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold)
                                    ->size(Components\TextEntry\TextEntrySize::Large),
                                    
                                Components\TextEntry::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'L' => '👨 Laki-laki',
                                        'P' => '👩 Perempuan',
                                        'Laki-laki' => '👨 Laki-laki',
                                        'Perempuan' => '👩 Perempuan',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'L', 'Laki-laki' => 'info',
                                        'P', 'Perempuan' => 'success',
                                        default => 'gray',
                                    }),
                            ]),
                            
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('tanggal_lahir')
                                    ->label('Tanggal Lahir')
                                    ->date('d F Y')
                                    ->icon('heroicon-o-calendar-days')
                                    ->suffix(fn (Pasien $record): string => ' (' . ($record->umur ?: '0') . ' tahun)'),
                                    
                                Components\TextEntry::make('status')
                                    ->label('Status Verifikasi')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => '⏳ Menunggu Verifikasi',
                                        'verified' => '✅ Terverifikasi',
                                        'rejected' => '❌ Ditolak',
                                        default => '⏳ Menunggu Verifikasi',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'verified' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning',
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
                    
                Components\Section::make('👥 Informasi Personal')
                    ->description('Data tambahan dan informasi personal pasien')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('pekerjaan')
                                    ->label('Pekerjaan')
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('Tidak diisi'),
                                    
                                Components\TextEntry::make('status_pernikahan')
                                    ->label('Status Pernikahan')
                                    ->formatStateUsing(fn (string $state = null): string => match ($state) {
                                        'belum_menikah' => 'Belum Menikah',
                                        'menikah' => 'Menikah',
                                        'janda' => 'Janda',
                                        'duda' => 'Duda',
                                        default => 'Tidak diisi',
                                    })
                                    ->badge()
                                    ->color(fn (string $state = null): string => match ($state) {
                                        'belum_menikah' => 'gray',
                                        'menikah' => 'success',
                                        'janda' => 'warning',
                                        'duda' => 'info',
                                        default => 'gray',
                                    })
                                    ->icon('heroicon-o-heart'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Components\Section::make('📅 Informasi Sistem')
                    ->description('Data teknis dan riwayat sistem')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('Terdaftar Pada')
                                    ->dateTime('d F Y, H:i')
                                    ->icon('heroicon-o-calendar')
                                    ->since()
                                    ->color('success'),
                                    
                                Components\TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d F Y, H:i')
                                    ->icon('heroicon-o-arrow-path')
                                    ->since()
                                    ->color('warning'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inputBy'])
            ->orderBy('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasiens::route('/'),
            'create' => Pages\CreatePasien::route('/create'),
            'view' => Pages\ViewPasien::route('/{record}'),
            'edit' => Pages\EditPasien::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $panel = $panel ?? 'petugas';
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}