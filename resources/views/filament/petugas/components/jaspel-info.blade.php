@php
    use App\Models\DokterUmumJaspel;
    
    $totalPasien = $pasien_umum + $pasien_bpjs;
    $currentHour = now()->hour;
    
    // Determine shift based on current time
    $jenisShift = match(true) {
        $currentHour >= 7 && $currentHour < 14 => 'Pagi',
        $currentHour >= 14 && $currentHour < 21 => 'Sore', 
        default => 'Pagi' // Default fallback
    };
    
    // Get active Jaspel formula for current shift
    $jaspeFormula = DokterUmumJaspel::where('jenis_shift', $jenisShift)
        ->where('status_aktif', true)
        ->first();
    
    // Fallback to any active formula if shift-specific not found
    if (!$jaspeFormula) {
        $jaspeFormula = DokterUmumJaspel::where('status_aktif', true)->first();
    }
    
    // Calculate fees using proper threshold logic
    $feeUmum = 0;
    $feeBpjs = 0;
    $uangDuduk = 0;
    $totalFee = 0;
    $thresholdMessage = '';
    $pasienDihitungUmum = 0;
    $pasienDihitungBpjs = 0;
    
    if ($jaspeFormula) {
        $uangDuduk = $jaspeFormula->uang_duduk;
        
        if ($totalPasien <= $jaspeFormula->ambang_pasien) {
            // Hanya dapat uang duduk
            $feeUmum = 0;
            $feeBpjs = 0;
            $totalFee = $uangDuduk;
            $thresholdMessage = "⚠️ Belum mencapai ambang minimum {$jaspeFormula->ambang_pasien} pasien";
        } else {
            // Dapat uang duduk + fee pasien (threshold mengurangi total yang dihitung)
            $totalPasienDihitung = $totalPasien - $jaspeFormula->ambang_pasien;
            
            // Hitung proporsi pasien yang akan dihitung
            if ($totalPasien > 0) {
                $proporsiUmum = $pasien_umum / $totalPasien;
                $proporsiBpjs = $pasien_bpjs / $totalPasien;
                
                $pasienDihitungUmum = round($totalPasienDihitung * $proporsiUmum);
                $pasienDihitungBpjs = round($totalPasienDihitung * $proporsiBpjs);
            }
            
            $feeUmum = $pasienDihitungUmum * $jaspeFormula->fee_pasien_umum;
            $feeBpjs = $pasienDihitungBpjs * $jaspeFormula->fee_pasien_bpjs;
            $totalFee = $uangDuduk + $feeUmum + $feeBpjs;
            $thresholdMessage = "✅ Melewati ambang minimum {$jaspeFormula->ambang_pasien} pasien";
        }
    }
@endphp

<div class="space-y-3">
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-sm text-blue-900 dark:text-blue-200">📊 Perhitungan Jaspel</h4>
            <span class="text-xs bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded">
                {{ $jenisShift }} Shift
            </span>
        </div>
        
        @if($jaspeFormula)
            <div class="grid grid-cols-1 gap-3 text-sm">
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
                <p class="text-red-600 dark:text-red-400 font-medium">⚠️ Formula Jaspel Belum Dikonfigurasi</p>
                <p class="text-sm text-gray-500 mt-1">Hubungi administrator untuk mengatur formula jaspel</p>
            </div>
        @endif
    </div>
    
    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
        <p>ℹ️ Perhitungan menggunakan formula aktif dari Admin Panel (shift {{ $jenisShift }}).</p>
        <p>⚠️ Nilai final tergantung validasi Bendahara dan dapat berbeda dari estimasi.</p>
        @if($jaspeFormula)
            <p>📋 Formula: {{ $jaspeFormula->keterangan ?? 'Tidak ada keterangan' }}</p>
        @endif
    </div>
</div>