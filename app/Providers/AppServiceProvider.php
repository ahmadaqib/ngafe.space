<?php

namespace App\Providers;

use App\Domain\Cafe\Actions\SyncHiddenGem;
use App\Domain\Cafe\Listeners\RecomputeCafeAggregates;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Review\Events\ReviewStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ReviewStatusChanged::class, RecomputeCafeAggregates::class);

        Cafe::updated(function (Cafe $cafe): void {
            if ($cafe->wasChanged('status') && $cafe->status === CafeStatus::Active) {
                app(SyncHiddenGem::class)->handle($cafe);
            }
        });
    }
}
