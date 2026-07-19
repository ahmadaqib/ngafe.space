<?php

namespace Tests\Unit;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Cafe\Queries\SearchCafes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchCafesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scopes_active_cafes_and_intersects_categories(): void
    {
        $a = Category::create(['name' => 'Wifi', 'slug' => 'wifi', 'icon' => 'Wifi', 'sort_order' => 1]);
        $b = Category::create(['name' => 'WFC', 'slug' => 'wfc', 'icon' => 'Desk', 'sort_order' => 2]);
        $match = Cafe::factory()->create(['status' => 'active']);
        $match->categories()->attach([$a->id, $b->id]);
        $other = Cafe::factory()->create(['status' => 'active']);
        $other->categories()->attach($a->id);
        Cafe::factory()->create(['status' => 'pending']);
        $results = app(SearchCafes::class)->run(null, ['wifi', 'wfc'], null, null, null);
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($match));
    }

    public function test_it_orders_results_by_haversine_distance(): void
    {
        $near = Cafe::factory()->create(['status' => 'active', 'lat' => -5.147, 'lng' => 119.432]);
        $middle = Cafe::factory()->create(['status' => 'active', 'lat' => -5.160, 'lng' => 119.445]);
        $far = Cafe::factory()->create(['status' => 'active', 'lat' => -5.190, 'lng' => 119.490]);
        $results = app(SearchCafes::class)->run(null, [], -5.147, 119.432, null);
        $this->assertSame([$near->id, $middle->id, $far->id], $results->pluck('id')->all());
    }
}
