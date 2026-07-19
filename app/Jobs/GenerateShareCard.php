<?php

namespace App\Jobs;

use App\Domain\Cafe\Models\Cafe;
use App\Support\Format;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use Throwable;

/**
 * Docs/Spec.md §10 SEO — "Open Graph share card otomatis per cafe": WA is
 * the #1 distribution channel, so the link preview IS the acquisition
 * surface. Deterministic overwrite at a fixed path per cafe (idempotent —
 * safe to dispatch again any time rating/photo changes, per §10 v1.4 job
 * rules and Docs/plan.md Task 5.2).
 */
final class GenerateShareCard implements ShouldQueue
{
    use Queueable;

    private const WIDTH = 1200;

    private const HEIGHT = 630;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public string $cafeId) {}

    public function handle(): void
    {
        $cafe = Cafe::query()->with(['categories', 'photos' => fn ($q) => $q->where('status', 'published')])->find($this->cafeId);
        if (! $cafe) {
            return;
        }

        $manager = new ImageManager(new Driver);
        $canvas = $manager->createImage(self::WIDTH, self::HEIGHT);

        $photoUrl = $cafe->photos->first()?->url_full;
        $background = $photoUrl ? $this->fetchPhoto($photoUrl) : null;

        if ($background) {
            $canvas->insert($background->cover(self::WIDTH, self::HEIGHT), 0, 0);
        } else {
            $canvas->fill('#C4451C');
        }

        $this->drawScrim($canvas);
        $this->drawText($canvas, $cafe);

        $path = "share-cards/{$cafe->id}.webp";
        Storage::disk('r2')->put($path, (string) $canvas->encode(new WebpEncoder(quality: 80)));

        $cafe->update(['share_card_url' => Storage::disk('r2')->url($path)]);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }

    private function fetchPhoto(string $url): ?ImageInterface
    {
        try {
            $response = Http::timeout(5)->retry(2, 200)->get($url);
            if (! $response->successful()) {
                return null;
            }

            return (new ImageManager(new Driver))->read($response->body());
        } catch (Throwable) {
            return null;
        }
    }

    private function drawScrim(ImageInterface $canvas): void
    {
        // The one gradient the design system allows (--img-scrim, §12.2):
        // transparent at the top fading to ~60% black at the bottom, banded
        // into thin strips rather than per-pixel — visually smooth at
        // social preview size, far fewer draw calls.
        $bands = 90;
        $bandHeight = (int) ceil(self::HEIGHT / $bands);
        for ($i = 0; $i < $bands; $i++) {
            $progress = $i / ($bands - 1);
            $alpha = 0.6 * $progress;
            $y = $i * $bandHeight;
            $canvas->drawRectangle(function ($rectangle) use ($bandHeight, $alpha, $y): void {
                $rectangle->size(self::WIDTH, $bandHeight);
                $rectangle->at(0, $y);
                $rectangle->background(new Color(0, 0, 0, $alpha));
            });
        }
    }

    private function drawText(ImageInterface $canvas, Cafe $cafe): void
    {
        $bold = resource_path('fonts/PlusJakartaSans-Bold.ttf');
        $medium = resource_path('fonts/PlusJakartaSans-Medium.ttf');

        $canvas->text($cafe->name, 64, self::HEIGHT - 160, function (FontFactory $font) use ($bold): void {
            $font->filename($bold);
            $font->size(52);
            $font->color('#FFFFFF');
        });

        // Not "★ …": Plus Jakarta Sans doesn't ship the star glyph, which
        // GD/FreeType renders as a tofu box instead of failing loudly.
        $meta = $cafe->rating_count > 0
            ? 'Rating '.Format::rating($cafe->rating_avg)." · {$cafe->rating_count} review"
            : 'Belum ada review — jadi yang pertama?';
        $tags = $cafe->categories->take(2)->pluck('name')->join(' · ');
        if ($tags !== '') {
            $meta .= " · {$tags}";
        }

        $canvas->text($meta, 64, self::HEIGHT - 110, function (FontFactory $font) use ($medium): void {
            $font->filename($medium);
            $font->size(28);
            $font->color('#FFFFFF');
        });

        $canvas->text('ngafe.space', 64, self::HEIGHT - 56, function (FontFactory $font) use ($medium): void {
            $font->filename($medium);
            $font->size(24);
            $font->color('#F2EEE8');
        });
    }
}
