<?php

namespace App\Http\Requests\Akun;

class PasswordUpdateRequest extends AkunRequest
{
    /** Error ditampilkan di form password saja (bukan form profil). */
    protected $errorBag = 'password';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:customer'],
            'password' => self::passwordRules(),
        ];
    }
}
