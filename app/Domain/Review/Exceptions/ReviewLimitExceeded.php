<?php

namespace App\Domain\Review\Exceptions;

use App\Exceptions\DomainException;

class ReviewLimitExceeded extends DomainException
{
    public function userMessage(): string { return 'Kamu sudah pernah mengulas cafe ini.'; }
}
