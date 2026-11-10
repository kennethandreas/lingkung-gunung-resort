<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Quotation;
use App\Services\QuotationService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Penawaran')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('number')->label('Nomor')->copyable()->weight('bold')->size('lg'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('booking.code')
                            ->label('Booking')
                            ->placeholder('Belum dikonversi')
                            ->url(fn (Quotation $record) => $record->booking ? BookingResource::getUrl('view', ['record' => $record->booking]) : null)
                            ->color('primary'),
                        TextEntry::make('variant.name')->label('Paket'),
                        TextEntry::make('event_date')->label('Tanggal')->date('l, d M Y'),
                        TextEntry::make('nights')->label('Lama')->formatStateUsing(fn ($state) => ($state + 1)." hari {$state} malam"),
                        TextEntry::make('pax')->label('Peserta')->suffix(' orang'),
                        TextEntry::make('total')->label('Estimasi total')->money('IDR', locale: 'id', decimalPlaces: 0)->weight('bold'),
                        TextEntry::make('handler.name')->label('Ditangani')->placeholder('-'),
                        TextEntry::make('notes')->label('Catatan klien')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Klien')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('customer.organization')->label('Instansi')->placeholder('-'),
                        TextEntry::make('customer.name')->label('Penanggung jawab'),
                        TextEntry::make('customer.phone')
                            ->label('WhatsApp')
                            ->copyable()
                            ->url(fn (Quotation $record) => 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $record->customer->phone)), shouldOpenInNewTab: true),
                        TextEntry::make('customer.email')->label('Email')->placeholder('-'),
                        TextEntry::make('created_at')->label('Masuk')->dateTime('d M Y H:i'),
                    ]),
                Section::make('Rencana menu')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('menu_plan')
                            ->hiddenLabel()
                            ->state(fn (Quotation $record) => self::menuTable($record)),
                    ]),
            ]);
    }

    private static function menuTable(Quotation $record): HtmlString|string
    {
        if (! $record->variant || empty($record->menu_plan)) {
            return '-';
        }

        $options = app(QuotationService::class)->menuOptions($record->variant->loadMissing('menuSet.options'));
        $meals = ['breakfast' => 'Sarapan', 'lunch' => 'Makan siang', 'dinner' => 'Makan malam'];
        $rows = '';

        foreach ($record->menu_plan as $day) {
            $cells = '';
            foreach ($meals as $meal => $label) {
                $option = $options->get($meal)?->firstWhere('option_code', $day[$meal] ?? null);
                $cells .= '<td class="px-3 py-2 align-top">'.($option ? '<strong>Opsi '.e($option->option_code).'</strong><br><span class="text-gray-500">'.e(implode(', ', $option->items)).'</span>' : '-').'</td>';
            }
            $coffee = collect($day['coffee_breaks'] ?? [])->map(fn ($p, $i) => ($i + 1).'. '.e(implode(', ', $p)))->implode('<br>') ?: '-';
            $rows .= '<tr class="border-t border-gray-200 dark:border-white/10"><td class="px-3 py-2 align-top font-semibold">Hari '.(int) $day['day'].'</td>'.$cells.'<td class="px-3 py-2 align-top">'.$coffee.'</td></tr>';
        }

        return new HtmlString(
            '<div class="overflow-x-auto"><p class="mb-2 text-sm text-gray-500">'.e($record->variant->menuSet?->name).'</p><table class="w-full text-left text-sm"><thead><tr class="text-gray-500"><th class="px-3 py-2">Hari</th><th class="px-3 py-2">Sarapan</th><th class="px-3 py-2">Makan siang</th><th class="px-3 py-2">Makan malam</th><th class="px-3 py-2">Coffee break</th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
        );
    }
}
