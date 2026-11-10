<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewTargetType;
use App\Exceptions\ReviewException;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CustomerAccount;
use App\Models\GroupPackage;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\UnitType;
use App\Models\User;
use App\Support\ReviewTarget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ulasan tamu: kelayakan, kirim/ubah/hapus oleh pelanggan, moderasi staf, dan ringkasan rating publik.
 *
 * Aturan kelayakan (semua dicek di server setiap kali kirim/ubah):
 * - booking milik akun yang login;
 * - status booking "Sudah Check-in" (dicatat staf saat tamu datang) DAN tanggal check-out sudah tiba.
 *   Pembayaran saja tidak cukup: tamu yang lunas tetapi tidak datang tidak bisa mengulas;
 * - target = resort (satu per booking) atau akomodasi/aktivitas/paket yang benar-benar ada di booking itu;
 * - satu ulasan per booking per target (unique index database, aman dari klik ganda).
 * Rata-rata dan jumlah ulasan selalu dihitung dari ulasan tayang saat itu (tidak ada angka tersimpan yang bisa basi).
 */
class ReviewService
{
    /** Alasan booking belum/tidak bisa diulas, atau null bila boleh. */
    public function eligibilityProblem(CustomerAccount $account, Booking $booking): ?string
    {
        if ($booking->customer_account_id !== $account->id) {
            return 'Booking tidak ditemukan di akun Anda.';
        }

        if ($booking->status !== BookingStatus::CheckedIn) {
            return in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Expired], true)
                ? 'Booking yang dibatalkan atau kedaluwarsa tidak bisa diulas.'
                : 'Ulasan bisa ditulis setelah Anda check-in di resort dan masa menginap selesai.';
        }

        if ($booking->check_out->gt(today())) {
            return 'Ulasan bisa ditulis mulai tanggal check-out, '.$booking->check_out->translatedFormat('d F Y').'.';
        }

        return null;
    }

    /**
     * Semua yang boleh diulas dari booking ini: resort + tipe unit yang dipesan
     * + (booking rombongan) paket dan aktivitas dari penawaran yang dikonversi menjadi booking ini.
     *
     * @return Collection<int, ReviewTarget>
     */
    public function targetsFor(Booking $booking): Collection
    {
        $booking->loadMissing('items');
        $targets = collect([ReviewTarget::resort()]);

        $unitTypeIds = $booking->items->where('item_type', BookingItem::TYPE_UNIT_TYPE)->pluck('item_id')->unique()->all();
        UnitType::query()->whereKey($unitTypeIds)->ordered()->get()
            ->each(fn (UnitType $type) => $targets->push(new ReviewTarget(ReviewTargetType::UnitType, $type)));

        $quotationIds = $booking->items->where('item_type', BookingItem::TYPE_PACKAGE)->pluck('item_id')->filter()->all();
        if ($quotationIds !== []) {
            // Hanya penawaran yang memang terhubung ke booking ini
            $quotations = Quotation::query()->whereKey($quotationIds)->where('booking_id', $booking->id)->with(['variant', 'items'])->get();

            $packageIds = $quotations->pluck('variant.group_package_id')->filter()->unique()->all();
            GroupPackage::query()->whereKey($packageIds)->orderBy('sort_order')->get()
                ->each(fn (GroupPackage $package) => $targets->push(new ReviewTarget(ReviewTargetType::GroupPackage, $package)));

            $activityIds = $quotations->flatMap->items->where('item_type', 'activity')->pluck('item_id')->filter()->unique()->all();
            Activity::query()->whereKey($activityIds)->orderBy('sort_order')->get()
                ->each(fn (Activity $activity) => $targets->push(new ReviewTarget(ReviewTargetType::Activity, $activity)));
        }

        return $targets->values();
    }

    public function findTarget(Booking $booking, string $key): ?ReviewTarget
    {
        return $this->targetsFor($booking)->first(fn (ReviewTarget $target) => $target->key() === $key);
    }

    /**
     * Daftar kesempatan mengulas untuk halaman akun.
     *
     * @return array{eligible: bool, reason: ?string, items: Collection<int, array{target: ReviewTarget, review: ?Review}>}
     */
    public function opportunities(CustomerAccount $account, Booking $booking): array
    {
        $reason = $this->eligibilityProblem($account, $booking);
        $reviews = $booking->reviews()->where('customer_account_id', $account->id)->get();

        $items = $this->targetsFor($booking)->map(fn (ReviewTarget $target) => [
            'target' => $target,
            'review' => $reviews->first(fn (Review $review) => $target->matches($review->target_type, (int) $review->target_ref)),
        ]);

        return ['eligible' => $reason === null, 'reason' => $reason, 'items' => $items];
    }

    /** Jumlah target yang belum diulas (0 bila booking belum layak). Untuk penanda di daftar booking. */
    public function openCount(CustomerAccount $account, Booking $booking): int
    {
        if ($this->eligibilityProblem($account, $booking) !== null) {
            return 0;
        }

        return $this->opportunities($account, $booking)['items']->whereNull('review')->count();
    }

    /**
     * Kirim ulasan baru. Status selalu "Menunggu persetujuan".
     *
     * @param  array{rating: mixed, comment?: ?string}  $data
     * @param  list<UploadedFile>  $photos
     */
    public function create(CustomerAccount $account, Booking $booking, string $targetKey, array $data, array $photos = []): Review
    {
        [$rating, $comment] = $this->cleanInput($data);
        $this->assertPhotos($photos, 0);

        $review = DB::transaction(function () use ($account, $booking, $targetKey, $rating, $comment) {
            // Kunci baris booking: dua kiriman bersamaan untuk booking yang sama diproses bergiliran
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $target = $this->assertCanReview($account, $booking, $targetKey);

            if (Review::query()->where('booking_id', $booking->id)->forTarget($target->type, $target->id())->exists()) {
                throw new ReviewException('Anda sudah mengirim ulasan untuk '.$target->name().' dari booking ini.');
            }

            try {
                return Review::create([
                    ...$target->columns(),
                    'customer_account_id' => $account->id,
                    'booking_id' => $booking->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'reviewer_name' => Review::displayNameFor($account->name),
                    'status' => ReviewStatus::Pending,
                    'submitted_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new ReviewException('Anda sudah mengirim ulasan untuk '.$target->name().' dari booking ini.');
            }
        });

        try {
            $this->attachPhotos($review, $photos);
        } catch (Throwable $e) {
            // Foto gagal diproses: ulasan dibatalkan seluruhnya agar tidak setengah tersimpan
            $review->delete();
            throw $e instanceof ReviewException ? $e : new ReviewException('Foto tidak bisa diproses. Coba foto lain (JPG, PNG, atau WebP).');
        }

        return $review->refresh();
    }

    /**
     * Ubah ulasan sendiri. Kelayakan dicek ulang dan ulasan kembali "Menunggu persetujuan"
     * (hilang dari website sampai disetujui lagi).
     *
     * @param  array{rating: mixed, comment?: ?string}  $data
     * @param  list<UploadedFile>  $photos  foto baru
     * @param  list<int>  $removeMediaIds  foto lama yang dihapus
     */
    public function update(CustomerAccount $account, Review $review, array $data, array $photos = [], array $removeMediaIds = []): Review
    {
        [$rating, $comment] = $this->cleanInput($data);
        if ($review->customer_account_id !== $account->id) {
            throw new ReviewException('Ulasan tidak ditemukan.');
        }

        // Hanya foto milik ulasan ini yang bisa dihapus; ID lain diabaikan
        $remove = $review->getMedia('photos')->whereIn('id', array_map('intval', $removeMediaIds));
        $this->assertPhotos($photos, $review->getMedia('photos')->count() - $remove->count());

        $review = DB::transaction(function () use ($account, $review, $rating, $comment) {
            $review = Review::query()->whereKey($review->id)->lockForUpdate()->firstOrFail();
            if ($review->customer_account_id !== $account->id) {
                throw new ReviewException('Ulasan tidak ditemukan.');
            }

            $booking = Booking::query()->findOrFail($review->booking_id);
            $this->assertCanReview($account, $booking, $this->targetKeyOf($review));

            $review->update([
                'rating' => $rating,
                'comment' => $comment,
                'reviewer_name' => Review::displayNameFor($account->name),
                'status' => ReviewStatus::Pending,
                'published_at' => null,
                'edited_at' => now(),
                'customer_message' => null,
            ]);

            return $review;
        });

        $remove->each->delete();
        $this->attachPhotos($review, $photos);

        return $review->refresh();
    }

    /** Hapus ulasan sendiri beserta fotonya. Rata-rata publik otomatis ikut berubah (dihitung langsung). */
    public function delete(CustomerAccount $account, Review $review): void
    {
        if ($review->customer_account_id !== $account->id) {
            throw new ReviewException('Ulasan tidak ditemukan.');
        }

        // Model event media library menghapus file foto & konversinya
        $review->delete();
    }

    public function approve(Review $review, User $moderator): Review
    {
        return $this->moveTo($review, ReviewStatus::Published, $moderator, [
            'published_at' => now(),
            'customer_message' => null,
        ]);
    }

    /** Tolak ulasan yang menunggu. Catatan internal wajib; pesan untuk pelanggan opsional. */
    public function reject(Review $review, User $moderator, string $note, ?string $customerMessage = null): Review
    {
        if (blank($note)) {
            throw new ReviewException('Isi alasan penolakan (catatan internal).');
        }

        return $this->moveTo($review, ReviewStatus::Rejected, $moderator, [
            'published_at' => null,
            'moderation_note' => trim($note),
            'customer_message' => filled($customerMessage) ? Str::limit(trim($customerMessage), 500, '') : null,
        ]);
    }

    /** Sembunyikan ulasan yang sudah tayang. */
    public function hide(Review $review, User $moderator, string $note): Review
    {
        if (blank($note)) {
            throw new ReviewException('Isi alasan menyembunyikan ulasan (catatan internal).');
        }

        return $this->moveTo($review, ReviewStatus::Hidden, $moderator, [
            'published_at' => null,
            'moderation_note' => trim($note),
        ]);
    }

    /**
     * Rata-rata, jumlah, dan sebaran bintang dari ulasan tayang.
     *
     * @return array{average: ?float, count: int, distribution: array<int, int>}
     */
    public function summary(ReviewTargetType $type, ?int $id = null): array
    {
        $counts = Review::query()->published()->forTarget($type, $id)
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = (int) ($counts[$star] ?? 0);
        }
        $count = array_sum($distribution);
        $sum = array_sum(array_map(fn ($star, $total) => $star * $total, array_keys($distribution), $distribution));

        return [
            'average' => $count > 0 ? round($sum / $count, 1) : null,
            'count' => $count,
            'distribution' => $distribution,
        ];
    }

    /**
     * Ringkasan beberapa target sekaligus (satu query), mis. semua aktivitas di halaman Aktivitas.
     *
     * @param  list<int>  $ids
     * @return Collection<int, array{average: float, count: int}> dikunci id target; target tanpa ulasan tayang tidak ada
     */
    public function summaries(ReviewTargetType $type, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Review::query()->published()->where('target_type', $type)->whereIn('target_ref', $ids)
            ->selectRaw('target_ref, AVG(rating) as average, COUNT(*) as total')->groupBy('target_ref')->get()
            ->mapWithKeys(fn ($row) => [(int) $row->target_ref => ['average' => round((float) $row->average, 1), 'count' => (int) $row->total]]);
    }

    /** Ulasan tayang untuk halaman publik (berhalaman). */
    public function publishedReviews(ReviewTargetType $type, ?int $id, string $sort = 'terbaru', ?int $stars = null, ?int $perPage = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = Review::query()->published()->forTarget($type, $id)
            ->when($stars >= 1 && $stars <= 5, fn ($q) => $q->where('rating', $stars))
            ->with('media');

        match ($sort) {
            'tertinggi' => $query->orderByDesc('rating')->orderByDesc('published_at'),
            'terendah' => $query->orderBy('rating')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        return $query->orderByDesc('id')->paginate($perPage ?? (int) config('resort.reviews.per_page', 8), pageName: $pageName)->withQueryString();
    }

    /** Kunci target dari ulasan yang sudah ada (untuk cek ulang kelayakan saat diubah). */
    public function targetKeyOf(Review $review): string
    {
        return $review->target_type === ReviewTargetType::Resort ? 'resort' : $review->target_type->slug().'-'.$review->target_ref;
    }

    private function assertCanReview(CustomerAccount $account, Booking $booking, string $targetKey): ReviewTarget
    {
        if ($problem = $this->eligibilityProblem($account, $booking)) {
            throw new ReviewException($problem);
        }

        return $this->findTarget($booking, $targetKey)
            ?? throw new ReviewException('Yang ingin Anda ulas tidak termasuk dalam booking ini.');
    }

    /** @return array{0: int, 1: ?string} */
    private function cleanInput(array $data): array
    {
        $rating = filter_var($data['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        if ($rating === false) {
            throw new ReviewException('Pilih penilaian 1 sampai 5 bintang.');
        }

        // Teks polos: tag HTML dibuang, tampilan memakai {{ }} (di-escape)
        $comment = trim(strip_tags((string) ($data['comment'] ?? '')));
        $max = (int) config('resort.reviews.comment_max', 2000);
        if (mb_strlen($comment) > $max) {
            throw new ReviewException("Ulasan maksimal {$max} karakter.");
        }

        return [$rating, $comment === '' ? null : $comment];
    }

    /**
     * Periksa isi file sungguhan (bukan nama/ekstensi/MIME dari browser): jenis, ukuran, dimensi.
     *
     * @param  list<UploadedFile>  $photos
     */
    private function assertPhotos(array $photos, int $existing): void
    {
        $max = (int) config('resort.reviews.max_photos', 4);
        if ($existing + count($photos) > $max) {
            throw new ReviewException("Maksimal {$max} foto per ulasan.");
        }

        $maxBytes = (int) config('resort.reviews.photo_max_kb', 5120) * 1024;
        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile || ! $photo->isValid() && ! is_file((string) $photo->getRealPath())) {
                throw new ReviewException('Foto gagal diunggah. Silakan coba lagi.');
            }
            if ($photo->getSize() > $maxBytes) {
                throw new ReviewException('Ukuran foto maksimal '.round($maxBytes / 1048576).' MB.');
            }
            if (self::detectImageExtension((string) $photo->getRealPath()) === null) {
                throw new ReviewException('Foto harus berformat JPG, PNG, atau WebP.');
            }
        }
    }

    /** Ekstensi aman dari isi file (jpg/png/webp), atau null bila bukan gambar yang didukung / ukurannya tidak wajar. */
    public static function detectImageExtension(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info || $info[0] < 100 || $info[1] < 100 || $info[0] > 8000 || $info[1] > 8000) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        return match (true) {
            $info[2] === IMAGETYPE_JPEG && $mime === 'image/jpeg' => 'jpg',
            $info[2] === IMAGETYPE_PNG && $mime === 'image/png' => 'png',
            $info[2] === IMAGETYPE_WEBP && $mime === 'image/webp' => 'webp',
            default => null,
        };
    }

    /** @param  list<UploadedFile>  $photos */
    private function attachPhotos(Review $review, array $photos): void
    {
        foreach ($photos as $photo) {
            $path = (string) $photo->getRealPath();
            $extension = self::detectImageExtension($path) ?? throw new ReviewException('Foto harus berformat JPG, PNG, atau WebP.');

            // Nama file acak dari server; nama asli dari perangkat tamu tidak disimpan
            $review->addMedia($path)
                ->usingName('Foto ulasan')
                ->usingFileName(Str::random(32).'.'.$extension)
                ->toMediaCollection('photos');
        }
    }

    private function moveTo(Review $review, ReviewStatus $to, User $moderator, array $changes): Review
    {
        return DB::transaction(function () use ($review, $to, $moderator, $changes) {
            $review = Review::query()->whereKey($review->id)->lockForUpdate()->firstOrFail();

            if (! $review->status->canMoveTo($to)) {
                throw new ReviewException("Ulasan berstatus \"{$review->status->getLabel()}\" tidak bisa diubah menjadi \"{$to->getLabel()}\".");
            }

            $review->update([
                ...$changes,
                'status' => $to,
                'moderated_at' => now(),
                'moderated_by' => $moderator->id,
            ]);

            return $review;
        });
    }
}
