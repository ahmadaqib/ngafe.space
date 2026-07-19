<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_every_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=()');
    }

    public function test_csp_is_self_scoped_with_a_fresh_nonce_and_permits_alpine_eval(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]{32}' 'unsafe-eval'/", $csp);

        $second = $this->get('/')->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([A-Za-z0-9]{32})'/", $csp, $first);
        preg_match("/'nonce-([A-Za-z0-9]{32})'/", $second, $secondMatch);
        $this->assertNotSame($first[1], $secondMatch[1]);
    }

    public function test_inline_scripts_carry_the_response_nonce_and_json_ld_does_not_need_one(): void
    {
        $cafe = Cafe::factory()->create(['slug' => 'nonce-check', 'status' => 'active']);

        $response = $this->get("/makassar/{$cafe->slug}");
        $csp = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([A-Za-z0-9]{32})'/", $csp, $match);
        $nonce = $match[1];

        $response->assertSee("nonce=\"{$nonce}\"", false);
        $response->assertSee('<script type="application/ld+json">', false);
    }

    public function test_r2_public_url_host_is_allowed_in_img_src_when_configured(): void
    {
        config(['filesystems.disks.r2.url' => 'https://cdn.ngafe.space']);

        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://cdn.ngafe.space', $csp);
    }
}
