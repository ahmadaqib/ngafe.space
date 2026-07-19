<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Livewire\ReviewForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewFormFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_sheet_copy_and_exact_review_intent(): void
    {
        $cafe = Cafe::factory()->create();

        Livewire::test(ReviewForm::class, ['cafe' => $cafe])
            ->assertSee('Login sebentar biar reviewmu tersimpan')
            ->assertSee('Lanjut dengan Google');
    }

    public function test_authenticated_user_completes_three_steps_and_sees_peak_end_screen(): void
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();

        Livewire::actingAs($user)->test(ReviewForm::class, ['cafe' => $cafe])
            ->set('rating', 5)
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('body', 'Wifinya stabil, kursinya nyaman, dan saya betah bekerja hampir tiga jam.')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertDispatched('review-submitted')
            ->assertSee('Wifinya stabil')
            ->assertSee('Mau review cafe lain');

        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'cafe_id' => $cafe->id]);
    }

    public function test_existing_review_opens_in_edit_mode(): void
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $user->reviews()->create([
            'cafe_id' => $cafe->id, 'rating' => 3, 'body' => 'Cerita lama yang cukup panjang untuk diedit kembali dari formulir.',
            'display_alias' => 'Alias Lama', 'status' => 'published',
        ]);

        Livewire::actingAs($user)->test(ReviewForm::class, ['cafe' => $cafe])
            ->assertSee('Edit reviewmu')
            ->assertSet('rating', 3);
    }
}
