<?php

namespace App\Http\Controllers;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Cafe\Models\Category;
use Illuminate\Http\Response;

/**
 * Docs/Spec.md §10 SEO: only cafe `active` may be indexable. Category+city
 * combinations are only listed when they actually have results, so we never
 * point crawlers at a thin/empty page (Docs/plan.md Task 5.1).
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $cafes = Cafe::query()->where('status', CafeStatus::Active)->get(['city', 'slug', 'updated_at']);

        $categorySlugsWithActiveCafes = Category::query()
            ->whereHas('cafes', fn ($query) => $query->where('status', CafeStatus::Active))
            ->pluck('slug');

        $urls = collect();

        $urls->push(['loc' => url('/'), 'lastmod' => null]);
        $urls->push(['loc' => route('search'), 'lastmod' => null]);

        foreach ($cafes as $cafe) {
            $urls->push(['loc' => route('cafe.show', ['city' => $cafe->city, 'slug' => $cafe->slug]), 'lastmod' => $cafe->updated_at]);
        }

        foreach ($categorySlugsWithActiveCafes as $categorySlug) {
            $urls->push(['loc' => route('category-city', ['city' => 'makassar', 'categorySlug' => $categorySlug]), 'lastmod' => null]);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$urls->map(function (array $url): string {
                $entry = '<url><loc>'.e($url['loc']).'</loc>';
                if ($url['lastmod']) {
                    $entry .= '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>';
                }

                return $entry.'</url>';
            })->implode('')
            .'</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
