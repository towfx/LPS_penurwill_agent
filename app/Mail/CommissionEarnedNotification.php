<?php

namespace App\Mail;

use App\Models\Commission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommissionEarnedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Commission $commission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You earned a new commission',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commission-earned',
            with: [
                'amount' => (float) $this->commission->amount,
                'commissionType' => $this->commission->commission_type,
                'saleId' => $this->commission->sale_id,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
