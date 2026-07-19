<?php

namespace App\Domain\Review\Models;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Moderation\Models\Report;
use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Photo extends Model
{
    use HasFactory, HasUlids;

    public $timestamps = false;

    protected static function newFactory(): PhotoFactory
    {
        return PhotoFactory::new();
    }

    protected $fillable = ['review_id', 'cafe_id', 'url_card', 'url_full', 'width', 'height', 'status', 'processing_error', 'content_hash'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
