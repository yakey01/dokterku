<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tindakan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class ValidationCenterComponent extends Component
{
    use WithPagination;

    public string $activeTab = 'pending';
    public string $search = '';
    public string $statusFilter = 'all';
    public int $perPage = 10;

    protected $listeners = ['refreshValidation' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->statusFilter = $tab;
        $this->resetPage();
    }

    public function getValidationStatsProperty(): array
    {
        return Cache::remember('validation_stats_live', now()->addMinutes(2), function () {
            return [
                'all' => Tindakan::count(),
                'pending' => Tindakan::where('status_validasi', 'pending')->count(),
                'approved' => Tindakan::where('status_validasi', 'disetujui')->count(),
                'rejected' => Tindakan::where('status_validasi', 'ditolak')->count(),
                'today_approved' => Tindakan::where('status_validasi', 'disetujui')
                    ->whereDate('validated_at', today())
                    ->count(),
                'high_value_pending' => Tindakan::where('status_validasi', 'pending')
                    ->where('tarif', '>', 500000)
                    ->count(),
            ];
        });
    }

    public function getValidationDataProperty()
    {
        $query = Tindakan::with(['jenisTindakan', 'pasien', 'dokter', 'validatedBy'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->whereHas('jenisTindakan', function (Builder $subQ) {
                        $subQ->where('nama', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('pasien', function (Builder $subQ) {
                        $subQ->where('nama', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when($this->statusFilter !== 'all', function (Builder $query) {
                $query->where('status_validasi', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($this->perPage);
    }

    public function getRecentActivityProperty(): array
    {
        return Cache::remember('recent_validation_activity', now()->addMinutes(1), function () {
            return Tindakan::with(['jenisTindakan', 'pasien', 'validatedBy'])
                ->whereNotNull('validated_at')
                ->latest('validated_at')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'procedure' => $item->jenisTindakan->nama ?? 'Unknown',
                        'patient' => $item->pasien->nama ?? 'Unknown',
                        'amount' => $item->tarif,
                        'status' => $item->status_validasi,
                        'validator' => $item->validatedBy->name ?? 'System',
                        'date' => $item->validated_at,
                    ];
                })
                ->toArray();
        });
    }

    public function quickApprove(int $tindakanId)
    {
        try {
            $tindakan = Tindakan::find($tindakanId);
            if ($tindakan && $tindakan->status_validasi === 'pending') {
                $tindakan->update([
                    'status_validasi' => 'disetujui',
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                    'komentar_validasi' => 'Quick approved via validation center',
                ]);
                
                session()->flash('message', 'Tindakan berhasil disetujui');
                $this->dispatch('refreshValidation');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    public function quickReject(int $tindakanId)
    {
        try {
            $tindakan = Tindakan::find($tindakanId);
            if ($tindakan && $tindakan->status_validasi === 'pending') {
                $tindakan->update([
                    'status_validasi' => 'ditolak',
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                    'komentar_validasi' => 'Quick rejected via validation center',
                ]);
                
                session()->flash('message', 'Tindakan berhasil ditolak');
                $this->dispatch('refreshValidation');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.validation-center-component');
    }
}