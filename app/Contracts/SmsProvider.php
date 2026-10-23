<?php

namespace App\Contracts;

interface SmsProvider
{
    public function name(): string;
    public function send(string $recipient, string $message): SmsResult;
}
