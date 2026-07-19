<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Domain\Moderation\Actions\ModerateReview;
use App\Domain\Moderation\Actions\RevealReviewIdentity;
use App\Domain\Review\Models\Review;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cafe', 'user'])->orderByRaw("case when status = 'pending' then 0 else 1 end"))
            ->columns([
                TextColumn::make('display_alias')->label('Alias')->searchable(),
                TextColumn::make('cafe.name')->label('Cafe'),
                TextColumn::make('rating')->label('Rating'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(['pending' => 'Pending', 'published' => 'Tayang', 'removed' => 'Diturunkan'])])
            ->recordActions([
                self::moderationAction('approve', 'Tayangkan'),
                self::moderationAction('takedown', 'Turunkan'),
                self::moderationAction('ban', 'Turunkan + ban'),
                Action::make('reveal_identity')
                    ->label('Reveal identitas')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('Alasan akses')->required()->minLength(10)])
                    ->action(function (Review $record, array $data): void {
                        $identity = app(RevealReviewIdentity::class)->handle(auth()->user(), $record, $data['reason']);
                        Notification::make()->warning()->title('Akses identitas tercatat')->body($identity['email'] ?? 'Akun sudah dianonimkan')->persistent()->send();
                    }),
            ]);
    }

    private static function moderationAction(string $decision, string $label): Action
    {
        return Action::make($decision)
            ->label($label)
            ->requiresConfirmation()
            ->schema([Textarea::make('reason')->label('Alasan keputusan')->required()->minLength(10)])
            ->action(function (Review $record, array $data) use ($decision): void {
                app(ModerateReview::class)->handle(auth()->user(), $record, $decision, $data['reason']);
                Notification::make()->success()->title('Keputusan moderasi tersimpan')->send();
            });
    }
}
