<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Klinik Dokterku - {{ auth()->user()->name ?? 'Non Paramedis' }}</title>
    <style>
        /* Using built-in system fonts */
        
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --accent-yellow: #fbbf24;
            --warning-orange: #f59e0b;
            --success-green: #10b981;
            --error-red: #ef4444;
            --neutral-50: #f8fafc;
            --neutral-100: #f1f5f9;
            --neutral-200: #e2e8f0;
            --neutral-300: #cbd5e1;
            --neutral-600: #475569;
            --neutral-900: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 35%, var(--accent-yellow) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
            animation: backgroundFlow 20s ease-in-out infinite;
            font-feature-settings: 'kern' 1, 'liga' 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        @keyframes backgroundFlow {
            0%, 100% { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 35%, var(--accent-yellow) 100%); }
            25% { background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--primary-blue) 35%, var(--warning-orange) 100%); }
            50% { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 35%, var(--accent-yellow) 100%); }
            75% { background: linear-gradient(135deg, var(--primary-blue) 0%, #2563eb 35%, #f97316 100%); }
        }
        
        .device-container {
            width: 375px;
            height: 812px;
            background: linear-gradient(145deg, #0f172a, #1e293b, #334155);
            border-radius: 40px;
            padding: 4px;
            box-shadow: 
                0 50px 150px rgba(0,0,0,0.4),
                0 0 0 1px rgba(59, 130, 246, 0.3),
                inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .device-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: conic-gradient(from 0deg, var(--secondary-blue), var(--accent-yellow), var(--secondary-blue));
            border-radius: 42px;
            z-index: -1;
            animation: borderRotate 8s linear infinite;
            opacity: 0.6;
        }
        
        @keyframes borderRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .screen {
            width: 100%;
            height: 100%;
            background: #ffffff;
            border-radius: 36px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .status-bar {
            height: 44px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            position: relative;
        }
        
        .app-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 70%, var(--accent-yellow) 100%);
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .app-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.2) 0%, transparent 70%);
            animation: headerGlow 15s ease-in-out infinite;
        }
        
        @keyframes headerGlow {
            0%, 100% { transform: translate(-25%, -25%) scale(1); opacity: 0.3; }
            50% { transform: translate(-75%, -75%) scale(1.2); opacity: 0.1; }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .back-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.35);
            transform: scale(1.05);
        }
        
        .header-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .main-content {
            flex: 1;
            background: linear-gradient(to bottom, var(--neutral-50), var(--neutral-100));
            overflow-y: auto;
            position: relative;
        }
        
        .page {
            display: none;
            padding: 24px 20px;
            min-height: 100%;
        }
        
        .page.active {
            display: block;
        }
        
        /* HOME PAGE STYLES */
        .welcome-card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--neutral-200);
            text-align: center;
        }
        
        .welcome-avatar {
            width: 80px;
            height: 80px;
            border-radius: 40px;
            background: linear-gradient(135deg, var(--accent-yellow), var(--warning-orange));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: 0 8px 30px rgba(251, 191, 36, 0.4);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06);
            border: 1px solid var(--neutral-200);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* PRESENSI PAGE STYLES */
        .current-time {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--neutral-200);
        }
        
        .time-display {
            font-size: 48px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -2px;
        }
        
        .date-display {
            font-size: 16px;
            color: var(--neutral-600);
            font-weight: 500;
        }
        
        .attendance-card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--neutral-200);
        }
        
        .attendance-status {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .status-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-yellow));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.3);
        }
        
        .status-info h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: 4px;
        }
        
        .status-info p {
            font-size: 14px;
            color: var(--neutral-600);
        }
        
        .map-container {
            height: 200px;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, var(--neutral-100), var(--neutral-200));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neutral-600);
            font-size: 16px;
            font-weight: 500;
            position: relative;
        }
        
        .map-placeholder {
            text-align: center;
            padding: 40px 20px;
        }
        
        .map-placeholder::before {
            content: '🗺️';
            font-size: 48px;
            display: block;
            margin-bottom: 16px;
        }
        
        .location-info {
            padding: 20px;
            background: var(--neutral-100);
            border-radius: 16px;
            margin-bottom: 24px;
        }
        
        .location-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .location-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--success-green), #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .attendance-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .attendance-btn {
            padding: 18px 24px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .check-in-btn {
            background: linear-gradient(135deg, var(--success-green), #059669);
            color: white;
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
        }
        
        .check-out-btn {
            background: linear-gradient(135deg, var(--error-red), #dc2626);
            color: white;
            box-shadow: 0 8px 30px rgba(239, 68, 68, 0.3);
        }
        
        .attendance-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        }
        
        .attendance-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Additional styles for other pages... */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: 16px;
        }
        
        /* BOTTOM NAVIGATION */
        .bottom-nav {
            background: linear-gradient(135deg, #ffffff 0%, var(--neutral-50) 100%);
            border-top: 1px solid var(--neutral-200);
            padding: 16px 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -8px 32px rgba(0,0,0,0.06);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            min-width: 44px;
            min-height: 44px;
            position: relative;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-yellow));
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(59, 130, 246, 0.3);
        }
        
        .nav-item:not(.active) {
            color: #64748b;
        }
        
        .nav-item:not(.active):hover {
            background: rgba(59, 130, 246, 0.08);
            color: var(--secondary-blue);
            transform: translateY(-2px);
        }
        
        .nav-icon {
            font-size: 22px;
            transition: all 0.3s ease;
        }
        
        .nav-item.active .nav-icon {
            transform: scale(1.1);
        }
        
        .nav-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        /* SUCCESS TOAST */
        .toast {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--success-green), #059669);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
            animation: toastSlide 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }
        
        .toast.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .toast.error {
            background: linear-gradient(135deg, var(--error-red), #dc2626);
            box-shadow: 0 8px 30px rgba(239, 68, 68, 0.3);
        }
        
        .toast.warning {
            background: linear-gradient(135deg, var(--warning-orange), #f59e0b);
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.3);
        }
        
        .toast.info {
            background: linear-gradient(135deg, var(--secondary-blue), #2563eb);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.3);
        }
        
        @keyframes toastSlide {
            from { transform: translateX(-50%) translateY(-20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .floating-action {
            position: absolute;
            bottom: 100px;
            right: 24px;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--accent-yellow), var(--warning-orange));
            border-radius: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 16px 60px rgba(251, 191, 36, 0.4);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            border: 3px solid white;
        }
        
        .floating-action:hover {
            transform: scale(1.15) rotate(90deg);
            box-shadow: 0 24px 80px rgba(251, 191, 36, 0.6);
        }
    </style>
</head>
<body>
    <div class="device-container">
        <div class="screen">
            <!-- Status Bar -->
            <div class="status-bar">
                <div id="currentTimeStatus">9:41</div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div id="connectionStatus" style="display: flex; align-items: center; gap: 4px; font-size: 12px; cursor: pointer;" onclick="retryConnection()">
                        <div id="connectionDot" style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></div>
                        <span id="connectionText">LIVE</span>
                    </div>
                    <span>●●●●○</span>
                    <span>100%</span>
                    <div style="width: 24px; height: 12px; border: 1px solid white; border-radius: 2px; position: relative;">
                        <div style="width: 100%; height: 100%; background: white; border-radius: 1px;"></div>
                    </div>
                </div>
            </div>
            
            <!-- App Header -->
            <div class="app-header">
                <div class="header-content">
                    <button class="back-btn" onclick="showPage('home')" style="opacity: 0; pointer-events: none;">←</button>
                    <h1 class="header-title" id="headerTitle">KLINIK DOKTERKU</h1>
                    <div class="header-actions">
                        <button class="back-btn">🔔</button>
                        <button class="back-btn">⚙️</button>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <!-- HOME PAGE -->
                <div id="homePage" class="page active">
                    <div class="welcome-card">
                        <div class="welcome-avatar" id="userAvatar">AS</div>
                        <h2 style="font-size: 24px; font-weight: 800; color: var(--neutral-900); margin-bottom: 8px;">Selamat Datang!</h2>
                        <p style="color: var(--neutral-600); font-size: 16px;" id="userInfo">{{ auth()->user()->name ?? 'Ahmad Santoso' }} - Admin Klinik</p>
                        <div style="margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 8px;" id="shiftStatus">
                            <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success-green);"></div>
                            <span style="font-size: 14px; color: var(--success-green); font-weight: 600;">Shift Aktif</span>
                        </div>
                    </div>
                    
                    <div class="quick-stats" id="statsContainer">
                        <div class="stat-card">
                            <div class="stat-number" id="hoursToday">08</div>
                            <div class="stat-label">Jam Kerja Hari Ini</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="workDaysMonth">22</div>
                            <div class="stat-label">Hari Kerja Bulan Ini</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="attendanceRate">95%</div>
                            <div class="stat-label">Tingkat Kehadiran</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="shiftsWeek">3</div>
                            <div class="stat-label">Shift Minggu Ini</div>
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 style="font-size: 18px; font-weight: 700; color: var(--neutral-900); margin-bottom: 16px;">Aksi Cepat</h3>
                        <div style="display: grid; gap: 12px;" id="quickActionsContainer">
                            <!-- Quick actions will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- PRESENSI PAGE -->
                <div id="presensiPage" class="page">
                    <div class="current-time">
                        <div class="time-display" id="currentTime">08:45</div>
                        <div class="date-display" id="currentDate">Senin, 15 Juli 2025</div>
                    </div>
                    
                    <div class="attendance-card">
                        <div class="attendance-status" id="attendanceStatusContainer">
                            <div class="status-icon" id="statusIcon">🕐</div>
                            <div class="status-info">
                                <h3 id="statusTitle">Belum Check-in</h3>
                                <p id="statusDescription">Klik tombol di bawah untuk memulai presensi</p>
                            </div>
                        </div>
                        
                        <div class="map-container">
                            <div class="map-placeholder">
                                Lokasi Klinik Dokterku<br>
                                <small style="color: var(--neutral-500);">Malang, Indonesia</small>
                            </div>
                        </div>
                        
                        <div class="location-info">
                            <div class="location-header">
                                <div class="location-icon">📍</div>
                                <div>
                                    <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900); margin-bottom: 4px;">Klinik Dokterku</div>
                                    <div style="font-size: 14px; color: var(--neutral-600);">Jl. Merdeka No. 123, Malang</div>
                                </div>
                            </div>
                            <div style="margin-top: 12px; display: inline-flex; align-items: center; gap: 8px; background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;" id="locationStatus">
                                <div style="width: 6px; height: 6px; border-radius: 50%; background: var(--success-green);"></div>
                                <span>Dalam jangkauan (25m)</span>
                            </div>
                        </div>
                        
                        <div class="attendance-buttons">
                            <button class="attendance-btn check-in-btn" onclick="handleCheckIn()" id="checkInBtn">
                                Check In
                            </button>
                            <button class="attendance-btn check-out-btn" onclick="handleCheckOut()" id="checkOutBtn" disabled>
                                Check Out
                            </button>
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Riwayat Presensi Hari Ini</h3>
                        <div id="attendanceHistory" style="text-align: center; padding: 40px 20px; color: var(--neutral-600);">
                            <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                            <div style="font-size: 16px; font-weight: 500;">Belum ada aktivitas presensi hari ini</div>
                        </div>
                    </div>
                </div>
                
                <!-- JADWAL PAGE -->
                <div id="jadwalPage" class="page">
                    <!-- Calendar component would go here -->
                </div>
                
                <!-- LAPORAN PAGE -->
                <div id="laporanPage" class="page">
                    <!-- Reports component would go here -->
                </div>
                
                <!-- PROFIL PAGE -->
                <div id="profilPage" class="page">
                    <!-- Profile component would go here -->
                </div>
            </div>
            
            <!-- Bottom Navigation -->
            <div class="bottom-nav">
                <div class="nav-item active" onclick="showPage('home')">
                    <div class="nav-icon">🏠</div>
                    <div class="nav-label">Beranda</div>
                </div>
                <div class="nav-item" onclick="showPage('presensi')">
                    <div class="nav-icon">📋</div>
                    <div class="nav-label">Presensi</div>
                </div>
                <div class="nav-item" onclick="showPage('jadwal')">
                    <div class="nav-icon">📅</div>
                    <div class="nav-label">Jadwal</div>
                </div>
                <div class="nav-item" onclick="showPage('laporan')">
                    <div class="nav-icon">📊</div>
                    <div class="nav-label">Laporan</div>
                </div>
                <div class="nav-item" onclick="showPage('profil')">
                    <div class="nav-icon">👤</div>
                    <div class="nav-label">Profil</div>
                </div>
            </div>
            
            <!-- Floating Action Button -->
            <div class="floating-action" onclick="showPage('presensi')">📸</div>
            
            <!-- Toast Notification -->
            <div id="toast" class="toast"></div>
        </div>
    </div>

    <script>
        // Global state
        let appState = {
            isCheckedIn: false,
            checkInTime: null,
            userLocation: null,
            dashboardData: null
        };
        
        // API Configuration with ngrok bypass options
        const APP_URL = "{{ config('app.url') }}";
        const API_BASE = `${APP_URL}/api/v2`;
        const API_TOKEN = "{{ $token ?? '' }}";
        
        // Alternative API bases for ngrok bypass
        const API_ALTERNATIVES = [
            `${APP_URL}/api/v2`, // Primary
            `${APP_URL.replace('https://', 'https://api.')}/api/v2`, // API subdomain fallback
            `${APP_URL.replace('.ngrok-free.app', '.ngrok.app')}/api/v2` // Paid ngrok fallback
        ];
        
        console.log('API Configuration:', {
            primary: API_BASE,
            alternatives: API_ALTERNATIVES,
            token_present: API_TOKEN ? 'YES' : 'NO'
        });
        
        // Set up axios defaults
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['Authorization'] = `Bearer ${API_TOKEN}`;
            axios.defaults.headers.common['Accept'] = 'application/json';
            axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }
        
        // Safe DOM operations
        function safeQuery(selector) {
            try {
                return document.querySelector(selector);
            } catch (e) {
                console.warn('Query selector failed:', selector, e);
                return null;
            }
        }
        
        function safeQueryAll(selector) {
            try {
                return document.querySelectorAll(selector);
            } catch (e) {
                console.warn('Query selector all failed:', selector, e);
                return [];
            }
        }
        
        // Enhanced API helper with ngrok bypass and intelligent retry
        async function apiRequest(endpoint, options = {}) {
            const MAX_RETRIES = 3;
            const RETRY_DELAY = 1000; // 1 second
            
            for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
                try {
                    const url = `${API_BASE}${endpoint}`;
                    console.log(`[Attempt ${attempt}] Making API request to:`, url);
                    console.log('API Token present:', API_TOKEN ? 'YES' : 'NO');
                    
                    const defaultOptions = {
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${API_TOKEN}`,
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            // Add ngrok bypass headers
                            'ngrok-skip-browser-warning': 'true',
                            'User-Agent': 'Klinik-Dokterku-Mobile-App/2.0'
                        }
                    };
                    
                    const response = await fetch(url, { ...defaultOptions, ...options });
                    console.log(`[Attempt ${attempt}] Response status:`, response.status);
                    console.log(`[Attempt ${attempt}] Response headers:`, response.headers.get('content-type'));
                    
                    // Check for ngrok warning page
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('text/html')) {
                        const textResponse = await response.text();
                        
                        // Detect ngrok warning page
                        if (textResponse.includes('ngrok.com') && textResponse.includes('You are about to visit')) {
                            console.warn(`[Attempt ${attempt}] Detected ngrok warning page, retrying...`);
                            
                            if (attempt < MAX_RETRIES) {
                                // Wait before retry with exponential backoff
                                await new Promise(resolve => setTimeout(resolve, RETRY_DELAY * attempt));
                                continue;
                            } else {
                                // Last attempt, try to handle gracefully
                                console.error('ngrok warning page detected after all retries, falling back to offline mode');
                                return getFallbackData(endpoint);
                            }
                        }
                        
                        // Other HTML response (not ngrok)
                        console.error(`[Attempt ${attempt}] Non-JSON response:`, textResponse.substring(0, 500));
                        throw new Error(`API returned HTML response: ${response.status} ${response.statusText}`);
                    }
                    
                    // Check if response is JSON
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error(`API returned invalid content-type: ${contentType}`);
                    }
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.message || `API request failed: ${response.status} ${response.statusText}`);
                    }
                    
                    console.log(`[Attempt ${attempt}] API request successful`);
                    updateConnectionStatus('live');
                    return data;
                    
                } catch (error) {
                    console.error(`[Attempt ${attempt}] API request failed:`, error);
                    
                    if (attempt < MAX_RETRIES) {
                        // Check if it's a network error that can be retried
                        if (error.name === 'TypeError' || error.message.includes('fetch')) {
                            console.log(`[Attempt ${attempt}] Network error, retrying in ${RETRY_DELAY * attempt}ms...`);
                            await new Promise(resolve => setTimeout(resolve, RETRY_DELAY * attempt));
                            continue;
                        }
                    }
                    
                    // Final attempt or non-retryable error
                    if (attempt === MAX_RETRIES) {
                        console.error('All API attempts failed, falling back to offline mode');
                        updateConnectionStatus('offline');
                        showToast('Koneksi bermasalah, beberapa fitur tidak tersedia', 'warning');
                        return getFallbackData(endpoint);
                    }
                    
                    throw error;
                }
            }
        }
        
        // Fallback data for when API is temporarily unavailable
        function getFallbackData(endpoint) {
            console.warn('API temporarily unavailable, using fallback data for:', endpoint);
            
            // Return minimal fallback data to keep app functional
            if (endpoint.includes('/dashboards/nonparamedis/')) {
                return {
                    success: true,
                    data: {
                        user: {
                            id: {{ auth()->user()->id ?? 1 }},
                            name: '{{ auth()->user()->name ?? "User" }}',
                            initials: '{{ substr(auth()->user()->name ?? "User", 0, 1) }}{{ substr(explode(" ", auth()->user()->name ?? "User")[1] ?? "S", 0, 1) }}',
                            role: 'Administrator Non-Medis'
                        },
                        stats: {
                            hours_today: 0,
                            work_days_this_month: 0,
                            attendance_rate: 0,
                            shifts_this_week: 0
                        },
                        current_status: 'not_checked_in',
                        quick_actions: [
                            {
                                id: 'attendance',
                                title: 'Presensi',
                                subtitle: 'Koneksi bermasalah',
                                icon: '📋',
                                action: 'presensi',
                                enabled: false
                            }
                        ]
                    }
                };
            }
            
            // Default error response
            return {
                success: false,
                message: 'Layanan sementara tidak tersedia. Silakan coba lagi.',
                data: null
            };
        }
        
        // Navigation functions
        function showPage(pageId) {
            try {
                // Hide all pages
                const pages = safeQueryAll('.page');
                pages.forEach(page => {
                    if (page) page.classList.remove('active');
                });
                
                // Show selected page
                const targetPage = safeQuery('#' + pageId + 'Page');
                if (targetPage) {
                    targetPage.classList.add('active');
                }
                
                // Update navigation
                const navItems = safeQueryAll('.nav-item');
                navItems.forEach(item => {
                    if (item) item.classList.remove('active');
                });
                
                // Find and activate the corresponding nav item
                const pageOrder = ['home', 'presensi', 'jadwal', 'laporan', 'profil'];
                const pageIndex = pageOrder.indexOf(pageId);
                if (pageIndex !== -1 && navItems[pageIndex]) {
                    navItems[pageIndex].classList.add('active');
                }
                
                // Update header title
                const titles = {
                    'home': 'KLINIK DOKTERKU',
                    'presensi': 'PRESENSI',
                    'jadwal': 'JADWAL JAGA',
                    'laporan': 'LAPORAN JAGA',
                    'profil': 'PROFIL SAYA'
                };
                
                const headerTitle = safeQuery('#headerTitle');
                if (headerTitle) {
                    headerTitle.textContent = titles[pageId] || 'KLINIK DOKTERKU';
                }
                
                // Load page data
                loadPageData(pageId);
                
            } catch (e) {
                console.warn('Error in showPage:', e);
            }
        }
        
        // Load page-specific data
        async function loadPageData(pageId) {
            try {
                switch (pageId) {
                    case 'home':
                        await loadDashboardData();
                        break;
                    case 'presensi':
                        await loadAttendanceStatus();
                        await loadTodayHistory();
                        break;
                    case 'jadwal':
                        // Load schedule data
                        break;
                    case 'laporan':
                        // Load reports data
                        break;
                    case 'profil':
                        // Load profile data
                        break;
                }
            } catch (error) {
                console.error('Error loading page data:', error);
            }
        }
        
        // Dashboard functions
        async function loadDashboardData() {
            try {
                // Update status to connecting
                updateConnectionStatus('connecting');
                
                // First test basic connectivity
                console.log('Testing API connectivity...');
                const testData = await apiRequest('/dashboards/nonparamedis/test');
                console.log('API test successful:', testData);
                
                const data = await apiRequest('/dashboards/nonparamedis/');
                appState.dashboardData = data.data;
                
                // Update UI with dashboard data
                updateDashboardUI(data.data);
                
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        }
        
        function updateDashboardUI(data) {
            try {
                // Update user info
                const userAvatar = safeQuery('#userAvatar');
                const userInfo = safeQuery('#userInfo');
                
                if (userAvatar && data.user) {
                    userAvatar.textContent = data.user.initials;
                }
                
                if (userInfo && data.user) {
                    userInfo.textContent = `${data.user.name} - ${data.user.role}`;
                }
                
                // Update stats
                if (data.stats) {
                    const hoursToday = safeQuery('#hoursToday');
                    const workDaysMonth = safeQuery('#workDaysMonth');
                    const attendanceRate = safeQuery('#attendanceRate');
                    const shiftsWeek = safeQuery('#shiftsWeek');
                    
                    if (hoursToday) hoursToday.textContent = String(data.stats.hours_today).padStart(2, '0');
                    if (workDaysMonth) workDaysMonth.textContent = data.stats.work_days_this_month;
                    if (attendanceRate) attendanceRate.textContent = data.stats.attendance_rate + '%';
                    if (shiftsWeek) shiftsWeek.textContent = data.stats.shifts_this_week;
                }
                
                // Update quick actions
                if (data.quick_actions) {
                    updateQuickActions(data.quick_actions);
                }
                
            } catch (error) {
                console.error('Error updating dashboard UI:', error);
            }
        }
        
        function updateQuickActions(actions) {
            const container = safeQuery('#quickActionsContainer');
            if (!container) return;
            
            container.innerHTML = '';
            
            actions.forEach(action => {
                const actionDiv = document.createElement('div');
                actionDiv.className = 'quick-action-item';
                
                // Check if action is enabled
                const isEnabled = action.enabled !== false;
                const bgColor = isEnabled ? 'var(--neutral-50)' : 'var(--neutral-100)';
                const textColor = isEnabled ? 'var(--neutral-900)' : 'var(--neutral-400)';
                const cursor = isEnabled ? 'pointer' : 'not-allowed';
                
                if (isEnabled) {
                    actionDiv.onclick = () => showPage(action.action);
                } else {
                    actionDiv.onclick = () => showToast('Fitur tidak tersedia saat offline', 'warning');
                }
                
                actionDiv.style.cssText = `
                    display: flex; align-items: center; gap: 16px; padding: 16px; 
                    background: ${bgColor}; border-radius: 16px; cursor: ${cursor}; 
                    transition: all 0.3s ease; opacity: ${isEnabled ? '1' : '0.6'};
                `;
                
                const iconBg = isEnabled 
                    ? 'linear-gradient(135deg, var(--secondary-blue), var(--accent-yellow))' 
                    : 'var(--neutral-300)';
                
                actionDiv.innerHTML = `
                    <div style="width: 48px; height: 48px; border-radius: 16px; background: ${iconBg}; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">${action.icon}</div>
                    <div style="flex: 1;">
                        <div style="font-size: 16px; font-weight: 600; color: ${textColor}; margin-bottom: 4px;">${action.title}</div>
                        <div style="font-size: 12px; color: var(--neutral-600);">${action.subtitle}</div>
                    </div>
                    <div style="color: var(--neutral-300); font-size: 18px;">${isEnabled ? '→' : '⚠️'}</div>
                `;
                
                // Add hover effects only for enabled actions
                if (isEnabled) {
                    actionDiv.addEventListener('mouseenter', () => {
                        actionDiv.style.background = 'white';
                        actionDiv.style.boxShadow = '0 4px 20px rgba(0,0,0,0.06)';
                    });
                    
                    actionDiv.addEventListener('mouseleave', () => {
                        actionDiv.style.background = 'var(--neutral-50)';
                        actionDiv.style.boxShadow = 'none';
                    });
                }
                
                container.appendChild(actionDiv);
            });
        }
        
        // Attendance functions
        async function loadAttendanceStatus() {
            try {
                const data = await apiRequest('/dashboards/nonparamedis/attendance/status');
                updateAttendanceUI(data.data);
            } catch (error) {
                console.error('Failed to load attendance status:', error);
            }
        }
        
        function updateAttendanceUI(data) {
            try {
                const statusIcon = safeQuery('#statusIcon');
                const statusTitle = safeQuery('#statusTitle');
                const statusDescription = safeQuery('#statusDescription');
                const checkInBtn = safeQuery('#checkInBtn');
                const checkOutBtn = safeQuery('#checkOutBtn');
                
                if (data.status === 'checked_in') {
                    appState.isCheckedIn = true;
                    appState.checkInTime = data.check_in_time;
                    
                    if (statusIcon) statusIcon.textContent = '✅';
                    if (statusTitle) statusTitle.textContent = 'Sudah Check-in';
                    if (statusDescription) statusDescription.textContent = `Masuk pada ${data.check_in_time}`;
                    
                    if (checkInBtn) {
                        checkInBtn.disabled = true;
                        checkInBtn.style.opacity = '0.5';
                    }
                    if (checkOutBtn) {
                        checkOutBtn.disabled = false;
                        checkOutBtn.style.opacity = '1';
                    }
                } else if (data.status === 'checked_out') {
                    appState.isCheckedIn = false;
                    
                    if (statusIcon) statusIcon.textContent = '🏁';
                    if (statusTitle) statusTitle.textContent = 'Sudah Check-out';
                    if (statusDescription) statusDescription.textContent = `Keluar pada ${data.check_out_time}`;
                    
                    if (checkInBtn) {
                        checkInBtn.disabled = true;
                        checkInBtn.style.opacity = '0.5';
                    }
                    if (checkOutBtn) {
                        checkOutBtn.disabled = true;
                        checkOutBtn.style.opacity = '0.5';
                    }
                } else {
                    appState.isCheckedIn = false;
                    
                    if (statusIcon) statusIcon.textContent = '🕐';
                    if (statusTitle) statusTitle.textContent = 'Belum Check-in';
                    if (statusDescription) statusDescription.textContent = 'Klik tombol di bawah untuk memulai presensi';
                    
                    if (checkInBtn) {
                        checkInBtn.disabled = false;
                        checkInBtn.style.opacity = '1';
                    }
                    if (checkOutBtn) {
                        checkOutBtn.disabled = true;
                        checkOutBtn.style.opacity = '0.5';
                    }
                }
            } catch (error) {
                console.error('Error updating attendance UI:', error);
            }
        }
        
        async function handleCheckIn() {
            try {
                // Get user location
                const location = await getUserLocation();
                if (!location) {
                    showToast('Gagal mendapatkan lokasi GPS', 'error');
                    return;
                }
                
                // Set loading state
                const checkInBtn = safeQuery('#checkInBtn');
                if (checkInBtn) {
                    checkInBtn.textContent = 'Checking in...';
                    checkInBtn.disabled = true;
                }
                
                // Send check-in request
                const response = await apiRequest('/dashboards/nonparamedis/attendance/checkin', {
                    method: 'POST',
                    body: JSON.stringify({
                        latitude: location.latitude,
                        longitude: location.longitude,
                        accuracy: location.accuracy
                    })
                });
                
                if (response.success) {
                    showToast(response.message || 'Check-in berhasil!');
                    await loadAttendanceStatus();
                    await loadTodayHistory();
                    await loadDashboardData(); // Refresh dashboard stats
                }
                
            } catch (error) {
                console.error('Check-in failed:', error);
                showToast('Check-in gagal. Silakan coba lagi.', 'error');
            } finally {
                // Reset button state
                const checkInBtn = safeQuery('#checkInBtn');
                if (checkInBtn) {
                    checkInBtn.textContent = 'Check In';
                }
            }
        }
        
        async function handleCheckOut() {
            try {
                // Get user location
                const location = await getUserLocation();
                if (!location) {
                    showToast('Gagal mendapatkan lokasi GPS', 'error');
                    return;
                }
                
                // Set loading state
                const checkOutBtn = safeQuery('#checkOutBtn');
                if (checkOutBtn) {
                    checkOutBtn.textContent = 'Checking out...';
                    checkOutBtn.disabled = true;
                }
                
                // Send check-out request
                const response = await apiRequest('/dashboards/nonparamedis/attendance/checkout', {
                    method: 'POST',
                    body: JSON.stringify({
                        latitude: location.latitude,
                        longitude: location.longitude,
                        accuracy: location.accuracy
                    })
                });
                
                if (response.success) {
                    showToast(response.message || 'Check-out berhasil!');
                    await loadAttendanceStatus();
                    await loadTodayHistory();
                    await loadDashboardData(); // Refresh dashboard stats
                }
                
            } catch (error) {
                console.error('Check-out failed:', error);
                showToast('Check-out gagal. Silakan coba lagi.', 'error');
            } finally {
                // Reset button state
                const checkOutBtn = safeQuery('#checkOutBtn');
                if (checkOutBtn) {
                    checkOutBtn.textContent = 'Check Out';
                }
            }
        }
        
        async function loadTodayHistory() {
            try {
                const data = await apiRequest('/dashboards/nonparamedis/attendance/today-history');
                updateHistoryUI(data.data);
            } catch (error) {
                console.error('Failed to load today history:', error);
            }
        }
        
        function updateHistoryUI(data) {
            const historyContainer = safeQuery('#attendanceHistory');
            if (!historyContainer) return;
            
            if (!data.has_activity) {
                historyContainer.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px; color: var(--neutral-600);">
                        <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                        <div style="font-size: 16px; font-weight: 500;">Belum ada aktivitas presensi hari ini</div>
                    </div>
                `;
                return;
            }
            
            historyContainer.innerHTML = '';
            
            data.history.forEach(item => {
                const historyItem = document.createElement('div');
                historyItem.style.cssText = `
                    display: flex; align-items: center; gap: 16px; padding: 16px;
                    background: var(--neutral-50); border-radius: 16px; margin-bottom: 12px;
                    border: 1px solid var(--neutral-200);
                `;
                
                historyItem.innerHTML = `
                    <div style="background: linear-gradient(135deg, var(--secondary-blue), var(--accent-yellow)); color: white; padding: 8px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; min-width: 70px; text-align: center;">
                        ${item.time}
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900); margin-bottom: 4px;">
                            ${item.action}
                        </div>
                        <div style="font-size: 12px; color: var(--neutral-600);">
                            ${item.subtitle}
                        </div>
                    </div>
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success-green);"></div>
                `;
                
                historyContainer.appendChild(historyItem);
            });
        }
        
        // Location functions
        async function getUserLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation tidak didukung oleh browser ini'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        });
                    },
                    (error) => {
                        reject(new Error('Gagal mendapatkan lokasi: ' + error.message));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            });
        }
        
        // Connection status management
        function updateConnectionStatus(status) {
            try {
                const dot = safeQuery('#connectionDot');
                const text = safeQuery('#connectionText');
                
                if (!dot || !text) return;
                
                switch (status) {
                    case 'live':
                        dot.style.background = '#10b981'; // Green
                        text.textContent = 'LIVE';
                        break;
                    case 'offline':
                        dot.style.background = '#ef4444'; // Red
                        text.textContent = 'OFFLINE';
                        break;
                    case 'connecting':
                        dot.style.background = '#6b7280'; // Gray
                        text.textContent = 'LOADING';
                        break;
                    case 'error':
                        dot.style.background = '#f59e0b'; // Orange
                        text.textContent = 'ERROR';
                        break;
                }
            } catch (e) {
                console.warn('Error updating connection status:', e);
            }
        }
        
        // Utility functions
        function showToast(message, type = 'success') {
            try {
                const toast = safeQuery('#toast');
                if (!toast) return;
                
                // Add emoji based on type
                const emojis = {
                    'success': '✅',
                    'error': '❌',
                    'warning': '⚠️',
                    'info': 'ℹ️'
                };
                
                const emoji = emojis[type] || emojis['info'];
                toast.textContent = `${emoji} ${message}`;
                toast.className = `toast ${type}`;
                toast.classList.add('show');
                
                // Auto hide after duration based on type
                const durations = {
                    'success': 3000,
                    'error': 5000,
                    'warning': 4000,
                    'info': 3000
                };
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, durations[type] || 3000);
                
            } catch (e) {
                console.warn('Error showing toast:', e);
            }
        }
        
        function updateTime() {
            try {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                const dateString = now.toLocaleDateString('id-ID', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                const timeDisplay = safeQuery('#currentTime');
                const dateDisplay = safeQuery('#currentDate');
                const timeStatus = safeQuery('#currentTimeStatus');
                
                if (timeDisplay) timeDisplay.textContent = timeString;
                if (dateDisplay) dateDisplay.textContent = dateString;
                if (timeStatus) timeStatus.textContent = timeString;
                
            } catch (e) {
                console.warn('Error updating time:', e);
            }
        }
        
        // Enhanced initialization with connection monitoring
        function initializeApp() {
            try {
                console.log('Initializing Klinik Dokterku Mobile App...');
                
                // Set initial connection status
                updateConnectionStatus('connecting');
                
                // Update time immediately and set interval
                updateTime();
                setInterval(updateTime, 60000);
                
                // Initialize with home page
                showPage('home');
                
                // Start connection monitoring
                startConnectionMonitoring();
                
                console.log('App initialized successfully!');
                
            } catch (e) {
                console.error('Error initializing app:', e);
                updateConnectionStatus('offline');
                showToast('Gagal memuat aplikasi', 'error');
            }
        }
        
        // Connection monitoring system
        function startConnectionMonitoring() {
            // Check connection every 30 seconds
            setInterval(async () => {
                try {
                    await apiRequest('/dashboards/nonparamedis/test');
                    // Connection successful - status updated in apiRequest
                } catch (error) {
                    // Connection failed - will use mock data
                    console.log('Connection check failed, using demo mode');
                }
            }, 30000);
        }
        
        // Add retry functionality for user-triggered actions
        async function retryConnection() {
            try {
                updateConnectionStatus('connecting');
                showToast('Mencoba menghubungkan kembali...', 'info');
                
                const testData = await apiRequest('/dashboards/nonparamedis/test');
                
                if (testData.success) {
                    showToast('Koneksi berhasil dipulihkan!', 'success');
                    // Reload dashboard data
                    await loadDashboardData();
                    await loadAttendanceStatus();
                }
            } catch (error) {
                updateConnectionStatus('error');
                showToast('Koneksi gagal, coba lagi atau hubungi administrator', 'error');
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', initializeApp);
        
        // Global error handling
        window.addEventListener('error', function(e) {
            console.warn('Global error caught:', e.error);
            return true;
        });
        
        window.addEventListener('unhandledrejection', function(e) {
            console.warn('Unhandled promise rejection:', e.reason);
            e.preventDefault();
        });
    </script>
</body>
</html>