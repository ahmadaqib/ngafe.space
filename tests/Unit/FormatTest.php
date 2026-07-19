<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function test_it_formats_distance_in_indonesian(): void
    {
        $this->assertSame('850 m', Format::distance(850));
        $this->assertSame('1,2 km', Format::distance(1234));
    }
}
