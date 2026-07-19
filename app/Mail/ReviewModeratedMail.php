<?php

namespace App\Mail;

use App\Domain\Review\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ReviewModeratedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Review $review, public string $decision, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Status reviewmu di ngafe.space');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.review-moderated');
    }
}
