<?php
namespace App\Domain\Cafe\Support;
use App\Domain\Cafe\Models\Cafe; use Carbon\CarbonImmutable;
final class OpeningHours {
 public static function statusNow(Cafe $cafe, CarbonImmutable $now): OpeningStatus { $override=collect($cafe->opening_hours_override ?? [])->first(fn($o)=>$now->toDateString()>=$o['date_start']&&$now->toDateString()<=$o['date_end']); $hours=$override['hours']??($cafe->opening_hours[strtolower($now->format('D'))]??null); if(!$hours)return new OpeningStatus(false,'Jam belum tersedia',$override['label']??null); if($hours==='24 jam')return new OpeningStatus(true,'Buka 24 jam',$override['label']??null); [$start,$end]=array_map('trim',explode('-',$hours)); $time=$now->format('H:i'); $open=$start<=$end?($time>=$start&&$time<$end):($time>=$start||$time<$end); return new OpeningStatus($open,$open?'Buka sekarang':'Tutup',$override['label']??null); }
}
