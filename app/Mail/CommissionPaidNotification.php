<?php

namespace App\Mail;

use App\Models\Commission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommissionPaidNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Commission $commission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your commission has been paid',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commission-paid',
            with: [
                'amount' => (float) $this->commission->amount,
                'paidAt' => $this->commission->paid_at?->toDateTimeString(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
