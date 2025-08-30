<x-filament-panels::page>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #ffffff !important;">
        
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 1.875rem; font-weight: 700; color: #ffffff; margin: 0 0 0.5rem 0; letter-spacing: -0.025em;">
                ✏️ Edit Data Pasien
            </h1>
            <p style="font-size: 1rem; color: #d1d5db; margin: 0;">
                Ubah data jumlah pasien harian dengan elegant glass form
            </p>
            
            <!-- Breadcrumb -->
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #9ca3af; font-size: 0.875rem; margin-top: 1rem;">
                <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}" style="color: #a5b4fc; text-decoration: none;">
                    Data Jumlah Pasien
                </a>
                <span>›</span>
                <span style="color: #d1d5db;">Edit Record #{{ $record->id }}</span>
            </div>
        </div>

        <!-- Glass Form Container -->
        <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(12px) saturate(110%); -webkit-backdrop-filter: blur(12px) saturate(110%); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1rem; box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), 0 2px 8px -2px rgba(0, 0, 0, 0.3), inset 0 1px 0 0 rgba(255, 255, 255, 0.06); overflow: hidden; margin: 0 auto; max-width: 800px;">
            
            <!-- Form Header -->
            <div style="padding: 1.5rem 2rem; background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                    📊 Form Edit Data Pasien
                </h2>
                <p style="font-size: 0.875rem; color: #d1d5db; margin: 0.5rem 0 0 0;">
                    Silakan ubah data sesuai kebutuhan. Status validasi akan di-reset jika mengubah data kritis.
                </p>
            </div>

            <!-- INLINE FORM - ZERO EXTERNAL DEPENDENCIES -->
            <div style="padding: 2rem;">
                
                <!-- Current Data Display -->
                <div style="background: rgba(255, 255, 255, 0.05) !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 8px !important; padding: 1rem !important; margin-bottom: 2rem !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;">
                    <h4 style="color: #a5b4fc; font-size: 0.875rem; font-weight: 600; margin: 0 0 0.75rem 0;">ℹ️ Data Saat Ini</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; font-size: 0.8125rem;">
                        <div style="color: #d1d5db;">
                            <strong>Tanggal:</strong> {{ $record->tanggal->format('d/m/Y') }}
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Poli:</strong> {{ $record->poli === 'umum' ? 'Poli Umum' : 'Poli Gigi' }}
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Shift:</strong> {{ $record->shift }}
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Dokter:</strong> {{ $record->dokter?->nama_lengkap ?? 'N/A' }}
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Umum:</strong> <span style="color: #a5b4fc;">{{ $record->jumlah_pasien_umum }}</span>
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>BPJS:</strong> <span style="color: #67e8f9;">{{ $record->jumlah_pasien_bpjs }}</span>
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Total:</strong> <span style="color: #86efac;">{{ $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs }}</span>
                        </div>
                        <div style="color: #d1d5db;">
                            <strong>Status:</strong> 
                            <span style="color: {{ $record->status_validasi === 'approved' ? '#86efac' : ($record->status_validasi === 'rejected' ? '#fca5a5' : '#fcd34d') }};">
                                {{ $record->status_validasi === 'approved' ? 'Disetujui' : ($record->status_validasi === 'rejected' ? 'Ditolak' : 'Menunggu') }}
                            </span>
                        </div>
                    </div>
                    
                    @if(in_array($record->status_validasi, ['approved', 'disetujui']))
                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: rgba(245, 158, 11, 0.1) !important; border: 1px solid rgba(245, 158, 11, 0.2) !important; border-radius: 6px !important; backdrop-filter: blur(8px) !important;">
                        <span style="color: #fcd34d; font-size: 0.8125rem;">
                            ⚠️ <strong>Perhatian:</strong> Data ini sudah disetujui. Mengubah data akan me-reset status validasi ke "Menunggu".
                        </span>
                    </div>
                    @endif
                </div>

                <!-- ACTION BUTTONS -->
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}"
                       style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; color: #d1d5db; font-weight: 500; padding: 0.875rem 1.5rem; transition: all 0.2s ease; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"
                       onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.borderColor='rgba(255, 255, 255, 0.2)'; this.style.color='#ffffff'; this.style.transform='translateY(-1px)';"
                       onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.12)'; this.style.color='#d1d5db'; this.style.transform='';">
                        ← Kembali ke List
                    </a>
                    
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" 
                                onclick="if(confirm('Yakin ingin menghapus data ini?')) { window.location.href='{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}'; }"
                                style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; color: #fca5a5; font-weight: 500; padding: 0.875rem 1.5rem; transition: all 0.2s ease; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"
                                onmouseover="this.style.background='rgba(239, 68, 68, 0.25)'; this.style.borderColor='rgba(239, 68, 68, 0.3)'; this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.background='rgba(239, 68, 68, 0.15)'; this.style.borderColor='rgba(239, 68, 68, 0.2)'; this.style.transform='';">
                            🗑️ Delete
                        </button>
                        
                        <button onclick="document.querySelector('form').submit();" 
                                style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; color: #ffffff; font-weight: 600; padding: 0.875rem 2rem; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"
                                onmouseover="this.style.background='linear-gradient(135deg, #047857 0%, #065f46 100%)'; this.style.transform='translateY(-1px) scale(1.02)'; this.style.boxShadow='0 8px 16px -4px rgba(5, 150, 105, 0.3)';"
                                onmouseout="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'; this.style.transform=''; this.style.boxShadow='';">
                            ✅ Update Data
                        </button>
                    </div>
                </div>

                <!-- DEFAULT FILAMENT FORM - WITH ENHANCED STYLING -->
                <div style="background: rgba(255, 255, 255, 0.02) !important; border-radius: 0.75rem !important; padding: 0 !important; backdrop-filter: blur(8px) !important;">
                    {{ $this->form }}
                </div>

            </div>
        </div>

        <!-- Info Panel -->
        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(34, 197, 94, 0.1) !important; border: 1px solid rgba(34, 197, 94, 0.2) !important; border-radius: 8px !important; backdrop-filter: blur(8px) !important; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;">
            <strong style="color: #86efac;">✨ Elegant Glass Edit Form:</strong>
            <span style="color: #d1d5db;"> Default Filament form dengan enhanced glass styling yang konsisten dengan table design</span>
        </div>

    </div>
</x-filament-panels::page>