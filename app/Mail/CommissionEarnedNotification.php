<?php

namespace App\Mail;

use App\Models\Commission;
use App\Models\TemplateEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommissionEarnedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TemplateEmail $template;

    public function __construct(public Commission $commission) {
        $vars = [
            'COMMISSION_AMOUNT' => number_format((float) $this->commission->amount, 2),
            'COMMISSION_TYPE' => $this->commission->commission_type,
            'SALE_ID' => (string) $this->commission->sale_id,
            'CONFIG_APP_NAME' => config('app.name'),
            'CONFIG_APP_URL' => config('app.url'),
        ];

        $this->template = TemplateEmail::render('commission-earned', $vars);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->getFilledTitle(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commission-earned',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
