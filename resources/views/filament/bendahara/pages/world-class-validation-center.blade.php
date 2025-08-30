<x-filament-panels::page>
    <!-- WORLD-CLASS VALIDATION CENTER WITH HORIZONTAL SCROLL -->
    <style>
        /* FORCE BLACK THEME - OVERRIDE ALL FILAMENT DEFAULTS */
        .fi-main, .fi-resource-list-records, .fi-resource-table {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            color: #ffffff !important;
            overflow: visible !important;
            max-width: none !important;
            width: auto !important;
        }
        
        /* GLOBAL CONTAINER FIXES */
        .fi-page, .fi-page-content, .fi-resource-list-records {
            overflow: visible !important;
            max-width: none !important;
            width: auto !important;
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
            max-width: 900px !important;
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
        
        .world-class-tab.active.tab-pending {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%) !important;
            border-color: #f59e0b !important;
            box-shadow: 0 8px 24px -4px rgba(245, 158, 11, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .world-class-tab.active.tab-approved {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%) !important;
            border-color: #10b981 !important;
            box-shadow: 0 8px 24px -4px rgba(16, 185, 129, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
        }
        
        .world-class-tab.active.tab-rejected {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%) !important;
            border-color: #ef4444 !important;
            box-shadow: 0 8px 24px -4px rgba(239, 68, 68, 0.4), 0 4px 12px -2px rgba(0, 0, 0, 0.4) !important;
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

        /* CRITICAL FIXES FOR TABLE FUNCTIONALITY */
        .fi-ta-content {
            background: transparent !important;
            backdrop-filter: none !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        
        .fi-ta-table {
            background: transparent !important;
            width: 100% !important;
        }
        
        .fi-ta-header {
            background: rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(8px) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
        }
        
        .fi-ta-header-cell {
            color: #ffffff !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
            white-space: nowrap !important;
            padding: 1rem !important;
        }
        
        .fi-ta-row {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            transition: background-color 0.2s ease !important;
        }
        
        .fi-ta-row:hover {
            background: rgba(255, 255, 255, 0.08) !important;
        }
        
        .fi-ta-cell {
            color: #ffffff !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            white-space: nowrap !important;
            padding: 1rem !important;
        }

        /* INLINE ACTION BUTTONS - SIMPLIFIED & VISIBLE */
        .fi-ta-actions {
            display: flex !important;
            gap: 0.5rem !important;
            align-items: center !important;
            justify-content: flex-end !important;
            padding: 0.5rem 1rem !important;
            min-width: 300px !important;
        }
        
        .fi-ac-btn {
            /* Professional Button Styling */
            min-width: 2.5rem !important;
            height: 2.25rem !important;
            padding: 0.5rem !important;
            border-radius: 0.375rem !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            transition: all 0.15s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
        }
        
        /* SUCCESS ACTION - GREEN */
        .fi-ac-btn-color-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
            border: 1px solid #22c55e !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px -2px rgba(34, 197, 94, 0.4) !important;
        }
        
        .fi-ac-btn-color-success:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
            box-shadow: 0 4px 12px -2px rgba(34, 197, 94, 0.5) !important;
            transform: translateY(-1px) !important;
        }
        
        /* DANGER ACTION - RED */
        .fi-ac-btn-color-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            border: 1px solid #ef4444 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px -2px rgba(239, 68, 68, 0.4) !important;
        }
        
        .fi-ac-btn-color-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            box-shadow: 0 4px 12px -2px rgba(239, 68, 68, 0.5) !important;
            transform: translateY(-1px) !important;
        }
        
        /* INFO ACTION - BLUE */
        .fi-ac-btn-color-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border: 1px solid #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px -2px rgba(59, 130, 246, 0.4) !important;
        }
        
        .fi-ac-btn-color-info:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.5) !important;
            transform: translateY(-1px) !important;
        }
        
        /* WARNING ACTION - YELLOW */
        .fi-ac-btn-color-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            border: 1px solid #f59e0b !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px -2px rgba(245, 158, 11, 0.4) !important;
        }
        
        .fi-ac-btn-color-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            box-shadow: 0 4px 12px -2px rgba(245, 158, 11, 0.5) !important;
            transform: translateY(-1px) !important;
        }
        
        /* GRAY ACTION - NEUTRAL */
        .fi-ac-btn-color-gray {
            background: rgba(156, 163, 175, 0.2) !important;
            border: 1px solid rgba(156, 163, 175, 0.3) !important;
            color: #d1d5db !important;
            box-shadow: 0 2px 8px -2px rgba(156, 163, 175, 0.2) !important;
        }
        
        .fi-ac-btn-color-gray:hover {
            background: rgba(156, 163, 175, 0.3) !important;
            border-color: rgba(156, 163, 175, 0.5) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px -2px rgba(156, 163, 175, 0.3) !important;
            transform: translateY(-1px) !important;
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

        /* HORIZONTAL SCROLL TABLE - CLEAN IMPLEMENTATION */
        .horizontal-scroll-table {
            overflow-x: auto !important;
            overflow-y: visible !important;
            width: 100% !important;
            border-radius: 1rem !important;
            background: linear-gradient(135deg, rgba(10, 10, 11, 0.6) 0%, rgba(17, 17, 24, 0.8) 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(12px) saturate(120%) !important;
            box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06) !important;
        }
        
        /* FORCE TABLE TO EXPAND BEYOND CONTAINER */
        .horizontal-scroll-table .fi-ta-table {
            min-width: 1400px !important; /* Force minimum table width */
            width: max-content !important;
        }
        
        /* Custom Scrollbar */
        .horizontal-scroll-table::-webkit-scrollbar {
            height: 10px !important;
            background: rgba(0, 0, 0, 0.2) !important;
            border-radius: 0.5rem !important;
        }
        
        .horizontal-scroll-table::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.4)) !important;
            border-radius: 0.5rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .horizontal-scroll-table::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.5)) !important;
        }
        
        /* TABLE RESET - REMOVE CONFLICTING STYLES */
        .fi-ta-content {
            background: transparent !important;
            backdrop-filter: none !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
            padding: 0 !important;
            width: auto !important;
            max-width: none !important;
        }
        
        .fi-ta-table {
            background: transparent !important;
            width: auto !important;
            min-width: 1400px !important;
            max-width: none !important;
        }
        
        .fi-ta-header {
            background: rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(8px) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
        }
        
        .fi-ta-header-cell {
            color: #ffffff !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
            white-space: nowrap !important;
            padding: 1rem !important;
            min-width: fit-content !important;
        }
        
        .fi-ta-row {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        }
        
        .fi-ta-row:hover {
            background: rgba(255, 255, 255, 0.08) !important;
        }
        
        .fi-ta-cell {
            color: #ffffff !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            white-space: nowrap !important;
            padding: 1rem !important;
            min-width: fit-content !important;
        }
        
        /* Generous Column Widths for Better Visibility */
        .fi-ta-cell:nth-child(1) { min-width: 160px !important; } /* Tanggal */
        .fi-ta-cell:nth-child(2) { min-width: 250px !important; } /* Jenis Tindakan */
        .fi-ta-cell:nth-child(3) { min-width: 180px !important; } /* Pasien */
        .fi-ta-cell:nth-child(4) { min-width: 150px !important; } /* Tarif */
        .fi-ta-cell:nth-child(5) { min-width: 150px !important; } /* Jaspel */
        .fi-ta-cell:nth-child(6) { min-width: 180px !important; } /* Status */
        .fi-ta-cell:nth-child(7) { min-width: 220px !important; } /* Pelaksana */
        .fi-ta-cell:nth-child(8) { min-width: 150px !important; } /* Petugas Input */
        .fi-ta-cell:nth-child(9) { min-width: 160px !important; } /* Tgl Validasi */
        .fi-ta-cell:nth-child(10) { min-width: 150px !important; } /* Validator */
        .fi-ta-cell:last-child { 
            min-width: 350px !important; /* Actions - Extra wide for buttons */
            background: rgba(17, 17, 24, 0.8) !important;
            backdrop-filter: blur(4px) !important;
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
                            'pending' => ['label' => '⏳ Menunggu Validasi', 'count' => $this->getTabBadge('pending')],
                            'approved' => ['label' => '✅ Sudah Disetujui', 'count' => $this->getTabBadge('approved')],
                            'rejected' => ['label' => '❌ Ditolak', 'count' => $this->getTabBadge('rejected')],
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

        <!-- Enhanced Horizontal Scroll Table -->
        <div style="margin: 0 1rem; position: relative; width: calc(100% - 2rem); max-width: none; overflow: visible;">
            <!-- Scroll Instruction -->
            <div style="
                background: rgba(59, 130, 246, 0.1);
                border: 1px solid rgba(59, 130, 246, 0.2);
                border-radius: 0.5rem;
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                color: #60a5fa;
                font-size: 0.875rem;
                backdrop-filter: blur(8px);
            ">
                <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span><strong>📋 Validation Center:</strong> Scroll horizontally ↔️ to view all columns • Total width: ~2000px</span>
            </div>
            
            <!-- Table with Horizontal Scroll -->
            <div class="horizontal-scroll-table" style="position: relative; z-index: 1;">
                {{ $this->table }}
            </div>
        </div>

        <!-- Tab Switching JavaScript -->
        <script>
            let currentActiveTab = 'all';
            
            function switchTab(tabKey, event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                currentActiveTab = tabKey;
                const url = new URL(window.location);
                url.searchParams.set('activeTab', tabKey);
                window.history.replaceState(null, null, url);
                
                document.querySelectorAll('.world-class-tab').forEach(tab => {
                    const isActive = tab.dataset.tab === tabKey;
                    tab.classList.remove('active');
                    if (isActive) {
                        tab.classList.add('active');
                    }
                });

                @this.call('filterTable', tabKey);
            }

            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    const filamentTabContainer = document.querySelector('.fi-tabs');
                    if (filamentTabContainer) {
                        filamentTabContainer.style.display = 'none';
                    }
                    
                    const urlParams = new URLSearchParams(window.location.search);
                    const initialTab = urlParams.get('activeTab') || 'all';
                    switchTab(initialTab, null);
                }, 100);
            });

            document.addEventListener('livewire:update', function () {
                console.log('Validation Center updated for tab:', currentActiveTab);
            });
        </script>

        <!-- Animation Styles -->
        <style>
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
        </style>
    </div>
</x-filament-panels::page>