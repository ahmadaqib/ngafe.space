<?php

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Review\Models\Review;

/**
 * Real-browser coverage for Docs/plan.md Task 5.2 — the Web Share API
 * button and its clipboard fallback. Headless Chromium doesn't implement
 * navigator.share, so this always exercises the fallback path, which is
 * exactly the path most desktop/CI users would hit too.
 */
it('copies the cafe link and shows a confirmation toast when native share is unavailable', function () {
    $cafe = Cafe::factory()->create(['name' => 'Kopi Anjis Perintis', 'slug' => 'kopi-anjis-perintis', 'city' => 'makassar', 'status' => CafeStatus::Active]);
    Review::factory()->for($cafe)->create(['status' => 'published']);

    $page = visit("/{$cafe->city}/{$cafe->slug}", ['permissions' => ['clipboard-read', 'clipboard-write']]);

    $page->assertSee('Share')
        ->click('Share')
        ->assertSee('Link kesalin!')
        ->assertNoJavaScriptErrors();
});
