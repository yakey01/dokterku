<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Pasien;
use App\Models\Tindakan;

class ScanQRPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Scan QR';
    protected static ?string $title = 'Scan QR Code';
    protected static ?string $slug = 'scan-qr';
    protected static ?int $navigationSort = 13;
    protected static string $view = 'filament.petugas.pages.scan-qr';

    public ?array $data = [];
    public $scannedData = '';
    public $scannedPatient = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scanner QR Code')
                    ->description('Scan QR code untuk akses cepat ke data pasien')
                    ->schema([
                        TextInput::make('scannedData')
                            ->label('Data QR Code')
                            ->placeholder('Scan QR code atau ketik manual')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->processScannedData()),
                        Action::make('scan_qr')
                            ->label('Scan QR Code')
                            ->icon('heroicon-o-qr-code')
                            ->color('primary')
                            ->action('openScanner'),
                    ])
                    ->collapsible(false),

                Section::make('Data Pasien')
                    ->description('Informasi pasien dari QR code')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama_pasien')
                                    ->label('Nama Pasien')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('no_rm')
                                    ->label('No. RM')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('umur')
                                    ->label('Umur')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),
                    ])
                    ->visible(fn () => $this->scannedPatient !== null)
                    ->collapsible(),

                Section::make('Aksi Cepat')
                    ->description('Pilih aksi yang akan dilakukan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Action::make('view_patient')
                                    ->label('Lihat Detail Pasien')
                                    ->icon('heroicon-o-eye')
                                    ->color('info')
                                    ->visible(fn () => $this->scannedPatient !== null)
                                    ->action('viewPatientDetail'),
                                Action::make('add_medical_action')
                                    ->label('Tambah Tindakan')
                                    ->icon('heroicon-o-plus')
                                    ->color('success')
                                    ->visible(fn () => $this->scannedPatient !== null)
                                    ->action('addMedicalAction'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Action::make('view_history')
                                    ->label('Riwayat Tindakan')
                                    ->icon('heroicon-o-clock')
                                    ->color('warning')
                                    ->visible(fn () => $this->scannedPatient !== null)
                                    ->action('viewMedicalHistory'),
                                Action::make('edit_patient')
                                    ->label('Edit Data Pasien')
                                    ->icon('heroicon-o-pencil')
                                    ->color('primary')
                                    ->visible(fn () => $this->scannedPatient !== null)
                                    ->action('editPatientData'),
                            ]),
                    ])
                    ->visible(fn () => $this->scannedPatient !== null)
                    ->collapsible(),

                Section::make('Manual Input')
                    ->description('Input data manual jika scan tidak berhasil')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('manual_no_rm')
                                    ->label('No. RM Manual')
                                    ->placeholder('Masukkan No. RM')
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->searchByRM()),
                                TextInput::make('manual_nama')
                                    ->label('Nama Pasien Manual')
                                    ->placeholder('Masukkan nama pasien')
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->searchByName()),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public function processScannedData(): void
    {
        if (empty($this->scannedData)) {
            $this->scannedPatient = null;
            return;
        }

        // Try to find patient by various identifiers
        $patient = $this->findPatient($this->scannedData);
        
        if ($patient) {
            $this->scannedPatient = $patient;
            $this->loadPatientData($patient);
        } else {
            Notification::make()
                ->title('Pasien tidak ditemukan')
                ->body('QR code tidak valid atau pasien tidak terdaftar')
                ->danger()
                ->send();
        }
    }

    private function findPatient(string $data): ?Pasien
    {
        // Try to find by RM number
        $patient = Pasien::where('no_rm', $data)->first();
        if ($patient) return $patient;

        // Try to find by name
        $patient = Pasien::where('nama', 'LIKE', "%{$data}%")->first();
        if ($patient) return $patient;

        // Try to find by phone number
        $patient = Pasien::where('no_telepon', $data)->first();
        if ($patient) return $patient;

        return null;
    }

    private function loadPatientData(Pasien $patient): void
    {
        $this->data['nama_pasien'] = $patient->nama;
        $this->data['no_rm'] = $patient->no_rm;
        $this->data['jenis_kelamin'] = $patient->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
        $this->data['umur'] = $patient->umur ?? 'N/A';
        $this->data['alamat'] = $patient->alamat ?? 'N/A';
    }

    public function searchByRM(): void
    {
        if (empty($this->data['manual_no_rm'])) return;
        
        $patient = Pasien::where('no_rm', $this->data['manual_no_rm'])->first();
        if ($patient) {
            $this->scannedPatient = $patient;
            $this->loadPatientData($patient);
        }
    }

    public function searchByName(): void
    {
        if (empty($this->data['manual_nama'])) return;
        
        $patient = Pasien::where('nama', 'LIKE', "%{$this->data['manual_nama']}%")->first();
        if ($patient) {
            $this->scannedPatient = $patient;
            $this->loadPatientData($patient);
        }
    }

    public function openScanner(): void
    {
        // This would integrate with a QR scanner library
        Notification::make()
            ->title('Scanner QR')
            ->body('Fitur scanner QR akan diintegrasikan dengan library scanner')
            ->info()
            ->send();
    }

    public function viewPatientDetail(): void
    {
        if ($this->scannedPatient) {
            $this->redirect("/petugas/pasien/{$this->scannedPatient->id}");
        }
    }

    public function addMedicalAction(): void
    {
        if ($this->scannedPatient) {
            $this->redirect("/petugas/input-tindakan?pasien_id={$this->scannedPatient->id}");
        }
    }

    public function viewMedicalHistory(): void
    {
        if ($this->scannedPatient) {
            $this->redirect("/petugas/timeline-tindakan?pasien_id={$this->scannedPatient->id}");
        }
    }

    public function editPatientData(): void
    {
        if ($this->scannedPatient) {
            $this->redirect("/petugas/pasien/{$this->scannedPatient->id}/edit");
        }
    }
}
