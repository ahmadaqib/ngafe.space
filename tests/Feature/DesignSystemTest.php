<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_tokens_include_complete_palettes_semantics_and_only_allowed_radii(): void
    {
        $tokens = file_get_contents(resource_path('css/tokens.css'));

        foreach (['--terra-900', '--leaf-900', '--sand-900', '--petrol-900', '--gold-500', '--accent-cool', '--info-subtle', '--motion-sheet', '--z-toast'] as $token) {
            $this->assertStringContainsString($token, $tokens);
        }
        $this->assertStringContainsString('--radius-sm: 8px', $tokens);
        $this->assertStringContainsString('--radius-md: 12px', $tokens);
        $this->assertStringContainsString('--radius-full: 9999px', $tokens);
        $this->assertStringNotContainsString('--radius-card', $tokens);
    }

    public function test_plus_jakarta_is_bundled_locally_and_sheet_has_both_snap_points(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $sheet = file_get_contents(resource_path('views/components/ui/sheet.blade.php'));

        $this->assertStringContainsString('@fontsource-variable/plus-jakarta-sans', $css);
        $this->assertStringContainsString('is-full', $sheet);
        $this->assertStringContainsString('is-peek', $sheet);
        $this->assertStringContainsString('ngafe-sheet__handle', $sheet);
        $this->assertStringContainsString('@pointermove', $sheet);
    }

    public function test_primary_cta_contrast_meets_wcag_aa(): void
    {
        $this->assertGreaterThanOrEqual(4.5, $this->contrast('#c4451c', '#ffffff'));
        $this->assertGreaterThanOrEqual(4.5, $this->contrast('#d96c43', '#141210'));
    }

    private function contrast(string $foreground, string $background): float
    {
        $luminance = function (string $hex): float {
            $channels = str_split(ltrim($hex, '#'), 2);
            $rgb = array_map(function (string $channel): float {
                $value = hexdec($channel) / 255;

                return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            }, $channels);

            return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
        };
        [$lighter, $darker] = [max($luminance($foreground), $luminance($background)), min($luminance($foreground), $luminance($background))];

        return ($lighter + 0.05) / ($darker + 0.05);
    }
}
