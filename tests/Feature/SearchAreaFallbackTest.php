<?php

namespace Tests\Feature;

use App\Livewire\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchAreaFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_denial_keeps_all_area_fallbacks_available(): void
    {
        Livewire::test(Search::class)
            ->call('geoFailed', 'denied')
            ->assertSee('Izin lokasi belum aktif')
            ->assertSee('Tamalanrea')
            ->assertSee('Panakkukang')
            ->assertSee('Losari/Pantai')
            ->assertSee('Sekitar Unhas')
            ->assertSee('Sekitar UNM/UIN');
    }

    public function test_location_success_changes_state_without_persisting_coordinates(): void
    {
        Livewire::test(Search::class)
            ->call('geoReady', -5.147, 119.432)
            ->assertSet('locationState', 'ready')
            ->assertSee('Lokasimu aktif');

        $source = file_get_contents(resource_path('js/geo.js'));
        $this->assertStringNotContainsString('localStorage', $source);
        $this->assertStringNotContainsString('console.', $source);
    }
}
