<?php

namespace Tests\Feature;

use App\Domain\Cafe\Actions\SyncHiddenGem;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Review\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignHiddenGemTest extends TestCase
{
    use RefreshDatabase;

    private Category $hiddenGem;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-07-19 12:00:00');
        $this->hiddenGem = Category::query()->create([
            'name' => 'Hidden gem / baru buka',
            'slug' => 'hidden-gem-baru-buka',
            'icon' => 'MapPinIcon',
            'sort_order' => 9,
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_recent_cafe_is_auto_assigned_even_with_ten_reviews(): void
    {
        $cafe = Cafe::factory()->create(['created_at' => now()->subDays(30)]);
        Review::factory()->count(10)->create(['cafe_id' => $cafe->id]);

        app(SyncHiddenGem::class)->handle($cafe);

        $this->assertAutoHiddenGemExists($cafe);
    }

    public function test_old_cafe_is_auto_assigned_while_it_has_fewer_than_ten_reviews(): void
    {
        $cafe = Cafe::factory()->create(['created_at' => now()->subDays(120)]);
        Review::factory()->count(9)->create(['cafe_id' => $cafe->id]);

        app(SyncHiddenGem::class)->handle($cafe);

        $this->assertAutoHiddenGemExists($cafe);
    }

    public function test_auto_label_is_removed_after_both_thresholds_are_passed(): void
    {
        $cafe = Cafe::factory()->create(['created_at' => now()->subDays(120)]);
        $cafe->categories()->attach($this->hiddenGem, ['source' => 'auto', 'confidence' => 1]);
        Review::factory()->count(10)->create(['cafe_id' => $cafe->id]);

        app(SyncHiddenGem::class)->handle($cafe);

        $this->assertDatabaseMissing('cafe_category', [
            'cafe_id' => $cafe->id,
            'category_id' => $this->hiddenGem->id,
            'source' => 'auto',
        ]);
    }

    public function test_daily_command_syncs_active_cafes(): void
    {
        $cafe = Cafe::factory()->create(['created_at' => now()->subDays(120)]);

        $this->artisan('cafes:assign-hidden-gem')
            ->expectsOutput('Hidden gem tersinkron untuk 1 cafe aktif.')
            ->assertSuccessful();

        $this->assertAutoHiddenGemExists($cafe);
    }

    public function test_approval_assigns_hidden_gem_immediately(): void
    {
        $cafe = Cafe::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(120),
        ]);

        $cafe->update(['status' => 'active']);

        $this->assertAutoHiddenGemExists($cafe);
    }

    private function assertAutoHiddenGemExists(Cafe $cafe): void
    {
        $this->assertDatabaseHas('cafe_category', [
            'cafe_id' => $cafe->id,
            'category_id' => $this->hiddenGem->id,
            'source' => 'auto',
            'confidence' => 1.000,
        ]);
    }
}
