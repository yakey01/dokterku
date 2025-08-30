<x-filament-panels::page>
    {{-- Simplified Petugas Dashboard View --}}
    <style>
        /* Ensure proper styling for petugas panel */
        [data-panel-id="petugas"] .fi-main {
            background: #f9fafb !important;
        }
    </style>

    <!-- Welcome Message -->
    <div class="mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h1 class="text-xl font-semibold">Welcome {{ auth()->user()->name }}</h1>
                    <p class="text-blue-100 text-sm">Petugas Dashboard - {{ \Carbon\Carbon::now()->format('l, d F Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Patients Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Pasien</h3>
                    <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Pasien::count() }}</p>
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Tindakan</h3>
                    <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Tindakan::count() }}</p>
                </div>
            </div>
        </div>

        <!-- Today's Activities -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Aktivitas Hari Ini</h3>
                    <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Tindakan::whereDate('created_at', today())->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Revenue Bulan Ini</h3>
                    <p class="text-lg font-semibold text-gray-900">Rp {{ number_format(\App\Models\Pendapatan::whereMonth('tanggal', now()->month)->sum('nominal'), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Manajemen Pasien</h3>
            <div class="space-y-2">
                <a href="{{ route('filament.petugas.resources.pasiens.index') }}" class="block text-blue-600 hover:text-blue-800">
                    → Kelola Data Pasien
                </a>
                <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}" class="block text-blue-600 hover:text-blue-800">
                    → Jumlah Pasien Harian
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tindakan Medis</h3>
            <div class="space-y-2">
                <a href="{{ route('filament.petugas.resources.tindakans.index') }}" class="block text-blue-600 hover:text-blue-800">
                    → Input Tindakan
                </a>
                <a href="{{ route('filament.petugas.resources.tindakans.create') }}" class="block text-blue-600 hover:text-blue-800">
                    → Tindakan Baru
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Keuangan</h3>
            <div class="space-y-2">
                <a href="{{ route('filament.petugas.resources.pendapatan-harians.index') }}" class="block text-blue-600 hover:text-blue-800">
                    → Pendapatan Harian
                </a>
                <a href="{{ route('filament.petugas.resources.pengeluaran-harians.index') }}" class="block text-blue-600 hover:text-blue-800">
                    → Pengeluaran Harian
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>