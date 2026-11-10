<?php

namespace App\Http\Controllers;

use App\Enums\ReviewTargetType;
use App\Models\Activity;
use App\Models\AdditionalItem;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\GroupPackage;
use App\Models\MenuSet;
use App\Models\Page;
use App\Models\Program;
use App\Models\UnitType;
use App\Services\PricingService;
use App\Services\ReviewService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman publik (hanya membaca data; harga dari PricingService).
 * Semua isi dikelola dari admin panel.
 */
class PublicPageController extends Controller
{
    public function __construct(private PricingService $pricing, private ReviewService $reviews) {}

    public function home(): View
    {
        return view('public.home', [
            'resortRating' => $this->reviews->summary(ReviewTargetType::Resort),
            'hero' => Page::query()->where('slug', 'beranda')->first(),
            'banner' => Banner::query()->live()->first(),
            'unitTypes' => $this->unitTypes(),
            'gallery' => GalleryPhoto::query()->visible()->where('is_featured', true)->with('media')->take(5)->get(),
            'faqs' => Faq::query()->visible()->take(6)->get(),
            'articles' => Article::query()->published()->with('media')->take(3)->get(),
            'activities' => Activity::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'packages' => GroupPackage::query()->where('is_active', true)->with(['variants' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get(),
        ]);
    }

    public function accommodations(): View
    {
        return view('public.accommodations', ['unitTypes' => $this->unitTypes(), 'weekendLabel' => $this->pricing->weekendLabel()]);
    }

    public function accommodation(UnitType $unitType): View
    {
        abort_unless($unitType->is_active, 404);
        $unitType->load(['rates', 'media']);

        return view('public.accommodation', [
            'unitType' => $unitType,
            'prices' => $this->pricing->displayPrices($unitType),
            'photos' => $unitType->getMedia('photos'),
            'others' => $this->unitTypes()->reject(fn ($row) => $row['model']->is($unitType))->take(3),
            // Ulasan khusus tipe unit ini (bukan rating resort secara umum)
            'reviewSummary' => $this->reviews->summary(ReviewTargetType::UnitType, $unitType->id),
            'latestReviews' => $this->reviews->publishedReviews(ReviewTargetType::UnitType, $unitType->id, perPage: 4),
        ]);
    }

    public function play(): View
    {
        $activities = Activity::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('public.play', [
            'groups' => Program::query()->visible('play')->with('media')->get()->toBase()->groupBy('group'),
            'activities' => $activities,
            'activityRatings' => $this->reviews->summaries(ReviewTargetType::Activity, $activities->modelKeys()),
        ]);
    }

    public function learn(): View
    {
        return view('public.learn', [
            'groups' => Program::query()->visible('learn')->with('media')->get()->toBase()->groupBy('group'),
            'sanggar' => Activity::query()->where('is_active', true)->where('category', 'sanggar_alam')->orderBy('sort_order')->get(),
        ]);
    }

    public function gather(): View
    {
        $packages = GroupPackage::query()->where('is_active', true)->with(['variants' => fn ($q) => $q->where('is_active', true)->with('menuSet')])->orderBy('sort_order')->get();

        return view('public.gather', [
            'groups' => Program::query()->visible('gather')->with('media')->get()->toBase()->groupBy('group'),
            'packages' => $packages,
            'packageRatings' => $this->reviews->summaries(ReviewTargetType::GroupPackage, $packages->modelKeys()),
            'halls' => AdditionalItem::query()->where('is_active', true)->where('category', 'aula')->orderBy('sort_order')->get(),
            'itinerary' => Page::query()->where('slug', 'contoh-itinerary-2-hari')->where('is_published', true)->first(),
        ]);
    }

    public function taste(): View
    {
        return view('public.taste', [
            'menuSets' => MenuSet::query()->with('options')->orderBy('code')->get(),
            'photo' => GalleryPhoto::query()->visible()->where('category', 'kuliner')->with('media')->first(),
        ]);
    }

    public function route(): View
    {
        return view('public.route');
    }

    public function contact(): View
    {
        return view('public.contact', [
            'faqs' => Faq::query()->visible()->get()->groupBy('category'),
        ]);
    }

    public function gallery(): View
    {
        return view('public.gallery', [
            'photos' => GalleryPhoto::query()->visible()->with('media')->get(),
        ]);
    }

    public function page(Page $page): View
    {
        abort_if(! $page->is_published || $page->slug === 'beranda', 404);

        return view('public.page', ['page' => $page]);
    }

    /** @return Collection<int, array> */
    private function unitTypes(): Collection
    {
        return UnitType::query()->active()->ordered()->with(['rates', 'media'])->get()->map(fn (UnitType $type) => [
            'model' => $type,
            'prices' => $this->pricing->displayPrices($type),
            'cover' => $type->getFirstMedia('photos'),
            'unit' => match ($type->pricing_unit->value) {
                'person' => 'orang/malam',
                'tent' => 'tenda/malam',
                'plot' => 'kavling/malam',
                default => 'kamar/malam',
            },
        ]);
    }
}
