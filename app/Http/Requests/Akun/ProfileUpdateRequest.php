<?php

namespace App\Http\Requests\Akun;

use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends AkunRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => preg_replace('/[\s\-().]/', '', (string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['nullable', 'regex:/^(\+62|62|0)8\d{7,12}$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', Rule::unique('customer_accounts', 'email')->ignore($this->user('customer')->id)],
        ];
    }
}
