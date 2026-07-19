<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Cafe\Actions\SyncCrowdCategories;
use App\Domain\Cafe\Actions\SyncHiddenGem;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use App\Jobs\RecomputeCafeAggregates;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DeleteAccount
{
    public function __construct(
        private SyncCrowdCategories $syncCrowdCategories,
        private SyncHiddenGem $syncHiddenGem,
    ) {}

    public function handle(User $user, string $reviewMode): void
    {
        if (! in_array($reviewMode, ['anonymize', 'delete'], true)) {
            throw new InvalidArgumentException('Unknown review deletion mode.');
        }

        $cafeIds = Review::query()
            ->where('user_id', $user->id)
            ->pluck('cafe_id')
            ->unique()
            ->values();

        DB::transaction(function () use ($user, $reviewMode): void {
            $reviews = Review::query()->where('user_id', $user->id);

            if ($reviewMode === 'delete') {
                $reviews->delete();
            } else {
                $reviews->update(['user_id' => null, 'content_hash' => null]);
            }

            $user->delete();
        });

        Cafe::query()->whereKey($cafeIds)->each(function (Cafe $cafe): void {
            RecomputeCafeAggregates::dispatch($cafe->id);
            $this->syncCrowdCategories->handle($cafe);
            $this->syncHiddenGem->handle($cafe);
        });
    }
}
