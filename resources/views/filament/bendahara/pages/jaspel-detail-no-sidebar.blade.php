<x-filament-panels::page>
    <!-- SINGLE ROOT ELEMENT - LIVEWIRE COMPLIANCE -->
    <div class="world-class-jaspel-detail" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%); min-height: 100vh; color: #ffffff; margin: 0; padding: 0;">
        
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
            
            .nav-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
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
            .item-list {
                space-y: 1rem;
            }
            
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
                
                .hero-section {
                    padding: 2rem 1.5rem;
                }
                
                .hero-title {
                    font-size: 2rem;
                }
                
                .hero-stats {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }
                
                .content-grid {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }
                
                
            }
            
            /* SCROLL ENHANCEMENTS */
            .content-area {
                padding-bottom: 4rem;
            }
            
            /* WORLD-CLASS BACK BUTTON ENHANCEMENTS */
            .world-class-back-btn {
                font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1, 'pnum' 1, 'tnum' 0, 'onum' 1, 'lnum' 0, 'dlig' 0;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                text-rendering: optimizeLegibility;
            }
            
            .world-class-back-btn:hover .back-icon-container {
                transform: translateX(-3px);
            }
            
            .world-class-back-btn:hover .back-arrow {
                transform: scale(1.1);
            }
            
            .world-class-back-btn:active {
                transform: translateY(-1px) scale(0.98) !important;
            }
            
            /* RIPPLE EFFECT */
            @keyframes ripple {
                0% {
                    transform: scale(0);
                    opacity: 0.5;
                }
                100% {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(96, 165, 250, 0.3) 0%, transparent 70%);
                pointer-events: none;
                animation: ripple 0.6s ease-out forwards;
            }
            
            /* BREADCRUMB ENHANCEMENT FOR BACK NAVIGATION */
            .breadcrumb-nav a.back-highlight {
                background: rgba(96, 165, 250, 0.15);
                border: 1px solid rgba(96, 165, 250, 0.3);
                transform: scale(1.05);
            }
            
            /* KEYBOARD NAVIGATION STYLES */
            .world-class-back-btn:focus {
                outline: 2px solid rgba(96, 165, 250, 0.5);
                outline-offset: 2px;
                transform: translateY(-2px) scale(1.02);
            }
            
            /* BACK BUTTON PULSE ANIMATION FOR ATTENTION */
            @keyframes backButtonPulse {
                0%, 100% { 
                    box-shadow: 0 4px 12px rgba(96, 165, 250, 0.15); 
                }
                50% { 
                    box-shadow: 0 4px 20px rgba(96, 165, 250, 0.25), 0 0 0 2px rgba(96, 165, 250, 0.1); 
                }
            }
            
            .world-class-back-btn.pulse {
                animation: backButtonPulse 2s ease-in-out infinite;
            }
            
            /* MOBILE OPTIMIZATIONS */
            @media (max-width: 768px) {
                .world-class-back-btn {
                    padding: 0.625rem 1rem;
                    gap: 0.5rem;
                    font-size: 0.8125rem;
                }
                
                .world-class-back-btn .back-icon-container svg {
                    width: 14px;
                    height: 14px;
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
                
                <!-- Right Section: Action Buttons with Premium Back Button -->
                <div class="nav-actions" style="display: flex; align-items: center; gap: 0.75rem;">
                    <!-- WORLD-CLASS BACK BUTTON -->
                    <a href="/bendahara/laporan-jaspel" 
                       id="worldClassBackButton"
                       class="world-class-back-btn"
                       style="
                           display: inline-flex; 
                           align-items: center; 
                           gap: 0.75rem; 
                           padding: 0.75rem 1.25rem; 
                           background: linear-gradient(135deg, rgba(96, 165, 250, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%); 
                           border: 1px solid rgba(96, 165, 250, 0.3); 
                           border-radius: 0.75rem; 
                           color: #60a5fa; 
                           text-decoration: none; 
                           font-size: 0.875rem; 
                           font-weight: 600; 
                           transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                           position: relative;
                           overflow: hidden;
                           backdrop-filter: blur(8px);
                           box-shadow: 0 4px 12px rgba(96, 165, 250, 0.15);
                       "
                       onmouseover="
                           this.style.background='linear-gradient(135deg, rgba(96, 165, 250, 0.25) 0%, rgba(139, 92, 246, 0.25) 100%)'; 
                           this.style.transform='translateY(-2px) scale(1.02)'; 
                           this.style.borderColor='rgba(96, 165, 250, 0.5)';
                           this.style.boxShadow='0 8px 20px rgba(96, 165, 250, 0.25)';
                           this.style.color='#93c5fd';
                       "
                       onmouseout="
                           this.style.background='linear-gradient(135deg, rgba(96, 165, 250, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%)'; 
                           this.style.transform='translateY(0) scale(1)'; 
                           this.style.borderColor='rgba(96, 165, 250, 0.3)';
                           this.style.boxShadow='0 4px 12px rgba(96, 165, 250, 0.15)';
                           this.style.color='#60a5fa';
                       "
                       onclick="handlePremiumBackNavigation(event);"
                       data-destination="/bendahara/laporan-jaspel"
                       data-label="Laporan Jaspel"
                       title="Kembali ke Laporan Jaspel - ALT+← atau ESC">
                        
                        <!-- Animated Back Arrow with Chevron -->
                        <div style="
                            display: flex; 
                            align-items: center; 
                            position: relative;
                            transition: all 0.3s ease;
                        " class="back-icon-container">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" 
                                 style="transition: all 0.3s ease;" class="back-arrow">
                                <path d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                            </svg>
                        </div>
                        
                        <!-- Enhanced Text with Status Indicator -->
                        <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                            <span style="font-weight: 600; line-height: 1;">Kembali ke</span>
                            <span style="font-size: 0.75rem; opacity: 0.8; line-height: 1;">Laporan Jaspel</span>
                        </div>
                        
                        <!-- Keyboard Shortcut Indicator -->
                        <div style="
                            display: flex; 
                            align-items: center; 
                            gap: 0.25rem; 
                            font-size: 0.625rem; 
                            opacity: 0.6; 
                            background: rgba(0, 0, 0, 0.2); 
                            padding: 0.25rem 0.5rem; 
                            border-radius: 0.375rem;
                            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
                        ">
                            <span>ALT</span>
                            <span>+</span>
                            <span>←</span>
                        </div>
                        
                        <!-- Ripple Effect Overlay -->
                        <div class="ripple-overlay" style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            pointer-events: none;
                            border-radius: inherit;
                            overflow: hidden;
                        "></div>
                    </a>
                    
                    <button wire:click="exportDetailedBreakdown" 
                            wire:confirm="Export rincian lengkap jaspel untuk user ini?"
                            style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; color: #ffffff; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease; cursor: pointer;"
                            onmouseover="this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.3)'; this.style.transform='translateY(-1px)';"
                            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Export Detail
                    </button>
                    
                    <button wire:click="refreshCalculation" 
                            wire:confirm="Hitung ulang jaspel dari data procedures terbaru?"
                            style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 0.5rem; color: #ffffff; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease; cursor: pointer;"
                            onmouseover="this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.3)'; this.style.transform='translateY(-1px)';"
                            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh Calculation
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
            <div class="content-area">
                @php
                    try {
                        $procedureCalculator = app(\App\Services\ProcedureJaspelCalculationService::class);
                        $procedureData = $procedureCalculator->calculateJaspelFromProcedures($this->userId ?? 0, []);
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
                        \Log::warning('ProcedureJaspelCalculationService failed: ' . $e->getMessage());
                    }
                @endphp
                
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
                        ">{{ $this->user->name ?? 'User' }}</h2>
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
                            ">Rp {{ number_format($procedureData['total_jaspel'] ?? 0, 0, ',', '.') }}</div>
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
                            <div class="item-list">
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
                            <div class="item-list">
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
        
        <!-- WORLD-CLASS BACK BUTTON JAVASCRIPT -->
        <script>
            // PREMIUM BACK BUTTON FUNCTIONALITY
            function handlePremiumBackNavigation(event) {
                event.preventDefault();
                
                const backButton = event.currentTarget;
                const destination = backButton.getAttribute('data-destination');
                const label = backButton.getAttribute('data-label');
                
                // Create ripple effect
                createRippleEffect(event, backButton);
                
                // Show navigation feedback
                showNavigationFeedback(`🔄 Navigating to ${label}...`, '#60a5fa');
                
                // Add exit animation
                backButton.style.transform = 'scale(0.95)';
                backButton.style.opacity = '0.8';
                
                // Store navigation state for better UX
                if (typeof(Storage) !== "undefined") {
                    localStorage.setItem('dokterku_back_navigation', JSON.stringify({
                        from: window.location.pathname,
                        to: destination,
                        timestamp: Date.now(),
                        label: label
                    }));
                }
                
                // Smooth navigation with delay for animation
                setTimeout(() => {
                    window.location.href = destination;
                }, 200);
            }
            
            // CREATE RIPPLE EFFECT
            function createRippleEffect(event, element) {
                const rect = element.getBoundingClientRect();
                const overlay = element.querySelector('.ripple-overlay');
                
                const ripple = document.createElement('div');
                ripple.className = 'ripple-effect';
                
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                ripple.style.left = (x - 10) + 'px';
                ripple.style.top = (y - 10) + 'px';
                ripple.style.width = '20px';
                ripple.style.height = '20px';
                
                overlay.appendChild(ripple);
                
                setTimeout(() => {
                    if (overlay.contains(ripple)) {
                        overlay.removeChild(ripple);
                    }
                }, 600);
            }
            
            // KEYBOARD SHORTCUTS FOR NAVIGATION
            function initializeKeyboardShortcuts() {
                document.addEventListener('keydown', function(event) {
                    // ALT + Left Arrow = Back navigation
                    if (event.altKey && event.key === 'ArrowLeft') {
                        event.preventDefault();
                        const backButton = document.getElementById('worldClassBackButton');
                        if (backButton) {
                            backButton.click();
                        }
                    }
                    
                    // ESC = Back navigation (alternative)
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        const backButton = document.getElementById('worldClassBackButton');
                        if (backButton) {
                            // Add visual feedback for ESC key
                            backButton.classList.add('pulse');
                            setTimeout(() => {
                                backButton.classList.remove('pulse');
                                backButton.click();
                            }, 1000);
                        }
                    }
                    
                    // Show keyboard hint on Alt key
                    if (event.key === 'Alt') {
                        const backButton = document.getElementById('worldClassBackButton');
                        if (backButton) {
                            backButton.style.boxShadow = '0 8px 20px rgba(96, 165, 250, 0.35), 0 0 0 2px rgba(96, 165, 250, 0.2)';
                        }
                    }
                });
                
                document.addEventListener('keyup', function(event) {
                    if (event.key === 'Alt') {
                        const backButton = document.getElementById('worldClassBackButton');
                        if (backButton) {
                            backButton.style.boxShadow = '0 4px 12px rgba(96, 165, 250, 0.15)';
                        }
                    }
                });
            }
            
            // ENHANCED BREADCRUMB INTERACTION
            function enhanceBreadcrumbNavigation() {
                const breadcrumbLinks = document.querySelectorAll('.breadcrumb-nav a');
                breadcrumbLinks.forEach(link => {
                    link.addEventListener('mouseenter', function() {
                        // Highlight the potential back destination
                        if (this.href === document.getElementById('worldClassBackButton')?.getAttribute('data-destination')) {
                            this.classList.add('back-highlight');
                        }
                    });
                    
                    link.addEventListener('mouseleave', function() {
                        this.classList.remove('back-highlight');
                    });
                });
            }
            
            // SHOW NAVIGATION FEEDBACK
            function showNavigationFeedback(message, color) {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 6rem;
                    right: 2rem;
                    background: linear-gradient(135deg, ${color} 0%, ${color}dd 100%);
                    color: white;
                    padding: 0.875rem 1.25rem;
                    border-radius: 0.75rem;
                    box-shadow: 0 8px 24px ${color}40;
                    z-index: 9999;
                    transform: translateX(100%);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    font-weight: 600;
                    backdrop-filter: blur(12px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    font-size: 0.875rem;
                `;
                notification.innerHTML = message;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 100);
                
                setTimeout(() => {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (document.body.contains(notification)) {
                            document.body.removeChild(notification);
                        }
                    }, 300);
                }, 2000);
            }
            
            // LEGACY NOTIFICATION FUNCTION (for other buttons)
            function showNotification(message, color) {
                showNavigationFeedback(message, color);
            }
            
            function exportDetail() {
                showNotification('📥 Export functionality handled by Livewire', '#10b981');
            }
            
            function refreshCalculation() {
                showNotification('🔄 Refresh functionality handled by Livewire', '#f59e0b');
            }

            // LOADING PERFORMANCE OPTIMIZATION
            document.addEventListener('DOMContentLoaded', function() {
                // Show loading state initially
                const loadingOverlay = document.createElement('div');
                loadingOverlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(10, 10, 11, 0.95);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    backdrop-filter: blur(8px);
                `;
                loadingOverlay.innerHTML = `
                    <div style="text-align: center; color: #ffffff;">
                        <div style="width: 3rem; height: 3rem; border: 3px solid rgba(255, 255, 255, 0.3); border-top: 3px solid #60a5fa; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                        <p style="font-size: 0.875rem; opacity: 0.8;">Loading jaspel data...</p>
                    </div>
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `;
                document.body.appendChild(loadingOverlay);
                
                // Remove loading overlay after content is ready
                setTimeout(() => {
                    loadingOverlay.style.opacity = '0';
                    loadingOverlay.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        if (document.body.contains(loadingOverlay)) {
                            document.body.removeChild(loadingOverlay);
                        }
                    }, 300);
                }, 1500);
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
                
                // Force hide sidebar elements with multiple strategies
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
                
                // Initialize animations
                setTimeout(animateElements, 100);
                
                // Initialize world-class back button features
                setTimeout(() => {
                    initializeKeyboardShortcuts();
                    enhanceBreadcrumbNavigation();
                    
                    // Show welcome hint for keyboard shortcuts
                    const backButton = document.getElementById('worldClassBackButton');
                    if (backButton && !localStorage.getItem('dokterku_keyboard_hint_shown')) {
                        setTimeout(() => {
                            showNavigationFeedback('💡 Tip: Use ALT+← or ESC to navigate back quickly!', '#8b5cf6');
                            localStorage.setItem('dokterku_keyboard_hint_shown', 'true');
                        }, 2000);
                    }
                }, 300);
                
                // Multiple sidebar elimination attempts
                setTimeout(eliminateSidebar, 100);
                setTimeout(eliminateSidebar, 500);
                setTimeout(eliminateSidebar, 1000);
                
                // Add scroll-triggered animations
                window.addEventListener('scroll', () => {
                    const scrolled = window.pageYOffset;
                    const parallax = document.querySelector('.hero-section');
                    if (parallax) {
                        parallax.style.transform = `translateY(${scrolled * 0.1}px)`;
                    }
                });
            });
        </script>
    </div>
</x-filament-panels::page>