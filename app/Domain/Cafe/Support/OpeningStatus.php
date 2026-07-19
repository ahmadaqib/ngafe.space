<?php

namespace App\Domain\Cafe\Support;

final readonly class OpeningStatus
{
    public function __construct(public bool $isOpen, public string $label, public ?string $activeOverride = null) {}
}
