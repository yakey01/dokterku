{{-- 
  Modern Bendahara Dashboard - Conflict-Free Architecture
  Uses Filament v3 widgets and component system properly
--}}

<x-filament-panels::page>
    {{-- Clean, minimal dashboard using pure Filament architecture --}}
    <div class="space-y-6">
        {{-- Dashboard Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    💰 Financial Dashboard
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitor treasury operations and financial metrics
                </p>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                {{ now()->format('D, d M Y • H:i') }}
            </div>
        </div>

        {{-- Widgets will be rendered here by Filament --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :data="$this->getWidgetData()"
        />

        {{-- Additional dashboard sections can be added here --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Quick Actions Section --}}
            <x-filament::section>
                <x-slot name="heading">
                    Quick Actions
                </x-slot>
                
                <div class="space-y-3">
                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.bendahara.resources.validasi-jumlah-pasiens.index') }}"
                        icon="heroicon-o-clipboard-document-check"
                        color="gray"
                    >
                        Validate Patient Data
                    </x-filament::button>
                    
                    <x-filament::button
                        tag="a" 
                        href="{{ route('filament.bendahara.resources.laporan-jaspel.index') }}"
                        icon="heroicon-o-document-chart-bar"
                        color="gray"
                    >
                        Financial Reports
                    </x-filament::button>
                </div>
            </x-filament::section>

            {{-- System Status Section --}}
            <x-filament::section>
                <x-slot name="heading">
                    System Status
                </x-slot>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon 
                                icon="heroicon-o-check-circle" 
                                class="w-5 h-5 text-green-600 dark:text-green-400"
                            />
                            <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                Financial System
                            </span>
                        </div>
                        <span class="text-xs text-green-600 dark:text-green-400 font-mono">
                            Online
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon 
                                icon="heroicon-o-shield-check" 
                                class="w-5 h-5 text-green-600 dark:text-green-400"
                            />
                            <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                Validation Engine
                            </span>
                        </div>
                        <span class="text-xs text-green-600 dark:text-green-400 font-mono">
                            Active
                        </span>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>