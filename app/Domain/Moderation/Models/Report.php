<?php

namespace App\Domain\Moderation\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUlids;
    protected $fillable = ['reporter_id', 'review_id', 'photo_id', 'reason', 'note', 'status', 'resolved_by'];
    protected function casts(): array { return ['reason' => ReportReason::class, 'status' => ReportStatus::class]; }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reporter_id'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
    public function review(): BelongsTo { return $this->belongsTo(Review::class); }
    public function photo(): BelongsTo { return $this->belongsTo(Photo::class); }
}
