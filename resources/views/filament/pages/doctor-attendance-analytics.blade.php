<x-filament-panels::page>
    <div class="space-y-6">
        
        {{-- Filter Form --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">📊 Filter Options</h3>
            {{ $this->form }}
        </div>

        {{-- Overview Statistics --}}
        @php
            $stats = $this->getOverviewStats();
        @endphp
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Attendances --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Attendances</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_attendances']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Completed Shifts --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed Shifts</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed_shifts']) }}</p>
                        <p class="text-sm text-green-600">{{ $stats['completion_rate'] }}% completion rate</p>
                    </div>
                </div>
            </div>

            {{-- Average Work Hours --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Avg Work Hours</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['avg_work_hours'] }}h</p>
                    </div>
                </div>
            </div>

            {{-- Punctuality Rate --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Punctuality Rate</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['punctuality_rate'] }}%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Attendance Chart --}}
        @php
            $dailyData = $this->getDailyAttendanceChart();
        @endphp
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">📈 Daily Attendance Trend</h3>
            <div class="overflow-x-auto">
                <div class="min-w-full">
                    <canvas id="dailyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        {{-- Doctor Performance Table --}}
        @php
            $doctorPerformance = $this->getDoctorPerformance();
        @endphp
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">👩‍⚕️ Doctor Performance Summary</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Attendances</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Punctuality Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Work Hours</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($doctorPerformance as $doctor)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $doctor['name'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                        {{ $doctor['specialization'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    {{ $doctor['total_attendances'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $doctor['completion_rate'] }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $doctor['completion_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $doctor['punctuality_rate'] }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $doctor['punctuality_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    {{ $doctor['avg_work_hours'] }}h
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No data available for selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Schedule Compliance --}}
        @php
            $complianceData = $this->getScheduleCompliance();
        @endphp
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">⏰ Schedule Compliance Details</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(array_slice($complianceData, 0, 20) as $compliance)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    {{ \Carbon\Carbon::parse($compliance['date'])->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                    {{ $compliance['doctor'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                        {{ $compliance['shift_name'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-900">
                                    {{ $compliance['scheduled_time'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-900">
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $compliance['actual_time'])->format('H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    @if($compliance['difference_minutes'] > 0)
                                        +{{ $compliance['difference_minutes'] }}m
                                    @elseif($compliance['difference_minutes'] < 0)
                                        {{ $compliance['difference_minutes'] }}m
                                    @else
                                        0m
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $this->getComplianceStatusColor($compliance['status']) }} 
                                        @switch($compliance['status'])
                                            @case('Very Early')
                                                bg-blue-100
                                                @break
                                            @case('On Time')
                                                bg-green-100
                                                @break
                                            @case('Acceptable Late')
                                                bg-yellow-100
                                                @break
                                            @case('Late')
                                                bg-orange-100
                                                @break
                                            @case('Very Late')
                                                bg-red-100
                                                @break
                                            @default
                                                bg-gray-100
                                        @endswitch
                                    ">
                                        {{ $compliance['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No compliance data available for selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(count($complianceData) > 20)
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500">
                        Showing first 20 records of {{ count($complianceData) }} total compliance records
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Chart.js Script --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('dailyChart').getContext('2d');
            const dailyData = @json($dailyData);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dailyData.map(d => d.date),
                    datasets: [
                        {
                            label: 'Total Attendances',
                            data: dailyData.map(d => d.total),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'Completed Shifts',
                            data: dailyData.map(d => d.completed),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'No Shows',
                            data: dailyData.map(d => d.no_show),
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>