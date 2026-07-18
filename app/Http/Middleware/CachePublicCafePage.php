<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicCafePage
{
    public function __construct(private Repository $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) return $next($request);
        $key = 'cafe-page:'.sha1($request->path());
        if ($cached = $this->cache->get($key)) return response($cached['body'], $cached['status'], $cached['headers']);
        $response = $next($request);
        if ($response->isSuccessful()) $this->cache->put($key, ['body' => $response->getContent(), 'status' => $response->getStatusCode(), 'headers' => ['Cache-Control' => 'public, max-age=300']], now()->addMinutes(5));
        return $response;
    }
}
