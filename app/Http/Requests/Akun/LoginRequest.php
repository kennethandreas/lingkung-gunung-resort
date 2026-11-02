<?php

namespace App\Http\Requests\Akun;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Login pelanggan: maks. 5 percobaan gagal per email+IP per menit. */
class LoginRequest extends AkunRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::guard('customer')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam '.RateLimiter::availableIn($this->throttleKey()).' detik.',
        ]);
    }

    private function throttleKey(): string
    {
        return 'akun-login:'.Str::transliterate(Str::lower((string) $this->input('email'))).'|'.$this->ip();
    }
}
