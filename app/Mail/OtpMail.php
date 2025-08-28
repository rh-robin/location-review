<?php

namespace App\Mail;
use App\Models\EmailContent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class OtpMail extends Mailable
{

    use Queueable, SerializesModels;
    public int $otp;
    public User $user;
    public string $header_message;
    /**
     * Create a new message instance.
     */
    public function __construct(int $otp,User $user, string $message) {
        $this->otp = $otp;
        $this->user = $user;
        $this->header_message= $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope {
        return new Envelope(
            subject: $this->header_message,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content {
        $emailContent = EmailContent::first(); // Fetch the single record
        return new Content(
            view: 'mail.otpmail',
            with: [
                'company_name' => $emailContent->company_name,
                'company_location' => $emailContent->company_location,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
}
