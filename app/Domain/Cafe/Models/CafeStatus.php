<?php

namespace App\Domain\Cafe\Models;

enum CafeStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case ClosedPerm = 'closed_perm';
}
