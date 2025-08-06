<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pasien - {{ $pasien->nama_pasien }}</title>
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
        
        .patient-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .patient-avatar.male {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .patient-avatar.female {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-medical-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="patientDetail()">
    
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-900 shadow-lg border-b border-medical-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="/petugas/enhanced/pasien" class="text-medical-600 hover:text-medical-800 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Detail Pasien</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Informasi lengkap pasien dan riwayat medis</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="printPatientCard()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Cetak Kartu
                    </button>
                    
                    <a href="/petugas/enhanced/pasien/{{ $pasien->id }}/edit" 
                       class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Data
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Patient Header Card -->
        <div class="glass-card rounded-xl p-8 mb-8 shadow-lg animate-fade-in">
            <div class="flex items-start space-x-6">
                <!-- Patient Avatar -->
                <div class="flex-shrink-0">
                    <div class="patient-avatar {{ $pasien->jenis_kelamin === 'L' ? 'male' : 'female' }} w-24 h-24 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                        {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                    </div>
                </div>
                
                <!-- Patient Info -->
                <div class="flex-1">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $pasien->nama_pasien }}</h2>
                            <div class="flex items-center space-x-4 text-lg text-gray-600 dark:text-gray-400 mb-4">
                                <span class="font-medium">{{ $pasien->nomor_pasien }}</span>
                                <span>•</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium {{ $pasien->jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                    {{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                                <span>•</span>
                                <span>{{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->age }} tahun</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-gray-600 dark:text-gray-400">Lahir:</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->format('d M Y') }}</span>
                                </div>
                                
                                @if($pasien->nomor_telepon)
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-gray-600 dark:text-gray-400">Telepon:</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $pasien->nomor_telepon }}</span>
                                </div>
                                @endif
                                
                                <div class="flex items-start space-x-2 md:col-span-2">
                                    <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-gray-600 dark:text-gray-400">Alamat:</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $pasien->alamat }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Registration Info -->
                        <div class="text-right">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Terdaftar</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($pasien->created_at)->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($pasien->created_at)->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Patient Statistics -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">📊 Statistik Pasien</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="stats-card rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-medical-600 dark:text-medical-400">{{ $stats['total_tindakan'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Tindakan</div>
                        </div>
                        
                        <div class="stats-card rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($stats['total_biaya'] ?? 0, 0, ',', '.') }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Biaya</div>
                        </div>
                        
                        <div class="stats-card rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                @if($stats['last_visit'])
                                    {{ \Carbon\Carbon::parse($stats['last_visit'])->diffForHumans() }}
                                @else
                                    Belum ada
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kunjungan Terakhir</div>
                        </div>
                    </div>
                </div>

                <!-- Medical Timeline -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">🏥 Riwayat Medis</h3>
                        <button @click="loadTimeline()" 
                                :disabled="loadingTimeline"
                                class="inline-flex items-center px-3 py-1 bg-medical-600 hover:bg-medical-700 text-white text-sm rounded-lg transition-colors"
                                :class="loadingTimeline ? 'opacity-50 cursor-not-allowed' : ''">
                            <svg class="w-4 h-4 mr-1" :class="loadingTimeline ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                    
                    <div x-show="loadingTimeline" class="text-center py-8">
                        <div class="inline-flex items-center text-gray-500 dark:text-gray-400">
                            <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memuat riwayat medis...
                        </div>
                    </div>
                    
                    <div x-show="!loadingTimeline && timeline.length > 0" class="space-y-6">
                        <template x-for="(item, index) in timeline" :key="index">
                            <div class="timeline-item">
                                <div class="timeline-dot" :class="{
                                    'bg-blue-500': item.type === 'registration',
                                    'bg-green-500': item.type === 'procedure' && item.status === 'approved',
                                    'bg-yellow-500': item.type === 'procedure' && item.status === 'pending',
                                    'bg-red-500': item.type === 'procedure' && item.status === 'rejected'
                                }"></div>
                                
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white" x-text="item.title"></h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="item.description"></p>
                                            <div x-show="item.status" class="mt-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-green-100 text-green-800': item.status === 'approved',
                                                          'bg-yellow-100 text-yellow-800': item.status === 'pending',
                                                          'bg-red-100 text-red-800': item.status === 'rejected'
                                                      }">
                                                    <span x-text="item.status === 'approved' ? 'Disetujui' : item.status === 'pending' ? 'Menunggu' : 'Ditolak'"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500 dark:text-gray-400" x-text="formatDateTime(item.date)"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <div x-show="!loadingTimeline && timeline.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum Ada Riwayat Medis</h3>
                        <p class="text-gray-500 dark:text-gray-400">Pasien belum memiliki riwayat tindakan medis.</p>
                    </div>
                </div>

                <!-- Recent Procedures -->
                @if($pasien->tindakan->count() > 0)
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">🩺 Tindakan Terbaru</h3>
                    
                    <div class="space-y-4">
                        @foreach($pasien->tindakan->take(5) as $tindakan)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover-lift">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis' }}
                                    </h4>
                                    <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        <span>{{ \Carbon\Carbon::parse($tindakan->tanggal_tindakan)->format('d M Y') }}</span>
                                        <span>•</span>
                                        <span>Rp {{ number_format($tindakan->tarif, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($tindakan->status_validasi === 'approved') bg-green-100 text-green-800
                                        @elseif($tindakan->status_validasi === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        @if($tindakan->status_validasi === 'approved') Disetujui
                                        @elseif($tindakan->status_validasi === 'pending') Menunggu
                                        @else Ditolak
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    @if($pasien->tindakan->count() > 5)
                    <div class="text-center mt-6">
                        <button class="text-medical-600 hover:text-medical-800 font-medium text-sm">
                            Lihat Semua Tindakan ({{ $pasien->tindakan->count() }})
                        </button>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                
                <!-- Quick Actions -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Aksi Cepat</h3>
                    
                    <div class="space-y-3">
                        <a href="/petugas/tindakans/create?pasien_id={{ $pasien->id }}" 
                           class="flex items-center w-full p-3 bg-medical-50 dark:bg-medical-900 hover:bg-medical-100 dark:hover:bg-medical-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-medical-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Tambah Tindakan</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Catat tindakan baru</p>
                            </div>
                        </a>
                        
                        <button @click="generateReport()" 
                                class="flex items-center w-full p-3 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Laporan Pasien</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Generate laporan lengkap</p>
                            </div>
                        </button>
                        
                        <button @click="sharePatientInfo()" 
                                class="flex items-center w-full p-3 bg-purple-50 dark:bg-purple-900 hover:bg-purple-100 dark:hover:bg-purple-800 rounded-lg transition-colors group">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">Bagikan Info</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kirim ke WhatsApp/Email</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Patient Summary Chart -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Ringkasan Bulanan</h3>
                    <div id="patient-chart" class="h-48"></div>
                </div>

                <!-- System Information -->
                <div class="glass-card rounded-xl p-6 shadow-lg animate-fade-in">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ℹ️ Informasi Sistem</h3>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ID Pasien:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $pasien->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Dibuat oleh:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $pasien->inputBy->name ?? 'System' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Terakhir update:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($pasien->updated_at)->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function patientDetail() {
            return {
                timeline: [],
                loadingTimeline: false,
                
                // Initialize
                init() {
                    this.loadTimeline();
                    this.initChart();
                },
                
                // Load patient timeline
                async loadTimeline() {
                    this.loadingTimeline = true;
                    try {
                        const response = await axios.get('/petugas/enhanced/pasien/{{ $pasien->id }}/timeline');
                        if (response.data.success) {
                            this.timeline = response.data.data;
                        }
                    } catch (error) {
                        console.error('Error loading timeline:', error);
                        this.showAlert('error', 'Gagal memuat riwayat medis');
                    } finally {
                        this.loadingTimeline = false;
                    }
                },
                
                // Initialize chart
                initChart() {
                    const options = {
                        series: [{
                            name: 'Tindakan',
                            data: [5, 8, 3, 12, 7, 9] // Mock data - replace with real data
                        }],
                        chart: {
                            type: 'area',
                            height: 180,
                            toolbar: { show: false },
                            background: 'transparent'
                        },
                        colors: ['#10b981'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                type: 'vertical',
                                colorStops: [
                                    { offset: 0, color: '#10b981', opacity: 0.4 },
                                    { offset: 100, color: '#10b981', opacity: 0.1 }
                                ]
                            }
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        xaxis: {
                            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                            labels: { show: false },
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: { show: false }
                        },
                        grid: { show: false },
                        dataLabels: { enabled: false },
                        tooltip: {
                            theme: 'dark'
                        }
                    };
                    
                    const chart = new ApexCharts(document.querySelector("#patient-chart"), options);
                    chart.render();
                },
                
                // Utility methods
                formatDateTime(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                
                // Actions
                printPatientCard() {
                    window.print();
                },
                
                generateReport() {
                    this.showAlert('info', 'Fitur laporan akan segera tersedia');
                },
                
                sharePatientInfo() {
                    if (navigator.share) {
                        navigator.share({
                            title: 'Info Pasien - {{ $pasien->nama_pasien }}',
                            text: 'Informasi pasien {{ $pasien->nama_pasien }} ({{ $pasien->nomor_pasien }})',
                            url: window.location.href
                        });
                    } else {
                        // Fallback to copy to clipboard
                        navigator.clipboard.writeText(window.location.href);
                        this.showAlert('success', 'Link berhasil disalin ke clipboard');
                    }
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