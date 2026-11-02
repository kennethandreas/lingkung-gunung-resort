<?php

namespace App\Http\Requests\Akun;

class ResetPasswordRequest extends AkunRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => self::passwordRules(),
        ];
    }
}
