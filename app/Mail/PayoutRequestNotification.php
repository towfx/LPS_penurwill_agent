<?php

namespace App\Mail;

use App\Models\Payout;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Payout $payout
    ) {
        $vars = [
            'PAYOUT_ID' => $this->payout->id,
            'AGENT_NAME' => $this->payout->agent->name,
            'PAYOUT_AMOUNT' => number_format((float) $this->payout->amount, 2),
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('payout-request-notification', $vars);
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
            view: 'emails.payout-request-notification',
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
