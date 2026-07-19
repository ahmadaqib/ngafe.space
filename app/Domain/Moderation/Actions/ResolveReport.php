<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Models\Report;
use App\Domain\Moderation\Models\ReportStatus;
use Illuminate\Auth\Access\AuthorizationException;

final class ResolveReport
{
    public function __construct(private AuditModeration $audit) {}

    public function handle(User $admin, Report $report): Report
    {
        if ($admin->role !== 'admin' || $admin->status !== 'active') {
            throw new AuthorizationException;
        }
        $report->update(['status' => ReportStatus::Resolved, 'resolved_by' => $admin->id]);
        $this->audit->record($admin, 'report.resolve', $report);

        return $report->refresh();
    }
}
