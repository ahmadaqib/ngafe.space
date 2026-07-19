<?php

namespace App\Domain\Cafe\Queries;

use App\Domain\Cafe\Models\Cafe;
use Illuminate\Support\Collection;

final class HomeSections
{
    public function trending(string $city = 'makassar'): Collection
    {
        return Cafe::query()
            ->where(['city' => $city, 'status' => 'active'])
            ->whereHas('reviews', fn ($query) => $query->where('status', 'published'))
            ->with([
                'categories',
                'photos' => fn ($query) => $query->where('status', 'published'),
                'reviews' => fn ($query) => $query->where('status', 'published')->latest()->limit(1),
            ])
            ->orderByDesc('trending_score')
            ->orderByDesc('rating_count')
            ->limit(10)
            ->get();
    }
}
