<?php

namespace App\Mail;

use App\Models\Payout;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutPaidNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Payout $payout
    ) {
        $formattedAmount = number_format($this->payout->amount, 2);

        $vars = [
            'PAYOUT_ID' => $this->payout->id,
            'PAYOUT_AMOUNT' => $formattedAmount,
            'PAYOUT_PAID_AT' => $this->payout->paid_at ? $this->payout->paid_at->format('d M Y, h:i A') : 'N/A',
            'CONFIG_APP_NAME' => config('app.name'),
        ];

        $this->template = TemplateEmail::render('payout-paid-notification', $vars);
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
            view: 'emails.payout-paid-notification',
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
