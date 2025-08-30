<x-filament-panels::page>
    <!-- Complete Inline CSS for Black Glass Theme -->
    <style>
        /* FORCE BLACK THEME - OVERRIDE ALL FILAMENT DEFAULTS */
        .fi-main, .fi-resource-list-records, .fi-resource-table {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            color: #ffffff !important;
        }
        
        /* WORLD-CLASS TAB SYSTEM */
        .world-class-tab-container {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            margin: 2rem 0 !important;
            padding: 0 1rem !important;
        }
        
        .world-class-tab-wrapper {
            background: linear-gradient(135deg, rgba(10, 10, 11, 0.8) 0%, rgba(17, 17, 24, 0.95) 100%) !important;
            backdrop-filter: blur(16px) saturate(150%) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 1rem !important;
            padding: 1rem !important;
            box-shadow: 
                0 8px 32px -8px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
            max-width: 800px !important;
            width: 100% !important;
        }
        
        .world-class-tab-nav {
            display: flex !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            flex-wrap: wrap !important;
        }
        
        .world-class-tab {
            position: relative !important;
            padding: 0.875rem 1.75rem !important;
            border-radius: 0.875rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(8px) !important;
            color: #d1d5db !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
            user-select: none !important;
            overflow: hidden !important;
            transform: translateY(0) !important;
            box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.2) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
        }
        
        .world-class-tab:hover {
            transform: translateY(-2px) !important;
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            box-shadow: 0 8px 24px -4px rgba(255, 255, 255, 0.1), 0 4px 12px -2px rgba(0, 0, 0, 0.5) !important;
        }
        
        .world-class-tab.active {
            transform: translateY(-2px) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8) !important;
        }
        
        .world-class-tab.active.tab-all {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.2) 0%, rgba(107, 114, 128, 0.1) 100%) !important;
            border-color: #6b7280 !important;
            box-shadow: 0 8px 24px -4px rgba(107, 114, 128, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .world-class-tab.active.tab-sudah-validasi {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%) !important;
            border-color: #10b981 !important;
            box-shadow: 0 8px 24px -4px rgba(16, 185, 129, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .world-class-tab.active.tab-belum-validasi {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%) !important;
            border-color: #f59e0b !important;
            box-shadow: 0 8px 24px -4px rgba(245, 158, 11, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .world-class-tab.active.tab-dokter {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 8px 24px -4px rgba(59, 130, 246, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .world-class-tab.active.tab-paramedis {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.2) 0%, rgba(236, 72, 153, 0.1) 100%) !important;
            border-color: #ec4899 !important;
            box-shadow: 0 8px 24px -4px rgba(236, 72, 153, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .tab-content {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            position: relative !important;
            z-index: 1 !important;
        }
        
        .tab-badge {
            padding: 0.125rem 0.5rem !important;
            border-radius: 0.5rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            min-width: 1.5rem !important;
            text-align: center !important;
            background: rgba(0, 0, 0, 0.3) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* HIDE DEFAULT FILAMENT TABS */
        .fi-tabs, [role="tablist"] {
            display: none !important;
        }

        /* TABLE STYLING */
        .fi-ta-content {
            background: linear-gradient(135deg, rgba(10, 10, 11, 0.6) 0%, rgba(17, 17, 24, 0.8) 100%) !important;
            backdrop-filter: blur(12px) saturate(120%) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 1rem !important;
            box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06) !important;
        }
        
        .fi-ta-table {
            background: transparent !important;
        }
        
        .fi-ta-header {
            background: rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(8px) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .fi-ta-header-cell {
            color: #ffffff !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
        }
        
        .fi-ta-row {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            transition: all 0.2s ease !important;
        }
        
        .fi-ta-row:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            transform: translateY(-1px) !important;
        }
        
        .fi-ta-cell {
            color: #ffffff !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }
    </style>
    
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%); min-height: 100vh; color: #ffffff;">
        
        <!-- World-Class Tab Navigation - CENTERED -->
        <div class="world-class-tab-container">
            <div class="world-class-tab-wrapper">
                <nav class="world-class-tab-nav">
                    @php
                        $activeTab = request()->get('activeTab', 'all');
                        $tabs = [
                            'all' => ['label' => '🗂️ Semua Data', 'count' => $this->getTabBadge('all')],
                            'sudah_validasi' => ['label' => '✅ Sudah Validasi', 'count' => $this->getTabBadge('sudah_validasi')],
                            'belum_validasi' => ['label' => '⏳ Belum Validasi', 'count' => $this->getTabBadge('belum_validasi')],
                            'dokter' => ['label' => '👨‍⚕️ Dokter', 'count' => $this->getTabBadge('dokter')],
                            'paramedis' => ['label' => '👩‍⚕️ Paramedis', 'count' => $this->getTabBadge('paramedis')]
                        ];
                    @endphp

                    @foreach($tabs as $tabKey => $tabData)
                        <button 
                            type="button"
                            onclick="switchTab('{{ $tabKey }}', event)"
                            class="world-class-tab {{ $tabKey === $activeTab ? 'active' : '' }} tab-{{ str_replace('_', '-', $tabKey) }}"
                            data-tab="{{ $tabKey }}"
                        >
                            <span class="tab-content">
                                {{ $tabData['label'] }}
                                <span class="tab-badge">{{ $tabData['count'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- Filament Table -->
        <div style="margin: 0 1rem;">
            {{ $this->table }}
        </div>

        <!-- Tab Switching JavaScript -->
        <script>
            let currentActiveTab = 'all';
            
            function switchTab(tabKey, event) {
                // Prevent any default button behavior that might cause scrolling
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Update active tab reference
                currentActiveTab = tabKey;
                
                // Update URL parameter
                const url = new URL(window.location);
                url.searchParams.set('activeTab', tabKey);
                window.history.replaceState(null, null, url);
                
                // Update tab visual states
                document.querySelectorAll('.world-class-tab').forEach(tab => {
                    const isActive = tab.dataset.tab === tabKey;
                    
                    // Remove all active classes first
                    tab.classList.remove('active');
                    
                    if (isActive) {
                        tab.classList.add('active');
                    }
                });

                // Call Livewire method to filter the table (no scroll triggers)
                @this.call('filterTable', tabKey);
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Hide default Filament tabs
                setTimeout(() => {
                    const filamentTabContainer = document.querySelector('.fi-tabs');
                    if (filamentTabContainer) {
                        filamentTabContainer.style.display = 'none';
                    }
                    
                    // Set initial tab from URL or default to 'all'
                    const urlParams = new URLSearchParams(window.location.search);
                    const initialTab = urlParams.get('activeTab') || 'all';
                    switchTab(initialTab, null);
                }, 100);
            });

            // smoothScrollToTable function removed to prevent unwanted autoscroll
            // Users can manually scroll if needed after tab switching

            // Listen for Livewire updates to refresh badge counts
            document.addEventListener('livewire:update', function () {
                // Optional: Update badge counts after table refresh
                console.log('Table updated for tab:', currentActiveTab);
            });
        </script>
    </div>
</x-filament-panels::page>