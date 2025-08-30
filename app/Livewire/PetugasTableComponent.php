<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JumlahPasienHarian;
use Livewire\WithPagination;

class PetugasTableComponent extends Component
{
    use WithPagination;
    
    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'desc';
    
    protected $listeners = ['refreshTable' => '$refresh'];
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    public function render()
    {
        $data = JumlahPasienHarian::with(['dokter', 'inputBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('dokter', function ($doctorQuery) {
                        $doctorQuery->where('nama_lengkap', 'like', '%' . $this->search . '%')
                                  ->orWhere('nik', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('poli', 'like', '%' . $this->search . '%')
                    ->orWhere('shift', 'like', '%' . $this->search . '%')
                    ->orWhere('tanggal', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
            
        return view('livewire.petugas-table-component', [
            'data' => $data
        ]);
    }
}