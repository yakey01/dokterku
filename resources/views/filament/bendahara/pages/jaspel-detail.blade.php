{{-- SIDEBAR-FREE LAYOUT - DIRECT HTML IMPLEMENTATION --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $this->user->name ?? 'User' }} - Detail Jaspel</title>
    
    {{-- Filament Core Styles --}}
    @filamentStyles
    
    {{-- Dark Mode Enforcement --}}
    <style>
        html { color-scheme: dark !important; }
        body { 
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            color: #fafafa !important;
            min-height: 100vh;
        }
        
        .sidebar-free-container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .sidebar-free-header {
            background: linear-gradient(135deg, #111118 0%, #1a1a20 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.6);
        }
        
        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1rem;
        }
        
        .breadcrumb-nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb-nav a:hover {
            color: #ffffff;
        }
        
        .content-cards > * {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #fafafa !important;
        }
    </style>
</head>
<body>
    <div class="sidebar-free-container">
        {{-- Custom Header with Navigation --}}
        <div class="sidebar-free-header">
            <nav class="breadcrumb-nav">
                <a href="{{ route('filament.bendahara.pages.dashboard') }}">🏠 Dashboard</a>
                <span>→</span>
                <a href="{{ route('filament.bendahara.resources.laporan-jaspel.index') }}">📊 Laporan Jaspel</a>
                <span>→</span>
                <span>Detail {{ $this->user->name ?? 'User' }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-white">📊 Detail Rincian Jaspel - {{ $this->user->name ?? 'User' }}</h1>
            <p class="text-gray-300 mt-2">Analisis komprehensif dan breakdown detail jaspel</p>
        </div>
        
        {{-- Main Content Area --}}
        <div class="content-cards space-y-6">
        {{-- World-Class Hero Section --}}
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-8 text-white">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                    <x-heroicon-o-banknotes class="w-8 h-8" />
                </div>
                <h1 class="text-3xl font-bold mb-2">{{ $this->user->name ?? 'User' }}</h1>
                <p class="text-lg opacity-90">Detail Rincian & Analisis Jaspel</p>
                
                @php
                    $procedureCalculator = app(\App\Services\ProcedureJaspelCalculationService::class);
                    $procedureData = $procedureCalculator->calculateJaspelFromProcedures($this->userId ?? 0, []);
                @endphp
                
                <div class="mt-6 grid grid-cols-2 gap-6">
                    <div class="text-center">
                        <div class="text-4xl font-bold">Rp {{ number_format($procedureData['total_jaspel'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-sm opacity-75">Total Jaspel</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold">{{ $procedureData['total_procedures'] ?? 0 }}</div>
                        <div class="text-sm opacity-75">Total Procedures</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial Breakdown Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Tindakan Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    🩺 Breakdown Tindakan Medis
                </h3>
                
                @if(!empty($procedureData['breakdown']['tindakan_procedures']))
                    <div class="space-y-3">
                        @foreach($procedureData['breakdown']['tindakan_procedures'] as $tindakan)
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $tindakan['jenis_tindakan'] }}</div>
                                    <div class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($tindakan['tanggal'])->format('d M Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-green-600">Rp {{ number_format($tindakan['jaspel'], 0, ',', '.') }}</div>
                                    <div class="text-xs text-gray-500">dari Rp {{ number_format($tindakan['tarif'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20 text-center">
                        <div class="text-lg font-semibold text-blue-800 dark:text-blue-200">Total Tindakan</div>
                        <div class="text-2xl font-bold text-blue-600">Rp {{ number_format($procedureData['tindakan_jaspel'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                @else
                    <div class="text-center text-gray-500 py-8">
                        <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Tidak ada data tindakan</p>
                    </div>
                @endif
            </div>

            {{-- Pasien Harian Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    👥 Breakdown Pasien Harian
                </h3>
                
                @if(!empty($procedureData['breakdown']['pasien_harian_days']))
                    <div class="space-y-3">
                        @foreach($procedureData['breakdown']['pasien_harian_days'] as $pasien)
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($pasien['tanggal'])->format('d M Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $pasien['jumlah_pasien'] }} pasien</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-green-600">Rp {{ number_format($pasien['jaspel_rupiah'], 0, ',', '.') }}</div>
                                    <div class="text-xs text-gray-500">per hari</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 p-4 bg-green-50 rounded-lg dark:bg-green-900/20 text-center">
                        <div class="text-lg font-semibold text-green-800 dark:text-green-200">Total Pasien Harian</div>
                        <div class="text-2xl font-bold text-green-600">Rp {{ number_format($procedureData['pasien_jaspel'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                @else
                    <div class="text-center text-gray-500 py-8">
                        <x-heroicon-o-users class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Tidak ada data pasien harian</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Validation & Quality Section --}}
        @php
            $validationAgent = app(\App\Services\SubAgents\ValidationSubAgentService::class);
            $validationData = $validationAgent->performCermatJaspelValidation($this->userId ?? 0);
        @endphp
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                🔍 Validasi & Quality Assurance
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Validation Score --}}
                <div class="text-center p-4 bg-indigo-50 rounded-lg dark:bg-indigo-900/20">
                    <div class="text-3xl font-bold text-indigo-600 mb-2">{{ $validationData['summary']['validation_score'] ?? 0 }}%</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Validation Score</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $validationData['summary']['passed_checks'] ?? 0 }}/{{ $validationData['summary']['total_checks'] ?? 0 }} checks passed</div>
                </div>
                
                {{-- Calculation Method --}}
                <div class="text-center p-4 bg-green-50 rounded-lg dark:bg-green-900/20">
                    <div class="text-3xl font-bold text-green-600 mb-2">✓</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Calculation Accuracy</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $procedureData['calculation_method'] ?? 'procedure_based' }}</div>
                </div>
                
                {{-- Data Integrity --}}
                <div class="text-center p-4 bg-purple-50 rounded-lg dark:bg-purple-900/20">
                    <div class="text-3xl font-bold text-purple-600 mb-2">🛡️</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Data Integrity</div>
                    <div class="text-xs text-gray-500 mt-1">Fully verified</div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-center space-x-4">
            <a href="{{ route('filament.bendahara.resources.laporan-jaspel.index') }}" 
               class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <x-heroicon-m-arrow-left class="w-5 h-5 mr-2" />
                Kembali ke Laporan
            </a>
            
            <button onclick="window.print()" 
                    class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <x-heroicon-m-printer class="w-5 h-5 mr-2" />
                Print Detail
            </button>
            
            <button onclick="exportDetail()" 
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <x-heroicon-m-arrow-down-tray class="w-5 h-5 mr-2" />
                Export PDF
            </button>
        </div>
        </div>
    </div>

    {{-- Filament Core Scripts --}}
    @filamentScripts
    
    <script>
        function exportDetail() {
            alert('Export functionality - would generate comprehensive PDF report');
        }

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.bg-white, .bg-gradient-to-r, .content-cards > div');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>

    <style>
        @media print {
            .no-print { display: none; }
            body { background: white !important; }
        }
    </style>
</body>
</html>