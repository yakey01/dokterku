<div class="space-y-3">
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h4 class="font-semibold text-sm text-blue-900 dark:text-blue-200 mb-2">📊 Perhitungan Jaspel</h4>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-600 dark:text-gray-400">Pasien Umum:</p>
                <p class="font-semibold text-blue-700 dark:text-blue-300">
                    {{ $pasien_umum }} pasien × Rp 7.000 = 
                    <span class="text-green-600">Rp {{ number_format($pasien_umum * 7000, 0, ',', '.') }}</span>
                </p>
            </div>
            
            <div>
                <p class="text-gray-600 dark:text-gray-400">Pasien BPJS:</p>
                <p class="font-semibold text-blue-700 dark:text-blue-300">
                    {{ $pasien_bpjs }} pasien × Rp 5.000 = 
                    <span class="text-green-600">Rp {{ number_format($pasien_bpjs * 5000, 0, ',', '.') }}</span>
                </p>
            </div>
        </div>
        
        <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-800">
            <p class="text-sm text-gray-600 dark:text-gray-400">Estimasi Total Jaspel:</p>
            <p class="text-lg font-bold text-green-600 dark:text-green-400">
                💰 Rp {{ number_format(($pasien_umum * 7000) + ($pasien_bpjs * 5000), 0, ',', '.') }}
            </p>
        </div>
    </div>
    
    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
        <p>ℹ️ Perhitungan di atas adalah estimasi berdasarkan tarif standar.</p>
        <p>⚠️ Jumlah final dapat berbeda tergantung kebijakan dan validasi manajemen.</p>
    </div>
</div>