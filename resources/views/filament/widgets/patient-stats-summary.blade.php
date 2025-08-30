{{-- Patient Statistics Summary Widget for Petugas Dashboard --}}
<div class="elegant-dark-header-widget p-6 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-xl border border-slate-700">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- Total Patients --}}
        <div class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-400 text-sm font-medium">Total Pasien</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ \App\Models\Pasien::count() }}</p>
                </div>
                <div class="bg-blue-500/20 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-sm">
                <span class="text-green-400 text-xs">+{{ \App\Models\Pasien::whereDate('created_at', today())->count() }}</span>
                <span class="text-slate-400 text-xs ml-2">pasien hari ini</span>
            </div>
        </div>

        {{-- Verified Patients --}}
        <div class="bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-400 text-sm font-medium">Terverifikasi</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ \App\Models\Pasien::where('status', 'verified')->count() }}</p>
                </div>
                <div class="bg-green-500/20 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-sm">
                <span class="text-green-400 text-xs">{{ number_format((\App\Models\Pasien::where('status', 'verified')->count() / max(\App\Models\Pasien::count(), 1)) * 100, 1) }}%</span>
                <span class="text-slate-400 text-xs ml-2">dari total</span>
            </div>
        </div>

        {{-- Pending Verification --}}
        <div class="bg-gradient-to-br from-amber-500/10 to-amber-600/5 border border-amber-500/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-400 text-sm font-medium">Menunggu</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ \App\Models\Pasien::where('status', 'pending')->count() }}</p>
                </div>
                <div class="bg-amber-500/20 p-3 rounded-full">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-sm">
                <span class="text-amber-400 text-xs">{{ \App\Models\Pasien::where('status', 'pending')->whereDate('created_at', today())->count() }}</span>
                <span class="text-slate-400 text-xs ml-2">baru hari ini</span>
            </div>
        </div>

        {{-- Gender Distribution --}}
        <div class="bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-400 text-sm font-medium">Distribusi</p>
                    <div class="flex items-center space-x-4 mt-1">
                        <div class="text-center">
                            <p class="text-lg font-bold text-white">{{ \App\Models\Pasien::where('jenis_kelamin', 'L')->count() }}</p>
                            <p class="text-xs text-slate-400">👨 L</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-white">{{ \App\Models\Pasien::where('jenis_kelamin', 'P')->count() }}</p>
                            <p class="text-xs text-slate-400">👩 P</p>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-500/20 p-3 rounded-full">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 00-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Row --}}
    <div class="mt-6 pt-4 border-t border-slate-700">
        <div class="flex flex-wrap gap-3">
            <div class="flex items-center space-x-2 bg-slate-800/50 px-3 py-2 rounded-lg border border-slate-600">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-sm text-slate-300">Sistem Aktif</span>
            </div>
            <div class="flex items-center space-x-2 bg-slate-800/50 px-3 py-2 rounded-lg border border-slate-600">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm text-slate-300">Auto-refresh: 30s</span>
            </div>
            <div class="flex items-center space-x-2 bg-slate-800/50 px-3 py-2 rounded-lg border border-slate-600">
                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span class="text-sm text-slate-300">Mode: {{ config('app.env') }}</span>
            </div>
        </div>
    </div>
</div>