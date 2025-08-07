// Enhanced GPS Help System for Dokterku
// Provides comprehensive guidance for GPS detection issues

window.showGPSHelp = function(errorType = 'general') {
    const helpContent = {
        permission: {
            title: '🔐 GPS Permission Ditolak',
            icon: '🚫',
            color: 'bg-red-50 border-red-200',
            solutions: [
                {
                    step: '1. Periksa Address Bar',
                    detail: 'Lihat ikon 🔒 atau 🌐 di sebelah kiri URL',
                    action: 'Klik ikon tersebut'
                },
                {
                    step: '2. Ubah Permission',
                    detail: 'Pilih "Always allow" atau "Izinkan" untuk Location',
                    action: 'Set permission ke Allow'
                },
                {
                    step: '3. Refresh Halaman',
                    detail: 'Tekan F5 atau Ctrl+R untuk reload',
                    action: 'Refresh dan coba GPS lagi'
                }
            ],
            browserGuide: {
                Chrome: 'Klik ikon 🔒 → Site Settings → Location → Allow',
                Firefox: 'Klik ikon 🛡️ → Permissions → Location → Allow',
                Safari: 'Safari → Preferences → Websites → Location → Allow',
                Edge: 'Klik ikon 🔒 → Site Permissions → Location → Allow'
            }
        },
        unavailable: {
            title: '📡 GPS Tidak Tersedia',
            icon: '⚠️',
            color: 'bg-yellow-50 border-yellow-200',
            solutions: [
                {
                    step: '1. Aktifkan Location Services',
                    detail: 'Settings → Privacy → Location Services → ON',
                    action: 'Pastikan GPS aktif di device'
                },
                {
                    step: '2. Periksa Koneksi Internet',
                    detail: 'GPS assisted butuh koneksi internet untuk akurasi',
                    action: 'Pastikan WiFi/Data aktif'
                },
                {
                    step: '3. Pindah ke Area Terbuka',
                    detail: 'GPS bekerja optimal di outdoor/dekat jendela',
                    action: 'Hindari basement atau area tertutup'
                }
            ],
            deviceGuide: {
                Android: 'Settings → Location → ON → High Accuracy',
                iOS: 'Settings → Privacy & Security → Location Services → ON',
                Windows: 'Settings → Privacy → Location → ON',
                Mac: 'System Preferences → Security & Privacy → Location Services'
            }
        },
        timeout: {
            title: '⏰ GPS Timeout (Waktu Habis)',
            icon: '🕐',
            color: 'bg-blue-50 border-blue-200',
            solutions: [
                {
                    step: '1. Berikan Waktu Lebih',
                    detail: 'GPS cold start butuh 30-60 detik untuk lock satelit',
                    action: 'Tunggu 1-2 menit di tempat terbuka'
                },
                {
                    step: '2. Optimalkan Posisi',
                    detail: 'Langit terbuka memberikan sinyal GPS terbaik',
                    action: 'Keluar ruangan atau dekat jendela besar'
                },
                {
                    step: '3. Restart GPS',
                    detail: 'Matikan dan nyalakan kembali location services',
                    action: 'Toggle OFF → ON location services'
                }
            ],
            tips: [
                '🌤️ Cuaca berawan dapat memperlambat GPS lock',
                '🏢 Gedung tinggi dapat menghalangi sinyal satelit', 
                '🔋 GPS high accuracy menguras battery lebih cepat',
                '📍 GPS indoor akurasi hanya 3-5 meter'
            ]
        },
        general: {
            title: '🛠️ Troubleshooting GPS',
            icon: '⚙️',
            color: 'bg-gray-50 border-gray-200',
            solutions: [
                {
                    step: '1. Manual Input Alternative',
                    detail: 'Gunakan Google Maps untuk mendapatkan koordinat',
                    action: 'Maps → Right Click → Copy Coordinates'
                },
                {
                    step: '2. Browser Troubleshooting',
                    detail: 'Clear cache, disable extensions, try incognito',
                    action: 'Ctrl+Shift+N untuk incognito mode'
                },
                {
                    step: '3. Device Restart',
                    detail: 'Restart browser atau device jika masalah persisten',
                    action: 'Restart dan coba lagi'
                }
            ],
            quickFixes: [
                '🔄 Refresh halaman (F5)',
                '🌐 Periksa koneksi internet',
                '🔒 Cek permission browser', 
                '📱 Coba browser lain',
                '🗺️ Gunakan Google Maps sebagai referensi'
            ]
        }
    };

    const help = helpContent[errorType] || helpContent.general;
    
    // Create modal HTML
    const modalHTML = `
        <div id="gpsHelpModal" class="gps-help-modal-overlay" onclick="closeGPSHelp(event)">
            <div class="gps-help-modal-content" onclick="event.stopPropagation()">
                <div class="gps-help-header ${help.color}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl">${help.icon}</span>
                            <h3 class="text-lg font-bold text-gray-800">${help.title}</h3>
                        </div>
                        <button onclick="closeGPSHelp()" class="close-btn">
                            <span class="text-gray-500 hover:text-gray-700 text-xl">×</span>
                        </button>
                    </div>
                </div>
                
                <div class="gps-help-body">
                    <div class="solutions-section mb-6">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span class="text-green-600 mr-2">🔧</span>
                            Langkah Perbaikan:
                        </h4>
                        <div class="space-y-4">
                            ${help.solutions.map((solution, index) => `
                                <div class="solution-item bg-white border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start space-x-3">
                                        <div class="solution-step-number bg-blue-100 text-blue-800 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">
                                            ${index + 1}
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="font-medium text-gray-800 mb-1">${solution.step}</h5>
                                            <p class="text-sm text-gray-600 mb-2">${solution.detail}</p>
                                            <div class="action-button bg-blue-50 text-blue-700 px-3 py-1 rounded text-xs font-medium">
                                                ⚡ ${solution.action}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    ${help.browserGuide ? `
                        <div class="browser-guide-section mb-6">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="text-purple-600 mr-2">🌐</span>
                                Panduan per Browser:
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                ${Object.entries(help.browserGuide).map(([browser, guide]) => `
                                    <div class="browser-item bg-purple-50 border border-purple-200 rounded p-3">
                                        <div class="font-medium text-purple-800 text-sm mb-1">${browser}</div>
                                        <div class="text-xs text-purple-600">${guide}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${help.deviceGuide ? `
                        <div class="device-guide-section mb-6">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="text-orange-600 mr-2">📱</span>
                                Panduan per Device:
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                ${Object.entries(help.deviceGuide).map(([device, guide]) => `
                                    <div class="device-item bg-orange-50 border border-orange-200 rounded p-3">
                                        <div class="font-medium text-orange-800 text-sm mb-1">${device}</div>
                                        <div class="text-xs text-orange-600">${guide}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${help.tips ? `
                        <div class="tips-section mb-6">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="text-yellow-600 mr-2">💡</span>
                                Tips & Info:
                            </h4>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="space-y-2">
                                    ${help.tips.map(tip => `
                                        <div class="flex items-start space-x-2 text-sm">
                                            <span class="text-yellow-600 mt-0.5">•</span>
                                            <span class="text-yellow-800">${tip}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${help.quickFixes ? `
                        <div class="quick-fixes-section">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="text-green-600 mr-2">⚡</span>
                                Quick Fixes:
                            </h4>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-2">
                                    ${help.quickFixes.map(fix => `
                                        <div class="flex items-center space-x-2 text-sm text-green-800">
                                            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                            <span>${fix}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                <div class="gps-help-footer">
                    <div class="flex justify-between items-center">
                        <button onclick="openGoogleMapsGuide()" class="maps-btn bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            🗺️ Panduan Google Maps
                        </button>
                        <button onclick="closeGPSHelp()" class="close-btn-primary bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Inject CSS if not already present
    if (!document.getElementById('gpsHelpStyles')) {
        const styles = `
            <style id="gpsHelpStyles">
                .gps-help-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    padding: 20px;
                }
                
                .gps-help-modal-content {
                    background: white;
                    border-radius: 12px;
                    max-width: 600px;
                    width: 100%;
                    max-height: 90vh;
                    overflow: hidden;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                }
                
                .gps-help-header {
                    padding: 20px;
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .gps-help-body {
                    padding: 20px;
                    max-height: 60vh;
                    overflow-y: auto;
                }
                
                .gps-help-footer {
                    padding: 20px;
                    border-top: 1px solid #e5e7eb;
                    background: #f9fafb;
                }
                
                .close-btn {
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 4px;
                }
                
                @media (max-width: 640px) {
                    .gps-help-modal-overlay {
                        padding: 10px;
                    }
                    
                    .gps-help-modal-content {
                        max-height: 95vh;
                    }
                    
                    .grid-cols-2 {
                        grid-template-columns: 1fr !important;
                    }
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', styles);
    }

    // Remove existing modal
    const existingModal = document.getElementById('gpsHelpModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
};

window.closeGPSHelp = function(event) {
    if (event && event.target !== event.currentTarget) {
        return; // Don't close if clicking inside modal content
    }
    
    const modal = document.getElementById('gpsHelpModal');
    if (modal) {
        modal.remove();
    }
};

window.openGoogleMapsGuide = function() {
    const guide = `
        <div class="google-maps-guide">
            <h3>📍 Cara Mendapatkan Koordinat dari Google Maps:</h3>
            <div class="steps">
                <div class="step">
                    <strong>1. Buka Google Maps</strong><br>
                    <a href="https://maps.google.com" target="_blank">https://maps.google.com</a>
                </div>
                <div class="step">
                    <strong>2. Cari Lokasi</strong><br>
                    Ketik nama lokasi di search box
                </div>
                <div class="step">
                    <strong>3. Klik Kanan di Map</strong><br>
                    Klik kanan pada titik yang diinginkan
                </div>
                <div class="step">
                    <strong>4. Copy Koordinat</strong><br>
                    Klik koordinat yang muncul di popup (format: -6.123456, 106.654321)
                </div>
                <div class="step">
                    <strong>5. Paste ke Form</strong><br>
                    Masukkan latitude dan longitude ke form manual
                </div>
            </div>
            <div class="example">
                <strong>Contoh Koordinat Jakarta:</strong><br>
                Latitude: -6.2088200<br>
                Longitude: 106.8238800
            </div>
        </div>
    `;
    
    if (window.Filament) {
        window.Filament.notification()
            .title('🗺️ Panduan Google Maps')
            .body(guide)
            .info()
            .duration(15000)
            .send();
    } else {
        alert(guide.replace(/<[^>]*>/g, '\n').replace(/\n+/g, '\n'));
    }
};

// Auto-attach to window for global access
console.log('📍 GPS Help System loaded successfully');