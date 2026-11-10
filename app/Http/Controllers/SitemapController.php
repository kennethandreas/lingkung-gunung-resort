<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Page;
use App\Models\UnitType;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

/** Sitemap otomatis untuk Google Search Console (dokumen teknis 8.3). */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = Sitemap::create();

        foreach (['home', 'akomodasi.index', 'play', 'learn', 'gather', 'taste', 'galeri', 'artikel.index', 'ulasan.index', 'rute', 'kontak', 'pesan'] as $name) {
            $sitemap->add(Url::create(route($name))->setPriority($name === 'home' ? 1.0 : 0.8));
        }

        UnitType::query()->active()->ordered()->get()->each(
            fn (UnitType $type) => $sitemap->add(Url::create(route('akomodasi.show', $type))->setLastModificationDate($type->updated_at)->setPriority(0.9))
        );

        Article::query()->published()->get()->each(
            fn (Article $article) => $sitemap->add(Url::create(route('artikel.show', $article))->setLastModificationDate($article->updated_at)->setPriority(0.6))
        );

        Page::query()->where('is_published', true)->where('slug', '!=', 'beranda')->get()->each(
            fn (Page $page) => $sitemap->add(Url::create(route('halaman', $page))->setLastModificationDate($page->updated_at)->setPriority(0.4))
        );

        return $sitemap->toResponse(request());
    }
}
