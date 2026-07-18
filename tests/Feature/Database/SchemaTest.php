<?php

namespace Tests\Feature\Database;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ngafe_core_tables_and_columns_exist(): void
    {
        foreach (['cafes', 'categories', 'reviews', 'photos', 'reports', 'cafe_category', 'review_tags'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasColumns('cafes', ['id', 'city', 'opening_hours_override', 'rating_avg', 'trending_score']));
        $this->assertTrue(Schema::hasColumns('users', ['google_sub', 'display_alias_seed', 'role', 'status']));
    }

    public function test_reviews_and_cafe_slugs_are_unique_in_their_context(): void
    {
        $user = User::factory()->create();
        $cafe = Cafe::create(['name' => 'Kopi Anjis', 'slug' => 'kopi-anjis', 'city' => 'makassar', 'area' => 'tamalanrea', 'lat' => -5.1476651, 'lng' => 119.4327311]);
        Review::create(['user_id' => $user->id, 'cafe_id' => $cafe->id, 'rating' => 5, 'body' => str_repeat('Enak dan nyaman untuk nugas. ', 2), 'display_alias' => 'Penikmat Kopi']);

        $this->expectException(QueryException::class);
        Review::create(['user_id' => $user->id, 'cafe_id' => $cafe->id, 'rating' => 4, 'body' => str_repeat('Tempatnya tenang dan wifi cepat. ', 2), 'display_alias' => 'Penikmat Kopi']);
    }
}
