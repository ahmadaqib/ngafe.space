<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsNoPii;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use AssertsNoPii,RefreshDatabase;

    public function test_home_only_lists_cafes_with_published_reviews(): void
    {
        $user = User::factory()->create();
        $shown = Cafe::factory()->create(['name' => 'Yang Tampil', 'status' => 'active', 'rating_avg' => 4.6, 'rating_count' => 1]);
        Review::factory()->create(['user_id' => $user->id, 'cafe_id' => $shown->id]);
        Cafe::factory()->create(['name' => 'Jangan Tampil', 'status' => 'active']);
        $response = $this->get('/');
        $response->assertOk()->assertSee('Yang Tampil')->assertDontSee('Jangan Tampil');
        $response->assertSee('4,6 · 1 review')->assertSee('Cari space-mu buat ngafe');
        $this->assertNoPii($response, $user);
    }

    public function test_contextual_categories_are_ordered_for_weekday_and_weekend(): void
    {
        Category::query()->create(['name' => 'Biasa', 'slug' => 'biasa', 'icon' => 'Coffee', 'sort_order' => 1]);
        Category::query()->create(['name' => 'Cocok nugas & WFC', 'slug' => 'cocok-nugas-wfc', 'icon' => 'Laptop', 'sort_order' => 9]);
        Category::query()->create(['name' => 'Hidden gem / baru buka', 'slug' => 'hidden-gem-baru-buka', 'icon' => 'Gem', 'sort_order' => 8]);
        Category::query()->create(['name' => 'Aesthetic', 'slug' => 'aesthetic', 'icon' => 'Sparkles', 'sort_order' => 7]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-20 10:00', 'Asia/Makassar'));
        $weekday = $this->get('/')->getContent();
        $this->assertLessThan(strpos($weekday, 'Biasa'), strpos($weekday, 'Cocok nugas &amp; WFC'));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-19 10:00', 'Asia/Makassar'));
        $weekend = $this->get('/')->getContent();
        $this->assertLessThan(strpos($weekend, 'Biasa'), strpos($weekend, 'Hidden gem / baru buka'));
        $this->assertLessThan(strpos($weekend, 'Biasa'), strpos($weekend, 'Aesthetic'));
        CarbonImmutable::setTestNow();
    }
}
