<?php

namespace App\Services\Payments;

use App\Contracts\GatewayVerification;
use App\Contracts\PaymentGateway;
use App\Models\PaymentAttempt;

class SandboxPaymentGateway implements PaymentGateway
{
    public function name(): string { return 'sandbox'; }
    public function redirectUrl(PaymentAttempt $attempt): string { return route('online-payment.sandbox', [$attempt->invoice->payment_token, $attempt->authority]); }
    public function verify(PaymentAttempt $attempt, array $callback): GatewayVerification
    {
        if (($callback['status'] ?? null) !== 'OK') return new GatewayVerification(false, message: 'پرداخت توسط کاربر لغو شد.');
        return new GatewayVerification(true, 'SBX-'.now()->format('ymdHis').'-'.$attempt->id, 'پرداخت آزمایشی تأیید شد.');
    }
}
