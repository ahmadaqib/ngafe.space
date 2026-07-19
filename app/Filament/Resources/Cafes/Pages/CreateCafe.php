<?php

namespace App\Filament\Resources\Cafes\Pages;

use App\Filament\Concerns\LogsAdminAudit;
use App\Filament\Resources\Cafes\CafeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCafe extends CreateRecord
{
    use LogsAdminAudit;

    protected static string $resource = CafeResource::class;
}
