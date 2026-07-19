<?php

namespace App\Domain\Cafe\Support;

use App\Domain\Cafe\Models\Cafe;
use Carbon\CarbonImmutable;

final class OpeningHours
{
    public static function statusNow(Cafe $cafe, CarbonImmutable $now): OpeningStatus
    {
        $override = collect($cafe->opening_hours_override ?? [])->first(fn ($o) => isset($o['date_start'], $o['date_end']) && $now->toDateString() >= $o['date_start'] && $now->toDateString() <= $o['date_end']);
        $hours = $override['hours'] ?? ($cafe->opening_hours[strtolower($now->format('D'))] ?? null);
        if (! is_string($hours) || trim($hours) === '') {
            return new OpeningStatus(false, 'Jam belum tersedia', $override['label'] ?? null);
        }
        if (mb_strtolower(trim($hours)) === '24 jam') {
            return new OpeningStatus(true, 'Buka 24 jam', $override['label'] ?? null);
        }
        if (! preg_match('/^(\d{1,2}):([0-5]\d)\s*-\s*(\d{1,2}):([0-5]\d)$/', trim($hours), $matches)) {
            return new OpeningStatus(false, 'Jam belum tersedia', $override['label'] ?? null);
        }
        $start = sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        $end = sprintf('%02d:%s', (int) $matches[3], $matches[4]);
        if ((int) $matches[1] > 23 || (int) $matches[3] > 23) {
            return new OpeningStatus(false, 'Jam belum tersedia', $override['label'] ?? null);
        }
        $time = $now->format('H:i');
        $open = $start <= $end ? ($time >= $start && $time < $end) : ($time >= $start || $time < $end);

        $label = $open
            ? 'Buka · tutup '.str_replace(':', '.', $end)
            : 'Tutup · buka '.str_replace(':', '.', $start);

        return new OpeningStatus($open, $label, $override['label'] ?? null);
    }
}
