<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes the "sesi pendek" hardening rule (Docs/Spec.md §10, admin panel row)
 * to the Filament panel only, instead of shortening every visitor's session
 * app-wide. Must run before Illuminate\Session\Middleware\StartSession in
 * the panel's middleware stack, since StartSession reads session.lifetime
 * when it boots the session for the request.
 */
final class ShortenAdminSessionLifetime
{
    private const MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        config(['session.lifetime' => self::MINUTES]);

        return $next($request);
    }
}
