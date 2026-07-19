<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Actions\SubmitReview;
use App\Domain\Review\Exceptions\ReviewLimitExceeded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ReviewRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_fourth_review_in_an_hour_is_rejected(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 3) as $index) {
            app(SubmitReview::class)->handle($user, Cafe::factory()->create(), 4, "Review nomor {$index} memiliki cerita yang cukup panjang dan berbeda untuk pengujian.");
        }

        $this->expectException(ReviewLimitExceeded::class);
        app(SubmitReview::class)->handle($user, Cafe::factory()->create(), 4, 'Review keempat ini semestinya ditolak oleh batas tiga review per jam.');
    }

    public function test_eleventh_review_in_a_day_is_rejected(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 10) as $attempt) {
            RateLimiter::hit("review:day:{$user->id}", 86400);
        }

        $this->expectException(ReviewLimitExceeded::class);
        app(SubmitReview::class)->handle($user, Cafe::factory()->create(), 4, 'Review kesebelas semestinya ditolak oleh batas harian yang sudah habis.');
    }
}
