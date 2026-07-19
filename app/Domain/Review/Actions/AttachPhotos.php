<?php

namespace App\Domain\Review\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Review\Exceptions\PhotoValidationFailed;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use App\Jobs\ProcessReviewPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AttachPhotos
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Photo>
     */
    public function handle(User $user, Review $review, array $files): array
    {
        if ((string) $review->user_id !== (string) $user->id || count($files) > 4) {
            throw new PhotoValidationFailed;
        }

        $key = "photo:day:{$user->id}";
        if (RateLimiter::remaining($key, (int) config('rate_limits.photo.per_day')) < count($files)) {
            throw new PhotoValidationFailed('daily-limit');
        }

        $photos = [];
        foreach ($files as $file) {
            $binary = file_get_contents($file->getRealPath());
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary ?: '');
            if ($binary === false || $file->getSize() > self::MAX_BYTES || ! in_array($mime, self::ALLOWED_MIMES, true)) {
                throw new PhotoValidationFailed;
            }

            $hash = hash('sha256', $binary);
            $existing = Photo::query()->where('content_hash', $hash)->first();
            if ($existing) {
                if ((string) $existing->review_id !== (string) $review->id) {
                    throw new PhotoValidationFailed;
                }
                if ($existing->status === 'failed') {
                    ProcessReviewPhoto::dispatch($existing->id, $existing->processing_error ?? '');
                }
                $photos[] = $existing;

                continue;
            }

            $stagingPath = 'review-photo-staging/'.Str::uuid().'.upload';
            Storage::disk('local')->put($stagingPath, $binary);

            $photo = Photo::query()->create([
                'review_id' => $review->id,
                'cafe_id' => $review->cafe_id,
                'url_card' => '',
                'url_full' => '',
                'width' => 1,
                'height' => 1,
                'status' => 'pending',
                'processing_error' => null,
                'content_hash' => $hash,
            ]);

            ProcessReviewPhoto::dispatch($photo->id, $stagingPath);
            RateLimiter::hit($key, 86400);
            $photos[] = $photo;
        }

        return $photos;
    }
}
