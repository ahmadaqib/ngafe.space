<?php

namespace App\Domain\Review\Events;

use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReviewStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Review $review,
        public ?ReviewStatus $from,
    ) {}
}
