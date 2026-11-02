<?php

namespace App\Services;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PricingUnit;
use App\Models\Booking;
use App\Models\DailyInventory;
use App\Models\Payment;
use App\Models\UnitType;
use Carbon\CarbonImmutable;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Laporan admin: pendapatan, okupansi, sumber booking (dokumen teknis 4.4), plus ekspor Excel.
 * Hanya membaca data; tidak mengubah apa pun.
 */
class ReportService
{
    private const CONFIRMED = [BookingStatus::DpPaid, BookingStatus::FullyPaid, BookingStatus::CheckedIn];

    /**
     * @return array{period: array, revenue: array, bookings: array, occupancy: array}
     */
    public function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        [$from, $to] = [$from->startOfDay(), $to->endOfDay()];

        return [
            'period' => ['from' => $from, 'to' => $to, 'days' => (int) $from->diffInDays($to) + 1],
            'revenue' => $this->revenue($from, $to),
            'bookings' => $this->bookings($from, $to),
            'occupancy' => $this->occupancy($from, $to),
        ];
    }

    /** Pendapatan = pembayaran yang terverifikasi di dalam periode (basis kas). */
    public function revenue(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payments = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('verified_at', [$from, $to])
            ->get(['amount', 'method', 'type', 'verified_at']);

        $byMethod = collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [
            $m->getLabel() => (int) $payments->where('method', $m)->sum('amount'),
        ])->all();

        $byType = collect(PaymentType::cases())->mapWithKeys(fn (PaymentType $t) => [
            $t->getLabel() => (int) $payments->where('type', $t)->sum('amount'),
        ])->all();

        $daily = $payments->groupBy(fn (Payment $p) => $p->verified_at->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'))
            ->sortKeys()
            ->all();

        return [
            'total' => (int) $payments->sum('amount'),
            'count' => $payments->count(),
            'by_method' => $byMethod,
            'by_type' => $byType,
            'daily' => $daily,
        ];
    }

    /** Booking yang DIBUAT di dalam periode: status, sumber (channel), nilai. */
    public function bookings(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $bookings = Booking::query()->whereBetween('created_at', [$from, $to])->get(['status', 'channel', 'total', 'nights', 'check_in', 'created_at']);
        $confirmed = $bookings->filter(fn (Booking $b) => in_array($b->status, self::CONFIRMED, true));

        $byChannel = collect(BookingChannel::cases())->mapWithKeys(fn (BookingChannel $c) => [
            $c->getLabel() => [
                'count' => $bookings->where('channel', $c)->count(),
                'confirmed' => $confirmed->where('channel', $c)->count(),
                'value' => (int) $confirmed->where('channel', $c)->sum('total'),
            ],
        ])->all();

        $byStatus = collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $s) => [
            $s->getLabel() => $bookings->where('status', $s)->count(),
        ])->all();

        return [
            'count' => $bookings->count(),
            'confirmed' => $confirmed->count(),
            'value' => (int) $confirmed->sum('total'),
            'conversion' => $bookings->count() ? round($confirmed->count() / $bookings->count() * 100, 1) : 0.0,
            'average_nights' => $confirmed->count() ? round($confirmed->avg('nights'), 1) : 0.0,
            'by_channel' => $byChannel,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Okupansi per tipe unit = malam terjual (booked) / kapasitas pada tanggal di dalam periode.
     * Tipe "per orang" (camp site, campervan) dihitung terpisah dari total okupansi kamar/tenda.
     */
    public function occupancy(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = DailyInventory::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('unit_type_id, COUNT(*) as days, SUM(capacity) as capacity, SUM(booked) as booked, SUM(CASE WHEN is_blocked THEN capacity ELSE 0 END) as blocked')
            ->groupBy('unit_type_id')
            ->get()
            ->keyBy('unit_type_id');

        $days = (int) $from->startOfDay()->diffInDays($to->startOfDay()) + 1;

        $types = UnitType::query()->ordered()->get()->map(function (UnitType $type) use ($rows, $days) {
            $row = $rows->get($type->id);
            // Tanggal yang belum punya baris stok dihitung dengan jumlah unit standar (belum ada yang terjual)
            $missingDays = max(0, $days - (int) ($row->days ?? 0));
            $capacity = (int) ($row->capacity ?? 0) + $missingDays * (int) $type->total_units;
            $booked = (int) ($row->booked ?? 0);

            return [
                'id' => $type->id,
                'name' => $type->marketing_name,
                'per_person' => $type->pricing_unit === PricingUnit::Person,
                'capacity' => $capacity,
                'booked' => $booked,
                'blocked' => (int) ($row->blocked ?? 0),
                'rate' => $capacity > 0 ? round($booked / $capacity * 100, 1) : 0.0,
            ];
        });

        $units = $types->reject(fn ($t) => $t['per_person']);
        $capacity = $units->sum('capacity');

        return [
            'types' => $types->values()->all(),
            'overall' => $capacity > 0 ? round($units->sum('booked') / $capacity * 100, 1) : 0.0,
            'nights_sold' => (int) $units->sum('booked'),
        ];
    }

    /** Buat file Excel laporan dan kembalikan path sementaranya. */
    public function exportXlsx(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $summary = $this->summary($from, $to);
        [$from, $to] = [$summary['period']['from'], $summary['period']['to']];
        $path = storage_path('app/private/laporan-'.$from->format('Ymd').'-'.$to->format('Ymd').'-'.now()->format('His').'.xlsx');
        $bold = (new Style)->setFontBold();

        $writer = new Writer;
        $writer->openToFile($path);

        // Sheet 1: Ringkasan
        $writer->getCurrentSheet()->setName('Ringkasan');
        $writer->addRows([
            Row::fromValues(['Laporan Lingkung Gunung'], $bold),
            Row::fromValues(['Periode', $from->format('d/m/Y').' - '.$to->format('d/m/Y')]),
            Row::fromValues([]),
            Row::fromValues(['Pendapatan (pembayaran terverifikasi)', $summary['revenue']['total']], $bold),
            ...collect($summary['revenue']['by_method'])->map(fn ($v, $k) => Row::fromValues(['  '.$k, $v]))->values()->all(),
            ...collect($summary['revenue']['by_type'])->map(fn ($v, $k) => Row::fromValues(['  '.$k, $v]))->values()->all(),
            Row::fromValues([]),
            Row::fromValues(['Booking dibuat', $summary['bookings']['count']], $bold),
            Row::fromValues(['Booking terkonfirmasi', $summary['bookings']['confirmed']]),
            Row::fromValues(['Nilai booking terkonfirmasi', $summary['bookings']['value']]),
            Row::fromValues(['Konversi (%)', $summary['bookings']['conversion']]),
            Row::fromValues([]),
            Row::fromValues(['Sumber booking', 'Jumlah', 'Terkonfirmasi', 'Nilai'], $bold),
            ...collect($summary['bookings']['by_channel'])->map(fn ($v, $k) => Row::fromValues([$k, $v['count'], $v['confirmed'], $v['value']]))->values()->all(),
            Row::fromValues([]),
            Row::fromValues(['Okupansi kamar/tenda (%)', $summary['occupancy']['overall']], $bold),
        ]);

        // Sheet 2: Pembayaran
        $writer->addNewSheetAndMakeItCurrent()->setName('Pembayaran');
        $writer->addRow(Row::fromValues(['Waktu verifikasi', 'Kode booking', 'Pemesan', 'Jenis', 'Metode', 'Kanal', 'Nominal'], $bold));
        Payment::query()->with('booking.customer')->where('status', PaymentStatus::Paid)->whereBetween('verified_at', [$from, $to])->orderBy('verified_at')
            ->each(fn (Payment $p) => $writer->addRow(Row::fromValues([
                $p->verified_at->format('d/m/Y H:i'), $p->booking->code, $p->booking->customer->name,
                $p->type->getLabel(), $p->method->getLabel(), (string) $p->payment_channel, $p->amount,
            ])));

        // Sheet 3: Booking
        $writer->addNewSheetAndMakeItCurrent()->setName('Booking');
        $writer->addRow(Row::fromValues(['Dibuat', 'Kode', 'Pemesan', 'Instansi', 'Sumber', 'Status', 'Check-in', 'Malam', 'Tamu', 'Total', 'Dibayar'], $bold));
        Booking::query()->with('customer')->whereBetween('created_at', [$from, $to])->orderBy('created_at')
            ->each(fn (Booking $b) => $writer->addRow(Row::fromValues([
                $b->created_at->format('d/m/Y H:i'), $b->code, $b->customer->name, (string) $b->customer->organization,
                $b->channel->getLabel(), $b->status->getLabel(), $b->check_in->format('d/m/Y'), $b->nights, $b->guests, $b->total, $b->paid_amount,
            ])));

        // Sheet 4: Okupansi
        $writer->addNewSheetAndMakeItCurrent()->setName('Okupansi');
        $writer->addRow(Row::fromValues(['Tipe unit', 'Satuan', 'Kapasitas (unit-malam)', 'Terjual', 'Diblokir', 'Okupansi (%)'], $bold));
        foreach ($summary['occupancy']['types'] as $type) {
            $writer->addRow(Row::fromValues([$type['name'], $type['per_person'] ? 'per orang' : 'per unit', $type['capacity'], $type['booked'], $type['blocked'], $type['rate']]));
        }

        $writer->close();

        return $path;
    }
}
