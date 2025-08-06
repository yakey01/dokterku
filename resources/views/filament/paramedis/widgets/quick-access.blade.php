<div class="fi-wi-widget">
    <div class="fi-wi-widget-content">
        {{-- Responsive grid: 1 column mobile, 2 columns tablet/desktop --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Welcome Section - Full width on mobile, half on desktop --}}
            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg p-4 sm:p-5 md:p-6 text-white">
                <h2 class="text-lg sm:text-xl font-bold mb-2">
                    🩺 Selamat datang, {{ $user_name }}!
                </h2>
                <p class="text-emerald-100 mb-4 text-sm sm:text-base">
                    Kelola presensi dan lihat laporan kehadiran Anda dengan mudah
                </p>
                {{-- Stack on mobile, flex on larger screens --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-sm">
                    <span class="bg-white/20 px-3 py-1.5 rounded inline-block">
                        📊 {{ $attendance_count }} total presensi
                    </span>
                    <span class="bg-white/20 px-3 py-1.5 rounded inline-block">
                        📅 {{ $this_month_count }} bulan ini
                    </span>
                </div>
            </div>

            {{-- Quick Actions - Full width on mobile, half on desktop --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-5 md:p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-900 dark:text-white">
                    🚀 Akses Cepat
                </h3>
                <div class="space-y-2 sm:space-y-3">
                    <a href="{{ url('/paramedis/attendance-histories') }}" 
                       class="flex items-center p-3 sm:p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-emerald-500 rounded-lg flex items-center justify-center text-white mr-3 text-lg sm:text-xl">
                            📊
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm sm:text-base text-gray-900 dark:text-white group-hover:text-emerald-600 truncate">
                                Laporan Presensi Saya
                            </div>
                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                Lihat riwayat kehadiran lengkap
                            </div>
                        </div>
                        <div class="ml-2 text-emerald-500 text-lg">
                            →
                        </div>
                    </a>

                    <a href="{{ url('/paramedis/attendances') }}" 
                       class="flex items-center p-3 sm:p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white mr-3 text-lg sm:text-xl">
                            ✏️
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm sm:text-base text-gray-900 dark:text-white group-hover:text-blue-600 truncate">
                                Input Presensi
                            </div>
                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                Tambah atau edit presensi
                            </div>
                        </div>
                        <div class="ml-2 text-blue-500 text-lg">
                            →
                        </div>
                    </a>

                    <a href="{{ url('/paramedis/mobile-app') }}" 
                       class="flex items-center p-3 sm:p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-purple-500 rounded-lg flex items-center justify-center text-white mr-3 text-lg sm:text-xl">
                            📱
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm sm:text-base text-gray-900 dark:text-white group-hover:text-purple-600 truncate">
                                Mobile App
                            </div>
                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                Akses aplikasi mobile
                            </div>
                        </div>
                        <div class="ml-2 text-purple-500 text-lg">
                            →
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Help Section - Full width with responsive padding --}}
        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 sm:p-4 md:p-5">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm sm:text-base font-bold">
                        💡
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <h4 class="text-sm sm:text-base font-medium text-amber-800 dark:text-amber-200">
                        Tips: Cara Melihat Laporan Presensi
                    </h4>
                    <div class="mt-1 sm:mt-2 text-xs sm:text-sm text-amber-700 dark:text-amber-300">
                        <p class="break-words">Klik tombol <strong>"📊 Laporan Presensi Saya"</strong> di atas atau gunakan menu sidebar <strong class="hidden sm:inline">"📅 PRESENSI & LAPORAN"</strong> <span class="sm:hidden">📅 PRESENSI</span> → <strong>"📊 Laporan Presensi Saya"</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>