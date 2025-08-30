{{-- Integrated Welcome Message for Existing Filament Topbar --}}
@props([
    'user' => null
])

@php
    $user = $user ?? auth()->user();
    $welcomeData = \App\Services\WelcomeGreetingService::getWelcomeData($user);
@endphp

<div class="integrated-welcome-message" 
     style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0%, rgba(255, 255, 255, 0.05) 100%);
        backdrop-filter: blur(12px) saturate(120%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0.75rem 1.5rem;
        margin: -1px -1px 1rem -1px;
        border-radius: 0 0 1rem 1rem;
        color: #ffffff;
        position: relative;
        overflow: hidden;
     ">
    
    {{-- Welcome Content --}}
    <div class="flex items-center justify-between">
        {{-- Left: Greeting --}}
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center"
                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                <span class="text-white font-semibold text-sm">
                    {{ strtoupper(substr($welcomeData['first_name'], 0, 1)) }}
                </span>
            </div>
            
            <div>
                <div class="font-semibold text-sm" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                    {{ $welcomeData['greeting'] }}
                </div>
                <div class="text-xs text-white/70">
                    {{ $welcomeData['role_display'] }} • {{ $welcomeData['current_date'] }}
                </div>
            </div>
        </div>
        
        {{-- Right: Time & Quick Info --}}
        <div class="flex items-center space-x-4">
            {{-- Current Time --}}
            <div class="hidden sm:block text-right">
                <div class="text-sm font-semibold text-white/90" id="current-time-integrated">
                    {{ $welcomeData['current_time'] }}
                </div>
                <div class="text-xs text-white/60">WIB</div>
            </div>
            
            {{-- Weather Icon (placeholder) --}}
            <div class="hidden md:flex items-center space-x-1">
                <span class="text-lg">{{ ['☀️', '🌤️', '⛅', '🌧️'][rand(0,3)] }}</span>
                <span class="text-xs text-white/70">{{ rand(25, 32) }}°C</span>
            </div>
        </div>
    </div>
    
    {{-- Motivational Message Bar --}}
    <div class="mt-2 pt-2 border-t border-white/10">
        <p class="text-xs text-white/80 italic leading-relaxed">
            💡 {{ $welcomeData['motivational_message'] }}
        </p>
    </div>
    
    {{-- Subtle Animation Overlay --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute inset-0 opacity-30 bg-gradient-to-r from-transparent via-white/5 to-transparent transform -translate-x-full animate-shimmer"></div>
    </div>
</div>

{{-- Real-time Clock Update Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateIntegratedTime() {
        const timeElement = document.getElementById('current-time-integrated');
        if (timeElement) {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0');
            timeElement.textContent = timeString;
        }
    }
    
    // Update time immediately and then every minute
    updateIntegratedTime();
    setInterval(updateIntegratedTime, 60000);
});
</script>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.animate-shimmer {
    animation: shimmer 3s ease-in-out infinite;
}

.integrated-welcome-message {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.integrated-welcome-message:hover {
    backdrop-filter: blur(16px) saturate(130%);
    border-bottom-color: rgba(255, 255, 255, 0.12);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .integrated-welcome-message {
        padding: 0.5rem 1rem;
        margin: -1px -1px 0.75rem -1px;
    }
    
    .integrated-welcome-message .space-x-3 {
        gap: 0.5rem;
    }
}
</style>