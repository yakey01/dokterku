<?php

namespace App\Livewire;

use Livewire\Component;

class MapComponent extends Component
{
    public $latitude;
    public $longitude;
    public $statePath;
    
    public function mount($statePath = null)
    {
        $this->statePath = $statePath ?? 'default';
        $this->latitude = old($this->statePath . '.latitude', -6.2088) ?: -6.2088;
        $this->longitude = old($this->statePath . '.longitude', 106.8456) ?: 106.8456;
    }

    public function render()
    {
        return view('livewire.map-component');
    }
    
    public function updateCoordinates($lat, $lng)
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        
        // Dispatch event to parent form
        $this->dispatch('coordinatesUpdated', [
            'latitude' => $lat,
            'longitude' => $lng,
            'statePath' => $this->statePath
        ]);
    }
}