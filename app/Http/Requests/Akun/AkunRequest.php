<?php

namespace App\Http\Requests\Akun;

use Illuminate\Foundation\Http\FormRequest;

/** Dasar form akun pelanggan: pesan validasi Bahasa Indonesia yang ramah. */
abstract class AkunRequest extends FormRequest
{
    /** Aturan password baru: minimal 8 karakter, ada huruf dan angka. */
    public static function passwordRules(): array
    {
        return ['required', 'string', 'min:8', 'max:100', 'regex:/[A-Za-z]/', 'regex:/\d/', 'confirmed'];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama sepertinya terlalu pendek.',
            'name.max' => 'Nama terlalu panjang (maks. 100 karakter).',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar. Contoh: nama@email.com.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan "Lupa password".',
            'email.max' => 'Email terlalu panjang.',
            'phone.regex' => 'Nomor WhatsApp sepertinya belum benar. Contoh: 0812 3456 7890.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password terlalu panjang.',
            'password.regex' => 'Password harus berisi huruf dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'current_password.required' => 'Masukkan password Anda saat ini.',
            'current_password.current_password' => 'Password saat ini salah.',
            'token.required' => 'Tautan atur ulang password tidak valid.',
        ];
    }
}
