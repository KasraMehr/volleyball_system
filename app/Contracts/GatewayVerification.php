<?php

namespace App\Contracts;

final readonly class GatewayVerification
{
    public function __construct(public bool $successful, public ?string $reference = null, public ?string $message = null) {}
}
