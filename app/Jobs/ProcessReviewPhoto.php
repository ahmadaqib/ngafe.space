<?php

namespace App\Jobs;

use App\Domain\Review\Models\Photo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

final class ProcessReviewPhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public string $photoId, public string $stagingPath) {}

    public function handle(): void
    {
        $photo = Photo::query()->findOrFail($this->photoId);
        if ($photo->status === 'published' && filled($photo->url_card) && filled($photo->url_full)) {
            return;
        }

        $binary = Storage::disk('local')->get($this->stagingPath);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'], true)) {
            throw new \RuntimeException('Invalid image magic bytes.');
        }

        $manager = new ImageManager(new Driver);
        $source = $manager->decodeBinary($binary)->orient()->removeProfile();
        $full = clone $source;
        $card = clone $source;
        $this->scaleDownToMaxSide($full, 1600);
        $this->scaleDownToMaxSide($card, 400);

        $uuid = (string) Str::uuid();
        $cardPath = "reviews/{$photo->review_id}/{$uuid}-card.webp";
        $fullPath = "reviews/{$photo->review_id}/{$uuid}-full.webp";
        Storage::disk('r2')->put($cardPath, (string) $card->encode(new WebpEncoder(quality: 78, strip: true)));
        Storage::disk('r2')->put($fullPath, (string) $full->encode(new WebpEncoder(quality: 82, strip: true)));

        $photo->update([
            'url_card' => Storage::disk('r2')->url($cardPath),
            'url_full' => Storage::disk('r2')->url($fullPath),
            'width' => $full->width(),
            'height' => $full->height(),
            'status' => 'published',
            'processing_error' => null,
        ]);
        Storage::disk('local')->delete($this->stagingPath);
    }

    public function failed(?Throwable $exception): void
    {
        Photo::query()->whereKey($this->photoId)->update([
            'status' => 'failed',
            'processing_error' => $this->stagingPath,
        ]);

        if ($exception) {
            report($exception);
        }
    }

    private function scaleDownToMaxSide($image, int $maxSide): void
    {
        if ($image->width() >= $image->height()) {
            $image->scaleDown(width: $maxSide);
        } else {
            $image->scaleDown(height: $maxSide);
        }
    }
}
