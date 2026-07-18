<?php

namespace Tests\Unit;

use App\Support\LogContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LogContextTest extends TestCase
{
    public function test_it_rejects_pii_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LogContext::safe(['email' => 'private@example.test']);
    }
}
