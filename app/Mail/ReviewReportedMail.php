<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewReportedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $report;
    public $review;
    public $location;
    public $reporter;

    /**
     * Create a new message instance.
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
        $this->review = $report->review;
        $this->location = $report->review->location ?? null;
        $this->reporter = $report->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Review Has Been Reported'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.review_reported'
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
