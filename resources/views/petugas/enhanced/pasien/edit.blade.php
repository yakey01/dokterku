<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pasien - {{ $pasien->nama_pasien }}</title>
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
    </style>
</head>
<body class="bg-gradient-to-br from-medical-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen" x-data="pasienEditForm()">
    
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Pasien</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pasien->nama_pasien }} - {{ $pasien->nomor_pasien }}</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/petugas/enhanced/pasien/{{ $pasien->id }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Patient Quick Info -->
        <div class="glass-card rounded-xl p-6 mb-8 shadow-lg animate-fade-in">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0 h-16 w-16">
                    <div class="h-16 w-16 rounded-full flex items-center justify-center text-white font-bold text-xl"
                         :class="'{{ $pasien->jenis_kelamin }}' === 'L' ? 'bg-blue-500' : 'bg-pink-500'">
                        {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $pasien->nama_pasien }}</h3>
                    <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <span>{{ $pasien->nomor_pasien }}</span>
                        <span>•</span>
                        <span>{{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                        <span>•</span>
                        <span>{{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->age }} tahun</span>
                        <span>•</span>
                        <span>Terdaftar {{ \Carbon\Carbon::parse($pasien->created_at)->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Terakhir diupdate
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($pasien->updated_at)->format('d M Y, H:i') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="glass-card rounded-xl shadow-lg overflow-hidden">
            <form @submit.prevent="submitForm()" x-ref="editForm">
                
                <!-- Personal Information Section -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Personal</h3>
                        <p class="text-gray-600 dark:text-gray-400">Perbarui data pribadi pasien.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Pasien -->
                        <div class="input-group">
                            <input type="text" 
                                   x-model="formData.nama_pasien" 
                                   @input="validateField('nama_pasien')"
                                   required
                                   class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all"
                                   :class="getFieldClass('nama_pasien')">
                            <label class="floating-label">Nama Lengkap *</label>
                            <div x-show="errors.nama_pasien" class="text-red-500 text-sm mt-1" x-text="errors.nama_pasien"></div>
                        </div>
                        
                        <!-- Nomor Pasien (Read Only) -->
                        <div class="input-group">
                            <input type="text" 
                                   :value="'{{ $pasien->nomor_pasien }}'"
                                   readonly
                                   class="w-full px-3 py-3 border-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 cursor-not-allowed">
                            <label class="floating-label">Nomor Pasien</label>
                            <div class="text-gray-500 text-sm mt-1">Nomor pasien tidak dapat diubah</div>
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

                <!-- Contact Information Section -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Kontak</h3>
                        <p class="text-gray-600 dark:text-gray-400">Perbarui data kontak dan alamat pasien.</p>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Nomor Telepon -->
                        <div class="input-group">
                            <input type="tel" 
                                   x-model="formData.nomor_telepon" 
                                   @input="validateField('nomor_telepon')"
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
                                      rows="4"
                                      required
                                      class="w-full px-3 py-3 border-2 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent transition-all resize-none"
                                      :class="getFieldClass('alamat')"></textarea>
                            <label class="floating-label">Alamat Lengkap *</label>
                            <div class="flex justify-between items-center mt-1">
                                <div x-show="errors.alamat" class="text-red-500 text-sm" x-text="errors.alamat"></div>
                                <div class="text-gray-500 text-sm">
                                    <span x-text="formData.alamat ? formData.alamat.length : 0"></span>/500 karakter
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Log Section -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Log Perubahan</h3>
                        <p class="text-gray-600 dark:text-gray-400">Catatan perubahan data pasien.</p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Dibuat:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($pasien->created_at)->format('d M Y, H:i') }}
                                    oleh {{ $pasien->inputBy->name ?? 'System' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Terakhir diupdate:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($pasien->updated_at)->format('d M Y, H:i') }}
                                </span>
                            </div>
                            <div x-show="hasChanges()" class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                <div class="text-sm text-orange-600 dark:text-orange-400 font-medium">
                                    ⚠️ Ada perubahan yang belum disimpan
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Klik tombol "Simpan Perubahan" untuk menyimpan perubahan data.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-gray-50 dark:bg-gray-800 px-8 py-6 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <button type="button" 
                                @click="resetForm()"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Reset
                        </button>
                        
                        <button type="button" 
                                @click="previewChanges()"
                                x-show="hasChanges()"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Preview Perubahan
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <a href="/petugas/enhanced/pasien/{{ $pasien->id }}" 
                           class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                            Batal
                        </a>
                        
                        <button type="submit" 
                                :disabled="submitting || !hasChanges() || !isFormValid()"
                                :class="(submitting || !hasChanges() || !isFormValid()) ? 'bg-gray-400 cursor-not-allowed' : 'bg-medical-600 hover:bg-medical-700'"
                                class="inline-flex items-center px-8 py-3 text-white font-medium rounded-lg transition-colors">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="submitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Changes Preview Modal -->
    <div x-show="showPreviewModal" 
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
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Preview Perubahan</h3>
                    <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <template x-for="change in getChanges()" :key="change.field">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="change.label"></div>
                            <div class="mt-1 space-y-1">
                                <div class="text-xs text-red-600 dark:text-red-400">
                                    <span class="font-medium">Sebelum:</span> <span x-text="change.before || '-'"></span>
                                </div>
                                <div class="text-xs text-green-600 dark:text-green-400">
                                    <span class="font-medium">Sesudah:</span> <span x-text="change.after || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div class="flex items-center justify-end space-x-3 mt-6">
                    <button @click="showPreviewModal = false" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400">
                        Tutup
                    </button>
                    <button @click="showPreviewModal = false; submitForm()" 
                            class="px-4 py-2 bg-medical-500 text-white text-sm font-medium rounded-md hover:bg-medical-600">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up CSRF token for axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function pasienEditForm() {
            return {
                // Current state
                submitting: false,
                showPreviewModal: false,
                
                // Original data
                originalData: {
                    nama_pasien: @json($pasien->nama_pasien),
                    jenis_kelamin: @json($pasien->jenis_kelamin),
                    tanggal_lahir: @json($pasien->tanggal_lahir),
                    nomor_telepon: @json($pasien->nomor_telepon),
                    alamat: @json($pasien->alamat)
                },
                
                // Form data
                formData: {
                    nama_pasien: @json($pasien->nama_pasien),
                    jenis_kelamin: @json($pasien->jenis_kelamin),
                    tanggal_lahir: @json($pasien->tanggal_lahir),
                    nomor_telepon: @json($pasien->nomor_telepon),
                    alamat: @json($pasien->alamat)
                },
                
                // Validation
                errors: {},
                validationRules: {
                    nama_pasien: { required: true, min: 2, max: 255 },
                    jenis_kelamin: { required: true, in: ['L', 'P'] },
                    tanggal_lahir: { required: true, date: true, before: 'today' },
                    nomor_telepon: { max: 20, pattern: /^[0-9+\-\s\(\)]*$/ },
                    alamat: { required: true, min: 10, max: 500 }
                },
                
                // Field labels for preview
                fieldLabels: {
                    nama_pasien: 'Nama Pasien',
                    jenis_kelamin: 'Jenis Kelamin',
                    tanggal_lahir: 'Tanggal Lahir',
                    nomor_telepon: 'Nomor Telepon',
                    alamat: 'Alamat'
                },
                
                // Computed properties
                calculatedAge: '',
                maxDate: new Date().toISOString().split('T')[0],
                
                // Initialize
                init() {
                    this.calculateAge();
                },
                
                // Validation methods
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
                
                isFormValid() {
                    const requiredFields = ['nama_pasien', 'jenis_kelamin', 'tanggal_lahir', 'alamat'];
                    return requiredFields.every(field => this.validateField(field));
                },
                
                // Change detection
                hasChanges() {
                    return Object.keys(this.formData).some(key => 
                        this.formData[key] !== this.originalData[key]
                    );
                },
                
                getChanges() {
                    const changes = [];
                    Object.keys(this.formData).forEach(key => {
                        if (this.formData[key] !== this.originalData[key]) {
                            let beforeValue = this.originalData[key];
                            let afterValue = this.formData[key];
                            
                            // Format gender values
                            if (key === 'jenis_kelamin') {
                                beforeValue = beforeValue === 'L' ? 'Laki-laki' : beforeValue === 'P' ? 'Perempuan' : beforeValue;
                                afterValue = afterValue === 'L' ? 'Laki-laki' : afterValue === 'P' ? 'Perempuan' : afterValue;
                            }
                            
                            // Format date values
                            if (key === 'tanggal_lahir') {
                                beforeValue = beforeValue ? this.formatDate(beforeValue) : '';
                                afterValue = afterValue ? this.formatDate(afterValue) : '';
                            }
                            
                            changes.push({
                                field: key,
                                label: this.fieldLabels[key],
                                before: beforeValue,
                                after: afterValue
                            });
                        }
                    });
                    return changes;
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
                
                // Form actions
                resetForm() {
                    if (this.hasChanges()) {
                        if (confirm('Apakah Anda yakin ingin mereset form? Semua perubahan akan hilang.')) {
                            this.formData = { ...this.originalData };
                            this.errors = {};
                            this.calculateAge();
                        }
                    }
                },
                
                previewChanges() {
                    this.showPreviewModal = true;
                },
                
                // Form submission
                async submitForm() {
                    if (!this.hasChanges()) {
                        this.showAlert('info', 'Tidak ada perubahan untuk disimpan');
                        return;
                    }
                    
                    if (!this.isFormValid()) {
                        this.showAlert('error', 'Harap perbaiki kesalahan pada form');
                        return;
                    }
                    
                    this.submitting = true;
                    
                    try {
                        const response = await axios.put('/petugas/enhanced/pasien/{{ $pasien->id }}', this.formData);
                        
                        if (response.data.success) {
                            this.originalData = { ...this.formData };
                            this.showAlert('success', response.data.message);
                            
                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = '/petugas/enhanced/pasien/{{ $pasien->id }}';
                            }, 1500);
                        } else {
                            throw new Error(response.data.message || 'Terjadi kesalahan');
                        }
                    } catch (error) {
                        console.error('Error updating patient:', error);
                        
                        // Handle validation errors
                        if (error.response?.status === 422) {
                            this.errors = error.response.data.errors || {};
                        }
                        
                        this.showAlert('error', 'Gagal menyimpan perubahan: ' + (error.response?.data?.message || error.message));
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