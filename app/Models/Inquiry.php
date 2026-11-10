<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryTopic;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pesan dari form kontak publik. Dibuat & diubah hanya lewat App\Services\InquiryService. */
#[Fillable([
    'name', 'phone', 'email', 'topic', 'visit_date', 'participants', 'message',
    'status', 'staff_note', 'customer_account_id', 'handled_by', 'handled_at',
])]
class Inquiry extends Model
{
    protected function casts(): array
    {
        return [
            'topic' => InquiryTopic::class,
            'status' => InquiryStatus::class,
            'visit_date' => 'date',
            'handled_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /** Nomor HP untuk wa.me (62...). */
    public function whatsAppNumber(): string
    {
        return preg_replace('/^0/', '62', preg_replace('/\D/', '', $this->phone));
    }
}
