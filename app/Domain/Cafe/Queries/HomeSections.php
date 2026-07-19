<?php

namespace App\Domain\Cafe\Queries;

use App\Domain\Cafe\Models\Cafe;
use Illuminate\Support\Collection;

final class HomeSections
{
    public function trending(string $city = 'makassar'): Collection
    {
        return Cafe::query()->where(['city' => $city, 'status' => 'active'])->whereHas('reviews', fn ($q) => $q->where('status', 'published'))->with(['categories', 'photos'])->orderByDesc('trending_score')->orderByDesc('rating_count')->limit(10)->get();
    }
}
