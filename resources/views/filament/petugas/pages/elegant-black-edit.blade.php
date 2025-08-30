<x-filament-panels::page>
    <div style="background: linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%); min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; color: #ffffff; padding: 2rem;">
        
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 700; color: #ffffff; margin: 0; letter-spacing: -0.025em;">
                        ✏️ Edit Data Pasien
                    </h1>
                    <p style="font-size: 1rem; color: #d1d5db; margin: 0.5rem 0 0 0;">
                        Ubah data jumlah pasien harian dengan elegant black theme
                    </p>
                </div>
                
                <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}" 
                   style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; color: #d1d5db; font-weight: 500; padding: 0.75rem 1rem; transition: all 0.2s ease; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                    ← Kembali
                </a>
            </div>
            
            <!-- Breadcrumb -->
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #9ca3af; font-size: 0.875rem;">
                <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}" style="color: #a5b4fc; text-decoration: none;">
                    Data Jumlah Pasien
                </a>
                <span style="color: #6b7280;">›</span>
                <span style="color: #d1d5db;">Edit Record #{{ $record->id }}</span>
            </div>
        </div>

        <!-- Current Data Display - ELEGANT BLACK GLASS -->
        <div style="background: rgba(10, 10, 11, 0.8); backdrop-filter: blur(16px) saturate(150%); -webkit-backdrop-filter: blur(16px) saturate(150%); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.6), 0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);">
            <h4 style="color: #ffffff; font-size: 1rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                ℹ️ Data Saat Ini
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; font-size: 0.875rem;">
                <div style="color: #ffffff;">
                    <strong style="color: #a5b4fc;">Tanggal:</strong> {{ $record->tanggal->format('d/m/Y') }}
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #a5b4fc;">Poli:</strong> {{ $record->poli === 'umum' ? 'Poli Umum' : 'Poli Gigi' }}
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #a5b4fc;">Shift:</strong> {{ $record->shift }}
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #a5b4fc;">Dokter:</strong> {{ $record->dokter?->nama_lengkap ?? 'N/A' }}
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #67e8f9;">Umum:</strong> <span style="color: #a5b4fc; font-weight: 600;">{{ $record->jumlah_pasien_umum }}</span>
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #67e8f9;">BPJS:</strong> <span style="color: #67e8f9; font-weight: 600;">{{ $record->jumlah_pasien_bpjs }}</span>
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #86efac;">Total:</strong> <span style="color: #86efac; font-weight: 700;">{{ $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs }}</span>
                </div>
                <div style="color: #ffffff;">
                    <strong style="color: #a5b4fc;">Status:</strong> 
                    <span style="color: {{ $record->status_validasi === 'approved' ? '#86efac' : ($record->status_validasi === 'rejected' ? '#fca5a5' : '#fcd34d') }}; font-weight: 600;">
                        {{ $record->status_validasi === 'approved' ? 'Disetujui' : ($record->status_validasi === 'rejected' ? 'Ditolak' : 'Menunggu') }}
                    </span>
                </div>
            </div>
            
            @if(in_array($record->status_validasi, ['approved', 'disetujui']))
            <div style="margin-top: 1rem; padding: 1rem; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; backdrop-filter: blur(8px);">
                <span style="color: #fcd34d; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                    ⚠️ <strong>Perhatian:</strong> Data ini sudah disetujui. Mengubah data akan me-reset status validasi ke "Menunggu".
                </span>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem;">
            <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}"
               style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; color: #d1d5db; font-weight: 500; padding: 0.875rem 1.5rem; transition: all 0.2s ease; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                ← Kembali ke List
            </a>
            
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" 
                        onclick="if(confirm('Yakin ingin menghapus data ini?')) { 
                            window.location.href='{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}';
                        }"
                        style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.8) 0%, rgba(220, 38, 38, 0.9) 100%); border: none; color: #ffffff; font-weight: 500; padding: 0.875rem 1.5rem; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                    🗑️ Delete
                </button>
                
                <button onclick="document.querySelector('form').submit();" 
                        style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; color: #ffffff; font-weight: 600; padding: 0.875rem 2rem; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                    ✅ Update Data
                </button>
            </div>
        </div>

        <!-- ELEGANT BLACK FORM CONTAINER -->
        <div style="background: linear-gradient(135deg, rgba(17, 17, 24, 0.95) 0%, rgba(26, 26, 32, 0.98) 100%); backdrop-filter: blur(20px) saturate(150%); -webkit-backdrop-filter: blur(20px) saturate(150%); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1rem; box-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.6), 0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.08); overflow: hidden;">
            
            <!-- Form Header -->
            <div style="padding: 1.5rem 2rem; background: linear-gradient(135deg, rgba(26, 26, 32, 0.8) 0%, rgba(42, 42, 50, 0.9) 100%); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                    📊 Form Edit Data Pasien
                </h2>
                <p style="font-size: 0.875rem; color: #e4e4e7; margin: 0.5rem 0 0 0;">
                    Silakan ubah data sesuai kebutuhan. Form menggunakan elegant black glassmorphic design.
                </p>
            </div>

            <!-- FILAMENT FORM WITH ELEGANT BLACK OVERRIDE -->
            <div style="padding: 2rem;">
                <style>
                    /* WORLD-CLASS BLACK THEME - INLINE CSS */
                    .fi-section, .fi-form, .fi-fo-section,
                    [data-field-wrapper], .fi-fo-field-wrp {
                        background: linear-gradient(135deg, rgba(10, 10, 11, 0.6) 0%, rgba(17, 17, 24, 0.8) 100%) !important;
                        backdrop-filter: blur(12px) saturate(120%) !important;
                        -webkit-backdrop-filter: blur(12px) saturate(120%) !important;
                        border: 1px solid rgba(255, 255, 255, 0.08) !important;
                        border-radius: 12px !important;
                        color: #ffffff !important;
                        box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.3), inset 0 1px 0 0 rgba(255, 255, 255, 0.06) !important;
                        margin-bottom: 1.5rem !important;
                        padding: 1.5rem !important;
                    }
                    
                    .fi-section-header, .fi-fo-section-header,
                    .fi-section h3, .fi-form h3, 
                    .fi-fo-field-wrp-label label {
                        color: #ffffff !important;
                        font-weight: 600 !important;
                        font-size: 1rem !important;
                        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
                    }
                    
                    .fi-section-description, .fi-fo-section-description,
                    .fi-fo-field-wrp-hint {
                        color: #d1d5db !important;
                        font-size: 0.875rem !important;
                    }
                    
                    .fi-input, .fi-select, .fi-textarea,
                    input, select, textarea {
                        background: linear-gradient(135deg, rgba(26, 26, 32, 0.8) 0%, rgba(42, 42, 50, 0.9) 100%) !important;
                        backdrop-filter: blur(8px) !important;
                        -webkit-backdrop-filter: blur(8px) !important;
                        border: 1px solid rgba(255, 255, 255, 0.12) !important;
                        border-radius: 8px !important;
                        color: #ffffff !important;
                        font-weight: 500 !important;
                        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
                    }
                    
                    .fi-input:focus, .fi-select:focus, .fi-textarea:focus,
                    input:focus, select:focus, textarea:focus {
                        background: linear-gradient(135deg, rgba(42, 42, 50, 0.9) 0%, rgba(64, 64, 80, 0.8) 100%) !important;
                        border-color: rgba(100, 116, 139, 0.5) !important;
                        box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                        outline: none !important;
                    }
                    
                    .fi-btn-primary {
                        background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
                        border: none !important;
                        color: #ffffff !important;
                        font-weight: 600 !important;
                        border-radius: 8px !important;
                        padding: 0.875rem 1.5rem !important;
                        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3) !important;
                    }
                    
                    .fi-btn-primary:hover {
                        background: linear-gradient(135deg, #047857 0%, #065f46 100%) !important;
                        transform: translateY(-1px) scale(1.02) !important;
                        box-shadow: 0 8px 16px -4px rgba(5, 150, 105, 0.4) !important;
                    }
                    
                    .fi-btn:not(.fi-btn-primary) {
                        background: rgba(255, 255, 255, 0.08) !important;
                        border: 1px solid rgba(255, 255, 255, 0.12) !important;
                        color: #d1d5db !important;
                        border-radius: 8px !important;
                    }
                    
                    .fi-btn:not(.fi-btn-primary):hover {
                        background: rgba(255, 255, 255, 0.12) !important;
                        color: #ffffff !important;
                        transform: translateY(-1px) !important;
                    }
                    
                    /* OVERRIDE ANY REMAINING NAVY BLUE */
                    .bg-primary-50, .text-primary-600, .border-primary-200,
                    [style*="rgb(59, 130, 246)"], [style*="#3b82f6"] {
                        background: rgba(10, 10, 11, 0.8) !important;
                        color: #ffffff !important;
                        border-color: rgba(255, 255, 255, 0.08) !important;
                    }
                    
                    /* ENSURE WHITE TEXT */
                    .fi-section *, .fi-form *, .fi-fo-field-wrp *,
                    label, span, p, div {
                        color: #ffffff !important;
                    }
                    
                    /* HELPER TEXT */
                    .text-gray-500, .fi-fo-field-wrp-hint {
                        color: #a1a1aa !important;
                    }
                    
                    /* ERROR TEXT */
                    .text-danger-600, .fi-fo-field-wrp-error-message {
                        color: #fca5a5 !important;
                    }
                </style>
                
                <!-- FILAMENT FORM -->
                {{ $this->form }}
            </div>
        </div>

        <!-- Success Message -->
        <div style="margin-top: 2rem; text-align: center; padding: 1.5rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(22, 163, 74, 0.1) 100%); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; backdrop-filter: blur(12px);">
            <strong style="color: #86efac;">✨ Elegant Black Glass Edit Form:</strong>
            <span style="color: #d1d5db;"> Pure inline styles dengan zero external CSS dependencies - guaranteed elegant black theme</span>
        </div>

    </div>
</x-filament-panels::page>