{{-- Universal Welcome Topbar Integration Helper --}}
@props([
    'panel' => 'default',
    'user' => null,
    'compact' => false
])

@php
    $user = $user ?? auth()->user();
    $welcomeData = \App\Services\WelcomeGreetingService::getWelcomeData($user);
@endphp

{{-- Integration Instructions for All Panels --}}
{{-- 
    Usage in PanelProvider renderHook:
    
    ->renderHook(
        'panels::body.start',
        fn (): string => view('components.welcome-integration', [
            'panel' => 'petugas', // or admin, manajer, bendahara, dokter, paramedis
            'user' => auth()->user()
        ])->render()
    )
--}}

<div class="welcome-topbar-integration panel-{{ $panel }}">
    <x-world-class-welcome-topbar 
        :user="$user"
        :show-avatar="true"
        :show-date="true"
        :show-role="true"
        :compact="$compact"
    />
    
    {{-- Special Announcement Banner (if any) --}}
    @if($specialAnnouncement = \App\Services\WelcomeGreetingService::getSpecialAnnouncement())
        <div class="special-announcement-banner mb-4"
             style="
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(59, 130, 246, 0.2);
                border-radius: 0.75rem;
                padding: 1rem 1.5rem;
                color: #ffffff;
             ">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <span class="text-2xl">📢</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-100">
                        {{ $specialAnnouncement }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Panel-specific styling adjustments --}}
<style>
.welcome-topbar-integration.panel-admin .world-class-welcome-topbar {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.05) 0%, rgba(239, 68, 68, 0.08) 100%);
    border-bottom-color: rgba(239, 68, 68, 0.2);
}

.welcome-topbar-integration.panel-manajer .world-class-welcome-topbar {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(34, 197, 94, 0.08) 100%);
    border-bottom-color: rgba(34, 197, 94, 0.2);
}

.welcome-topbar-integration.panel-bendahara .world-class-welcome-topbar {
    background: linear-gradient(135deg, rgba(217, 119, 6, 0.05) 0%, rgba(245, 158, 11, 0.08) 100%);
    border-bottom-color: rgba(245, 158, 11, 0.2);
}

.welcome-topbar-integration.panel-dokter .world-class-welcome-topbar {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(99, 102, 241, 0.08) 100%);
    border-bottom-color: rgba(99, 102, 241, 0.2);
}

.welcome-topbar-integration.panel-petugas .world-class-welcome-topbar {
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.05) 0%, rgba(147, 51, 234, 0.08) 100%);
    border-bottom-color: rgba(147, 51, 234, 0.2);
}

.welcome-topbar-integration.panel-paramedis .world-class-welcome-topbar {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.08) 100%);
    border-bottom-color: rgba(6, 182, 212, 0.2);
}
</style>