<?php

namespace App\Domain\Review\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use App\Domain\Review\Support\ReviewGuards;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class EditReview
{
    public function __construct(private ReviewGuards $guards) {}

    /** @param list<int|string> $tagIds */
    public function handle(User $user, Review $review, int $rating, string $body, array $tagIds = []): Review
    {
        if ((string) $review->user_id !== (string) $user->id) {
            throw new AuthorizationException;
        }

        $previousStatus = $review->status;
        $contentHash = $this->guards->assertCanEdit($user, $review, $body);
        $status = $this->guards->statusFor($user, $review->cafe, $rating, $body);

        DB::transaction(function () use ($review, $rating, $body, $tagIds, $status, $contentHash): void {
            $review->update([
                'rating' => $rating,
                'body' => trim($body),
                'content_hash' => $contentHash,
                'status' => $status,
                'is_edited' => true,
            ]);
            $review->tags()->sync($tagIds);
        });

        $this->guards->recordSubmission($user);
        ReviewStatusChanged::dispatch($review, $previousStatus instanceof ReviewStatus ? $previousStatus : null);

        return $review->refresh()->load('tags');
    }
}
