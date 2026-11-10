<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuotationStatus;
use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Angka dashboard admin dari data asli (bukan contoh).
 * Istilah dibedakan dengan tegas supaya tidak tertukar:
 * - "Uang diterima"  = pembayaran berstatus Lunas/terverifikasi, dihitung per tanggal verifikasi (sama dengan Laporan).
 * - "Nilai booking"  = total harga booking terkonfirmasi (DP/lunas/check-in), dihitung per tanggal booking dibuat; BUKAN uang masuk.
 * - "Sisa tagihan"   = total - sudah dibayar untuk booking yang baru DP (piutang aktif).
 */
class DashboardService
{
    /** @return list<BookingStatus> */
    public static function confirmedStatuses(): array
    {
        return [BookingStatus::DpPaid, BookingStatus::FullyPaid, BookingStatus::CheckedIn];
    }

    public function received(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('verified_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('amount');
    }

    /** @return array{count: int, value: int, lost: int} booking dibuat pada periode */
    public function bookingsCreated(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from->startOfDay(), $to->endOfDay()];

        $confirmed = Booking::query()->whereBetween('created_at', $range)->whereIn('status', self::confirmedStatuses());

        return [
            'count' => (clone $confirmed)->count(),
            'value' => (int) (clone $confirmed)->sum('total'),
            'lost' => Booking::query()->whereBetween('created_at', $range)->whereIn('status', [BookingStatus::Expired, BookingStatus::Cancelled])->count(),
        ];
    }

    /** @return array{count: int, amount: int, overdue: int} */
    public function outstanding(): array
    {
        $awaiting = Booking::query()->where('status', BookingStatus::DpPaid);

        return [
            'count' => (clone $awaiting)->count(),
            'amount' => (int) (clone $awaiting)->selectRaw('COALESCE(SUM(total - paid_amount),0) as outstanding')->value('outstanding'),
            'overdue' => (clone $awaiting)->whereDate('due_date', '<', today())->count(),
        ];
    }

    /**
     * Tren harian (tanggal lokal) untuk grafik.
     *
     * @return array{labels: list<string>, received: list<int>, confirmed: list<int>, lost: list<int>}
     */
    public function dailyTrend(int $days): array
    {
        $days = max(7, min(90, $days));
        $from = CarbonImmutable::today()->subDays($days - 1);
        $to = CarbonImmutable::today()->endOfDay();

        $received = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('verified_at', [$from, $to])
            ->selectRaw('DATE(verified_at) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $bookings = Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, status, COUNT(*) as total')
            ->groupBy('d', 'status')
            ->get();

        $confirmedValues = array_map(fn (BookingStatus $s) => $s->value, self::confirmedStatuses());
        $trend = ['labels' => [], 'received' => [], 'confirmed' => [], 'lost' => []];

        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $key = $date->toDateString();
            $ofDay = $bookings->filter(fn ($row) => $row->d === $key);

            $trend['labels'][] = $date->translatedFormat('d M');
            $trend['received'][] = (int) ($received[$key] ?? 0);
            $trend['confirmed'][] = (int) $ofDay->filter(fn ($row) => in_array($this->statusValue($row->status), $confirmedValues, true))->sum('total');
            $trend['lost'][] = (int) $ofDay->filter(fn ($row) => in_array($this->statusValue($row->status), [BookingStatus::Expired->value, BookingStatus::Cancelled->value], true))->sum('total');
        }

        return $trend;
    }

    /**
     * Daftar hal yang perlu ditindaklanjuti staf, hanya yang jumlahnya > 0.
     *
     * @return list<array{key: string, label: string, hint: string, count: int, color: string, icon: string}>
     */
    public function attentionItems(): array
    {
        $outstanding = $this->outstanding();

        $items = [
            [
                'key' => 'payment_action',
                'label' => 'Pembayaran perlu tindakan',
                'hint' => 'Dibayar setelah stok habis: hubungi tamu / proses refund manual.',
                'count' => Payment::query()->where('note', 'like', 'PERLU TINDAKAN%')->count(),
                'color' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
            ],
            [
                'key' => 'overdue',
                'label' => 'Pelunasan lewat batas',
                'hint' => 'Booking DP yang melewati batas pelunasan (H-'.Setting::get('settlement_days_before', 3).').',
                'count' => $outstanding['overdue'],
                'color' => 'danger',
                'icon' => 'heroicon-o-calendar-days',
            ],
            [
                'key' => 'arrivals_unpaid',
                'label' => 'Tiba ≤ 3 hari, belum lunas',
                'hint' => 'Ingatkan tamu atau siapkan penagihan saat check-in.',
                'count' => Booking::query()->where('status', BookingStatus::DpPaid)->whereBetween('check_in', [today()->toDateString(), today()->addDays(3)->toDateString()])->count(),
                'color' => 'warning',
                'icon' => 'heroicon-o-banknotes',
            ],
            [
                'key' => 'holds',
                'label' => 'Menunggu pembayaran',
                'hint' => 'Booking online yang stoknya sedang ditahan.',
                'count' => Booking::query()->where('status', BookingStatus::Pending)->where('hold_expires_at', '>', now())->count(),
                'color' => 'info',
                'icon' => 'heroicon-o-clock',
            ],
            [
                'key' => 'quotations',
                'label' => 'Penawaran B2B baru',
                'hint' => 'Belum dihubungi tim sales.',
                'count' => Quotation::query()->where('status', QuotationStatus::Baru)->count(),
                'color' => 'info',
                'icon' => 'heroicon-o-document-currency-dollar',
            ],
            [
                'key' => 'inquiries',
                'label' => 'Pesan masuk baru',
                'hint' => 'Dari form kontak website.',
                'count' => Inquiry::query()->where('status', InquiryStatus::Baru)->count(),
                'color' => 'info',
                'icon' => 'heroicon-o-inbox-arrow-down',
            ],
            [
                'key' => 'reviews',
                'label' => 'Ulasan menunggu persetujuan',
                'hint' => 'Belum tampil di website sampai disetujui.',
                'count' => Review::query()->where('status', ReviewStatus::Pending)->count(),
                'color' => 'info',
                'icon' => 'heroicon-o-star',
            ],
        ];

        return array_values(array_filter($items, fn ($item) => $item['count'] > 0));
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof BookingStatus ? $status->value : (string) $status;
    }
}
