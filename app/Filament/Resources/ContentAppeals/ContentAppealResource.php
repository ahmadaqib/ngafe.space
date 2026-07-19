<?php

namespace App\Filament\Resources\ContentAppeals;

use App\Domain\Moderation\Models\ContentAppeal;
use App\Filament\Resources\ContentAppeals\Pages\ListContentAppeals;
use App\Filament\Resources\ContentAppeals\Tables\ContentAppealsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ContentAppealResource extends Resource
{
    protected static ?string $model = ContentAppeal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Keberatan Konten';

    public static function table(Table $table): Table
    {
        return ContentAppealsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListContentAppeals::route('/')];
    }
}
