<?php

return [
    'merchant_id' => env('PAYWAY_MERCHANT_ID', ''),
    'api_key' => env('PAYWAY_API_KEY', ''),
    'api_url' => env('PAYWAY_API_URL', 'https://checkout.payway.com.kh/api/payment-gateway/v1/payments'),
    'checkout_js' => env('PAYWAY_CHECKOUT_JS', 'https://checkout.payway.com.kh/plugins/checkout2-0.js'),
    'refund_url' => env('PAYWAY_REFUND_URL', 'https://checkout.payway.com.kh/api/merchant-portal/merchant-access/online-transaction/refund'),
    'rsa_public_key' => env('PAYWAY_RSA_PUBLIC_KEY', ''),
];
