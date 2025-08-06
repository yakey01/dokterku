<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pasien Baru - Enhanced System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
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
            top: 12px;
            background: white;
            padding: 0 4px;
            color: #6b7280;
            transition: all 0.2s ease;
            pointer-events: none;
        }
        
        .dark .floating-label {
            background: #1f2937;
            color: #9ca3af;
        }
        
        .input-group input:focus + .floating-label,
        .input-group input:not(:placeholder-shown) + .floating-label,
        .input-group select:focus + .floating-label,
        .input-group select:not([value=""]) + .floating-label,
        .input-group textarea:focus + .floating-label,
        .input-group textarea:not(:placeholder-shown) + .floating-label {
            top: -6px;
            left: 8px;
            font-size: 0.75rem;
            color: #10b981;
            font-weight: 500;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success-checkmark {
            animation: checkmark 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes checkmark {
            0% { transform: scale(0) rotate(45deg); }
            50% { transform: scale(1.2) rotate(45deg); }
            100% { transform: scale(1) rotate(45deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-medical-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="pasienCreateWizard()">
    
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah Pasien Baru</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Multi-step Registration Wizard</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Langkah <span x-text="currentStep"></span> dari <span x-text="totalSteps"></span>
                    </span>
                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-medical-500 h-2 rounded-full transition-all duration-500" 
                             :style="`width: ${(currentStep / totalSteps) * 100}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Step Indicator -->
        <div class="glass-card rounded-xl p-6 mb-8 shadow-lg animate-fade-in">
            <div class="flex items-center justify-between">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="flex items-center" :class="index < steps.length - 1 ? 'flex-1' : ''">
                        <!-- Step Circle -->
                        <div class="relative">
                            <div class="step-indicator w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold"
                                 :class="{
                                     'active': currentStep === index + 1,
                                     'completed': currentStep > index + 1,
                                     'bg-gray-300 text-gray-600': currentStep < index + 1
                                 }">
                                <span x-show="currentStep <= index + 1" x-text="index + 1"></span>
                                <svg x-show="currentStep > index + 1" class="w-6 h-6 success-checkmark" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <!-- Step Label -->
                            <div class="absolute top-14 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                                <span class="text-sm font-medium" 
                                      :class="{
                                          'text-medical-600': currentStep === index + 1,
                                          'text-gray-900 dark:text-white': currentStep > index + 1,
                                          'text-gray-500': currentStep < index + 1
                                      }"
                                      x-text="step.title"></span>
                            </div>
                        </div>
                        
                        <!-- Step Line -->
                        <div x-show="index < steps.length - 1" class="step-line flex-1 mx-4"
                             :class="{ 'completed': currentStep > index + 1 }"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Form Container -->
        <div class="glass-card rounded-xl shadow-lg overflow-hidden">
            <form @submit.prevent="submitForm()" x-ref="mainForm">
                
                <!-- Step 1: Personal Information -->
                <div x-show="currentStep === 1" class="form-section active p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Personal</h3>
                        <p class="text-gray-600 dark:text-gray-400">Masukkan data pribadi pasien dengan lengkap dan akurat.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Pasien -->
                        <div class="input-group">
                            <input type="text" 
                                   x-model="formData.nama_pasien" 
                                   @input="validateField('nama_pasien')"
                                   placeholder=" "
                                   required
                                   class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                   :class="getFieldClass('nama_pasien')">
                            <label class="floating-label">Nama Lengkap *</label>
                            <div x-show="errors.nama_pasien" class="text-red-500 text-sm mt-1" x-text="errors.nama_pasien"></div>
                        </div>
                        
                        <!-- Nomor Pasien -->
                        <div class="input-group">
                            <input type="text" 
                                   x-model="formData.nomor_pasien" 
                                   @input="validateField('nomor_pasien')"
                                   placeholder=" "
                                   class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                   :class="getFieldClass('nomor_pasien')">
                            <label class="floating-label">Nomor Pasien (Auto-generate jika kosong)</label>
                            <div x-show="errors.nomor_pasien" class="text-red-500 text-sm mt-1" x-text="errors.nomor_pasien"></div>
                        </div>
                        
                        <!-- Jenis Kelamin -->
                        <div class="input-group">
                            <select x-model="formData.jenis_kelamin" 
                                    @change="validateField('jenis_kelamin')"
                                    required
                                    class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                    :class="getFieldClass('jenis_kelamin')">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                            <label class="floating-label">Jenis Kelamin *</label>
                            <div x-show="errors.jenis_kelamin" class="text-red-500 text-sm mt-1" x-text="errors.jenis_kelamin"></div>
                        </div>
                        
                        <!-- Tanggal Lahir -->
                        <div class="input-group">
                            <input type="date" 
                                   x-model="formData.tanggal_lahir" 
                                   @change="validateField('tanggal_lahir'); calculateAge()"
                                   :max="maxDate"
                                   required
                                   class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                   :class="getFieldClass('tanggal_lahir')">
                            <label class="floating-label">Tanggal Lahir *</label>
                            <div x-show="calculatedAge" class="text-medical-600 text-sm mt-1">
                                Umur: <span x-text="calculatedAge"></span> tahun
                            </div>
                            <div x-show="errors.tanggal_lahir" class="text-red-500 text-sm mt-1" x-text="errors.tanggal_lahir"></div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contact Information -->
                <div x-show="currentStep === 2" class="form-section p-8" :class="{ 'active': currentStep === 2 }">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Kontak</h3>
                        <p class="text-gray-600 dark:text-gray-400">Data kontak dan alamat untuk komunikasi dengan pasien.</p>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Nomor Telepon -->
                        <div class="input-group">
                            <input type="tel" 
                                   x-model="formData.nomor_telepon" 
                                   @input="validateField('nomor_telepon')"
                                   placeholder=" "
                                   pattern="[0-9+\-\s\(\)]+"
                                   class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                   :class="getFieldClass('nomor_telepon')">
                            <label class="floating-label">Nomor Telepon</label>
                            <div class="text-gray-500 text-sm mt-1">Format: 08123456789 atau +6281234567890</div>
                            <div x-show="errors.nomor_telepon" class="text-red-500 text-sm mt-1" x-text="errors.nomor_telepon"></div>
                        </div>
                        
                        <!-- Alamat -->
                        <div class="input-group">
                            <textarea x-model="formData.alamat" 
                                      @input="validateField('alamat')"
                                      placeholder=" "
                                      rows="4"
                                      required
                                      class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all resize-none"
                                      :class="getFieldClass('alamat')"></textarea>
                            <label class="floating-label">Alamat Lengkap *</label>
                            <div class="flex justify-between items-center mt-1">
                                <div x-show="errors.alamat" class="text-red-500 text-sm" x-text="errors.alamat"></div>
                                <div class="text-gray-500 text-sm">
                                    <span x-text="formData.alamat.length"></span>/500 karakter
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Suggestions (mockup for future implementation) -->
                        <div x-show="showAddressSuggestions" class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Saran Alamat</span>
                            </div>
                            <div class="space-y-2">
                                <button type="button" class="block w-full text-left text-sm text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-800 p-2 rounded">
                                    📍 Gunakan lokasi GPS saat ini
                                </button>
                                <button type="button" class="block w-full text-left text-sm text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-800 p-2 rounded">
                                    🏥 Alamat rumah sakit sebagai referensi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Medical Information -->
                <div x-show="currentStep === 3" class="form-section p-8" :class="{ 'active': currentStep === 3 }">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Medis</h3>
                        <p class="text-gray-600 dark:text-gray-400">Data medis dasar dan riwayat kesehatan (opsional).</p>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Emergency Contact -->
                        <div class="bg-orange-50 dark:bg-orange-900 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-orange-800 dark:text-orange-200 mb-3">Kontak Darurat</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="input-group">
                                    <input type="text" 
                                           x-model="formData.emergency_contact_name" 
                                           placeholder=" "
                                           class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                                    <label class="floating-label">Nama Kontak Darurat</label>
                                </div>
                                
                                <div class="input-group">
                                    <input type="tel" 
                                           x-model="formData.emergency_contact_phone" 
                                           placeholder=" "
                                           class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                                    <label class="floating-label">Telepon Kontak Darurat</label>
                                </div>
                                
                                <div class="input-group md:col-span-2">
                                    <select x-model="formData.emergency_contact_relation" 
                                            class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                                        <option value="">Pilih Hubungan</option>
                                        <option value="orang_tua">Orang Tua</option>
                                        <option value="pasangan">Pasangan</option>
                                        <option value="anak">Anak</option>
                                        <option value="saudara">Saudara</option>
                                        <option value="teman">Teman</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                    <label class="floating-label">Hubungan dengan Pasien</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical History -->
                        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-red-800 dark:text-red-200 mb-3">Riwayat Medis</h4>
                            
                            <div class="space-y-4">
                                <!-- Allergies -->
                                <div class="input-group">
                                    <textarea x-model="formData.allergies" 
                                              placeholder=" "
                                              rows="2"
                                              class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all resize-none"></textarea>
                                    <label class="floating-label">Alergi Obat/Makanan</label>
                                    <div class="text-gray-500 text-sm mt-1">Sebutkan alergi yang diketahui (jika ada)</div>
                                </div>
                                
                                <!-- Medical Conditions -->
                                <div class="input-group">
                                    <textarea x-model="formData.medical_conditions" 
                                              placeholder=" "
                                              rows="2"
                                              class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all resize-none"></textarea>
                                    <label class="floating-label">Riwayat Penyakit</label>
                                    <div class="text-gray-500 text-sm mt-1">Riwayat penyakit yang pernah diderita</div>
                                </div>
                                
                                <!-- Current Medications -->
                                <div class="input-group">
                                    <textarea x-model="formData.current_medications" 
                                              placeholder=" "
                                              rows="2"
                                              class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all resize-none"></textarea>
                                    <label class="floating-label">Obat yang Sedang Dikonsumsi</label>
                                    <div class="text-gray-500 text-sm mt-1">Daftar obat yang sedang dikonsumsi rutin</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review & Confirmation -->
                <div x-show="currentStep === 4" class="form-section p-8" :class="{ 'active': currentStep === 4 }">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Review & Konfirmasi</h3>
                        <p class="text-gray-600 dark:text-gray-400">Periksa kembali data yang telah dimasukkan sebelum menyimpan.</p>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Personal Information Review -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">📋 Informasi Personal</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Nama Lengkap:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.nama_pasien || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Nomor Pasien:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.nomor_pasien || 'Auto-generate'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Jenis Kelamin:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.jenis_kelamin === 'L' ? 'Laki-laki' : formData.jenis_kelamin === 'P' ? 'Perempuan' : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Tanggal Lahir:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formatDate(formData.tanggal_lahir) + (calculatedAge ? ` (${calculatedAge} tahun)` : '')"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Information Review -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">📞 Informasi Kontak</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Telepon:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.nomor_telepon || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Alamat:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.alamat || '-'"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Information Review -->
                        <div x-show="hasEmergencyContact || hasMedicalInfo" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">🏥 Informasi Medis</h4>
                            <div class="space-y-2 text-sm">
                                <div x-show="formData.emergency_contact_name">
                                    <span class="text-gray-600 dark:text-gray-400">Kontak Darurat:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2">
                                        <span x-text="formData.emergency_contact_name"></span>
                                        <span x-show="formData.emergency_contact_phone"> - <span x-text="formData.emergency_contact_phone"></span></span>
                                        <span x-show="formData.emergency_contact_relation"> (<span x-text="formData.emergency_contact_relation"></span>)</span>
                                    </span>
                                </div>
                                <div x-show="formData.allergies">
                                    <span class="text-gray-600 dark:text-gray-400">Alergi:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.allergies"></span>
                                </div>
                                <div x-show="formData.medical_conditions">
                                    <span class="text-gray-600 dark:text-gray-400">Riwayat Penyakit:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.medical_conditions"></span>
                                </div>
                                <div x-show="formData.current_medications">
                                    <span class="text-gray-600 dark:text-gray-400">Obat Saat Ini:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2" x-text="formData.current_medications"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Confirmation Checkbox -->
                        <div class="bg-medical-50 dark:bg-medical-900 border border-medical-200 dark:border-medical-700 rounded-lg p-4">
                            <label class="flex items-start space-x-3">
                                <input type="checkbox" 
                                       x-model="dataConfirmed" 
                                       required
                                       class="mt-1 rounded border-gray-300 text-medical-600 focus:ring-medical-500">
                                <div class="text-sm">
                                    <span class="font-medium text-medical-800 dark:text-medical-200">Konfirmasi Data</span>
                                    <p class="text-medical-700 dark:text-medical-300 mt-1">
                                        Saya menyatakan bahwa data yang dimasukkan sudah benar dan akurat. 
                                        Data ini akan digunakan untuk keperluan medis dan administrasi rumah sakit.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="bg-gray-50 dark:bg-gray-800 px-8 py-6 flex justify-between items-center">
                    <button type="button" 
                            @click="previousStep()" 
                            x-show="currentStep > 1"
                            class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Sebelumnya
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Save as Draft Button -->
                        <button type="button" 
                                @click="saveAsDraft()"
                                x-show="currentStep > 1 && currentStep < totalSteps"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Simpan Draft
                        </button>
                        
                        <!-- Next/Submit Button -->
                        <button type="button" 
                                @click="nextStep()" 
                                x-show="currentStep < totalSteps"
                                :disabled="!canProceedToNext()"
                                :class="canProceedToNext() ? 'bg-medical-600 hover:bg-medical-700' : 'bg-gray-400 cursor-not-allowed'"
                                class="inline-flex items-center px-6 py-3 text-white font-medium rounded-lg transition-colors">
                            Selanjutnya
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        
                        <button type="submit" 
                                x-show="currentStep === totalSteps"
                                :disabled="submitting || !dataConfirmed"
                                :class="(submitting || !dataConfirmed) ? 'bg-gray-400 cursor-not-allowed' : 'bg-medical-600 hover:bg-medical-700'"
                                class="inline-flex items-center px-8 py-3 text-white font-medium rounded-lg transition-colors">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="submitting ? 'Menyimpan...' : 'Simpan Pasien'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
         style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600 success-checkmark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mt-4">Pasien Berhasil Disimpan!</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Data pasien <strong x-text="formData.nama_pasien"></strong> telah berhasil disimpan ke dalam sistem.
                    </p>
                </div>
                <div class="items-center px-4 py-3 space-y-2">
                    <button @click="goToPatientList()" 
                            class="px-4 py-2 bg-medical-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-300">
                        Kembali ke Daftar Pasien
                    </button>
                    <button @click="createAnother()" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Tambah Pasien Lain
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function pasienCreateWizard() {
            return {
                // Current state
                currentStep: 1,
                totalSteps: 4,
                submitting: false,
                showSuccessModal: false,
                dataConfirmed: false,
                
                // Form data
                formData: {
                    nama_pasien: '',
                    nomor_pasien: '',
                    jenis_kelamin: '',
                    tanggal_lahir: '',
                    nomor_telepon: '',
                    alamat: '',
                    emergency_contact_name: '',
                    emergency_contact_phone: '',
                    emergency_contact_relation: '',
                    allergies: '',
                    medical_conditions: '',
                    current_medications: ''
                },
                
                // Validation
                errors: {},
                validationRules: {
                    nama_pasien: { required: true, min: 2, max: 255 },
                    nomor_pasien: { max: 50 },
                    jenis_kelamin: { required: true, in: ['L', 'P'] },
                    tanggal_lahir: { required: true, date: true, before: 'today' },
                    nomor_telepon: { max: 20, pattern: /^[0-9+\-\s\(\)]*$/ },
                    alamat: { required: true, min: 10, max: 500 }
                },
                
                // Steps configuration
                steps: [
                    { title: 'Personal', icon: 'user' },
                    { title: 'Kontak', icon: 'phone' },
                    { title: 'Medis', icon: 'heart' },
                    { title: 'Review', icon: 'check' }
                ],
                
                // Computed properties
                calculatedAge: '',
                maxDate: new Date().toISOString().split('T')[0],
                showAddressSuggestions: false,
                
                // Initialize
                init() {
                    this.loadFormData();
                },
                
                // Step navigation
                nextStep() {
                    if (this.canProceedToNext()) {
                        if (this.currentStep < this.totalSteps) {
                            this.currentStep++;
                            this.updateFormSections();
                            this.saveFormData(); // Auto-save progress
                        }
                    }
                },
                
                previousStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                        this.updateFormSections();
                    }
                },
                
                updateFormSections() {
                    // Add transition effects
                    const sections = document.querySelectorAll('.form-section');
                    sections.forEach((section, index) => {
                        if (index + 1 === this.currentStep) {
                            setTimeout(() => section.classList.add('active'), 100);
                        } else {
                            section.classList.remove('active');
                        }
                    });
                },
                
                canProceedToNext() {
                    switch (this.currentStep) {
                        case 1:
                            return this.validateStep1();
                        case 2:
                            return this.validateStep2();
                        case 3:
                            return true; // Optional step
                        case 4:
                            return this.dataConfirmed;
                        default:
                            return false;
                    }
                },
                
                // Validation methods
                validateStep1() {
                    const requiredFields = ['nama_pasien', 'jenis_kelamin', 'tanggal_lahir'];
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!this.validateField(field)) {
                            isValid = false;
                        }
                    });
                    
                    return isValid;
                },
                
                validateStep2() {
                    return this.validateField('alamat');
                },
                
                validateField(fieldName) {
                    const value = this.formData[fieldName];
                    const rules = this.validationRules[fieldName];
                    
                    if (!rules) return true;
                    
                    // Clear previous error
                    delete this.errors[fieldName];
                    
                    // Required validation
                    if (rules.required && (!value || value.trim() === '')) {
                        this.errors[fieldName] = 'Field ini wajib diisi';
                        return false;
                    }
                    
                    // Skip other validations if field is empty and not required
                    if (!value || value.trim() === '') return true;
                    
                    // Minimum length validation
                    if (rules.min && value.length < rules.min) {
                        this.errors[fieldName] = `Minimal ${rules.min} karakter`;
                        return false;
                    }
                    
                    // Maximum length validation
                    if (rules.max && value.length > rules.max) {
                        this.errors[fieldName] = `Maksimal ${rules.max} karakter`;
                        return false;
                    }
                    
                    // Pattern validation
                    if (rules.pattern && !rules.pattern.test(value)) {
                        this.errors[fieldName] = 'Format tidak valid';
                        return false;
                    }
                    
                    // Date validation
                    if (rules.date) {
                        const date = new Date(value);
                        if (isNaN(date.getTime())) {
                            this.errors[fieldName] = 'Format tanggal tidak valid';
                            return false;
                        }
                        
                        if (rules.before === 'today' && date >= new Date()) {
                            this.errors[fieldName] = 'Tanggal harus sebelum hari ini';
                            return false;
                        }
                    }
                    
                    // In validation
                    if (rules.in && !rules.in.includes(value)) {
                        this.errors[fieldName] = 'Pilihan tidak valid';
                        return false;
                    }
                    
                    return true;
                },
                
                getFieldClass(fieldName) {
                    if (this.errors[fieldName]) {
                        return 'border-red-500 dark:border-red-500';
                    } else if (this.formData[fieldName] && this.validateField(fieldName)) {
                        return 'border-green-500 dark:border-green-500';
                    }
                    return 'border-gray-300 dark:border-gray-600';
                },
                
                // Utility methods
                calculateAge() {
                    if (!this.formData.tanggal_lahir) {
                        this.calculatedAge = '';
                        return;
                    }
                    
                    const today = new Date();
                    const birthDate = new Date(this.formData.tanggal_lahir);
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    
                    this.calculatedAge = age;
                },
                
                formatDate(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },
                
                // Computed properties for review
                get hasEmergencyContact() {
                    return this.formData.emergency_contact_name || this.formData.emergency_contact_phone;
                },
                
                get hasMedicalInfo() {
                    return this.formData.allergies || this.formData.medical_conditions || this.formData.current_medications;
                },
                
                // Form submission
                async submitForm() {
                    if (!this.dataConfirmed) return;
                    
                    this.submitting = true;
                    
                    try {
                        const response = await axios.post('/petugas/enhanced/pasien', this.formData);
                        
                        if (response.data.success) {
                            this.showSuccessModal = true;
                            this.clearFormData(); // Clear saved draft
                        } else {
                            throw new Error(response.data.message || 'Terjadi kesalahan');
                        }
                    } catch (error) {
                        console.error('Error submitting form:', error);
                        this.showAlert('error', 'Gagal menyimpan data: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.submitting = false;
                    }
                },
                
                // Draft management
                saveAsDraft() {
                    this.saveFormData();
                    this.showAlert('success', 'Draft berhasil disimpan');
                },
                
                saveFormData() {
                    localStorage.setItem('pasien_form_draft', JSON.stringify({
                        formData: this.formData,
                        currentStep: this.currentStep,
                        timestamp: new Date().toISOString()
                    }));
                },
                
                loadFormData() {
                    const saved = localStorage.getItem('pasien_form_draft');
                    if (saved) {
                        try {
                            const data = JSON.parse(saved);
                            const savedTime = new Date(data.timestamp);
                            const now = new Date();
                            const hoursDiff = (now - savedTime) / (1000 * 60 * 60);
                            
                            // Load draft if less than 24 hours old
                            if (hoursDiff < 24) {
                                this.formData = { ...this.formData, ...data.formData };
                                if (confirm('Ditemukan draft yang belum selesai. Apakah Anda ingin melanjutkan?')) {
                                    this.currentStep = data.currentStep || 1;
                                }
                            }
                        } catch (e) {
                            console.error('Error loading draft:', e);
                        }
                    }
                },
                
                clearFormData() {
                    localStorage.removeItem('pasien_form_draft');
                },
                
                // Success modal actions
                goToPatientList() {
                    window.location.href = '/petugas/enhanced/pasien';
                },
                
                createAnother() {
                    // Reset form
                    this.formData = {
                        nama_pasien: '',
                        nomor_pasien: '',
                        jenis_kelamin: '',
                        tanggal_lahir: '',
                        nomor_telepon: '',
                        alamat: '',
                        emergency_contact_name: '',
                        emergency_contact_phone: '',
                        emergency_contact_relation: '',
                        allergies: '',
                        medical_conditions: '',
                        current_medications: ''
                    };
                    this.errors = {};
                    this.currentStep = 1;
                    this.dataConfirmed = false;
                    this.showSuccessModal = false;
                    this.calculatedAge = '';
                    this.clearFormData();
                    this.updateFormSections();
                },
                
                // Alert system
                showAlert(type, message) {
                    const alertClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
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