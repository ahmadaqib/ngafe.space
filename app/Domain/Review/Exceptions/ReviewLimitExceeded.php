<?php

namespace App\Domain\Review\Exceptions;

use App\Exceptions\DomainException;

class ReviewLimitExceeded extends DomainException
{
    public function userMessage(): string
    {
        return $this->getMessage() === 'account-banned'
            ? 'Akunmu sedang dibatasi. Hubungi kami kalau menurutmu ini keliru.'
            : 'Banyak cerita hari ini. Istirahat sebentar, lalu coba lagi ya.';
    }
}
