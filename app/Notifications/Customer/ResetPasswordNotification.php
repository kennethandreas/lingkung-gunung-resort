<?php

namespace App\Notifications\Customer;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Email atur ulang password akun pelanggan (Bahasa Indonesia). */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    protected function resetUrl($notifiable): string
    {
        return route('akun.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.customer_accounts.expire', 60);

        return (new MailMessage)
            ->subject('Atur ulang password akun Lingkung Gunung')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda.')
            ->action('Atur ulang password', $url)
            ->line("Tautan ini berlaku {$minutes} menit.")
            ->line('Bila Anda tidak meminta ini, abaikan email ini. Password Anda tidak berubah.')
            ->salutation("Salam hangat,\nLingkung Gunung Adventure Camp");
    }
}
