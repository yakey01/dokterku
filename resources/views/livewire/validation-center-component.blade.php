<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    
    <!-- CSS Variables dan Glassmorphic Framework -->
    <style>
        .validation-glassmorphic {
            /* Professional Dark Color System */
            --glass-surface: rgba(10, 10, 11, 0.8);
            --glass-surface-light: rgba(17, 17, 24, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-border-hover: rgba(255, 255, 255, 0.15);
            --text-primary: #fafafa;
            --text-secondary: #e4e4e7;
            --text-muted: #a1a1aa;
            --text-subtle: #71717a;
            
            /* Semantic Colors */
            --color-success: #22c55e;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: #3b82f6;
            --color-primary: #8b5cf6;
            
            /* Glass Effects */
            --backdrop-blur: blur(20px);
            --backdrop-blur-strong: blur(40px);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --glass-shadow-hover: 0 16px 64px rgba(0, 0, 0, 0.4);
        }
        
        /* Glassmorphic Tab System */
        .glass-tab {
            position: relative;
            padding: 1rem 1.5rem;
            background: var(--glass-surface);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .glass-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .glass-tab:hover {
            background: var(--glass-surface-light);
            border-color: var(--glass-border-hover);
            color: var(--text-secondary);
            transform: translateY(-2px);
            box-shadow: var(--glass-shadow);
        }
        
        .glass-tab:hover::before {
            opacity: 1;
        }
        
        .glass-tab.active {
            background: linear-gradient(135deg, var(--color-primary), rgba(139, 92, 246, 0.8));
            border-color: var(--color-primary);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 24px rgba(139, 92, 246, 0.3);
        }
        
        .glass-tab.active::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            opacity: 1;
        }
        
        /* Glass Card System */
        .glass-card {
            background: var(--glass-surface);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .glass-card:hover {
            border-color: var(--glass-border-hover);
            box-shadow: var(--glass-shadow-hover);
            transform: translateY(-4px);
        }
        
        .glass-card:hover::before {
            opacity: 1;
        }
        
        /* Glass Input System */
        .glass-input {
            background: var(--glass-surface);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .glass-input:focus {
            outline: none;
            border-color: var(--color-info);
            box-shadow: 
                inset 0 2px 4px rgba(0, 0, 0, 0.1),
                0 0 0 3px rgba(59, 130, 246, 0.1),
                0 4px 12px rgba(59, 130, 246, 0.2);
            background: rgba(17, 17, 24, 0.9);
        }
        
        .glass-input::placeholder {
            color: var(--text-subtle);
        }
        
        /* Glass Button System */
        .glass-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--glass-surface);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        
        .glass-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .glass-btn:hover {
            background: var(--glass-surface-light);
            border-color: var(--glass-border-hover);
            color: var(--text-primary);
            transform: translateY(-2px);
            box-shadow: var(--glass-shadow);
        }
        
        .glass-btn:hover::before {
            left: 100%;
        }
        
        .glass-btn-primary {
            background: linear-gradient(135deg, var(--color-primary), rgba(139, 92, 246, 0.8));
            border-color: var(--color-primary);
            color: white;
            box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3);
        }
        
        .glass-btn-primary:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.9), rgba(124, 58, 237, 0.9));
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.4);
            color: white;
        }
        
        .glass-btn-success {
            background: linear-gradient(135deg, var(--color-success), rgba(34, 197, 94, 0.8));
            border-color: var(--color-success);
            color: white;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.3);
        }
        
        .glass-btn-success:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.9), rgba(22, 163, 74, 0.9));
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.4);
            color: white;
        }
        
        .glass-btn-danger {
            background: linear-gradient(135deg, var(--color-danger), rgba(239, 68, 68, 0.8));
            border-color: var(--color-danger);
            color: white;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
        }
        
        .glass-btn-danger:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(220, 38, 38, 0.9));
            box-shadow: 0 8px 32px rgba(239, 68, 68, 0.4);
            color: white;
        }
        
        /* Status Badge System */
        .status-glass {
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            backdrop-filter: var(--backdrop-blur);
            position: relative;
            overflow: hidden;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            animation: pulse-glow 2s infinite;
        }
        
        .status-approved {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22d65f;
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
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
        
        /* Data Row System */
        .data-row {
            background: var(--glass-surface);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .data-row::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.02), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .data-row:hover {
            border-color: var(--glass-border-hover);
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .data-row:hover::before {
            opacity: 1;
        }
    </style>

    <!-- Main Validation Interface -->
    <div class="validation-glassmorphic" style="position: relative; z-index: 1;">
        
        <!-- Professional Header dengan Quick Stats -->
        <div class="glass-card" style="padding: 2rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div style="
                        width: 4rem;
                        height: 4rem;
                        background: linear-gradient(135deg, var(--color-primary), rgba(139, 92, 246, 0.7));
                        border-radius: 16px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);
                        backdrop-filter: var(--backdrop-blur);
                    ">
                        <svg style="width: 2rem; height: 2rem; color: white;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 style="
                            font-size: 2rem;
                            font-weight: 700;
                            color: var(--text-primary);
                            margin: 0 0 0.5rem 0;
                            line-height: 1.2;
                            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                        ">Validation Center</h1>
                        <p style="
                            font-size: 1rem;
                            color: var(--text-muted);
                            margin: 0;
                        ">Professional medical procedure validation workflow</p>
                    </div>
                </div>
                
                <!-- Live Stats -->
                <div style="display: flex; gap: 1rem;">
                    <div style="
                        text-align: center;
                        padding: 1rem 1.25rem;
                        background: var(--glass-surface);
                        backdrop-filter: var(--backdrop-blur);
                        border: 1px solid var(--glass-border);
                        border-radius: 12px;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='var(--glass-border-hover)';" onmouseout="this.style.transform='scale(1)'; this.style.borderColor='var(--glass-border)';">
                        <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-warning);">{{ $this->validationStats['pending'] }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Pending</div>
                    </div>
                    
                    <div style="
                        text-align: center;
                        padding: 1rem 1.25rem;
                        background: var(--glass-surface);
                        backdrop-filter: var(--backdrop-blur);
                        border: 1px solid var(--glass-border);
                        border-radius: 12px;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='var(--glass-border-hover)';" onmouseout="this.style.transform='scale(1)'; this.style.borderColor='var(--glass-border)';">
                        <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-success);">{{ $this->validationStats['today_approved'] }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Today</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Glassmorphic Tab Navigation -->
        <div style="display: flex; margin-bottom: 2rem; background: var(--glass-surface); backdrop-filter: var(--backdrop-blur); border: 1px solid var(--glass-border); border-radius: 16px; padding: 0.5rem; box-shadow: var(--glass-shadow);">
            <button 
                wire:click="setActiveTab('pending')"
                class="glass-tab {{ $activeTab === 'pending' ? 'active' : '' }}"
                style="flex: 1; border-radius: 12px; border: {{ $activeTab === 'pending' ? '1px solid var(--color-primary)' : '1px solid transparent' }};">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Pending Review</span>
                    <div class="status-glass status-pending">{{ $this->validationStats['pending'] }}</div>
                </div>
            </button>
            
            <button 
                wire:click="setActiveTab('approved')"
                class="glass-tab {{ $activeTab === 'approved' ? 'active' : '' }}"
                style="flex: 1; border-radius: 12px; border: {{ $activeTab === 'approved' ? '1px solid var(--color-primary)' : '1px solid transparent' }};">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Approved</span>
                    <div class="status-glass status-approved">{{ $this->validationStats['approved'] }}</div>
                </div>
            </button>
            
            <button 
                wire:click="setActiveTab('rejected')"
                class="glass-tab {{ $activeTab === 'rejected' ? 'active' : '' }}"
                style="flex: 1; border-radius: 12px; border: {{ $activeTab === 'rejected' ? '1px solid var(--color-primary)' : '1px solid transparent' }};">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Rejected</span>
                    <div class="status-glass status-rejected">{{ $this->validationStats['rejected'] }}</div>
                </div>
            </button>
            
            <button 
                wire:click="setActiveTab('all')"
                class="glass-tab {{ $activeTab === 'all' ? 'active' : '' }}"
                style="flex: 1; border-radius: 12px; border: {{ $activeTab === 'all' ? '1px solid var(--color-primary)' : '1px solid transparent' }};">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>All Records</span>
                    <div style="
                        padding: 0.25rem 0.5rem;
                        background: rgba(255, 255, 255, 0.1);
                        border-radius: 6px;
                        font-size: 0.7rem;
                        color: white;
                        font-weight: 700;
                    ">{{ $this->validationStats['all'] }}</div>
                </div>
            </button>
        </div>

        <!-- Search dan Actions Bar -->
        <div class="glass-card" style="padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <!-- Search Input dengan Icon -->
                <div style="position: relative; flex: 1; max-width: 20rem;">
                    <input 
                        wire:model.live="search"
                        type="text" 
                        placeholder="Search procedures, patients..."
                        class="glass-input"
                        style="width: 100%; padding-left: 2.5rem;"
                    >
                    <svg style="
                        position: absolute;
                        left: 0.75rem;
                        top: 50%;
                        transform: translateY(-50%);
                        width: 1rem;
                        height: 1rem;
                        color: var(--text-subtle);
                        pointer-events: none;
                    " fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 0.75rem;">
                    <button class="glass-btn glass-btn-success">
                        <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Quick Approve</span>
                    </button>
                    
                    <button class="glass-btn glass-btn-primary">
                        <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Batch Review</span>
                    </button>
                    
                    <button 
                        wire:click="$refresh"
                        class="glass-btn"
                        title="Refresh Data"
                        style="aspect-ratio: 1;"
                    >
                        <svg style="width: 1rem; height: 1rem; transition: transform 0.3s ease;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            
            <!-- Main Data View -->
            <div class="glass-card" style="padding: 2rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                    <h2 style="
                        font-size: 1.25rem;
                        font-weight: 600;
                        color: var(--text-primary);
                        margin: 0;
                    ">
                        @if($activeTab === 'pending')
                            🕐 Pending Validations
                        @elseif($activeTab === 'approved')
                            ✅ Approved Items  
                        @elseif($activeTab === 'rejected')
                            ❌ Rejected Items
                        @else
                            📋 All Records
                        @endif
                    </h2>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <select wire:model.live="perPage" class="glass-input" style="width: auto; font-size: 0.75rem;">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                        </select>
                    </div>
                </div>
                
                <!-- Data Rows dengan Glassmorphic Effects -->
                <div style="max-height: 32rem; overflow-y: auto;">
                    @forelse($this->validationData as $item)
                        <div class="data-row" style="position: relative;">
                            <div style="position: relative; z-index: 2;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                                    <div style="flex: 1; min-width: 0;">
                                        <h3 style="
                                            font-size: 0.975rem;
                                            font-weight: 600;
                                            color: var(--text-primary);
                                            margin: 0 0 0.25rem 0;
                                            overflow: hidden;
                                            text-overflow: ellipsis;
                                            white-space: nowrap;
                                        ">{{ $item->jenisTindakan->nama ?? 'Unknown Procedure' }}</h3>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                                👤 {{ $item->pasien->nama ?? 'Unknown Patient' }}
                                            </span>
                                            <span style="font-size: 0.75rem; color: var(--text-subtle);">
                                                📅 {{ $item->tanggal_tindakan?->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div style="text-align: right; margin-left: 1rem;">
                                        <div style="font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
                                            Rp {{ number_format($item->tarif, 0, ',', '.') }}
                                        </div>
                                        <div class="status-glass status-{{ $item->status_validasi === 'disetujui' ? 'approved' : ($item->status_validasi === 'ditolak' ? 'rejected' : 'pending') }}">
                                            {{ ucfirst($item->status_validasi) }}
                                        </div>
                                    </div>
                                </div>
                                
                                @if($item->status_validasi === 'pending')
                                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                        <button 
                                            wire:click="quickApprove({{ $item->id }})"
                                            class="glass-btn glass-btn-success"
                                            style="font-size: 0.75rem; padding: 0.5rem 0.75rem;"
                                        >
                                            <svg style="width: 0.875rem; height: 0.875rem;" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 12l2 2 4-4"/>
                                            </svg>
                                            <span>Approve</span>
                                        </button>
                                        
                                        <button 
                                            wire:click="quickReject({{ $item->id }})"
                                            class="glass-btn glass-btn-danger"
                                            style="font-size: 0.75rem; padding: 0.5rem 0.75rem;"
                                        >
                                            <svg style="width: 0.875rem; height: 0.875rem;" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Reject</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="glass-card" style="
                            padding: 3rem;
                            text-align: center;
                            background: rgba(139, 92, 246, 0.05);
                            border: 2px dashed rgba(139, 92, 246, 0.2);
                        ">
                            <svg style="
                                width: 4rem;
                                height: 4rem;
                                color: var(--color-primary);
                                margin: 0 auto 1rem;
                                opacity: 0.6;
                            " fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 style="
                                font-size: 1.25rem;
                                font-weight: 600;
                                color: var(--text-primary);
                                margin: 0 0 0.5rem 0;
                            ">No {{ $activeTab === 'all' ? '' : $activeTab }} records found</h3>
                            <p style="
                                font-size: 0.875rem;
                                color: var(--text-muted);
                                margin: 0;
                            ">{{ $search ? 'Try adjusting your search criteria' : 'All items have been processed' }}</p>
                        </div>
                    @endforelse
                </div>
                
                <!-- Pagination -->
                @if($this->validationData->hasPages())
                    <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
                        {{ $this->validationData->links() }}
                    </div>
                @endif
            </div>
            
            <!-- Sidebar dengan Activity Feed -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <!-- Recent Activity -->
                <div class="glass-card" style="padding: 1.5rem;">
                    <h3 style="
                        font-size: 1rem;
                        font-weight: 600;
                        color: var(--text-primary);
                        margin: 0 0 1rem 0;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    ">
                        <svg style="width: 1.125rem; height: 1.125rem; color: var(--color-success);" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Recent Activity
                    </h3>
                    
                    <div style="space-y: 0.75rem;">
                        @foreach($this->recentActivity as $activity)
                            <div style="
                                padding: 0.75rem;
                                background: var(--glass-surface);
                                backdrop-filter: var(--backdrop-blur);
                                border: 1px solid var(--glass-border);
                                border-radius: 8px;
                                margin-bottom: 0.75rem;
                                transition: all 0.3s ease;
                            " onmouseover="this.style.borderColor='var(--glass-border-hover)'; this.style.transform='translateX(4px)';" onmouseout="this.style.borderColor='var(--glass-border)'; this.style.transform='translateX(0)';">
                                <div style="
                                    font-size: 0.875rem;
                                    font-weight: 500;
                                    color: var(--text-primary);
                                    margin: 0 0 0.25rem 0;
                                ">{{ $activity['procedure'] }}</div>
                                <div style="
                                    font-size: 0.75rem;
                                    color: var(--text-muted);
                                    margin: 0 0 0.5rem 0;
                                ">👤 {{ $activity['patient'] }}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div class="status-glass status-{{ $activity['status'] === 'disetujui' ? 'approved' : ($activity['status'] === 'ditolak' ? 'rejected' : 'pending') }}">
                                        {{ ucfirst($activity['status']) }}
                                    </div>
                                    <span style="
                                        font-size: 0.6875rem;
                                        color: var(--text-subtle);
                                    ">{{ $activity['date']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Quick Actions Panel -->
                <div class="glass-card" style="padding: 1.5rem;">
                    <h3 style="
                        font-size: 1rem;
                        font-weight: 600;
                        color: var(--text-primary);
                        margin: 0 0 1rem 0;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    ">
                        <svg style="width: 1.125rem; height: 1.125rem; color: var(--color-info);" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Quick Actions
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/bendahara/validation-center" class="glass-btn" style="text-decoration: none; justify-content: center;">
                            <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                            </svg>
                            <span>Full Data Table</span>
                        </a>
                        
                        <button class="glass-btn" style="justify-content: center;">
                            <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Export Report</span>
                        </button>
                        
                        <button class="glass-btn" style="justify-content: center;">
                            <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Settings</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div style="
                position: fixed;
                top: 6rem;
                right: 2rem;
                z-index: 1000;
                background: var(--glass-surface);
                backdrop-filter: var(--backdrop-blur-strong);
                border: 1px solid rgba(34, 197, 94, 0.3);
                border-radius: 12px;
                padding: 1rem 1.5rem;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                animation: slideInRight 0.3s ease-out;
            ">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: var(--color-success);" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span style="color: var(--text-primary); font-weight: 500;">{{ session('message') }}</span>
                </div>
            </div>
        @endif

        <!-- Loading Overlay -->
        <div wire:loading style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: var(--backdrop-blur);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            <div style="
                background: var(--glass-surface);
                backdrop-filter: var(--backdrop-blur-strong);
                border: 1px solid var(--glass-border);
                border-radius: 16px;
                padding: 2rem;
                text-align: center;
                box-shadow: 0 16px 64px rgba(0, 0, 0, 0.5);
            ">
                <div style="
                    width: 3rem;
                    height: 3rem;
                    border: 3px solid var(--glass-border);
                    border-top: 3px solid var(--color-primary);
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <p style="color: var(--text-primary); font-weight: 500; margin: 0;">Processing...</p>
            </div>
        </div>
        
        <style>
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>
    </div>
</div>