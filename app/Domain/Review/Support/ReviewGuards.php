<?php

namespace App\Domain\Review\Support;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Exceptions\DuplicateReview;
use App\Domain\Review\Exceptions\DuplicateReviewContent;
use App\Domain\Review\Exceptions\ReviewLimitExceeded;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Support\Facades\RateLimiter;

final class ReviewGuards
{
    public function assertCanSubmit(User $user, Cafe $cafe, string $body): string
    {
        if ($user->status !== 'active') {
            throw new ReviewLimitExceeded('account-banned');
        }

        if (Review::query()->whereBelongsTo($user)->whereBelongsTo($cafe)->exists()) {
            throw new DuplicateReview;
        }

        foreach ($this->rateLimitKeys($user) as [$key, $maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ReviewLimitExceeded('rate-limit');
            }
        }

        $hash = $this->contentHash($body);
        if (Review::query()->whereBelongsTo($user)->where('content_hash', $hash)->exists()) {
            throw new DuplicateReviewContent;
        }

        return $hash;
    }

    public function recordSubmission(User $user): void
    {
        foreach ($this->rateLimitKeys($user) as [$key, $maxAttempts, $decaySeconds]) {
            RateLimiter::hit($key, $decaySeconds);
        }
    }

    public function assertCanEdit(User $user, Review $review, string $body): string
    {
        if ($user->status !== 'active') {
            throw new ReviewLimitExceeded('account-banned');
        }

        foreach ($this->rateLimitKeys($user) as [$key, $maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ReviewLimitExceeded('rate-limit');
            }
        }

        $hash = $this->contentHash($body);
        if (Review::query()
            ->whereBelongsTo($user)
            ->where('content_hash', $hash)
            ->whereKeyNot($review->id)
            ->exists()) {
            throw new DuplicateReviewContent;
        }

        return $hash;
    }

    public function statusFor(User $user, Cafe $cafe, int $rating, string $body): ReviewStatus
    {
        $containsBannedWord = collect(config('moderation.banned_words', []))
            ->contains(fn (string $word) => preg_match('/\b'.preg_quote($word, '/').'\b/iu', $body) === 1);

        $newAccountOneStarBurst = $rating === 1
            && $user->created_at?->greaterThan(now()->subDay())
            && Review::query()
                ->whereBelongsTo($cafe)
                ->where('rating', 1)
                ->where('created_at', '>=', now()->subHour())
                ->whereHas('user', fn ($query) => $query->where('created_at', '>=', now()->subDay()))
                ->count() >= 2;

        return ($containsBannedWord || $newAccountOneStarBurst)
            ? ReviewStatus::Pending
            : ReviewStatus::Published;
    }

    public function assertHoneypotEmpty(?string $website): void
    {
        if (filled($website)) {
            throw new DuplicateReviewContent('honeypot');
        }
    }

    public function contentHash(string $body): string
    {
        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower(trim($body))) ?? trim($body);

        return hash('sha256', $normalized);
    }

    /** @return list<array{string, int, int}> */
    private function rateLimitKeys(User $user): array
    {
        return [
            ["review:hour:{$user->id}", (int) config('rate_limits.review.per_hour'), 3600],
            ["review:day:{$user->id}", (int) config('rate_limits.review.per_day'), 86400],
        ];
    }
}
