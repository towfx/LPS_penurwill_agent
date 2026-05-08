<?php

namespace App\Mail;

use App\Models\RegistrationVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RegistrationVerification $verification
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Verification Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-verification-code');
    }

    public function attachments(): array
    {
        return [];
    }
}
