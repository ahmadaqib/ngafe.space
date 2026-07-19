<?php

namespace App\Filament\Resources\ContentAppeals\Tables;

use App\Domain\Moderation\Actions\DecideContentAppeal;
use App\Domain\Moderation\Models\ContentAppeal;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ContentAppealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('review.display_alias')->label('Review'),
                TextColumn::make('status')->badge(),
                TextColumn::make('appeal_count')->label('Banding'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at')
            ->recordActions([
                self::decision('content_restored', 'Pertahankan konten'),
                self::decision('content_removed', 'Turunkan konten'),
            ]);
    }

    private static function decision(string $decision, string $label): Action
    {
        return Action::make($decision)->label($label)->requiresConfirmation()
            ->schema([Textarea::make('explanation')->label('Keputusan tertulis')->required()->minLength(20)])
            ->action(function (ContentAppeal $record, array $data) use ($decision): void {
                app(DecideContentAppeal::class)->handle(auth()->user(), $record, $decision, $data['explanation']);
                Notification::make()->success()->title('Keputusan keberatan tercatat')->send();
            });
    }
}
