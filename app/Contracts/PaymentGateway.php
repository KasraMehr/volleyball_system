<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;

interface PaymentGateway
{
    public function name(): string;
    public function redirectUrl(PaymentAttempt $attempt): string;
    public function verify(PaymentAttempt $attempt, array $callback): GatewayVerification;
}
