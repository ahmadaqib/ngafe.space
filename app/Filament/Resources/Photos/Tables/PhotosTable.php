<?php

namespace App\Filament\Resources\Photos\Tables;

use App\Domain\Moderation\Actions\ModeratePhoto;
use App\Domain\Review\Models\Photo;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class PhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('url_card')->label('Foto'),
                TextColumn::make('review.display_alias')->label('Alias'),
                TextColumn::make('cafe.name')->label('Cafe'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')->options(['pending' => 'Pending', 'published' => 'Tayang', 'removed' => 'Diturunkan', 'failed' => 'Gagal'])])
            ->recordActions([self::decision('approve', 'Tayangkan'), self::decision('takedown', 'Turunkan')]);
    }

    private static function decision(string $decision, string $label): Action
    {
        return Action::make($decision)->label($label)->requiresConfirmation()
            ->schema([Textarea::make('reason')->required()->minLength(10)])
            ->action(function (Photo $record, array $data) use ($decision): void {
                app(ModeratePhoto::class)->handle(auth()->user(), $record, $decision, $data['reason']);
                Notification::make()->success()->title('Status foto diperbarui')->send();
            });
    }
}
