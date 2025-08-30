{{-- Export Options Component --}}
@props(['record', 'period'])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
    <h4 class="text-md font-semibold mb-4 flex items-center">
        <span class="mr-2">📤</span> Export & Laporan
    </h4>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- PDF Report --}}
        <button class="group p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 hover:border-red-300 dark:hover:border-red-500 transition-all duration-200 hover:shadow-md">
            <div class="text-center">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform duration-200">📄</div>
                <div class="font-medium text-gray-900 dark:text-gray-100">PDF Report</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Laporan lengkap</div>
            </div>
        </button>
        
        {{-- Excel Export --}}
        <button class="group p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-500 transition-all duration-200 hover:shadow-md">
            <div class="text-center">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform duration-200">📊</div>
                <div class="font-medium text-gray-900 dark:text-gray-100">Excel Export</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Data analysis</div>
            </div>
        </button>
        
        {{-- Email Report --}}
        <button class="group p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500 transition-all duration-200 hover:shadow-md">
            <div class="text-center">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform duration-200">📧</div>
                <div class="font-medium text-gray-900 dark:text-gray-100">Kirim Email</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Auto delivery</div>
            </div>
        </button>
        
        {{-- Print View --}}
        <button class="group p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500 transition-all duration-200 hover:shadow-md"
                onclick="window.print()">
            <div class="text-center">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform duration-200">🖨️</div>
                <div class="font-medium text-gray-900 dark:text-gray-100">Print</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Cetak langsung</div>
            </div>
        </button>
    </div>
    
    {{-- Export Options Details --}}
    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <div class="font-medium text-gray-900 dark:text-gray-100 mb-2">📋 Yang Disertakan:</div>
                <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                    <li class="flex items-center"><span class="mr-2">✓</span> Statistik kehadiran lengkap</li>
                    <li class="flex items-center"><span class="mr-2">✓</span> Detail harian per shift</li>
                    <li class="flex items-center"><span class="mr-2">✓</span> Analisis GPS dan lokasi</li>
                    <li class="flex items-center"><span class="mr-2">✓</span> Tren 6 bulan terakhir</li>
                    <li class="flex items-center"><span class="mr-2">✓</span> Rekomendasi perbaikan</li>
                </ul>
            </div>
            
            <div>
                <div class="font-medium text-gray-900 dark:text-gray-100 mb-2">🎯 Format Laporan:</div>
                <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                    <li class="flex items-center"><span class="mr-2">📄</span> PDF: Siap cetak profesional</li>
                    <li class="flex items-center"><span class="mr-2">📊</span> Excel: Data analysis</li>
                    <li class="flex items-center"><span class="mr-2">📧</span> Email: Auto-send ke manager</li>
                    <li class="flex items-center"><span class="mr-2">🖨️</span> Print: Direct printing</li>
                </ul>
            </div>
        </div>
    </div>
    
    {{-- Quick Actions --}}
    <div class="mt-4 flex flex-wrap gap-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
            📅 Periode: {{ $period['month_name'] ?? 'Current Month' }}
        </span>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
            👤 {{ $record->staff_name }}
        </span>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
            🏥 {{ $record->profession }}
        </span>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100">
            🎯 {{ number_format($record->attendance_percentage, 1) }}% Kehadiran
        </span>
    </div>
</div>