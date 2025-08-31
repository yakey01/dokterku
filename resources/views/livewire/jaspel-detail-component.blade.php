<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%); min-height: 100vh; color: #ffffff; margin: 0; padding: 0;">
    
    <style>
        /* ULTIMATE SIDEBAR ELIMINATION */
        .fi-sidebar,
        .fi-sidebar-nav,
        .fi-sidebar-header,
        .fi-sidebar-content,
        .fi-layout-sidebar,
        aside,
        nav:not(.breadcrumb-nav):not(.top-nav) {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
            opacity: 0 !important;
            position: absolute !important;
            left: -9999px !important;
            pointer-events: none !important;
        }
        
        /* FORCE FULL WIDTH LAYOUT */
        .fi-main,
        .fi-main-content,
        .fi-page,
        .fi-page-content {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
        }
        
        .fi-layout {
            grid-template-columns: 1fr !important;
            grid-template-areas: "main" !important;
        }
        
        /* WORLD-CLASS CONTAINER SYSTEM */
        .main-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
        }
        
        /* TOP NAVIGATION BAR - SAAS PATTERN */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 10, 11, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1rem 2rem;
        }
        
        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }
        
        /* BREADCRUMB NAVIGATION - MODERN STYLE */
        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 6rem 0 2rem 0;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            backdrop-filter: blur(8px);
            font-size: 0.875rem;
        }
        
        .breadcrumb-nav a {
            color: #60a5fa;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .breadcrumb-nav a:hover {
            background: rgba(96, 165, 250, 0.1);
            color: #93c5fd;
        }
        
        .breadcrumb-separator {
            color: #6b7280;
            font-weight: 300;
        }
        
        /* CONTENT GRID - MODERN SAAS LAYOUT */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 3rem 0;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* BREAKDOWN CARDS - PREMIUM DESIGN */
        .breakdown-card {
            background: linear-gradient(135deg, rgba(17, 17, 24, 0.8) 0%, rgba(26, 26, 32, 0.6) 100%);
            backdrop-filter: blur(16px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 
                0 8px 32px -8px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            transition: all 0.3s ease;
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
        
        .breakdown-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 20px 60px -12px rgba(0, 0, 0, 0.8),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
        }
        
        .breakdown-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .breakdown-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        
        .breakdown-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }
        
        /* ITEM LIST - DRIBBBLE INSPIRED */
        .item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0.875rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .item-row:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
            transform: translateX(8px);
        }
        
        .item-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 0 0.5rem 0.5rem 0;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .item-row:hover::before {
            opacity: 1;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-title {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }
        
        .item-subtitle {
            font-size: 0.875rem;
            color: #9ca3af;
        }
        
        .item-value {
            text-align: right;
        }
        
        .item-amount {
            font-size: 1.125rem;
            font-weight: 700;
            color: #22d65f;
            margin-bottom: 0.25rem;
        }
        
        .item-detail {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .top-nav {
                padding: 0.75rem 1rem;
            }
            
            .nav-content {
                flex-wrap: wrap;
                gap: 1rem;
            }
        }
        
        /* ANIMATION CLASSES */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        
        .fade-in.animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        .slide-in-left {
            opacity: 0;
            transform: translateX(-30px);
            transition: all 0.6s ease-out;
        }
        
        .slide-in-left.animate {
            opacity: 1;
            transform: translateX(0);
        }
        
        .slide-in-right {
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.6s ease-out;
        }
        
        .slide-in-right.animate {
            opacity: 1;
            transform: translateX(0);
        }
    </style>
    
    <!-- TOP NAVIGATION WITH TITLE AND ACTIONS -->
    <nav class="top-nav">
        <div class="nav-content">
            <!-- Left Section: Brand + Page Title -->
            <div style="display: flex; align-items: center; gap: 2rem;">
                <a href="/bendahara" class="nav-brand">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    Bendahara
                </a>
                
                <div style="height: 2rem; width: 1px; background: rgba(255, 255, 255, 0.2);"></div>
                
                <h1 style="font-size: 1.125rem; font-weight: 600; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5V3z" clip-rule="evenodd" />
                    </svg>
                    Detail Rincian Jaspel
                </h1>
            </div>
            
            <!-- Right Section: Action Buttons -->
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <a href="/bendahara/laporan-jaspel" 
                   style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 0.5rem; color: #ffffff; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease;"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.transform='translateY(-1px)';"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.transform='translateY(0)';">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Kembali ke Laporan
                </a>
                
                <button wire:click="exportDetail" wire:loading.attr="disabled"
                        style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; color: #ffffff; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease; cursor: pointer; border: none;"
                        onmouseover="this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.3)'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <span wire:loading.remove wire:target="exportDetail">Export Detail</span>
                    <span wire:loading wire:target="exportDetail">Exporting...</span>
                </button>
                
                <button wire:click="refreshCalculation" wire:loading.attr="disabled"
                        style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 0.5rem; color: #ffffff; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease; cursor: pointer; border: none;"
                        onmouseover="this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.3)'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span wire:loading.remove wire:target="refreshCalculation">Refresh Calculation</span>
                    <span wire:loading wire:target="refreshCalculation">Refreshing...</span>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- MAIN CONTENT CONTAINER -->
    <div class="main-container">
        <!-- BREADCRUMB NAVIGATION -->
        <nav class="breadcrumb-nav fade-in">
            <a href="/bendahara">🏠 Dashboard</a>
            <span class="breadcrumb-separator">→</span>
            <a href="/bendahara/laporan-jaspel">📊 Laporan Jaspel</a>
            <span class="breadcrumb-separator">→</span>
            <span style="color: #ffffff; font-weight: 600;">Detail Jaspel</span>
        </nav>
        
        <!-- CONTENT AREA -->
        <div style="padding-bottom: 4rem;">
            <!-- MINIMALIST DOCTOR CARD - COMPACT DESIGN -->
            <div style="
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
            " 
            class="fade-in"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px -4px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06)';">
                
                <!-- Doctor Icon -->
                <div style="
                    width: 3rem;
                    height: 3rem;
                    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
                    border-radius: 0.75rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    backdrop-filter: blur(4px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    flex-shrink: 0;
                ">
                    <svg width="20" height="20" fill="#60a5fa" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1L9 7V9H21M3 13V11L9 5L15 11V13H3M12 20.5C13.38 20.5 14.5 19.38 14.5 18S13.38 15.5 12 15.5 9.5 16.62 9.5 18 10.62 20.5 12 20.5Z"/>
                    </svg>
                </div>
                
                <!-- Doctor Info -->
                <div style="flex: 1; min-width: 0;">
                    <h2 style="
                        font-size: 1.25rem;
                        font-weight: 700;
                        color: #ffffff;
                        margin: 0 0 0.25rem 0;
                        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
                    ">{{ $user->name ?? 'User' }}</h2>
                    <p style="
                        font-size: 0.875rem;
                        color: #9ca3af;
                        margin: 0;
                        opacity: 0.8;
                    ">Detail Rincian & Analisis Jaspel</p>
                </div>
                
                <!-- Compact Stats -->
                <div style="display: flex; gap: 2rem; align-items: center;">
                    <div style="text-align: center;">
                        <div style="
                            font-size: 1.5rem;
                            font-weight: 800;
                            color: #22d65f;
                            margin-bottom: 0.25rem;
                            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
                        " data-total-jaspel>Rp {{ number_format($procedureData['total_jaspel'] ?? 0, 0, ',', '.') }}</div>
                        <div style="
                            font-size: 0.75rem;
                            color: #6b7280;
                            font-weight: 500;
                        ">Total Jaspel</div>
                    </div>
                    
                    <div style="width: 1px; height: 2.5rem; background: rgba(255, 255, 255, 0.1);"></div>
                    
                    <div style="text-align: center;">
                        <div style="
                            font-size: 1.5rem;
                            font-weight: 800;
                            color: #60a5fa;
                            margin-bottom: 0.25rem;
                            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
                        ">{{ $procedureData['total_procedures'] ?? 0 }}</div>
                        <div style="
                            font-size: 0.75rem;
                            color: #6b7280;
                            font-weight: 500;
                        ">Total Procedures</div>
                    </div>
                </div>
            </div>

            <!-- BREAKDOWN CARDS GRID -->
            <div class="content-grid">
                <!-- TINDAKAN BREAKDOWN -->
                <div class="breakdown-card slide-in-left">
                    <div class="breakdown-header">
                        <div class="breakdown-icon" style="background: rgba(59, 130, 246, 0.2);">
                            <svg width="20" height="20" fill="#3b82f6" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="breakdown-title">🩺 Breakdown Tindakan Medis</h3>
                    </div>
                    
                    @if(!empty($procedureData['breakdown']['tindakan_procedures']))
                        <div>
                            @foreach($procedureData['breakdown']['tindakan_procedures'] as $tindakan)
                                <div class="item-row">
                                    <div class="item-info">
                                        <div class="item-title">{{ $tindakan['jenis_tindakan'] }}</div>
                                        <div class="item-subtitle">{{ \Carbon\Carbon::parse($tindakan['tanggal'])->format('d M Y') }}</div>
                                    </div>
                                    <div class="item-value">
                                        <div class="item-amount">Rp {{ number_format($tindakan['jaspel'], 0, ',', '.') }}</div>
                                        <div class="item-detail">dari Rp {{ number_format($tindakan['tarif'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 1rem; text-align: center;">
                            <div style="font-size: 1.125rem; font-weight: 600; color: #60a5fa; margin-bottom: 0.5rem;">Total Tindakan</div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #3b82f6;">Rp {{ number_format($procedureData['tindakan_jaspel'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 3rem; color: #6b7280;">
                            <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <p>Tidak ada data tindakan</p>
                        </div>
                    @endif
                </div>

                <!-- PASIEN HARIAN BREAKDOWN -->
                <div class="breakdown-card slide-in-right">
                    <div class="breakdown-header">
                        <div class="breakdown-icon" style="background: rgba(16, 185, 129, 0.2);">
                            <svg width="20" height="20" fill="#10b981" viewBox="0 0 24 24">
                                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <h3 class="breakdown-title">👥 Breakdown Pasien Harian</h3>
                    </div>
                    
                    @if(!empty($procedureData['breakdown']['pasien_harian_days']))
                        <div>
                            @foreach($procedureData['breakdown']['pasien_harian_days'] as $pasien)
                                <div class="item-row">
                                    <div class="item-info">
                                        <div class="item-title">{{ \Carbon\Carbon::parse($pasien['tanggal'])->format('d M Y') }}</div>
                                        <div class="item-subtitle">{{ $pasien['jumlah_pasien'] }} pasien</div>
                                    </div>
                                    <div class="item-value">
                                        <div class="item-amount">Rp {{ number_format($pasien['jaspel_rupiah'], 0, ',', '.') }}</div>
                                        <div class="item-detail">per hari</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 1rem; text-align: center;">
                            <div style="font-size: 1.125rem; font-weight: 600; color: #34d399; margin-bottom: 0.5rem;">Total Pasien Harian</div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #10b981;">Rp {{ number_format($procedureData['pasien_jaspel'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 3rem; color: #6b7280;">
                            <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                            <p>Tidak ada data pasien harian</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- ENHANCED JAVASCRIPT -->
    <script>
        // STABLE PAGE RENDERING (No Infinite Loops)
        document.addEventListener('DOMContentLoaded', function() {
            // Progressive animation system
            const animateElements = () => {
                const elements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry, index) => {
                        if (entry.isIntersecting) {
                            setTimeout(() => {
                                entry.target.classList.add('animate');
                            }, index * 150);
                        }
                    });
                }, { threshold: 0.1 });
                
                elements.forEach(el => observer.observe(el));
            };
            
            // Force hide sidebar elements
            const eliminateSidebar = () => {
                const sidebarSelectors = [
                    '.fi-sidebar',
                    '.fi-sidebar-nav', 
                    '.fi-sidebar-header',
                    '.fi-sidebar-content',
                    'aside',
                    'nav:not(.breadcrumb-nav):not(.top-nav)'
                ];
                
                sidebarSelectors.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => {
                        el.style.display = 'none';
                        el.style.visibility = 'hidden';
                        el.style.width = '0';
                        el.style.opacity = '0';
                        el.style.position = 'absolute';
                        el.style.left = '-9999px';
                    });
                });
            };
            
            // Initialize
            setTimeout(animateElements, 100);
            setTimeout(eliminateSidebar, 100);
            setTimeout(eliminateSidebar, 500);
        });
    </script>
</div>