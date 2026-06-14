<?php

namespace App\Mail;

use App\Models\Payout;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutCancelledNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(
        public Payout $payout
    ) {
        $vars = [
            'PAYOUT_ID' => $this->payout->id,
            'AGENT_NAME' => $this->payout->agent->name,
            'PAYOUT_AMOUNT' => number_format($this->payout->amount, 2),
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('payout-cancelled-notification', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->getFilledTitle());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payout-cancelled-notification');
    }

    public function attachments(): array
    {
        return [];
    }
}
