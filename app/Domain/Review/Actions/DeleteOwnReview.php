<?php

namespace App\Domain\Review\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use Illuminate\Auth\Access\AuthorizationException;

final class DeleteOwnReview
{
    public function handle(User $user, Review $review): void
    {
        if ((string) $review->user_id !== (string) $user->id) {
            throw new AuthorizationException;
        }

        $snapshot = clone $review;
        $review->delete();
        ReviewStatusChanged::dispatch($snapshot, $snapshot->status);
    }
}
