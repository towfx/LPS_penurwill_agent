<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentRegisteredNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Agent $agent
    ) {
        $vars = [
            'AGENT_NAME' => $this->agent->name,
            'PARTNER_COMPANY_NAME' => $this->agent->partner ? $this->agent->partner->company_name : 'N/A',
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('agent-registered-notification', $vars);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->getFilledTitle(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-registered-notification',
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
