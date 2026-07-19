<?php

namespace App\Support;

use InvalidArgumentException;

class LogContext
{
    private const FORBIDDEN_KEYS = ['email', 'google_sub', 'token', 'body', 'lat', 'lng', 'ip'];

    /** @param array<string, mixed> $context */
    public static function safe(array $context): array
    {
        foreach (array_keys($context) as $key) {
            if (in_array(strtolower((string) $key), self::FORBIDDEN_KEYS, true)) {
                throw new InvalidArgumentException("Unsafe log context key: {$key}");
            }
        }

        return $context;
    }
}
