<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use App\Mail\ReviewModeratedMail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class ModerateReview
{
    public function __construct(private AuditModeration $audit) {}

    public function handle(User $admin, Review $review, string $decision, string $reason): Review
    {
        if ($admin->role !== 'admin' || $admin->status !== 'active') {
            throw new AuthorizationException;
        }
        if (! in_array($decision, ['approve', 'takedown', 'ban'], true)) {
            throw new InvalidArgumentException('Unknown moderation decision.');
        }

        $from = $review->status;
        $status = $decision === 'approve' ? ReviewStatus::Published : ReviewStatus::Removed;
        DB::transaction(function () use ($admin, $review, $decision, $reason, $status): void {
            $review->update([
                'status' => $status,
                'moderation_reason' => $reason,
                'moderated_by' => $admin->id,
                'moderated_at' => now(),
            ]);
            if ($decision === 'ban' && $review->user) {
                $review->user->update(['status' => 'banned']);
            }
            $this->audit->record($admin, "review.{$decision}", $review, ['reason' => $reason]);
        });

        ReviewStatusChanged::dispatch($review, $from);
        if ($review->user?->email) {
            Mail::to($review->user->email)->queue(new ReviewModeratedMail($review, $decision, $reason));
        }

        return $review->refresh();
    }
}
