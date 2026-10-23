<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'user_id', 'club_notification_id', 'provider', 'recipient', 'message', 'status', 'provider_message_id', 'attempts', 'last_error', 'queued_at', 'sent_at', 'failed_at'])]
class SmsMessage extends Model
{
    protected $table = 'sms_outbox';
    protected function casts(): array { return ['queued_at' => 'datetime', 'sent_at' => 'datetime', 'failed_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function notification(): BelongsTo { return $this->belongsTo(ClubNotification::class, 'club_notification_id'); }
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
}
