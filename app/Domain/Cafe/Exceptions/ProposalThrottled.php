<?php

namespace App\Domain\Cafe\Exceptions;

use App\Exceptions\DomainException;

class ProposalThrottled extends DomainException
{
    public function userMessage(): string { return 'Usulanmu sudah cukup banyak hari ini. Coba lagi besok ya.'; }
}
