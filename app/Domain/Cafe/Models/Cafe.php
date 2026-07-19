<?php

namespace App\Domain\Cafe\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use Database\Factories\CafeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cafe extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'slug', 'city', 'area', 'address', 'lat', 'lng', 'opening_hours', 'opening_hours_override', 'price_range', 'status', 'created_by', 'last_verified_at'];

    protected static function newFactory(): CafeFactory
    {
        return CafeFactory::new();
    }

    protected function casts(): array
    {
        return ['opening_hours' => 'array', 'opening_hours_override' => 'array', 'status' => CafeStatus::class, 'last_verified_at' => 'datetime', 'rating_avg' => 'decimal:2', 'quality_score' => 'decimal:4', 'trending_score' => 'decimal:2'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot(['source', 'confidence']);
    }
}
