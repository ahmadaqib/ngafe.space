<?php

namespace App\Domain\Review\Actions;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Support\AliasGenerator;
use App\Domain\Review\Support\ReviewGuards;
use Illuminate\Support\Facades\DB;

final class SubmitReview
{
    public function __construct(
        private AliasGenerator $aliasGenerator,
        private ReviewGuards $guards,
    ) {}

    /** @param list<int|string> $tagIds */
    public function handle(User $user, Cafe $cafe, int $rating, string $body, array $tagIds = []): Review
    {
        $contentHash = $this->guards->assertCanSubmit($user, $cafe, $body);
        $status = $this->guards->statusFor($user, $cafe, $rating, $body);

        $review = DB::transaction(function () use ($user, $cafe, $rating, $body, $tagIds, $contentHash, $status): Review {
            $review = Review::query()->create([
                'user_id' => $user->id,
                'cafe_id' => $cafe->id,
                'rating' => $rating,
                'body' => trim($body),
                'content_hash' => $contentHash,
                'display_alias' => $this->aliasGenerator->for($user, $cafe),
                'status' => $status,
            ]);
            $review->tags()->sync($tagIds);

            return $review;
        });

        $this->guards->recordSubmission($user);
        ReviewStatusChanged::dispatch($review, null);

        return $review->load('tags');
    }
}
