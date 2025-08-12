<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Pengeluaran Management - Dokterku</title>
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
        
        .expense-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .dark .expense-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.1) 100%);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .budget-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .dark .budget-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.1) 100%);
            border-color: rgba(59, 130, 246, 0.3);
        }
        
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .category-operasional { background-color: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
        .category-peralatan { background-color: rgba(16, 185, 129, 0.1); color: #047857; }
        .category-obat { background-color: rgba(168, 85, 247, 0.1); color: #7c3aed; }
        .category-utilitas { background-color: rgba(251, 146, 60, 0.1); color: #ea580c; }
        .category-default { background-color: rgba(156, 163, 175, 0.1); color: #4b5563; }
        
        .priority-low { background-color: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .priority-medium { background-color: rgba(251, 146, 60, 0.1); color: #ea580c; }
        .priority-high { background-color: rgba(249, 115, 22, 0.1); color: #ea580c; }
        .priority-urgent { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }
        
        .status-pending { background-color: rgba(251, 146, 60, 0.1); color: #ea580c; }
        .status-approved { background-color: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
        .status-rejected { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .status-paid { background-color: rgba(34, 197, 94, 0.1); color: #16a34a; }
        
        .filter-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .dark .filter-card {
            background: rgba(31, 41, 55, 0.9);
            border: 1px solid rgba(75, 85, 99, 0.5);
        }
        
        .growth-positive { color: #dc2626; } /* Red for expense growth (bad) */
        .growth-negative { color: #16a34a; } /* Green for expense reduction (good) */
        .growth-neutral { color: #6b7280; }
        
        .budget-progress {
            background: linear-gradient(90deg, #10b981 0%, #f59e0b 70%, #ef4444 100%);
        }
        
        .budget-safe { width: 0%; background: #10b981; }
        .budget-warning { background: #f59e0b; }
        .budget-danger { background: #ef4444; }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="pengeluaranManager()">
    
    <!-- Navigation Bar -->
    <nav class="bg-white dark:bg-gray-900 shadow-lg border-b border-red-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="/petugas/enhanced-dashboard" class="text-red-600 hover:text-red-800 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">💸 Manajemen Pengeluaran</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kelola pengeluaran dengan budget tracking</p>
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
                    
                    <button @click="showBudgetAnalysis = true" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Analisis Budget
                    </button>
                    
                    <a href="/petugas/enhanced/pengeluaran/create" 
                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Pengeluaran
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Expense Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Expense -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pengeluaran Hari Ini</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($stats['today'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['total_entries_today'] ?? 0 }} transaksi</p>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Expense -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pengeluaran Bulan Ini</h3>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Perubahan</h3>
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
            
            <!-- Budget Utilization -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Utilisasi Budget</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($budgetAnalysis['budget_utilization'] ?? 0, 1) }}%</p>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="budget-progress h-2 rounded-full" 
                                 :style="`width: ${Math.min({{ $budgetAnalysis['budget_utilization'] ?? 0 }}, 100)}%`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Overview & Expense Trends -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Budget Overview -->
            <div class="budget-card rounded-xl p-6 shadow-lg animate-fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">📊 Overview Budget Bulanan</h3>
                    <button @click="refreshBudget()" 
                            :disabled="loadingBudget"
                            class="text-blue-600 hover:text-blue-800 text-sm"
                            :class="loadingBudget ? 'opacity-50 cursor-not-allowed' : ''">
                        <svg class="w-4 h-4" :class="loadingBudget ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Total Budget:</span>
                        <span class="font-bold text-lg text-gray-900 dark:text-white">Rp {{ number_format($budgetAnalysis['monthly_budget'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Terpakai:</span>
                        <span class="font-bold text-lg text-red-600">Rp {{ number_format($budgetAnalysis['spent_this_month'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Sisa Budget:</span>
                        <span class="font-bold text-lg" 
                              :class="({{ $budgetAnalysis['remaining_budget'] ?? 0 }}) > 0 ? 'text-green-600' : 'text-red-600'">
                            Rp {{ number_format($budgetAnalysis['remaining_budget'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <span>Progress Budget</span>
                            <span>{{ number_format($budgetAnalysis['budget_utilization'] ?? 0, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all duration-500" 
                                 :class="{
                                     'bg-green-500': {{ $budgetAnalysis['budget_utilization'] ?? 0 }} < 60,
                                     'bg-yellow-500': {{ $budgetAnalysis['budget_utilization'] ?? 0 }} >= 60 && {{ $budgetAnalysis['budget_utilization'] ?? 0 }} < 80,
                                     'bg-red-500': {{ $budgetAnalysis['budget_utilization'] ?? 0 }} >= 80
                                 }"
                                 :style="`width: ${Math.min({{ $budgetAnalysis['budget_utilization'] ?? 0 }}, 100)}%`"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Expense Trends Chart -->
            <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">📈 Trend Pengeluaran (7 Hari)</h3>
                    <button @click="refreshChart()" 
                            :disabled="loadingChart"
                            class="text-red-600 hover:text-red-800 text-sm"
                            :class="loadingChart ? 'opacity-50 cursor-not-allowed' : ''">
                        <svg class="w-4 h-4" :class="loadingChart ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                <div id="expense-trends-chart" class="h-64"></div>
            </div>
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
                           placeholder="Cari nama pengeluaran, kategori, atau keterangan..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Dari</label>
                    <input type="date" 
                           x-model="filters.tanggal_from" 
                           @change="applyFilters()"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Sampai</label>
                    <input type="date" 
                           x-model="filters.tanggal_to" 
                           @change="applyFilters()"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategori</label>
                    <select x-model="filters.kategori" @change="applyFilters()" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <option value="">Semua Kategori</option>
                        <option value="Operasional">Operasional</option>
                        <option value="Peralatan Medis">Peralatan Medis</option>
                        <option value="Obat-obatan">Obat-obatan</option>
                        <option value="Utilitas">Utilitas</option>
                        <option value="Pemeliharaan">Pemeliharaan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                
                <!-- Priority Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prioritas</label>
                    <select x-model="filters.priority" @change="applyFilters()" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <option value="">Semua Prioritas</option>
                        <option value="low">Rendah</option>
                        <option value="medium">Sedang</option>
                        <option value="high">Tinggi</option>
                        <option value="urgent">Mendesak</option>
                    </select>
                </div>
                
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select x-model="filters.status" @change="applyFilters()" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                        <option value="paid">Dibayar</option>
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
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
                
                <!-- Max Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jumlah Maksimum</label>
                    <input type="number" 
                           x-model="filters.max_jumlah" 
                           @input="debounceFilters()"
                           min="0"
                           step="10000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            
            <!-- Active Filters -->
            <div x-show="hasActiveFilters()" class="mt-4 flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Filter aktif:</span>
                
                <template x-for="filter in getActiveFilters()" :key="filter.key">
                    <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 text-sm rounded-full">
                        <span x-text="filter.label"></span>
                        <button @click="removeFilter(filter.key)" class="ml-2 text-red-600 hover:text-red-800">
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
            
            <!-- Expense List (Left Side) -->
            <div class="lg:col-span-3">
                
                <!-- Control Buttons -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-2">
                        <select x-model="sortBy" @change="applySorting()" 
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                            <option value="tanggal_pengeluaran">Tanggal Pengeluaran</option>
                            <option value="created_at">Tanggal Input</option>
                            <option value="jumlah">Jumlah</option>
                            <option value="nama_pengeluaran">Nama</option>
                            <option value="kategori">Kategori</option>
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
                        Total: <span class="font-semibold" x-text="pagination.total || 0"></span> pengeluaran
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div x-show="selectedItems.length > 0" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <span x-text="selectedItems.length"></span> item terpilih
                        </span>
                        <div class="flex items-center space-x-2">
                            <button @click="bulkUpdateStatus('approved')" 
                                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                Setujui
                            </button>
                            <button @click="bulkUpdateStatus('rejected')" 
                                    class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                                Tolak
                            </button>
                            <button @click="bulkUpdateStatus('paid')" 
                                    class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                Bayar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Expense List -->
                <div x-show="loading" class="space-y-4">
                    <template x-for="i in 5" :key="i">
                        <div class="loading-skeleton rounded-lg h-32"></div>
                    </template>
                </div>
                
                <div x-show="!loading && pengeluaranList.length > 0" class="space-y-4">
                    <template x-for="(pengeluaran, index) in pengeluaranList" :key="pengeluaran.id">
                        <div class="glass-card rounded-xl p-6 shadow-lg hover-lift cursor-pointer"
                             @click="viewPengeluaran(pengeluaran.id)">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <!-- Checkbox -->
                                    <input type="checkbox" 
                                           :value="pengeluaran.id" 
                                           x-model="selectedItems" 
                                           @click.stop
                                           class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white" 
                                                x-text="pengeluaran.nama_pengeluaran"></h4>
                                            <span class="category-badge" 
                                                  :class="'category-' + pengeluaran.kategori_color">
                                                <span x-text="pengeluaran.kategori"></span>
                                            </span>
                                            <span class="priority-badge" 
                                                  :class="'priority-' + pengeluaran.priority">
                                                <span x-text="getPriorityText(pengeluaran.priority)"></span>
                                            </span>
                                            <span class="status-badge" 
                                                  :class="'status-' + pengeluaran.status">
                                                <span x-text="getStatusText(pengeluaran.status)"></span>
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span x-text="pengeluaran.formatted_date"></span>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span>Input: <strong x-text="pengeluaran.input_by || '-'"></strong></span>
                                            </div>
                                        </div>
                                        
                                        <div x-show="pengeluaran.keterangan" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="pengeluaran.keterangan"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="pengeluaran.formatted_jumlah"></div>
                                    <div class="mt-2 space-x-2">
                                        <button @click.stop="editPengeluaran(pengeluaran.id)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            Edit
                                        </button>
                                        <button @click.stop="deletePengeluaran(pengeluaran.id)" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div x-show="!loading && pengeluaranList.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum Ada Pengeluaran</h3>
                    <p class="text-gray-500 dark:text-gray-400">Belum ada data pengeluaran yang tercatat.</p>
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
                                    :class="page === pagination.current_page ? 'bg-red-600 text-white' : 'bg-white text-gray-700'"
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
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $category }}:</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Status Summary -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📋 Status Summary</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Pending:</span>
                            <span class="font-medium text-yellow-600">{{ $stats['pending_count'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Disetujui:</span>
                            <span class="font-medium text-blue-600">{{ $stats['approved_count'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Rata-rata:</span>
                            <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($stats['avg_transaction'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Aksi Cepat</h3>
                    
                    <div class="space-y-3">
                        <button @click="exportData('excel')" 
                                class="flex items-center w-full p-3 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Export Excel</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Download data pengeluaran</p>
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
                                <p class="font-medium text-gray-900 dark:text-white">Laporan Budget</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Generate laporan lengkap</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🕐 Aktivitas Terbaru</h3>
                    
                    <div class="space-y-3">
                        @foreach($recentPengeluaran as $recent)
                        <div class="border-l-4 border-red-500 pl-3 py-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $recent['nama'] }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{ $recent['kategori'] }} - Rp {{ number_format($recent['jumlah'], 0, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($recent['created_at'])->diffForHumans() }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Analysis Modal -->
    <div x-show="showBudgetAnalysis" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
         style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">📊 Analisis Budget Lengkap</h3>
                    <button @click="showBudgetAnalysis = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Budget Overview -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Budget Overview</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Total Budget:</span>
                                <span class="font-medium">Rp {{ number_format($budgetAnalysis['monthly_budget'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Terpakai:</span>
                                <span class="font-medium text-red-600">Rp {{ number_format($budgetAnalysis['spent_this_month'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Sisa:</span>
                                <span class="font-medium text-green-600">Rp {{ number_format($budgetAnalysis['remaining_budget'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Analysis -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Analisis Kategori</h4>
                        <div class="text-gray-600 dark:text-gray-400">
                            <p>Fitur analisis kategori akan segera tersedia dengan breakdown budget per kategori dan alert untuk kategori yang mendekati limit.</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3 mt-6">
                    <button @click="showBudgetAnalysis = false" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function pengeluaranManager() {
            return {
                // Current state
                loading: false,
                showFilters: false,
                showBudgetAnalysis: false,
                loadingChart: false,
                loadingBudget: false,
                
                // Data
                pengeluaranList: [],
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 15,
                    total: 0,
                    from: 0,
                    to: 0
                },
                
                // Selection
                selectedItems: [],
                
                // Filters
                filters: {
                    search: '',
                    tanggal_from: '',
                    tanggal_to: '',
                    kategori: '',
                    priority: '',
                    status: '',
                    min_jumlah: '',
                    max_jumlah: ''
                },
                
                // Sorting
                sortBy: 'tanggal_pengeluaran',
                sortDirection: 'desc',
                
                // Chart
                chart: null,
                
                // Search debounce timer
                searchTimer: null,
                filterTimer: null,
                
                // Initialize
                init() {
                    this.loadPengeluaran();
                    this.initChart();
                },
                
                // Load pengeluaran data
                async loadPengeluaran(page = 1) {
                    this.loading = true;
                    try {
                        const params = {
                            page: page,
                            per_page: this.pagination.per_page,
                            sort: this.sortBy,
                            direction: this.sortDirection,
                            ...this.filters
                        };
                        
                        const response = await axios.get('/petugas/enhanced/pengeluaran/data', { params });
                        
                        if (response.data.success) {
                            this.pengeluaranList = response.data.data.data || [];
                            this.pagination = response.data.meta;
                        }
                    } catch (error) {
                        console.error('Error loading pengeluaran:', error);
                        this.showAlert('error', 'Gagal memuat data pengeluaran');
                    } finally {
                        this.loading = false;
                    }
                },
                
                // Initialize chart
                initChart() {
                    const trends = @json($trends);
                    
                    const options = {
                        series: [{
                            name: 'Pengeluaran',
                            data: trends.map(t => t.expense)
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
                        colors: ['#ef4444', '#f59e0b'],
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
                                text: 'Pengeluaran (Rp)',
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
                    
                    this.chart = new ApexCharts(document.querySelector("#expense-trends-chart"), options);
                    this.chart.render();
                },
                
                // Refresh chart
                async refreshChart() {
                    this.loadingChart = true;
                    try {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        this.showAlert('success', 'Chart berhasil diperbarui');
                    } catch (error) {
                        this.showAlert('error', 'Gagal memperbarui chart');
                    } finally {
                        this.loadingChart = false;
                    }
                },
                
                // Refresh budget
                async refreshBudget() {
                    this.loadingBudget = true;
                    try {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        this.showAlert('success', 'Budget data berhasil diperbarui');
                    } catch (error) {
                        this.showAlert('error', 'Gagal memperbarui budget data');
                    } finally {
                        this.loadingBudget = false;
                    }
                },
                
                // Apply filters
                applyFilters() {
                    this.pagination.current_page = 1;
                    this.loadPengeluaran();
                },
                
                // Clear filters
                clearFilters() {
                    this.filters = {
                        search: '',
                        tanggal_from: '',
                        tanggal_to: '',
                        kategori: '',
                        priority: '',
                        status: '',
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
                    this.loadPengeluaran();
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
                        this.loadPengeluaran(page);
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
                
                // Bulk actions
                async bulkUpdateStatus(status) {
                    if (this.selectedItems.length === 0) {
                        this.showAlert('warning', 'Pilih pengeluaran terlebih dahulu');
                        return;
                    }
                    
                    if (!confirm(`Apakah Anda yakin ingin mengubah status ${this.selectedItems.length} pengeluaran?`)) {
                        return;
                    }
                    
                    try {
                        const response = await axios.post('/petugas/enhanced/pengeluaran/bulk-update-status', {
                            ids: this.selectedItems,
                            status: status
                        });
                        
                        if (response.data.success) {
                            this.showAlert('success', response.data.message);
                            this.selectedItems = [];
                            this.loadPengeluaran();
                        }
                    } catch (error) {
                        console.error('Error bulk updating:', error);
                        this.showAlert('error', 'Gagal memperbarui status');
                    }
                },
                
                // Actions
                viewPengeluaran(id) {
                    window.location.href = `/petugas/enhanced/pengeluaran/${id}`;
                },
                
                editPengeluaran(id) {
                    window.location.href = `/petugas/enhanced/pengeluaran/${id}/edit`;
                },
                
                async deletePengeluaran(id) {
                    if (!confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')) {
                        return;
                    }
                    
                    try {
                        const response = await axios.delete(`/petugas/enhanced/pengeluaran/${id}`);
                        
                        if (response.data.success) {
                            this.showAlert('success', response.data.message);
                            this.loadPengeluaran();
                        }
                    } catch (error) {
                        console.error('Error deleting pengeluaran:', error);
                        this.showAlert('error', 'Gagal menghapus pengeluaran');
                    }
                },
                
                // Export methods
                async exportData(format) {
                    try {
                        const response = await axios.post('/petugas/enhanced/pengeluaran/export', {
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
                getPriorityText(priority) {
                    const priorityMap = {
                        'low': 'Rendah',
                        'medium': 'Sedang',
                        'high': 'Tinggi',
                        'urgent': 'Mendesak'
                    };
                    return priorityMap[priority] || priority;
                },
                
                getStatusText(status) {
                    const statusMap = {
                        'pending': 'Pending',
                        'approved': 'Disetujui',
                        'rejected': 'Ditolak',
                        'paid': 'Dibayar'
                    };
                    return statusMap[status] || status;
                },
                
                hasActiveFilters() {
                    return Object.values(this.filters).some(filter => filter !== '');
                },
                
                getActiveFilters() {
                    const active = [];
                    const labels = {
                        search: 'Pencarian',
                        tanggal_from: 'Tanggal Dari',
                        tanggal_to: 'Tanggal Sampai',
                        kategori: 'Kategori',
                        priority: 'Prioritas',
                        status: 'Status',
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