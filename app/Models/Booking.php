<?php

namespace App\Models;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Header pemesanan. Status dan stok hanya diubah lewat
 * App\Services\BookingService dan App\Services\PaymentService.
 */
#[Fillable([
    'code', 'customer_id', 'customer_account_id', 'channel', 'status', 'payment_option', 'check_in', 'check_out',
    'nights', 'guests', 'subtotal', 'discount', 'total', 'dp_amount', 'paid_amount', 'promo_id',
    'due_date', 'hold_expires_at', 'confirmed_at', 'fully_paid_at', 'checked_in_at',
    'cancelled_at', 'expired_at', 'cancel_reason', 'notes', 'internal_notes', 'created_by',
])]
class Booking extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return [
            'channel' => BookingChannel::class,
            'status' => BookingStatus::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'due_date' => 'date',
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'fully_paid_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Akun pelanggan pemilik booking (kosong untuk booking tamu / manual / B2B). */
    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function paidPayments(): HasMany
    {
        return $this->payments()->where('status', PaymentStatus::Paid);
    }

    public function outstanding(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    /** Status pembayaran untuk ditampilkan ke tamu (dihitung dari pembayaran terverifikasi). */
    public function paymentStatusLabel(): string
    {
        return match (true) {
            $this->total > 0 && $this->paid_amount >= $this->total => 'Lunas',
            $this->paid_amount > 0 => 'DP dibayar',
            default => 'Belum dibayar',
        };
    }

    /** Nama akomodasi / paket yang dipesan, mis. "Glamping Imah Lembah × 2". */
    public function accommodationSummary(): string
    {
        $items = $this->items->whereIn('item_type', [BookingItem::TYPE_UNIT_TYPE, BookingItem::TYPE_PACKAGE]);

        return $items->map(fn (BookingItem $item) => $item->description.($item->qty > 1 ? ' × '.$item->qty : ''))->implode(', ') ?: '-';
    }
}
