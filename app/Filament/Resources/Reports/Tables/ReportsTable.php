<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Domain\Moderation\Actions\ResolveReport;
use App\Domain\Moderation\Models\Report;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['review.cafe', 'photo'])->orderByDesc('priority')->orderBy('created_at'))
            ->columns([
                IconColumn::make('priority')->boolean(),
                TextColumn::make('reason')->badge(),
                TextColumn::make('review.display_alias')->label('Review'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')->options(['open' => 'Terbuka', 'resolved' => 'Selesai'])])
            ->recordActions([
                Action::make('resolve')->label('Selesaikan')->requiresConfirmation()->action(function (Report $record): void {
                    app(ResolveReport::class)->handle(auth()->user(), $record);
                    Notification::make()->success()->title('Report diselesaikan')->send();
                }),
            ]);
    }
}
