<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use App\Contracts\SmsResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SandboxSmsProvider implements SmsProvider
{
    public function name(): string { return 'sandbox'; }
    public function send(string $recipient, string $message): SmsResult
    {
        Log::channel(config('sms.log_channel'))->info('Sandbox SMS delivered', ['recipient' => $recipient, 'characters' => mb_strlen($message)]);
        return new SmsResult(true, 'SMS-'.Str::upper(Str::random(12)));
    }
}
