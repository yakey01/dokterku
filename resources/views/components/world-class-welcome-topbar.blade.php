{{-- World-Class Welcome Topbar Component --}}
@props([
    'user' => null,
    'showAvatar' => true,
    'showDate' => true,
    'showRole' => true,
    'compact' => false
])

@php
    $user = $user ?? auth()->user();
    
    // Time-based greeting logic
    $hour = (int) date('H');
    $greeting = match (true) {
        $hour >= 5 && $hour < 12 => '🌅 Selamat pagi',
        $hour >= 12 && $hour < 15 => '☀️ Selamat siang', 
        $hour >= 15 && $hour < 18 => '🌤️ Selamat sore',
        $hour >= 18 || $hour < 5 => '🌙 Selamat malam',
        default => '👋 Selamat datang kembali'
    };
    
    // Get first name only for greeting
    $firstName = explode(' ', $user->name)[0];
    
    // Format current date in Indonesian
    $currentDate = \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y');
    
    // Get user role display name
    $roleDisplay = match ($user->role?->name) {
        'admin' => 'Administrator',
        'manajer' => 'Manajer Klinik',
        'bendahara' => 'Bendahara',
        'dokter' => 'Dokter',
        'petugas' => 'Petugas Klinik',
        'paramedis' => 'Paramedis',
        default => $user->role?->name ? ucfirst($user->role->name) : 'User'
    };
@endphp

<div class="world-class-welcome-topbar" 
     style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0%, rgba(255, 255, 255, 0.05) 100%);
        backdrop-filter: blur(20px) saturate(150%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: 1rem;
        box-shadow: 
            0 8px 32px -8px rgba(0, 0, 0, 0.3),
            0 2px 8px -2px rgba(0, 0, 0, 0.2),
            inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
     "
     onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 48px -12px rgba(0, 0, 0, 0.4), 0 4px 12px -4px rgba(0, 0, 0, 0.3), inset 0 1px 0 0 rgba(255, 255, 255, 0.1)';"
     onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 8px 32px -8px rgba(0, 0, 0, 0.3), 0 2px 8px -2px rgba(0, 0, 0, 0.2), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)';"
>
    <div class="flex items-center justify-between">
        {{-- Welcome Section --}}
        <div class="flex items-center space-x-4">
            @if($showAvatar)
                <div class="relative">
                    <div class="w-12 h-12 rounded-full overflow-hidden ring-2 ring-white/20 shadow-lg"
                         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" 
                                 alt="{{ $user->name }}"
                                 class="w-full h-full object-cover"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center text-white font-semibold text-lg">
                                {{ strtoupper(substr($firstName, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    {{-- Online Status Indicator --}}
                    <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-green-500 border-2 border-white rounded-full shadow-sm"></div>
                </div>
            @endif
            
            <div class="space-y-1">
                {{-- Main Greeting --}}
                <h1 class="text-xl font-semibold text-white leading-tight" 
                    style="text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);">
                    {{ $greeting }}, {{ $firstName }}! ✨
                </h1>
                
                {{-- Subtitle with Role and Date --}}
                <div class="flex items-center space-x-2 text-sm text-white/80">
                    @if($showRole)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                              style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px);">
                            {{ $roleDisplay }}
                        </span>
                    @endif
                    
                    @if($showDate)
                        <span class="text-white/70">•</span>
                        <span class="font-medium">{{ $currentDate }}</span>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Right Section - Quick Actions --}}
        <div class="flex items-center space-x-3">
            {{-- Current Time --}}
            <div class="hidden md:flex flex-col items-end text-right text-sm">
                <span class="text-white/80 font-medium" id="current-time">
                    {{ date('H:i') }}
                </span>
                <span class="text-white/60 text-xs">
                    WIB
                </span>
            </div>
            
            {{-- Quick Stats Badge (Optional) --}}
            <div class="hidden lg:flex items-center space-x-2">
                <div class="px-3 py-2 rounded-lg text-center"
                     style="background: rgba(34, 197, 94, 0.1); backdrop-filter: blur(8px); border: 1px solid rgba(34, 197, 94, 0.2);">
                    <div class="text-green-300 text-xs font-medium">Hari Ini</div>
                    <div class="text-white text-lg font-bold">{{ date('d') }}</div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Motivational Message (Random) --}}
    @php
        $motivationalMessages = [
            "Semoga hari ini penuh berkah dan produktivitas! 💪",
            "Mari berikan pelayanan terbaik untuk pasien kita! 🏥",
            "Kesuksesan dimulai dari langkah kecil hari ini! ⭐",
            "Tetap semangat dan jaga kesehatan! 🌟",
            "Setiap pasien yang dilayani adalah amanah! 💙"
        ];
        $randomMessage = $motivationalMessages[array_rand($motivationalMessages)];
    @endphp
    
    <div class="mt-3 pt-3 border-t border-white/10">
        <p class="text-sm text-white/70 italic">
            {{ $randomMessage }}
        </p>
    </div>
</div>

{{-- Real-time Clock Update Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0');
            timeElement.textContent = timeString;
        }
    }
    
    // Update time immediately and then every minute
    updateTime();
    setInterval(updateTime, 60000);
});
</script>

<style>
.world-class-welcome-topbar {
    position: relative;
    overflow: hidden;
}

.world-class-welcome-topbar::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    transition: left 1.5s ease;
}

.world-class-welcome-topbar:hover::before {
    left: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .world-class-welcome-topbar {
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
    }
    
    .world-class-welcome-topbar h1 {
        font-size: 1.125rem;
    }
    
    .world-class-welcome-topbar .space-x-4 {
        gap: 0.75rem;
    }
}
</style>