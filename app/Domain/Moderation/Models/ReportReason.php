<?php

namespace App\Domain\Moderation\Models;

enum ReportReason: string
{
    case Spam = 'spam';
    case Kasar = 'kasar';
    case BukanTentangCafe = 'bukan_tentang_cafe';
    case InfoSalah = 'info_salah';
    case MembukaIdentitas = 'membuka_identitas';
}
