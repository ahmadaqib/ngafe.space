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

    public function test_it_formats_public_cafe_copy(): void
    {
        $this->assertSame('4,6', Format::rating(4.56));
        $this->assertSame('Rp 15–30rb', Format::priceRange('15-30'));
        $excerpt = Format::reviewExcerpt(str_repeat('Kopinya enak dan tempatnya tenang untuk kerja. ', 4));
        $this->assertLessThanOrEqual(91, mb_strlen($excerpt));
        $this->assertStringEndsWith('…', $excerpt);
    }
}
