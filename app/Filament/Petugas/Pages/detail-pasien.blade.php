<x-filament-panels::page>
    <div class="space-y-6">
        @if(isset($pasien) && $pasien)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Informasi Pasien</h2>
                
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $pasien->nama ?? '-' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">No. Rekam Medis</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $pasien->no_rm ?? '-' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $pasien->tanggal_lahir ?? '-' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Alamat</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $pasien->alamat ?? '-' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">No. Telepon</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $pasien->no_telepon ?? '-' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Aktif
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Riwayat Kunjungan</h2>
                <p class="text-sm text-gray-500">Belum ada riwayat kunjungan</p>
            </div>
        @else
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            @if(isset($pasienId) && $pasienId)
                                Data pasien dengan ID {{ $pasienId }} tidak ditemukan.
                            @else
                                Tidak ada pasien yang dipilih. Silakan pilih pasien dari daftar.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <div class="flex justify-start">
            <a href="{{ url('/petugas/daftar-pasien') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Daftar Pasien
            </a>
        </div>
    </div>
</x-filament-panels::page>