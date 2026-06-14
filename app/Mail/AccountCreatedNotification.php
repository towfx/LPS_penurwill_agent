<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\User;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(
        public User $user,
        public Agent $agent
    ) {
        $vars = [
            'AGENT_NAME' => $this->agent->name,
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('account-created-notification', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->getFilledTitle());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-created-notification');
    }

    public function attachments(): array
    {
        return [];
    }
}
