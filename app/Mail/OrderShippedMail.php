<?php

namespace App\Mail;

use App\Models\WebsiteOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WebsiteOrder $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Order Has Been Shipped - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.shipped',
            with: [
                'order' => $this->order,
                'imageBaseUrl' => 'https://www.kabasconceptstore.com',
            ],
        );
    }
}
