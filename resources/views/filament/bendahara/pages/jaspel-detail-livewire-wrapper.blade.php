{{-- FIXED: Direct render without Filament page wrapper to prevent conflicts --}}
<div class="filament-page">
    <livewire:jaspel-detail-component :userId="$this->userId" />
</div>