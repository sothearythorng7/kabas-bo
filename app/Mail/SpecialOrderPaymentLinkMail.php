<?php

namespace App\Mail;

use App\Models\WebsiteOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpecialOrderPaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WebsiteOrder $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Link - Order ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.payment-link',
            with: [
                'order' => $this->order,
                'paymentLink' => $this->order->payment_link_url,
            ],
        );
    }
}
