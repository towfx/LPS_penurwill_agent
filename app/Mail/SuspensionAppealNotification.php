<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspensionAppealNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(
        public Agent $agent,
        public string $message
    ) {
        $vars = [
            'AGENT_NAME' => $this->agent->name,
            'APPEAL_MESSAGE' => $this->message,
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('suspension-appeal-notification', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->getFilledTitle());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.suspension-appeal-notification');
    }

    public function attachments(): array
    {
        return [];
    }
}
