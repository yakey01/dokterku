<x-filament-panels::page>
    @php
        // Financial data untuk dashboard metrics
        $financial = $this->getFinancialSummary();
        $validation = $this->getValidationMetrics();
        $activities = $this->getRecentActivities();
        $trends = $this->getMonthlyTrends();
        
        // Calculate key metrics
        $totalPendapatan = $financial['current']['pendapatan'] ?? 0;
        $totalPengeluaran = $financial['current']['pengeluaran'] ?? 0;
        $netIncome = $totalPendapatan - $totalPengeluaran;
        $pendingValidation = $validation['total_pending'] ?? 0;
        $approvedValidation = $validation['total_approved'] ?? 0;
        $totalValidation = $pendingValidation + $approvedValidation;
        
        // Growth calculations
        $pendapatanGrowth = $financial['changes']['pendapatan'] ?? 0;
        $pengeluaranGrowth = $financial['changes']['pengeluaran'] ?? 0;
        
        // Current month data
        $currentMonth = date('F Y');
    @endphp

    <!-- WORLD-CLASS LIQUID GLASS DASHBOARD -->
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%); min-height: 100vh; padding: 2rem;">
        
        <!-- LIQUID GLASS CSS - CONTEXT7 + DRIBBBLE PATTERNS -->
        <style>
            :root {
                /* Liquid Glass Variables - Context7 Pattern */
                --glass-shadow-blur: 20px;
                --glass-shadow-spread: -5px;
                --glass-shadow-color: rgba(255, 255, 255, 0.1);
                --glass-tint-color: 255, 255, 255;
                --glass-tint-opacity: 0.05;
                --glass-frost-blur: 16px;
                --glass-outer-shadow: 24px;
                
                /* Dashboard Colors - 2024 Dark Mode Trends */
                --primary-glass: rgba(59, 130, 246, 0.1);
                --success-glass: rgba(16, 185, 129, 0.1);
                --warning-glass: rgba(245, 158, 11, 0.1);
                --danger-glass: rgba(239, 68, 68, 0.1);
                --neutral-glass: rgba(156, 163, 175, 0.1);
            }
            
            /* MINIMALIST CONTAINER - DRIBBBLE INSPIRED */
            .glass-dashboard {
                max-width: 1400px;
                margin: 0 auto;
                padding: 0;
            }
            
            /* HERO GLASS SECTION - LIQUID GLASS PATTERN */
            .hero-glass {
                position: relative;
                background: linear-gradient(135deg, rgba(17, 17, 24, 0.6) 0%, rgba(26, 26, 32, 0.4) 100%);
                backdrop-filter: blur(var(--glass-frost-blur)) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 2rem;
                padding: 3rem;
                margin-bottom: 2rem;
                isolation: isolate;
                overflow: hidden;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 
                    0 8px var(--glass-outer-shadow) rgba(0, 0, 0, 0.3),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            }
            
            .hero-glass::before {
                content: '';
                position: absolute;
                inset: 0;
                z-index: -1;
                background: linear-gradient(135deg, 
                    rgba(59, 130, 246, 0.05) 0%, 
                    rgba(139, 92, 246, 0.05) 50%, 
                    rgba(236, 72, 153, 0.05) 100%);
                border-radius: 2rem;
                transition: opacity 0.4s ease;
            }
            
            .hero-glass:hover {
                transform: translateY(-8px) scale(1.02);
                border-color: rgba(255, 255, 255, 0.15);
                box-shadow: 
                    0 20px 60px rgba(0, 0, 0, 0.4),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
            }
            
            .hero-glass:hover::before {
                opacity: 1.5;
            }
            
            /* STATS GRID - MINIMALIST GLASS CARDS */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
                margin-bottom: 3rem;
            }
            
            .glass-stat {
                position: relative;
                background: linear-gradient(135deg, rgba(17, 17, 24, 0.8) 0%, rgba(26, 26, 32, 0.6) 100%);
                backdrop-filter: blur(var(--glass-frost-blur)) saturate(150%);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 1.5rem;
                padding: 2rem;
                isolation: isolate;
                overflow: hidden;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                cursor: pointer;
                box-shadow: 
                    0 4px 16px rgba(0, 0, 0, 0.3),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            }
            
            .glass-stat::before {
                content: '';
                position: absolute;
                inset: 0;
                z-index: -1;
                background: var(--stat-color, var(--neutral-glass));
                border-radius: 1.5rem;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .glass-stat::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, 
                    var(--stat-gradient-start, #6b7280), 
                    var(--stat-gradient-end, #9ca3af));
                border-radius: 1.5rem 1.5rem 0 0;
                opacity: 0.6;
            }
            
            .glass-stat:hover {
                transform: translateY(-12px) scale(1.03);
                border-color: rgba(255, 255, 255, 0.15);
                box-shadow: 
                    0 25px 50px rgba(0, 0, 0, 0.5),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
            }
            
            .glass-stat:hover::before {
                opacity: 0.15;
            }
            
            .glass-stat:hover::after {
                opacity: 1;
                height: 4px;
                box-shadow: 0 2px 8px var(--stat-glow, rgba(255, 255, 255, 0.2));
            }
            
            /* STAT CONTENT STYLING */
            .stat-icon {
                width: 3rem;
                height: 3rem;
                background: rgba(255, 255, 255, 0.08);
                border-radius: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1.5rem;
                backdrop-filter: blur(8px);
                transition: all 0.3s ease;
            }
            
            .glass-stat:hover .stat-icon {
                background: rgba(255, 255, 255, 0.15);
                transform: scale(1.1) rotate(5deg);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            }
            
            .stat-title {
                font-size: 0.875rem;
                font-weight: 500;
                color: #9ca3af;
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            
            .stat-value {
                font-size: 2rem;
                font-weight: 800;
                color: #ffffff;
                margin-bottom: 0.5rem;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
                transition: all 0.3s ease;
            }
            
            .glass-stat:hover .stat-value {
                transform: scale(1.05);
                text-shadow: 0 4px 8px rgba(0, 0, 0, 0.6);
            }
            
            .stat-desc {
                font-size: 0.75rem;
                color: #6b7280;
                opacity: 0.8;
            }
            
            /* SPECIFIC STAT TYPES */
            .stat-pendapatan {
                --stat-color: var(--success-glass);
                --stat-gradient-start: #10b981;
                --stat-gradient-end: #059669;
                --stat-glow: rgba(16, 185, 129, 0.3);
            }
            
            .stat-pengeluaran {
                --stat-color: var(--danger-glass);
                --stat-gradient-start: #ef4444;
                --stat-gradient-end: #dc2626;
                --stat-glow: rgba(239, 68, 68, 0.3);
            }
            
            .stat-net-income {
                --stat-color: var(--primary-glass);
                --stat-gradient-start: #3b82f6;
                --stat-gradient-end: #2563eb;
                --stat-glow: rgba(59, 130, 246, 0.3);
            }
            
            .stat-validation {
                --stat-color: var(--warning-glass);
                --stat-gradient-start: #f59e0b;
                --stat-gradient-end: #d97706;
                --stat-glow: rgba(245, 158, 11, 0.3);
            }
            
            /* ACTIVITIES SECTION - GLASS DESIGN */
            .activities-glass {
                background: linear-gradient(135deg, rgba(17, 17, 24, 0.8) 0%, rgba(26, 26, 32, 0.6) 100%);
                backdrop-filter: blur(var(--glass-frost-blur)) saturate(150%);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 1.5rem;
                padding: 2rem;
                isolation: isolate;
                transition: all 0.3s ease;
                box-shadow: 
                    0 8px 32px rgba(0, 0, 0, 0.4),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            }
            
            .activities-glass:hover {
                border-color: rgba(255, 255, 255, 0.12);
                box-shadow: 
                    0 12px 40px rgba(0, 0, 0, 0.5),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
            }
            
            .activity-item {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 0.875rem;
                margin-bottom: 0.75rem;
                transition: all 0.2s ease;
                position: relative;
                overflow: hidden;
            }
            
            .activity-item::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            
            .activity-item:hover {
                background: rgba(255, 255, 255, 0.08);
                border-color: rgba(255, 255, 255, 0.12);
                transform: translateX(8px);
            }
            
            .activity-item:hover::before {
                opacity: 1;
            }
            
            /* RESPONSIVE DESIGN */
            @media (max-width: 768px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }
                
                .hero-glass {
                    padding: 2rem;
                }
                
                .glass-stat {
                    padding: 1.5rem;
                }
            }
            
            /* ANIMATION SYSTEM */
            .fade-in {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.6s ease-out;
            }
            
            .fade-in.animate {
                opacity: 1;
                transform: translateY(0);
            }
            
            .slide-up {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .slide-up.animate {
                opacity: 1;
                transform: translateY(0);
            }
        </style>

        <!-- MAIN DASHBOARD CONTAINER -->
        <div class="glass-dashboard">
            
            <!-- HERO SECTION -->
            <div class="hero-glass fade-in">
                <div style="text-align: center;">
                    <h1 style="font-size: 2.5rem; font-weight: 800; color: #ffffff; margin-bottom: 1rem; background: linear-gradient(135deg, #ffffff 0%, rgba(255, 255, 255, 0.8) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Bendahara Dashboard
                    </h1>
                    <p style="font-size: 1.125rem; color: #9ca3af; margin-bottom: 2rem; opacity: 0.9;">
                        Financial validation and reporting overview for {{ $currentMonth }}
                    </p>
                    
                    <!-- QUICK STATS -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <div style="text-align: center; padding: 1.5rem; background: rgba(255, 255, 255, 0.05); border-radius: 1rem; backdrop-filter: blur(8px);">
                            <div style="font-size: 1.75rem; font-weight: 800; color: #22d65f;">Rp {{ number_format($netIncome, 0, ',', '.') }}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Net Income</div>
                        </div>
                        <div style="text-align: center; padding: 1.5rem; background: rgba(255, 255, 255, 0.05); border-radius: 1rem; backdrop-filter: blur(8px);">
                            <div style="font-size: 1.75rem; font-weight: 800; color: #f59e0b;">{{ $totalValidation }}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Validations</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GLASS STATS GRID -->
            <div class="stats-grid">
                
                <!-- PENDAPATAN CARD -->
                <div class="glass-stat stat-pendapatan slide-up">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24">
                            <path d="M12 2v20m9-9H3"/>
                        </svg>
                    </div>
                    <div class="stat-title">Total Pendapatan</div>
                    <div class="stat-value">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
                    <div class="stat-desc">
                        <span style="color: {{ $pendapatanGrowth >= 0 ? '#22d65f' : '#ef4444' }};">
                            {{ $pendapatanGrowth >= 0 ? '+' : '' }}{{ number_format($pendapatanGrowth, 1) }}%
                        </span> 
                        bulan ini
                    </div>
                </div>

                <!-- PENGELUARAN CARD -->
                <div class="glass-stat stat-pengeluaran slide-up">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="#ef4444" viewBox="0 0 24 24">
                            <path d="M19 14l-5 5l-5-5m10-5l-5 5l-5-5"/>
                        </svg>
                    </div>
                    <div class="stat-title">Total Pengeluaran</div>
                    <div class="stat-value">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
                    <div class="stat-desc">
                        <span style="color: {{ $pengeluaranGrowth <= 0 ? '#22d65f' : '#ef4444' }};">
                            {{ $pengeluaranGrowth >= 0 ? '+' : '' }}{{ number_format($pengeluaranGrowth, 1) }}%
                        </span> 
                        bulan ini
                    </div>
                </div>

                <!-- NET INCOME CARD -->
                <div class="glass-stat stat-net-income slide-up">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="#3b82f6" viewBox="0 0 24 24">
                            <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="stat-title">Net Income</div>
                    <div class="stat-value" style="color: {{ $netIncome >= 0 ? '#22d65f' : '#ef4444' }};">
                        Rp {{ number_format($netIncome, 0, ',', '.') }}
                    </div>
                    <div class="stat-desc">{{ $netIncome >= 0 ? 'Surplus' : 'Deficit' }} periode ini</div>
                </div>

                <!-- VALIDATION CARD -->
                <div class="glass-stat stat-validation slide-up">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="#f59e0b" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="stat-title">Validasi Center</div>
                    <div class="stat-value">{{ $totalValidation }}</div>
                    <div class="stat-desc">
                        <span style="color: #f59e0b;">{{ $pendingValidation }}</span> pending, 
                        <span style="color: #22d65f;">{{ $approvedValidation }}</span> approved
                    </div>
                </div>

            </div>

            <!-- RECENT ACTIVITIES - GLASS SECTION -->
            <div class="activities-glass fade-in">
                <h2 style="font-size: 1.5rem; font-weight: 700; color: #ffffff; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 2.5rem; height: 2.5rem; background: rgba(59, 130, 246, 0.2); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center;">
                        <svg width="16" height="16" fill="#3b82f6" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    Recent Activities
                </h2>
                
                @if(!empty($activities['pendapatan']))
                    @foreach(array_slice($activities['pendapatan'], 0, 5) as $activity)
                        <div class="activity-item">
                            <div style="width: 2rem; height: 2rem; background: rgba(16, 185, 129, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="12" height="12" fill="#10b981" viewBox="0 0 24 24">
                                    <path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12s-1.536-.219-2.121-.659c-1.172-.879-1.172-2.303 0-3.182C10.464 7.781 11.232 8 12 8s1.536.219 2.121.659"/>
                                </svg>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #ffffff; margin-bottom: 0.25rem;">{{ $activity['description'] ?? 'Pendapatan' }}</div>
                                <div style="font-size: 0.875rem; color: #9ca3af;">{{ \Carbon\Carbon::parse($activity['date'] ?? now())->format('d M Y') }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: #22d65f;">Rp {{ number_format($activity['amount'] ?? 0, 0, ',', '.') }}</div>
                                <div style="font-size: 0.75rem; color: #6b7280;">{{ $activity['type'] ?? 'income' }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div style="text-align: center; padding: 3rem; color: #6b7280;">
                        <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p>No recent activities</p>
                    </div>
                @endif
            </div>

        </div>
        
        <!-- ENHANCED ANIMATION SYSTEM -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Progressive animation system with staggered timing
                const animateElements = () => {
                    const elements = document.querySelectorAll('.fade-in, .slide-up');
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry, index) => {
                            if (entry.isIntersecting) {
                                setTimeout(() => {
                                    entry.target.classList.add('animate');
                                }, index * 200);
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    elements.forEach(el => observer.observe(el));
                };
                
                // Enhanced glass effect interactions
                const enhanceGlassEffects = () => {
                    const glassElements = document.querySelectorAll('.glass-stat, .hero-glass, .activities-glass');
                    
                    glassElements.forEach(element => {
                        element.addEventListener('mouseenter', function() {
                            this.style.backdropFilter = 'blur(24px) saturate(200%)';
                        });
                        
                        element.addEventListener('mouseleave', function() {
                            this.style.backdropFilter = 'blur(16px) saturate(150%)';
                        });
                    });
                };
                
                // Initialize animations and effects
                setTimeout(() => {
                    animateElements();
                    enhanceGlassEffects();
                }, 100);
            });
        </script>
    </div>
</x-filament-panels::page>