<div class="petugas-edit-root" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; --glass-bg-primary: rgba(255, 255, 255, 0.05); --glass-bg-hover: rgba(255, 255, 255, 0.08); --glass-border: rgba(255, 255, 255, 0.08); --glass-border-hover: rgba(255, 255, 255, 0.12); --text-primary: #ffffff; --text-secondary: #d1d5db; --text-muted: #9ca3af; --input-bg: rgba(255, 255, 255, 0.08); --input-border: rgba(255, 255, 255, 0.12); --input-focus: rgba(99, 102, 241, 0.4); background: linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%); min-height: 100vh; padding: 2rem; color: #ffffff;">
    
    <!-- Page Header -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div>
                <h1 style="font-size: 1.875rem; font-weight: 700; color: #ffffff; margin: 0; letter-spacing: -0.025em;">
                    ✏️ Edit Data Pasien
                </h1>
                <p style="font-size: 1rem; color: #d1d5db; margin: 0.5rem 0 0 0;">
                    Ubah data jumlah pasien harian dengan elegant glass form
                </p>
            </div>
            
            <button wire:click="cancel" 
                    style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; color: #d1d5db; font-weight: 500; padding: 0.75rem 1rem; transition: all 0.2s ease; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"
                    onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='rgba(255, 255, 255, 0.2)'; this.style.color='#ffffff'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.12)'; this.style.color='#d1d5db'; this.style.transform='';">
                ← Kembali
            </button>
        </div>
        
        <!-- Breadcrumb -->
        <div style="display: flex; align-items: center; gap: 0.5rem; color: #9ca3af; font-size: 0.875rem;">
            <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}" style="color: #a5b4fc; text-decoration: none; transition: color 0.2s ease;" onmouseover="this.style.color='#ffffff';" onmouseout="this.style.color='#a5b4fc';">
                Data Jumlah Pasien
            </a>
            <span style="color: #6b7280;">›</span>
            <span style="color: #d1d5db;">Edit Record #{{ $record->id }}</span>
        </div>
    </div>

    <!-- Form Container -->
    <div style="background: var(--glass-bg-primary); backdrop-filter: blur(12px) saturate(110%); -webkit-backdrop-filter: blur(12px) saturate(110%); border: 1px solid var(--glass-border); border-radius: 1rem; box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), 0 2px 8px -2px rgba(0, 0, 0, 0.3), inset 0 1px 0 0 rgba(255, 255, 255, 0.06); transition: all 0.3s ease; overflow: hidden; margin: 0 auto; max-width: 800px;">
        
        <form wire:submit="save">
            <!-- Form Header -->
            <div style="padding: 1.5rem 2rem; background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                    📊 Form Edit Data Pasien
                </h2>
                <p style="font-size: 0.875rem; color: #d1d5db; margin: 0.5rem 0 0 0;">
                    Silakan ubah data sesuai kebutuhan. Status validasi akan di-reset jika mengubah data kritis.
                </p>
            </div>

            <!-- Form Content -->
            <div style="padding: 2rem;">
                
                <!-- Date and Poli Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    
                    <!-- Date Input -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            📅 Tanggal
                        </label>
                        <input type="date" 
                               wire:model.live="tanggal"
                               max="{{ date('Y-m-d') }}"
                               style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                               onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                               onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                        @error('tanggal') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Poli Select -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            🏥 Poli
                        </label>
                        <select wire:model.live="poli"
                                style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                                onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                                onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                            <option value="umum">Poli Umum</option>
                            <option value="gigi">Poli Gigi</option>
                        </select>
                        @error('poli') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Shift and Doctor Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    
                    <!-- Shift Select -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            ⏰ Shift
                        </label>
                        <select wire:model.live="shift"
                                style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                                onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                                onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                            <option value="Pagi">🌅 Pagi</option>
                            <option value="Sore">🌇 Sore</option>
                            <option value="Hari Libur Besar">🏖️ Hari Libur Besar</option>
                        </select>
                        @error('shift') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Doctor Select -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            👨‍⚕️ Dokter Pelaksana
                        </label>
                        <select wire:model.live="dokter_id"
                                style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                                onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                                onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                            <option value="">Pilih Dokter...</option>
                            @foreach($availableDokters as $dokter)
                                <option value="{{ $dokter->id }}">
                                    {{ $dokter->nama_lengkap }} - {{ $dokter->nik }}
                                </option>
                            @endforeach
                        </select>
                        @error('dokter_id') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Patient Count Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    
                    <!-- Pasien Umum -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            👥 Pasien Umum
                        </label>
                        <input type="number" 
                               wire:model.live="jumlah_pasien_umum"
                               min="0" max="500" step="1"
                               style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                               onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                               onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                        @error('jumlah_pasien_umum') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Pasien BPJS -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            🆔 Pasien BPJS
                        </label>
                        <input type="number" 
                               wire:model.live="jumlah_pasien_bpjs"
                               min="0" max="500" step="1"
                               style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                               onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                               onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                        @error('jumlah_pasien_bpjs') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Total Display -->
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                            📊 Total Pasien
                        </label>
                        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 8px; color: #86efac; font-weight: 600; padding: 0.75rem 1rem; font-size: 1rem; text-align: center;">
                            {{ ($jumlah_pasien_umum ?? 0) + ($jumlah_pasien_bpjs ?? 0) }} pasien
                        </div>
                    </div>
                </div>

                <!-- Jadwal Jaga (Optional) -->
                @if(count($availableJadwal) > 0)
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                        📋 Jadwal Jaga (Opsional)
                    </label>
                    <select wire:model.live="jadwal_jaga_id"
                            style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem;"
                            onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                            onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';">
                        <option value="">Tidak ada jadwal jaga spesifik</option>
                        @foreach($availableJadwal as $jadwal)
                            <option value="{{ $jadwal->id }}">
                                {{ $jadwal->shiftTemplate?->nama_shift ?? $jadwal->jam_shift }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Notes -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                        📝 Catatan (Opsional)
                    </label>
                    <textarea wire:model="catatan" 
                              rows="3" 
                              maxlength="500"
                              placeholder="Catatan tambahan jika diperlukan..."
                              style="background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: #ffffff; font-weight: 400; padding: 0.75rem 1rem; transition: all 0.2s ease; width: 100%; font-size: 0.875rem; resize: vertical; min-height: 80px;"
                              onfocus="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='var(--input-focus)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                              onblur="this.style.background='var(--input-bg)'; this.style.borderColor='var(--input-border)'; this.style.boxShadow='none';"></textarea>
                    @error('catatan') <span style="color: #fca5a5; font-size: 0.8125rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                </div>

                <!-- Current Status Display -->
                <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="color: #a5b4fc; font-size: 0.875rem; font-weight: 600; margin: 0 0 0.5rem 0;">ℹ️ Status Saat Ini</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; font-size: 0.8125rem;">
                        <div style="color: #d1d5db;">
                            <strong>Status:</strong> 
                            <span style="color: {{ $record->status_validasi === 'approved' ? '#86efac' : ($record->status_validasi === 'rejected' ? '#fca5a5' : '#fcd34d') }};">
                                {{ $record->status_validasi === 'approved' ? 'Disetujui' : ($record->status_validasi === 'rejected' ? 'Ditolak' : 'Menunggu') }}
                            </span>
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Input by:</strong> {{ $record->inputBy?->name ?? 'System' }}
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Created:</strong> {{ $record->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @if(in_array($record->status_validasi, ['approved', 'disetujui']))
                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 6px;">
                        <span style="color: #fcd34d; font-size: 0.8125rem;">
                            ⚠️ <strong>Perhatian:</strong> Data ini sudah disetujui. Mengubah data akan me-reset status validasi ke "Menunggu".
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Form Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                    
                    <button type="button" 
                            wire:click="cancel"
                            style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; color: #d1d5db; font-weight: 500; padding: 0.875rem 1.5rem; transition: all 0.2s ease; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"
                            onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='rgba(255, 255, 255, 0.2)'; this.style.color='#ffffff'; this.style.transform='translateY(-1px)';"
                            onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.12)'; this.style.color='#d1d5db'; this.style.transform='';">
                        ❌ Batal
                    </button>
                    
                    <button type="submit" 
                            style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; color: #ffffff; font-weight: 600; padding: 0.875rem 2rem; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"
                            onmouseover="this.style.background='linear-gradient(135deg, #047857 0%, #065f46 100%)'; this.style.transform='translateY(-1px) scale(1.02)'; this.style.boxShadow='0 8px 16px -4px rgba(5, 150, 105, 0.3)';"
                            onmouseout="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'; this.style.transform=''; this.style.boxShadow='';">
                        ✅ Update Data
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Info Panel -->
    <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 8px; backdrop-filter: blur(8px); text-align: center; max-width: 800px; margin-left: auto; margin-right: auto;">
        <strong style="color: #86efac;">✨ Elegant Glass Form Design:</strong>
        <span style="color: #d1d5db;"> Form edit dengan minimalist glass aesthetic yang konsisten dengan table design</span>
    </div>
    
    <!-- Notification Scripts - INSIDE SINGLE ROOT -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (event) => {
                const { type, title, message } = event[0];
                
                // Create notification element
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 2rem;
                    right: 2rem;
                    background: ${type === 'success' ? 'rgba(34, 197, 94, 0.15)' : 'rgba(245, 158, 11, 0.15)'};
                    border: 1px solid ${type === 'success' ? 'rgba(34, 197, 94, 0.3)' : 'rgba(245, 158, 11, 0.3)'};
                    border-radius: 8px;
                    color: ${type === 'success' ? '#86efac' : '#fcd34d'};
                    padding: 1rem 1.5rem;
                    font-weight: 500;
                    backdrop-filter: blur(10px);
                    z-index: 9999;
                    font-size: 0.875rem;
                    max-width: 320px;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                `;
                
                notification.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 0.25rem;">${title}</div>
                    <div style="opacity: 0.9;">${message}</div>
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 10);
                
                // Auto remove after 4 seconds
                setTimeout(() => {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 4000);
            });
        });
    </script>
    
</div>