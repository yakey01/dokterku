<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Tindakan;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\JenisTindakan;

class InputTindakanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus';
    protected static ?string $navigationLabel = 'Input Tindakan';
    protected static ?string $title = 'Input Tindakan Medis';
    protected static ?string $slug = 'input-tindakan';
    protected static ?int $navigationSort = 7;
    protected static string $view = 'filament.petugas.pages.input-tindakan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pasien')
                    ->description('Pilih pasien yang akan ditindak')
                    ->schema([
                        Select::make('pasien_id')
                            ->label('Pilih Pasien')
                            ->options(Pasien::pluck('nama', 'id'))
                            ->searchable()
                            ->required()
                            ->placeholder('Cari nama pasien...'),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('no_rm')
                                    ->label('No. RM')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('umur')
                                    ->label('Umur')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Detail Tindakan')
                    ->description('Informasi lengkap tindakan medis')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('jenis_tindakan_id')
                                    ->label('Jenis Tindakan')
                                    ->options(JenisTindakan::pluck('nama', 'id'))
                                    ->searchable()
                                    ->required(),
                                Select::make('dokter_id')
                                    ->label('Dokter Penanggung Jawab')
                                    ->options(Dokter::pluck('nama', 'id'))
                                    ->searchable()
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('tanggal_tindakan')
                                    ->label('Tanggal Tindakan')
                                    ->required()
                                    ->default(now()),
                                TimePicker::make('waktu_tindakan')
                                    ->label('Waktu Tindakan')
                                    ->required()
                                    ->default(now()),
                                TextInput::make('durasi')
                                    ->label('Durasi (menit)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(480),
                            ]),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi Tindakan')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                        Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->collapsible(),

                Section::make('Biaya & Pembayaran')
                    ->description('Informasi biaya tindakan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('biaya_dasar')
                                    ->label('Biaya Dasar')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                TextInput::make('biaya_tambahan')
                                    ->label('Biaya Tambahan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ]),
                        TextInput::make('total_biaya')
                                    ->label('Total Biaya')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),
                        Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer Bank',
                                'kartu' => 'Kartu Debit/Kredit',
                                'bpjs' => 'BPJS',
                                'lainnya' => 'Lainnya',
                            ])
                            ->required(),
                    ])
                    ->collapsible(),

                Section::make('Obat & Alat')
                    ->description('Obat dan alat yang digunakan')
                    ->schema([
                        Repeater::make('obat_alat')
                            ->label('Obat & Alat')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('nama')
                                            ->label('Nama Obat/Alat')
                                            ->required(),
                                        TextInput::make('jumlah')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->required(),
                                        TextInput::make('satuan')
                                            ->label('Satuan')
                                            ->required(),
                                    ]),
                                TextInput::make('catatan')
                                    ->label('Catatan')
                                    ->maxLength(200),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['nama'] ?? null),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Tindakan')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-o-check'),
            Action::make('save_draft')
                ->label('Simpan Draft')
                ->color('gray')
                ->icon('heroicon-o-document')
                ->action('saveDraft'),
            Action::make('reset')
                ->label('Reset Form')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->form->fill()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // Calculate total cost
        $data['total_biaya'] = ($data['biaya_dasar'] ?? 0) + ($data['biaya_tambahan'] ?? 0);
        
        // Set status
        $data['status'] = 'jadwal';
        
        $tindakan = Tindakan::create($data);

        Notification::make()
            ->title('Tindakan berhasil disimpan')
            ->body("Tindakan untuk pasien telah berhasil disimpan dengan ID: {$tindakan->id}")
            ->success()
            ->send();

        $this->redirect('/petugas/timeline-tindakan');
    }

    public function saveDraft(): void
    {
        $data = $this->form->getState();
        
        // Calculate total cost
        $data['total_biaya'] = ($data['biaya_dasar'] ?? 0) + ($data['biaya_tambahan'] ?? 0);
        
        // Set status as draft
        $data['status'] = 'draft';
        
        $tindakan = Tindakan::create($data);

        Notification::make()
            ->title('Draft tindakan berhasil disimpan')
            ->body("Draft tindakan telah disimpan dan dapat diedit nanti")
            ->success()
            ->send();

        $this->redirect('/petugas/timeline-tindakan');
    }
}
