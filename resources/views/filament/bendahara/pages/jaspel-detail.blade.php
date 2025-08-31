<x-filament-panels::page>
    <!-- SINGLE ROOT ELEMENT - LIVEWIRE COMPLIANCE -->
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%); min-height: 100vh; color: #ffffff; margin: 0; padding: 0;">
        
        <!-- IMMEDIATE DATA LOADING - CONSISTENT WITH LIST PAGE FILTERS -->
        @php
            try {
                // Get same filters used in list page for data consistency
                $listFilters = session('laporan_jaspel_filters', []);
                
                $procedureCalculator = app(\App\Services\ProcedureJaspelCalculationService::class);
                $procedureData = $procedureCalculator->calculateJaspelFromProcedures($this->userId ?? 0, $listFilters);
                
                // Log filter application for debugging
                \Log::info('JaspelDetail: Applied list page filters', [
                    'user_id' => $this->userId,
                    'filters' => $listFilters,
                    'total_jaspel' => $procedureData['total_jaspel'] ?? 0
                ]);
                
            } catch (\Exception $e) {
                // Fallback data if service fails
                $procedureData = [
                    'total_jaspel' => 0,
                    'total_procedures' => 0,
                    'tindakan_jaspel' => 0,
                    'pasien_jaspel' => 0,
                    'breakdown' => [
                        'tindakan_procedures' => [],
                        'pasien_harian_days' => []
                    ]
                ];
                \Log::error('ProcedureJaspelCalculationService failed: ' . $e->getMessage());
            }
        @endphp
        
        <style>
            /* SIDEBAR ELIMINATION */
            .fi-sidebar,
            .fi-sidebar-nav,
            .fi-sidebar-header,
            .fi-sidebar-content,
            aside,
            nav:not(.breadcrumb-nav) {
                display: none !important;
            }
            
            /* FULL WIDTH LAYOUT */
            .fi-main,
            .fi-page,
            .fi-page-content {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .main-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }
            
            /* MINIMALIST CARD DESIGN */
            .doctor-card {
                display: flex;
                align-items: center;
                gap: 1.5rem;
                padding: 1.5rem 2rem;
                background: linear-gradient(135deg, rgba(17, 17, 24, 0.8) 0%, rgba(26, 26, 32, 0.6) 100%);
                backdrop-filter: blur(16px) saturate(150%);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 1rem;
                margin-bottom: 2rem;
                box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
                transition: all 0.3s ease;
            }
            
            .doctor-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
            }
            
            .doctor-icon {
                width: 3rem;
                height: 3rem;
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            
            .breakdown-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
                margin-top: 2rem;
            }
            
            @media (max-width: 768px) {
                .breakdown-grid {
                    grid-template-columns: 1fr;
                }
            }
            
            .breakdown-card {
                background: linear-gradient(135deg, rgba(17, 17, 24, 0.8) 0%, rgba(26, 26, 32, 0.6) 100%);
                backdrop-filter: blur(16px) saturate(150%);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 1.5rem;
                padding: 2rem;
                box-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
                position: relative;
                overflow: hidden;
            }
            
            .breakdown-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
                opacity: 0.6;
            }
            
            .item-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 1.5rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 0.875rem;
                margin-bottom: 1rem;
                transition: all 0.2s ease;
            }
            
            .item-row:hover {
                background: rgba(255, 255, 255, 0.08);
                transform: translateX(8px);
            }
        </style>
        
        <!-- MAIN CONTENT -->
        <div class="main-container">
            
            <!-- BACK BUTTON -->
            <div style="margin-bottom: 2rem;">
                <a href="{{ route('filament.bendahara.resources.laporan-jaspel.index') }}" 
                   style="
                       display: inline-flex;
                       align-items: center;
                       gap: 0.75rem;
                       padding: 0.75rem 1.25rem;
                       background: linear-gradient(135deg, rgba(75, 85, 99, 0.8) 0%, rgba(55, 65, 81, 0.6) 100%);
                       backdrop-filter: blur(12px);
                       border: 1px solid rgba(255, 255, 255, 0.1);
                       border-radius: 0.75rem;
                       color: #ffffff;
                       text-decoration: none;
                       font-weight: 500;
                       font-size: 0.875rem;
                       transition: all 0.2s ease;
                       box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.3);
                   "
                   onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.4)';"
                   onmouseout="this.style.transform='translateX(0px)'; this.style.boxShadow='0 2px 8px -2px rgba(0, 0, 0, 0.3)';">
                    
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                    
                    <span>Kembali ke Laporan Jaspel</span>
                </a>
            </div>
            <!-- FILTER CONTEXT INDICATOR -->
            @if(!empty($listFilters))
                <div style="
                    background: rgba(59, 130, 246, 0.1);
                    border: 1px solid rgba(59, 130, 246, 0.2);
                    border-radius: 0.75rem;
                    padding: 1rem 1.5rem;
                    margin-bottom: 1.5rem;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    color: #60a5fa;
                ">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Data Filtered</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">
                            Showing data consistent with list page filters
                            @if(isset($listFilters['date_from']) || isset($listFilters['date_to']))
                                • Date Range Applied
                            @endif
                            @if(!empty($listFilters['search']))
                                • Search: "{{ $listFilters['search'] }}"
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- MINIMALIST DOCTOR CARD -->
            <div class="doctor-card">
                <div class="doctor-icon">
                    <svg width="20" height="20" fill="#60a5fa" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1L9 7V9H21M3 13V11L9 5L15 11V13H3M12 20.5C13.38 20.5 14.5 19.38 14.5 18S13.38 15.5 12 15.5 9.5 16.62 9.5 18 10.62 20.5 12 20.5Z"/>
                    </svg>
                </div>
                
                <div style="flex: 1;">
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem 0;">{{ $this->user->name ?? 'User' }}</h2>
                    <p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">Detail Rincian & Analisis Jaspel</p>
                </div>
                
                <div style="display: flex; gap: 2rem; align-items: center;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #22d65f;">
                            Rp {{ number_format($procedureData['total_jaspel'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280;">Total Jaspel</div>
                    </div>
                    
                    <div style="width: 1px; height: 2.5rem; background: rgba(255, 255, 255, 0.1);"></div>
                    
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #60a5fa;">
                            {{ $procedureData['total_procedures'] ?? 0 }}
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280;">Total Procedures</div>
                    </div>
                </div>
            </div>

            <!-- BREAKDOWN CARDS -->
            <div class="breakdown-grid">
                <!-- TINDAKAN BREAKDOWN -->
                <div class="breakdown-card">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                        🩺 Breakdown Tindakan Medis
                    </h3>
                    
                    @if(!empty($procedureData['breakdown']['tindakan_procedures']))
                        @foreach($procedureData['breakdown']['tindakan_procedures'] as $tindakan)
                            <div class="item-row">
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ $tindakan['jenis_tindakan'] }}</div>
                                    <div style="font-size: 0.875rem; color: #9ca3af;">{{ \Carbon\Carbon::parse($tindakan['tanggal'])->format('d M Y') }}</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 700; color: #22d65f;">Rp {{ number_format($tindakan['jaspel'], 0, ',', '.') }}</div>
                                    <div style="font-size: 0.75rem; color: #6b7280;">dari Rp {{ number_format($tindakan['tarif'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @endforeach
                        
                        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 1rem; text-align: center;">
                            <div style="font-size: 1.125rem; font-weight: 600; color: #60a5fa; margin-bottom: 0.5rem;">Total Tindakan</div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #3b82f6;">Rp {{ number_format($procedureData['tindakan_jaspel'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 3rem; color: #6b7280;">
                            <p>Tidak ada data tindakan</p>
                        </div>
                    @endif
                </div>

                <!-- PASIEN HARIAN BREAKDOWN -->
                <div class="breakdown-card">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                        👥 Breakdown Pasien Harian
                    </h3>
                    
                    @if(!empty($procedureData['breakdown']['pasien_harian_days']))
                        @foreach($procedureData['breakdown']['pasien_harian_days'] as $pasien)
                            <div class="item-row">
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ \Carbon\Carbon::parse($pasien['tanggal'])->format('d M Y') }}</div>
                                    <div style="font-size: 0.875rem; color: #9ca3af;">{{ $pasien['jumlah_pasien'] }} pasien</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 700; color: #22d65f;">Rp {{ number_format($pasien['jaspel_rupiah'], 0, ',', '.') }}</div>
                                    <div style="font-size: 0.75rem; color: #6b7280;">per hari</div>
                                </div>
                            </div>
                        @endforeach
                        
                        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 1rem; text-align: center;">
                            <div style="font-size: 1.125rem; font-weight: 600; color: #34d399; margin-bottom: 0.5rem;">Total Pasien Harian</div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #10b981;">Rp {{ number_format($procedureData['pasien_jaspel'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 3rem; color: #6b7280;">
                            <p>Tidak ada data pasien harian</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>