{{-- Performance Chart Component for Monthly Trends --}}
@props(['data', 'title' => 'Performance Chart'])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
    <h4 class="text-md font-semibold mb-4">{{ $title }}</h4>
    
    <div class="relative">
        {{-- Chart Area --}}
        <div class="h-64 flex items-end justify-between space-x-2">
            @foreach($data as $index => $item)
                @php
                    $percentage = $item['attendance_percentage'] ?? 0;
                    $height = max(10, ($percentage / 100) * 100); // Min 10% height
                    $color = match(true) {
                        $percentage >= 95 => 'bg-green-500',
                        $percentage >= 85 => 'bg-blue-500',
                        $percentage >= 75 => 'bg-yellow-500',
                        default => 'bg-red-500'
                    };
                @endphp
                
                <div class="flex-1 flex flex-col items-center">
                    {{-- Bar --}}
                    <div class="w-full max-w-12 relative group">
                        <div class="{{ $color }} rounded-t-md transition-all duration-500 ease-out hover:opacity-80" 
                             style="height: {{ $height }}%;">
                        </div>
                        
                        {{-- Tooltip --}}
                        <div class="invisible group-hover:visible absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap z-10">
                            <div class="font-medium">{{ $item['month_name'] ?? 'N/A' }}</div>
                            <div>Kehadiran: {{ number_format($percentage, 1) }}%</div>
                            <div>Hadir: {{ $item['attended_shifts'] ?? 0 }}/{{ $item['scheduled_shifts'] ?? 0 }}</div>
                            <div>Jam: {{ number_format($item['total_hours'] ?? 0, 1) }}</div>
                            {{-- Arrow --}}
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                    
                    {{-- Label --}}
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 text-center">
                        {{ $item['month_name'] ?? 'N/A' }}
                    </div>
                    <div class="text-xs font-medium text-gray-900 dark:text-gray-100">
                        {{ number_format($percentage, 0) }}%
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Y-axis labels --}}
        <div class="absolute left-0 top-0 h-64 flex flex-col justify-between text-xs text-gray-500">
            <span>100%</span>
            <span>75%</span>
            <span>50%</span>
            <span>25%</span>
            <span>0%</span>
        </div>
        
        {{-- Grid lines --}}
        <div class="absolute inset-0 pointer-events-none">
            @for($i = 0; $i <= 4; $i++)
                <div class="absolute w-full border-t border-gray-200 dark:border-gray-600 opacity-30" 
                     style="top: {{ $i * 25 }}%;"></div>
            @endfor
        </div>
    </div>
    
    {{-- Legend --}}
    <div class="flex flex-wrap gap-4 mt-4 text-xs">
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
            <span>Excellent (≥95%)</span>
        </div>
        <div class="flex items-center">
            <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
            <span>Good (85-94%)</span>
        </div>
        <div class="flex items-center">
            <div class="w-3 h-3 bg-yellow-500 rounded mr-2"></div>
            <span>Average (75-84%)</span>
        </div>
        <div class="flex items-center">
            <div class="w-3 h-3 bg-red-500 rounded mr-2"></div>
            <span>Poor (<75%)</span>
        </div>
    </div>
</div>