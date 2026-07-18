<?php

namespace App\Domain\Cafe\Actions;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Support\Facades\DB;

final class SyncCrowdCategories
{
    private const THRESHOLD = 0.30;

    public function handle(Cafe $cafe): void
    {
        $publishedReviewCount = $cafe->reviews()
            ->where('status', ReviewStatus::Published->value)
            ->count();

        $tagCounts = DB::table('review_tags')
            ->join('reviews', 'reviews.id', '=', 'review_tags.review_id')
            ->where('reviews.cafe_id', $cafe->id)
            ->where('reviews.status', ReviewStatus::Published->value)
            ->groupBy('review_tags.category_id')
            ->selectRaw('review_tags.category_id, COUNT(*) as tag_count')
            ->pluck('tag_count', 'category_id');

        $qualified = $tagCounts
            ->map(fn ($count) => $publishedReviewCount > 0 ? (int) $count / $publishedReviewCount : 0.0)
            ->filter(fn (float $confidence) => $confidence >= self::THRESHOLD);

        DB::transaction(function () use ($cafe, $qualified): void {
            $existingCrowdIds = DB::table('cafe_category')
                ->where('cafe_id', $cafe->id)
                ->where('source', 'crowd')
                ->pluck('category_id');

            foreach ($existingCrowdIds->diff($qualified->keys()) as $categoryId) {
                DB::table('cafe_category')
                    ->where('cafe_id', $cafe->id)
                    ->where('category_id', $categoryId)
                    ->where('source', 'crowd')
                    ->delete();
            }

            foreach ($qualified as $categoryId => $confidence) {
                $existing = DB::table('cafe_category')
                    ->where('cafe_id', $cafe->id)
                    ->where('category_id', $categoryId)
                    ->first();

                if ($existing === null) {
                    DB::table('cafe_category')->insert([
                        'cafe_id' => $cafe->id,
                        'category_id' => $categoryId,
                        'source' => 'crowd',
                        'confidence' => round($confidence, 3),
                    ]);
                } elseif ($existing->source === 'crowd') {
                    DB::table('cafe_category')
                        ->where('cafe_id', $cafe->id)
                        ->where('category_id', $categoryId)
                        ->update(['confidence' => round($confidence, 3)]);
                }
            }
        });
    }
}
