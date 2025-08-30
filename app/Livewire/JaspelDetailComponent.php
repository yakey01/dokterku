<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Services\ProcedureJaspelCalculationService;
use Filament\Notifications\Notification;

class JaspelDetailComponent extends Component
{
    public int $userId;
    public ?User $user = null;
    public array $procedureData = [];
    public bool $dataLoaded = false;
    
    protected $listeners = ['refreshData' => 'loadProcedureData'];

    public function mount(int $userId): void
    {
        $this->userId = $userId;
        $this->user = User::find($userId);
        
        if (!$this->user) {
            abort(404, 'User tidak ditemukan');
        }
        
        // Initialize with empty data structure to prevent undefined errors
        $this->procedureData = [
            'total_jaspel' => 0,
            'total_procedures' => 0,
            'tindakan_jaspel' => 0,
            'pasien_jaspel' => 0,
            'breakdown' => [
                'tindakan_procedures' => [],
                'pasien_harian_days' => []
            ]
        ];
        
        // Load data immediately in mount
        $this->loadProcedureData();
    }

    public function loadProcedureData(): void
    {
        try {
            // TEMPORARY DEBUG: Use static data to test rendering
            $this->procedureData = [
                'total_jaspel' => 1177000,
                'total_procedures' => 5,
                'tindakan_jaspel' => 64000,
                'pasien_jaspel' => 1113000,
                'breakdown' => [
                    'tindakan_procedures' => [
                        [
                            'jenis_tindakan' => 'Jahit Luka (1-4 jahitan)',
                            'tanggal' => '2025-08-19',
                            'jaspel' => 12000,
                            'tarif' => 30000
                        ],
                        [
                            'jenis_tindakan' => 'Surat Keterangan Sehat', 
                            'tanggal' => '2025-08-17',
                            'jaspel' => 52000,
                            'tarif' => 130000
                        ]
                    ],
                    'pasien_harian_days' => [
                        [
                            'tanggal' => '2025-08-21',
                            'jumlah_pasien' => 70,
                            'jaspel_rupiah' => 364000
                        ],
                        [
                            'tanggal' => '2025-08-15',
                            'jumlah_pasien' => 80,
                            'jaspel_rupiah' => 399000
                        ],
                        [
                            'tanggal' => '2025-08-23',
                            'jumlah_pasien' => 60,
                            'jaspel_rupiah' => 350000
                        ]
                    ]
                ]
            ];
            
            $this->dataLoaded = true;
            
            \Log::info('JaspelDetailComponent: Static data loaded for debugging', [
                'user_id' => $this->userId,
                'total_jaspel' => $this->procedureData['total_jaspel'],
                'data_loaded' => $this->dataLoaded
            ]);
            
        } catch (\Exception $e) {
            \Log::error('JaspelDetailComponent: Failed to load data', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function exportDetail(): void
    {
        try {
            $procedureCalculator = app(ProcedureJaspelCalculationService::class);
            $detailData = $procedureCalculator->calculateJaspelFromProcedures($this->userId, []);
            
            Notification::make()
                ->title('Export Started')
                ->body('Detail breakdown export sedang diproses...')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshCalculation(): void
    {
        try {
            // Clear related caches
            \Cache::forget("jaspel_calculation_{$this->userId}");
            
            // Reload procedure data
            $this->loadProcedureData();
            
            Notification::make()
                ->title('Calculation Refreshed')
                ->body('Jaspel dihitung ulang: Rp ' . number_format($this->procedureData['total_jaspel'], 0, ',', '.'))
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Refresh Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.jaspel-detail-component');
    }
}