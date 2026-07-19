<?php

namespace App\Domain\Review\Models;

enum ReviewStatus: string
{
    case Published = 'published';
    case Pending = 'pending';
    case Removed = 'removed';
}
