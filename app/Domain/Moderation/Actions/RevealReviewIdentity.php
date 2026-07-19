<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Illuminate\Auth\Access\AuthorizationException;

final class RevealReviewIdentity
{
    public function __construct(private AuditModeration $audit) {}

    /** @return array{email:?string, google_sub:?string} */
    public function handle(User $admin, Review $review, string $reason): array
    {
        if ($admin->role !== 'admin' || $admin->status !== 'active') {
            throw new AuthorizationException;
        }
        $this->audit->record($admin, 'review.reveal_identity', $review, ['reason' => $reason]);

        return ['email' => $review->user?->email, 'google_sub' => $review->user?->google_sub];
    }
}
