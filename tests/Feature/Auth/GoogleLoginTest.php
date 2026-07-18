<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Actions\HandleGoogleCallback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_sub_is_the_identity_and_email_can_change(): void
    {
        $action = app(HandleGoogleCallback::class);
        $first = $action->handle($this->googleUser('google-sub-123', 'lama@example.test'));
        $same = $action->handle($this->googleUser('google-sub-123', 'baru@example.test'));

        $this->assertTrue($first->is($same));
        $this->assertSame('baru@example.test', $same->email);
        $this->assertNotEmpty($same->display_alias_seed);
        $this->assertNull($same->getAttribute('name'));
    }

    private function googleUser(string $id, string $email): SocialiteUser
    {
        return new class($id, $email) implements SocialiteUser {
            public function __construct(private string $id, private string $email) {}
            public function getId() { return $this->id; }
            public function getNickname() { return null; }
            public function getName() { return 'Tidak Disimpan'; }
            public function getEmail() { return $this->email; }
            public function getAvatar() { return 'https://example.test/avatar.jpg'; }
        };
    }
}
