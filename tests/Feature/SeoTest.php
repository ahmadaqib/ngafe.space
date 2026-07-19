<?php

namespace Tests\Feature;

use App\Domain\Cafe\Exceptions\ReservedSlugPrefix;
use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cafe_detail_page_has_valid_json_ld_with_local_business_and_aggregate_rating(): void
    {
        $cafe = Cafe::factory()->create(['name' => 'Kopi Anjis Perintis', 'slug' => 'kopi-anjis-perintis', 'city' => 'makassar', 'status' => 'active', 'rating_avg' => 4.6, 'rating_count' => 12]);
        Review::factory()->for($cafe)->create(['status' => 'published']);

        $response = $this->get('/makassar/kopi-anjis-perintis');

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('<link rel="canonical" href="'.route('cafe.show', ['city' => 'makassar', 'slug' => 'kopi-anjis-perintis']).'"', false);

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $jsonLd = json_decode($matches[1], true);

        $this->assertSame('CafeOrCoffeeShop', $jsonLd['@type']);
        $this->assertSame('Kopi Anjis Perintis', $jsonLd['name']);
        $this->assertSame(4.6, $jsonLd['aggregateRating']['ratingValue']);
        $this->assertSame(12, $jsonLd['aggregateRating']['reviewCount']);
    }

    public function test_json_ld_cannot_be_broken_out_of_by_a_closing_script_tag_in_cafe_fields(): void
    {
        // A cafe name/address containing "</script>" must not be able to
        // terminate the JSON-LD <script> tag early and inject markup —
        // json_encode's default `/` -> `\/` escaping is what prevents this;
        // JSON_UNESCAPED_SLASHES must never be used for this block.
        $cafe = Cafe::factory()->create([
            'name' => '</script><img src=x onerror=alert(1)>',
            'address' => '</script><script>alert(2)</script>',
            'slug' => 'nama-berbahaya',
            'status' => 'active',
        ]);
        Review::factory()->for($cafe)->create(['status' => 'published']);

        $response = $this->get('/makassar/nama-berbahaya');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString('</script><img', $body);
        $this->assertStringContainsString('<\/script><img', $body);

        // Non-greedy match stops at the first real `</script>`. If the
        // payload's internal slash weren't escaped, that would be the
        // injected tag rather than this block's real closing tag, and the
        // captured "JSON" would fail to decode.
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $body, $matches);
        $jsonLd = json_decode($matches[1] ?? '', true);
        $this->assertIsArray($jsonLd);
        $this->assertSame('</script><img src=x onerror=alert(1)>', $jsonLd['name']);
    }

    public function test_cafe_detail_page_without_ratings_omits_aggregate_rating(): void
    {
        $cafe = Cafe::factory()->create(['slug' => 'belum-ada-rating', 'status' => 'active']);
        Review::factory()->for($cafe)->create(['status' => 'published']);

        $response = $this->get('/makassar/belum-ada-rating');

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $jsonLd = json_decode($matches[1], true);

        $this->assertArrayNotHasKey('aggregateRating', $jsonLd);
    }

    public function test_sitemap_only_lists_active_cafes_and_populated_category_city_pages(): void
    {
        $category = Category::factory()->create(['name' => 'Wifi kencang', 'slug' => 'wifi-kencang']);
        $active = Cafe::factory()->create(['slug' => 'aktif-sitemap', 'city' => 'makassar', 'status' => 'active']);
        $active->categories()->attach($category, ['source' => 'admin']);
        Cafe::factory()->create(['slug' => 'pending-sitemap', 'city' => 'makassar', 'status' => 'pending']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('cafe.show', ['city' => 'makassar', 'slug' => 'aktif-sitemap']), false);
        $response->assertDontSee('pending-sitemap');
        $response->assertSee(route('category-city', ['city' => 'makassar', 'categorySlug' => 'wifi-kencang']), false);
    }

    public function test_sitemap_omits_category_city_pages_with_no_active_cafes(): void
    {
        Category::factory()->create(['name' => 'Musala & parkir gampang', 'slug' => 'musala-parkir-gampang']);

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('musala-parkir-gampang');
    }

    public function test_category_city_page_is_indexable_and_lists_active_cafes(): void
    {
        $category = Category::factory()->create(['name' => 'Wifi kencang', 'slug' => 'wifi-kencang']);
        $cafe = Cafe::factory()->create(['name' => 'Bilik Kayu Tamalanrea', 'slug' => 'bilik-kayu', 'city' => 'makassar', 'status' => 'active']);
        $cafe->categories()->attach($category, ['source' => 'admin']);

        $response = $this->get('/makassar/cafe-wifi-kencang');

        $response->assertOk();
        $response->assertSee('Bilik Kayu Tamalanrea');
        $response->assertSee('Wifi kencang');
    }

    public function test_category_city_page_returns_404_for_unknown_category(): void
    {
        $this->get('/makassar/cafe-tidak-ada')->assertNotFound();
    }

    public function test_cafe_slug_cannot_start_with_the_category_city_route_prefix(): void
    {
        // /{city}/cafe-{categorySlug} is registered before cafe.show (both
        // match /{city}/{something}), so a cafe slug starting with "cafe-"
        // would be shadowed and become unreachable. Rejected at the model
        // layer instead of silently producing a dead page.
        $this->expectException(ReservedSlugPrefix::class);

        Cafe::factory()->create(['slug' => 'cafe-hopper-spot', 'city' => 'makassar', 'status' => 'active']);
    }
}
