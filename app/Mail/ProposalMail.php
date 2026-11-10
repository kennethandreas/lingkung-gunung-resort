<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Proposal penawaran paket rombongan (PDF terlampir). */
class ProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Proposal {$this->quotation->number} · Paket Rombongan Lingkung Gunung");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.proposal', with: [
            'url' => route('proposal.show', $this->quotation),
        ]);
    }

    public function attachments(): array
    {
        if (! $this->quotation->pdf_path || ! Storage::disk('local')->exists($this->quotation->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->quotation->pdf_path)
                ->as("proposal-{$this->quotation->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
