<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Exceptions\ContentAppealLimitExceeded;
use App\Domain\Moderation\Models\ContentAppeal;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Support\Facades\RateLimiter;

final class SubmitContentAppeal
{
    public function __construct(private AuditModeration $audit) {}

    public function handle(Review $review, string $name, string $email, string $reason): ContentAppeal
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $existing = ContentAppeal::query()
            ->where('review_id', $review->id)
            ->where('reporter_email', $normalizedEmail)
            ->latest()
            ->first();
        if ($existing) {
            return $existing;
        }

        $rateKey = 'content-appeal:day:'.$this->emailHash($normalizedEmail);
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            throw new ContentAppealLimitExceeded;
        }

        $appeal = ContentAppeal::query()->create([
            'review_id' => $review->id,
            'reporter_name' => trim($name),
            'reporter_email' => $normalizedEmail,
            'reason' => trim($reason),
        ]);
        RateLimiter::hit($rateKey, 86400);

        if ($review->status !== ReviewStatus::Pending) {
            $from = $review->status;
            $review->update(['status' => ReviewStatus::Pending]);
            ReviewStatusChanged::dispatch($review, $from);
        }
        $this->audit->record(null, 'content_appeal.submitted', $appeal, ['review_id' => $review->id]);

        return $appeal;
    }

    public function appealOnce(ContentAppeal $appeal, string $email, string $reason): ContentAppeal
    {
        $rateKey = "content-appeal:verify:{$appeal->id}";
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw new ContentAppealLimitExceeded;
        }
        RateLimiter::hit($rateKey, 3600);

        abort_unless(hash_equals($appeal->reporter_email, mb_strtolower(trim($email))), 403);
        abort_if($appeal->appeal_count >= 1 || $appeal->status === 'submitted', 422, 'Banding hanya tersedia satu kali setelah keputusan.');

        $appeal->update([
            'reason' => $appeal->reason."\n\nBANDING: ".trim($reason),
            'status' => 'submitted',
            'appeal_count' => 1,
            'decision' => null,
            'decided_by' => null,
            'decided_at' => null,
        ]);
        $this->audit->record(null, 'content_appeal.appealed', $appeal);

        return $appeal->refresh();
    }

    private function emailHash(string $email): string
    {
        return hash_hmac('sha256', $email, (string) config('app.key'));
    }
}
