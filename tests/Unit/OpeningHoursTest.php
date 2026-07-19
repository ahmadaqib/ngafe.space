<?php

namespace Tests\Unit;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Support\OpeningHours;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class OpeningHoursTest extends TestCase
{
    public function test_it_handles_normal_overnight_closed_and_missing_hours(): void
    {
        $cafe = new Cafe(['opening_hours' => ['mon' => '20:00 - 02:00', 'tue' => '08:00 - 22:00']]);
        $this->assertTrue(OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-20 01:00'))->isOpen);
        $this->assertSame('Buka · tutup 02.00', OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-20 21:00'))->label);
        $this->assertSame('Tutup · buka 08.00', OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-21 23:00'))->label);
        $this->assertSame('Jam belum tersedia', OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-22 12:00'))->label);
    }

    public function test_override_wins_and_twenty_four_hours_is_open(): void
    {
        $cafe = new Cafe(['opening_hours' => ['mon' => '20:00 - 02:00'], 'opening_hours_override' => [['label' => 'Jam khusus Ramadan', 'date_start' => '2026-07-20', 'date_end' => '2026-07-20', 'hours' => '24 jam']]]);
        $status = OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-20 12:00'));
        $this->assertTrue($status->isOpen);
        $this->assertSame('Buka 24 jam', $status->label);
        $this->assertSame('Jam khusus Ramadan', $status->activeOverride);
    }

    public function test_malformed_hours_fail_safely(): void
    {
        $cafe = new Cafe(['opening_hours' => ['mon' => 'besok saja']]);
        $this->assertSame('Jam belum tersedia', OpeningHours::statusNow($cafe, CarbonImmutable::parse('2026-07-20 12:00'))->label);
    }
}
