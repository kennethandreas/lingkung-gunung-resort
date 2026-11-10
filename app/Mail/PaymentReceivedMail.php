<?php

namespace App\Mail;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\ETicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Bukti pembayaran + e-ticket (PDF terlampir). */
class PaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public Payment $payment) {}

    public function envelope(): Envelope
    {
        $status = $this->booking->status === BookingStatus::FullyPaid ? 'Lunas' : 'DP diterima';

        return new Envelope(subject: "E-ticket {$this->booking->code} · {$status} · Lingkung Gunung");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.payment-received', with: [
            'url' => route('booking.show', $this->booking),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => ETicket::pdf($this->booking->fresh(['customer', 'items'])), ETicket::filename($this->booking))
                ->withMime('application/pdf'),
        ];
    }
}
