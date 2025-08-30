<div class="work-location-tolerance-actions-wrapper">
    <div class="quick-actions-container flex items-center justify-between gap-4 p-6 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl border border-slate-200">
        <div class="quick-actions-info">
            <h4 class="text-lg font-bold text-slate-800 mb-1">💾 Simpan Pengaturan</h4>
            <p class="text-sm text-slate-600">Pastikan semua pengaturan toleransi sudah sesuai sebelum menyimpan</p>
        </div>
        <div class="quick-actions-buttons flex gap-3">
            <button 
                type="button" 
                onclick="(function() {
                    if (confirm('Apakah Anda yakin ingin mengatur ulang semua pengaturan toleransi ke nilai default?')) {
                        const inputs = {
                            late_tolerance_minutes: 15,
                            early_departure_tolerance_minutes: 15,
                            break_time_minutes: 60,
                            overtime_threshold_minutes: 480
                        };
                        Object.entries(inputs).forEach(([name, value]) => {
                            const input = document.querySelector(`input[name='${name}']`);
                            if (input) {
                                input.value = value;
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        });
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                title: '✅ Reset Berhasil!',
                                body: 'Pengaturan toleransi telah direset ke nilai default',
                                type: 'success',
                                duration: 3000
                            }
                        }));
                    }
                })()" 
                class="reset-btn px-6 py-3 bg-slate-500 hover:bg-slate-600 text-white font-semibold rounded-lg shadow-md transition-all duration-200 flex items-center gap-2"
            >
                <span>🔄</span>
                <span>Reset Default</span>
            </button>
            <button 
                type="submit" 
                class="save-btn px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition-all duration-200 flex items-center gap-2"
            >
                <span>✅</span>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </div>
</div>