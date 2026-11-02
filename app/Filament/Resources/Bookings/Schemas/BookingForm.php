<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Exceptions\BookingException;
use App\Models\Setting;
use App\Models\UnitType;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Form input reservasi manual (walk-in / telepon).
 * Harga yang tampil hanya pratinjau; angka final dihitung ulang oleh BookingService.
 */
class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Tanggal & Akomodasi')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        DatePicker::make('check_in')
                            ->label('Check-in')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live(),
                        DatePicker::make('check_out')
                            ->label('Check-out')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('check_in')
                            ->live(),
                        TextInput::make('guests')
                            ->label('Jumlah tamu')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->suffix('orang'),
                        Repeater::make('lines')
                            ->label('Akomodasi')
                            ->addActionLabel('Tambah akomodasi')
                            ->columnSpanFull()
                            ->columns(3)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->live()
                            ->schema([
                                Select::make('unit_type_id')
                                    ->label('Tipe unit')
                                    ->options(fn (Get $get) => self::unitTypeOptions($get('../../check_in'), $get('../../check_out')))
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),
                                TextInput::make('qty')
                                    ->label('Jumlah (unit / orang)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true),
                            ]),
                    ]),
                Section::make('Ringkasan Harga')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('price_preview')
                            ->hiddenLabel()
                            ->state(fn (Get $get) => self::pricePreview($get)),
                        Radio::make('payment_option')
                            ->label('Rencana pembayaran')
                            ->options(fn () => ['dp' => 'DP '.Setting::get('dp_percent').'% dulu', 'full' => 'Langsung lunas'])
                            ->default('dp')
                            ->required(),
                        TextInput::make('promo_code')->label('Kode promo')->maxLength(30)->live(onBlur: true),
                    ]),
                Section::make('Data Pemesan')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer.name')->label('Nama')->required()->maxLength(255),
                        TextInput::make('customer.phone')->label('No. HP / WhatsApp')->tel()->required()->maxLength(30),
                        TextInput::make('customer.email')->label('Email')->email()->maxLength(255),
                        TextInput::make('customer.organization')->label('Instansi')->maxLength(255),
                    ]),
                Section::make('Catatan')
                    ->columnSpan(1)
                    ->schema([
                        Textarea::make('notes')->label('Permintaan tamu')->rows(2),
                        Textarea::make('internal_notes')->label('Catatan internal')->rows(2),
                    ]),
            ]);
    }

    /** Daftar tipe unit beserta sisa stok bila tanggal sudah diisi. */
    private static function unitTypeOptions(?string $checkIn, ?string $checkOut): array
    {
        if (! $checkIn || ! $checkOut || $checkOut <= $checkIn) {
            return UnitType::query()->active()->ordered()->pluck('marketing_name', 'id')->all();
        }

        try {
            return app(BookingService::class)
                ->availability(CarbonImmutable::parse($checkIn), CarbonImmutable::parse($checkOut))
                ->mapWithKeys(fn ($row) => [$row['unit_type']->id => "{$row['unit_type']->marketing_name} (sisa {$row['available']})"])
                ->all();
        } catch (BookingException) {
            return UnitType::query()->active()->ordered()->pluck('marketing_name', 'id')->all();
        }
    }

    private static function pricePreview(Get $get): HtmlString|string
    {
        $checkIn = $get('check_in');
        $checkOut = $get('check_out');
        $lines = collect($get('lines') ?? [])
            ->filter(fn ($line) => filled($line['unit_type_id'] ?? null) && (int) ($line['qty'] ?? 0) > 0)
            ->groupBy('unit_type_id')
            ->map(fn ($group) => $group->sum(fn ($line) => (int) $line['qty']))
            ->all();

        if (! $checkIn || ! $checkOut || $checkOut <= $checkIn || $lines === []) {
            return 'Isi tanggal dan akomodasi untuk melihat harga.';
        }

        try {
            $quote = app(PricingService::class)->quoteBooking($lines, CarbonImmutable::parse($checkIn), CarbonImmutable::parse($checkOut), $get('promo_code') ?: null);
        } catch (BookingException $e) {
            return $e->getMessage();
        }

        $rows = collect($quote['items'])->map(fn ($item) => sprintf(
            '<div class="flex justify-between gap-2"><span>%s × %d</span><span>%s</span></div>',
            e($item['unit_type']->marketing_name),
            $item['qty'],
            PaymentService::rupiah($item['subtotal']),
        ))->implode('');

        $discount = $quote['discount'] > 0
            ? '<div class="flex justify-between gap-2 text-success-600"><span>Diskon</span><span>−'.PaymentService::rupiah($quote['discount']).'</span></div>'
            : '';

        return new HtmlString(
            "<div class=\"space-y-1 text-sm\">{$rows}{$discount}"
            .'<div class="flex justify-between gap-2 border-t pt-1 font-bold"><span>Total ('.$quote['nights'].' malam)</span><span>'.PaymentService::rupiah($quote['total']).'</span></div>'
            .'<div class="flex justify-between gap-2 text-gray-500"><span>DP '.Setting::get('dp_percent').'%</span><span>'.PaymentService::rupiah($quote['dp_amount']).'</span></div></div>'
        );
    }
}
