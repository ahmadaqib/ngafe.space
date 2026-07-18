<?php

namespace App\Domain\Review\Models;

use App\Domain\Cafe\Models\Cafe;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasUlids;
    public $timestamps = false;
    protected $fillable = ['review_id', 'cafe_id', 'url_card', 'url_full', 'width', 'height', 'status', 'content_hash'];
    public function review(): BelongsTo { return $this->belongsTo(Review::class); }
    public function cafe(): BelongsTo { return $this->belongsTo(Cafe::class); }
}
