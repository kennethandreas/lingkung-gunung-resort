<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\QuotationStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewTargetType;
use App\Exceptions\ReviewException;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Livewire\ReviewForm;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CustomerAccount;
use App\Models\GroupPackage;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\UnitType;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\ReviewService;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Sistem ulasan: kelayakan booking, duplikat, moderasi, rating publik, foto, dan hak akses. */
class ReviewTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    private ReviewService $reviews;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        Mail::fake();
        // ID media di DB test bisa sama dengan media lokal: jangan sentuh folder storage asli
        Storage::fake('public');
        Storage::fake('ulasan');
        $this->reviews = app(ReviewService::class);
    }

    private function account(string $email = 'tamu.ulasan@example.com', string $name = 'Rina Kartika Sari'): CustomerAccount
    {
        $account = CustomerAccount::query()->create(['name' => $name, 'email' => $email, 'password' => 'rahasia123']);
        $account->forceFill(['email_verified_at' => now()])->save();

        return $account;
    }

    /** Booking lunas milik akun (siklus asli: hold → bayar lunas oleh staf). */
    private function paidBooking(CustomerAccount $account, UnitType $type, string $checkIn = '2026-10-12', string $checkOut = '2026-10-14'): Booking
    {
        $booking = $this->holdBooking($type, $checkIn, $checkOut, overrides: ['payment_option' => 'full']);
        $booking->forceFill(['customer_account_id' => $account->id])->save();
        app(PaymentService::class)->recordManualPayment($booking, $booking->total, User::factory()->create());

        return $booking->fresh();
    }

    /** Booking selesai: lunas → check-in oleh staf pada hari kedatangan → waktu dimajukan ke hari check-out. */
    private function completedBooking(CustomerAccount $account, UnitType $type): Booking
    {
        $booking = $this->paidBooking($account, $type);
        $this->travelTo('2026-10-12 14:00:00');
        app(BookingService::class)->checkIn($booking);
        $this->travelTo('2026-10-14 12:00:00');

        return $booking->fresh();
    }

    private function staff(string $role): User
    {
        $this->seed(RolesSeeder::class);

        return tap(User::factory()->create())->assignRole($role);
    }

    private function unitKey(UnitType $type): string
    {
        return 'akomodasi-'.$type->id;
    }

    // ------------------------------------------------------------------ kelayakan

    public function test_hanya_booking_yang_sudah_check_in_dan_selesai_menginap_yang_bisa_diulas(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType();

        // Lunas tetapi belum check-in: belum boleh (pembayaran saja tidak cukup)
        $booking = $this->paidBooking($account, $type);
        $this->assertSame(BookingStatus::FullyPaid, $booking->status);
        $this->assertNotNull($this->reviews->eligibilityProblem($account, $booking));
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5]), 'setelah Anda check-in');

        // Sudah check-in tetapi masih menginap: belum boleh
        $this->travelTo('2026-10-12 14:00:00');
        app(BookingService::class)->checkIn($booking);
        $booking->refresh();
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5]), 'mulai tanggal check-out');

        // Hari check-out: boleh
        $this->travelTo('2026-10-14 12:00:00');
        $this->assertNull($this->reviews->eligibilityProblem($account, $booking));
        $review = $this->reviews->create($account, $booking, 'resort', ['rating' => 5, 'comment' => 'Udara sejuk, staf ramah.']);
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertNull($review->published_at);
        $this->assertSame('Rina S.', $review->reviewer_name);
    }

    public function test_booking_batal_dan_booking_akun_lain_tidak_bisa_diulas(): void
    {
        $owner = $this->account();
        $other = $this->account('lain@example.com', 'Orang Lain');
        $type = $this->makeUnitType();
        $booking = $this->completedBooking($owner, $type);

        // Akun lain: service menolak, halaman & form 404
        $this->expectReviewError(fn () => $this->reviews->create($other, $booking, 'resort', ['rating' => 4]), 'tidak ditemukan');
        $this->actingAs($other, 'customer')->get(route('akun.ulasan.edit', [$booking->code, 'resort']))->assertNotFound();

        // Booking dibatalkan
        $cancelled = $this->holdBooking($type, '2026-10-20', '2026-10-21');
        $cancelled->forceFill(['customer_account_id' => $owner->id])->save();
        app(BookingService::class)->cancel($cancelled, 'uji');
        $this->assertStringContainsString('dibatalkan', (string) $this->reviews->eligibilityProblem($owner, $cancelled->fresh()));
    }

    public function test_tamu_belum_login_diarahkan_ke_halaman_masuk(): void
    {
        $booking = $this->completedBooking($this->account(), $this->makeUnitType());

        $this->get(route('akun.ulasan.edit', [$booking->code, 'resort']))->assertRedirect(route('akun.masuk'));
    }

    public function test_tidak_bisa_mengulas_akomodasi_yang_tidak_dipesan(): void
    {
        $account = $this->account();
        $booked = $this->makeUnitType(attributes: ['marketing_name' => 'Glamping Dipesan']);
        $notBooked = $this->makeUnitType(attributes: ['marketing_name' => 'Kamar Lain']);
        $booking = $this->completedBooking($account, $booked);

        $keys = $this->reviews->targetsFor($booking)->map->key()->all();
        $this->assertSame(['resort', $this->unitKey($booked)], $keys);

        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, $this->unitKey($notBooked), ['rating' => 5]), 'tidak termasuk');
        $this->actingAs($account, 'customer')->get(route('akun.ulasan.edit', [$booking->code, $this->unitKey($notBooked)]))->assertNotFound();
        $this->actingAs($account, 'customer')->get(route('akun.ulasan.edit', [$booking->code, $this->unitKey($booked)]))->assertOk()->assertSee('Glamping Dipesan');
    }

    public function test_paket_dan_aktivitas_hanya_dari_penawaran_yang_menjadi_booking_ini(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType();
        $booking = $this->completedBooking($account, $type);

        $package = GroupPackage::query()->create(['slug' => 'paket-uji', 'name' => 'Paket Fullboard Uji', 'accommodation' => 'Kamar']);
        $variant = $package->variants()->create(['code' => 'A', 'name' => 'Varian A', 'min_pax' => 20, 'price_weekday' => 300000, 'price_weekend' => 350000]);
        $rafting = Activity::query()->create(['slug' => 'rafting-uji', 'name' => 'Rafting Uji', 'category' => 'adventure', 'price' => 200000, 'price_unit' => 'pax']);
        $paintball = Activity::query()->create(['slug' => 'paintball-uji', 'name' => 'Paintball Uji', 'category' => 'adventure', 'price' => 150000, 'price_unit' => 'pax']);

        $quotation = Quotation::query()->create([
            'number' => 'LG-Q-2610-9001', 'code' => 'QUJI12345', 'customer_id' => $booking->customer_id, 'group_package_variant_id' => $variant->id,
            'event_date' => '2026-10-12', 'days' => 3, 'pax' => 20, 'status' => QuotationStatus::Deal, 'total' => 1000000, 'booking_id' => $booking->id,
        ]);
        $quotation->items()->create(['item_type' => 'activity', 'item_id' => $rafting->id, 'description' => 'Rafting Uji', 'qty' => 20, 'unit_price' => 200000, 'subtotal' => 4000000]);
        $booking->items()->create(['item_type' => BookingItem::TYPE_PACKAGE, 'item_id' => $quotation->id, 'description' => 'Paket', 'qty' => 1, 'unit_price' => 0, 'subtotal' => 0]);

        $keys = $this->reviews->targetsFor($booking->fresh())->map->key()->all();
        $this->assertContains('paket-'.$package->id, $keys);
        $this->assertContains('aktivitas-'.$rafting->id, $keys);
        $this->assertNotContains('aktivitas-'.$paintball->id, $keys);

        $review = $this->reviews->create($account, $booking, 'aktivitas-'.$rafting->id, ['rating' => 4, 'comment' => 'Arusnya seru.']);
        $this->assertSame($rafting->id, $review->activity_id);
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'aktivitas-'.$paintball->id, ['rating' => 5]), 'tidak termasuk');

        // Penawaran yang tidak terhubung ke booking ini tidak memberi hak mengulas
        $quotation->forceFill(['booking_id' => null])->save();
        $this->assertSame(['resort', $this->unitKey($type)], $this->reviews->targetsFor($booking->fresh())->map->key()->all());

        // Setelah disetujui: tampil di halaman ulasan aktivitas & ringkas di halaman Aktivitas
        $this->reviews->approve($review, User::factory()->create());
        $this->get(route('ulasan.target', ['aktivitas', 'rafting-uji']))->assertOk()->assertSee('Arusnya seru.')->assertSee('Rina S.');
        $this->get(route('play'))->assertOk()->assertSee('4,0 · 1 ulasan');
    }

    // ------------------------------------------------------------------ input & duplikat

    public function test_nilai_harus_bilangan_bulat_1_sampai_5_dan_html_dibuang(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());

        foreach ([0, 6, '4.5', 'abc', null] as $bad) {
            $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => $bad]), '1 sampai 5');
        }
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5, 'comment' => str_repeat('a', 2001)]), 'maksimal 2000');
        $this->assertSame(0, Review::query()->count());

        $review = $this->reviews->create($account, $booking, 'resort', ['rating' => '4', 'comment' => '<script>alert(1)</script>Bagus <b>sekali</b>']);
        $this->assertSame(4, $review->rating);
        $this->assertSame('alert(1)Bagus sekali', $review->comment);

        // Database juga menolak nilai di luar 1–5 (CHECK constraint)
        $this->expectException(QueryException::class);
        DB::table('reviews')->where('id', $review->id)->update(['rating' => 7]);
    }

    public function test_satu_booking_hanya_satu_ulasan_per_target_juga_di_database(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType();
        $booking = $this->completedBooking($account, $type);

        $this->reviews->create($account, $booking, 'resort', ['rating' => 5]);
        $this->reviews->create($account, $booking, $this->unitKey($type), ['rating' => 4]);
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 3]), 'sudah mengirim ulasan');
        $this->assertSame(2, Review::query()->count());

        // Lewat jalur aplikasi pun dicegah; unique index menahan sisipan langsung (kiriman bersamaan)
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('reviews')->insert([
            'customer_account_id' => $account->id, 'booking_id' => $booking->id, 'target_type' => 'resort',
            'rating' => 1, 'reviewer_name' => 'X', 'status' => 'pending', 'submitted_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------ moderasi & rating publik

    public function test_alur_lengkap_kirim_menunggu_disetujui_tampil_dan_rata_rata_berubah(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType(attributes: ['marketing_name' => 'Pangrango Uji', 'slug' => 'pangrango-uji']);
        $booking = $this->completedBooking($account, $type);

        // Kirim lewat form akun
        $this->actingAs($account, 'customer');
        Livewire::test(ReviewForm::class, ['bookingCode' => $booking->code, 'targetKey' => $this->unitKey($type)])
            ->set('rating', 4)
            ->set('comment', 'Kamar bersih dan balkonnya menghadap lembah.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('akun.booking.show', $booking->code).'#ulasan');

        $review = Review::query()->sole();
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertSame($type->id, $review->unit_type_id);

        // Menunggu: tidak tampil & tidak dihitung
        $this->get(route('akomodasi.show', $type))->assertOk()->assertDontSee('balkonnya menghadap lembah')->assertSee('Belum ada ulasan');
        $this->assertSame(['average' => null, 'count' => 0], array_intersect_key($this->reviews->summary(ReviewTargetType::UnitType, $type->id), ['average' => 1, 'count' => 1]));
        $this->get(route('akun.booking.show', $booking->code))->assertSee('Menunggu persetujuan');

        // Admin menyetujui dari panel
        $admin = $this->staff('admin');
        $this->actingAs($admin, 'web');
        Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])->callAction('approve')->assertHasNoActionErrors();
        $review->refresh();
        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertSame($admin->id, $review->moderated_by);
        $this->assertNotNull($review->published_at);

        // Tampil di halaman akomodasi yang benar, tidak di resort-wide
        $this->get(route('akomodasi.show', $type))->assertOk()->assertSee('balkonnya menghadap lembah')->assertSee('Rina S.')->assertSee('4,0 · 1 ulasan');
        $this->get(route('ulasan.index'))->assertOk()->assertDontSee('balkonnya menghadap lembah');
        $this->get(route('home'))->assertOk()->assertDontSee('ulasan</a>', false);

        // Ulasan kedua (booking lain) mengubah rata-rata
        $second = $this->account('kedua@example.com', 'Dodi');
        $this->travelTo('2026-10-05 10:00:00');
        $booking2 = $this->paidBooking($second, $type, '2026-10-20', '2026-10-21');
        $this->travelTo('2026-10-20 14:00:00');
        app(BookingService::class)->checkIn($booking2);
        $this->travelTo('2026-10-21 12:00:00');
        $this->reviews->approve($this->reviews->create($second, $booking2->fresh(), $this->unitKey($type), ['rating' => 5]), $admin);

        $summary = $this->reviews->summary(ReviewTargetType::UnitType, $type->id);
        $this->assertSame(4.5, $summary['average']);
        $this->assertSame(2, $summary['count']);
        $this->assertSame([5 => 1, 4 => 1, 3 => 0, 2 => 0, 1 => 0], $summary['distribution']);
    }

    public function test_ditolak_dan_disembunyikan_tidak_tampil_dan_tidak_dihitung(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $moderator = User::factory()->create();

        $review = $this->reviews->create($account, $booking, 'resort', ['rating' => 2, 'comment' => 'Ulasan rahasia uji']);

        // Perpindahan status divalidasi server
        $this->expectReviewError(fn () => $this->reviews->hide($review, $moderator, 'uji'), 'tidak bisa diubah');
        $this->expectReviewError(fn () => $this->reviews->reject($review, $moderator, ''), 'alasan');

        $this->reviews->approve($review, $moderator);
        $this->assertSame(1, $this->reviews->summary(ReviewTargetType::Resort)['count']);
        $this->get(route('ulasan.index'))->assertSee('Ulasan rahasia uji');
        $this->get(route('home'))->assertSee('2,0 · 1 ulasan');

        $this->reviews->hide($review, $moderator, 'Mengandung data pribadi');
        $this->assertSame(0, $this->reviews->summary(ReviewTargetType::Resort)['count']);
        $this->get(route('ulasan.index'))->assertDontSee('Ulasan rahasia uji');
        $this->get(route('home'))->assertDontSee('1 ulasan');

        // Disembunyikan → boleh ditayangkan lagi; ditolak hanya dari menunggu
        $this->reviews->approve($review, $moderator);
        $this->expectReviewError(fn () => $this->reviews->reject($review->fresh(), $moderator, 'uji'), 'tidak bisa diubah');

        // Pelanggan melihat status & pesan untuk pelanggan, bukan catatan internal
        $other = $this->reviews->create($account, $booking, $this->unitKey(UnitType::query()->first()), ['rating' => 1, 'comment' => 'Isi ditolak']);
        $this->reviews->reject($other, $moderator, 'CATATAN-INTERNAL-RAHASIA', 'Mohon hapus nomor telepon dari ulasan.');
        $this->assertSame(1, $this->reviews->summary(ReviewTargetType::Resort)['count']);
        $this->actingAs($account, 'customer')->get(route('akun.booking.show', $booking->code))
            ->assertSee('Tidak ditayangkan')
            ->assertSee('Mohon hapus nomor telepon dari ulasan.')
            ->assertDontSee('CATATAN-INTERNAL-RAHASIA');
    }

    public function test_mengubah_ulasan_tayang_kembali_menunggu_dan_hapus_mengurangi_rata_rata(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $moderator = User::factory()->create();
        $review = $this->reviews->approve($this->reviews->create($account, $booking, 'resort', ['rating' => 5, 'comment' => 'Versi awal']), $moderator);

        $updated = $this->reviews->update($account, $review, ['rating' => 3, 'comment' => 'Versi baru']);
        $this->assertSame(ReviewStatus::Pending, $updated->status);
        $this->assertNull($updated->published_at);
        $this->assertNotNull($updated->edited_at);
        $this->assertSame(0, $this->reviews->summary(ReviewTargetType::Resort)['count']);
        $this->get(route('ulasan.index'))->assertDontSee('Versi awal')->assertDontSee('Versi baru');

        // Akun lain tidak bisa mengubah / menghapus
        $other = $this->account('lain@example.com');
        $this->expectReviewError(fn () => $this->reviews->update($other, $updated, ['rating' => 1]), 'tidak ditemukan');
        $this->expectReviewError(fn () => $this->reviews->delete($other, $updated), 'tidak ditemukan');

        $this->reviews->approve($updated, $moderator);
        $this->assertSame(3.0, $this->reviews->summary(ReviewTargetType::Resort)['average']);

        $this->reviews->delete($account, $updated->fresh());
        $this->assertSame(0, Review::query()->count());
        $this->assertSame(0, $this->reviews->summary(ReviewTargetType::Resort)['count']);
    }

    public function test_form_tidak_bisa_mengubah_status_atau_booking_dari_browser(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $this->actingAs($account, 'customer');

        $component = Livewire::test(ReviewForm::class, ['bookingCode' => $booking->code, 'targetKey' => 'resort']);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('bookingCode', 'LGLAINNYA1');
    }

    public function test_form_tidak_punya_properti_status(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $this->actingAs($account, 'customer');

        Livewire::test(ReviewForm::class, ['bookingCode' => $booking->code, 'targetKey' => 'resort'])
            ->set('rating', 5)
            ->call('save');

        $this->assertSame(ReviewStatus::Pending, Review::query()->sole()->status);
        $this->assertFalse(property_exists(ReviewForm::class, 'status'));
    }

    // ------------------------------------------------------------------ foto

    public function test_foto_disimpan_privat_dan_hanya_terbuka_setelah_tayang(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());

        $review = $this->reviews->create($account, $booking, 'resort', ['rating' => 5], [UploadedFile::fake()->image('IMG_lokasi rumah.jpg', 1600, 1200)]);
        $media = $review->getFirstMedia('photos');

        $this->assertSame('ulasan', $media->disk);
        $this->assertStringNotContainsString('lokasi', $media->file_name); // nama dari server, bukan dari perangkat
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}\.jpg$/', $media->file_name);
        $this->assertTrue($media->hasGeneratedConversion('sm'));
        $this->assertTrue($media->hasGeneratedConversion('md'));

        $url = route('ulasan.foto', [$media->uuid, 'sm']);

        // Menunggu: pengunjung & akun lain 404, pemilik bisa melihat
        $this->get($url)->assertNotFound();
        $this->actingAs($this->account('lain@example.com'), 'customer')->get($url)->assertNotFound();
        $this->actingAs($account, 'customer')->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');

        // Staf dengan izin melihat ulasan bisa; front office tidak
        // (guard ditulis eksplisit: actingAs pelanggan di atas mengganti guard bawaan test)
        auth('customer')->logout();
        $this->actingAs($this->staff('front_office'), 'web')->get($url)->assertNotFound();
        $this->actingAs(User::factory()->create()->assignRole('sales'), 'web')->get($url)->assertOk();

        // Setelah tayang: semua pengunjung; ukuran yang tidak dikenal & file asli tidak pernah dikirim
        $this->reviews->approve($review, User::factory()->create());
        auth('web')->logout();
        $this->get($url)->assertOk();
        $this->get('/ulasan/foto/'.$media->uuid.'/original')->assertNotFound();
        $this->get(route('ulasan.index'))->assertSee('Foto dari tamu')->assertSee($url, false);

        // Hapus ulasan → file ikut terhapus
        $path = $media->getPath();
        $this->assertFileExists($path);
        $this->reviews->delete($account, $review->fresh());
        $this->assertFileDoesNotExist($path);
    }

    public function test_foto_palsu_terlalu_banyak_atau_terlalu_besar_ditolak(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());

        // File teks/PHP yang diberi nama .jpg dan MIME palsu
        $fake = UploadedFile::fake()->createWithContent('foto.jpg', '<?php echo "x"; ?>');
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5], [$fake]), 'JPG, PNG, atau WebP');

        // SVG / GIF tidak didukung
        $gif = UploadedFile::fake()->image('a.gif', 300, 300);
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5], [$gif]), 'JPG, PNG, atau WebP');

        $five = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg", 400, 300), range(1, 5));
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5], $five), 'Maksimal 4 foto');

        $big = UploadedFile::fake()->image('besar.jpg', 400, 300)->size(6000);
        $this->expectReviewError(fn () => $this->reviews->create($account, $booking, 'resort', ['rating' => 5], [$big]), 'maksimal 5 MB');

        $this->assertSame(0, Review::query()->count(), 'Ulasan tidak boleh tersimpan setengah jadi');

        // Lewat form: validasi tampil sebagai pesan, bukan error server
        $this->actingAs($account, 'customer');
        Livewire::test(ReviewForm::class, ['bookingCode' => $booking->code, 'targetKey' => 'resort'])
            ->set('upload', [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')])
            ->assertHasErrors(['upload.0'])
            ->assertSet('photos', []);
    }

    public function test_form_mengunggah_foto_menghapus_pilihan_lalu_mengirim(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $this->actingAs($account, 'customer');

        Livewire::test(ReviewForm::class, ['bookingCode' => $booking->code, 'targetKey' => 'resort'])
            ->set('upload', [UploadedFile::fake()->image('satu.jpg', 800, 600), UploadedFile::fake()->image('dua.png', 800, 600)])
            ->assertHasNoErrors()
            ->assertCount('photos', 2)
            ->assertSet('upload', [])
            ->call('removeUpload', 1)
            ->assertCount('photos', 1)
            ->set('rating', 5)
            ->call('save')
            ->assertHasNoErrors();

        $review = Review::query()->sole();
        $this->assertCount(1, $review->getMedia('photos'));
        $this->assertSame('ulasan', $review->getFirstMedia('photos')->disk);

        // Hapus lewat form: ulasan & foto hilang
        $path = $review->getFirstMedia('photos')->getPath();
        Livewire::test(ReviewForm::class, ['bookingCode' => $booking->code, 'targetKey' => 'resort'])
            ->assertSet('rating', 5)
            ->call('deleteReview')
            ->assertRedirect(route('akun.booking.show', $booking->code).'#ulasan');
        $this->assertSame(0, Review::query()->count());
        $this->assertFileDoesNotExist($path);
    }

    public function test_ubah_ulasan_bisa_menghapus_dan_menambah_foto_dalam_batas(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $review = $this->reviews->create($account, $booking, 'resort', ['rating' => 5], [
            UploadedFile::fake()->image('a.jpg', 400, 300), UploadedFile::fake()->image('b.png', 400, 300),
        ]);
        [$first, $second] = $review->getMedia('photos')->all();
        $firstPath = $first->getPath();

        $this->expectReviewError(fn () => $this->reviews->update($account, $review, ['rating' => 5], array_map(fn ($i) => UploadedFile::fake()->image("n{$i}.jpg", 400, 300), range(1, 3))), 'Maksimal 4 foto');

        $updated = $this->reviews->update($account, $review, ['rating' => 4], [UploadedFile::fake()->image('c.webp', 400, 300)], [$first->id, 999999]);
        $this->assertCount(2, $updated->getMedia('photos'));
        $this->assertFileDoesNotExist($firstPath);
        $this->assertTrue($updated->getMedia('photos')->contains('id', $second->id));
    }

    // ------------------------------------------------------------------ admin & hak akses

    public function test_hak_akses_moderasi_per_role(): void
    {
        $account = $this->account();
        $booking = $this->completedBooking($account, $this->makeUnitType());
        $review = $this->reviews->create($account, $booking, 'resort', ['rating' => 5, 'comment' => 'Menunggu moderasi']);

        // Front office: tidak bisa membuka menu ulasan
        $this->actingAs($this->staff('front_office'), 'web')->get(ReviewResource::getUrl('index'))->assertForbidden();

        // Sales: bisa melihat, tidak bisa memoderasi
        $sales = User::factory()->create()->assignRole('sales');
        $this->actingAs($sales, 'web')->get(ReviewResource::getUrl('index'))->assertOk();
        Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');
        $this->assertFalse($sales->can('moderate', $review));

        // Admin: daftar menunggu + tolak dengan catatan
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin, 'web');
        Livewire::test(ListReviews::class)->assertCanSeeTableRecords([$review]);
        Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])
            ->assertActionHidden('hide')
            ->callAction('reject', ['note' => 'Spam', 'customer_message' => ''])
            ->assertHasNoActionErrors();
        $this->assertSame(ReviewStatus::Rejected, $review->fresh()->status);
        $this->assertSame('Spam', $review->fresh()->moderation_note);

        // Tidak ada aksi buat ulasan dari admin
        $this->assertFalse(ReviewResource::canCreate());
    }

    public function test_halaman_akun_menunjukkan_status_ulasan_per_booking(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType(attributes: ['marketing_name' => 'Imah Lembah Uji']);
        $booking = $this->completedBooking($account, $type);
        $this->actingAs($account, 'customer');

        $this->get(route('akun.booking.index'))->assertOk()->assertSee('Bagikan pengalaman Anda')->assertSee('2 hal dari booking ini bisa diulas');
        $this->get(route('akun.booking.show', $booking->code))->assertOk()
            ->assertSee('Ulasan Anda')->assertSee('Imah Lembah Uji')->assertSee('Tulis ulasan')
            ->assertSee(route('akun.ulasan.edit', [$booking->code, 'resort']), false);

        $this->reviews->create($account, $booking, 'resort', ['rating' => 5]);
        $this->get(route('akun.booking.index'))->assertSee('1 hal dari booking ini bisa diulas');

        // Booking belum selesai: penjelasan, tanpa tombol tulis
        $this->travelTo('2026-10-05 10:00:00');
        $future = $this->paidBooking($account, $type, '2026-11-02', '2026-11-03');
        $this->get(route('akun.booking.show', $future->code))->assertSee('setelah Anda check-in')->assertDontSee(route('akun.ulasan.edit', [$future->code, 'resort']), false);
        $this->get(route('akun.ulasan.edit', [$future->code, 'resort']))->assertRedirect(route('akun.booking.show', $future->code));
    }

    public function test_halaman_publik_tanpa_ulasan_tidak_menampilkan_rating_palsu(): void
    {
        $type = $this->makeUnitType(attributes: ['slug' => 'kosong-uji']);

        $this->get(route('home'))->assertOk()->assertDontSee('dari 5 bintang');
        $this->get(route('ulasan.index'))->assertOk()->assertSee('Belum ada ulasan');
        $this->get(route('akomodasi.show', $type))->assertOk()->assertSee('Belum ada ulasan')->assertDontSee('5,0');
        $this->get(route('ulasan.target', ['aktivitas', 'tidak-ada']))->assertNotFound();
        $this->get('/ulasan/kamar/'.$type->slug)->assertNotFound();
    }

    public function test_ulasan_tayang_berhalaman_dan_bisa_disaring(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 20]);
        $moderator = User::factory()->create();

        foreach (range(1, 10) as $i) {
            $this->travelTo('2026-10-05 10:00:00');
            $account = $this->account("tamu{$i}@example.com", "Tamu {$i}");
            $booking = $this->completedBooking($account, $type);
            $this->reviews->approve($this->reviews->create($account, $booking, 'resort', ['rating' => $i % 5 + 1, 'comment' => "Komentar nomor {$i}."]), $moderator);
        }

        $this->get(route('ulasan.index'))->assertOk()->assertSee('Halaman 1 dari 2')->assertSee('10 ulasan');
        $this->get(route('ulasan.index', ['bintang' => 5]))->assertSee('Komentar nomor 4.')->assertSee('Komentar nomor 9.')->assertDontSee('Komentar nomor 1.');
        $this->get(route('ulasan.index', ['urut' => 'terendah', 'bintang' => 'x']))->assertOk();
    }

    private function expectReviewError(callable $callback, string $message): void
    {
        try {
            $callback();
        } catch (ReviewException $e) {
            $this->assertStringContainsString($message, $e->getMessage());

            return;
        }

        $this->fail("ReviewException \"{$message}\" tidak dilempar.");
    }
}
