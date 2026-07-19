<?php

namespace App\Domain\Review\Support;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;

final class AliasGenerator
{
    /** @var list<string> */
    private const ADJECTIVES = ['Penikmat', 'Pemburu', 'Penghuni', 'Pengelana', 'Penjaga', 'Pencari', 'Penyuka'];

    /** @var list<string> */
    private const NOUNS = ['Senja', 'Kopi Susu', 'Sudut', 'Wifi', 'Deadline', 'Wiken', 'Meja Pojok'];

    public function for(User $user, Cafe $cafe): string
    {
        $hash = hash_hmac('sha256', $user->id.'|'.$cafe->id, (string) config('app.key'));
        $adjective = hexdec(substr($hash, 0, 8));
        $noun = hexdec(substr($hash, 8, 8));

        return self::ADJECTIVES[$adjective % count(self::ADJECTIVES)].' '
            .self::NOUNS[$noun % count(self::NOUNS)].' '
            .str($cafe->area)->replace('-', ' ')->title().' · '.strtoupper(substr($hash, 16, 4));
    }
}
