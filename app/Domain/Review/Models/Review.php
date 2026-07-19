<?php

namespace App\Domain\Review\Models;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Models\Report;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['user_id', 'cafe_id', 'rating', 'body', 'content_hash', 'display_alias', 'status', 'is_edited', 'moderation_reason', 'moderated_by', 'moderated_at'];

    protected static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }

    protected function casts(): array
    {
        return ['status' => ReviewStatus::class, 'is_edited' => 'boolean', 'moderated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'review_tags');
    }
}
