<?php

namespace App\Jobs;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecomputeCafeAggregates implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $cafeId) {}

    public function handle(): void
    {
        $aggregate = Review::query()
            ->where('cafe_id', $this->cafeId)
            ->where('status', ReviewStatus::Published->value)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as rating_average, COALESCE(SUM(rating), 0) as rating_sum')
            ->first();

        $count = (int) $aggregate->getAttribute('review_count');
        $sum = (float) $aggregate->getAttribute('rating_sum');
        $priorMean = (float) config('ngafe.ranking.bayesian_prior_mean', 3.8);
        $priorWeight = (int) config('ngafe.ranking.bayesian_prior_weight', 5);
        $qualityScore = (($priorWeight * $priorMean) + $sum) / ($priorWeight + $count);

        Cafe::query()->whereKey($this->cafeId)->update([
            'rating_avg' => $count > 0 ? round((float) $aggregate->getAttribute('rating_average'), 2) : null,
            'rating_count' => $count,
            'quality_score' => round($qualityScore, 4),
        ]);
    }
}
