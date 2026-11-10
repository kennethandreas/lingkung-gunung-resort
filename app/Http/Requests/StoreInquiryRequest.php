<?php

namespace App\Http\Requests;

use App\Enums\InquiryTopic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Form "Kirim pertanyaan" di halaman Kontak. Pesan validasi Bahasa Indonesia. */
class StoreInquiryRequest extends FormRequest
{
    /** Ke anchor form saat validasi gagal, supaya tamu langsung melihat pesannya. */
    protected function getRedirectUrl(): string
    {
        return route('kontak').'#kirim-pertanyaan';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))) ?: null,
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[\d\s\-]{7,14}$/'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'topic' => ['required', Rule::enum(InquiryTopic::class)],
            'visit_date' => ['nullable', 'date', 'after_or_equal:today', 'before:'.now()->addYears(2)->toDateString()],
            'participants' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'consent' => ['accepted'],
            // honeypot: diisi bot, kosong untuk manusia
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.min' => 'Nama sepertinya terlalu pendek.',
            'name.max' => 'Nama terlalu panjang (maks. 100 karakter).',
            'phone.required' => 'Nomor WhatsApp wajib diisi supaya tim kami bisa membalas.',
            'phone.regex' => 'Nomor WhatsApp sepertinya belum benar. Contoh: 0812 3456 7890.',
            'email.email' => 'Format email belum benar. Contoh: nama@email.com.',
            'email.max' => 'Email terlalu panjang.',
            'topic.required' => 'Pilih topik pertanyaan.',
            'topic.enum' => 'Pilih topik pertanyaan dari daftar.',
            'visit_date.date' => 'Tanggal belum benar.',
            'visit_date.after_or_equal' => 'Rencana tanggal tidak boleh di masa lalu.',
            'visit_date.before' => 'Rencana tanggal maksimal 2 tahun ke depan.',
            'participants.integer' => 'Jumlah peserta harus berupa angka.',
            'participants.min' => 'Jumlah peserta minimal 1 orang.',
            'participants.max' => 'Untuk lebih dari 5.000 peserta, hubungi kami via WhatsApp.',
            'message.required' => 'Tulis pertanyaan Anda.',
            'message.min' => 'Pertanyaan terlalu singkat (minimal 10 karakter).',
            'message.max' => 'Pertanyaan terlalu panjang (maks. 2.000 karakter).',
            'consent.accepted' => 'Centang persetujuan agar kami boleh menghubungi Anda.',
            'website.prohibited' => 'Pesan tidak dapat dikirim.',
        ];
    }
}
