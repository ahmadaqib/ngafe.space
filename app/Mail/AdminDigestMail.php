<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AdminDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public int $pendingReviews, public int $openReports, public int $openAppeals) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Digest moderasi harian ngafe.space');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.admin-digest');
    }
}
