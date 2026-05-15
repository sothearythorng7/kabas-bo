<?php

return [
    // Section title on product page
    'title' => 'Customer Reviews',
    'subtitle' => 'Verified reviews from real buyers — all manually moderated.',
    'count' => '{0} no reviews|{1} based on 1 review|[2,*] based on :count verified reviews',
    'recommend' => ':pct% recommend this product',
    'purchased_recently' => 'Reviews from customers who purchased recently',
    'verified' => '✓ Verified',
    'reply_label' => 'Reply from Kabas',
    'load_more' => 'Show more reviews',
    'empty' => 'No reviews yet.',
    'empty_invite' => 'Be the first to share your experience with this product.',

    // Public form
    'form_title' => 'Write a review',
    'form_subtitle' => 'About your order :order',
    'form_verified_note' => 'Your review will be marked as a verified purchase since you ordered this item.',
    'choose_product' => 'Which product do you want to review?',
    'your_rating' => 'Your rating',
    'review_title' => 'Review title',
    'review_title_placeholder' => 'e.g. Best pepper I\'ve ever tried',
    'review_body' => 'Your review',
    'review_body_placeholder' => 'Tell us what you loved (or didn\'t) about this product…',
    'review_body_help' => 'Minimum 20 characters. Your review will appear after moderation (usually within 24h).',
    'your_name' => 'Display name',
    'your_email' => 'Email',
    'email_help' => 'We won\'t publish your email. Used only to contact you if needed.',
    'submit_button' => 'Submit review',

    // Tokens / errors
    'token_invalid' => 'This review link is invalid.',
    'token_expired' => 'This review link has expired or has already been used.',
    'no_products' => 'No products eligible for review on this order.',
    'thanks_message' => 'Thanks! Your review will appear shortly.',
    'thanks_title' => 'Thank you!',
    'thanks_body' => 'Your review has been submitted and will appear after a quick moderation. We really appreciate your feedback.',
    'back_home' => 'Back to shop',

    // Validation
    'validation' => [
        'body_min' => 'Please share at least 20 characters in your review.',
        'body_max' => 'Your review is too long (max 3000 characters).',
        'rating_between' => 'Please give a rating between 1 and 5 stars.',
        'recaptcha_failed' => 'Spam check failed. Please try again or contact us.',
    ],

    // Admin
    'admin' => [
        'approved' => 'Review approved.',
        'rejected' => 'Review rejected.',
        'reply_saved' => 'Reply saved.',
        'deleted' => 'Review deleted.',
    ],
];
