<?php

namespace App\Filament\Resources\Cafes;

use App\Filament\Resources\Cafes\Pages\CreateCafe;
use App\Filament\Resources\Cafes\Pages\EditCafe;
use App\Filament\Resources\Cafes\Pages\ListCafes;
use App\Filament\Resources\Cafes\Schemas\CafeForm;
use App\Filament\Resources\Cafes\Tables\CafesTable;
use App\Domain\Cafe\Models\Cafe;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CafeResource extends Resource
{
    protected static ?string $model = Cafe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CafeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CafesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafes::route('/'),
            'create' => CreateCafe::route('/create'),
            'edit' => EditCafe::route('/{record}/edit'),
        ];
    }
}
