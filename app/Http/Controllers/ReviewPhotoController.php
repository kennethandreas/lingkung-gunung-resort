<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Foto ulasan (disk privat "ulasan"). Hanya versi WebP hasil konversi yang dikirim, bukan file asli.
 * - Ulasan tayang: semua pengunjung.
 * - Belum tayang (menunggu/ditolak/disembunyikan): hanya pemilik ulasan atau staf dengan izin melihat ulasan.
 * Selain itu 404, supaya keberadaan foto tidak terbaca.
 */
class ReviewPhotoController extends Controller
{
    public function __invoke(Request $request, string $uuid, string $size): BinaryFileResponse
    {
        abort_unless(array_key_exists($size, Review::PHOTO_SIZES), 404);

        $media = Media::query()
            ->where('uuid', $uuid)
            ->where('model_type', (new Review)->getMorphClass())
            ->where('collection_name', 'photos')
            ->firstOrFail();
        $review = Review::query()->find($media->model_id) ?? abort(404);

        $public = $review->isPublished();
        if (! $public) {
            $customer = $request->user('customer');
            $staff = $request->user('web');
            abort_unless(($customer && $customer->id === $review->customer_account_id) || $staff?->can('view', $review), 404);
        }

        abort_unless($media->hasGeneratedConversion($size), 404);
        $path = $media->getPath($size);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => $public ? 'public, max-age=3600' : 'private, no-store',
        ]);
    }
}
