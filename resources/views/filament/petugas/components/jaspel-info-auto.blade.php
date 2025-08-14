@php
    use App\Services\Jaspel\UnifiedJaspelCalculationService;
    use App\Models\DokterUmumJaspel;
    
    $calculationService = app(UnifiedJaspelCalculationService::class);
    $totalPasien = $pasien_umum + $pasien_bpjs;
    
    // Use unified calculation service for consistent results
    $calculation = $calculationService->calculateEstimated($pasien_umum, $pasien_bpjs, $shift ?? 'Pagi');
    
    if (isset($calculation['error'])) {
        $hasError = true;
        $errorMessage = $calculation['error'];
        $jaspeFormula = null;
    } else {
        $hasError = false;
        $jaspeFormula = $calculation['formula_used'] ? (object) $calculation['formula_used'] : null;
        
        $feeUmum = $calculation['fee_umum'];
        $feeBpjs = $calculation['fee_bpjs'];
        $uangDuduk = $calculation['uang_duduk'];
        $totalFee = $calculation['total'];
        $pasienDihitungUmum = $calculation['pasien_umum_dihitung'] ?? 0;
        $pasienDihitungBpjs = $calculation['pasien_bpjs_dihitung'] ?? 0;
        
        $thresholdMet = $calculation['threshold_met'] ?? false;
        $thresholdValue = $calculation['threshold_value'] ?? 0;
        $thresholdMessage = $thresholdMet 
            ? "✅ Melewati ambang minimum {$thresholdValue} pasien"
            : "⚠️ Belum mencapai ambang minimum {$thresholdValue} pasien";
    }
    
    $displayShift = $shift ?? ($jaspeFormula ? $jaspeFormula->jenis_shift : 'Tidak Diketahui');
@endphp

<div class="space-y-3">
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-sm text-blue-900 dark:text-blue-200">📊 Perhitungan Jaspel</h4>
            <span class="text-xs bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded">
                {{ $displayShift }} Shift
            </span>
        </div>
        
        @if(!$hasError && $jaspeFormula)
            <div class="grid grid-cols-1 gap-3 text-sm">
                <!-- Auto-Selected Formula Information -->
                <div class="bg-white dark:bg-gray-800 p-3 rounded border-l-4 border-green-400">
                    <p class="font-medium text-green-800 dark:text-green-200 mb-1">🤖 Formula Otomatis</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $jaspeFormula->jenis_shift ?? 'Shift' }}
                        <span class="text-green-600 font-medium">(Dipilih otomatis berdasarkan shift)</span>
                    </p>
                    <div class="mt-1 text-xs text-gray-500 grid grid-cols-3 gap-2">
                        <span>Threshold: {{ $jaspeFormula->ambang_pasien }} pasien</span>
                        <span>Uang Duduk: Rp {{ number_format($jaspeFormula->uang_duduk, 0, ',', '.') }}</span>
                        <span>Fee Umum: Rp {{ number_format($jaspeFormula->fee_pasien_umum, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Threshold Information -->
                <div class="bg-white dark:bg-gray-800 p-3 rounded border-l-4 border-blue-400">
                    <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Ambang Minimum Total Pasien</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $jaspeFormula->ambang_pasien }} pasien ({{ $thresholdMessage }})
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Total saat ini: {{ $totalPasien }} pasien ({{ $pasien_umum }} umum + {{ $pasien_bpjs }} BPJS)
                    </p>
                </div>
                
                <!-- Uang Duduk Section -->
                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded border-l-4 border-green-400">
                    <p class="font-medium text-green-800 dark:text-green-200 mb-1">💰 Uang Duduk (Base Fee)</p>
                    <p class="font-semibold text-green-700 dark:text-green-300">
                        Rp {{ number_format($uangDuduk, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">
                        (tetap didapat tanpa melihat jumlah pasien)
                    </p>
                </div>

                <!-- Fee Breakdown -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 mb-1">Pasien Umum:</p>
                        @if($totalPasien <= $jaspeFormula->ambang_pasien)
                            <p class="text-sm text-gray-500">
                                {{ $pasien_umum }} pasien (total belum mencapai ambang)
                                <br><span class="text-red-500">Rp 0</span>
                            </p>
                        @else
                            <p class="font-semibold text-blue-700 dark:text-blue-300">
                                {{ $pasienDihitungUmum }} pasien × Rp {{ number_format($jaspeFormula->fee_pasien_umum, 0, ',', '.') }} = 
                                <span class="text-green-600">Rp {{ number_format($feeUmum, 0, ',', '.') }}</span>
                            </p>
                            <p class="text-xs text-gray-500">
                                (dari {{ $pasien_umum }} pasien umum, {{ $pasienDihitungUmum }} yang dihitung setelah threshold {{ $jaspeFormula->ambang_pasien }})
                            </p>
                        @endif
                    </div>
                    
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 mb-1">Pasien BPJS:</p>
                        @if($totalPasien <= $jaspeFormula->ambang_pasien)
                            <p class="text-sm text-gray-500">
                                {{ $pasien_bpjs }} pasien (total belum mencapai ambang)
                                <br><span class="text-red-500">Rp 0</span>
                            </p>
                        @else
                            <p class="font-semibold text-blue-700 dark:text-blue-300">
                                {{ $pasienDihitungBpjs }} pasien × Rp {{ number_format($jaspeFormula->fee_pasien_bpjs, 0, ',', '.') }} = 
                                <span class="text-green-600">Rp {{ number_format($feeBpjs, 0, ',', '.') }}</span>
                            </p>
                            <p class="text-xs text-gray-500">
                                (dari {{ $pasien_bpjs }} pasien BPJS, {{ $pasienDihitungBpjs }} yang dihitung setelah threshold {{ $jaspeFormula->ambang_pasien }})
                            </p>
                        @endif
                    </div>
                </div>
                
                <!-- Total Calculation -->
                <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Estimasi Total Jaspel:</p>
                    
                    @if($totalPasien <= $jaspeFormula->ambang_pasien)
                        <div class="text-sm text-gray-600 mb-2">
                            <p>Uang Duduk: Rp {{ number_format($uangDuduk, 0, ',', '.') }}</p>
                            <p>Fee Pasien: Rp 0 (belum mencapai threshold)</p>
                        </div>
                    @else
                        <div class="text-sm text-gray-600 mb-2">
                            <p>Uang Duduk: Rp {{ number_format($uangDuduk, 0, ',', '.') }}</p>
                            <p>Fee Umum: Rp {{ number_format($feeUmum, 0, ',', '.') }}</p>
                            <p>Fee BPJS: Rp {{ number_format($feeBpjs, 0, ',', '.') }}</p>
                            <hr class="my-1">
                        </div>
                    @endif
                    
                    <p class="text-lg font-bold {{ $totalFee > 0 ? 'text-green-600' : 'text-red-500' }} dark:text-green-400">
                        💰 Rp {{ number_format($totalFee, 0, ',', '.') }}
                    </p>
                    
                    @if($totalPasien <= $jaspeFormula->ambang_pasien)
                        <p class="text-xs text-orange-600 mt-1">
                            ⚠️ Hanya dapat uang duduk karena belum mencapai ambang minimum
                        </p>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-red-600 dark:text-red-400 font-medium">⚠️ {{ $hasError ? 'Error Perhitungan Jaspel' : 'Formula Jaspel Belum Dikonfigurasi' }}</p>
                <p class="text-sm text-gray-500 mt-1">
                    @if($hasError)
                        {{ $errorMessage }}
                    @elseif($shift)
                        Tidak ada formula aktif untuk shift {{ $shift }}. 
                    @else
                        Pilih shift terlebih dahulu untuk melihat perhitungan jaspel.
                    @endif
                    Hubungi administrator untuk mengatur formula jaspel.
                </p>
            </div>
        @endif
    </div>
    
    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
        <p>🤖 Formula dipilih otomatis berdasarkan shift yang dipilih.</p>
        <p>⚙️ Menggunakan Unified Calculation Service untuk konsistensi hasil.</p>
        <p>⚠️ Nilai final tergantung validasi Bendahara dan dapat berbeda dari estimasi.</p>
        @if(!$hasError && $jaspeFormula)
            <p>📋 Formula: {{ $jaspeFormula->keterangan ?? 'Tidak ada keterangan' }}</p>
            <p>🔧 Method: {{ $calculation['calculation_method'] ?? 'unknown' }}</p>
        @endif
    </div>
</div>