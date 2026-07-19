<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\LogsAdminAudit;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use LogsAdminAudit;

    protected static string $resource = CategoryResource::class;
}
