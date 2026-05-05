<?php

namespace App\Mail;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentRenewalReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Agent $agent) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Penurwill membership renewal is coming up',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-renewal-reminder',
            with: [
                'agentName' => $this->agent->name,
                'expiresAt' => $this->agent->expires_at?->toDateString(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
