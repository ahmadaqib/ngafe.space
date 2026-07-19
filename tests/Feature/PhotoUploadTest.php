<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Actions\AttachPhotos;
use App\Domain\Review\Exceptions\PhotoValidationFailed;
use App\Domain\Review\Models\Review;
use App\Jobs\ProcessReviewPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_photo_is_queued_independently_and_failure_does_not_remove_review_text(): void
    {
        Storage::fake('local');
        Queue::fake();
        [$user, $review] = $this->review();
        $upload = UploadedFile::fake()->image('cafe.jpg', 1200, 800);

        $photos = app(AttachPhotos::class)->handle($user, $review, [$upload]);
        Queue::assertPushed(ProcessReviewPhoto::class, 1);
        $job = new ProcessReviewPhoto($photos[0]->id, $photos[0]->processing_error ?? 'review-photo-staging/missing');
        $job->failed(new RuntimeException('temporary R2 failure'));

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'body' => $review->body]);
        $this->assertSame('failed', $photos[0]->fresh()->status);
    }

    public function test_server_rejects_non_image_magic_bytes(): void
    {
        Storage::fake('local');
        [$user, $review] = $this->review();

        $this->expectException(PhotoValidationFailed::class);
        app(AttachPhotos::class)->handle($user, $review, [UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "no";')]);
    }

    public function test_twenty_first_photo_in_a_day_is_rejected(): void
    {
        Storage::fake('local');
        [$user, $review] = $this->review();
        foreach (range(1, 20) as $attempt) {
            RateLimiter::hit("photo:day:{$user->id}", 86400);
        }

        $this->expectException(PhotoValidationFailed::class);
        app(AttachPhotos::class)->handle($user, $review, [UploadedFile::fake()->image('extra.jpg')]);
    }

    private function review(): array
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'cafe_id' => Cafe::factory()->create()->id]);

        return [$user, $review];
    }
}
