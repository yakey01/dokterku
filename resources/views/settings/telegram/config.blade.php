@extends('layouts.settings')

@section('title', 'Konfigurasi Telegram Bot')

@section('content')
<div class="space-y-8">
    
    <!-- Section 1: Bot Configuration -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                <svg class="w-6 h-6 text-blue-400 mr-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                </svg>
                Konfigurasi Bot Telegram
            </h2>
            <p class="text-sm text-gray-400 mt-1">Setup dasar bot dan admin utama</p>
        </div>
        
        <div class="p-6">
            <form id="botConfigForm" class="space-y-6">
                @csrf
                
                <!-- Bot Token (Readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        🔐 Token Bot Telegram
                    </label>
                    <div class="flex">
                        <input type="password" 
                               id="botToken"
                               class="flex-1 bg-gray-700 border border-gray-600 text-gray-300 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block p-3"
                               value="{{ str_repeat('•', 20) . substr(env('TELEGRAM_BOT_TOKEN', ''), -6) }}"
                               readonly>
                        <button type="button" 
                                id="toggleToken"
                                class="px-4 py-3 bg-gray-600 border border-l-0 border-gray-600 text-gray-300 text-sm rounded-r-lg hover:bg-gray-500 focus:ring-2 focus:ring-blue-500">
                            👁️
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Token disimpan aman di file .env. Hubungi admin sistem untuk mengubah.
                    </p>
                </div>

                <!-- Admin Chat ID -->
                <div>
                    <label for="adminChatId" class="block text-sm font-medium text-gray-300 mb-2">
                        📲 Telegram Chat ID Admin Utama
                    </label>
                    <input type="text" 
                           id="adminChatId"
                           name="admin_chat_id"
                           class="bg-gray-700 border border-gray-600 text-gray-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3"
                           placeholder="Contoh: 123456789 atau -1001234567890"
                           value="{{ $adminChatId ?? '' }}">
                    <p class="text-xs text-gray-500 mt-1">
                        ID ini digunakan untuk fallback notifikasi dan pengujian. Dapatkan dari @userinfobot
                    </p>
                </div>

                <!-- Test Connection -->
                <div class="flex space-x-4">
                    <button type="button" 
                            id="testConnection"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Test Kirim Notifikasi
                    </button>
                    
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Konfigurasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section 2: Role-based Notifications -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                <svg class="w-6 h-6 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                Notifikasi Role-based
            </h2>
            <p class="text-sm text-gray-400 mt-1">Konfigurasi Chat ID dan jenis notifikasi per role</p>
        </div>
        
        <div class="p-6">
            <form id="roleNotificationForm" class="space-y-6">
                @csrf
                
                @php
                $roles = [
                    'admin' => ['name' => 'Admin', 'color' => 'red', 'icon' => '👑'],
                    'manajer' => ['name' => 'Manajer', 'color' => 'yellow', 'icon' => '📊'],
                    'bendahara' => ['name' => 'Bendahara', 'color' => 'green', 'icon' => '💰'],
                    'petugas' => ['name' => 'Petugas', 'color' => 'blue', 'icon' => '👨‍💼'],
                    'dokter' => ['name' => 'Dokter', 'color' => 'purple', 'icon' => '👩‍⚕️'],
                    'paramedis' => ['name' => 'Paramedis', 'color' => 'pink', 'icon' => '👨‍⚕️']
                ];

                $notificationTypes = [
                    'income_created' => '💰 Pendapatan Baru',
                    'expense_created' => '💸 Pengeluaran Baru',
                    'patient_created' => '👥 Pasien Baru',
                    'user_created' => '👤 User Baru',
                    'validation_approved' => '✅ Validasi Disetujui',
                    'validation_rejected' => '❌ Validasi Ditolak',
                    'daily_recap' => '📊 Rekap Harian',
                    'weekly_recap' => '📈 Rekap Mingguan',
                    'system_error' => '🚨 Error Sistem',
                    'backup_status' => '💾 Status Backup'
                ];
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($roles as $roleKey => $roleData)
                    @php
                    $setting = $telegramSettings->where('role', $roleKey)->first();
                    @endphp
                    
                    <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-3">{{ $roleData['icon'] }}</span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-100">{{ $roleData['name'] }}</h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $roleData['color'] }}-100 text-{{ $roleData['color'] }}-800">
                                    {{ ucfirst($roleKey) }}
                                </span>
                            </div>
                        </div>

                        <!-- Chat ID Input -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Chat ID Telegram
                            </label>
                            <input type="text" 
                                   name="roles[{{ $roleKey }}][chat_id]"
                                   class="bg-gray-600 border border-gray-500 text-gray-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3"
                                   placeholder="Contoh: -1001234567890"
                                   value="{{ $setting->chat_id ?? '' }}">
                        </div>

                        <!-- Notification Types Checklist -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-3">
                                Jenis Notifikasi
                            </label>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                @foreach($notificationTypes as $typeKey => $typeName)
                                @php
                                $checked = $setting && in_array($typeKey, $setting->notification_types ?? []);
                                @endphp
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="roles[{{ $roleKey }}][notifications][]"
                                           value="{{ $typeKey }}"
                                           class="rounded border-gray-500 text-blue-600 shadow-sm focus:ring-blue-500 bg-gray-600"
                                           {{ $checked ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-300">{{ $typeName }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="mt-4 pt-4 border-t border-gray-600">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="roles[{{ $roleKey }}][is_active]"
                                       value="1"
                                       class="rounded border-gray-500 text-green-600 shadow-sm focus:ring-green-500 bg-gray-600"
                                       {{ ($setting && $setting->is_active) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm font-medium text-gray-300">Aktifkan Notifikasi</span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Save Button -->
                <div class="flex justify-end pt-6 border-t border-gray-700">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Konfigurasi Role
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Toast Function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
        const icon = type === 'success' ? '✅' : '❌';
        
        toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform transition-all duration-300 translate-x-full opacity-0`;
        toast.innerHTML = `
            <span class="text-lg">${icon}</span>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        
        document.getElementById('toast-container').appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                try {
                    if (toast && toast.parentNode && document.contains(toast)) {
                        toast.remove();
                    }
                } catch (error) {
                    if (error.name !== 'NotFoundError') {
                        console.warn('Toast cleanup error:', error.message);
                    }
                }
            }, 300);
        }, 5000);
    }

    // Toggle Token Visibility
    document.getElementById('toggleToken').addEventListener('click', function() {
        const tokenInput = document.getElementById('botToken');
        const actualToken = '{{ env("TELEGRAM_BOT_TOKEN", "") }}';
        
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            tokenInput.value = actualToken;
            this.innerHTML = '🙈';
        } else {
            tokenInput.type = 'password';
            tokenInput.value = '{{ str_repeat("•", 20) . substr(env("TELEGRAM_BOT_TOKEN", ""), -6) }}';
            this.innerHTML = '👁️';
        }
    });

    // Test Connection
    document.getElementById('testConnection').addEventListener('click', function() {
        const adminChatId = document.getElementById('adminChatId').value;
        
        if (!adminChatId) {
            showToast('Masukkan Chat ID Admin terlebih dahulu', 'error');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Testing...';

        fetch('{{ route("settings.telegram.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                chat_id: adminChatId,
                message: '🧪 Test koneksi Telegram Bot berhasil!\n\nBot: Dokterku\nWaktu: ' + new Date().toLocaleString('id-ID')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Test notifikasi berhasil dikirim!', 'success');
            } else {
                showToast('Gagal mengirim notifikasi: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>Test Kirim Notifikasi';
        });
    });

    // Bot Config Form Submit
    document.getElementById('botConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("settings.telegram.update") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Konfigurasi bot berhasil disimpan!', 'success');
            } else {
                showToast('Gagal menyimpan konfigurasi: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
        });
    });

    // Role Notification Form Submit
    document.getElementById('roleNotificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("settings.telegram.update") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Konfigurasi role berhasil disimpan!', 'success');
            } else {
                showToast('Gagal menyimpan konfigurasi: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
        });
    });
});
</script>

<style>
/* Custom scrollbar for notification lists */
.space-y-2::-webkit-scrollbar {
    width: 4px;
}

.space-y-2::-webkit-scrollbar-track {
    background: #374151;
    border-radius: 2px;
}

.space-y-2::-webkit-scrollbar-thumb {
    background: #6B7280;
    border-radius: 2px;
}

.space-y-2::-webkit-scrollbar-thumb:hover {
    background: #9CA3AF;
}

/* Role card hover effect */
.bg-gray-700:hover {
    background-color: #4B5563;
    transition: background-color 0.2s ease;
}

/* Button loading animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
@endsection