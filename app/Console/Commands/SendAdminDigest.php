<?php

namespace App\Console\Commands;

use App\Domain\Moderation\Models\ContentAppeal;
use App\Domain\Moderation\Models\Report;
use App\Domain\Review\Models\Review;
use App\Mail\AdminDigestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendAdminDigest extends Command
{
    protected $signature = 'moderation:send-digest';

    protected $description = 'Kirim digest hanya bila antrian moderasi tidak kosong';

    public function handle(): int
    {
        $pendingReviews = Review::query()->where('status', 'pending')->count();
        $openReports = Report::query()->where('status', 'open')->count();
        $openAppeals = ContentAppeal::query()->where('status', 'submitted')->count();
        $recipient = config('moderation.admin_email');

        if (($pendingReviews + $openReports + $openAppeals) === 0 || blank($recipient)) {
            $this->info('Tidak ada digest yang perlu dikirim.');

            return self::SUCCESS;
        }

        Mail::to($recipient)->queue(new AdminDigestMail($pendingReviews, $openReports, $openAppeals));
        $this->info('Digest moderasi masuk antrean email.');

        return self::SUCCESS;
    }
}
