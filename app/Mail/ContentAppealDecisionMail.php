<?php

namespace App\Mail;

use App\Domain\Moderation\Models\ContentAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ContentAppealDecisionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContentAppeal $appeal) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Keputusan keberatan konten ngafe.space');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.content-appeal-decision');
    }
}
