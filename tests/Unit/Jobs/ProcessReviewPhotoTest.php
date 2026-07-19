<?php

namespace Tests\Unit\Jobs;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use App\Jobs\GenerateShareCard;
use App\Jobs\ProcessReviewPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessReviewPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_reencodes_two_webp_variants_without_exif_and_is_idempotent(): void
    {
        Bus::fake([GenerateShareCard::class]);
        Storage::fake('local');
        Storage::fake('r2');
        $review = $this->review();
        $upload = UploadedFile::fake()->image('cafe-with-gps.jpg', 2000, 1200);
        $staging = 'review-photo-staging/source.upload';
        Storage::disk('local')->put($staging, file_get_contents($upload->getRealPath()));
        $photo = Photo::query()->create([
            'review_id' => $review->id, 'cafe_id' => $review->cafe_id, 'url_card' => '', 'url_full' => '',
            'width' => 1, 'height' => 1, 'status' => 'pending', 'content_hash' => hash_file('sha256', $upload->getRealPath()),
        ]);

        $job = new ProcessReviewPhoto($photo->id, $staging);
        $job->handle();
        $job->handle();

        $photo->refresh();
        $this->assertSame('published', $photo->status);
        $this->assertLessThanOrEqual(1600, max($photo->width, $photo->height));
        $files = Storage::disk('r2')->allFiles('reviews/'.$review->id);
        $this->assertCount(2, $files);
        foreach ($files as $path) {
            $binary = Storage::disk('r2')->get($path);
            $this->assertSame('image/webp', (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary));
            $tmp = tmpfile();
            fwrite($tmp, $binary);
            $metadata = @exif_read_data(stream_get_meta_data($tmp)['uri']);
            $this->assertTrue($metadata === false || ! isset($metadata['GPSLatitude'], $metadata['GPSLongitude']));
        }

        Bus::assertDispatched(GenerateShareCard::class, fn (GenerateShareCard $job): bool => $job->cafeId === $review->cafe_id);
    }

    private function review(): Review
    {
        return Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'cafe_id' => Cafe::factory()->create()->id,
        ]);
    }
}
