<x-filament-panels::page>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
        
        <!-- Glassmorphic CSS Framework -->
        <style>
            .glass-validation {
                /* Professional Glass Color System */
                --glass-bg: rgba(10, 10, 11, 0.85);
                --glass-bg-light: rgba(17, 17, 24, 0.8);
                --glass-border: rgba(255, 255, 255, 0.1);
                --glass-border-hover: rgba(255, 255, 255, 0.2);
                --text-primary: #fafafa;
                --text-secondary: #e4e4e7;
                --text-muted: #a1a1aa;
                
                /* Semantic Colors */
                --success: #22c55e;
                --warning: #f59e0b;
                --danger: #ef4444;
                --info: #3b82f6;
                --primary: #8b5cf6;
                
                /* Glass Effects */
                --backdrop-blur: blur(20px);
                --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            
            /* Override Filament Table Styles dengan Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-ta-table,
            [data-filament-panel-id="bendahara"] .fi-ta-content,
            [data-filament-panel-id="bendahara"] .fi-section {
                background: var(--glass-bg) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 16px !important;
                box-shadow: var(--glass-shadow) !important;
                color: var(--text-primary) !important;
            }
            
            /* Table Header Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-ta-header,
            [data-filament-panel-id="bendahara"] .fi-ta-header-cell {
                background: var(--glass-bg-light) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                border-color: var(--glass-border) !important;
                color: var(--text-secondary) !important;
                font-weight: 600 !important;
            }
            
            /* Table Rows Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-ta-row {
                background: transparent !important;
                border-color: rgba(255, 255, 255, 0.05) !important;
                color: var(--text-primary) !important;
                transition: all 0.3s ease !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-ta-row:hover {
                background: var(--glass-bg-light) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2) !important;
            }
            
            /* Minimalist Tabs */
            [data-filament-panel-id="bendahara"] .fi-tabs {
                background: transparent !important;
                border: none !important;
                border-bottom: 1px solid var(--glass-border) !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin-bottom: 2rem !important;
                box-shadow: none !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-tabs-item {
                background: transparent !important;
                border: none !important;
                border-bottom: 2px solid transparent !important;
                border-radius: 0 !important;
                color: var(--text-muted) !important;
                font-size: 0.875rem !important;
                font-weight: 500 !important;
                padding: 0.75rem 1rem !important;
                transition: all 0.2s ease !important;
                position: relative !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-tabs-item:hover {
                background: transparent !important;
                color: var(--text-secondary) !important;
                border-bottom-color: rgba(139, 92, 246, 0.3) !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-tabs-item[aria-selected="true"] {
                background: transparent !important;
                border-bottom-color: var(--primary) !important;
                color: var(--text-primary) !important;
                font-weight: 600 !important;
                box-shadow: none !important;
            }
            
            /* Tab badges minimalist */
            [data-filament-panel-id="bendahara"] .fi-tabs .fi-badge {
                background: var(--glass-bg) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 6px !important;
                font-size: 0.6875rem !important;
                font-weight: 600 !important;
                margin-left: 0.375rem !important;
                padding: 0.25rem 0.5rem !important;
            }
            
            /* Filament Actions Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-ac-btn,
            [data-filament-panel-id="bendahara"] .fi-btn {
                background: var(--glass-bg) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 8px !important;
                color: var(--text-secondary) !important;
                transition: all 0.3s ease !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-ac-btn:hover,
            [data-filament-panel-id="bendahara"] .fi-btn:hover {
                background: var(--glass-bg-light) !important;
                border-color: var(--glass-border-hover) !important;
                color: var(--text-primary) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2) !important;
            }
            
            /* Primary Actions */
            [data-filament-panel-id="bendahara"] .fi-btn-color-primary,
            [data-filament-panel-id="bendahara"] .fi-ac-btn-color-primary {
                background: linear-gradient(135deg, var(--primary), rgba(139, 92, 246, 0.8)) !important;
                border-color: var(--primary) !important;
                color: white !important;
                box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3) !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-btn-color-success,
            [data-filament-panel-id="bendahara"] .fi-ac-btn-color-success {
                background: linear-gradient(135deg, var(--success), rgba(34, 197, 94, 0.8)) !important;
                border-color: var(--success) !important;
                color: white !important;
                box-shadow: 0 4px 16px rgba(34, 197, 94, 0.3) !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-btn-color-danger,
            [data-filament-panel-id="bendahara"] .fi-ac-btn-color-danger {
                background: linear-gradient(135deg, var(--danger), rgba(239, 68, 68, 0.8)) !important;
                border-color: var(--danger) !important;
                color: white !important;
                box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3) !important;
            }
            
            /* Badges Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-badge {
                background: var(--glass-bg) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 8px !important;
                color: var(--text-secondary) !important;
                font-weight: 600 !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-badge-color-warning {
                background: rgba(245, 158, 11, 0.2) !important;
                border-color: rgba(245, 158, 11, 0.3) !important;
                color: #fbbf24 !important;
                animation: pulse-glow 2s infinite;
            }
            
            [data-filament-panel-id="bendahara"] .fi-badge-color-success {
                background: rgba(34, 197, 94, 0.2) !important;
                border-color: rgba(34, 197, 94, 0.3) !important;
                color: #22d65f !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-badge-color-danger {
                background: rgba(239, 68, 68, 0.2) !important;
                border-color: rgba(239, 68, 68, 0.3) !important;
                color: #f87171 !important;
            }
            
            /* Page Background */
            [data-filament-panel-id="bendahara"] .fi-page-content,
            [data-filament-panel-id="bendahara"] .fi-main {
                background: 
                    radial-gradient(circle at 20% 50%, rgba(139, 92, 246, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                    linear-gradient(135deg, #020617 0%, #0f1419 100%) !important;
            }
            
            /* Header Actions Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-header-actions {
                background: var(--glass-bg) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 12px !important;
                padding: 1rem !important;
                margin-bottom: 2rem !important;
                box-shadow: var(--glass-shadow) !important;
            }
            
            @keyframes pulse-glow {
                0%, 100% {
                    opacity: 1;
                    box-shadow: 0 0 8px rgba(245, 158, 11, 0.3);
                }
                50% {
                    opacity: 0.8;
                    box-shadow: 0 0 16px rgba(245, 158, 11, 0.5);
                }
            }
            
            /* Search Input Glassmorphic */
            [data-filament-panel-id="bendahara"] .fi-input,
            [data-filament-panel-id="bendahara"] input[type="search"] {
                background: var(--glass-bg) !important;
                backdrop-filter: var(--backdrop-blur) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 8px !important;
                color: var(--text-primary) !important;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            }
            
            [data-filament-panel-id="bendahara"] .fi-input:focus,
            [data-filament-panel-id="bendahara"] input[type="search"]:focus {
                border-color: var(--info) !important;
                box-shadow: 
                    inset 0 2px 4px rgba(0, 0, 0, 0.1),
                    0 0 0 3px rgba(59, 130, 246, 0.1),
                    0 4px 12px rgba(59, 130, 246, 0.2) !important;
                background: rgba(17, 17, 24, 0.9) !important;
            }
        </style>

        <!-- Minimalist Header - Single Line -->
        <div style="
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--glass-shadow);
        ">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="
                        width: 2.5rem;
                        height: 2.5rem;
                        background: linear-gradient(135deg, var(--primary), rgba(139, 92, 246, 0.8));
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
                    ">
                        <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="
                            font-size: 1rem;
                            font-weight: 500;
                            color: var(--text-secondary);
                            margin: 0;
                            line-height: 1.4;
                        ">Professional medical procedure validation workflow</p>
                    </div>
                </div>
                
                <!-- Minimal Stats - Inline -->
                @php
                    $pending = \App\Models\Tindakan::where('status_validasi', 'pending')->count();
                    $todayApproved = \App\Models\Tindakan::where('status_validasi', 'disetujui')->whereDate('validated_at', today())->count();
                @endphp
                
                @if($pending > 0 || $todayApproved > 0)
                    <div style="display: flex; align-items: center; gap: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
                        @if($pending > 0)
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="
                                    width: 0.5rem;
                                    height: 0.5rem;
                                    background: var(--warning);
                                    border-radius: 50%;
                                    animation: pulse 2s infinite;
                                "></div>
                                <span>{{ $pending }} pending validation{{ $pending > 1 ? 's' : '' }}</span>
                            </div>
                        @endif
                        
                        @if($todayApproved > 0)
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="
                                    width: 0.5rem;
                                    height: 0.5rem;
                                    background: var(--success);
                                    border-radius: 50%;
                                "></div>
                                <span>{{ $todayApproved }} approved today</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="glass-validation">
            <!-- Render Filament Table dengan Enhanced Styling -->
            {{ $this->table }}
        </div>
        
        <!-- Custom JavaScript untuk Enhanced Interactions -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌟 Glassmorphic Validation Center - Enhanced');
            
            // Add glassmorphic glow effect ke action buttons
            setTimeout(() => {
                const actionButtons = document.querySelectorAll('[data-filament-panel-id="bendahara"] button');
                actionButtons.forEach(btn => {
                    btn.addEventListener('mouseenter', function() {
                        this.style.filter = 'brightness(1.1)';
                        this.style.boxShadow = '0 8px 32px rgba(139, 92, 246, 0.2)';
                    });
                    
                    btn.addEventListener('mouseleave', function() {
                        this.style.filter = 'brightness(1)';
                        this.style.boxShadow = '';
                    });
                });
                
                // Enhanced table row interactions
                const tableRows = document.querySelectorAll('[data-filament-panel-id="bendahara"] .fi-ta-row');
                tableRows.forEach(row => {
                    row.addEventListener('mouseenter', function() {
                        this.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                    });
                    
                    row.addEventListener('mouseleave', function() {
                        this.style.borderColor = 'rgba(255, 255, 255, 0.05)';
                    });
                });
                
            }, 1000);
        });
        </script>
    </div>
</x-filament-panels::page>