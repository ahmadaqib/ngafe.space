<?php

namespace App\Domain\Moderation\Exceptions;

use App\Exceptions\DomainException;

final class ContentAppealLimitExceeded extends DomainException
{
    public function userMessage(): string
    {
        return 'Pengajuanmu terlalu sering. Tunggu sebentar lalu coba lagi ya.';
    }
}
