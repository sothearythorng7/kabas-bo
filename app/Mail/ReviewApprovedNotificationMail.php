<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewApprovedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Review $review) {}

    public function envelope(): Envelope
    {
        $isFr = $this->review->language === 'fr';
        return new Envelope(
            subject: $isFr
                ? 'Votre avis est en ligne — merci !'
                : 'Your review is live — thank you!',
        );
    }

    public function content(): Content
    {
        $base = rtrim(config('app.url', 'https://www.kabasconceptstore.com'), '/');
        $product = $this->review->product;
        $slug = is_array(optional($product)->slugs)
            ? ($product->slugs[$this->review->language] ?? $product->slugs['en'] ?? null)
            : null;
        $productUrl = $slug ? ($base . '/' . $this->review->language . '/product/' . $slug . '#reviews') : $base;
        $productUrl .= (str_contains($productUrl, '?') ? '&' : '?')
            . 'utm_source=email&utm_medium=lifecycle&utm_campaign=review_approved';

        $unsubscribeUrl = $base . '/' . $this->review->language . '/contact?subject=unsubscribe-reviews&email=' . urlencode($this->review->customer_email);

        return new Content(
            view: 'emails.reviews.approved_notification',
            with: [
                'review'      => $this->review,
                'productName' => is_array(optional($product)->name)
                    ? ($product->name[$this->review->language] ?? '?')
                    : (optional($product)->name ?? '?'),
                'productUrl'  => $productUrl,
                'imageBaseUrl' => $base,
                'locale'      => $this->review->language,
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }
}
