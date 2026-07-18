<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Domain\Review\Models\Review;
use App\Http\Controllers\CafeController;
use App\Livewire\Home;
use App\Livewire\Search;

Route::get('/', Home::class);
Route::get('/cari', Search::class)->name('search');
Route::view('/dev/tokens', 'dev.tokens')->middleware('local');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/{city}/{slug}', [CafeController::class, 'show'])->whereIn('city', ['makassar'])->name('cafe.show');

Route::get('/api/public/cafes/{cafe}/reviews', function (string $cafe) {
    return response()->json(['data' => Review::query()->where('cafe_id', $cafe)->where('status', 'published')->latest()->get(['id', 'rating', 'body', 'display_alias', 'created_at'])]);
});
