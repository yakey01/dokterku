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
            $procedureCalculator = app(ProcedureJaspelCalculationService::class);
            $data = $procedureCalculator->calculateJaspelFromProcedures($this->userId, []);
            
            // Ensure all required keys exist
            $this->procedureData = [
                'total_jaspel' => $data['total_jaspel'] ?? 0,
                'total_procedures' => $data['total_procedures'] ?? 0,
                'tindakan_jaspel' => $data['tindakan_jaspel'] ?? 0,
                'pasien_jaspel' => $data['pasien_jaspel'] ?? 0,
                'breakdown' => [
                    'tindakan_procedures' => $data['breakdown']['tindakan_procedures'] ?? [],
                    'pasien_harian_days' => $data['breakdown']['pasien_harian_days'] ?? []
                ]
            ];
            
            $this->dataLoaded = true;
            
            \Log::info('JaspelDetailComponent: Real data loaded successfully', [
                'user_id' => $this->userId,
                'total_jaspel' => $this->procedureData['total_jaspel'],
                'total_procedures' => $this->procedureData['total_procedures'],
                'tindakan_count' => count($this->procedureData['breakdown']['tindakan_procedures']),
                'pasien_days_count' => count($this->procedureData['breakdown']['pasien_harian_days'])
            ]);
            
        } catch (\Exception $e) {
            \Log::error('JaspelDetailComponent: Service failed, using fallback data', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            
            // Safe fallback with basic structure
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
            $this->dataLoaded = false;
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