<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Models\JadwalJaga;
use Filament\Notifications\Notification;
use App\Events\ValidationStatusReset;
use Illuminate\Database\Eloquent\Builder;

class PetugasEditComponent extends Component
{
    public $record;
    public $tanggal;
    public $poli;
    public $shift;
    public $dokter_id;
    public $jadwal_jaga_id;
    public $jumlah_pasien_umum;
    public $jumlah_pasien_bpjs;
    public $catatan;
    
    public $availableDokters = [];
    public $availableJadwal = [];
    
    protected $listeners = ['refreshEdit' => '$refresh'];
    
    public function mount($recordId)
    {
        $this->record = JumlahPasienHarian::with(['dokter', 'jadwalJaga'])->findOrFail($recordId);
        
        // Load existing data
        $this->tanggal = $this->record->tanggal->format('Y-m-d');
        $this->poli = $this->record->poli;
        $this->shift = $this->record->shift;
        $this->dokter_id = $this->record->dokter_id;
        $this->jadwal_jaga_id = $this->record->jadwal_jaga_id;
        $this->jumlah_pasien_umum = $this->record->jumlah_pasien_umum;
        $this->jumlah_pasien_bpjs = $this->record->jumlah_pasien_bpjs;
        $this->catatan = $this->record->catatan;
        
        $this->loadDokters();
        $this->loadJadwalJaga();
    }
    
    public function updatedPoli()
    {
        $this->loadDokters();
        $this->dokter_id = null;
    }
    
    public function updatedTanggal()
    {
        $this->loadJadwalJaga();
    }
    
    public function updatedDokterId()
    {
        $this->loadJadwalJaga();
    }
    
    private function loadDokters()
    {
        $this->availableDokters = Dokter::where('aktif', true)
            ->when($this->poli, function ($query) {
                $query->where('jabatan', $this->poli === 'gigi' ? 'dokter_gigi' : 'dokter_umum');
            })
            ->orderBy('nama_lengkap')
            ->get();
    }
    
    private function loadJadwalJaga()
    {
        $this->availableJadwal = JadwalJaga::with(['shiftTemplate', 'pegawai'])
            ->when($this->tanggal, function ($query) {
                $query->where('tanggal_jaga', $this->tanggal);
            })
            ->when($this->dokter_id, function ($query) {
                $query->where('pegawai_id', $this->dokter_id);
            })
            ->get();
    }
    
    public function save()
    {
        $this->validate([
            'tanggal' => 'required|date|before_or_equal:today',
            'poli' => 'required|in:umum,gigi',
            'shift' => 'required',
            'dokter_id' => 'required|exists:dokters,id',
            'jumlah_pasien_umum' => 'required|integer|min:0|max:500',
            'jumlah_pasien_bpjs' => 'required|integer|min:0|max:500',
            'catatan' => 'nullable|string|max:500',
        ]);
        
        $data = [
            'tanggal' => $this->tanggal,
            'poli' => $this->poli,
            'shift' => $this->shift,
            'dokter_id' => $this->dokter_id,
            'jadwal_jaga_id' => $this->jadwal_jaga_id,
            'jumlah_pasien_umum' => $this->jumlah_pasien_umum,
            'jumlah_pasien_bpjs' => $this->jumlah_pasien_bpjs,
            'catatan' => $this->catatan,
        ];
        
        // Auto-calculate jaspel_rupiah
        try {
            $calculationService = app(\App\Services\Jaspel\UnifiedJaspelCalculationService::class);
            $calculation = $calculationService->calculateEstimated(
                $this->jumlah_pasien_umum,
                $this->jumlah_pasien_bpjs,
                $this->shift
            );
            
            if (!isset($calculation['error'])) {
                $data['jaspel_rupiah'] = $calculation['total'];
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to calculate jaspel during record update', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
        
        // Validation reset logic
        $isCurrentlyApproved = in_array($this->record->status_validasi, ['disetujui', 'approved']);
        if ($isCurrentlyApproved) {
            $criticalFields = ['jumlah_pasien_umum', 'jumlah_pasien_bpjs', 'jaspel_rupiah', 'dokter_id', 'tanggal', 'shift', 'poli'];
            
            $hasChanges = false;
            $changedFields = [];
            
            foreach ($criticalFields as $field) {
                if (isset($data[$field]) && $data[$field] != $this->record->{$field}) {
                    $hasChanges = true;
                    $changedFields[] = $field;
                }
            }
            
            if ($hasChanges) {
                $data['status_validasi'] = 'pending';
                $data['validasi_by'] = null;
                $data['validasi_at'] = null;
                $data['catatan_validasi'] = 'Data diubah oleh petugas - perlu validasi ulang. Fields: ' . implode(', ', $changedFields);
                
                // Fire validation reset event
                try {
                    event(new ValidationStatusReset([
                        'model_type' => 'JumlahPasienHarian',
                        'model_id' => $this->record->id,
                        'original_status' => $this->record->status_validasi,
                        'new_status' => 'pending',
                        'changed_fields' => $changedFields,
                        'edited_by' => auth()->id(),
                        'user_name' => auth()->user()?->name ?? 'System'
                    ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to fire ValidationStatusReset event', ['error' => $e->getMessage()]);
                }
                
                // Show notification
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'title' => 'Status Validasi Di-reset',
                    'message' => 'Data yang sudah disetujui telah diubah. Status dikembalikan ke "Menunggu".'
                ]);
            }
        }
        
        $this->record->update($data);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'title' => 'Data Berhasil Diupdate',
            'message' => 'Data jumlah pasien harian telah berhasil diperbarui.'
        ]);
        
        return redirect()->route('filament.petugas.resources.jumlah-pasien-harians.index');
    }
    
    public function cancel()
    {
        return redirect()->route('filament.petugas.resources.jumlah-pasien-harians.index');
    }
    
    public function render()
    {
        return view('livewire.petugas-edit-component');
    }
}