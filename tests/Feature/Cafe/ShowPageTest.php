<?php

namespace Tests\Feature\Cafe;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsNoPii;
use Tests\TestCase;

class ShowPageTest extends TestCase
{
    use AssertsNoPii, RefreshDatabase;

    public function test_only_active_cafes_are_public(): void
    {
        $active = Cafe::factory()->create(['slug' => 'aktif', 'status' => 'active']);
        Cafe::factory()->create(['slug' => 'pending', 'status' => 'pending']);

        $this->get('/makassar/aktif')->assertOk()->assertSee($active->name);
        $this->get('/makassar/pending')->assertNotFound();
    }

    public function test_public_detail_exposes_review_alias_but_not_author_identity(): void
    {
        $author = User::factory()->create(['google_sub' => 'private-google-sub']);
        $cafe = Cafe::factory()->create(['slug' => 'aman-pii', 'status' => 'active']);
        $review = Review::factory()->create([
            'user_id' => $author->id,
            'cafe_id' => $cafe->id,
            'display_alias' => 'Penikmat Senja Tamalanrea',
        ]);

        $response = $this->get('/makassar/aman-pii');

        $response->assertOk()->assertSee($review->display_alias);
        $this->assertNoPii($response, $author);
    }

    public function test_authenticated_page_is_never_shared_cacheable(): void
    {
        Cafe::factory()->create(['slug' => 'cache-test', 'status' => 'active']);

        $this->actingAs(User::factory()->create())
            ->get('/makassar/cache-test')
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
    }

    public function test_author_sees_only_their_pending_review_for_the_current_cafe(): void
    {
        $author = User::factory()->create();
        $currentCafe = Cafe::factory()->create(['slug' => 'cafe-current', 'status' => 'active']);
        $otherCafe = Cafe::factory()->create(['slug' => 'cafe-other', 'status' => 'active']);
        Review::factory()->create([
            'user_id' => $author->id,
            'cafe_id' => $currentCafe->id,
            'body' => 'Review pending yang benar untuk cafe yang sedang dibuka.',
            'status' => 'pending',
        ]);
        Review::factory()->create([
            'user_id' => $author->id,
            'cafe_id' => $otherCafe->id,
            'body' => 'Review pending dari cafe lain tidak boleh ikut bocor.',
            'status' => 'pending',
        ]);

        $this->actingAs($author)->get('/makassar/cafe-current')
            ->assertOk()
            ->assertSee('Review pending yang benar')
            ->assertDontSee('Review pending dari cafe lain')
            ->assertSee('sedang ditinjau');
    }
}
