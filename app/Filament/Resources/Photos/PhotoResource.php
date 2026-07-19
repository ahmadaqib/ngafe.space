<?php

namespace App\Filament\Resources\Photos;

use App\Domain\Review\Models\Photo;
use App\Filament\Resources\Photos\Pages\ListPhotos;
use App\Filament\Resources\Photos\Tables\PhotosTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Moderasi Foto';

    public static function table(Table $table): Table
    {
        return PhotosTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListPhotos::route('/')];
    }
}
