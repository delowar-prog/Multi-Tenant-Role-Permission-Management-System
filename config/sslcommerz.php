<?php
return [
    'store_id' => env('SSLCOMMERZ_STORE_ID'),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
    'sandbox' => env('SSLCOMMERZ_MODE') === 'sandbox', //change to productions
];
