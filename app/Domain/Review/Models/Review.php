<?php

namespace App\Domain\Review\Models;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasUlids;
    protected $fillable = ['user_id', 'cafe_id', 'rating', 'body', 'display_alias', 'status', 'is_edited'];
    protected function casts(): array { return ['status' => ReviewStatus::class, 'is_edited' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function cafe(): BelongsTo { return $this->belongsTo(Cafe::class); }
    public function photos(): HasMany { return $this->hasMany(Photo::class); }
    public function tags(): BelongsToMany { return $this->belongsToMany(\App\Domain\Cafe\Models\Category::class, 'review_tags'); }
}
