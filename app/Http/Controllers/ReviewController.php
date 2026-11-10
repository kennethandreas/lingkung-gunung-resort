<?php

namespace App\Http\Controllers;

use App\Enums\ReviewTargetType;
use App\Services\ReviewService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman ulasan publik: resort secara umum (/ulasan) dan per aktivitas / paket / akomodasi. Hanya ulasan tayang. */
class ReviewController extends Controller
{
    public const SORTS = ['terbaru' => 'Terbaru', 'tertinggi' => 'Nilai tertinggi', 'terendah' => 'Nilai terendah'];

    public function __construct(private ReviewService $reviews) {}

    public function index(Request $request): View
    {
        return $this->render($request, ReviewTargetType::Resort, null);
    }

    public function target(Request $request, string $jenis, string $slug): View
    {
        $type = ReviewTargetType::fromSlug($jenis);
        abort_if(! $type || $type === ReviewTargetType::Resort, 404);

        $model = $type->modelClass()::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return $this->render($request, $type, $model);
    }

    private function render(Request $request, ReviewTargetType $type, ?Model $model): View
    {
        $sort = array_key_exists((string) $request->query('urut'), self::SORTS) ? (string) $request->query('urut') : 'terbaru';
        $stars = (int) $request->query('bintang');
        $stars = $stars >= 1 && $stars <= 5 ? $stars : null;
        $id = $model?->getKey();

        return view('public.reviews', [
            'type' => $type,
            'target' => $model,
            'summary' => $this->reviews->summary($type, $id),
            'reviews' => $this->reviews->publishedReviews($type, $id, $sort, $stars),
            'sort' => $sort,
            'stars' => $stars,
        ]);
    }
}
