<?php
namespace App\Support; final class Format { public static function distance(float $meters): string { return $meters<1000?round($meters).' m':number_format($meters/1000,1,',','.').' km'; } }
