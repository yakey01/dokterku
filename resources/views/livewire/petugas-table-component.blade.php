<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;">
    <!-- PURE INLINE STYLES - COMPLETE CSS ISOLATION -->
    
    <style>
        /* Minimalist Glass Variables */
        .petugas-root {
            --glass-bg-primary: rgba(255, 255, 255, 0.05);
            --glass-bg-hover: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-border-hover: rgba(255, 255, 255, 0.12);
            --text-primary: #ffffff;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
        }
        
        .glass-container {
            background: var(--glass-bg-primary) !important;
            backdrop-filter: blur(12px) saturate(110%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(110%) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 1rem !important;
            box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), 0 2px 8px -2px rgba(0, 0, 0, 0.3), inset 0 1px 0 0 rgba(255, 255, 255, 0.06) !important;
            transition: all 0.3s ease !important;
        }
        
        .glass-container:hover {
            background: var(--glass-bg-hover) !important;
            backdrop-filter: blur(16px) saturate(115%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(115%) !important;
            border-color: var(--glass-border-hover) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 24px -8px rgba(0, 0, 0, 0.5), 0 4px 12px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
        }
    </style>
    
    <!-- SINGLE ROOT ELEMENT - LIVEWIRE COMPLIANT -->
    <div class="petugas-root" style="background: linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%); min-height: 100vh; padding: 2rem; color: #ffffff;">
        
        <!-- Page Header -->
        <div style="margin-bottom: 2rem; text-align: center;">
            <h1 style="font-size: 2rem; font-weight: 700; color: #ffffff; margin-bottom: 0.5rem; letter-spacing: -0.025em;">
                ✨ Data Jumlah Pasien Harian
            </h1>
            <p style="font-size: 1rem; color: #d1d5db; font-weight: 400;">
                Minimalist glass table design dengan modern SaaS aesthetic
            </p>
        </div>

        <!-- Search Bar -->
        <div style="margin-bottom: 1.5rem; text-align: center;">
            <input 
                type="text" 
                wire:model.live="search"
                placeholder="🔍 Search data..."
                style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; max-width: 320px; font-size: 0.875rem;"
                onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='rgba(99, 102, 241, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                onblur="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.12)'; this.style.boxShadow='none';"
            />
        </div>

        <!-- Table Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 8px; backdrop-filter: blur(8px);">
            <h3 style="color: #ffffff; font-weight: 600; font-size: 1rem; margin: 0;">📊 Data Management</h3>
            <div style="display: flex; gap: 0.75rem;">
                <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.create') }}" 
                   style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; color: white; padding: 0.75rem 1rem; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;"
                   onmouseover="this.style.background='linear-gradient(135deg, #4f46e5 0%, #4338ca 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px -1px rgba(99, 102, 241, 0.3)';"
                   onmouseout="this.style.background='linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)'; this.style.transform=''; this.style.boxShadow='';">
                    ➕ Add New
                </a>
            </div>
        </div>

        <!-- Glass Table Container -->
        <div class="glass-container" style="overflow: hidden; margin: 0 auto; max-width: 1200px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; color: #ffffff; background: transparent;">
                
                <!-- Table Headers -->
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.08); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                        <th wire:click="sortBy('tanggal')" 
                            style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: left; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)';"
                            onmouseout="this.style.backgroundColor='transparent';">
                            📅 Tanggal 
                            @if($sortField === 'tanggal')
                                <span style="margin-left: 0.5rem;">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: left;">🏥 Poli</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: left;">⏰ Shift</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: left;">👨‍⚕️ Doctor</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: center;">👥 General</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: center;">🆔 BPJS</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: center;">📊 Total</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: left;">✅ Status</th>
                        <th style="background: transparent; color: #ffffff; font-weight: 600; font-size: 0.8125rem; letter-spacing: 0.025em; text-transform: uppercase; padding: 1rem 1.25rem; text-align: left;">⚙️ Actions</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($data as $record)
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #ffffff; transition: all 0.25s ease; position: relative;"
                        onmouseover="this.style.background='rgba(255, 255, 255, 0.06)'; this.style.borderBottomColor='rgba(255, 255, 255, 0.12)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 8px -2px rgba(0, 0, 0, 0.2)'; this.style.cursor='pointer';"
                        onmouseout="this.style.background='rgba(255, 255, 255, 0.02)'; this.style.borderBottomColor='rgba(255, 255, 255, 0.05)'; this.style.transform=''; this.style.boxShadow=''; this.style.cursor='';">
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 600; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem; transition: all 0.2s ease;">
                            {{ $record->tanggal->format('d/m/Y') }}
                        </td>
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 500; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem;">
                            <span style="background: {{ $record->poli === 'umum' ? 'rgba(99, 102, 241, 0.15)' : 'rgba(34, 197, 94, 0.15)' }}; border: 1px solid {{ $record->poli === 'umum' ? 'rgba(99, 102, 241, 0.2)' : 'rgba(34, 197, 94, 0.2)' }}; border-radius: 0.5rem; color: {{ $record->poli === 'umum' ? '#a5b4fc' : '#86efac' }}; font-weight: 500; font-size: 0.8125rem; padding: 0.25rem 0.75rem; transition: all 0.2s ease; display: inline-flex; align-items: center;">
                                {{ $record->poli === 'umum' ? 'Poli Umum' : 'Poli Gigi' }}
                            </span>
                        </td>
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 500; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem;">
                            <span style="background: {{ $record->shift === 'Pagi' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(6, 182, 212, 0.15)' }}; border: 1px solid {{ $record->shift === 'Pagi' ? 'rgba(245, 158, 11, 0.2)' : 'rgba(6, 182, 212, 0.2)' }}; border-radius: 0.5rem; color: {{ $record->shift === 'Pagi' ? '#fcd34d' : '#67e8f9' }}; font-weight: 500; font-size: 0.8125rem; padding: 0.25rem 0.75rem;">
                                {{ $record->shift === 'Pagi' ? '🌅 Pagi' : '🌇 Sore' }}
                            </span>
                        </td>
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 500; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem;">
                            @if($record->dokter)
                                <strong style="color: #86efac; font-weight: 600;">{{ $record->dokter->nama_lengkap }}</strong><br>
                                <small style="color: #9ca3af; font-size: 0.75rem;">NIK: {{ $record->dokter->nik }}{{ $record->dokter->nomor_sip ? ' | SIP: ' . $record->dokter->nomor_sip : '' }}</small>
                            @else
                                <span style="color: #f87171;">Dokter tidak ditemukan</span>
                            @endif
                        </td>
                        
                        <td style="background: transparent; color: #a5b4fc; font-weight: 600; font-size: 1rem; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem; text-align: center;">
                            {{ $record->jumlah_pasien_umum }}
                        </td>
                        
                        <td style="background: transparent; color: #67e8f9; font-weight: 600; font-size: 1rem; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem; text-align: center;">
                            {{ $record->jumlah_pasien_bpjs }}
                        </td>
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 500; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem; text-align: center;">
                            <span style="background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 0.5rem; color: #86efac; font-weight: 600; font-size: 0.8125rem; padding: 0.25rem 0.75rem;">
                                {{ $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs }}
                            </span>
                        </td>
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 500; border-right: 1px solid rgba(255, 255, 255, 0.03); padding: 1rem 1.25rem;">
                            <span style="background: {{ $record->status_validasi === 'approved' ? 'rgba(34, 197, 94, 0.15)' : ($record->status_validasi === 'rejected' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(245, 158, 11, 0.15)') }}; border: 1px solid {{ $record->status_validasi === 'approved' ? 'rgba(34, 197, 94, 0.2)' : ($record->status_validasi === 'rejected' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(245, 158, 11, 0.2)') }}; border-radius: 0.5rem; color: {{ $record->status_validasi === 'approved' ? '#86efac' : ($record->status_validasi === 'rejected' ? '#fca5a5' : '#fcd34d') }}; font-weight: 500; font-size: 0.8125rem; padding: 0.25rem 0.75rem;">
                                {{ $record->status_validasi === 'approved' ? '✅ Disetujui' : ($record->status_validasi === 'rejected' ? '❌ Ditolak' : '⏳ Menunggu') }}
                            </span>
                        </td>
                        
                        <td style="background: transparent; color: #ffffff; font-weight: 500; padding: 1rem 1.25rem;">
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.edit', $record) }}"
                                   style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 6px; color: #ffffff; font-weight: 500; padding: 0.5rem 0.75rem; transition: all 0.15s ease; cursor: pointer; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.25rem; text-decoration: none;"
                                   onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='rgba(255, 255, 255, 0.2)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 4px -2px rgba(0, 0, 0, 0.1)';"
                                   onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.12)'; this.style.transform=''; this.style.boxShadow='';">
                                    ✏️ Edit
                                </a>
                                
                                <button wire:click="$dispatch('delete-record', {{ $record->id }})"
                                        style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; color: #fca5a5; font-weight: 500; padding: 0.5rem 0.75rem; transition: all 0.15s ease; cursor: pointer; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.25rem;"
                                        onmouseover="this.style.background='rgba(239, 68, 68, 0.25)'; this.style.borderColor='rgba(239, 68, 68, 0.3)'; this.style.transform='translateY(-1px)';"
                                        onmouseout="this.style.background='rgba(239, 68, 68, 0.15)'; this.style.borderColor='rgba(239, 68, 68, 0.2)'; this.style.transform='';">
                                    🗑️ Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="background: transparent; color: #9ca3af; font-weight: 400; padding: 3rem; text-align: center; font-style: italic;">
                            Belum ada data pasien harian. <br>
                            <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.create') }}" style="color: #a5b4fc; text-decoration: underline;">Input data pertama</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
            {{ $data->links('pagination::default') }}
        </div>
        
        <!-- Info Box -->
        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; backdrop-filter: blur(8px); text-align: center;">
            <strong style="color: #a5b4fc;">🎯 Elegant Glass Design Active:</strong>
            <span style="color: #d1d5db;"> Modern minimalist table dengan glassmorphism effects berdasarkan analisis top SaaS platforms</span>
        </div>

    </div>
</div>