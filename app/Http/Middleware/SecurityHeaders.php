<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Docs/Spec.md §10 hardening lapis 3. CSP nonce is registered before the
 * response renders so @vite/@livewireScripts/@livewireStyles pick it up
 * automatically (Vite::useCspNonce() is what Livewire's own tag helpers
 * read — see vendor/livewire/livewire/.../FrontendAssets.php).
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce(Str::random(32));

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->csp());
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=()');

        return $response;
    }

    private function csp(): string
    {
        $nonce = Vite::cspNonce();
        $r2Host = parse_url((string) config('filesystems.disks.r2.url'), PHP_URL_HOST);
        $imgSrc = "'self' data:".($r2Host ? " https://{$r2Host}" : '');

        $viteDevServer = app()->isLocal()
            ? ' http://127.0.0.1:5173 http://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5173'
            : '';

        return implode('; ', [
            "default-src 'self'",
            "img-src {$imgSrc}",
            // Confirmed empirically (tests/Browser/*): Livewire's bundled
            // Alpine evaluates x-data/directive expressions via
            // `new Function(...)`, which throws EvalError under a strict
            // script-src without this — Alpine's separate CSP-safe build
            // (precompiled expressions, no runtime eval) isn't used here.
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$viteDevServer}",
            "style-src 'self' 'unsafe-inline'{$viteDevServer}",
            "font-src 'self'{$viteDevServer}",
            "connect-src 'self'{$viteDevServer}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
