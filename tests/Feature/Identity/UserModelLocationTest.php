<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UserModelLocationTest extends TestCase
{
    public function test_the_auth_provider_and_factory_use_the_identity_user_model(): void
    {
        $this->assertSame(User::class, Config::get('auth.providers.users.model'));
        $this->assertInstanceOf(User::class, User::factory()->make());
    }
}
