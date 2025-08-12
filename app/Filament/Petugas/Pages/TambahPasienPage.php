<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Pasien;
use Illuminate\Support\Str;

class TambahPasienPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Tambah Pasien';
    protected static ?string $title = 'Tambah Pasien Baru';
    protected static ?string $slug = 'tambah-pasien';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.petugas.pages.tambah-pasien';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar')
                    ->description('Data identitas pasien')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('no_rm')
                                    ->label('Nomor Rekam Medis')
                                    ->required()
                                    ->unique('pasien', 'no_rm')
                                    ->maxLength(50),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'L' => 'Laki-laki',
                                        'P' => 'Perempuan',
                                    ])
                                    ->required(),
                                DatePicker::make('tanggal_lahir')
                                    ->label('Tanggal Lahir')
                                    ->required()
                                    ->maxDate(now()),
                                TextInput::make('umur')
                                    ->label('Umur')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Kontak & Alamat')
                    ->description('Informasi kontak dan alamat pasien')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('no_telepon')
                                    ->label('Nomor Telepon')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                            ]),
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('kota')
                                    ->label('Kota')
                                    ->maxLength(100),
                                TextInput::make('provinsi')
                                    ->label('Provinsi')
                                    ->maxLength(100),
                                TextInput::make('kode_pos')
                                    ->label('Kode Pos')
                                    ->maxLength(10),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Informasi Medis')
                    ->description('Data medis dasar pasien')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('golongan_darah')
                                    ->label('Golongan Darah')
                                    ->maxLength(5),
                                TextInput::make('tinggi_badan')
                                    ->label('Tinggi Badan (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(300),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('berat_badan')
                                    ->label('Berat Badan (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(500),
                                TextInput::make('riwayat_alergi')
                                    ->label('Riwayat Alergi')
                                    ->maxLength(255),
                            ]),
                        Textarea::make('catatan_medis')
                            ->label('Catatan Medis')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->collapsible(),

                Section::make('Dokumen')
                    ->description('Upload dokumen pendukung')
                    ->schema([
                        FileUpload::make('foto')
                            ->label('Foto Pasien')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048),
                        FileUpload::make('dokumen')
                            ->label('Dokumen Pendukung')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(5120),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pasien')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-o-check'),
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
        
        // Generate RM number if not provided
        if (empty($data['no_rm'])) {
            $data['no_rm'] = 'RM-' . date('Y') . '-' . Str::random(6);
        }

        // Calculate age
        if ($data['tanggal_lahir']) {
            $data['umur'] = now()->diffInYears($data['tanggal_lahir']);
        }

        $pasien = Pasien::create($data);

        Notification::make()
            ->title('Pasien berhasil ditambahkan')
            ->body("Pasien {$pasien->nama} telah berhasil ditambahkan dengan nomor RM: {$pasien->no_rm}")
            ->success()
            ->send();

        $this->redirect('/petugas/daftar-pasien');
    }
}
