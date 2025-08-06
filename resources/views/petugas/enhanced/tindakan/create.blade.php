<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Tindakan Baru - Enhanced System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Debug: Check shift data -->
    @if(isset($shiftList))
        <!-- DEBUG: Shift list available with {{ count($shiftList) }} items -->
    @else
        <!-- DEBUG: Shift list NOT available -->
    @endif
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
        
        .step-indicator {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .step-indicator.active {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            transform: scale(1.1);
            box-shadow: 0 8px 25px -5px rgba(16, 185, 129, 0.4);
        }
        
        .step-indicator.completed {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .step-line {
            height: 2px;
            background: linear-gradient(90deg, #e5e7eb 0%, #d1d5db 100%);
            transition: all 0.5s ease;
        }
        
        .step-line.completed {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }
        
        .form-section {
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-section.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .input-group {
            position: relative;
        }
        
        .floating-label {
            position: absolute;
            left: 12px;
            top: -6px;
            background: white;
            padding: 0 4px;
            color: #10b981;
            font-size: 0.75rem;
            font-weight: 500;
            pointer-events: none;
        }
        
        .dark .floating-label {
            background: #1f2937;
            color: #6ee7b7;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .smart-autocomplete {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .dark .smart-autocomplete {
            background: #374151;
            border-color: #4b5563;
        }
        
        .autocomplete-item {
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }
        
        .autocomplete-item:hover {
            background-color: #f9fafb;
        }
        
        .dark .autocomplete-item {
            border-color: #4b5563;
        }
        
        .dark .autocomplete-item:hover {
            background-color: #4b5563;
        }
        
        .calculation-display {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .dark .calculation-display {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-color: #38bdf8;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-medical-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="tindakanCreateWizard()">
    
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🩺 Tambah Tindakan Baru</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Input data tindakan medis dengan smart form</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="saveDraft()" 
                            x-show="hasChanges()"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="flex items-center" :class="index === steps.length - 1 ? '' : 'flex-1'">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full border-2 step-indicator"
                             :class="{
                                 'active': currentStep === index,
                                 'completed': currentStep > index,
                                 'border-medical-500 bg-medical-500 text-white': currentStep >= index,
                                 'border-gray-300 bg-white text-gray-500': currentStep < index
                             }">
                            <svg x-show="currentStep > index" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span x-show="currentStep <= index" x-text="index + 1"></span>
                        </div>
                        <div class="ml-4 min-w-0 flex-1" x-show="index < steps.length - 1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="step.title"></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400" x-text="step.description"></div>
                        </div>
                        <div x-show="index < steps.length - 1" class="hidden md:block flex-1 mx-4">
                            <div class="step-line" :class="currentStep > index ? 'completed' : ''"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main Form -->
        <div class="glass-card rounded-xl shadow-lg overflow-hidden">
            <form @submit.prevent="submitForm()" x-ref="tindakanForm">
                
                <!-- Step 1: Pasien Selection -->
                <div x-show="currentStep === 0" class="form-section" :class="currentStep === 0 ? 'active' : ''">
                    <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">👤 Pilih Pasien</h3>
                            <p class="text-gray-600 dark:text-gray-400">Cari dan pilih pasien untuk tindakan medis.</p>
                        </div>
                        
                        <!-- Patient Search -->
                        <div class="input-group">
                            <input type="text" 
                                   x-model="pasienSearch" 
                                   @input="searchPasien()"
                                   @focus="showPasienDropdown = true"
                                   placeholder="Cari nama atau nomor pasien..."
                                   class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                   :class="getFieldClass('pasien')">
                            <label class="floating-label">Cari Pasien *</label>
                            
                            <!-- Patient Dropdown -->
                            <div x-show="showPasienDropdown && pasienOptions.length > 0" 
                                 class="smart-autocomplete">
                                <template x-for="pasien in pasienOptions" :key="pasien.id">
                                    <div class="autocomplete-item" @click="selectPasien(pasien)">
                                        <div class="font-medium text-gray-900 dark:text-white" x-text="pasien.nama_pasien"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="pasien.nomor_pasien"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Selected Patient Display -->
                        <div x-show="formData.pasien_id" class="mt-6 p-4 bg-medical-50 dark:bg-medical-900 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-medical-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                    <span x-text="selectedPasien.nama_pasien ? selectedPasien.nama_pasien.charAt(0).toUpperCase() : ''"></span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 dark:text-white" x-text="selectedPasien.nama_pasien"></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedPasien.nomor_pasien"></p>
                                </div>
                                <button type="button" @click="clearPasien()" class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Tindakan Details -->
                <div x-show="currentStep === 1" class="form-section" :class="currentStep === 1 ? 'active' : ''">
                    <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">🩺 Detail Tindakan</h3>
                            <p class="text-gray-600 dark:text-gray-400">Pilih jenis tindakan dan atur detail medis.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Jenis Tindakan -->
                            <div class="input-group">
                                <select x-model="formData.jenis_tindakan_id" 
                                        @change="onTindakanChange()"
                                        required
                                        class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                        :class="getFieldClass('jenis_tindakan_id')">
                                    <option value="">Pilih Jenis Tindakan</option>
                                    @foreach($jenisTindakanList as $jenis)
                                    <option value="{{ $jenis->id }}" data-tarif="{{ $jenis->tarif_standar }}">{{ $jenis->nama_tindakan }}</option>
                                    @endforeach
                                </select>
                                <label class="floating-label">Jenis Tindakan *</label>
                            </div>
                            
                            <!-- Tanggal Tindakan -->
                            <div class="input-group">
                                <input type="datetime-local" 
                                       x-model="formData.tanggal_tindakan" 
                                       required
                                       :max="maxDateTime"
                                       class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                       :class="getFieldClass('tanggal_tindakan')">
                                <label class="floating-label">Tanggal & Waktu Tindakan *</label>
                            </div>
                            
                            <!-- Shift -->
                            <div class="input-group">
                                <select x-model="formData.shift_id" 
                                        required
                                        class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                        :class="getFieldClass('shift_id')">
                                    <option value="">Pilih Shift</option>
                                    @forelse($shiftList as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
                                    @empty
                                    <option value="" disabled>Tidak ada shift tersedia</option>
                                    @endforelse
                                </select>
                                <label class="floating-label">Shift *</label>
                            </div>
                            
                            <!-- Tarif -->
                            <div class="input-group">
                                <input type="number" 
                                       x-model.number="formData.tarif" 
                                       @input="calculateJasa()"
                                       min="0"
                                       step="1000"
                                       required
                                       class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                       :class="getFieldClass('tarif')">
                                <label class="floating-label">Tarif Tindakan (Rp) *</label>
                            </div>
                            
                            <!-- Status Validasi -->
                            <div class="input-group">
                                <select x-model="formData.status_validasi" 
                                        class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <label class="floating-label">Status Validasi</label>
                            </div>
                        </div>
                        
                        <!-- Keterangan -->
                        <div class="mt-6">
                            <div class="input-group">
                                <textarea x-model="formData.keterangan" 
                                          rows="4"
                                          maxlength="1000"
                                          class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all resize-none"
                                          placeholder="Keterangan tambahan tentang tindakan..."></textarea>
                                <label class="floating-label">Keterangan</label>
                                <div class="flex justify-between items-center mt-1">
                                    <div class="text-gray-500 text-sm">Opsional - catatan tambahan</div>
                                    <div class="text-gray-500 text-sm">
                                        <span x-text="formData.keterangan ? formData.keterangan.length : 0"></span>/1000 karakter
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Staff Assignment -->
                <div x-show="currentStep === 2" class="form-section" :class="currentStep === 2 ? 'active' : ''">
                    <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">👨‍⚕️ Penugasan Staff</h3>
                            <p class="text-gray-600 dark:text-gray-400">Pilih dokter dan staff yang terlibat dalam tindakan.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Dokter -->
                            <div class="input-group">
                                <select x-model="formData.dokter_id" 
                                        @change="calculateJasa()"
                                        class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all">
                                    <option value="">Pilih Dokter</option>
                                    @foreach($dokterList as $dokter)
                                    <option value="{{ $dokter->id }}">{{ $dokter->nama_dokter }} ({{ $dokter->jabatan }})</option>
                                    @endforeach
                                </select>
                                <label class="floating-label">Dokter</label>
                            </div>
                            
                            <!-- Paramedis -->
                            <div class="input-group">
                                <select x-model="formData.paramedis_id" 
                                        @change="calculateJasa()"
                                        class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all">
                                    <option value="">Pilih Paramedis</option>
                                    @foreach($paramedisList as $paramedis)
                                    <option value="{{ $paramedis->id }}">{{ $paramedis->nama_pegawai }}</option>
                                    @endforeach
                                </select>
                                <label class="floating-label">Paramedis</label>
                            </div>
                            
                            <!-- Non-Paramedis -->
                            <div class="input-group">
                                <select x-model="formData.non_paramedis_id" 
                                        @change="calculateJasa()"
                                        class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all">
                                    <option value="">Pilih Non-Paramedis</option>
                                    @foreach($nonParamedisList as $nonParamedis)
                                    <option value="{{ $nonParamedis->id }}">{{ $nonParamedis->nama_pegawai }}</option>
                                    @endforeach
                                </select>
                                <label class="floating-label">Non-Paramedis</label>
                            </div>
                        </div>
                        
                        <!-- Jasa Calculations -->
                        <div class="calculation-display">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">💰 Perhitungan Jasa Pelayanan</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="input-group">
                                    <input type="number" 
                                           x-model.number="formData.jasa_dokter" 
                                           min="0"
                                           step="1000"
                                           class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    <label class="floating-label">Jasa Dokter (Rp)</label>
                                </div>
                                
                                <div class="input-group">
                                    <input type="number" 
                                           x-model.number="formData.jasa_paramedis" 
                                           min="0"
                                           step="1000"
                                           class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    <label class="floating-label">Jasa Paramedis (Rp)</label>
                                </div>
                                
                                <div class="input-group">
                                    <input type="number" 
                                           x-model.number="formData.jasa_non_paramedis" 
                                           min="0"
                                           step="1000"
                                           class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    <label class="floating-label">Jasa Non-Paramedis (Rp)</label>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-gray-900 dark:text-white">Total Jasa:</span>
                                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="formatCurrency(getTotalJasa())"></span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="font-medium text-gray-900 dark:text-white">Sisa untuk Klinik:</span>
                                    <span class="text-lg font-semibold text-gray-700 dark:text-gray-300" x-text="formatCurrency(formData.tarif - getTotalJasa())"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div x-show="currentStep === 3" class="form-section" :class="currentStep === 3 ? 'active' : ''">
                    <div class="p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">✅ Review & Konfirmasi</h3>
                            <p class="text-gray-600 dark:text-gray-400">Periksa kembali data sebelum menyimpan tindakan.</p>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- Patient Summary -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">👤 Informasi Pasien</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Nama:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="selectedPasien.nama_pasien"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Nomor:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="selectedPasien.nomor_pasien"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Procedure Summary -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🩺 Detail Tindakan</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Jenis Tindakan:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="getSelectedTindakanName()"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Tanggal & Waktu:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="formatDateTime(formData.tanggal_tindakan)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Tarif:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="formatCurrency(formData.tarif)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                        <span class="font-medium capitalize" 
                                              :class="{
                                                  'text-green-600': formData.status_validasi === 'approved',
                                                  'text-yellow-600': formData.status_validasi === 'pending',
                                                  'text-red-600': formData.status_validasi === 'rejected'
                                              }" 
                                              x-text="getStatusText(formData.status_validasi)"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Staff Summary -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">👨‍⚕️ Tim Medis & Jasa</h4>
                                <div class="space-y-2 text-sm">
                                    <div x-show="formData.dokter_id" class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Dokter:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="getSelectedDokterName()"></span>
                                    </div>
                                    <div x-show="formData.paramedis_id" class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Paramedis:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="getSelectedParamedisName()"></span>
                                    </div>
                                    <div x-show="formData.non_paramedis_id" class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Non-Paramedis:</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="getSelectedNonParamedisName()"></span>
                                    </div>
                                    
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-3">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-gray-900 dark:text-white">Total Jasa:</span>
                                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400" x-text="formatCurrency(getTotalJasa())"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="bg-gray-50 dark:bg-gray-800 px-8 py-6 flex justify-between items-center">
                    <button type="button" 
                            @click="previousStep()" 
                            x-show="currentStep > 0"
                            class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Sebelumnya
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <a href="/petugas/enhanced/tindakan" 
                           class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                            Batal
                        </a>
                        
                        <button type="button" 
                                @click="nextStep()" 
                                x-show="currentStep < steps.length - 1"
                                :disabled="!canProceedToNext()"
                                :class="!canProceedToNext() ? 'bg-gray-400 cursor-not-allowed' : 'bg-medical-600 hover:bg-medical-700'"
                                class="inline-flex items-center px-6 py-3 text-white font-medium rounded-lg transition-colors">
                            Selanjutnya
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                        
                        <button type="submit" 
                                x-show="currentStep === steps.length - 1"
                                :disabled="submitting || !isFormValid()"
                                :class="(submitting || !isFormValid()) ? 'bg-gray-400 cursor-not-allowed' : 'bg-medical-600 hover:bg-medical-700'"
                                class="inline-flex items-center px-8 py-3 text-white font-medium rounded-lg transition-colors">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="submitting ? 'Menyimpan...' : 'Simpan Tindakan'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function tindakanCreateWizard() {
            return {
                // Current state
                currentStep: 0,
                submitting: false,
                
                // Steps configuration
                steps: [
                    { title: 'Pilih Pasien', description: 'Cari dan pilih pasien' },
                    { title: 'Detail Tindakan', description: 'Atur jenis dan tarif' },
                    { title: 'Tim Medis', description: 'Pilih staff dan jasa' },
                    { title: 'Review', description: 'Konfirmasi data' }
                ],
                
                // Form data
                formData: {
                    pasien_id: '',
                    jenis_tindakan_id: '',
                    dokter_id: '',
                    paramedis_id: '',
                    non_paramedis_id: '',
                    shift_id: '',
                    tanggal_tindakan: '',
                    tarif: 0,
                    jasa_dokter: 0,
                    jasa_paramedis: 0,
                    jasa_non_paramedis: 0,
                    catatan: '',
                    status_validasi: 'pending'
                },
                
                // Patient search
                pasienSearch: '',
                pasienOptions: [],
                showPasienDropdown: false,
                selectedPasien: {},
                
                // Lists from server
                jenisTindakanList: @json($jenisTindakanList),
                dokterList: @json($dokterList),
                paramedisList: @json($paramedisList),
                nonParamedisList: @json($nonParamedisList),
                
                // Validation
                errors: {},
                
                // Computed properties
                maxDateTime: new Date().toISOString().slice(0, 16),
                
                // Initialize
                init() {
                    this.setDefaultDateTime();
                    this.loadDraft();
                    
                    // Debug: Check if shift data is available
                    console.log('Shift data available:', @json($shiftList ?? []));
                },
                
                // Step navigation
                nextStep() {
                    if (this.canProceedToNext()) {
                        this.currentStep = Math.min(this.currentStep + 1, this.steps.length - 1);
                    }
                },
                
                previousStep() {
                    this.currentStep = Math.max(this.currentStep - 1, 0);
                },
                
                canProceedToNext() {
                    switch (this.currentStep) {
                        case 0: // Patient selection
                            return this.formData.pasien_id !== '';
                        case 1: // Procedure details
                            return this.formData.jenis_tindakan_id !== '' && 
                                   this.formData.tanggal_tindakan !== '' && 
                                   this.formData.tarif > 0;
                        case 2: // Staff assignment
                            return true; // Optional fields
                        default:
                            return true;
                    }
                },
                
                // Patient search methods
                async searchPasien() {
                    if (this.pasienSearch.length < 2) {
                        this.pasienOptions = [];
                        return;
                    }
                    
                    try {
                        const response = await axios.get('/petugas/enhanced/pasien/search/autocomplete', {
                            params: { q: this.pasienSearch }
                        });
                        
                        if (response.data.success && response.data.data.pasien) {
                            this.pasienOptions = response.data.data.pasien;
                        }
                    } catch (error) {
                        console.error('Error searching patients:', error);
                    }
                },
                
                selectPasien(pasien) {
                    this.formData.pasien_id = pasien.id;
                    this.selectedPasien = pasien;
                    this.pasienSearch = `${pasien.nama_pasien} (${pasien.nomor_pasien})`;
                    this.showPasienDropdown = false;
                },
                
                clearPasien() {
                    this.formData.pasien_id = '';
                    this.selectedPasien = {};
                    this.pasienSearch = '';
                    this.pasienOptions = [];
                },
                
                // Procedure methods
                onTindakanChange() {
                    const select = event.target;
                    const selectedOption = select.options[select.selectedIndex];
                    if (selectedOption && selectedOption.dataset.tarif) {
                        this.formData.tarif = parseInt(selectedOption.dataset.tarif);
                        this.calculateJasa();
                    }
                },
                
                calculateJasa() {
                    if (this.formData.tarif > 0) {
                        // Simple calculation: distribute based on staff presence
                        const staffCount = [
                            this.formData.dokter_id,
                            this.formData.paramedis_id,
                            this.formData.non_paramedis_id
                        ].filter(id => id !== '').length;
                        
                        if (staffCount > 0) {
                            const baseShare = Math.floor(this.formData.tarif * 0.6 / staffCount);
                            
                            if (this.formData.dokter_id) {
                                this.formData.jasa_dokter = baseShare * 1.5; // Doctor gets 1.5x
                            } else {
                                this.formData.jasa_dokter = 0;
                            }
                            
                            if (this.formData.paramedis_id) {
                                this.formData.jasa_paramedis = baseShare;
                            } else {
                                this.formData.jasa_paramedis = 0;
                            }
                            
                            if (this.formData.non_paramedis_id) {
                                this.formData.jasa_non_paramedis = baseShare * 0.8; // Non-paramedis gets 0.8x
                            } else {
                                this.formData.jasa_non_paramedis = 0;
                            }
                        }
                    }
                },
                
                getTotalJasa() {
                    return (this.formData.jasa_dokter || 0) + 
                           (this.formData.jasa_paramedis || 0) + 
                           (this.formData.jasa_non_paramedis || 0);
                },
                
                // Validation methods
                isFormValid() {
                    return this.formData.pasien_id !== '' &&
                           this.formData.jenis_tindakan_id !== '' &&
                           this.formData.shift_id !== '' &&
                           this.formData.tanggal_tindakan !== '' &&
                           this.formData.tarif > 0;
                },
                
                getFieldClass(fieldName) {
                    if (this.errors[fieldName]) {
                        return 'border-red-500 dark:border-red-500';
                    }
                    return 'border-gray-300 dark:border-gray-600';
                },
                
                // Utility methods
                setDefaultDateTime() {
                    const now = new Date();
                    this.formData.tanggal_tindakan = now.toISOString().slice(0, 16);
                },
                
                hasChanges() {
                    return Object.values(this.formData).some(value => 
                        value !== '' && value !== 0 && value !== null
                    );
                },
                
                formatCurrency(amount) {
                    return 'Rp ' + (amount || 0).toLocaleString('id-ID');
                },
                
                formatDateTime(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                
                getStatusText(status) {
                    const statusMap = {
                        'pending': 'Menunggu',
                        'approved': 'Disetujui',
                        'rejected': 'Ditolak'
                    };
                    return statusMap[status] || status;
                },
                
                // Get selected names for review
                getSelectedTindakanName() {
                    const selected = this.jenisTindakanList.find(t => t.id == this.formData.jenis_tindakan_id);
                    return selected ? selected.nama_tindakan : '-';
                },
                
                getSelectedDokterName() {
                    const selected = this.dokterList.find(d => d.id == this.formData.dokter_id);
                    return selected ? `${selected.nama_dokter} (${selected.jabatan})` : '-';
                },
                
                getSelectedParamedisName() {
                    const selected = this.paramedisList.find(p => p.id == this.formData.paramedis_id);
                    return selected ? selected.nama_pegawai : '-';
                },
                
                getSelectedNonParamedisName() {
                    const selected = this.nonParamedisList.find(np => np.id == this.formData.non_paramedis_id);
                    return selected ? selected.nama_pegawai : '-';
                },
                
                // Draft management
                saveDraft() {
                    localStorage.setItem('tindakan_draft', JSON.stringify(this.formData));
                    this.showAlert('success', 'Draft berhasil disimpan');
                },
                
                loadDraft() {
                    const draft = localStorage.getItem('tindakan_draft');
                    if (draft) {
                        try {
                            const parsedDraft = JSON.parse(draft);
                            // Only load if not empty
                            if (Object.values(parsedDraft).some(value => value !== '' && value !== 0)) {
                                this.formData = { ...this.formData, ...parsedDraft };
                                
                                // Restore selected patient if exists
                                if (this.formData.pasien_id) {
                                    this.restoreSelectedPasien();
                                }
                            }
                        } catch (error) {
                            console.error('Error loading draft:', error);
                        }
                    }
                },
                
                async restoreSelectedPasien() {
                    try {
                        const response = await axios.get(`/petugas/enhanced/pasien/${this.formData.pasien_id}`);
                        if (response.data.success) {
                            const pasien = response.data.data;
                            this.selectedPasien = pasien;
                            this.pasienSearch = `${pasien.nama_pasien} (${pasien.nomor_pasien})`;
                        }
                    } catch (error) {
                        console.error('Error restoring patient:', error);
                    }
                },
                
                clearDraft() {
                    localStorage.removeItem('tindakan_draft');
                },
                
                // Form submission
                async submitForm() {
                    if (!this.isFormValid()) {
                        this.showAlert('error', 'Harap lengkapi semua field yang wajib');
                        return;
                    }
                    
                    this.submitting = true;
                    
                    try {
                        const response = await axios.post('/petugas/enhanced/tindakan', this.formData);
                        
                        if (response.data.success) {
                            this.clearDraft();
                            this.showAlert('success', response.data.message);
                            
                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = '/petugas/enhanced/tindakan';
                            }, 1500);
                        } else {
                            throw new Error(response.data.message || 'Terjadi kesalahan');
                        }
                    } catch (error) {
                        console.error('Error creating tindakan:', error);
                        
                        // Handle validation errors
                        if (error.response?.status === 422) {
                            this.errors = error.response.data.errors || {};
                        }
                        
                        this.showAlert('error', 'Gagal menyimpan tindakan: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.submitting = false;
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