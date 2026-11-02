<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Services\InquiryService;
use Illuminate\Http\RedirectResponse;

/** Form "Kirim pertanyaan" di halaman Kontak. Logika di InquiryService. */
class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request, InquiryService $inquiries): RedirectResponse
    {
        $inquiries->submit($request->validated(), $request->user('customer'));

        return redirect()->to(route('kontak').'#kirim-pertanyaan')
            ->with('inquiry_sent', true);
    }
}
