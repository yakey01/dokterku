{{-- Modern SaaS Profile Menu (No Theme Toggle) --}}
@props([
    'user' => null,
    'variant' => 'default' // default, minimal, professional
])

@php
    $user = $user ?? auth()->user();
    
    if (!$user) {
        return;
    }
    
    // Get user initials
    $initials = strtoupper(substr($user->name, 0, 2));
    
    // Get role display
    $roleDisplay = match ($user->role?->name) {
        'admin' => 'Administrator',
        'manajer' => 'Manajer',
        'bendahara' => 'Bendahara',
        'dokter' => 'Dokter',
        'petugas' => 'Petugas',
        'paramedis' => 'Paramedis',
        default => 'User'
    };
    
    // Panel context
    $currentPanel = request()->route()?->getPrefix() ?? '';
    $panelName = match ($currentPanel) {
        '/admin' => 'Admin Panel',
        '/manajer' => 'Manajer Dashboard',
        '/bendahara' => 'Bendahara Dashboard',
        '/dokter' => 'Dokter Portal',
        '/petugas' => 'Petugas Dashboard', 
        '/paramedis' => 'Paramedis Portal',
        default => 'Dashboard'
    };
@endphp

<div class="saas-profile-menu" x-data="{ open: false }">
    {{-- Profile Trigger Button --}}
    <button 
        @click="open = !open"
        @click.away="open = false"
        class="profile-trigger"
        type="button"
        aria-expanded="false"
        aria-haspopup="true"
        style="
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            color: #ffffff;
            transition: all 0.2s ease;
            cursor: pointer;
            backdrop-filter: blur(8px);
            box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.2);
        "
        onmouseover="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.12)'; this.style.transform='translateY(-1px)';"
        onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.borderColor='rgba(255, 255, 255, 0.08)'; this.style.transform='translateY(0)';"
    >
        {{-- User Avatar --}}
        <div class="profile-avatar"
             style="
                width: 2rem;
                height: 2rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.75rem;
                font-weight: 700;
                color: #ffffff;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             ">
            @if($user->avatar)
                <img src="{{ $user->avatar }}" 
                     alt="{{ $user->name }}"
                     class="w-full h-full rounded-full object-cover"
                />
            @else
                {{ $initials }}
            @endif
        </div>
        
        {{-- User Info (Hidden on mobile) --}}
        <div class="profile-info hidden lg:block" 
             style="display: flex; flex-direction: column; align-items: flex-start; min-width: 0;">
            <span style="
                font-size: 0.875rem;
                font-weight: 600;
                color: #ffffff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 120px;
            ">{{ $user->name }}</span>
            <span style="
                font-size: 0.6875rem;
                color: rgba(255, 255, 255, 0.7);
                text-transform: uppercase;
                letter-spacing: 0.025em;
            ">{{ $roleDisplay }}</span>
        </div>
        
        {{-- Dropdown Chevron --}}
        <svg class="chevron-icon"
             style="
                width: 1rem;
                height: 1rem;
                color: rgba(255, 255, 255, 0.6);
                transition: transform 0.2s ease;
             "
             x-bind:style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'"
             fill="none" 
             stroke="currentColor" 
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="profile-dropdown"
        style="
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            z-index: 50;
            min-width: 240px;
            background: linear-gradient(135deg, rgba(17, 17, 24, 0.95) 0%, rgba(26, 26, 32, 0.98) 100%);
            backdrop-filter: blur(20px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1rem;
            box-shadow: 
                0 20px 40px -12px rgba(0, 0, 0, 0.6),
                0 8px 16px -4px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
            padding: 1rem;
            color: #ffffff;
        "
    >
        {{-- User Header --}}
        <div class="dropdown-header"
             style="
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                margin-bottom: 0.75rem;
             ">
            <div class="header-avatar"
                 style="
                    width: 2.5rem;
                    height: 2.5rem;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.875rem;
                    font-weight: 700;
                    color: #ffffff;
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
                    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
                 ">
                @if($user->avatar)
                    <img src="{{ $user->avatar }}" 
                         alt="{{ $user->name }}"
                         class="w-full h-full rounded-full object-cover"
                    />
                @else
                    {{ $initials }}
                @endif
            </div>
            <div style="flex-grow: 1; min-width: 0;">
                <div style="
                    font-size: 0.875rem;
                    font-weight: 600;
                    color: #ffffff;
                    margin-bottom: 0.125rem;
                ">{{ $user->name }}</div>
                <div style="
                    font-size: 0.6875rem;
                    color: rgba(255, 255, 255, 0.6);
                    text-transform: uppercase;
                    letter-spacing: 0.025em;
                ">{{ $roleDisplay }} • {{ $panelName }}</div>
            </div>
        </div>

        {{-- Menu Items (NO THEME TOGGLE) --}}
        <div class="dropdown-menu" style="display: flex; flex-direction: column; gap: 0.25rem;">
            {{-- Account Settings --}}
            <a href="{{ route('filament.admin.auth.profile') }}" 
               class="dropdown-item"
               style="
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                background: transparent;
                border: 1px solid transparent;
                border-radius: 0.5rem;
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                transition: all 0.2s ease;
                font-size: 0.875rem;
                font-weight: 500;
               "
               onmouseover="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.borderColor='rgba(255, 255, 255, 0.1)'; this.style.transform='translateX(2px)';"
               onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'; this.style.transform='translateX(0)';"
            >
                <svg style="width: 1.125rem; height: 1.125rem; color: rgba(255, 255, 255, 0.6);" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Profil Saya</span>
            </a>

            {{-- Account Settings --}}
            <a href="#" 
               class="dropdown-item"
               style="
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                background: transparent;
                border: 1px solid transparent;
                border-radius: 0.5rem;
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                transition: all 0.2s ease;
                font-size: 0.875rem;
                font-weight: 500;
               "
               onmouseover="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.borderColor='rgba(255, 255, 255, 0.1)'; this.style.transform='translateX(2px)';"
               onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'; this.style.transform='translateX(0)';"
            >
                <svg style="width: 1.125rem; height: 1.125rem; color: rgba(255, 255, 255, 0.6);" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Pengaturan</span>
            </a>

            {{-- Separator --}}
            <div style="
                height: 1px;
                background: rgba(255, 255, 255, 0.08);
                margin: 0.5rem 0;
            "></div>

            {{-- Logout --}}
            <form method="POST" action="{{ route('filament.' . (str_replace('/', '', $currentPanel) ?: 'admin') . '.auth.logout') }}" 
                  style="margin: 0;">
                @csrf
                <button type="submit"
                        class="dropdown-item logout"
                        style="
                         display: flex;
                         align-items: center;
                         gap: 0.75rem;
                         padding: 0.75rem;
                         background: transparent;
                         border: 1px solid transparent;
                         border-radius: 0.5rem;
                         color: rgba(248, 113, 113, 0.9);
                         text-decoration: none;
                         transition: all 0.2s ease;
                         font-size: 0.875rem;
                         font-weight: 500;
                         width: 100%;
                         text-align: left;
                        "
                        onmouseover="this.style.background='rgba(248, 113, 113, 0.1)'; this.style.borderColor='rgba(248, 113, 113, 0.2)'; this.style.transform='translateX(2px)';"
                        onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'; this.style.transform='translateX(0)';"
                >
                    <svg style="width: 1.125rem; height: 1.125rem;" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modern SaaS Profile Styles --}}
<style>
.saas-profile-menu {
    position: relative;
    z-index: 50;
}

.profile-trigger:focus-visible {
    outline: 2px solid rgba(99, 102, 241, 0.6);
    outline-offset: 2px;
}

.profile-dropdown {
    animation: slideDownAndFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.dropdown-item:focus-visible {
    outline: 2px solid rgba(99, 102, 241, 0.4);
    outline-offset: -2px;
}

@keyframes slideDownAndFade {
    from {
        opacity: 0;
        transform: translateY(-2px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .profile-info {
        display: none !important;
    }
    
    .profile-trigger {
        padding: 0.5rem !important;
    }
}

/* Modern SaaS hover animations */
.dropdown-item {
    position: relative;
    overflow: hidden;
}

.dropdown-item::before {
    content: '';
    position: absolute;
    left: -100%;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    transition: left 0.5s ease;
}

.dropdown-item:hover::before {
    left: 100%;
}

/* Status indicator */
.profile-avatar::after {
    content: '';
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 0.5rem;
    height: 0.5rem;
    background: #22c55e;
    border: 2px solid #111118;
    border-radius: 50%;
    animation: statusPulse 2s infinite;
}

@keyframes statusPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}
</style>

{{-- Alpine.js will be loaded by Filament - no need for additional script --}}