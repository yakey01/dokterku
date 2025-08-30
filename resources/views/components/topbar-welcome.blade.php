{{-- Topbar Welcome Message - Integrates INSIDE Filament Topbar --}}
@props([
    'user' => null
])

@php
    $user = $user ?? auth()->user();
    
    // Only render if user exists
    if (!$user) {
        return;
    }
    
    // Time-based greeting logic
    $hour = (int) date('H');
    $greeting = match (true) {
        $hour >= 5 && $hour < 12 => 'Selamat pagi',
        $hour >= 12 && $hour < 15 => 'Selamat siang', 
        $hour >= 15 && $hour < 18 => 'Selamat sore',
        $hour >= 18 || $hour < 5 => 'Selamat malam',
        default => 'Selamat datang'
    };
    
    $firstName = explode(' ', trim($user->name))[0] ?? 'User';
    $currentTime = date('H:i');
    $emoji = match (true) {
        $hour >= 5 && $hour < 12 => '🌅',
        $hour >= 12 && $hour < 15 => '☀️',
        $hour >= 15 && $hour < 18 => '🌤️',
        default => '🌙'
    };
@endphp

{{-- Welcome message integrated into topbar --}}
<div class="topbar-welcome-integration" 
     style="
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.375rem 0.75rem;
        margin-right: 1rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        backdrop-filter: blur(8px) saturate(110%);
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #ffffff;
        font-size: 0.875rem;
        box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
     "
     onmouseover="this.style.background='linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.08) 100%)'; this.style.transform='scale(1.02)';"
     onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%)'; this.style.transform='scale(1)';"
>
    {{-- User Avatar --}}
    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold text-white"
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 1px 3px rgba(0,0,0,0.3);">
        {{ strtoupper(substr($firstName, 0, 1)) }}
    </div>
    
    {{-- Welcome Text --}}
    <div class="flex items-center gap-2">
        <span class="font-medium whitespace-nowrap" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
            {{ $greeting }}, {{ $firstName }}! {{ $emoji }}
        </span>
        
        {{-- Time Display --}}
        <div class="hidden sm:flex flex-col text-right text-xs">
            <span class="font-mono font-semibold text-white/90" id="topbar-time">{{ $currentTime }}</span>
            <span class="text-white/60" style="font-size: 0.625rem;">WIB</span>
        </div>
    </div>
</div>

{{-- Real-time clock update script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTopbarTime() {
        const timeElement = document.getElementById('topbar-time');
        if (timeElement) {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0');
            timeElement.textContent = timeString;
        }
    }
    
    updateTopbarTime();
    setInterval(updateTopbarTime, 60000);
});
</script>

<style>
/* Responsive adjustments */
@media (max-width: 640px) {
    .topbar-welcome-integration {
        font-size: 0.8125rem;
        gap: 0.5rem;
        padding: 0.25rem 0.5rem;
        margin-right: 0.5rem;
    }
    
    .topbar-welcome-integration .w-7 {
        width: 1.5rem;
        height: 1.5rem;
    }
}

/* Animation for smooth integration */
.topbar-welcome-integration {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>