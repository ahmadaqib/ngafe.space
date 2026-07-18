<?php

namespace App\Domain\Cafe\Queries;

use App\Domain\Cafe\Models\Cafe;
use Illuminate\Support\Collection;

final class SearchCafes
{
    public function run(?string $q, array $categorySlugs, ?float $lat, ?float $lng, ?string $area, string $city = 'makassar'): Collection
    {
        $query = Cafe::query()->where(['city' => $city, 'status' => 'active'])->with(['categories', 'photos']);
        foreach ($categorySlugs as $slug) {
            $query->whereHas('categories', fn ($categories) => $categories->where('slug', $slug));
        }
        if ($area) $query->where('area', $area);
        if (filled($q)) {
            if ($query->getConnection()->getDriverName() === 'pgsql') $query->whereRaw('name % ?', [$q])->orderByRaw('similarity(name, ?) DESC', [$q]);
            else $query->where('name', 'like', '%'.str_replace(' ', '%', trim($q)).'%');
        }
        if ($lat !== null && $lng !== null) {
            $cap = $query->getConnection()->getDriverName() === 'pgsql' ? 'least' : 'min';
            $query->selectRaw("*, (6371 * acos({$cap}(1.0, cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat))))) AS distance_km", [$lat, $lng, $lat])->orderBy('distance_km');
        } else $query->orderByDesc('quality_score')->orderByDesc('rating_count');
        return $query->get();
    }
}
