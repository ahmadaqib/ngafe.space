<?php

namespace Database\Factories;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Photo> */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;
    public function definition(): array
    {
        return ['review_id' => Review::factory(), 'url_card' => 'https://example.test/photos/card.jpg', 'url_full' => 'https://example.test/photos/full.jpg', 'width' => 1200, 'height' => 900, 'status' => 'published', 'content_hash' => hash('sha256', fake()->unique()->uuid())];
    }
}
