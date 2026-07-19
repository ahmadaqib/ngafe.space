<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Livewire\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_updates_count_without_reload_and_uses_debounce(): void
    {
        Cafe::factory()->create(['name' => 'Kopi Perintis', 'slug' => 'kopi-perintis']);
        Cafe::factory()->create(['name' => 'Teh Panakkukang', 'slug' => 'teh-panakkukang']);

        $this->get('/cari')->assertOk()->assertSee('wire:model.live.debounce.250ms="q"', false);
        $startedAt = hrtime(true);
        Livewire::test(Search::class)->set('q', 'Kopi')->assertSee('1 cafe cocok')->assertSee('Kopi Perintis')->assertDontSee('Teh Panakkukang');
        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        $this->assertLessThan(500, $elapsedMilliseconds, "Search render took {$elapsedMilliseconds}ms");
    }

    public function test_empty_state_suggests_the_filter_with_largest_result_gain(): void
    {
        $rare = Category::query()->create(['name' => 'Buka 24 jam', 'slug' => 'buka-24-jam', 'icon' => 'Clock', 'sort_order' => 1]);
        $common = Category::query()->create(['name' => 'Ada WiFi', 'slug' => 'wifi', 'icon' => 'Wifi', 'sort_order' => 2]);
        Cafe::factory()->create()->categories()->attach($common);
        Cafe::factory()->create()->categories()->attach($common);
        Cafe::factory()->create()->categories()->attach($rare);

        Livewire::test(Search::class)
            ->set('categorySlugs', ['buka-24-jam', 'wifi'])
            ->assertSee('Coba lepas “Buka 24 jam” — ada 2 cafe lain.')
            ->call('removeCategory', 'buka-24-jam')
            ->assertSee('2 cafe cocok');
    }
}
