<?php

namespace App\Http\Controllers\Akun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akun\ResetPasswordRequest;
use App\Models\CustomerAccount;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Lupa password & atur ulang password akun pelanggan (broker "customer_accounts"). */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('akun.auth.lupa-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'string', 'email']],
            ['email.required' => 'Email wajib diisi.', 'email.email' => 'Format email belum benar. Contoh: nama@email.com.'],
        );

        Password::broker('customer_accounts')->sendResetLink(['email' => mb_strtolower(trim($request->input('email')))]);

        // Pesan sama untuk email terdaftar/tidak, agar daftar pelanggan tidak bisa ditebak
        return back()->with('status', 'Bila email tersebut terdaftar, tautan atur ulang password sudah kami kirim. Periksa juga folder spam.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('akun.auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('customer_accounts')->reset(
            [
                'email' => mb_strtolower(trim($request->input('email'))),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function (CustomerAccount $account, string $password) {
                $account->forceFill(['password' => $password])->setRememberToken(Str::random(60));

                // Membuka tautan dari email = bukti memiliki email tersebut
                if (! $account->hasVerifiedEmail()) {
                    $account->email_verified_at = now();
                }

                $account->save();
                event(new PasswordReset($account));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan atur ulang password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.']);
        }

        return redirect()->route('akun.masuk')->with('status', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }
}
