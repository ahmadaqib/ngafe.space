<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Docs/Spec.md §10: "login attempt per IP" — the one rate limiter that
 * fits Laravel's route-level throttle:name middleware directly (Docs/plan.md
 * Task 5.4). Named limiter registered in AppServiceProvider::boot().
 */
class LoginRateLimitTest extends TestCase
{
    public function test_google_redirect_is_throttled_per_ip(): void
    {
        $limit = (int) config('rate_limits.login.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->get('/auth/google/redirect')->assertRedirect();
        }

        $this->get('/auth/google/redirect')->assertStatus(429);
    }

    public function test_google_callback_is_throttled_per_ip(): void
    {
        $limit = (int) config('rate_limits.login.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->get('/auth/google/callback');
        }

        $this->get('/auth/google/callback')->assertStatus(429);
    }
}
