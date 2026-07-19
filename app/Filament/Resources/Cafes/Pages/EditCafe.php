<?php

namespace App\Filament\Resources\Cafes\Pages;

use App\Domain\Moderation\Actions\AuditModeration;
use App\Filament\Concerns\LogsAdminAudit;
use App\Filament\Resources\Cafes\CafeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCafe extends EditRecord
{
    use LogsAdminAudit;

    protected static string $resource = CafeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn () => app(AuditModeration::class)->record(Auth::user(), 'admin.delete', $this->record)),
        ];
    }
}
