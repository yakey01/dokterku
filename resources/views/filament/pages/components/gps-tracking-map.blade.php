{{-- GPS Tracking Map Component --}}
@props(['dailyBreakdown', 'userId'])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
    <h4 class="text-md font-semibold mb-4 flex items-center">
        <span class="mr-2">🗺️</span> Tracking Lokasi Check-in
    </h4>
    
    {{-- GPS Summary Stats --}}
    @php
        $totalCheckins = collect($dailyBreakdown)->where('status', 'present')->count();
        $validGPS = collect($dailyBreakdown)->where('gps_valid', true)->count();
        $gpsValidationRate = $totalCheckins > 0 ? ($validGPS / $totalCheckins) * 100 : 0;
        
        $locationGroups = collect($dailyBreakdown)
            ->where('status', 'present')
            ->groupBy('location_name')
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'percentage' => 0 // Will calculate below
                ];
            });
        
        foreach($locationGroups as $location => $data) {
            $locationGroups[$location]['percentage'] = $totalCheckins > 0 ? ($data['count'] / $totalCheckins) * 100 : 0;
        }
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- GPS Validation Rate --}}
        <div class="text-center p-4 rounded-lg bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <div class="text-2xl font-bold text-green-600">{{ number_format($gpsValidationRate, 1) }}%</div>
            <div class="text-sm text-green-700 dark:text-green-300">GPS Valid</div>
            <div class="text-xs text-gray-600">{{ $validGPS }}/{{ $totalCheckins }} check-ins</div>
        </div>
        
        {{-- Total Locations --}}
        <div class="text-center p-4 rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <div class="text-2xl font-bold text-blue-600">{{ $locationGroups->count() }}</div>
            <div class="text-sm text-blue-700 dark:text-blue-300">Lokasi Berbeda</div>
            <div class="text-xs text-gray-600">check-in locations</div>
        </div>
        
        {{-- Accuracy Score --}}
        <div class="text-center p-4 rounded-lg bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <div class="text-2xl font-bold text-purple-600">
                {{ $gpsValidationRate >= 95 ? 'A+' : ($gpsValidationRate >= 85 ? 'A' : ($gpsValidationRate >= 75 ? 'B' : 'C')) }}
            </div>
            <div class="text-sm text-purple-700 dark:text-purple-300">Grade GPS</div>
            <div class="text-xs text-gray-600">accuracy score</div>
        </div>
    </div>
    
    {{-- Location Breakdown --}}
    @if($locationGroups->count() > 0)
    <div class="mb-6">
        <h5 class="font-medium mb-3">Distribusi Lokasi Check-in</h5>
        <div class="space-y-3">
            @foreach($locationGroups->sortByDesc('count') as $location => $data)
            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-blue-500 mr-3"></div>
                    <div>
                        <div class="font-medium">{{ $location ?: 'Lokasi Tidak Diketahui' }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $data['count'] }} check-ins</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-medium">{{ number_format($data['percentage'], 1) }}%</div>
                    <div class="w-24 bg-gray-200 rounded-full h-2 dark:bg-gray-600 mt-1">
                        <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                             style="width: {{ $data['percentage'] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    {{-- GPS Timeline --}}
    <div>
        <h5 class="font-medium mb-3">Timeline Validasi GPS</h5>
        <div class="grid grid-cols-7 gap-1">
            @foreach(collect($dailyBreakdown)->take(35) as $day) {{-- Show last 5 weeks --}}
            <div class="aspect-square rounded border-2 flex items-center justify-center text-xs font-medium
                {{ $day['status'] === 'present' 
                    ? ($day['gps_valid'] ?? false 
                        ? 'bg-green-100 border-green-300 text-green-800 dark:bg-green-800 dark:border-green-600 dark:text-green-100' 
                        : 'bg-red-100 border-red-300 text-red-800 dark:bg-red-800 dark:border-red-600 dark:text-red-100')
                    : 'bg-gray-100 border-gray-300 text-gray-400 dark:bg-gray-700 dark:border-gray-600' }}"
                 title="{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} - {{ $day['status'] === 'present' ? ($day['gps_valid'] ?? false ? 'GPS Valid' : 'GPS Invalid') : 'Tidak Hadir' }}">
                {{ \Carbon\Carbon::parse($day['date'])->format('j') }}
            </div>
            @endforeach
        </div>
        
        <div class="flex items-center justify-center space-x-6 mt-4 text-xs">
            <div class="flex items-center">
                <div class="w-3 h-3 bg-green-100 border-2 border-green-300 rounded mr-2"></div>
                <span>GPS Valid</span>
            </div>
            <div class="flex items-center">
                <div class="w-3 h-3 bg-red-100 border-2 border-red-300 rounded mr-2"></div>
                <span>GPS Invalid</span>
            </div>
            <div class="flex items-center">
                <div class="w-3 h-3 bg-gray-100 border-2 border-gray-300 rounded mr-2"></div>
                <span>Tidak Hadir</span>
            </div>
        </div>
    </div>
    
    {{-- GPS Troubleshooting Tips --}}
    @if($gpsValidationRate < 90)
    <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <div class="flex items-start">
            <div class="text-yellow-600 mr-3">⚠️</div>
            <div>
                <h6 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">Tips Perbaikan GPS</h6>
                <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                    <li>• Pastikan GPS/Location Service aktif pada device</li>
                    <li>• Check-in di area terbuka untuk signal GPS yang lebih baik</li>
                    <li>• Tunggu hingga akurasi GPS ≤ 20 meter sebelum check-in</li>
                    <li>• Restart aplikasi jika GPS tidak akurat</li>
                </ul>
            </div>
        </div>
    </div>
    @endif
</div>