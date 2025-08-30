{{-- Stripe-Style Complete Profile Replacement (No Theme Toggle) --}}
@props([
    'user' => null,
    'panelId' => 'default'
])

@php
    $user = $user ?? auth()->user();
    if (!$user) return;
    
    $userInitials = strtoupper(substr($user->name ?? 'U', 0, 2));
    $userRole = match ($user->role?->name) {
        'admin' => 'Administrator',
        'manajer' => 'Manager',
        'bendahara' => 'Treasurer', 
        'dokter' => 'Doctor',
        'petugas' => 'Staff',
        'paramedis' => 'Paramedic',
        default => 'User'
    };
    
    // Generate logout URL for current panel
    $logoutUrl = match ($panelId) {
        'petugas' => route('filament.petugas.auth.logout'),
        'bendahara' => route('filament.bendahara.auth.logout'),
        'admin' => route('filament.admin.auth.logout'),
        default => route('logout')
    };
@endphp

{{-- Stripe-Inspired Complete Profile System --}}
<div class="stripe-profile-system" 
     x-data="{ 
        open: false, 
        closeMenu() { this.open = false },
        toggleMenu() { this.open = !this.open }
     }"
     @click.away="closeMenu()"
     style="position: relative; z-index: 9999;">
    
    {{-- Profile Avatar Button (Stripe Pattern) --}}
    <button @click="toggleMenu()" 
            class="stripe-profile-trigger"
            :class="{ 'active': open }"
            style="
                display: flex;
                align-items: center;
                justify-content: center;
                width: 2.5rem;
                height: 2.5rem;
                background: linear-gradient(135deg, #635bff 0%, #4f46e5 100%);
                border: 2px solid rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                color: #ffffff;
                font-size: 0.875rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 
                    0 4px 12px rgba(99, 91, 255, 0.25),
                    0 2px 4px rgba(0, 0, 0, 0.1);
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
                position: relative;
                overflow: hidden;
            "
            onmouseover="
                this.style.transform = 'scale(1.05)';
                this.style.boxShadow = '0 6px 20px rgba(99, 91, 255, 0.35), 0 3px 6px rgba(0, 0, 0, 0.15)';
                this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            "
            onmouseout="
                this.style.transform = 'scale(1)';
                this.style.boxShadow = '0 4px 12px rgba(99, 91, 255, 0.25), 0 2px 4px rgba(0, 0, 0, 0.1)';
                this.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            ">
        
        @if($user->avatar)
            <img src="{{ $user->avatar }}" 
                 alt="{{ $user->name }}"
                 style="width: 100%; height: 100%; object-cover: cover; border-radius: 50%;" />
        @else
            {{ $userInitials }}
        @endif
        
        {{-- Online Status Indicator --}}
        <div style="
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 0.75rem;
            height: 0.75rem;
            background: #22c55e;
            border: 2px solid #111118;
            border-radius: 50%;
            animation: pulse 2s infinite;
        "></div>
    </button>

    {{-- Stripe-Style Profile Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
         class="stripe-profile-dropdown"
         style="
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 280px;
            background: linear-gradient(135deg, 
                rgba(17, 17, 24, 0.98) 0%, 
                rgba(30, 30, 40, 0.95) 100%);
            backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.8),
                0 10px 20px -5px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            padding: 1rem;
            z-index: 50;
            transform-origin: top right;
         ">
        
        {{-- Profile Header (Stripe Style) --}}
        <div style="
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 0.75rem;
        ">
            <div style="
                width: 3rem;
                height: 3rem;
                background: linear-gradient(135deg, #635bff 0%, #4f46e5 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1rem;
                font-weight: 700;
                box-shadow: 0 4px 8px rgba(99, 91, 255, 0.3);
            ">
                @if($user->avatar)
                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" 
                         style="width: 100%; height: 100%; object-cover: cover; border-radius: 50%;" />
                @else
                    {{ $userInitials }}
                @endif
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="
                    font-size: 1rem;
                    font-weight: 600;
                    color: #ffffff;
                    margin-bottom: 0.125rem;
                    line-height: 1.2;
                ">{{ $user->name }}</div>
                <div style="
                    font-size: 0.8125rem;
                    color: rgba(255, 255, 255, 0.6);
                    line-height: 1.2;
                ">{{ $userRole }} • {{ $user->email ?? 'user@example.com' }}</div>
            </div>
        </div>

        {{-- Professional Menu Items (NO THEME TOGGLE) --}}
        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            
            {{-- Account Management --}}
            <a href="#" 
               @click="closeMenu()"
               class="stripe-menu-item"
               style="
                display: flex;
                align-items: center;
                gap: 0.875rem;
                padding: 0.875rem;
                border-radius: 0.5rem;
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                transition: all 0.15s ease;
                font-size: 0.875rem;
                font-weight: 500;
               "
               onmouseover="
                this.style.background = 'rgba(99, 91, 255, 0.08)';
                this.style.color = '#ffffff';
                this.style.transform = 'translateX(2px)';
               "
               onmouseout="
                this.style.background = 'transparent';
                this.style.color = 'rgba(255, 255, 255, 0.9)';
                this.style.transform = 'translateX(0)';
               ">
                <div style="
                    width: 2rem;
                    height: 2rem;
                    background: rgba(99, 91, 255, 0.1);
                    border-radius: 0.375rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <div style="font-weight: 500; color: inherit;">Account Settings</div>
                    <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.5);">Manage your profile</div>
                </div>
            </a>

            {{-- Security Settings --}}
            <a href="#" 
               @click="closeMenu()"
               class="stripe-menu-item"
               style="
                display: flex;
                align-items: center;
                gap: 0.875rem;
                padding: 0.875rem;
                border-radius: 0.5rem;
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                transition: all 0.15s ease;
                font-size: 0.875rem;
                font-weight: 500;
               "
               onmouseover="
                this.style.background = 'rgba(99, 91, 255, 0.08)';
                this.style.color = '#ffffff';
                this.style.transform = 'translateX(2px)';
               "
               onmouseout="
                this.style.background = 'transparent';
                this.style.color = 'rgba(255, 255, 255, 0.9)';
                this.style.transform = 'translateX(0)';
               ">
                <div style="
                    width: 2rem;
                    height: 2rem;
                    background: rgba(34, 197, 94, 0.1);
                    border-radius: 0.375rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <svg style="width: 1rem; height: 1rem; color: #22c55e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <div style="font-weight: 500; color: inherit;">Security</div>
                    <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.5);">Privacy & access</div>
                </div>
            </a>

            {{-- Separator --}}
            <div style="
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                margin: 0.75rem 0;
            "></div>

            {{-- Sign Out (Stripe Style) --}}
            <form method="POST" action="{{ $logoutUrl }}" style="margin: 0;">
                @csrf
                <button type="submit"
                        class="stripe-menu-item logout-item"
                        style="
                         display: flex;
                         align-items: center;
                         gap: 0.875rem;
                         padding: 0.875rem;
                         border-radius: 0.5rem;
                         background: transparent;
                         border: none;
                         color: rgba(248, 113, 113, 0.9);
                         text-decoration: none;
                         transition: all 0.15s ease;
                         font-size: 0.875rem;
                         font-weight: 500;
                         width: 100%;
                         text-align: left;
                         cursor: pointer;
                        "
                        onmouseover="
                         this.style.background = 'rgba(248, 113, 113, 0.08)';
                         this.style.color = '#f87171';
                         this.style.transform = 'translateX(2px)';
                        "
                        onmouseout="
                         this.style.background = 'transparent';
                         this.style.color = 'rgba(248, 113, 113, 0.9)';
                         this.style.transform = 'translateX(0)';
                        ">
                    <div style="
                        width: 2rem;
                        height: 2rem;
                        background: rgba(248, 113, 113, 0.1);
                        border-radius: 0.375rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight: 500; color: inherit;">Sign Out</div>
                        <div style="font-size: 0.75rem; color: rgba(248, 113, 113, 0.6);">End your session</div>
                    </div>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Stripe-Style CSS --}}
<style>
/* Stripe Profile System Styles */
.stripe-profile-system {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.stripe-profile-trigger.active {
    transform: scale(1.05) !important;
    box-shadow: 0 6px 20px rgba(99, 91, 255, 0.4) !important;
}

.stripe-profile-dropdown {
    animation: stripeSlideIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.stripe-menu-item:focus-visible {
    outline: 2px solid #635bff;
    outline-offset: 2px;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes stripeSlideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Responsive behavior */
@media (max-width: 768px) {
    .stripe-profile-dropdown {
        min-width: 260px;
        right: -1rem;
    }
}

/* Professional hover effects */
.stripe-menu-item {
    position: relative;
    overflow: hidden;
}

.stripe-menu-item::before {
    content: '';
    position: absolute;
    left: -100%;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(99, 91, 255, 0.05), transparent);
    transition: left 0.6s ease;
}

.stripe-menu-item:hover::before {
    left: 100%;
}
</style>