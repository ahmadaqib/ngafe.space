<?php

namespace Tests\Unit\Jobs;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Review\Models\Photo;
use App\Jobs\GenerateShareCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GenerateShareCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_deterministic_card_and_updates_the_cafe(): void
    {
        Storage::fake('r2');
        Http::fake(['*' => Http::response(file_get_contents(base_path('tests/Fixtures/sample-photo.jpg')), 200)]);

        $cafe = Cafe::factory()->create(['name' => 'Kopi Anjis Perintis', 'rating_avg' => 4.6, 'rating_count' => 12]);
        $category = Category::factory()->create(['name' => 'Wifi kencang']);
        $cafe->categories()->attach($category, ['source' => 'admin']);
        Photo::factory()->create(['cafe_id' => $cafe->id, 'status' => 'published']);

        (new GenerateShareCard($cafe->id))->handle();

        $path = "share-cards/{$cafe->id}.webp";
        Storage::disk('r2')->assertExists($path);

        $cafe->refresh();
        $this->assertNotNull($cafe->share_card_url);
        $this->assertStringContainsString($path, $cafe->share_card_url);
    }

    public function test_it_falls_back_to_a_solid_background_when_the_cafe_has_no_photo(): void
    {
        Storage::fake('r2');
        Http::fake();

        $cafe = Cafe::factory()->create(['rating_count' => 0]);

        (new GenerateShareCard($cafe->id))->handle();

        Storage::disk('r2')->assertExists("share-cards/{$cafe->id}.webp");
    }

    public function test_it_is_idempotent_and_overwrites_deterministically(): void
    {
        Storage::fake('r2');
        Http::fake();

        $cafe = Cafe::factory()->create();

        (new GenerateShareCard($cafe->id))->handle();
        $firstSize = Storage::disk('r2')->size("share-cards/{$cafe->id}.webp");

        $cafe->update(['name' => 'Nama Baru Setelah Update']);
        (new GenerateShareCard($cafe->id))->handle();

        Storage::disk('r2')->assertExists("share-cards/{$cafe->id}.webp");
        $this->assertNotSame($firstSize, Storage::disk('r2')->size("share-cards/{$cafe->id}.webp"));
    }

    public function test_it_does_nothing_when_the_cafe_no_longer_exists(): void
    {
        Storage::fake('r2');

        $job = new GenerateShareCard((string) Str::ulid());
        $job->handle();

        Storage::disk('r2')->assertDirectoryEmpty('share-cards');
    }
}
