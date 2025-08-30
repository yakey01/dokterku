{{-- 
  Elegant Black Theme Card - Filament v3 Native Component
  Eliminates CSS conflicts by using component-level styling
--}}

@php
    $colorMap = [
        'emerald' => 'emerald',
        'red' => 'red', 
        'blue' => 'blue',
        'amber' => 'amber',
    ];
    $selectedColor = $colorMap[$color] ?? 'emerald';
@endphp

<div 
    class="elegant-black-card group"
    style="
        background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
        border: 1px solid #333340;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #fafafa;
    "
    onmouseover="this.style.transform = 'translateY(-3px)'; this.style.boxShadow = '0 8px 25px -5px rgba(0, 0, 0, 0.8), 0 8px 16px -4px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.background = 'linear-gradient(135deg, #111118 0%, #1a1a20 100%)';"
    onmouseout="this.style.transform = 'translateY(0px)'; this.style.boxShadow = '0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.background = 'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)';"
>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        @if($icon)
            <div style="
                padding: 0.75rem;
                background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.08) 100%);
                border: 1px solid rgba(34, 197, 94, 0.2);
                border-radius: 0.5rem;
                box-shadow: inset 0 1px 0 0 rgba(34, 197, 94, 0.1);
            ">
                <x-filament::icon 
                    :icon="$icon" 
                    class="w-6 h-6"
                    style="color: #22d65f;"
                />
            </div>
        @endif
        
        @if($trend)
            <div style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.875rem;">
                @if(str_starts_with($trend, '+'))
                    <x-filament::icon 
                        icon="heroicon-s-arrow-up-right" 
                        class="w-4 h-4"
                        style="color: #22d65f;"
                    />
                    <span style="color: #22d65f; font-weight: 600;">{{ $trend }}</span>
                @else
                    <x-filament::icon 
                        icon="heroicon-s-arrow-down-right" 
                        class="w-4 h-4"
                        style="color: #f87171;"
                    />
                    <span style="color: #f87171; font-weight: 600;">{{ $trend }}</span>
                @endif
            </div>
        @endif
    </div>

    @if($value)
        <h3 style="
            font-size: 2.25rem;
            line-height: 2.5rem;
            font-weight: 700;
            color: #fafafa;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        ">
            {{ $value }}
        </h3>
    @endif

    @if($title)
        <h4 style="
            font-size: 1.125rem;
            font-weight: 600;
            color: #fafafa;
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        ">
            {{ $title }}
        </h4>
    @endif

    @if($description)
        <p style="
            font-size: 0.875rem;
            font-weight: 600;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            line-height: 1.4;
            opacity: 0.9;
        ">
            {{ $description }}
        </p>
    @endif

    {{ $slot }}
</div>

<style>
    /* Component-level CSS to prevent conflicts */
    .elegant-black-card * {
        color: inherit !important;
    }
    
    .elegant-black-card:hover {
        border-color: #404050 !important;
    }
</style>