<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Pengingat pelunasan (H-7 dan H-4) berisi link pembayaran. */
class SettlementReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pengingat pelunasan booking {$this->booking->code} · Lingkung Gunung");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.settlement-reminder', with: [
            'url' => route('booking.show', $this->booking),
        ]);
    }
}
