<?php

return [
    'tagline' => 'Every Cake Has a Story.',
    'support_email' => env('CAKELLE_SUPPORT_EMAIL', 'hello@cakelle.ph'),
    'support_phone' => env('CAKELLE_SUPPORT_PHONE', '+63 917 000 0000'),
    'order_payment_window_minutes' => (int) env('CAKELLE_PAYMENT_WINDOW', 45),
    'schedule_days_ahead' => 90,
    'platform_fee_percent' => (float) env('CAKELLE_PLATFORM_FEE', 0),
];
