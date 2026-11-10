<?php

namespace App\Mail;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Pemberitahuan ke tim resort: ada pesan baru dari form kontak. */
class InquiryReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pesan baru dari website: {$this->inquiry->topic->getLabel()} · {$this->inquiry->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.inquiry-received', with: [
            'url' => InquiryResource::getUrl('view', ['record' => $this->inquiry], panel: 'admin'),
        ]);
    }
}
