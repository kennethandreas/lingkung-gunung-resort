<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman publik artikel & kabar resort. */
class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $category = array_key_exists((string) $request->query('kategori'), Article::CATEGORIES) ? $request->query('kategori') : null;

        $articles = Article::query()->published()->with('media')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->paginate(9)
            ->withQueryString();

        return view('public.articles', [
            'articles' => $articles,
            'category' => $category,
            'categories' => collect(Article::CATEGORIES)->filter(fn ($label, $key) => Article::query()->published()->where('category', $key)->exists()),
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->is_published && $article->published_at?->lte(now()), 404);

        return view('public.article', [
            'article' => $article->load('media'),
            'related' => Article::query()->published()->with('media')->whereKeyNot($article->id)->take(3)->get(),
        ]);
    }
}
