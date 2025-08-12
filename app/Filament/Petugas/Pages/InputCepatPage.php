<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Income;
use App\Models\Expense;

class InputCepatPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Input Cepat';
    protected static ?string $title = 'Input Data Cepat';
    protected static ?string $slug = 'input-cepat';
    protected static ?int $navigationSort = 12;
    protected static string $view = 'filament.petugas.pages.input-cepat';

    public ?array $data = [];
    public $inputType = 'pasien';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Jenis Input')
                    ->description('Pilih jenis data yang akan diinput')
                    ->schema([
                        Select::make('inputType')
                            ->label('Tipe Input')
                            ->options([
                                'pasien' => 'Data Pasien',
                                'tindakan' => 'Tindakan Medis',
                                'pendapatan' => 'Pendapatan',
                                'pengeluaran' => 'Pengeluaran',
                            ])
                            ->default('pasien')
                            ->reactive()
                            ->required(),
                    ])
                    ->collapsible(false),

                // Patient Quick Input
                Section::make('Data Pasien Cepat')
                    ->description('Input data pasien secara cepat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama')
                                    ->label('Nama Pasien')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'pasien'),
                                TextInput::make('no_telepon')
                                    ->label('No. Telepon')
                                    ->tel()
                                    ->visible(fn () => $this->inputType === 'pasien'),
                            ]),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->rows(2)
                            ->visible(fn () => $this->inputType === 'pasien'),
                    ])
                    ->visible(fn () => $this->inputType === 'pasien')
                    ->collapsible(),

                // Medical Action Quick Input
                Section::make('Tindakan Medis Cepat')
                    ->description('Input tindakan medis secara cepat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('pasien_id')
                                    ->label('Pilih Pasien')
                                    ->options(Pasien::pluck('nama', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'tindakan'),
                                TextInput::make('jenis_tindakan')
                                    ->label('Jenis Tindakan')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'tindakan'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('tanggal_tindakan')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'tindakan'),
                                TextInput::make('biaya')
                                    ->label('Biaya')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'tindakan'),
                            ]),
                        Textarea::make('catatan_tindakan')
                            ->label('Catatan')
                            ->rows(2)
                            ->visible(fn () => $this->inputType === 'tindakan'),
                    ])
                    ->visible(fn () => $this->inputType === 'tindakan')
                    ->collapsible(),

                // Income Quick Input
                Section::make('Pendapatan Cepat')
                    ->description('Input pendapatan secara cepat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('kategori_pendapatan')
                                    ->label('Kategori')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'pendapatan'),
                                TextInput::make('jumlah_pendapatan')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'pendapatan'),
                            ]),
                        Textarea::make('deskripsi_pendapatan')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->required()
                            ->visible(fn () => $this->inputType === 'pendapatan'),
                    ])
                    ->visible(fn () => $this->inputType === 'pendapatan')
                    ->collapsible(),

                // Expense Quick Input
                Section::make('Pengeluaran Cepat')
                    ->description('Input pengeluaran secara cepat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('kategori_pengeluaran')
                                    ->label('Kategori')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'pengeluaran'),
                                TextInput::make('jumlah_pengeluaran')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->visible(fn () => $this->inputType === 'pengeluaran'),
                            ]),
                        Textarea::make('deskripsi_pengeluaran')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->required()
                            ->visible(fn () => $this->inputType === 'pengeluaran'),
                    ])
                    ->visible(fn () => $this->inputType === 'pengeluaran')
                    ->collapsible(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Cepat')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-o-check'),
            Action::make('reset')
                ->label('Reset')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->form->fill()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        switch ($this->inputType) {
            case 'pasien':
                $this->savePatient($data);
                break;
            case 'tindakan':
                $this->saveMedicalAction($data);
                break;
            case 'pendapatan':
                $this->saveIncome($data);
                break;
            case 'pengeluaran':
                $this->saveExpense($data);
                break;
        }
    }

    private function savePatient(array $data): void
    {
        $pasien = Pasien::create([
            'nama' => $data['nama'],
            'no_telepon' => $data['no_telepon'],
            'alamat' => $data['alamat'],
            'no_rm' => 'RM-' . date('Y') . '-' . \Str::random(6),
        ]);

        Notification::make()
            ->title('Pasien berhasil ditambahkan')
            ->body("Pasien {$pasien->nama} telah berhasil ditambahkan")
            ->success()
            ->send();
    }

    private function saveMedicalAction(array $data): void
    {
        $tindakan = Tindakan::create([
            'pasien_id' => $data['pasien_id'],
            'jenis_tindakan' => $data['jenis_tindakan'],
            'tanggal_tindakan' => $data['tanggal_tindakan'],
            'biaya' => $data['biaya'],
            'catatan' => $data['catatan_tindakan'],
            'status' => 'jadwal',
        ]);

        Notification::make()
            ->title('Tindakan berhasil ditambahkan')
            ->body("Tindakan medis telah berhasil disimpan")
            ->success()
            ->send();
    }

    private function saveIncome(array $data): void
    {
        $income = Income::create([
            'kategori' => $data['kategori_pendapatan'],
            'deskripsi' => $data['deskripsi_pendapatan'],
            'jumlah' => $data['jumlah_pendapatan'],
            'tanggal' => now(),
            'status' => 'diterima',
        ]);

        Notification::make()
            ->title('Pendapatan berhasil ditambahkan')
            ->body("Pendapatan telah berhasil disimpan")
            ->success()
            ->send();
    }

    private function saveExpense(array $data): void
    {
        $expense = Expense::create([
            'kategori' => $data['kategori_pengeluaran'],
            'deskripsi' => $data['deskripsi_pengeluaran'],
            'jumlah' => $data['jumlah_pengeluaran'],
            'tanggal' => now(),
            'status' => 'pending',
        ]);

        Notification::make()
            ->title('Pengeluaran berhasil ditambahkan')
            ->body("Pengeluaran telah berhasil disimpan")
            ->success()
            ->send();
    }
}
