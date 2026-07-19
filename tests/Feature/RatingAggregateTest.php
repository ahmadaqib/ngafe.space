<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use App\Jobs\RecomputeCafeAggregates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RatingAggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_reviews_recompute_rating_count_average_and_bayesian_score(): void
    {
        $cafe = Cafe::factory()->create();
        $review = Review::factory()->create([
            'cafe_id' => $cafe->id,
            'rating' => 5,
            'status' => ReviewStatus::Published,
        ]);

        ReviewStatusChanged::dispatch($review, null);

        $cafe->refresh();
        $this->assertSame(1, $cafe->rating_count);
        $this->assertSame('5.00', $cafe->rating_avg);
        $this->assertSame('4.0000', $cafe->quality_score);
    }

    public function test_pending_and_removed_reviews_are_excluded_from_aggregates(): void
    {
        $cafe = Cafe::factory()->create();
        $review = Review::factory()->create([
            'cafe_id' => $cafe->id,
            'rating' => 4,
            'status' => ReviewStatus::Published,
        ]);
        ReviewStatusChanged::dispatch($review, null);

        $review->update(['status' => ReviewStatus::Pending]);
        ReviewStatusChanged::dispatch($review, ReviewStatus::Published);

        $cafe->refresh();
        $this->assertSame(0, $cafe->rating_count);
        $this->assertNull($cafe->rating_avg);
        $this->assertSame('3.8000', $cafe->quality_score);

        $review->update(['status' => ReviewStatus::Published]);
        ReviewStatusChanged::dispatch($review, ReviewStatus::Pending);
        $this->assertSame(1, $cafe->fresh()->rating_count);

        $review->update(['status' => ReviewStatus::Removed]);
        ReviewStatusChanged::dispatch($review, ReviewStatus::Published);
        $this->assertSame(0, $cafe->fresh()->rating_count);
    }

    public function test_recompute_job_is_idempotent(): void
    {
        $cafe = Cafe::factory()->create();
        Review::factory()->create(['cafe_id' => $cafe->id, 'rating' => 4]);

        $job = new RecomputeCafeAggregates($cafe->id);
        $job->handle();
        $first = $cafe->fresh()->only(['rating_avg', 'rating_count', 'quality_score']);

        $job->handle();

        $this->assertSame($first, $cafe->fresh()->only(['rating_avg', 'rating_count', 'quality_score']));
    }

    public function test_public_cafe_page_uses_denormalized_values_without_request_time_aggregates(): void
    {
        $cafe = Cafe::factory()->create([
            'slug' => 'tanpa-agregasi-request',
            'rating_avg' => 4.5,
            'rating_count' => 2,
            'quality_score' => 4.1,
        ]);
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get('/makassar/tanpa-agregasi-request')
            ->assertOk()
            ->assertSee('4,5 · 2 review');

        $this->assertFalse(collect($queries)->contains(
            fn (string $sql) => str_contains($sql, 'avg(') || str_contains($sql, 'sum('),
        ));
    }

    public function test_cafe_without_rating_uses_the_empty_rating_copy(): void
    {
        $cafe = Cafe::factory()->create([
            'slug' => 'belum-direview',
            'rating_avg' => null,
            'rating_count' => 0,
        ]);

        $this->get('/makassar/belum-direview')
            ->assertOk()
            ->assertSee('Belum ada review — jadi yang pertama?')
            ->assertDontSee('0.0');
    }
}
