<?php

namespace Tests\Unit;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Support\AliasGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AliasGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_alias_is_deterministic_per_user_and_cafe_but_differs_across_pairs(): void
    {
        $generator = app(AliasGenerator::class);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $cafeA = Cafe::factory()->create();
        $cafeB = Cafe::factory()->create();

        $alias = $generator->for($userA, $cafeA);

        $this->assertSame($alias, $generator->for($userA, $cafeA));
        $this->assertNotSame($alias, $generator->for($userA, $cafeB));
        $this->assertNotSame($alias, $generator->for($userB, $cafeA));
        $this->assertNotSame((string) $userA->id, $alias);
        $this->assertStringNotContainsString((string) $cafeA->id, $alias);
    }
}
