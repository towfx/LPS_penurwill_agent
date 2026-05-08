<?php

namespace App\Mail;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspensionAppealNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agent $agent,
        public string $message
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Suspension Appeal — {$this->agent->name}");
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
