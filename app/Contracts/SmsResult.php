<?php

namespace App\Contracts;

final readonly class SmsResult
{
    public function __construct(public bool $successful, public ?string $messageId = null, public ?string $error = null) {}
}
