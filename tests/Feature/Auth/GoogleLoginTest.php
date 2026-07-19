<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Actions\HandleGoogleCallback;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use RuntimeException;
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

    public function test_existing_ban_and_admin_role_survive_google_reauthentication(): void
    {
        $banned = User::factory()->create([
            'google_sub' => 'banned-sub',
            'role' => 'user',
            'status' => 'banned',
        ]);
        $admin = User::factory()->create([
            'google_sub' => 'admin-sub',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $action = app(HandleGoogleCallback::class);
        $action->handle($this->googleUser('banned-sub', 'banned-new@example.test'));
        $action->handle($this->googleUser('admin-sub', 'admin-new@example.test'));

        $this->assertSame('banned', $banned->fresh()->status);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_redirect_stores_only_a_safe_same_origin_intended_url_and_review_intent(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('scopes')->once()->with(['openid', 'email'])->andReturnSelf();
        $driver->shouldReceive('redirect')->once()->andReturn(redirect('/fake-google-consent'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->get('/auth/google/redirect?intended=https://evil.example/phish&intent=review:cafe-1')
            ->assertRedirect('/fake-google-consent');

        $this->assertSame('/', session('auth.intended_url'));
        $this->assertSame('review:cafe-1', session('auth.intent'));
    }

    public function test_callback_creates_and_logs_in_new_user_then_returns_to_exact_context(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($this->googleUser('new-google-sub', 'baru@example.test'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->withSession(['auth.intended_url' => '/makassar/kedai-kala#review-form'])
            ->get('/auth/google/callback')
            ->assertRedirect('/makassar/kedai-kala#review-form');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['google_sub' => 'new-google-sub', 'email' => 'baru@example.test', 'name' => null]);
    }

    public function test_cancelled_or_invalid_callback_returns_safely_without_crashing(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andThrow(new RuntimeException('Invalid OAuth state.'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->withSession(['auth.intended_url' => '/cari'])
            ->get('/auth/google/callback')
            ->assertRedirect('/cari')
            ->assertSessionHas('toast_error');

        $this->assertGuest();
    }

    public function test_banned_user_cannot_create_an_authenticated_session(): void
    {
        User::factory()->create(['google_sub' => 'blocked-sub', 'status' => 'banned']);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($this->googleUser('blocked-sub', 'blocked@example.test'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->withSession(['auth.intended_url' => '/kontribusimu'])
            ->get('/auth/google/callback')
            ->assertRedirect('/kontribusimu')
            ->assertSessionHas('toast_error');

        $this->assertGuest();
    }

    private function googleUser(string $id, string $email): SocialiteUser
    {
        return new class($id, $email) implements SocialiteUser
        {
            public function __construct(private string $id, private string $email) {}

            public function getId()
            {
                return $this->id;
            }

            public function getNickname()
            {
                return null;
            }

            public function getName()
            {
                return 'Tidak Disimpan';
            }

            public function getEmail()
            {
                return $this->email;
            }

            public function getAvatar()
            {
                return 'https://example.test/avatar.jpg';
            }
        };
    }
}
