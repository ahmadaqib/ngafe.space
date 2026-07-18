<?php
namespace Tests\Feature\Cafe;
use App\Domain\Cafe\Models\Cafe; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class ShowPageTest extends TestCase { use RefreshDatabase; public function test_only_active_cafes_are_public(): void {$active=Cafe::factory()->create(['slug'=>'aktif','status'=>'active']);$pending=Cafe::factory()->create(['slug'=>'pending','status'=>'pending']);$this->get('/makassar/aktif')->assertOk()->assertSee($active->name);$this->get('/makassar/pending')->assertNotFound();} }
