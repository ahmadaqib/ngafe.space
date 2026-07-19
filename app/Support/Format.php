<?php

namespace App\Support;

final class Format
{
    public static function distance(float $meters): string
    {
        return $meters < 1000 ? round($meters).' m' : number_format($meters / 1000, 1, ',', '.').' km';
    }

    public static function rating(float|string|null $rating): ?string
    {
        return $rating === null ? null : number_format((float) $rating, 1, ',', '.');
    }

    public static function priceRange(?string $range): ?string
    {
        if (! filled($range)) {
            return null;
        }

        return 'Rp '.str_replace('-', '–', $range).'rb';
    }

    public static function reviewExcerpt(?string $body, int $limit = 90): ?string
    {
        $body = trim(preg_replace('/\s+/u', ' ', (string) $body));
        if ($body === '') {
            return null;
        }
        if (mb_strlen($body) <= $limit) {
            return $body;
        }

        $cut = mb_substr($body, 0, $limit + 1);
        $cut = preg_replace('/\s+\S*$/u', '', $cut) ?: mb_substr($body, 0, $limit);

        return rtrim($cut, " \t\n\r\0\x0B.,;:!?").'…';
    }
}
