<?php

namespace App\Mail;

use App\Models\AgentNotification;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InboxNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(
        public AgentNotification $notification
    ) {
        $vars = [
            'AGENT_NAME' => $this->notification->agent->name,
            'NOTIFICATION_SUBJECT' => $this->notification->subject,
            'NOTIFICATION_BODY' => $this->notification->body,
            'CONFIG_APP_NAME' => config('app.name'),
            'CONFIG_APP_URL' => config('app.url'),
        ];

        $this->template = TemplateEmail::render('inbox-notification', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->getFilledTitle());
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
