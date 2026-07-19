<?php

namespace App\Domain\Review\Exceptions;

use App\Exceptions\DomainException;

final class DuplicateReviewContent extends DomainException
{
    public function userMessage(): string
    {
        return 'Ceritanya sama dengan reviewmu yang lain. Coba tulis pengalaman khusus di cafe ini ya.';
    }
}
