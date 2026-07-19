<?php

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Review\Models\Review;

/**
 * Real-browser coverage for Docs/plan.md Task 5.3 — PWA installability.
 * Static files under public/ (manifest, sw.js) aren't reachable through
 * Laravel's HTTP test client (it invokes the kernel directly, skipping the
 * "serve from disk first" step a real front controller does), so
 * tests/Feature/PwaTest.php checks them via the filesystem instead. This
 * file proves the service worker actually registers and activates in a
 * real browser, which no filesystem check can prove.
 */
it('registers and activates the service worker from a real page load', function () {
    $cafe = Cafe::factory()->create(['status' => CafeStatus::Active]);
    Review::factory()->for($cafe)->create(['status' => 'published']);

    $page = visit("/{$cafe->city}/{$cafe->slug}");

    // No waitForFunction helper on this API surface, so the poll lives
    // inside one evaluate() call instead of an external wait primitive.
    $activeScriptUrl = $page->script('
        (async () => {
            const deadline = Date.now() + 5000;
            while (Date.now() < deadline) {
                const reg = await navigator.serviceWorker.getRegistration();
                if (reg && reg.active) return reg.active.scriptURL;
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return null;
        })()
    ');

    expect($activeScriptUrl)->toContain('/sw.js');
});
