<?php

use App\Domain\Review\Models\Review;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\CachePublicCafePage;
use App\Livewire\CategoryCity;
use App\Livewire\ContentAppealForm;
use App\Livewire\ContentAppealStatus;
use App\Livewire\Home;
use App\Livewire\MyContributions;
use App\Livewire\Search;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class);
Route::get('/cari', Search::class)->name('search');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::view('/dev/tokens', 'dev.tokens')->middleware('local');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/keberatan-konten/{review}', ContentAppealForm::class)->name('content-appeal');
Route::get('/keberatan-konten/status/{appeal}', ContentAppealStatus::class)->name('content-appeal-status');
Route::get('/kontribusimu', MyContributions::class)->middleware('auth')->name('my-contributions');
Route::post('/reviews/{review}/reports', [ReportController::class, 'review'])->middleware('auth')->name('reviews.report');
Route::post('/photos/{photo}/reports', [ReportController::class, 'photo'])->middleware('auth')->name('photos.report');
// Must be registered before cafe.show: both match /{city}/{something}, and
// this one is only reachable for the literal "cafe-" prefixed segment.
Route::get('/{city}/cafe-{categorySlug}', CategoryCity::class)->whereIn('city', ['makassar'])->name('category-city');
Route::get('/{city}/{slug}', [CafeController::class, 'show'])->whereIn('city', ['makassar'])->middleware(CachePublicCafePage::class)->name('cafe.show');

Route::get('/api/public/cafes/{cafe}/reviews', function (string $cafe) {
    return response()->json(['data' => Review::query()->where('cafe_id', $cafe)->where('status', 'published')->latest()->get(['id', 'rating', 'body', 'display_alias', 'created_at'])]);
});
