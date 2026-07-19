<?php

use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| Only ever loaded when app()->environment('testing') is true — see
| bootstrap/app.php. Lets browser E2E tests (tests/Browser) authenticate a
| real session without driving Google's OAuth screen in headless Chromium.
*/
Route::get('/_testing/login/{user}', function (User $user, Request $request) {
    Auth::login($user);

    return redirect($request->query('redirect', '/'));
})->name('testing.login');
