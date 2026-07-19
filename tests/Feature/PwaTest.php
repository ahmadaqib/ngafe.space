<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Docs/plan.md Task 5.3. public/manifest.webmanifest, public/sw.js and
 * public/offline.html are static files a real web server (or `php artisan
 * serve`) resolves before even reaching Laravel — Laravel's HTTP test
 * client invokes the kernel directly and skips that step, so it 404s on
 * them regardless of whether they're served correctly in production.
 * Checked via the filesystem here; checked via a real browser fetching
 * them for real in tests/Browser/PwaTest.php.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_valid_json_with_required_installability_fields(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#c4451c', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_file_exists_at_the_root_scope(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString('caches.open', file_get_contents(public_path('sw.js')));
    }

    public function test_offline_fallback_page_exists_with_the_expected_copy(): void
    {
        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString('Sinyalnya lagi ngambek', file_get_contents(public_path('offline.html')));
    }

    public function test_homepage_links_the_manifest_and_registers_the_service_worker(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="manifest" href="'.asset('manifest.webmanifest').'"', false);
        $response->assertSee("navigator.serviceWorker.register('/sw.js')", false);
    }
}
