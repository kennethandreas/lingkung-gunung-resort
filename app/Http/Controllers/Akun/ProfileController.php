<?php

namespace App\Http\Controllers\Akun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akun\PasswordUpdateRequest;
use App\Http\Requests\Akun\ProfileUpdateRequest;
use App\Services\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Profil pelanggan: nama, WhatsApp, email (verifikasi ulang bila diganti), dan password. */
class ProfileController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function edit(Request $request): View
    {
        return view('akun.profil', ['account' => $request->user('customer')]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $emailChanged = $this->accounts->updateProfile($request->user('customer'), $request->validated());

        if ($emailChanged) {
            return redirect()->route('akun.verifikasi.notice')
                ->with('status', 'Profil disimpan. Email baru perlu diverifikasi: kami sudah mengirim tautan ke '.$request->user('customer')->email.'.');
        }

        return back()->with('status', 'Profil berhasil disimpan.');
    }

    public function password(PasswordUpdateRequest $request): RedirectResponse
    {
        $this->accounts->changePassword($request->user('customer'), $request->validated('password'));
        $request->session()->regenerate();

        return back()->with('status', 'Password berhasil diubah.');
    }
}
