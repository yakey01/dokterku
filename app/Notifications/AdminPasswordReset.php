<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class AdminPasswordReset extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);
        $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('🔐 Admin Password Reset Request - ' . config('app.name'))
            ->greeting('Halo Administrator!')
            ->line('Anda menerima email ini karena terdapat permintaan reset password untuk akun administrator Anda di sistem ' . config('app.name') . '.')
            ->line('Untuk keamanan, permintaan ini memerlukan verifikasi melalui email.')
            ->action('Reset Password Admin', $url)
            ->line("Link reset password ini akan kedaluarsa dalam {$count} menit untuk keamanan akun.")
            ->line('**Penting:** Jika Anda tidak meminta reset password, segera abaikan email ini dan hubungi tim IT untuk memverifikasi keamanan akun.')
            ->line('Setelah berhasil reset password, Anda dapat login kembali ke panel admin.')
            ->line('Untuk keamanan maksimal:')
            ->line('• Jangan bagikan link ini kepada siapapun')
            ->line('• Gunakan password yang kuat dan unik')
            ->line('• Logout dari semua device setelah reset password')
            ->salutation('Hormat kami,')
            ->salutation('Tim Keamanan ' . config('app.name'))
            ->with([
                'actionColor' => 'primary',
                'displayableActionUrl' => $url,
            ]);
    }

    protected function resetUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(Config::get('auth.passwords.'.Config::get('auth.defaults.passwords').'.expire', 60)),
            [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]
        );
    }
}