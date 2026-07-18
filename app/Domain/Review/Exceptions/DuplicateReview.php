<?php

namespace App\Domain\Review\Exceptions;

use App\Exceptions\DomainException;

class DuplicateReview extends DomainException
{
    public function userMessage(): string { return 'Ulasan untuk cafe ini sudah ada.'; }
}
