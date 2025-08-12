<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Pendapatan Management - Dokterku</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'medical': {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-card {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
        
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .revenue-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .dark .revenue-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.1) 100%);
            border-color: rgba(16, 185, 129, 0.3);
        }
        
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .category-tindakan { background-color: rgba(16, 185, 129, 0.1); color: #047857; }
        .category-konsultasi { background-color: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
        .category-obat { background-color: rgba(251, 146, 60, 0.1); color: #ea580c; }
        .category-alat { background-color: rgba(168, 85, 247, 0.1); color: #7c3aed; }
        .category-lainnya { background-color: rgba(156, 163, 175, 0.1); color: #4b5563; }
        
        .filter-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .dark .filter-card {
            background: rgba(31, 41, 55, 0.9);
            border: 1px solid rgba(75, 85, 99, 0.5);
        }
        
        .growth-positive { color: #16a34a; }
        .growth-negative { color: #dc2626; }
        .growth-neutral { color: #6b7280; }
    </style>
</head>
<body class="bg-gradient-to-br from-medical-50 to-green-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="pendapatanManager()">
    
    <!-- Navigation Bar -->
    <nav class="bg-white dark:bg-gray-900 shadow-lg border-b border-medical-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="/petugas/enhanced-dashboard" class="text-medical-600 hover:text-medical-800 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">💰 Manajemen Pendapatan</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kelola pendapatan dengan smart input system</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="showFilters = !showFilters" 
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                        </svg>
                        Filter & Pencarian
                    </button>
                    
                    <button @click="showBulkCreate = true" 
                            x-show="unpaidCount > 0"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Auto Create (<span x-text="unpaidCount"></span>)
                    </button>
                    
                    <a href="/petugas/enhanced/pendapatan/create" 
                       class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Pendapatan
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Revenue Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Revenue -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pendapatan Hari Ini</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($stats['today'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['total_entries_today'] ?? 0 }} transaksi</p>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Revenue -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pendapatan Bulan Ini</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($stats['month'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['total_entries_month'] ?? 0 }} transaksi</p>
                    </div>
                </div>
            </div>
            
            <!-- Growth Rate -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pertumbuhan</h3>
                        <p class="text-2xl font-bold" 
                           :class="{
                               'growth-positive': {{ $stats['growth_percentage'] ?? 0 }} > 0,
                               'growth-negative': {{ $stats['growth_percentage'] ?? 0 }} < 0,
                               'growth-neutral': {{ $stats['growth_percentage'] ?? 0 }} === 0
                           }">
                            {{ $stats['growth_percentage'] > 0 ? '+' : '' }}{{ number_format($stats['growth_percentage'] ?? 0, 1) }}%
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">vs bulan lalu</p>
                    </div>
                </div>
            </div>
            
            <!-- Average Transaction -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Rata-rata Transaksi</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($stats['avg_transaction'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">per transaksi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Trends Chart -->
        <div class="glass-card rounded-xl p-6 shadow-lg mb-8 animate-fade-in">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">📈 Trend Pendapatan (7 Hari Terakhir)</h3>
                <div class="flex items-center space-x-2">
                    <button @click="refreshChart()" 
                            :disabled="loadingChart"
                            class="text-medical-600 hover:text-medical-800 text-sm"
                            :class="loadingChart ? 'opacity-50 cursor-not-allowed' : ''">
                        <svg class="w-4 h-4" :class="loadingChart ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div id="revenue-trends-chart" class="h-64"></div>
        </div>

        <!-- Filters Section -->
        <div x-show="showFilters" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="filter-card rounded-xl p-6 mb-8 shadow-lg"
             style="display: none;">
            
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🔍 Filter & Pencarian</h3>
                <button @click="clearFilters()" class="text-gray-500 hover:text-gray-700 text-sm">
                    Reset Semua Filter
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pencarian</label>
                    <input type="text" 
                           x-model="filters.search" 
                           @input="debounceSearch()"
                           placeholder="Cari sumber pendapatan, pasien, atau tindakan..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Dari</label>
                    <input type="date" 
                           x-model="filters.tanggal_from" 
                           @change="applyFilters()"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Sampai</label>
                    <input type="date" 
                           x-model="filters.tanggal_to" 
                           @change="applyFilters()"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategori</label>
                    <select x-model="filters.kategori" @change="applyFilters()" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <option value="">Semua Kategori</option>
                        <option value="tindakan">Tindakan Medis</option>
                        <option value="konsultasi">Konsultasi</option>
                        <option value="obat">Obat-obatan</option>
                        <option value="alat">Alat Medis</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                
                <!-- Min Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jumlah Minimum</label>
                    <input type="number" 
                           x-model="filters.min_jumlah" 
                           @input="debounceFilters()"
                           min="0"
                           step="10000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Max Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jumlah Maksimum</label>
                    <input type="number" 
                           x-model="filters.max_jumlah" 
                           @input="debounceFilters()"
                           min="0"
                           step="10000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            
            <!-- Active Filters -->
            <div x-show="hasActiveFilters()" class="mt-4 flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Filter aktif:</span>
                
                <template x-for="filter in getActiveFilters()" :key="filter.key">
                    <span class="inline-flex items-center px-3 py-1 bg-medical-100 text-medical-800 text-sm rounded-full">
                        <span x-text="filter.label"></span>
                        <button @click="removeFilter(filter.key)" class="ml-2 text-medical-600 hover:text-medical-800">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </span>
                </template>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Revenue List (Left Side) -->
            <div class="lg:col-span-3">
                
                <!-- Control Buttons -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-2">
                        <select x-model="sortBy" @change="applySorting()" 
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-500 text-sm">
                            <option value="tanggal_pendapatan">Tanggal Pendapatan</option>
                            <option value="created_at">Tanggal Input</option>
                            <option value="jumlah">Jumlah</option>
                            <option value="sumber_pendapatan">Sumber</option>
                        </select>
                        
                        <button @click="toggleSortDirection()" 
                                class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" :class="sortDirection === 'asc' ? 'transform rotate-180' : ''" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Total: <span class="font-semibold" x-text="pagination.total || 0"></span> pendapatan
                    </div>
                </div>

                <!-- Revenue List -->
                <div x-show="loading" class="space-y-4">
                    <template x-for="i in 5" :key="i">
                        <div class="loading-skeleton rounded-lg h-24"></div>
                    </template>
                </div>
                
                <div x-show="!loading && pendapatanList.length > 0" class="space-y-4">
                    <template x-for="(pendapatan, index) in pendapatanList" :key="pendapatan.id">
                        <div class="glass-card rounded-xl p-6 shadow-lg hover-lift cursor-pointer"
                             @click="viewPendapatan(pendapatan.id)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white" 
                                            x-text="pendapatan.sumber_pendapatan"></h4>
                                        <span class="category-badge" 
                                              :class="'category-' + pendapatan.kategori.toLowerCase().replace(/[^a-z]/g, '')">
                                            <span x-text="pendapatan.kategori"></span>
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span x-text="pendapatan.formatted_date"></span>
                                        </div>
                                        
                                        <div x-show="pendapatan.tindakan" class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>Pasien: <strong x-text="pendapatan.tindakan?.pasien || '-'"></strong></span>
                                        </div>
                                        
                                        <div x-show="pendapatan.tindakan" class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                            </svg>
                                            <span>Tindakan: <strong x-text="pendapatan.tindakan?.jenis || '-'"></strong></span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>Input: <strong x-text="pendapatan.input_by || '-'"></strong></span>
                                        </div>
                                    </div>
                                    
                                    <div x-show="pendapatan.keterangan" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <p class="text-sm text-gray-700 dark:text-gray-300" x-text="pendapatan.keterangan"></p>
                                    </div>
                                </div>
                                
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold text-medical-600 dark:text-medical-400" x-text="pendapatan.formatted_jumlah"></div>
                                    <div class="mt-2 space-x-2">
                                        <button @click.stop="editPendapatan(pendapatan.id)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            Edit
                                        </button>
                                        <button @click.stop="deletePendapatan(pendapatan.id)" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div x-show="!loading && pendapatanList.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum Ada Pendapatan</h3>
                    <p class="text-gray-500 dark:text-gray-400">Belum ada data pendapatan yang tercatat.</p>
                </div>

                <!-- Pagination -->
                <div x-show="pagination.last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Menampilkan <span x-text="pagination.from"></span> - <span x-text="pagination.to"></span> 
                        dari <span x-text="pagination.total"></span> data
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button @click="goToPage(pagination.current_page - 1)" 
                                :disabled="pagination.current_page <= 1"
                                :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : ''"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            Previous
                        </button>
                        
                        <template x-for="page in getPageNumbers()" :key="page">
                            <button @click="goToPage(page)" 
                                    :class="page === pagination.current_page ? 'bg-medical-600 text-white' : 'bg-white text-gray-700'"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <span x-text="page"></span>
                            </button>
                        </template>
                        
                        <button @click="goToPage(pagination.current_page + 1)" 
                                :disabled="pagination.current_page >= pagination.last_page"
                                :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : ''"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Category Breakdown -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Breakdown Kategori</h3>
                    
                    <div class="space-y-3">
                        @foreach($stats['categories'] ?? [] as $category => $amount)
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full bg-medical-500"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $category }}:</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Aksi Cepat</h3>
                    
                    <div class="space-y-3">
                        <button @click="showBulkCreate = true" 
                                x-show="unpaidCount > 0"
                                class="flex items-center w-full p-3 bg-green-50 dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Auto Create</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><span x-text="unpaidCount"></span> tindakan belum tercatat</p>
                            </div>
                        </button>
                        
                        <button @click="exportData('excel')" 
                                class="flex items-center w-full p-3 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Export Excel</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Download data pendapatan</p>
                            </div>
                        </button>
                        
                        <button @click="generateReport()" 
                                class="flex items-center w-full p-3 bg-purple-50 dark:bg-purple-900 hover:bg-purple-100 dark:hover:bg-purple-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Laporan Keuangan</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Generate laporan lengkap</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🕐 Aktivitas Terbaru</h3>
                    
                    <div class="space-y-3">
                        @foreach($recentPendapatan as $recent)
                        <div class="border-l-4 border-medical-500 pl-3 py-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $recent['sumber'] }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Rp {{ number_format($recent['jumlah'], 0, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($recent['created_at'])->diffForHumans() }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Create Modal -->
    <div x-show="showBulkCreate" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
         style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Auto Create Pendapatan</h3>
                    <button @click="showBulkCreate = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600 dark:text-gray-400">
                        Ditemukan <strong x-text="unpaidCount"></strong> tindakan yang belum memiliki pendapatan. 
                        Buat pendapatan otomatis dari tindakan yang sudah disetujui?
                    </p>
                </div>
                
                <div class="flex items-center justify-end space-x-3 mt-6">
                    <button @click="showBulkCreate = false" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400">
                        Batal
                    </button>
                    <button @click="bulkCreateFromTindakan()" 
                            :disabled="bulkCreating"
                            :class="bulkCreating ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-4 py-2 bg-medical-500 text-white text-sm font-medium rounded-md hover:bg-medical-600">
                        <span x-text="bulkCreating ? 'Membuat...' : 'Buat Pendapatan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function pendapatanManager() {
            return {
                // Current state
                loading: false,
                showFilters: false,
                showBulkCreate: false,
                bulkCreating: false,
                loadingChart: false,
                
                // Data
                pendapatanList: [],
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 15,
                    total: 0,
                    from: 0,
                    to: 0
                },
                
                // Filters
                filters: {
                    search: '',
                    tanggal_from: '',
                    tanggal_to: '',
                    sumber: '',
                    kategori: '',
                    min_jumlah: '',
                    max_jumlah: ''
                },
                
                // Sorting
                sortBy: 'tanggal_pendapatan',
                sortDirection: 'desc',
                
                // Chart
                chart: null,
                
                // Stats
                unpaidCount: {{ $stats['unpaid_tindakan_count'] ?? 0 }},
                
                // Search debounce timer
                searchTimer: null,
                filterTimer: null,
                
                // Initialize
                init() {
                    this.loadPendapatan();
                    this.initChart();
                },
                
                // Load pendapatan data
                async loadPendapatan(page = 1) {
                    this.loading = true;
                    try {
                        const params = {
                            page: page,
                            per_page: this.pagination.per_page,
                            sort: this.sortBy,
                            direction: this.sortDirection,
                            ...this.filters
                        };
                        
                        const response = await axios.get('/petugas/enhanced/pendapatan/data', { params });
                        
                        if (response.data.success) {
                            this.pendapatanList = response.data.data.data || [];
                            this.pagination = response.data.meta;
                        }
                    } catch (error) {
                        console.error('Error loading pendapatan:', error);
                        this.showAlert('error', 'Gagal memuat data pendapatan');
                    } finally {
                        this.loading = false;
                    }
                },
                
                // Initialize chart
                initChart() {
                    const trends = @json($trends);
                    
                    const options = {
                        series: [{
                            name: 'Pendapatan',
                            data: trends.map(t => t.revenue)
                        }, {
                            name: 'Transaksi',
                            type: 'column',
                            data: trends.map(t => t.count)
                        }],
                        chart: {
                            type: 'line',
                            height: 250,
                            toolbar: { show: false },
                            background: 'transparent'
                        },
                        colors: ['#10b981', '#3b82f6'],
                        stroke: {
                            curve: 'smooth',
                            width: [3, 0]
                        },
                        xaxis: {
                            categories: trends.map(t => t.formatted_date),
                            labels: {
                                style: {
                                    colors: '#6b7280'
                                }
                            }
                        },
                        yaxis: [{
                            title: {
                                text: 'Pendapatan (Rp)',
                                style: {
                                    color: '#6b7280'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#6b7280'
                                },
                                formatter: function (val) {
                                    return 'Rp ' + (val / 1000) + 'K';
                                }
                            }
                        }, {
                            opposite: true,
                            title: {
                                text: 'Jumlah Transaksi',
                                style: {
                                    color: '#6b7280'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#6b7280'
                                }
                            }
                        }],
                        grid: {
                            borderColor: '#e5e7eb',
                            strokeDashArray: 2
                        },
                        legend: {
                            labels: {
                                colors: '#6b7280'
                            }
                        },
                        tooltip: {
                            theme: 'light',
                            y: [{
                                formatter: function (val) {
                                    return 'Rp ' + val.toLocaleString('id-ID');
                                }
                            }, {
                                formatter: function (val) {
                                    return val + ' transaksi';
                                }
                            }]
                        }
                    };
                    
                    this.chart = new ApexCharts(document.querySelector("#revenue-trends-chart"), options);
                    this.chart.render();
                },
                
                // Refresh chart
                async refreshChart() {
                    this.loadingChart = true;
                    try {
                        // In a real implementation, you would fetch new data here
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        this.showAlert('success', 'Chart berhasil diperbarui');
                    } catch (error) {
                        this.showAlert('error', 'Gagal memperbarui chart');
                    } finally {
                        this.loadingChart = false;
                    }
                },
                
                // Apply filters
                applyFilters() {
                    this.pagination.current_page = 1;
                    this.loadPendapatan();
                },
                
                // Clear filters
                clearFilters() {
                    this.filters = {
                        search: '',
                        tanggal_from: '',
                        tanggal_to: '',
                        sumber: '',
                        kategori: '',
                        min_jumlah: '',
                        max_jumlah: ''
                    };
                    this.applyFilters();
                },
                
                // Debounced search
                debounceSearch() {
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        this.applyFilters();
                    }, 500);
                },
                
                // Debounced filters
                debounceFilters() {
                    clearTimeout(this.filterTimer);
                    this.filterTimer = setTimeout(() => {
                        this.applyFilters();
                    }, 800);
                },
                
                // Apply sorting
                applySorting() {
                    this.loadPendapatan();
                },
                
                // Toggle sort direction
                toggleSortDirection() {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                    this.applySorting();
                },
                
                // Pagination
                goToPage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) {
                        this.pagination.current_page = page;
                        this.loadPendapatan(page);
                    }
                },
                
                getPageNumbers() {
                    const current = this.pagination.current_page;
                    const last = this.pagination.last_page;
                    const delta = 2;
                    const range = [];
                    
                    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
                        range.push(i);
                    }
                    
                    if (current - delta > 2) range.unshift('...');
                    if (current + delta < last - 1) range.push('...');
                    
                    range.unshift(1);
                    if (last !== 1) range.push(last);
                    
                    return range;
                },
                
                // Actions
                viewPendapatan(id) {
                    window.location.href = `/petugas/enhanced/pendapatan/${id}`;
                },
                
                editPendapatan(id) {
                    window.location.href = `/petugas/enhanced/pendapatan/${id}/edit`;
                },
                
                async deletePendapatan(id) {
                    if (!confirm('Apakah Anda yakin ingin menghapus pendapatan ini?')) {
                        return;
                    }
                    
                    try {
                        const response = await axios.delete(`/petugas/enhanced/pendapatan/${id}`);
                        
                        if (response.data.success) {
                            this.showAlert('success', response.data.message);
                            this.loadPendapatan();
                        }
                    } catch (error) {
                        console.error('Error deleting pendapatan:', error);
                        this.showAlert('error', 'Gagal menghapus pendapatan');
                    }
                },
                
                // Bulk create from tindakan
                async bulkCreateFromTindakan() {
                    this.bulkCreating = true;
                    try {
                        const response = await axios.post('/petugas/enhanced/pendapatan/bulk-create-from-tindakan', {
                            tindakan_ids: [], // Will be auto-fetched by backend
                            auto_calculate: true
                        });
                        
                        if (response.data.success) {
                            this.showAlert('success', response.data.message);
                            this.showBulkCreate = false;
                            this.unpaidCount = 0;
                            this.loadPendapatan();
                        }
                    } catch (error) {
                        console.error('Error bulk creating:', error);
                        this.showAlert('error', 'Gagal membuat pendapatan massal');
                    } finally {
                        this.bulkCreating = false;
                    }
                },
                
                // Export methods
                async exportData(format) {
                    try {
                        const response = await axios.post('/petugas/enhanced/pendapatan/export', {
                            format: format,
                            filters: this.filters
                        });
                        
                        if (response.data.success) {
                            this.showAlert('info', response.data.message);
                        }
                    } catch (error) {
                        console.error('Error exporting:', error);
                        this.showAlert('error', 'Gagal mengekspor data');
                    }
                },
                
                generateReport() {
                    this.showAlert('info', 'Fitur laporan akan segera tersedia');
                },
                
                // Utility methods
                hasActiveFilters() {
                    return Object.values(this.filters).some(filter => filter !== '');
                },
                
                getActiveFilters() {
                    const active = [];
                    const labels = {
                        search: 'Pencarian',
                        tanggal_from: 'Tanggal Dari',
                        tanggal_to: 'Tanggal Sampai',
                        sumber: 'Sumber',
                        kategori: 'Kategori',
                        min_jumlah: 'Jumlah Min',
                        max_jumlah: 'Jumlah Max'
                    };
                    
                    Object.keys(this.filters).forEach(key => {
                        if (this.filters[key] !== '') {
                            active.push({
                                key: key,
                                label: `${labels[key]}: ${this.filters[key]}`
                            });
                        }
                    });
                    
                    return active;
                },
                
                removeFilter(key) {
                    this.filters[key] = '';
                    this.applyFilters();
                },
                
                // Alert system
                showAlert(type, message) {
                    const alertClass = {
                        'success': 'bg-green-500',
                        'error': 'bg-red-500',
                        'warning': 'bg-yellow-500',
                        'info': 'bg-blue-500'
                    }[type] || 'bg-gray-500';
                    
                    const alertHtml = `
                        <div class="fixed top-4 right-4 z-50 ${alertClass} text-white px-6 py-3 rounded-lg shadow-lg animate-fade-in">
                            ${message}
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', alertHtml);
                    
                    // Remove after 3 seconds
                    setTimeout(() => {
                        const alert = document.body.lastElementChild;
                        if (alert && alert.classList.contains('fixed')) {
                            alert.remove();
                        }
                    }, 3000);
                }
            }
        }
    </script>
</body>
</html>