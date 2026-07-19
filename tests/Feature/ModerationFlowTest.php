<?php

namespace Tests\Feature;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Moderation\Actions\DecideContentAppeal;
use App\Domain\Moderation\Actions\ModeratePhoto;
use App\Domain\Moderation\Actions\ModerateReview;
use App\Domain\Moderation\Actions\RevealReviewIdentity;
use App\Domain\Moderation\Actions\SubmitContentAppeal;
use App\Domain\Moderation\Actions\SubmitReport;
use App\Domain\Moderation\Exceptions\ContentAppealLimitExceeded;
use App\Domain\Moderation\Models\ContentAppeal;
use App\Domain\Moderation\Models\ReportReason;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewStatus;
use App\Exceptions\DomainException;
use App\Mail\AdminDigestMail;
use App\Mail\ContentAppealDecisionMail;
use App\Mail\ReviewModeratedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\AssertsNoPii;
use Tests\TestCase;

class ModerationFlowTest extends TestCase
{
    use AssertsNoPii, RefreshDatabase;

    public function test_three_unique_reports_suspend_review_and_duplicate_reporter_is_counted_once(): void
    {
        $review = $this->review();
        $action = app(SubmitReport::class);
        $firstReporter = User::factory()->create();
        $action->handle($firstReporter, $review, ReportReason::Spam);
        $action->handle($firstReporter, $review, ReportReason::Kasar);
        $action->handle(User::factory()->create(), $review, ReportReason::Kasar);
        $this->assertSame(ReviewStatus::Published, $review->fresh()->status);

        $action->handle(User::factory()->create(), $review, ReportReason::MembukaIdentitas);

        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);
        $this->assertSame(3, $review->reports()->count());
    }

    public function test_info_salah_is_prioritized_during_active_seasonal_override(): void
    {
        $review = $this->review([
            'opening_hours_override' => [[
                'label' => 'Jam khusus Ramadan', 'date_start' => now()->subDay()->toDateString(),
                'date_end' => now()->addDay()->toDateString(), 'hours' => '16:00-23:00',
            ]],
        ]);

        $report = app(SubmitReport::class)->handle(User::factory()->create(), $review, ReportReason::InfoSalah);

        $this->assertTrue($report->priority);
    }

    public function test_admin_moderation_sends_email_and_audits_actions_including_identity_reveal(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $review = $this->review();

        app(ModerateReview::class)->handle($admin, $review, 'takedown', 'Konten tidak membahas pengalaman di cafe.');
        Mail::assertQueued(ReviewModeratedMail::class);
        $this->assertSame(ReviewStatus::Removed, $review->fresh()->status);
        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'review.takedown', 'subject_id' => $review->id]);

        $identity = app(RevealReviewIdentity::class)->handle($admin, $review, 'Menindaklanjuti keberatan konten resmi.');
        $this->assertSame($review->user->email, $identity['email']);
        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'review.reveal_identity', 'subject_id' => $review->id]);
    }

    public function test_ban_decision_disables_author_account_and_review(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $review = $this->review();

        app(ModerateReview::class)->handle($admin, $review, 'ban', 'Serangan spam berulang yang sudah diverifikasi.');

        $this->assertSame('banned', $review->user->fresh()->status);
        $this->assertSame(ReviewStatus::Removed, $review->fresh()->status);
    }

    public function test_guest_appeal_suspends_content_decision_is_recorded_and_only_one_appeal_is_allowed(): void
    {
        Mail::fake();
        $review = $this->review();
        $appealAction = app(SubmitContentAppeal::class);
        $appeal = $appealAction->handle($review, 'Pemilik Cafe', 'owner@example.test', 'Review memuat tuduhan yang tidak sesuai dengan bukti transaksi kami.');
        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);

        $admin = User::factory()->create(['role' => 'admin']);
        app(DecideContentAppeal::class)->handle($admin, $appeal, 'content_restored', 'Review dipertahankan karena berisi pengalaman personal yang relevan.');
        Mail::assertQueued(ContentAppealDecisionMail::class);
        $appealAction->appealOnce($appeal->fresh(), 'owner@example.test', 'Kami mengajukan satu kali banding dengan bukti tambahan yang baru.');

        $this->assertSame(1, $appeal->fresh()->appeal_count);
        $this->expectException(HttpException::class);
        $appealAction->appealOnce($appeal->fresh(), 'owner@example.test', 'Banding kedua harus ditolak oleh sistem.');
    }

    public function test_public_appeal_page_exposes_alias_but_never_author_identity(): void
    {
        $review = $this->review();
        $author = $review->user;

        $response = $this->get(route('content-appeal', $review));

        $response->assertOk()->assertSee($review->display_alias);
        $this->assertNoPii($response, $author);
    }

    public function test_public_appeal_is_idempotent_and_email_verification_is_rate_limited(): void
    {
        $action = app(SubmitContentAppeal::class);
        $review = $this->review();
        $first = $action->handle($review, 'Pemilik Cafe', 'owner@example.test', 'Pengajuan keberatan pertama dengan alasan yang cukup panjang untuk ditinjau.');
        $duplicate = $action->handle($review, 'Pemilik Cafe', 'OWNER@example.test', 'Payload ulang tidak boleh membuat pengajuan kedua yang identik.');

        $this->assertTrue($first->is($duplicate));
        $this->assertSame(1, ContentAppeal::query()->count());

        foreach (range(1, 5) as $attempt) {
            try {
                $action->appealOnce($first, "wrong-{$attempt}@example.test", 'Percobaan email salah dengan alasan yang panjang dan tidak boleh diterima.');
            } catch (HttpException) {
                // Expected while the verification budget is still available.
            }
        }

        $this->expectException(ContentAppealLimitExceeded::class);
        $action->appealOnce($first, 'owner@example.test', 'Percobaan keenam diblokir sebelum verifikasi email diproses.');
    }

    public function test_public_appeal_is_limited_to_three_submissions_per_email_per_day(): void
    {
        $action = app(SubmitContentAppeal::class);
        foreach (range(1, 3) as $attempt) {
            $action->handle(
                $this->review(),
                'Pelapor Beritikad Baik',
                'rate-limited-owner@example.test',
                "Keberatan nomor {$attempt} memiliki alasan yang cukup panjang untuk masuk proses peninjauan.",
            );
        }

        $this->expectException(ContentAppealLimitExceeded::class);
        $action->handle(
            $this->review(),
            'Pelapor Beritikad Baik',
            'rate-limited-owner@example.test',
            'Pengajuan keempat pada hari yang sama harus dihentikan oleh pembatas laju.',
        );
    }

    public function test_report_daily_limit_and_photo_kill_switch_are_enforced(): void
    {
        Mail::fake();
        $reporter = User::factory()->create();
        foreach (range(1, 10) as $attempt) {
            RateLimiter::hit("report:day:{$reporter->id}", 86400);
        }
        try {
            app(SubmitReport::class)->handle($reporter, $this->review(), ReportReason::Spam);
            $this->fail('Report limit was not enforced.');
        } catch (DomainException) {
            $this->assertTrue(true);
        }

        $review = $this->review();
        $photo = Photo::factory()->create([
            'review_id' => $review->id,
            'cafe_id' => $review->cafe_id,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        app(ModeratePhoto::class)->handle($admin, $photo, 'takedown', 'Foto membuka identitas pengunjung tanpa izin.');
        $this->assertSame('removed', $photo->fresh()->status);
        $this->assertDatabaseHas('moderation_audit_logs', ['action' => 'photo.takedown', 'subject_id' => $photo->id]);
    }

    public function test_digest_is_sent_only_when_queue_exists(): void
    {
        Mail::fake();
        config()->set('moderation.admin_email', 'admin@example.test');
        $this->artisan('moderation:send-digest')->assertSuccessful();
        Mail::assertNothingQueued();

        Review::factory()->create(['status' => 'pending']);
        $this->artisan('moderation:send-digest')->assertSuccessful();
        Mail::assertQueued(AdminDigestMail::class);
    }

    private function review(array $cafeOverrides = []): Review
    {
        return Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'cafe_id' => Cafe::factory()->create($cafeOverrides)->id,
            'status' => 'published',
        ]);
    }
}
