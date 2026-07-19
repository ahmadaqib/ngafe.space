<?php

namespace Tests\Unit\Actions;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Actions\DeleteAccount;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Actions\EditReview;
use App\Domain\Review\Actions\SubmitReview;
use App\Domain\Review\Exceptions\DuplicateReview;
use App\Domain\Review\Exceptions\DuplicateReviewContent;
use App\Domain\Review\Exceptions\ReviewLimitExceeded;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use App\Domain\Review\Support\ReviewGuards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmitReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_review_is_published_with_a_stored_anonymous_alias(): void
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();

        $review = app(SubmitReview::class)->handle($user, $cafe, 5, 'Wifi stabil, colokan banyak, dan meja nyaman untuk bekerja cukup lama.');

        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertNotEmpty($review->display_alias);
        $this->assertStringNotContainsString($user->email, $review->display_alias);
        $this->assertSame(1, $cafe->fresh()->rating_count);
    }

    public function test_second_review_for_same_cafe_is_rejected_but_edit_updates_the_existing_row(): void
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $review = app(SubmitReview::class)->handle($user, $cafe, 4, 'Suasananya tenang dan minumannya pas untuk menemani kerja sampai sore.');

        try {
            app(SubmitReview::class)->handle($user, $cafe, 3, 'Cerita kedua yang seharusnya tidak membuat review baru untuk cafe yang sama.');
            $this->fail('Duplicate review was not rejected.');
        } catch (DuplicateReview) {
            $edited = app(EditReview::class)->handle($user, $review, 5, 'Setelah datang lagi, wifi makin stabil dan pelayanannya terasa lebih cepat.');
        }

        $this->assertTrue($edited->is_edited);
        $this->assertSame(5, $edited->rating);
        $this->assertSame(1, Review::query()->where('cafe_id', $cafe->id)->count());
    }

    public function test_banned_words_and_new_account_one_star_burst_are_auto_flagged(): void
    {
        $cafeWithBadWord = Cafe::factory()->create();
        $badWord = app(SubmitReview::class)->handle(User::factory()->create(), $cafeWithBadWord, 2, 'Pelayanannya sundala sekali dan pengalaman ini benar-benar tidak menyenangkan.');
        $this->assertSame(ReviewStatus::Pending, $badWord->status);

        $target = Cafe::factory()->create();
        Review::factory()->count(2)->sequence(
            ['user_id' => User::factory()->create(['created_at' => now()])->id],
            ['user_id' => User::factory()->create(['created_at' => now()])->id],
        )->create(['cafe_id' => $target->id, 'rating' => 1]);
        $burst = app(SubmitReview::class)->handle(
            User::factory()->create(['created_at' => now()]),
            $target,
            1,
            'Akun baru ini ikut memberi satu bintang dalam pola serangan yang beruntun.',
        );
        $this->assertSame(ReviewStatus::Pending, $burst->status);
    }

    public function test_duplicate_content_and_honeypot_are_rejected(): void
    {
        $user = User::factory()->create();
        $body = 'Isi review yang sama persis tidak boleh disalin ke berbagai cafe berbeda.';
        app(SubmitReview::class)->handle($user, Cafe::factory()->create(), 4, $body);

        $this->expectException(DuplicateReviewContent::class);
        app(SubmitReview::class)->handle($user, Cafe::factory()->create(), 4, "  {$body}  ");
    }

    public function test_honeypot_guard_rejects_bot_payload(): void
    {
        $this->expectException(DuplicateReviewContent::class);
        app(ReviewGuards::class)->assertHoneypotEmpty('https://spam.example');
    }

    public function test_banned_user_cannot_edit_an_existing_review(): void
    {
        $user = User::factory()->create(['status' => 'banned']);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'cafe_id' => Cafe::factory()->create()->id,
            'status' => 'removed',
        ]);

        $this->expectException(ReviewLimitExceeded::class);
        app(EditReview::class)->handle(
            $user,
            $review,
            5,
            'User yang diban tidak boleh memulihkan review dengan mengedit konten lama.',
        );
    }

    public function test_account_deletion_can_anonymize_or_delete_reviews(): void
    {
        $anonymizedUser = User::factory()->create();
        $anonymizedCafe = Cafe::factory()->create();
        $review = app(SubmitReview::class)->handle($anonymizedUser, $anonymizedCafe, 4, 'Review ini dipertahankan anonim setelah akun pemiliknya dihapus permanen.');
        app(DeleteAccount::class)->handle($anonymizedUser, 'anonymize');
        $this->assertNull($review->fresh()->user_id);
        $this->assertSame(1, $anonymizedCafe->fresh()->rating_count);

        $deletedUser = User::factory()->create();
        $deletedCafe = Cafe::factory()->create();
        $deletedReview = app(SubmitReview::class)->handle($deletedUser, $deletedCafe, 4, 'Review ini ikut dihapus sesuai pilihan pemilik akun saat menutup akun.');
        app(DeleteAccount::class)->handle($deletedUser, 'delete');
        $this->assertDatabaseMissing('reviews', ['id' => $deletedReview->id]);
        $this->assertSame(0, $deletedCafe->fresh()->rating_count);
    }
}
