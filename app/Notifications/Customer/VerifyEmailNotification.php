<?php

namespace App\Notifications\Customer;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/** Email verifikasi akun pelanggan (Bahasa Indonesia, tautan bertanda tangan 60 menit). */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi email akun Lingkung Gunung')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Terima kasih sudah mendaftar di Lingkung Gunung Adventure Camp. Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi email', $this->verificationUrl($notifiable))
            ->line('Tautan ini berlaku 60 menit. Abaikan email ini bila Anda tidak merasa mendaftar.')
            ->salutation("Salam hangat,\nLingkung Gunung Adventure Camp");
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'akun.verifikasi.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
        );
    }
}
