<?php

namespace Tests\Feature;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Filament\Pages\OccupancyCalendar;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\DailyInventory;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Alur admin lewat form Filament sungguhan (Livewire), bukan memanggil service langsung. */
class AdminBookingFlowTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        $this->seed(RolesSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = tap(User::factory()->create())->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_front_office_input_reservasi_manual_lalu_catat_dp_dan_pelunasan(): void
    {
        Storage::fake('local');
        $undo = Repeater::fake();
        $user = $this->actingAsRole('front_office');
        $type = $this->makeUnitType(500000, 600000);

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'check_in' => '2026-10-12',
                'check_out' => '2026-10-14',
                'guests' => 3,
                'lines' => [['unit_type_id' => $type->id, 'qty' => 1]],
                'payment_option' => 'dp',
                'customer' => ['name' => 'Siti Aminah', 'phone' => '0812-1111-2222'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undo();
        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame(BookingChannel::Manual, $booking->channel);
        $this->assertSame(1000000, $booking->total);
        $this->assertSame($user->id, $booking->created_by);
        $this->assertSame('081211112222', $booking->customer->phone);

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('recordPayment', data: [
                'amount' => 250000,
                'proof_path' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(BookingStatus::DpPaid, $booking->fresh()->status);

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('recordPayment', data: [
                'amount' => 750000,
                'proof_path' => UploadedFile::fake()->image('pelunasan.jpg'),
            ]);

        $booking->refresh();
        $this->assertSame(BookingStatus::FullyPaid, $booking->status);
        $this->assertSame(2, $booking->payments()->count());
        Storage::disk('local')->assertExists($booking->payments()->first()->proof_path);
    }

    public function test_form_menampilkan_pesan_ramah_saat_stok_habis(): void
    {
        $undo = Repeater::fake();
        $this->actingAsRole('admin');
        $type = $this->makeUnitType(attributes: ['total_units' => 1]);
        $this->holdBooking($type, '2026-10-12', '2026-10-13');

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'check_in' => '2026-10-12',
                'check_out' => '2026-10-13',
                'guests' => 2,
                'lines' => [['unit_type_id' => $type->id, 'qty' => 1]],
                'payment_option' => 'full',
                'customer' => ['name' => 'Andi', 'phone' => '0813'],
            ])
            ->call('create')
            ->assertNotified('Booking belum bisa dibuat');

        $undo();
        $this->assertSame(1, Booking::query()->count());
    }

    public function test_front_office_tidak_bisa_membatalkan_booking(): void
    {
        $this->actingAsRole('front_office');
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('cancel')
            ->assertActionVisible('recordPayment');
    }

    public function test_admin_bisa_membatalkan_dan_stok_kembali(): void
    {
        $this->actingAsRole('admin');
        $type = $this->makeUnitType();
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13');

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('cancel', data: ['reason' => 'Permintaan tamu']);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(0, (int) DailyInventory::query()->where('unit_type_id', $type->id)->sum('held'));
    }

    public function test_admin_memblokir_tanggal_dari_kalender_okupansi(): void
    {
        $this->actingAsRole('admin');
        $type = $this->makeUnitType();

        Livewire::test(OccupancyCalendar::class)
            ->assertSee($type->marketing_name)
            ->callAction('block', data: [
                'unit_type_id' => $type->id,
                'from' => '2026-10-20',
                'to' => '2026-10-22',
                'blocked' => '1',
                'note' => 'Perawatan',
            ]);

        $this->assertSame(3, DailyInventory::query()->where('unit_type_id', $type->id)->where('is_blocked', true)->count());
    }
}
