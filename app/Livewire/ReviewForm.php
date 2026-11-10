<?php

namespace App\Livewire;

use App\Exceptions\ReviewException;
use App\Models\Booking;
use App\Models\Review;
use App\Services\CustomerAccountService;
use App\Services\ReviewService;
use App\Support\ReviewTarget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Form tulis/ubah/hapus ulasan pelanggan. Validasi tampilan di sini; aturan sebenarnya
 * (kepemilikan booking, check-in & check-out, target, duplikat, isi foto) dicek ulang oleh ReviewService.
 * Booking & target selalu dicari ulang dari akun yang login, tidak pernah dipercaya dari browser.
 */
class ReviewForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $bookingCode;

    #[Locked]
    public string $targetKey;

    public ?int $rating = null;

    public string $comment = '';

    /** Pilihan file terbaru dari input (dipindah ke $photos setelah lolos validasi). */
    public array $upload = [];

    /** @var list<TemporaryUploadedFile> foto baru yang akan dikirim */
    public array $photos = [];

    /** @var list<int> foto lama yang ditandai untuk dihapus */
    public array $removeMediaIds = [];

    public ?string $errorMessage = null;

    public function mount(string $bookingCode, string $targetKey): void
    {
        $this->bookingCode = $bookingCode;
        $this->targetKey = $targetKey;
        [, , $review] = $this->resolve();

        if ($review) {
            $this->rating = $review->rating;
            $this->comment = (string) $review->comment;
        }
    }

    public function updatedUpload(): void
    {
        $this->errorMessage = null;
        $this->validate(['upload.*' => $this->photoRules()], $this->messages(), ['upload.*' => 'foto']);

        $room = $this->photoLimit() - $this->existingCount() - count($this->photos);
        if (count($this->upload) > $room) {
            $this->addError('upload', 'Maksimal '.$this->photoLimit().' foto per ulasan.');
        }

        $this->photos = array_values(array_merge($this->photos, array_slice($this->upload, 0, max(0, $room))));
        $this->upload = [];
    }

    public function removeUpload(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function toggleExisting(int $mediaId): void
    {
        $this->removeMediaIds = in_array($mediaId, $this->removeMediaIds, true)
            ? array_values(array_diff($this->removeMediaIds, [$mediaId]))
            : [...$this->removeMediaIds, $mediaId];
    }

    public function save(ReviewService $reviews)
    {
        $this->errorMessage = null;
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:'.(int) config('resort.reviews.comment_max', 2000)],
            'photos' => ['array', 'max:'.$this->photoLimit()],
            'photos.*' => $this->photoRules(),
        ], $this->messages(), ['photos.*' => 'foto']);

        $account = Auth::guard('customer')->user();
        $key = 'ulasan-kirim:'.$account->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->errorMessage = 'Terlalu banyak percobaan. Silakan coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.';

            return null;
        }
        RateLimiter::hit($key, 600);

        [$booking, , $review] = $this->resolve();
        $data = ['rating' => $this->rating, 'comment' => $this->comment];

        try {
            $review
                ? $reviews->update($account, $review, $data, $this->photos, $this->removeMediaIds)
                : $reviews->create($account, $booking, $this->targetKey, $data, $this->photos);
        } catch (ReviewException $e) {
            $this->errorMessage = $e->getMessage();

            return null;
        }

        session()->flash('status', $review
            ? 'Ulasan Anda diperbarui dan akan tayang lagi setelah disetujui tim kami.'
            : 'Terima kasih! Ulasan Anda terkirim dan akan tayang setelah disetujui tim kami.');

        return $this->redirect(route('akun.booking.show', $booking->code).'#ulasan');
    }

    public function deleteReview(ReviewService $reviews)
    {
        [$booking, , $review] = $this->resolve();
        if (! $review) {
            return null;
        }

        try {
            $reviews->delete(Auth::guard('customer')->user(), $review);
        } catch (ReviewException $e) {
            $this->errorMessage = $e->getMessage();

            return null;
        }

        session()->flash('status', 'Ulasan Anda sudah dihapus.');

        return $this->redirect(route('akun.booking.show', $booking->code).'#ulasan');
    }

    public function render(): View
    {
        [$booking, $target, $review] = $this->resolve();

        return view('livewire.review-form', [
            'booking' => $booking,
            'target' => $target,
            'review' => $review,
            'existingPhotos' => $review?->getMedia('photos') ?? collect(),
            'photoLimit' => $this->photoLimit(),
            'photoMaxMb' => round((int) config('resort.reviews.photo_max_kb', 5120) / 1024),
            'commentMax' => (int) config('resort.reviews.comment_max', 2000),
        ]);
    }

    /** @return array{0: Booking, 1: ReviewTarget, 2: ?Review} */
    private function resolve(): array
    {
        $account = Auth::guard('customer')->user() ?? abort(403);
        $booking = app(CustomerAccountService::class)->findOwnedBooking($account, $this->bookingCode) ?? abort(404);
        $target = app(ReviewService::class)->findTarget($booking, $this->targetKey) ?? abort(404);

        $review = Review::query()->with('media')->where('booking_id', $booking->id)->where('customer_account_id', $account->id)
            ->forTarget($target->type, $target->id())->first();

        return [$booking, $target, $review];
    }

    private function existingCount(): int
    {
        [, , $review] = $this->resolve();

        return $review ? $review->getMedia('photos')->whereNotIn('id', $this->removeMediaIds)->count() : 0;
    }

    private function photoLimit(): int
    {
        return (int) config('resort.reviews.max_photos', 4);
    }

    private function photoRules(): array
    {
        return [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:'.(int) config('resort.reviews.photo_max_kb', 5120),
            'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000',
        ];
    }

    protected function messages(): array
    {
        $maxMb = round((int) config('resort.reviews.photo_max_kb', 5120) / 1024);

        return [
            'rating.required' => 'Pilih penilaian 1 sampai 5 bintang.',
            'rating.integer' => 'Pilih penilaian 1 sampai 5 bintang.',
            'rating.between' => 'Pilih penilaian 1 sampai 5 bintang.',
            'comment.max' => 'Ulasan maksimal :max karakter.',
            'photos.max' => 'Maksimal :max foto per ulasan.',
            '*.image' => 'File harus berupa foto (JPG, PNG, atau WebP).',
            '*.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            '*.mimetypes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            '*.max' => "Ukuran foto maksimal {$maxMb} MB.",
            '*.dimensions' => 'Ukuran foto terlalu kecil (min. 100 px) atau terlalu besar (maks. 8000 px).',
            '*.uploaded' => "Foto gagal diunggah. Pastikan ukurannya di bawah {$maxMb} MB lalu coba lagi.",
        ];
    }
}
