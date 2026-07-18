<?php

namespace App\Filament\Resources\Cafes\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class CafeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(255),
                TextInput::make('city')->required()->default('makassar'),
                TextInput::make('area')->required(),
                Textarea::make('address'),
                TextInput::make('lat')->numeric()->required(),
                TextInput::make('lng')->numeric()->required(),
                TextInput::make('price_range'),
                Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'rejected' => 'Rejected', 'closed_perm' => 'Tutup permanen'])->required(),
                Repeater::make('opening_hours_override')->schema([
                    TextInput::make('label')->required(),
                    TextInput::make('date_start')->required(),
                    TextInput::make('date_end')->required(),
                    TextInput::make('hours')->required(),
                ]),
            ]);
    }
}
