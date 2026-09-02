<?php

namespace App\Mail;

use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public StoreSetting $store,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Struk '.$this->sale->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sale-receipt',
        );
    }
}
