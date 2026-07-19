<?php

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;

/**
 * Real-browser coverage for the two paths Task 4.3 (Docs/plan.md) calls out
 * explicitly: the guest "aha moment" reading path (§4.1) and the review
 * form's client-side behavior (Alpine login sheet, Livewire step
 * progression) that no HTTP-only Feature test can exercise.
 */
it('lets a guest reach a published review within two taps and no login gate', function () {
    $cafe = Cafe::factory()->create([
        'name' => 'Kopi Anjis Perintis',
        'slug' => 'kopi-anjis-perintis',
        'city' => 'makassar',
        'status' => CafeStatus::Active,
    ]);

    Review::factory()->for($cafe)->create([
        'body' => 'Wifinya kenceng banget, colokan banyak, betah nugas berjam-jam di sini.',
        'status' => 'published',
    ]);

    $page = visit('/');

    $page->assertSee('Kopi Anjis Perintis')
        ->assertDontSee('Login sebentar')
        ->click('Kopi Anjis Perintis')
        ->assertPathIs('/makassar/kopi-anjis-perintis')
        ->assertSee('Wifinya kenceng banget')
        ->assertDontSee('sedang ditinjau')
        ->assertNoJavaScriptErrors();
});

it('shows the anonymous-login bottom sheet when a guest tries to write, without leaving the page', function () {
    $cafe = Cafe::factory()->create(['status' => CafeStatus::Active]);
    Review::factory()->for($cafe)->create(['status' => 'published']);

    $page = visit("/{$cafe->city}/{$cafe->slug}");

    $page->assertSee('Tulis review')
        ->assertDontSee('Login sebentar biar reviewmu tersimpan')
        ->click('Tulis review')
        ->assertSee('Login sebentar biar reviewmu tersimpan')
        ->assertSee('Lanjut dengan Google')
        ->assertPathIs("/{$cafe->city}/{$cafe->slug}")
        ->assertNoJavaScriptErrors();
});

it('walks a logged-in user through the three-step review form in a real browser', function () {
    $cafe = Cafe::factory()->create(['status' => CafeStatus::Active]);
    Review::factory()->for($cafe)->create(['status' => 'published']);
    $user = User::factory()->create();

    $redirect = urlencode("/{$cafe->city}/{$cafe->slug}");

    $page = visit("/_testing/login/{$user->id}?redirect={$redirect}");

    $page->assertPathIs("/{$cafe->city}/{$cafe->slug}")
        ->assertSee('Langkah 1 dari 3')
        ->click('[data-review-field="rating"][value="5"]')
        ->click('Lanjut')
        ->assertSee('Langkah 2 dari 3')
        ->type('#review-body', 'Wifinya stabil dan colokannya banyak, cocok buat kerja seharian di sini.')
        ->click('Lanjut')
        ->assertSee('Langkah 3 dari 3')
        ->assertSee('Tayangkan review')
        ->assertNoJavaScriptErrors();
});
