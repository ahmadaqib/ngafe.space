<?php

namespace App\Domain\Review\Exceptions;

use App\Exceptions\DomainException;

class PhotoValidationFailed extends DomainException
{
    public function userMessage(): string
    {
        return 'Foto belum bisa dipakai. Coba pilih foto lain ya.';
    }
}
