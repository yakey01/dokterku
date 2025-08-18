<x-filament-panels::page>
    {{-- Pass auth token to React app --}}
    @if($apiToken)
        <meta name="auth-token" content="{{ $apiToken }}">
    @endif
    
    {{-- Main React App Container --}}
    <div id="manajer-dashboard-root" class="w-full -m-6">
        {{-- Loading state while React app initializes --}}
        <div class="flex items-center justify-center h-screen">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Loading Manager Dashboard...</p>
            </div>
        </div>
    </div>
    
    {{-- Load React App --}}
    @vite('resources/js/manajer-dashboard.tsx')
    
    {{-- Custom styles for full-width mobile support --}}
    <style>
        /* Remove Filament default padding for full-width dashboard */
        .fi-page-simple-wrapper {
            padding: 0 !important;
        }
        
        .fi-simple-main {
            max-width: 100% !important;
        }
        
        /* Hide Filament header and sidebar for this page */
        .fi-topbar {
            display: none;
        }
        
        .fi-sidebar {
            display: none;
        }
        
        /* Full height for dashboard */
        #manajer-dashboard-root {
            min-height: 100vh;
        }
        
        /* Mobile-first responsive adjustments */
        @media (max-width: 768px) {
            .fi-simple-page {
                padding: 0 !important;
            }
        }
    </style>
</x-filament-panels::page>