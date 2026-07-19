<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Domain\Moderation\Actions\AuditModeration;
use App\Filament\Concerns\LogsAdminAudit;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCategory extends EditRecord
{
    use LogsAdminAudit;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn () => app(AuditModeration::class)->record(Auth::user(), 'admin.delete', $this->record)),
        ];
    }
}
