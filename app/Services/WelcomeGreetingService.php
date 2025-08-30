<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class WelcomeGreetingService
{
    /**
     * Get time-based greeting message
     */
    public static function getTimeBasedGreeting(): string
    {
        $hour = (int) date('H');
        
        return match (true) {
            $hour >= 5 && $hour < 12 => '🌅 Selamat pagi',
            $hour >= 12 && $hour < 15 => '☀️ Selamat siang', 
            $hour >= 15 && $hour < 18 => '🌤️ Selamat sore',
            $hour >= 18 || $hour < 5 => '🌙 Selamat malam',
            default => '👋 Selamat datang kembali'
        };
    }

    /**
     * Get personalized greeting with user name
     */
    public static function getPersonalizedGreeting(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return self::getTimeBasedGreeting() . '!';
        }
        
        $firstName = explode(' ', $user->name)[0];
        $greeting = self::getTimeBasedGreeting();
        
        return "{$greeting}, {$firstName}! ✨";
    }

    /**
     * Get role-based display name
     */
    public static function getRoleDisplayName(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        
        if (!$user || !$user->role) {
            return 'User';
        }
        
        return match ($user->role->name) {
            'admin' => 'Administrator Sistem',
            'manajer' => 'Manajer Klinik',
            'bendahara' => 'Bendahara Klinik',
            'dokter' => 'Dokter Klinik',
            'petugas' => 'Petugas Administrasi',
            'paramedis' => 'Tenaga Paramedis',
            'verifikator' => 'Verifikator Data',
            default => ucfirst($user->role->name)
        };
    }

    /**
     * Get motivational message based on role and time
     */
    public static function getMotivationalMessage(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        $role = $user?->role?->name ?? 'user';
        
        $messages = [
            'admin' => [
                "Kelola sistem dengan bijaksana untuk kemajuan klinik! 🎯",
                "Setiap keputusan Anda membentuk masa depan yang lebih baik! ⭐",
                "Leadership yang baik dimulai dari teladan yang baik! 👑",
                "Mari ciptakan lingkungan kerja yang produktif! 🚀"
            ],
            'manajer' => [
                "Visi dan misi klinik ada di tangan Anda! 🎯",
                "Kepemimpinan yang baik menghasilkan tim yang solid! 💪",
                "Mari wujudkan pelayanan kesehatan terbaik! 🏥",
                "Setiap strategi yang baik dimulai dari hari ini! ⭐"
            ],
            'bendahara' => [
                "Kelola keuangan dengan amanah dan transparansi! 💰",
                "Setiap angka yang akurat adalah bentuk kepercayaan! 📊",
                "Mari jaga kesehatan finansial klinik bersama-sama! 💙",
                "Integritas dalam pengelolaan keuangan adalah kunci! 🔑"
            ],
            'dokter' => [
                "Setiap pasien yang disembuhkan adalah kebahagiaan! 👨‍⚕️",
                "Dedikasi Anda memberikan harapan bagi banyak orang! 💙",
                "Mari berikan pelayanan medis terbaik hari ini! 🏥",
                "Ilmu dan pengalaman Anda sangat berharga! 📚"
            ],
            'petugas' => [
                "Pelayanan prima dimulai dari senyuman Anda! 😊",
                "Setiap pasien yang dilayani adalah amanah! 💙",
                "Mari berikan pengalaman terbaik untuk setiap pengunjung! ⭐",
                "Kedisiplinan dan ketelitian adalah kunci sukses! 🎯"
            ],
            'paramedis' => [
                "Kepedulian Anda membuat perbedaan nyata! 💚",
                "Setiap tindakan medis adalah bentuk kasih sayang! 💙",
                "Mari jaga kesehatan dan keselamatan bersama! 🏥",
                "Profesionalisme dan empati adalah kekuatan Anda! ⭐"
            ],
            'default' => [
                "Semoga hari ini penuh berkah dan produktivitas! 💪",
                "Mari berikan yang terbaik dalam setiap tugas! 🌟",
                "Kesuksesan dimulai dari langkah kecil hari ini! ⭐",
                "Tetap semangat dan jaga kesehatan! 🌟",
                "Setiap kontribusi Anda sangat berarti! 💙"
            ]
        ];

        $roleMessages = $messages[$role] ?? $messages['default'];
        return $roleMessages[array_rand($roleMessages)];
    }

    /**
     * Get formatted current date in Indonesian
     */
    public static function getCurrentDateIndonesian(): string
    {
        return Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y');
    }

    /**
     * Get user initials for avatar
     */
    public static function getUserInitials(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return 'U';
        }
        
        $names = explode(' ', trim($user->name));
        
        if (count($names) >= 2) {
            return strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
        }
        
        return strtoupper(substr($user->name, 0, 1));
    }

    /**
     * Get complete welcome data for frontend components
     */
    public static function getWelcomeData(?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        return [
            'greeting' => self::getPersonalizedGreeting($user),
            'time_greeting' => self::getTimeBasedGreeting(),
            'role_display' => self::getRoleDisplayName($user),
            'motivational_message' => self::getMotivationalMessage($user),
            'current_date' => self::getCurrentDateIndonesian(),
            'current_time' => date('H:i'),
            'user_initials' => self::getUserInitials($user),
            'first_name' => $user ? explode(' ', $user->name)[0] : 'User',
            'is_online' => true, // Can be extended with actual online status logic
            'panel_name' => self::getPanelDisplayName()
        ];
    }

    /**
     * Get current panel display name
     */
    public static function getPanelDisplayName(): string
    {
        $panelId = request()->route()?->getPrefix() ?? '';
        
        return match ($panelId) {
            '/admin' => 'Panel Administrator',
            '/manajer' => 'Dashboard Manajer',
            '/bendahara' => 'Dashboard Bendahara',
            '/dokter' => 'Portal Dokter',
            '/petugas' => 'Dashboard Petugas',
            '/paramedis' => 'Portal Paramedis',
            '/verifikator' => 'Panel Verifikator',
            default => 'Dashboard Klinik'
        };
    }

    /**
     * Check if user should see special announcements
     */
    public static function hasSpecialAnnouncement(?User $user = null): bool
    {
        // Can be extended with actual announcement logic
        // For now, show special message on weekends
        return Carbon::now()->isWeekend();
    }

    /**
     * Get special announcement message
     */
    public static function getSpecialAnnouncement(): ?string
    {
        if (!self::hasSpecialAnnouncement()) {
            return null;
        }
        
        $weekendMessages = [
            "🌟 Selamat akhir pekan! Tetap jaga kesehatan dan semangat!",
            "🎉 Weekend yang produktif adalah weekend yang bermakna!",
            "💙 Terima kasih atas dedikasi Anda di akhir pekan ini!",
            "⭐ Pelayanan kesehatan tidak mengenal hari libur!"
        ];
        
        return $weekendMessages[array_rand($weekendMessages)];
    }
}