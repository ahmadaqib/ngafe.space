<?php

namespace App\Domain\Cafe\Actions;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Cafe\Models\Category;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Support\Facades\DB;

final class SyncHiddenGem
{
    public function handle(Cafe $cafe, ?int $publishedReviewCount = null): void
    {
        $category = Category::query()
            ->where('slug', config('ngafe.hidden_gem.category_slug', 'hidden-gem-baru-buka'))
            ->first();

        if ($category === null) {
            return;
        }

        $publishedReviewCount ??= $cafe->reviews()
            ->where('status', ReviewStatus::Published->value)
            ->count();

        $isActive = $cafe->status === CafeStatus::Active;
        $isNew = $cafe->created_at !== null
            && $cafe->created_at->gt(now()->subDays((int) config('ngafe.hidden_gem.max_age_days', 90)));
        $hasFewReviews = $publishedReviewCount < (int) config('ngafe.hidden_gem.max_published_reviews', 10);
        $shouldBeHiddenGem = $isActive && ($isNew || $hasFewReviews);

        $pivot = DB::table('cafe_category')
            ->where('cafe_id', $cafe->id)
            ->where('category_id', $category->id)
            ->first();

        if ($shouldBeHiddenGem && $pivot === null) {
            DB::table('cafe_category')->insert([
                'cafe_id' => $cafe->id,
                'category_id' => $category->id,
                'source' => 'auto',
                'confidence' => 1,
            ]);
        }

        if (! $shouldBeHiddenGem && $pivot?->source === 'auto') {
            DB::table('cafe_category')
                ->where('cafe_id', $cafe->id)
                ->where('category_id', $category->id)
                ->where('source', 'auto')
                ->delete();
        }
    }
}
