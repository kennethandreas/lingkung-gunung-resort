<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\InquiryTopic;
use App\Mail\InquiryReceivedMail;
use App\Models\CustomerAccount;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\ResortContact;
use Illuminate\Support\Facades\Mail;

/** Pesan dari form kontak: simpan, beri tahu tim resort, dan catat penanganan oleh staf. */
class InquiryService
{
    /**
     * @param  array{name: string, phone: string, email?: ?string, topic: string, visit_date?: ?string, participants?: ?int, message: string}  $data  sudah divalidasi
     */
    public function submit(array $data, ?CustomerAccount $account = null): Inquiry
    {
        $topic = InquiryTopic::from($data['topic']);

        $inquiry = Inquiry::query()->create([
            'name' => trim($data['name']),
            'phone' => preg_replace('/[^\d+]/', '', $data['phone']),
            'email' => ($data['email'] ?? null) ?: null,
            'topic' => $topic,
            // tanggal & jumlah peserta hanya relevan untuk rombongan/acara/aktivitas
            'visit_date' => ($data['visit_date'] ?? null) ?: null,
            'participants' => $topic->isGroup() ? (($data['participants'] ?? null) ?: null) : null,
            'message' => trim($data['message']),
            'status' => InquiryStatus::Baru,
            'customer_account_id' => $account?->id,
        ]);

        // Email pemberitahuan ke alamat resmi resort (Pengaturan Umum). Lewat antrean, setelah commit.
        if ($to = ResortContact::email()) {
            Mail::to($to)->queue(new InquiryReceivedMail($inquiry));
        }

        return $inquiry;
    }

    public function updateStatus(Inquiry $inquiry, InquiryStatus $status, User $staff, ?string $note = null): Inquiry
    {
        $inquiry->forceFill([
            'status' => $status,
            'handled_by' => $staff->id,
            'handled_at' => $inquiry->handled_at ?? now(),
            'staff_note' => $note ?? $inquiry->staff_note,
        ])->save();

        return $inquiry;
    }

    /** Dipanggil saat staf membuka chat WhatsApp: pesan baru otomatis jadi "Sedang dibalas". */
    public function markContacted(Inquiry $inquiry, User $staff): void
    {
        if ($inquiry->status === InquiryStatus::Baru) {
            $this->updateStatus($inquiry, InquiryStatus::Diproses, $staff);
        }
    }
}
