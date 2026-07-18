<?php

namespace App\Domain\Cafe\Listeners;

use App\Domain\Cafe\Actions\SyncCrowdCategories;
use App\Domain\Cafe\Actions\SyncHiddenGem;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Jobs\RecomputeCafeAggregates as RecomputeCafeAggregatesJob;

final class RecomputeCafeAggregates
{
    public function __construct(
        private SyncCrowdCategories $syncCrowdCategories,
        private SyncHiddenGem $syncHiddenGem,
    ) {}

    public function handle(ReviewStatusChanged $event): void
    {
        RecomputeCafeAggregatesJob::dispatch($event->review->cafe_id);

        if ($cafe = $event->review->cafe()->first()) {
            $this->syncCrowdCategories->handle($cafe);
            $this->syncHiddenGem->handle($cafe);
        }
    }
}
