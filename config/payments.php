<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'sandbox'),
    'attempt_ttl' => (int) env('PAYMENT_ATTEMPT_TTL', 20),
];
