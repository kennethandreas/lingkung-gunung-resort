<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Services\QuotationService;
use App\Support\WhatsApp;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Halaman proposal publik (URL kode acak) + PDF. */
class ProposalController extends Controller
{
    public function show(Quotation $quotation): View
    {
        $quotation->load(['customer', 'items', 'variant.groupPackage']);

        $message = "Halo tim sales Lingkung Gunung, saya {$quotation->customer->name}"
            .($quotation->customer->organization ? " dari {$quotation->customer->organization}" : '')
            .". Saya ingin menindaklanjuti penawaran {$quotation->number} ({$quotation->variant?->name}, {$quotation->pax} peserta, "
            .$quotation->event_date->translatedFormat('d F Y').'). Proposal: '.route('proposal.show', $quotation);

        return view('public.proposal', [
            'quotation' => $quotation,
            'whatsappUrl' => WhatsApp::link($message),
        ]);
    }

    public function pdf(Quotation $quotation, QuotationService $quotations): Response
    {
        if (! $quotation->pdf_path || ! Storage::disk('local')->exists($quotation->pdf_path)) {
            $quotations->storePdf($quotation);
        }

        return response(Storage::disk('local')->get($quotation->pdf_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="proposal-'.$quotation->number.'.pdf"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
