<?php

return [
    'default' => env('SMS_PROVIDER', 'sandbox'),
    'enabled' => (bool) env('SMS_ENABLED', true),
    'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),
];
