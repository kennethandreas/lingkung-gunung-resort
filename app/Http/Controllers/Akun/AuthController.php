<?php

namespace App\Http\Controllers\Akun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akun\LoginRequest;
use App\Http\Requests\Akun\RegisterRequest;
use App\Models\CustomerAccount;
use App\Services\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Daftar, masuk, dan keluar akun pelanggan (guard "customer"). */
class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('akun.auth.daftar');
    }

    public function register(RegisterRequest $request, CustomerAccountService $accounts): RedirectResponse
    {
        // Honeypot anti-bot: kolom tersembunyi harus kosong
        if (filled($request->input('website'))) {
            return redirect()->route('akun.masuk');
        }

        $account = $accounts->register($request->validated());

        Auth::guard('customer')->login($account);
        $request->session()->regenerate();

        if (! CustomerAccount::verificationRequired()) {
            // Kembali ke halaman yang dituju sebelum diminta daftar (mis. wizard booking)
            return redirect()->intended(route('akun.dashboard'))->with('status', 'Akun berhasil dibuat. Selamat datang di Lingkung Gunung!');
        }

        return redirect()->route('akun.verifikasi.notice')
            ->with('status', 'Akun berhasil dibuat. Kami sudah mengirim tautan verifikasi ke '.$account->email.'.');
    }

    public function showLogin(): View
    {
        return view('akun.auth.masuk');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('akun.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda sudah keluar dari akun.');
    }
}
