<?php


return [
    'test_mode' => env('UPAY_TESTMODE', true),
    'base_url' => env('UPAY_BASEURL','https://api-cib.ukrpayments.com'),
    'merchant_id' => env('UPAY_MERCHANT_ID'),
    'terminal_id' => env('UPAY_TERMINAL_ID'),
    'api_token' => env('UPAY_API_TOKEN'),
    'api_secret' => env('UPAY_API_SECRET'),
    'payform_id' => env('UPAY_PAYFORM_ID'),
    'mcc' => env('UPAY_MCC'),
    'site_url' => env('UPAY_SITE_URL'),
    'site_url_test' => env('UPAY_SITE_URL_TEST', env('APP_URL') . '/'),

    //Настройки комиссий
    'p2_fee_procent' => env('UPAY_P2P_FEE_PROCENT', 0), //комиссия в процентах (например, 0.04)
    'p2_fee_uah' => env('UPAY_P2P_FEE_UAH', 0), // сумма указывается в копейках (например, 200)
    'p2_min_amount' => env('UPAY_P2P_MIN_AMOUNT', 500), // сумма указывается в копейках (например, 500)
    'p2_max_amount' => env('UPAY_P2P_MIN_AMOUNT', 2900000), // сумма указывается в копейках (например, 500)

];
