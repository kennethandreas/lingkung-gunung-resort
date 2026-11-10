<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Pengaturan umum (tabel settings). Nilai default ada di config/resort.php.
 */
class ManageSettings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.manage-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pengaturan Umum';

    protected static ?string $title = 'Pengaturan Umum';

    /** Kunci yang dikelola halaman ini. */
    private const KEYS = [
        'weekend_nights', 'dp_percent', 'hold_minutes', 'settlement_days_before', 'booking_window_days',
        'contact_whatsapp', 'contact_phone', 'contact_instagram', 'contact_email', 'address',
        'contact_website', 'contact_facebook', 'contact_youtube',
        'google_maps_query', 'waze_query', 'bank_name', 'bank_account_name', 'bank_account_number',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $values = collect(self::KEYS)->mapWithKeys(fn ($key) => [$key => Setting::get($key)])->all();
        $values['weekend_nights'] = array_map('strval', (array) $values['weekend_nights']);

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Aturan Booking')
                    ->description('Berlaku untuk website, input manual, dan konversi B2B.')
                    ->columns(2)
                    ->schema([
                        CheckboxList::make('weekend_nights')
                            ->label('Malam yang dihitung tarif weekend')
                            ->helperText('Contoh: tamu menginap Sabtu malam = malam Sabtu.')
                            ->options(['0' => 'Minggu', '1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu'])
                            ->columns(4)
                            ->columnSpanFull(),
                        TextInput::make('dp_percent')->label('Besar DP')->numeric()->minValue(1)->maxValue(100)->suffix('%')->required(),
                        TextInput::make('hold_minutes')->label('Lama stok ditahan menunggu pembayaran')->numeric()->minValue(5)->suffix('menit')->required(),
                        TextInput::make('settlement_days_before')->label('Batas pelunasan sebelum kedatangan')->numeric()->minValue(0)->prefix('H-')->required(),
                        TextInput::make('booking_window_days')->label('Booking paling jauh')->numeric()->minValue(30)->suffix('hari ke depan')->required(),
                    ]),
                Section::make('Kontak Resort')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_whatsapp')
                            ->label('Nomor WhatsApp (format 62...)')
                            ->helperText('Dipakai untuk tombol wa.me. Contoh: 628111516727')
                            ->regex('/^62\d{8,13}$/')
                            ->required(),
                        TextInput::make('contact_phone')->label('Telepon')->tel(),
                        TextInput::make('contact_instagram')->label('Instagram (tanpa @)'),
                        TextInput::make('contact_email')->label('Email resmi')->email(),
                        TextInput::make('contact_website')->label('Situs resmi lain')->url()->helperText('Contoh: https://elji.id'),
                        TextInput::make('contact_facebook')->label('Facebook (nama halaman)')->helperText('Bagian setelah facebook.com/'),
                        TextInput::make('contact_youtube')->label('YouTube (nama kanal, dengan @)')->regex('/^@[\w.\-]+$/'),
                        TextInput::make('address')->label('Alamat')->columnSpanFull(),
                        TextInput::make('google_maps_query')->label('Kata kunci Google Maps'),
                        TextInput::make('waze_query')->label('Kata kunci Waze'),
                    ]),
                Section::make('Rekening Transfer')
                    ->columns(3)
                    ->schema([
                        TextInput::make('bank_name')->label('Bank'),
                        TextInput::make('bank_account_name')->label('Atas nama'),
                        TextInput::make('bank_account_number')->label('Nomor rekening'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['weekend_nights'] = array_values(array_map('intval', $data['weekend_nights'] ?? []));

        foreach (['dp_percent', 'hold_minutes', 'settlement_days_before', 'booking_window_days'] as $key) {
            $data[$key] = (int) $data[$key];
        }

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        Notification::make()->title('Pengaturan tersimpan')->success()->send();
    }
}
