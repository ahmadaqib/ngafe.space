<?php

namespace Tests\Feature\Api;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsNoPii;
use Tests\TestCase;

class NoPiiTest extends TestCase
{
    use AssertsNoPii, RefreshDatabase;

    public function test_public_reviews_never_expose_identity_fields(): void
    {
        $user = User::factory()->create(['google_sub' => 'google-sub-private']);
        $cafe = Cafe::create(['name' => 'Kedai Kala', 'slug' => 'kedai-kala', 'city' => 'makassar', 'area' => 'tamalanrea', 'lat' => -5.1476651, 'lng' => 119.4327311]);
        Review::create(['user_id' => $user->id, 'cafe_id' => $cafe->id, 'rating' => 5, 'body' => 'Tempatnya nyaman sekali untuk menyelesaikan tugas dan kopi susunya juga enak.', 'display_alias' => 'Kawan Ngafe']);

        $response = $this->getJson("/api/public/cafes/{$cafe->id}/reviews");

        $response->assertOk()->assertJsonPath('data.0.display_alias', 'Kawan Ngafe');
        $this->assertNoPii($response, $user);
    }
}
