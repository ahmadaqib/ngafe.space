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
        $intended = $this->safeIntendedUrl($request->string('intended')->toString() ?: url()->previous());
        $request->session()->put('auth.intended_url', $intended);
        $request->session()->put('auth.intent', $request->string('intent')->limit(120)->toString());

        return Socialite::driver('google')->scopes(['openid', 'email'])->redirect();
    }

    public function callback(Request $request, HandleGoogleCallback $handleGoogleCallback): RedirectResponse
    {
        try {
            $user = $handleGoogleCallback->handle(Socialite::driver('google')->user());
            if ($user->status !== 'active') {
                return redirect()->to($request->session()->pull('auth.intended_url', '/'))
                    ->with('toast_error', 'Akun ini sedang dinonaktifkan. Hubungi pengelola jika kamu ingin mengajukan keberatan.');
            }
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->to($request->session()->pull('auth.intended_url', '/'));
        } catch (Throwable) {
            return redirect()->to($request->session()->pull('auth.intended_url', '/'))
                ->with('toast_error', 'Yah, gagal nyambung ke Google. Coba lagi ya.');
        }
    }

    private function safeIntendedUrl(string $url): string
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host === request()->getHost() ? $url : '/';
    }
}
