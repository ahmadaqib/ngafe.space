<?php

namespace App\Http\Controllers;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
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

        return response()
            ->view('cafe.show', [
                'cafe' => $cafe,
                'opening' => OpeningHours::statusNow($cafe, CarbonImmutable::now()),
            ])
            ->header('Cache-Control', $user ? 'private, no-store' : 'public, max-age=300');
    }
}
