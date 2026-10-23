<?php

namespace App\Jobs;

use App\Contracts\SmsProvider;
use App\Models\SmsMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SendSmsMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120, 600];

    public function __construct(public int $smsMessageId) { $this->onQueue('notifications'); }

    public function handle(SmsProvider $provider): void
    {
        $sms = SmsMessage::query()->findOrFail($this->smsMessageId);
        if ($sms->status === 'sent') return;
        $sms->increment('attempts');
        $result = $provider->send($sms->recipient, $sms->message);
        if (! $result->successful) throw new RuntimeException($result->error ?? 'SMS provider rejected the message.');
        $sms->update(['status' => 'sent', 'provider' => $provider->name(), 'provider_message_id' => $result->messageId, 'sent_at' => now(), 'failed_at' => null, 'last_error' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        SmsMessage::query()->whereKey($this->smsMessageId)->update(['status' => 'failed', 'failed_at' => now(), 'last_error' => mb_substr((string) $exception?->getMessage(), 0, 1000)]);
    }
}
