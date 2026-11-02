<?php

return [
    // ISO currency code and symbol used across the calculator, API and PDF.
    'currency' => env('QUOTECALC_CURRENCY', 'GBP'),
    'currency_symbol' => env('QUOTECALC_CURRENCY_SYMBOL', '£'),

    // VAT rate in basis points (2000 = 20%).
    'vat_rate_bps' => (int) env('QUOTECALC_VAT_RATE_BPS', 2000),

    // Net prices are rounded to this step in cents (1 = to the penny, 100 = whole pounds).
    'rounding_step_cents' => (int) env('QUOTECALC_ROUNDING_STEP_CENTS', 1),

    // Who receives "new quote request" emails.
    'manager_email' => env('QUOTECALC_MANAGER_EMAIL', 'sales@example.com'),

    // Shared secret for the REST API (sent as the X-Api-Key header). Empty disables the API.
    'api_key' => env('QUOTECALC_API_KEY'),

    // How long a quote is valid, shown on the PDF.
    'valid_days' => (int) env('QUOTECALC_VALID_DAYS', 30),

    'company' => [
        'name' => env('QUOTECALC_COMPANY_NAME', 'Demo Sign & Print Ltd'),
        'address' => env('QUOTECALC_COMPANY_ADDRESS', '12 Example Street, London'),
        'email' => env('QUOTECALC_COMPANY_EMAIL', 'hello@example.com'),
    ],
];
