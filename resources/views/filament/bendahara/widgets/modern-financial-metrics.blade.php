{{-- 
  Modern Financial Metrics Widget - Conflict-Free Design
  Uses component-based architecture to prevent CSS conflicts
--}}

@php
    $financial = $this->getFinancialSummary();
@endphp

<x-filament-widgets::widget>
    {{-- Component-level styling inside root element --}}
    <style>
        /* Ensure this widget uses black theme regardless of external CSS */
        .filament-widget-modern-financial-metrics-widget {
            background: transparent !important;
        }
        
        .filament-widget-modern-financial-metrics-widget .fi-section {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border: 1px solid #333340 !important;
            border-radius: 1rem !important;
            color: #fafafa !important;
            box-shadow: 
                0 4px 12px -2px rgba(0, 0, 0, 0.8),
                0 2px 6px -2px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
        }
        
        .filament-widget-modern-financial-metrics-widget * {
            color: inherit !important;
        }
    </style>

    <x-filament::section>
        {{-- Widget Header --}}
        <div style="
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #333340;
        ">
            <div>
                <h2 style="
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #fafafa;
                    margin-bottom: 0.25rem;
                    letter-spacing: -0.02em;
                ">
                    💰 Financial Overview
                </h2>
                <p style="
                    font-size: 0.875rem;
                    color: #a1a1aa;
                    font-weight: 500;
                ">
                    Current month metrics with growth comparison
                </p>
            </div>
            <div style="
                font-size: 0.75rem;
                color: #71717a;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-weight: 600;
            ">
                {{ now()->format('F Y') }}
            </div>
        </div>

        {{-- Financial Metrics Grid --}}
        <div style="
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.5rem;
        " class="md:grid-cols-2 lg:grid-cols-4">
            
            {{-- Revenue Card --}}
            <x-filament-bendahara::black-theme-card
                title="Total Revenue"
                :value="$this->formatCurrency($financial['current']['revenue'])"
                description="Revenue This Month"
                icon="heroicon-o-currency-dollar"
                :trend="$this->formatGrowth($financial['growth']['revenue'])"
                color="emerald"
            />

            {{-- Expenses Card --}}
            <x-filament-bendahara::black-theme-card
                title="Total Expenses"
                :value="$this->formatCurrency($financial['current']['expenses'])"
                description="Expenses This Month"
                icon="heroicon-o-minus-circle"
                :trend="$this->formatGrowth($financial['growth']['expenses'])"
                color="red"
            />

            {{-- Jaspel Card --}}
            <x-filament-bendahara::black-theme-card
                title="Jaspel Payments"
                :value="$this->formatCurrency($financial['current']['jaspel'])"
                description="Jaspel This Month"
                icon="heroicon-o-user-group"
                :trend="$this->formatGrowth($financial['growth']['jaspel'])"
                color="blue"
            />

            {{-- Net Income Card --}}
            <x-filament-bendahara::black-theme-card
                title="Net Income"
                :value="$this->formatCurrency($financial['current']['net_income'])"
                description="Net Profit This Month"
                icon="heroicon-o-chart-bar"
                :trend="$this->formatGrowth($financial['growth']['net_income'])"
                color="amber"
            />
        </div>

        {{-- Quick Stats Summary --}}
        <div style="
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #1a1a20 0%, #2a2a32 100%);
            border: 1px solid #404050;
            border-radius: 0.75rem;
            box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.05);
        ">
            <div style="
                display: grid;
                grid-template-columns: repeat(1, 1fr);
                gap: 1rem;
                text-align: center;
            " class="md:grid-cols-3">
                <div>
                    <div style="
                        font-size: 1.25rem;
                        font-weight: 700;
                        color: #fafafa;
                        margin-bottom: 0.25rem;
                    ">
                        {{ $this->formatCurrency($financial['previous']['revenue']) }}
                    </div>
                    <div style="
                        font-size: 0.75rem;
                        color: #a1a1aa;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        font-weight: 600;
                    ">
                        Last Month Revenue
                    </div>
                </div>
                <div>
                    <div style="
                        font-size: 1.25rem;
                        font-weight: 700;
                        color: #fafafa;
                        margin-bottom: 0.25rem;
                    ">
                        {{ $this->formatCurrency($financial['previous']['expenses']) }}
                    </div>
                    <div style="
                        font-size: 0.75rem;
                        color: #a1a1aa;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        font-weight: 600;
                    ">
                        Last Month Expenses
                    </div>
                </div>
                <div>
                    <div style="
                        font-size: 1.25rem;
                        font-weight: 700;
                        color: {{ $financial['previous']['net_income'] >= 0 ? '#22d65f' : '#f87171' }};
                        margin-bottom: 0.25rem;
                    ">
                        {{ $this->formatCurrency($financial['previous']['net_income']) }}
                    </div>
                    <div style="
                        font-size: 0.75rem;
                        color: #a1a1aa;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        font-weight: 600;
                    ">
                        Last Month Net Income
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>