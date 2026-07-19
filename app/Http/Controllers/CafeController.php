<?php

namespace App\Http\Controllers;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Cafe\Support\CafeJsonLd;
use App\Domain\Cafe\Support\OpeningHours;
use Carbon\CarbonImmutable;

class CafeController extends Controller
{
    public function show(string $city, string $slug)
    {
        $user = request()->user();
        $cafe = Cafe::query()
            ->where(['city' => $city, 'slug' => $slug, 'status' => CafeStatus::Active])
            ->with([
                'categories',
                'photos' => fn ($query) => $query->where('status', 'published'),
                'reviews' => fn ($query) => $query
                    ->where(function ($visibility) use ($user): void {
                        $visibility->where('status', 'published');
                        if ($user) {
                            $visibility->orWhere('user_id', $user->id);
                        }
                    })
                    ->with('photos'),
            ])->firstOrFail();

        $canonical = route('cafe.show', ['city' => $cafe->city, 'slug' => $cafe->slug]);
        $image = $cafe->share_card_url ?: $cafe->photos->first()?->url_full;
        $categoryNames = $cafe->categories->take(2)->pluck('name')->join(', ');
        $description = trim("{$cafe->name} di ".ucfirst($cafe->area).', '.ucfirst($cafe->city)
            .($categoryNames ? " — {$categoryNames}." : '.')
            .($cafe->rating_count ? ' Rating '.number_format((float) $cafe->rating_avg, 1, ',', '.')." dari {$cafe->rating_count} review di ngafe.space." : ' Review jujur dari pengunjung asli di ngafe.space.'));

        return response()
            ->view('cafe.show', [
                'cafe' => $cafe,
                'opening' => OpeningHours::statusNow($cafe, CarbonImmutable::now()),
                'canonical' => $canonical,
                'metaDescription' => $description,
                'metaImage' => $image,
                'jsonLd' => CafeJsonLd::build($cafe, $canonical, $image),
            ])
            ->header('Cache-Control', $user ? 'private, no-store' : 'public, max-age=300');
    }
}
