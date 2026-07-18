<?php

namespace Tests\Feature\Database;

use App\Domain\Cafe\Models\Cafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrgmSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgres_similarity_finds_a_typo_tolerantly(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('pg_trgm is exercised by the PostgreSQL CI job.');
        }

        Cafe::create(['name' => 'Kopi Anjis Perintis', 'slug' => 'kopi-anjis-perintis', 'city' => 'makassar', 'area' => 'tamalanrea', 'lat' => -5.1476651, 'lng' => 119.4327311]);

        $similarity = DB::table('cafes')->selectRaw("similarity(name, ?) as score", ['kopi anjs'])->value('score');

        $this->assertGreaterThan(0.3, (float) $similarity);
    }
}
