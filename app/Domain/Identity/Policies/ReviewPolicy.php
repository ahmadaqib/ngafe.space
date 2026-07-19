<?php

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;

final class ReviewPolicy
{
    public function update(User $user, Review $review): bool
    {
        return $user->status === 'active' && (string) $review->user_id === (string) $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $this->update($user, $review);
    }
}
