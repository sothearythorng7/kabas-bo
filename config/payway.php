<?php

return [
    'merchant_id' => env('PAYWAY_MERCHANT_ID', ''),
    'api_key' => env('PAYWAY_API_KEY', ''),
    'refund_url' => env('PAYWAY_REFUND_URL', 'https://checkout-sandbox.payway.com.kh/api/merchant-portal/merchant-access/online-transaction/refund'),
    'rsa_public_key' => env('PAYWAY_RSA_PUBLIC_KEY', ''),
];
