<?php

namespace App\Mail;

use App\Models\RegistrationVerification;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(
        public RegistrationVerification $verification
    ) {
        $vars = [
            'VERIFICATION_CODE' => (string) $this->verification->code,
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('email-verification-code', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->getFilledTitle());
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
