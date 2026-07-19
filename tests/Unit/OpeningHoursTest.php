<?php

namespace Tests\Unit;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Support\OpeningHours;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class OpeningHoursTest extends TestCase
{
    public function test_it_handles_overnight_and_override_hours(): void
    {
        $cafe = new Cafe(['opening_hours' => ['mon' => '20:00 - 02:00'], 'opening_hours_override' => [['label' => 'Jam khusus Ramadan', 'date_start' => '2026-07-20', 'date_end' => '2026-07-20', 'hours' => '24 jam']]]);
        $this->assertTrue(OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-20 01:00'))->isOpen);
        $status = OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-20 12:00'));
        $this->assertTrue($status->isOpen);
        $this->assertSame('Jam khusus Ramadan', $status->activeOverride);
    }
}
