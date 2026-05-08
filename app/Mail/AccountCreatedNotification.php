<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Agent $agent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Agent Account Has Been Created');
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
