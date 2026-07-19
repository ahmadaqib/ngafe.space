<?php

namespace App\Filament\Concerns;

use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Actions\AuditModeration;
use Illuminate\Support\Facades\Auth;

/**
 * Closes the "audit log semua aksi admin" requirement (Docs/Spec.md §10,
 * admin panel row) for plain CRUD resources (Cafe, Category) that don't go
 * through a dedicated moderation Action. Reuses the same
 * ModerationAuditLog sink the moderation flows already write to, so admins
 * have one place to review every action instead of two.
 */
trait LogsAdminAudit
{
    protected function afterCreate(): void
    {
        $this->recordAdminAudit('admin.create');
    }

    protected function afterSave(): void
    {
        $this->recordAdminAudit('admin.update');
    }

    protected function recordAdminAudit(string $action): void
    {
        /** @var User|null $admin */
        $admin = Auth::user();

        app(AuditModeration::class)->record($admin, $action, $this->record);
    }
}
