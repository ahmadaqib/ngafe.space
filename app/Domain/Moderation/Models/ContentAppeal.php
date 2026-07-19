<?php

namespace App\Domain\Moderation\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContentAppeal extends Model
{
    use HasUlids;

    protected $fillable = ['review_id', 'reporter_name', 'reporter_email', 'reason', 'status', 'appeal_count', 'decision', 'decided_by', 'decided_at'];

    protected $hidden = ['reporter_name', 'reporter_email'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
