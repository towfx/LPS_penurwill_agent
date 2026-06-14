<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentExpiryAlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(public Agent $agent) {
        $vars = [
            'AGENT_NAME' => $this->agent->name,
            'EXPIRES_AT' => $this->agent->expires_at ? $this->agent->expires_at->toDateString() : 'N/A',
            'CONFIG_APP_NAME' => config('app.name'),
            'CONFIG_APP_URL' => config('app.url'),
        ];

        $this->template = TemplateEmail::render('agent-expiry-alert', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->getFilledTitle(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-expiry-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
