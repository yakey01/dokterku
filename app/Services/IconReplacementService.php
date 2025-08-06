<?php

namespace App\Services;

class IconReplacementService
{
    /**
     * Mapping of emoji icons to Heroicons
     */
    public static array $iconMapping = [
        // Medical & Healthcare
        '🏥' => 'heroicon-o-building-office-2',
        '💉' => 'heroicon-o-beaker',
        '🩺' => 'heroicon-o-heart',
        '📋' => 'heroicon-o-clipboard-document-list',
        '🔬' => 'heroicon-o-academic-cap',
        
        // Financial & Money
        '💰' => 'heroicon-o-currency-dollar',
        '💸' => 'heroicon-o-banknotes',
        '💳' => 'heroicon-o-credit-card',
        '📊' => 'heroicon-o-chart-bar-square',
        '📈' => 'heroicon-o-chart-bar',
        '📉' => 'heroicon-o-arrow-trending-down',
        
        // People & Users
        '👥' => 'heroicon-o-user-group',
        '👤' => 'heroicon-o-user',
        '👨‍⚕️' => 'heroicon-o-user',
        '👩‍⚕️' => 'heroicon-o-user',
        '🧑‍💼' => 'heroicon-o-briefcase',
        
        // System & Settings
        '⚙️' => 'heroicon-o-cog-6-tooth',
        '🔧' => 'heroicon-o-wrench-screwdriver',
        '🔩' => 'heroicon-o-cog',
        '⚡' => 'heroicon-o-bolt',
        '🔌' => 'heroicon-o-power',
        
        // Location & GPS
        '📍' => 'heroicon-o-map-pin',
        '🗺️' => 'heroicon-o-map',
        '🧭' => 'heroicon-o-compass',
        '📡' => 'heroicon-o-signal',
        
        // Documents & Files
        '📄' => 'heroicon-o-document',
        '📁' => 'heroicon-o-folder',
        '📂' => 'heroicon-o-folder-open',
        '📑' => 'heroicon-o-document-text',
        '📝' => 'heroicon-o-pencil-square',
        
        // Communication & Notifications
        '📧' => 'heroicon-o-envelope',
        '📨' => 'heroicon-o-inbox',
        '📢' => 'heroicon-o-megaphone',
        '🔔' => 'heroicon-o-bell',
        '📞' => 'heroicon-o-phone',
        
        // Time & Calendar
        '⏰' => 'heroicon-o-clock',
        '📅' => 'heroicon-o-calendar-days',
        '⏱️' => 'heroicon-o-stopwatch',
        '⌚' => 'heroicon-o-clock',
        
        // Actions & Status
        '✅' => 'heroicon-o-check-circle',
        '❌' => 'heroicon-o-x-circle',
        '⚠️' => 'heroicon-o-exclamation-triangle',
        '🔄' => 'heroicon-o-arrow-path',
        '🚀' => 'heroicon-o-rocket-launch',
        '🎯' => 'heroicon-o-target',
        
        // Security & Protection
        '🛡️' => 'heroicon-o-shield-check',
        '🔒' => 'heroicon-o-lock-closed',
        '🔓' => 'heroicon-o-lock-open',
        '🔑' => 'heroicon-o-key',
        '👁️' => 'heroicon-o-eye',
        
        // Data & Analytics
        '📊' => 'heroicon-o-chart-bar-square',
        '📈' => 'heroicon-o-presentation-chart-line',
        '📉' => 'heroicon-o-arrow-trending-down',
        '🔍' => 'heroicon-o-magnifying-glass',
        '📱' => 'heroicon-o-device-phone-mobile',
        
        // Workflow & Process
        '🔀' => 'heroicon-o-arrows-right-left',
        '🔁' => 'heroicon-o-arrow-path-rounded-square',
        '⬆️' => 'heroicon-o-arrow-up',
        '⬇️' => 'heroicon-o-arrow-down',
        '➡️' => 'heroicon-o-arrow-right',
        
        // Quality & Validation
        '✔️' => 'heroicon-o-check',
        '❎' => 'heroicon-o-x-mark',
        '🔄' => 'heroicon-o-arrow-path',
        '🎯' => 'heroicon-o-cursor-arrow-rays',
        
        // Tools & Utilities
        '🛠️' => 'heroicon-o-wrench',
        '⚒️' => 'heroicon-o-hammer',
        '🧰' => 'heroicon-o-squares-2x2',
        '📦' => 'heroicon-o-archive-box',
        
        // Transport & Logistics
        '🚗' => 'heroicon-o-truck',
        '✈️' => 'heroicon-o-paper-airplane',  
        '🏃‍♂️' => 'heroicon-o-user',
        '🚶‍♂️' => 'heroicon-o-user',
    ];

    /**
     * Navigation group icon mapping with better semantic meaning
     */
    public static array $navigationGroupMapping = [
        '🏥 Manajemen Pasien' => ['icon' => 'heroicon-o-user-group', 'name' => 'Patient Management'],
        '💰 Manajemen Transaksi' => ['icon' => 'heroicon-o-currency-dollar', 'name' => 'Transaction Management'],
        '📊 Data Entry Harian' => ['icon' => 'heroicon-o-chart-bar-square', 'name' => 'Daily Data Entry'],
        '⚙️ SYSTEM ADMINISTRATION' => ['icon' => 'heroicon-o-cog-6-tooth', 'name' => 'System Administration'],
        '📍 PRESENSI' => ['icon' => 'heroicon-o-map-pin', 'name' => 'Attendance'],
        '👥 USER MANAGEMENT' => ['icon' => 'heroicon-o-user-group', 'name' => 'User Management'],
        '🔧 PENGATURAN' => ['icon' => 'heroicon-o-cog-6-tooth', 'name' => 'Settings'],
        '💰 FINANSIAL MANAGEMENT' => ['icon' => 'heroicon-o-currency-dollar', 'name' => 'Financial Management'],
        '📊 Laporan & Analitik' => ['icon' => 'heroicon-o-presentation-chart-line', 'name' => 'Reports & Analytics'],
        '🏥 Medical Records' => ['icon' => 'heroicon-o-clipboard-document-list', 'name' => 'Medical Records'],
        '📋 SCHEDULE MANAGEMENT' => ['icon' => 'heroicon-o-calendar-days', 'name' => 'Schedule Management'],
        '📊 GAMIFICATION MANAGEMENT' => ['icon' => 'heroicon-o-trophy', 'name' => 'Gamification Management'],
        '📋 Validasi Management' => ['icon' => 'heroicon-o-clipboard-document-check', 'name' => 'Validation Management'],
        '📊 Dashboard & Analytics' => ['icon' => 'heroicon-o-chart-bar-square', 'name' => 'Dashboard & Analytics'],
        '📊 Executive Overview' => ['icon' => 'heroicon-o-presentation-chart-bar', 'name' => 'Executive Overview'],
        '🏥 Operations Analytics' => ['icon' => 'heroicon-o-building-office-2', 'name' => 'Operations Analytics'],
        '👥 Personnel Management' => ['icon' => 'heroicon-o-user-group', 'name' => 'Personnel Management'],
        '💰 Financial Oversight' => ['icon' => 'heroicon-o-banknotes', 'name' => 'Financial Oversight'],
        '📊 Strategic Planning' => ['icon' => 'heroicon-o-light-bulb', 'name' => 'Strategic Planning'],
        '📋 Verifikasi Pasien' => ['icon' => 'heroicon-o-clipboard-document-check', 'name' => 'Patient Verification'],
        '📊 DASHBOARD' => ['icon' => 'heroicon-o-squares-2x2', 'name' => 'Dashboard'],
        '📊 Laporan & Analisis' => ['icon' => 'heroicon-o-document-chart-bar', 'name' => 'Reports & Analysis'],
    ];

    /**
     * Get Heroicon equivalent for emoji
     */
    public static function getHeroicon(string $emoji): string
    {
        return self::$iconMapping[$emoji] ?? 'heroicon-o-squares-2x2';
    }

    /**
     * Replace emoji in text with Heroicon name (for documentation/comments)
     */
    public static function replaceEmojiInText(string $text): string
    {
        foreach (self::$iconMapping as $emoji => $heroicon) {
            $text = str_replace($emoji, "[{$heroicon}]", $text);
        }
        return $text;
    }

    /**
     * Get navigation group info with proper icon
     */
    public static function getNavigationGroupInfo(string $emojiGroup): array
    {
        return self::$navigationGroupMapping[$emojiGroup] ?? [
            'icon' => 'heroicon-o-squares-2x2',
            'name' => str_replace(['🏥', '💰', '📊', '👥', '💉', '📋', '⚙️', '📍', '🔧', '📈', '📄'], '', $emojiGroup)
        ];
    }

    /**
     * Generate replacement file content for navigation groups
     */
    public static function generateCleanNavigationGroups(): array
    {
        $cleanGroups = [];
        
        foreach (self::$navigationGroupMapping as $emojiGroup => $info) {
            $cleanGroups[] = [
                'old' => $emojiGroup,
                'new' => $info['name'],
                'icon' => $info['icon']
            ];
        }
        
        return $cleanGroups;
    }
}