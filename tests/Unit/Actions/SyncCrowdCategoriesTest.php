<?php

namespace Tests\Unit\Actions;

use App\Domain\Cafe\Actions\SyncCrowdCategories;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Review\Events\ReviewStatusChanged;
use App\Domain\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncCrowdCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_is_shown_when_at_least_thirty_percent_of_published_reviewers_tag_it(): void
    {
        $cafe = Cafe::factory()->create();
        $category = Category::query()->create([
            'name' => 'Wifi kencang',
            'slug' => 'wifi-kencang',
            'icon' => 'WifiIcon',
            'sort_order' => 1,
        ]);
        $reviews = Review::factory()->count(10)->create(['cafe_id' => $cafe->id]);
        $reviews->take(3)->each(fn (Review $review) => $review->tags()->attach($category));

        app(SyncCrowdCategories::class)->handle($cafe);

        $this->assertDatabaseHas('cafe_category', [
            'cafe_id' => $cafe->id,
            'category_id' => $category->id,
            'source' => 'crowd',
            'confidence' => 0.300,
        ]);
    }

    public function test_category_is_removed_when_support_falls_below_thirty_percent_without_touching_admin_categories(): void
    {
        $cafe = Cafe::factory()->create();
        $crowd = Category::query()->create(['name' => 'Wifi', 'slug' => 'wifi', 'icon' => 'WifiIcon', 'sort_order' => 1]);
        $admin = Category::query()->create(['name' => 'Tenang', 'slug' => 'tenang', 'icon' => 'MoonIcon', 'sort_order' => 2]);
        $reviews = Review::factory()->count(10)->create(['cafe_id' => $cafe->id]);
        $reviews->take(3)->each(fn (Review $review) => $review->tags()->attach($crowd));
        $cafe->categories()->attach($admin, ['source' => 'admin']);

        $sync = app(SyncCrowdCategories::class);
        $sync->handle($cafe);
        $reviews->get(2)->tags()->detach($crowd);
        $sync->handle($cafe);

        $this->assertDatabaseMissing('cafe_category', [
            'cafe_id' => $cafe->id,
            'category_id' => $crowd->id,
            'source' => 'crowd',
        ]);
        $this->assertDatabaseHas('cafe_category', [
            'cafe_id' => $cafe->id,
            'category_id' => $admin->id,
            'source' => 'admin',
        ]);
    }

    public function test_review_status_event_runs_the_crowd_category_sync(): void
    {
        $cafe = Cafe::factory()->create();
        $category = Category::query()->create(['name' => 'Aesthetic', 'slug' => 'aesthetic', 'icon' => 'SparklesIcon', 'sort_order' => 1]);
        $review = Review::factory()->create(['cafe_id' => $cafe->id]);
        $review->tags()->attach($category);

        ReviewStatusChanged::dispatch($review, null);

        $this->assertDatabaseHas('cafe_category', [
            'cafe_id' => $cafe->id,
            'category_id' => $category->id,
            'source' => 'crowd',
            'confidence' => 1.000,
        ]);
    }
}
