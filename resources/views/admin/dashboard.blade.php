@extends('admin.layouts.admin')

@section('page-title', 'Dashboard Premium')

@section('content')
<div class="space-y-8">
    <!-- Premium Welcome Section -->
    <div class="glass-morphism rounded-3xl p-8 premium-glow card-premium">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-500 via-blue-500 to-emerald-500 rounded-3xl flex items-center justify-center shadow-2xl premium-glow animate-float">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-white mb-2">
                        Selamat Datang, <span class="gradient-text">{{ auth()->user()->name }}</span>
                    </h1>
                    <p class="text-gray-400 text-lg">Dashboard Premium Klinik Dokterku</p>
                    <div class="flex items-center mt-3 space-x-4">
                        <div class="flex items-center glass-morphism px-4 py-2 rounded-2xl">
                            <svg class="w-4 h-4 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2V7zm16 0v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2z"></path>
                            </svg>
                            <span class="text-sm text-gray-300">{{ now()->format('d F Y') }}</span>
                        </div>
                        <div class="flex items-center glass-morphism px-4 py-2 rounded-2xl">
                            <div class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></div>
                            <span class="text-sm text-emerald-400 font-medium">System Online</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hidden lg:block">
                <div class="glass-morphism px-6 py-4 rounded-2xl">
                    <div class="text-center">
                        <p class="text-gray-400 text-sm">Total Hari Ini</p>
                        <p class="text-2xl font-bold gradient-text">Rp 12.5M</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Pasien -->
        <div class="glass-morphism rounded-3xl p-6 card-premium premium-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-2">Total Pasien</p>
                    <p class="text-3xl font-bold text-white stats-number">{{ $stats['patients'] ?? 1247 }}</p>
                    <div class="flex items-center mt-3">
                        <svg class="w-4 h-4 text-emerald-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        <span class="text-sm text-emerald-400 font-medium">+12.5%</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-2xl premium-glow">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Tindakan -->
        <div class="glass-morphism rounded-3xl p-6 card-premium premium-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-2">Total Tindakan</p>
                    <p class="text-3xl font-bold text-white stats-number">{{ $stats['procedures'] ?? 856 }}</p>
                    <div class="flex items-center mt-3">
                        <svg class="w-4 h-4 text-purple-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="text-sm text-purple-400 font-medium">+8.2%</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-2xl premium-glow">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Pendapatan -->
        <div class="glass-morphism rounded-3xl p-6 card-premium premium-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-2">Total Pendapatan</p>
                    <p class="text-3xl font-bold text-white stats-number">Rp {{ number_format($stats['total_income'] ?? 25750000, 0, ',', '.') }}</p>
                    <div class="flex items-center mt-3">
                        <svg class="w-4 h-4 text-emerald-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        <span class="text-sm text-emerald-400 font-medium">+15.7%</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-500 rounded-2xl flex items-center justify-center shadow-2xl premium-glow">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Approval -->
        <div class="glass-morphism rounded-3xl p-6 card-premium premium-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-2">Pending Approval</p>
                    <p class="text-3xl font-bold text-white stats-number">{{ $stats['pending_approvals'] ?? 12 }}</p>
                    <div class="flex items-center mt-3">
                        <svg class="w-4 h-4 text-amber-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm text-amber-400 font-medium">Perlu Perhatian</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-2xl premium-glow">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Revenue Chart -->
        <div class="lg:col-span-2 glass-morphism rounded-3xl p-8 card-premium premium-glow">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white gradient-text mb-2">Revenue Analytics</h3>
                    <p class="text-gray-400">Monthly revenue trends</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center glass-morphism px-4 py-2 rounded-2xl">
                        <div class="w-3 h-3 bg-purple-500 rounded-full mr-2"></div>
                        <span class="text-sm text-gray-300">Pendapatan</span>
                    </div>
                    <div class="flex items-center glass-morphism px-4 py-2 rounded-2xl">
                        <div class="w-3 h-3 bg-emerald-500 rounded-full mr-2"></div>
                        <span class="text-sm text-gray-300">Target</span>
                    </div>
                </div>
            </div>
            <div id="revenueChart" class="h-80"></div>
        </div>

        <!-- Procedure Distribution -->
        <div class="glass-morphism rounded-3xl p-8 card-premium premium-glow">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-white gradient-text mb-2">Procedure Distribution</h3>
                <p class="text-gray-400">Breakdown by procedure type</p>
            </div>
            <div id="procedureChart" class="h-64 mb-6"></div>
            <div class="space-y-3">
                <div class="flex items-center justify-between glass-morphism px-4 py-3 rounded-2xl">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                        <span class="text-sm text-gray-300">Konsultasi</span>
                    </div>
                    <span class="text-sm font-bold text-white">45%</span>
                </div>
                <div class="flex items-center justify-between glass-morphism px-4 py-3 rounded-2xl">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-emerald-500 rounded-full mr-3"></div>
                        <span class="text-sm text-gray-300">Pemeriksaan</span>
                    </div>
                    <span class="text-sm font-bold text-white">30%</span>
                </div>
                <div class="flex items-center justify-between glass-morphism px-4 py-3 rounded-2xl">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-purple-500 rounded-full mr-3"></div>
                        <span class="text-sm text-gray-300">Tindakan</span>
                    </div>
                    <span class="text-sm font-bold text-white">25%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Transactions -->
        <div class="glass-morphism rounded-3xl p-8 card-premium premium-glow">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-white gradient-text">Recent Activity</h3>
                <button class="glass-morphism px-4 py-2 rounded-2xl text-sm font-medium text-purple-400 hover:text-purple-300 transition-colors">
                    View All
                </button>
            </div>
            <div class="space-y-4">
                @forelse($recent_procedures ?? [] as $procedure)
                <div class="flex items-center space-x-4 glass-morphism px-4 py-4 rounded-2xl hover:bg-white/10 transition-colors">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium">
                            {{ $procedure['procedure_name'] ?? 'Tindakan Medis' }}
                        </p>
                        <p class="text-sm text-gray-400">
                            {{ $procedure['patient_name'] ?? 'Pasien' }} - 
                            @if(isset($procedure['timestamp']))
                                {{ \Carbon\Carbon::parse($procedure['timestamp'])->format('H:i') }}
                            @else
                                --:--
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-emerald-400 font-bold">Rp {{ number_format($procedure['procedure_cost'] ?? 150000, 0, ',', '.') }}</p>
                    </div>
                </div>
                @empty
                <div class="flex items-center space-x-4 glass-morphism px-4 py-4 rounded-2xl">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium">Konsultasi Umum</p>
                        <p class="text-sm text-gray-400">Dr. Sarah - 10:30</p>
                    </div>
                    <div class="text-right">
                        <p class="text-emerald-400 font-bold">Rp 150.000</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4 glass-morphism px-4 py-4 rounded-2xl">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium">Pemeriksaan Lab</p>
                        <p class="text-sm text-gray-400">Lab Tech - 09:15</p>
                    </div>
                    <div class="text-right">
                        <p class="text-emerald-400 font-bold">Rp 250.000</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass-morphism rounded-3xl p-8 card-premium premium-glow">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-white gradient-text">Quick Actions</h3>
                <p class="text-gray-400">Frequently used functions</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <button class="glass-morphism p-6 rounded-2xl hover:bg-white/10 transition-all duration-300 card-premium group">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <p class="text-white font-bold mb-1">Add Patient</p>
                    <p class="text-gray-400 text-sm">Register new patient</p>
                </button>
                <button class="glass-morphism p-6 rounded-2xl hover:bg-white/10 transition-all duration-300 card-premium group">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-500 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <p class="text-white font-bold mb-1">New Procedure</p>
                    <p class="text-gray-400 text-sm">Record medical procedure</p>
                </button>
                <button class="glass-morphism p-6 rounded-2xl hover:bg-white/10 transition-all duration-300 card-premium group">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-white font-bold mb-1">Add Income</p>
                    <p class="text-gray-400 text-sm">Record revenue</p>
                </button>
                <button class="glass-morphism p-6 rounded-2xl hover:bg-white/10 transition-all duration-300 card-premium group">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <p class="text-white font-bold mb-1">Generate Report</p>
                    <p class="text-gray-400 text-sm">Export financial data</p>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueOptions = {
        series: [{
            name: 'Pendapatan',
            data: [8500000, 9200000, 7800000, 10500000, 9800000, 11200000, 10800000, 12500000, 11800000, 13200000, 12800000, 14500000]
        }, {
            name: 'Target',
            data: [8000000, 8500000, 9000000, 9500000, 10000000, 10500000, 11000000, 11500000, 12000000, 12500000, 13000000, 13500000]
        }],
        chart: {
            height: 320,
            type: 'area',
            background: 'transparent',
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        colors: ['#8b5cf6', '#10b981'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.1,
                stops: [0, 100]
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: { style: { colors: '#9ca3af' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: { 
                style: { colors: '#9ca3af' },
                formatter: function(value) { return 'Rp ' + (value/1000000).toFixed(1) + 'M'; }
            }
        },
        grid: {
            borderColor: '#374151',
            strokeDashArray: 5
        },
        legend: { show: false },
        tooltip: {
            theme: 'dark',
            style: { fontSize: '12px' },
            y: {
                formatter: function(value) { return 'Rp ' + value.toLocaleString(); }
            }
        }
    };

    const revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
    revenueChart.render();

    // Procedure Distribution Chart
    const procedureOptions = {
        series: [45, 30, 25],
        chart: {
            height: 256,
            type: 'donut',
            background: 'transparent'
        },
        colors: ['#3b82f6', '#10b981', '#8b5cf6'],
        labels: ['Konsultasi', 'Pemeriksaan', 'Tindakan'],
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Total',
                            fontSize: '14px',
                            fontWeight: 'bold',
                            color: '#9ca3af'
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 'bold',
                            color: '#ffffff'
                        }
                    }
                }
            }
        },
        legend: { show: false },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) { return value + '%'; }
            }
        }
    };

    const procedureChart = new ApexCharts(document.querySelector("#procedureChart"), procedureOptions);
    procedureChart.render();
});
</script>
@endpush
@endsection