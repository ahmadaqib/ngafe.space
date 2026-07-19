<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Models\ModerationAuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditModeration
{
    public function record(?User $admin, string $action, Model $subject, array $metadata = []): ModerationAuditLog
    {
        return ModerationAuditLog::query()->create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
