<?php

namespace App\Providers;

use App\Domain\Cafe\Actions\SyncHiddenGem;
use App\Domain\Cafe\Listeners\RecomputeCafeAggregates;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Identity\Policies\ReviewPolicy;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(Review::class, ReviewPolicy::class);

        // Docs/Spec.md §10: "login attempt per IP". The only rate limiter
        // that fits Laravel's route-level throttle:name middleware — the
        // others (review, photo, report, content appeal) live in their
        // Actions because they need domain exceptions with friendly copy
        // and multi-window (hour + day) checks, not a flat 429.
        RateLimiter::for('login', fn ($request) => Limit::perMinute(
            (int) config('rate_limits.login.per_minute')
        )->by($request->ip()));

        Cafe::updated(function (Cafe $cafe): void {
            if ($cafe->wasChanged('status') && $cafe->status === CafeStatus::Active) {
                app(SyncHiddenGem::class)->handle($cafe);
            }
        });
    }
}
