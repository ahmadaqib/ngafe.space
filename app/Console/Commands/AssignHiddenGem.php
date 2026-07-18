<?php

namespace App\Console\Commands;

use App\Domain\Cafe\Actions\SyncHiddenGem;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Console\Command;

final class AssignHiddenGem extends Command
{
    protected $signature = 'cafes:assign-hidden-gem';

    protected $description = 'Sinkronkan kategori Hidden gem / baru buka untuk cafe aktif';

    public function handle(SyncHiddenGem $syncHiddenGem): int
    {
        $processed = 0;

        Cafe::query()
            ->where('status', CafeStatus::Active->value)
            ->withCount([
                'reviews as published_reviews_count' => fn ($query) => $query->where('status', ReviewStatus::Published->value),
            ])
            ->chunkById(200, function ($cafes) use ($syncHiddenGem, &$processed): void {
                foreach ($cafes as $cafe) {
                    $syncHiddenGem->handle($cafe, (int) $cafe->published_reviews_count);
                    $processed++;
                }
            });

        $this->info("Hidden gem tersinkron untuk {$processed} cafe aktif.");

        return self::SUCCESS;
    }
}
