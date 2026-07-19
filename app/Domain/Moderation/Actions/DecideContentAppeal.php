<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Models\ContentAppeal;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\ReviewStatus;
use App\Mail\ContentAppealDecisionMail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class DecideContentAppeal
{
    public function __construct(private AuditModeration $audit) {}

    public function handle(User $admin, ContentAppeal $appeal, string $decision, string $explanation): ContentAppeal
    {
        if ($admin->role !== 'admin' || $admin->status !== 'active') {
            throw new AuthorizationException;
        }
        if (! in_array($decision, ['content_restored', 'content_removed'], true)) {
            throw new InvalidArgumentException('Unknown appeal decision.');
        }

        $review = $appeal->review;
        $from = $review->status;
        $review->update(['status' => $decision === 'content_restored' ? ReviewStatus::Published : ReviewStatus::Removed]);
        $appeal->update([
            'status' => $decision,
            'decision' => trim($explanation),
            'decided_by' => $admin->id,
            'decided_at' => now(),
        ]);
        $this->audit->record($admin, "content_appeal.{$decision}", $appeal, ['decision' => $explanation]);
        ReviewStatusChanged::dispatch($review, $from);
        Mail::to($appeal->reporter_email)->queue(new ContentAppealDecisionMail($appeal));

        return $appeal->refresh();
    }
}
