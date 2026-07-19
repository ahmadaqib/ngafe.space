<?php

namespace App\Domain\Moderation\Models;

enum ReportStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
