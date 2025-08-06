<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tindakan - {{ $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        
        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: -1rem;
            width: 2px;
            background: linear-gradient(to bottom, #10b981, #d1d5db);
        }
        
        .timeline-item:last-child::before {
            background: linear-gradient(to bottom, #10b981 0%, #10b981 50%, transparent 50%);
        }
        
        .timeline-dot {
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 2px solid white;
            background: #10b981;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .procedure-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending { background-color: rgba(249, 115, 22, 0.1); color: #ea580c; }
        .status-approved { background-color: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .status-rejected { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }
        
        .financial-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .dark .financial-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.1) 100%);
            border-color: rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-medical-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="tindakanDetail()">
    
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-900 shadow-lg border-b border-medical-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="/petugas/enhanced/tindakan" class="text-medical-600 hover:text-medical-800 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Detail Tindakan</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis' }}</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="printDetail()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Cetak
                    </button>
                    
                    <a href="/petugas/enhanced/tindakan/{{ $tindakan->id }}/edit" 
                       class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Side - Main Details -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Procedure Header Card -->
                <div class="glass-card rounded-xl p-8 shadow-lg animate-fade-in">
                    <div class="flex items-start space-x-6">
                        <!-- Procedure Icon -->
                        <div class="flex-shrink-0">
                            <div class="procedure-badge w-20 h-20 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                                🩺
                            </div>
                        </div>
                        
                        <!-- Procedure Info -->
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis' }}
                                    </h2>
                                    <div class="flex items-center space-x-4 text-lg text-gray-600 dark:text-gray-400 mb-4">
                                        <span class="font-medium">ID: #{{ $tindakan->id }}</span>
                                        <span>•</span>
                                        <span class="status-badge {{ $tindakan->status_validasi === 'approved' ? 'status-approved' : ($tindakan->status_validasi === 'pending' ? 'status-pending' : 'status-rejected') }}">
                                            {{ $tindakan->status_validasi === 'approved' ? 'Disetujui' : ($tindakan->status_validasi === 'pending' ? 'Menunggu' : 'Ditolak') }}
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-gray-600 dark:text-gray-400">Tanggal:</span>
                                            <span class="text-gray-900 dark:text-white font-medium">{{ \Carbon\Carbon::parse($tindakan->tanggal_tindakan)->format('d M Y, H:i') }}</span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            <span class="text-gray-600 dark:text-gray-400">Tarif:</span>
                                            <span class="text-gray-900 dark:text-white font-bold text-lg">Rp {{ number_format($tindakan->tarif, 0, ',', '.') }}</span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span class="text-gray-600 dark:text-gray-400">Pasien:</span>
                                            <span class="text-gray-900 dark:text-white font-medium">{{ $tindakan->pasien->nama_pasien ?? '-' }}</span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                            </svg>
                                            <span class="text-gray-600 dark:text-gray-400">Dokter:</span>
                                            <span class="text-gray-900 dark:text-white font-medium">{{ $tindakan->dokter->nama_dokter ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Creation Info -->
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Dibuat</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tindakan->created_at)->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($tindakan->created_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Information -->
                @if($tindakan->pasien)
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">👤 Informasi Pasien</h3>
                    
                    <div class="flex items-center space-x-6">
                        <div class="w-16 h-16 bg-gradient-to-r {{ $tindakan->pasien->jenis_kelamin === 'L' ? 'from-blue-500 to-blue-600' : 'from-pink-500 to-pink-600' }} rounded-full flex items-center justify-center text-white font-bold text-xl">
                            {{ strtoupper(substr($tindakan->pasien->nama_pasien, 0, 1)) }}
                        </div>
                        
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $tindakan->pasien->nama_pasien }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <div><strong>Nomor:</strong> {{ $tindakan->pasien->nomor_pasien }}</div>
                                <div><strong>Jenis Kelamin:</strong> {{ $tindakan->pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                                <div><strong>Tanggal Lahir:</strong> {{ \Carbon\Carbon::parse($tindakan->pasien->tanggal_lahir)->format('d M Y') }}</div>
                                <div><strong>Umur:</strong> {{ \Carbon\Carbon::parse($tindakan->pasien->tanggal_lahir)->age }} tahun</div>
                            </div>
                        </div>
                        
                        <div>
                            <a href="/petugas/enhanced/pasien/{{ $tindakan->pasien->id }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Detail Pasien
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Medical Team Information -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">👨‍⚕️ Tim Medis</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Doctor -->
                        @if($tindakan->dokter)
                        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                    👨‍⚕️
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $tindakan->dokter->nama_dokter }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $tindakan->dokter->jabatan }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Paramedis -->
                        @if($tindakan->paramedis)
                        <div class="bg-green-50 dark:bg-green-900 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                                    👩‍⚕️
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $tindakan->paramedis->nama_pegawai }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Paramedis</div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Non-Paramedis -->
                        @if($tindakan->nonParamedis)
                        <div class="bg-purple-50 dark:bg-purple-900 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    👤
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $tindakan->nonParamedis->nama_pegawai }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Non-Paramedis</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="financial-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">💰 Rincian Keuangan</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Side - Jasa Breakdown -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">Jasa Pelayanan:</h4>
                            
                            @if($tindakan->jasa_dokter > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-600 dark:text-gray-400">Jasa Dokter:</span>
                                <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($tindakan->jasa_dokter, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            @if($tindakan->jasa_paramedis > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-600 dark:text-gray-400">Jasa Paramedis:</span>
                                <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($tindakan->jasa_paramedis, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            @if($tindakan->jasa_non_paramedis > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-600 dark:text-gray-400">Jasa Non-Paramedis:</span>
                                <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($tindakan->jasa_non_paramedis, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between items-center py-2 font-semibold text-lg border-t-2 border-gray-300 dark:border-gray-600">
                                <span class="text-gray-900 dark:text-white">Total Jasa:</span>
                                <span class="text-medical-600 dark:text-medical-400">Rp {{ number_format($tindakan->jasa_dokter + $tindakan->jasa_paramedis + $tindakan->jasa_non_paramedis, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        
                        <!-- Right Side - Summary -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">Ringkasan:</h4>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Tarif Tindakan:</span>
                                    <span class="font-bold text-xl text-gray-900 dark:text-white">Rp {{ number_format($tindakan->tarif, 0, ',', '.') }}</span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Total Jasa Tim:</span>
                                    <span class="font-medium text-medical-600 dark:text-medical-400">Rp {{ number_format($tindakan->jasa_dokter + $tindakan->jasa_paramedis + $tindakan->jasa_non_paramedis, 0, ',', '.') }}</span>
                                </div>
                                
                                <div class="border-t border-gray-200 dark:border-gray-600 pt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-gray-900 dark:text-white">Pendapatan Klinik:</span>
                                        <span class="font-bold text-lg text-blue-600 dark:text-blue-400">Rp {{ number_format($tindakan->tarif - ($tindakan->jasa_dokter + $tindakan->jasa_paramedis + $tindakan->jasa_non_paramedis), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes/Keterangan -->
                @if($tindakan->keterangan)
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">📝 Keterangan</h3>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $tindakan->keterangan }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Side - Timeline & Actions -->
            <div class="space-y-6">
                
                <!-- Quick Actions -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Aksi Cepat</h3>
                    
                    <div class="space-y-3">
                        @if($tindakan->status_validasi === 'pending')
                        <button @click="updateStatus('approved')" 
                                class="flex items-center w-full p-3 bg-green-50 dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Setujui Tindakan</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Validasi dan approve</p>
                            </div>
                        </button>
                        
                        <button @click="updateStatus('rejected')" 
                                class="flex items-center w-full p-3 bg-red-50 dark:bg-red-900 hover:bg-red-100 dark:hover:bg-red-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Tolak Tindakan</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Reject validasi</p>
                            </div>
                        </button>
                        @endif
                        
                        <button @click="duplicateTindakan()" 
                                class="flex items-center w-full p-3 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Duplikasi</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Buat tindakan serupa</p>
                            </div>
                        </button>
                        
                        <button @click="shareDetail()" 
                                class="flex items-center w-full p-3 bg-purple-50 dark:bg-purple-900 hover:bg-purple-100 dark:hover:bg-purple-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Bagikan Detail</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kirim link atau export</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Patient Timeline -->
                @if($tindakan->pasien)
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🏥 Timeline Pasien</h3>
                        <button @click="loadTimeline()" 
                                :disabled="loadingTimeline"
                                class="text-medical-600 hover:text-medical-800 text-sm"
                                :class="loadingTimeline ? 'opacity-50 cursor-not-allowed' : ''">
                            <svg class="w-4 h-4" :class="loadingTimeline ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div x-show="loadingTimeline" class="text-center py-8">
                        <div class="inline-flex items-center text-gray-500 dark:text-gray-400">
                            <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memuat timeline...
                        </div>
                    </div>
                    
                    <div x-show="!loadingTimeline && timeline.length > 0" class="space-y-4">
                        <template x-for="(item, index) in timeline" :key="index">
                            <div class="timeline-item">
                                <div class="timeline-dot" :class="{
                                    'bg-blue-500': item.type === 'registration',
                                    'bg-green-500': item.type === 'procedure' && item.status === 'approved',
                                    'bg-yellow-500': item.type === 'procedure' && item.status === 'pending',
                                    'bg-red-500': item.type === 'procedure' && item.status === 'rejected'
                                }"></div>
                                
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.title"></h4>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-text="item.description"></p>
                                            <div x-show="item.tarif" class="text-xs text-medical-600 dark:text-medical-400 mt-1">
                                                Tarif: <span x-text="formatCurrency(item.tarif)"></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(item.date)"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <div x-show="!loadingTimeline && timeline.length === 0" class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat lainnya</p>
                    </div>
                </div>
                @endif

                <!-- System Information -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ℹ️ Informasi Sistem</h3>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ID Tindakan:</span>
                            <span class="font-medium text-gray-900 dark:text-white">#{{ $tindakan->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Dibuat oleh:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $tindakan->inputBy->name ?? 'System' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tanggal input:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tindakan->created_at)->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Terakhir update:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tindakan->updated_at)->format('d M Y, H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function tindakanDetail() {
            return {
                // Current state
                loadingTimeline: false,
                timeline: [],
                
                // Initialize
                init() {
                    this.loadTimeline();
                },
                
                // Load patient timeline
                async loadTimeline() {
                    this.loadingTimeline = true;
                    try {
                        const response = await axios.get('/petugas/enhanced/tindakan/{{ $tindakan->pasien_id }}/timeline');
                        if (response.data.success) {
                            this.timeline = response.data.data;
                        }
                    } catch (error) {
                        console.error('Error loading timeline:', error);
                        this.showAlert('error', 'Gagal memuat timeline pasien');
                    } finally {
                        this.loadingTimeline = false;
                    }
                },
                
                // Update status
                async updateStatus(status) {
                    if (!confirm(`Apakah Anda yakin ingin mengubah status menjadi ${status}?`)) {
                        return;
                    }
                    
                    try {
                        const response = await axios.post('/petugas/enhanced/tindakan/bulk-update-status', {
                            ids: [{{ $tindakan->id }}],
                            status: status
                        });
                        
                        if (response.data.success) {
                            this.showAlert('success', response.data.message);
                            // Reload page after a short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    } catch (error) {
                        console.error('Error updating status:', error);
                        this.showAlert('error', 'Gagal memperbarui status');
                    }
                },
                
                // Actions
                printDetail() {
                    window.print();
                },
                
                duplicateTindakan() {
                    const url = new URL('/petugas/enhanced/tindakan/create', window.location.origin);
                    url.searchParams.set('duplicate_from', {{ $tindakan->id }});
                    window.location.href = url.toString();
                },
                
                shareDetail() {
                    if (navigator.share) {
                        navigator.share({
                            title: 'Detail Tindakan - {{ $tindakan->jenisTindakan->nama_tindakan ?? "Tindakan Medis" }}',
                            text: 'Detail tindakan {{ $tindakan->jenisTindakan->nama_tindakan ?? "medis" }} untuk pasien {{ $tindakan->pasien->nama_pasien ?? "" }}',
                            url: window.location.href
                        });
                    } else {
                        // Fallback to copy to clipboard
                        navigator.clipboard.writeText(window.location.href);
                        this.showAlert('success', 'Link berhasil disalin ke clipboard');
                    }
                },
                
                // Utility methods
                formatCurrency(amount) {
                    return 'Rp ' + (amount || 0).toLocaleString('id-ID');
                },
                
                formatDate(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },
                
                // Alert system
                showAlert(type, message) {
                    const alertClass = {
                        'success': 'bg-green-500',
                        'error': 'bg-red-500',
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