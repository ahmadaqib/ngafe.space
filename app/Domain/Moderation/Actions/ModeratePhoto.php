<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Photo;
use App\Mail\ReviewModeratedMail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class ModeratePhoto
{
    public function __construct(private AuditModeration $audit) {}

    public function handle(User $admin, Photo $photo, string $decision, string $reason): Photo
    {
        if ($admin->role !== 'admin' || $admin->status !== 'active') {
            throw new AuthorizationException;
        }
        if (! in_array($decision, ['approve', 'takedown'], true)) {
            throw new InvalidArgumentException('Unknown photo moderation decision.');
        }

        $photo->update(['status' => $decision === 'approve' ? 'published' : 'removed']);
        $this->audit->record($admin, "photo.{$decision}", $photo, ['reason' => $reason]);
        if ($photo->review->user?->email) {
            Mail::to($photo->review->user->email)->queue(new ReviewModeratedMail($photo->review, "photo_{$decision}", $reason));
        }

        return $photo->refresh();
    }
}
