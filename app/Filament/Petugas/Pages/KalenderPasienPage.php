<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use App\Models\Pasien;
use App\Models\Tindakan;
use Carbon\Carbon;

class KalenderPasienPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Kalender Pasien';
    protected static ?string $title = 'Kalender Pasien';
    protected static ?string $slug = 'kalender-pasien';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.petugas.pages.kalender-pasien';

    public ?array $data = [];
    public $selectedDate;
    public $patientCount = 0;
    public $scheduledActions = [];
    public $completedActions = [];

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('selectedDate')
                            ->label('Pilih Tanggal')
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->loadData()),
                        Select::make('view_type')
                            ->label('Tampilan')
                            ->options([
                                'daily' => 'Harian',
                                'weekly' => 'Mingguan',
                                'monthly' => 'Bulanan',
                            ])
                            ->default('daily')
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->loadData()),
                        Select::make('filter_status')
                            ->label('Filter Status')
                            ->options([
                                'all' => 'Semua Status',
                                'scheduled' => 'Terjadwal',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('all')
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->loadData()),
                    ]),
            ]);
    }

    public function loadData(): void
    {
        $date = Carbon::parse($this->selectedDate);
        
        // Count patients for the selected date
        $this->patientCount = Pasien::whereDate('created_at', $date)->count();
        
        // Get scheduled actions
        $this->scheduledActions = Tindakan::whereDate('tanggal_tindakan', $date)
            ->with(['pasien', 'dokter'])
            ->where('status', 'jadwal')
            ->orderBy('waktu_tindakan')
            ->get();
            
        // Get completed actions
        $this->completedActions = Tindakan::whereDate('tanggal_tindakan', $date)
            ->with(['pasien', 'dokter'])
            ->where('status', 'selesai')
            ->orderBy('waktu_tindakan')
            ->get();
    }

    public function getViewData(): array
    {
        return [
            'selectedDate' => $this->selectedDate,
            'patientCount' => $this->patientCount,
            'scheduledActions' => $this->scheduledActions,
            'completedActions' => $this->completedActions,
            'date' => Carbon::parse($this->selectedDate),
        ];
    }
}
