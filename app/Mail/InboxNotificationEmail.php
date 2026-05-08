<?php

namespace App\Mail;

use App\Models\AgentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InboxNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AgentNotification $notification
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notification->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inbox-notification');
    }

    public function attachments(): array
    {
        return [];
    }
}
