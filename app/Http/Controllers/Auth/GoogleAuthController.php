<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\HandleGoogleCallback;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $request->session()->put('auth.intended_url', url()->previous());
        return Socialite::driver('google')->scopes(['openid', 'email'])->redirect();
    }

    public function callback(Request $request, HandleGoogleCallback $handleGoogleCallback): RedirectResponse
    {
        try {
            $user = $handleGoogleCallback->handle(Socialite::driver('google')->user());
            Auth::login($user, true);
            $request->session()->regenerate();
            return redirect()->to($request->session()->pull('auth.intended_url', '/'));
        } catch (Throwable) {
            return redirect('/')->with('toast_error', 'Yah, gagal nyambung ke Google. Coba lagi ya.');
        }
    }
}
