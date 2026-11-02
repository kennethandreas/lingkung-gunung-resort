<?php

namespace App\Http\Requests\Akun;

class RegisterRequest extends AkunRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', 'unique:customer_accounts,email'],
            'password' => self::passwordRules(),
        ];
    }
}
