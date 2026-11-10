<?php

namespace App\Http\Controllers\Akun;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Verifikasi email akun pelanggan. */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user('customer')->hasVerifiedEmail()) {
            return redirect()->route('akun.dashboard');
        }

        return view('akun.auth.verifikasi');
    }

    /**
     * Tautan dari email (bertanda tangan & berbatas waktu, dicek middleware "signed").
     * Tidak wajib login, agar tautan bisa dibuka dari perangkat lain.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $account = CustomerAccount::query()->findOrFail($id);

        abort_unless(hash_equals(sha1($account->getEmailForVerification()), $hash), 403);

        if (! $account->hasVerifiedEmail() && $account->markEmailAsVerified()) {
            event(new Verified($account));
        }

        if (Auth::guard('customer')->id() === $account->id) {
            return redirect()->intended(route('akun.dashboard'))->with('status', 'Email berhasil diverifikasi. Selamat datang!');
        }

        return redirect()->route('akun.masuk')->with('status', 'Email berhasil diverifikasi. Silakan masuk.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $account = $request->user('customer');

        if ($account->hasVerifiedEmail()) {
            return redirect()->route('akun.dashboard');
        }

        $account->sendEmailVerificationNotification();

        return back()->with('status', 'Tautan verifikasi baru sudah dikirim ke '.$account->email.'.');
    }
}
