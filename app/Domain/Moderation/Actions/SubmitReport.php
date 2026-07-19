<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Cafe\Support\OpeningHours;
use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Models\Report;
use App\Domain\Moderation\Models\ReportReason;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use App\Exceptions\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;

final class SubmitReport
{
    public function handle(User $reporter, Review|Photo $subject, ReportReason $reason, ?string $note = null): Report
    {
        $key = "report:day:{$reporter->id}";
        if (RateLimiter::tooManyAttempts($key, (int) config('rate_limits.report.per_day'))) {
            throw new class extends DomainException
            {
                public function userMessage(): string
                {
                    return 'Laporanmu hari ini sudah cukup banyak. Coba lagi besok ya.';
                }
            };
        }

        $column = $subject instanceof Review ? 'review_id' : 'photo_id';
        $existing = Report::query()->where('reporter_id', $reporter->id)->where($column, $subject->id)->where('status', 'open')->first();
        if ($existing) {
            return $existing;
        }

        $priority = $reason === ReportReason::InfoSalah
            && $subject instanceof Review
            && OpeningHours::statusNow($subject->cafe, CarbonImmutable::now())->activeOverride !== null;

        $report = Report::query()->create([
            'reporter_id' => $reporter->id,
            $column => $subject->id,
            'reason' => $reason,
            'note' => $note,
            'priority' => $priority,
        ]);
        RateLimiter::hit($key, 86400);

        $uniqueReports = Report::query()->where($column, $subject->id)->where('status', 'open')->distinct()->count('reporter_id');
        if ($uniqueReports >= 3) {
            if ($subject instanceof Review && $subject->status !== ReviewStatus::Pending) {
                $from = $subject->status;
                $subject->update(['status' => ReviewStatus::Pending]);
                ReviewStatusChanged::dispatch($subject, $from);
            } elseif ($subject instanceof Photo) {
                $subject->update(['status' => 'pending']);
            }
        }

        return $report;
    }
}
