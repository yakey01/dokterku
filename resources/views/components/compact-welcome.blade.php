{{-- Compact Welcome Message for Filament Integration --}}
@props([
    'user' => null
])

@php
    $user = $user ?? auth()->user();
    
    // Time-based greeting logic
    $hour = (int) date('H');
    $greeting = match (true) {
        $hour >= 5 && $hour < 12 => 'Selamat pagi',
        $hour >= 12 && $hour < 15 => 'Selamat siang', 
        $hour >= 15 && $hour < 18 => 'Selamat sore',
        $hour >= 18 || $hour < 5 => 'Selamat malam',
        default => 'Selamat datang'
    };
    
    $firstName = explode(' ', $user->name)[0];
    $currentTime = date('H:i');
@endphp

{{-- Compact welcome bar yang terintegrasi dengan topbar existing --}}
<div class="filament-compact-welcome" 
     style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        backdrop-filter: blur(10px) saturate(120%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.5rem 1rem;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        color: #ffffff;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
     "
     onmouseover="this.style.backdropFilter='blur(12px) saturate(130%)'; this.style.borderBottomColor='rgba(255,255,255,0.15)';"
     onmouseout="this.style.backdropFilter='blur(10px) saturate(120%)'; this.style.borderBottomColor='rgba(255,255,255,0.1)';"
>
    {{-- Left: Greeting --}}
    <div class="flex items-center space-x-2">
        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold text-white"
             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            {{ strtoupper(substr($firstName, 0, 1)) }}
        </div>
        <span class="font-medium" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
            {{ $greeting }}, {{ $firstName }}! 
            @if($hour >= 5 && $hour < 12) 🌅
            @elseif($hour >= 12 && $hour < 15) ☀️
            @elseif($hour >= 15 && $hour < 18) 🌤️
            @else 🌙
            @endif
        </span>
    </div>
    
    {{-- Right: Time and role --}}
    <div class="flex items-center space-x-3 text-xs">
        <span class="text-white/70">
            {{ ucfirst($user->role?->name ?? 'User') }}
        </span>
        <div class="text-center">
            <div class="font-mono font-semibold text-white/90" id="compact-time">{{ $currentTime }}</div>
            <div class="text-white/60" style="font-size: 0.6875rem;">WIB</div>
        </div>
    </div>
</div>

{{-- Real-time clock update --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateCompactTime() {
        const timeElement = document.getElementById('compact-time');
        if (timeElement) {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0');
            timeElement.textContent = timeString;
        }
    }
    
    updateCompactTime();
    setInterval(updateCompactTime, 60000);
});
</script>

<style>
/* Responsive adjustments */
@media (max-width: 640px) {
    .filament-compact-welcome {
        padding: 0.375rem 0.75rem;
        font-size: 0.8125rem;
    }
    
    .filament-compact-welcome .space-x-2 {
        gap: 0.375rem;
    }
    
    .filament-compact-welcome .space-x-3 {
        gap: 0.5rem;
    }
}
</style>