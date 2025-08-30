@extends('layouts.enhanced')

@section('title', 'Elegant Dark - Patient Management')
@section('page-title', 'Manajemen Pasien - Elegant Dark Theme')
@section('page-description', 'World-Class Readability Patient Management System')

@push('styles')
<link href="{{ asset('css/elegant-dark-tables.css') }}" rel="stylesheet">
<style>
/* Additional Healthcare-Specific Styling */
.medical-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.875rem;
    color: white;
    margin-right: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.medical-avatar.male {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.medical-avatar.female {
    background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
}

.patient-meta {
    font-size: 0.75rem;
    color: var(--elegant-text-muted);
    margin-top: 0.25rem;
}

/* Custom Icons for Healthcare */
.icon-medical { color: #10b981; }
.icon-patient { color: #3b82f6; }
.icon-record { color: #f59e0b; }
.icon-alert { color: #ef4444; }

/* Patient Status Indicators */
.status-active { 
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #34d399;
}

.status-pending { 
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}

.status-inactive { 
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.2) 0%, rgba(107, 114, 128, 0.1) 100%);
    border: 1px solid rgba(107, 114, 128, 0.3);
    color: #9ca3af;
}

/* Quick Stats Cards */
.elegant-stats-card {
    background: linear-gradient(135deg, var(--elegant-dark-surface) 0%, var(--elegant-dark-secondary) 100%);
    border: 1px solid var(--elegant-dark-border);
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.elegant-stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--elegant-accent-blue), var(--elegant-accent-green), var(--elegant-accent-amber));
}

.elegant-stats-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--elegant-shadow-xl);
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--elegant-text-primary);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stats-label {
    font-size: 0.875rem;
    color: var(--elegant-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stats-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 2rem;
    height: 2rem;
    opacity: 0.3;
}

/* Advanced Search Panel */
.advanced-search-panel {
    background: linear-gradient(135deg, var(--elegant-dark-secondary) 0%, var(--elegant-dark-tertiary) 100%);
    border: 1px solid var(--elegant-dark-border);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--elegant-shadow-lg);
}

.search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

/* Filter Tags */
.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 1.5rem;
    color: #60a5fa;
    font-size: 0.75rem;
    font-weight: 500;
}

.filter-tag-close {
    cursor: pointer;
    padding: 0.125rem;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    transition: all 0.2s ease;
}

.filter-tag-close:hover {
    background: rgba(239, 68, 68, 0.4);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .search-grid {
        grid-template-columns: 1fr;
    }
    
    .elegant-stats-card {
        padding: 1rem;
    }
    
    .stats-number {
        font-size: 1.5rem;
    }
}
</style>
@endpush

@section('page-actions')
<div class="flex items-center space-x-4 mt-4">
    <!-- View Toggle -->
    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-1 flex">
        <button @click="viewMode = 'table'" 
                :class="viewMode === 'table' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                class="px-3 py-1 rounded-md text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18m-9 8h9"></path>
            </svg>
        </button>
        <button @click="viewMode = 'grid'" 
                :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                class="px-3 py-1 rounded-md text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
        </button>
    </div>
    
    <!-- Add Patient Button -->
    <a href="/petugas/enhanced/pasien/create" 
       class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg transition-all transform hover:scale-105 shadow-lg">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Tambah Pasien
    </a>
</div>
@endsection

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="elegantDarkPasienManager()">
    
    <!-- Quick Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="elegant-stats-card">
            <svg class="stats-icon icon-patient" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <div class="stats-number" x-text="stats.total || '0'"></div>
            <div class="stats-label">Total Pasien</div>
        </div>
        
        <div class="elegant-stats-card">
            <svg class="stats-icon icon-medical" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="stats-number" x-text="stats.active || '0'"></div>
            <div class="stats-label">Pasien Aktif</div>
        </div>
        
        <div class="elegant-stats-card">
            <svg class="stats-icon icon-record" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="stats-number" x-text="stats.pending || '0'"></div>
            <div class="stats-label">Menunggu Verifikasi</div>
        </div>
        
        <div class="elegant-stats-card">
            <svg class="stats-icon icon-alert" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <div class="stats-number" x-text="stats.thisWeek || '0'"></div>
            <div class="stats-label">Pasien Minggu Ini</div>
        </div>
    </div>

    <!-- Elegant Tab System -->
    <div class="elegant-dark-tabs">
        <nav class="elegant-dark-tab-nav">
            <button class="elegant-dark-tab active" @click="activeTab = 'all'" :class="{ 'active': activeTab === 'all' }">
                <svg class="elegant-dark-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                Semua Pasien
                <span class="elegant-dark-tab-badge" x-text="stats.total || '0'"></span>
            </button>
            
            <button class="elegant-dark-tab" @click="activeTab = 'active'" :class="{ 'active': activeTab === 'active' }">
                <svg class="elegant-dark-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Aktif
                <span class="elegant-dark-tab-badge" x-text="stats.active || '0'"></span>
            </button>
            
            <button class="elegant-dark-tab" @click="activeTab = 'pending'" :class="{ 'active': activeTab === 'pending' }">
                <svg class="elegant-dark-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Pending
                <span class="elegant-dark-tab-badge" x-text="stats.pending || '0'"></span>
            </button>
            
            <button class="elegant-dark-tab" @click="activeTab = 'recent'" :class="{ 'active': activeTab === 'recent' }">
                <svg class="elegant-dark-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Terbaru
                <span class="elegant-dark-tab-badge" x-text="stats.thisWeek || '0'"></span>
            </button>
            
            <button class="elegant-dark-tab" @click="activeTab = 'analytics'" :class="{ 'active': activeTab === 'analytics' }">
                <svg class="elegant-dark-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Analitik
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="elegant-dark-tab-content">
        
        <!-- All Patients Tab -->
        <div class="elegant-dark-tab-pane active" x-show="activeTab === 'all'" :class="{ 'active': activeTab === 'all' }">
            
            <!-- Advanced Search Panel -->
            <div class="advanced-search-panel">
                <div class="search-grid">
                    <!-- Main Search -->
                    <div class="col-span-full md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Pencarian Cerdas
                        </label>
                        <input type="text" 
                               x-model="filters.search" 
                               @input.debounce.300ms="loadPatients()"
                               placeholder="Cari nama, nomor rekam medis, telepon, atau alamat..."
                               class="elegant-dark-search-input">
                    </div>
                    
                    <!-- Gender Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Jenis Kelamin</label>
                        <select x-model="filters.jenis_kelamin" 
                                @change="loadPatients()"
                                class="elegant-dark-search-input">
                            <option value="">Semua Jenis Kelamin</option>
                            <option value="L">👨 Laki-laki</option>
                            <option value="P">👩 Perempuan</option>
                        </select>
                    </div>
                    
                    <!-- Age Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Rentang Umur</label>
                        <div class="flex space-x-2">
                            <input type="number" 
                                   x-model="filters.age_min" 
                                   @change="loadPatients()"
                                   placeholder="Min"
                                   class="elegant-dark-search-input">
                            <input type="number" 
                                   x-model="filters.age_max" 
                                   @change="loadPatients()"
                                   placeholder="Max"
                                   class="elegant-dark-search-input">
                        </div>
                    </div>
                </div>
                
                <!-- Active Filters Display -->
                <div x-show="hasActiveFilters()" class="flex flex-wrap gap-2 mt-4">
                    <span class="text-sm font-medium text-gray-300">Filter aktif:</span>
                    <template x-for="(value, key) in getActiveFilters()" :key="key">
                        <span class="filter-tag">
                            <span x-text="getFilterLabel(key, value)"></span>
                            <button @click="removeFilter(key)" class="filter-tag-close">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    </template>
                    <button @click="clearFilters()" class="text-sm text-red-400 hover:text-red-300 font-medium ml-2">
                        Hapus Semua Filter
                    </button>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-wrap gap-4 items-center justify-between mt-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-400">
                            Menampilkan <span x-text="pagination.from || 0" class="font-semibold text-blue-400"></span> - <span x-text="pagination.to || 0" class="font-semibold text-blue-400"></span> 
                            dari <span x-text="pagination.total || 0" class="font-semibold text-blue-400"></span> pasien
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Per Page -->
                        <select x-model="pagination.per_page" 
                                @change="loadPatients()"
                                class="px-3 py-1 border border-gray-600 rounded-lg text-sm bg-gray-800 text-white">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        
                        <!-- Export -->
                        <button @click="exportData()" 
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg text-sm font-medium transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Excel
                        </button>
                        
                        <!-- Bulk Actions -->
                        <button x-show="selectedPatients.length > 0" 
                                @click="showBulkDeleteModal = true"
                                class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg text-sm font-medium transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Hapus (<span x-text="selectedPatients.length"></span>)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Elegant Dark Table -->
            <div class="elegant-dark-table-container">
                <!-- Loading State -->
                <div x-show="loading" class="p-8">
                    <div class="space-y-4">
                        <div class="elegant-dark-skeleton h-4 rounded w-3/4"></div>
                        <div class="elegant-dark-skeleton h-4 rounded w-1/2"></div>
                        <div class="elegant-dark-skeleton h-4 rounded w-5/6"></div>
                        <div class="elegant-dark-skeleton h-4 rounded w-2/3"></div>
                    </div>
                </div>
                
                <!-- Table Content -->
                <div x-show="!loading" class="elegant-dark-table-wrapper">
                    <table class="elegant-dark-table">
                        <thead>
                            <tr>
                                <th class="w-12">
                                    <input type="checkbox" 
                                           @change="toggleAllPatients($event.target.checked)"
                                           class="rounded border-gray-500 bg-gray-800 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="sortable" @click="sortBy('no_rekam_medis')">
                                    <div class="flex items-center space-x-1">
                                        <span>No. Rekam Medis</span>
                                    </div>
                                </th>
                                <th class="sortable" @click="sortBy('nama')">
                                    <div class="flex items-center space-x-1">
                                        <span>Identitas Pasien</span>
                                    </div>
                                </th>
                                <th>Jenis Kelamin</th>
                                <th>Umur</th>
                                <th>Kontak</th>
                                <th>Status</th>
                                <th class="sortable" @click="sortBy('created_at')">
                                    <div class="flex items-center space-x-1">
                                        <span>Terdaftar</span>
                                    </div>
                                </th>
                                <th class="w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="patient in patients" :key="patient.id">
                                <tr @click="viewPatient(patient.id)">
                                    <td @click.stop>
                                        <input type="checkbox" 
                                               :value="patient.id"
                                               x-model="selectedPatients"
                                               class="rounded border-gray-500 bg-gray-800 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td>
                                        <div class="font-mono elegant-dark-font-semibold elegant-dark-text-sm" x-text="patient.no_rekam_medis || 'AUTO'"></div>
                                        <div class="patient-meta">ID: #<span x-text="patient.id"></span></div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <div class="medical-avatar" :class="patient.jenis_kelamin === 'L' ? 'male' : 'female'">
                                                <span x-text="patient.nama.charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div>
                                                <div class="elegant-dark-font-semibold elegant-dark-text-base text-white" x-text="patient.nama"></div>
                                                <div class="patient-meta" x-text="patient.alamat || 'Alamat tidak tersedia'"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="elegant-dark-badge" 
                                              :class="patient.jenis_kelamin === 'L' ? 'elegant-dark-badge-info' : 'elegant-dark-badge-success'">
                                            <svg class="elegant-dark-badge-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="patient.jenis_kelamin === 'L' ? 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' : 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'"></path>
                                            </svg>
                                            <span x-text="patient.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'"></span>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="elegant-dark-font-medium elegant-dark-text-base text-white">
                                            <span x-text="calculateAge(patient.tanggal_lahir)"></span> tahun
                                        </div>
                                        <div class="patient-meta" x-text="formatDate(patient.tanggal_lahir)"></div>
                                    </td>
                                    <td>
                                        <div class="elegant-dark-text-sm">
                                            <div x-text="patient.nomor_telepon || '-'" class="text-blue-400"></div>
                                            <div x-text="patient.email || '-'" class="patient-meta"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="elegant-dark-badge" 
                                              :class="{
                                                  'elegant-dark-badge-success': patient.status === 'active',
                                                  'elegant-dark-badge-warning': patient.status === 'pending',
                                                  'elegant-dark-badge-neutral': patient.status === 'inactive'
                                              }">
                                            <svg class="elegant-dark-badge-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span x-text="getStatusLabel(patient.status)"></span>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="elegant-dark-text-sm">
                                            <div x-text="formatDate(patient.created_at)" class="text-green-400"></div>
                                            <div class="patient-meta" x-text="formatTimeAgo(patient.created_at)"></div>
                                        </div>
                                    </td>
                                    <td @click.stop>
                                        <div class="elegant-dark-action-group">
                                            <a :href="'/petugas/enhanced/pasien/' + patient.id" 
                                               class="elegant-dark-action-btn view" title="Lihat Detail">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a :href="'/petugas/enhanced/pasien/' + patient.id + '/edit'" 
                                               class="elegant-dark-action-btn edit" title="Edit Data">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <button @click="deletePatient(patient.id)" 
                                                    class="elegant-dark-action-btn delete" title="Hapus Data">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            
                            <!-- Empty State -->
                            <tr x-show="!loading && patients.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                        <h3 class="text-lg font-medium text-white mb-2">Tidak ada data pasien</h3>
                                        <p class="text-gray-400 mb-6">Belum ada pasien yang terdaftar atau sesuai dengan filter pencarian.</p>
                                        <a href="/petugas/enhanced/pasien/create" 
                                           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg transition-all transform hover:scale-105">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Tambah Pasien Pertama
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Elegant Pagination -->
            <div x-show="!loading && patients.length > 0" class="elegant-dark-pagination">
                <button @click="previousPage()" 
                        :disabled="pagination.current_page <= 1"
                        :class="pagination.current_page <= 1 ? 'disabled' : ''"
                        class="elegant-dark-pagination-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Previous
                </button>
                
                <template x-for="page in getVisiblePages()" :key="page">
                    <button @click="goToPage(page)" 
                            :class="{ 'active': page === pagination.current_page }"
                            class="elegant-dark-pagination-btn"
                            x-text="page"></button>
                </template>
                
                <button @click="nextPage()" 
                        :disabled="pagination.current_page >= pagination.last_page"
                        :class="pagination.current_page >= pagination.last_page ? 'disabled' : ''"
                        class="elegant-dark-pagination-btn">
                    Next
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Other Tab Panes (Active, Pending, Recent, Analytics) -->
        <div class="elegant-dark-tab-pane" x-show="activeTab === 'active'" :class="{ 'active': activeTab === 'active' }">
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-white mb-2">Pasien Aktif</h3>
                <p class="text-gray-400">Menampilkan semua pasien dengan status aktif</p>
                <!-- Implementation similar to 'all' tab but filtered for active patients -->
            </div>
        </div>

        <div class="elegant-dark-tab-pane" x-show="activeTab === 'pending'" :class="{ 'active': activeTab === 'pending' }">
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-white mb-2">Pasien Pending</h3>
                <p class="text-gray-400">Menampilkan pasien yang menunggu verifikasi</p>
                <!-- Implementation similar to 'all' tab but filtered for pending patients -->
            </div>
        </div>

        <div class="elegant-dark-tab-pane" x-show="activeTab === 'recent'" :class="{ 'active': activeTab === 'recent' }">
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-blue-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-white mb-2">Pasien Terbaru</h3>
                <p class="text-gray-400">Menampilkan pasien yang terdaftar minggu ini</p>
                <!-- Implementation similar to 'all' tab but filtered for recent patients -->
            </div>
        </div>

        <div class="elegant-dark-tab-pane" x-show="activeTab === 'analytics'" :class="{ 'active': activeTab === 'analytics' }">
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-purple-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-white mb-2">Analitik Pasien</h3>
                <p class="text-gray-400">Dashboard analitik dan laporan komprehensif</p>
                <!-- Charts and analytics would go here -->
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function elegantDarkPasienManager() {
    return {
        // Core State
        loading: false,
        patients: [],
        selectedPatients: [],
        activeTab: 'all',
        viewMode: 'table',
        
        // Stats
        stats: {
            total: 0,
            active: 0,
            pending: 0,
            thisWeek: 0
        },
        
        // Filters
        filters: {
            search: '',
            jenis_kelamin: '',
            age_min: '',
            age_max: '',
            status: '',
            date_from: '',
            date_to: ''
        },
        
        // Pagination
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
            from: 0,
            to: 0
        },
        
        // Sorting
        sortField: 'created_at',
        sortDirection: 'desc',
        
        // Initialization
        init() {
            this.loadStats();
            this.loadPatients();
        },
        
        // Load patient statistics
        async loadStats() {
            try {
                const response = await fetch('/api/petugas/patients/stats');
                const data = await response.json();
                this.stats = data.stats || this.stats;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },
        
        // Load patients data
        async loadPatients() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort_field: this.sortField,
                    sort_direction: this.sortDirection,
                    tab: this.activeTab,
                    ...this.getActiveFilters()
                });
                
                const response = await fetch(`/api/petugas/patients?${params}`);
                const data = await response.json();
                
                this.patients = data.data || [];
                this.pagination = data.pagination || this.pagination;
                
            } catch (error) {
                console.error('Failed to load patients:', error);
                this.patients = [];
            } finally {
                this.loading = false;
            }
        },
        
        // Filter management
        hasActiveFilters() {
            return Object.values(this.getActiveFilters()).some(value => value !== '');
        },
        
        getActiveFilters() {
            return Object.fromEntries(
                Object.entries(this.filters).filter(([key, value]) => value !== '')
            );
        },
        
        getFilterLabel(key, value) {
            const labels = {
                search: `Pencarian: ${value}`,
                jenis_kelamin: `JK: ${value === 'L' ? 'Laki-laki' : 'Perempuan'}`,
                age_min: `Min Umur: ${value}`,
                age_max: `Max Umur: ${value}`,
                status: `Status: ${value}`,
                date_from: `Dari: ${value}`,
                date_to: `Sampai: ${value}`
            };
            return labels[key] || `${key}: ${value}`;
        },
        
        removeFilter(key) {
            this.filters[key] = '';
            this.loadPatients();
        },
        
        clearFilters() {
            this.filters = {
                search: '',
                jenis_kelamin: '',
                age_min: '',
                age_max: '',
                status: '',
                date_from: '',
                date_to: ''
            };
            this.loadPatients();
        },
        
        // Sorting
        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.loadPatients();
        },
        
        // Selection management
        toggleAllPatients(checked) {
            if (checked) {
                this.selectedPatients = this.patients.map(p => p.id);
            } else {
                this.selectedPatients = [];
            }
        },
        
        // Pagination
        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.loadPatients();
            }
        },
        
        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.loadPatients();
            }
        },
        
        goToPage(page) {
            this.pagination.current_page = page;
            this.loadPatients();
        },
        
        getVisiblePages() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const pages = [];
            
            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            return pages;
        },
        
        // Utility functions
        calculateAge(birthDate) {
            if (!birthDate) return 0;
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        },
        
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        
        formatTimeAgo(date) {
            if (!date) return '-';
            const now = new Date();
            const past = new Date(date);
            const diffInHours = Math.floor((now - past) / (1000 * 60 * 60));
            
            if (diffInHours < 24) {
                return `${diffInHours} jam lalu`;
            } else if (diffInHours < 168) {
                return `${Math.floor(diffInHours / 24)} hari lalu`;
            } else {
                return `${Math.floor(diffInHours / 168)} minggu lalu`;
            }
        },
        
        getStatusLabel(status) {
            const labels = {
                'active': 'Aktif',
                'pending': 'Pending',
                'inactive': 'Non-aktif',
                'verified': 'Terverifikasi'
            };
            return labels[status] || 'Unknown';
        },
        
        // Actions
        viewPatient(id) {
            window.location.href = `/petugas/enhanced/pasien/${id}`;
        },
        
        async deletePatient(id) {
            if (confirm('Apakah Anda yakin ingin menghapus pasien ini?')) {
                try {
                    const response = await fetch(`/api/petugas/patients/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    if (response.ok) {
                        this.loadPatients();
                        this.loadStats();
                    }
                } catch (error) {
                    console.error('Failed to delete patient:', error);
                }
            }
        },
        
        async exportData() {
            try {
                const params = new URLSearchParams(this.getActiveFilters());
                window.open(`/api/petugas/patients/export?${params}`, '_blank');
            } catch (error) {
                console.error('Failed to export data:', error);
            }
        }
    }
}
</script>
@endpush